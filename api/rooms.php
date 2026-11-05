<?php 
require __DIR__ . '/../config.php';

$user = current_user();
if (!$user) json_out(['error' => 'Please log in again.'], 401);

$pdo = db();
$action = $_GET['action'] ?? '';
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$in = $isPost ? (json_decode(file_get_contents('php://input'), true) ?: []) : [];

if ($isPost) {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')) json_out(['error' => 'Invalid session token.'], 403);
    if (in_array($action, ['add', 'update', 'delete', 'set_status'], true) && !in_array($user['role'], ['admin', 'manager'], true)) {
        json_out(['error' => 'Only admin/manager can change rooms.'], 403);
    }
}

const PAY_METHODS = ['Cash', 'Mobile Money', 'Card'];

function room_fields(array $in): array {
    $num = strtoupper(trim((string)($in['room_number'] ?? '')));
    $cap = (int)($in['capacity'] ?? 0);
    $short = (float)($in['short_price'] ?? 7000);
    $long = (float)($in['long_price'] ?? 15000);
    $desc = trim((string)($in['description'] ?? ''));
    
    if (!preg_match('/^[A-Z0-9_\-]{1,20}$/', $num)) json_out(['error' => 'Room number: up to 20 letters, numbers or dashes.'], 422);
    if ($cap < 1 || $cap > 20) json_out(['error' => 'Capacity must be between 1 and 20.'], 422);
    if ($short <= 0 || $long <= 0) json_out(['error' => 'Enter both the short and the long price (above 0).'], 422);
    if (mb_strlen($desc) > 255) json_out(['error' => 'Description is too long.'], 422);
    
    return [$num, $cap, $short, $long, $desc];
}

function paid_for(PDO $pdo, int $bookingId): float {
    $s = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM room_payments WHERE booking_id = ?");
    $s->execute([$bookingId]);
    return (float)$s->fetchColumn();
}

$fail = function (string $msg, int $code = 422) use ($pdo) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_out(['error' => $msg], $code);
};

try {
    switch ($action) {
        case 'list':
            $q = trim($_GET['q'] ?? '');
            $st = (string)($_GET['status'] ?? '');
            $sql = "SELECT r.room_id, r.room_number, r.capacity, r.short_price, r.long_price, r.description, r.status, r.status_note,
                    b.booking_id, b.booking_no, b.booking_type, b.guest_name, b.guest_phone, b.guests, b.check_in, b.nights, b.total_amount,
                    DATE_ADD(b.check_in, INTERVAL b.nights DAY) AS due_out,
                    COALESCE((SELECT SUM(p.amount) FROM room_payments p WHERE p.booking_id = b.booking_id), 0) AS paid
                    FROM rooms r
                    LEFT JOIN room_bookings b ON b.room_id = r.room_id AND b.status = 'active'
                    WHERE r.is_active = 1";
            $pr = [];
            if (in_array($st, ['available', 'occupied', 'maintenance'], true)) { $sql .= " AND r.status = :s"; $pr[':s'] = $st; }
            if ($q !== '') {
                $sql .= " AND (r.room_number LIKE :q1 OR b.guest_name LIKE :q2)";
                $pr[':q1'] = $pr[':q2'] = "%$q%";
            }
            $sql .= " ORDER BY LENGTH(r.room_number), r.room_number";
            $s = $pdo->prepare($sql);
            $s->execute($pr);
            $rows = $s->fetchAll();
            foreach ($rows as &$r) {
                $r['balance'] = $r['booking_id'] ? max(0, (float)$r['total_amount'] - (float)$r['paid']) : 0;
            }
            unset($r);
            $stats = $pdo->query("SELECT COUNT(*) total, COALESCE(SUM(status = 'available'), 0) available, COALESCE(SUM(status = 'occupied'), 0) occupied, COALESCE(SUM(status = 'maintenance'), 0) maintenance FROM rooms WHERE is_active = 1")->fetch();
            json_out(['rows' => $rows, 'stats' => $stats]);
            break;

        case 'book':
            $rid = (int)($in['room_id'] ?? 0);
            $guest = strtoupper(trim((string)($in['guest_name'] ?? '')));
            $phone = trim((string)($in['guest_phone'] ?? ''));
            $idno = strtoupper(trim((string)($in['guest_id_no'] ?? '')));
            $guests = (int)($in['guests'] ?? 1);
            $nights = (int)($in['nights'] ?? 1);
            $method = (string)($in['method'] ?? 'Cash');
            $paid = round((float)($in['paid'] ?? 0), 2);
            $type = (string)($in['booking_type'] ?? 'short');

            if (!in_array($type, ['short', 'long'], true)) json_out(['error' => 'Choose a short or long stay.'], 422);
            if ($type === 'short') $nights = 1;

            if ($rid < 1) json_out(['error' => 'Pick a room first.'], 422);
            if (mb_strlen($guest) < 2 || mb_strlen($guest) > 100) json_out(['error' => 'Enter the guest name.'], 422);
            
            // NEW: Phone number is now required
            if ($phone === '') {
                json_out(['error' => 'Phone number is required.'], 422);
            }
            if (!preg_match('/^[0-9+() \-]{7,25}$/', $phone)) {
                json_out(['error' => 'Phone number has invalid characters or is too short.'], 422);
            }
            
            if (mb_strlen($idno) > 40) json_out(['error' => 'ID / passport number is too long.'], 422);
            if ($nights < 1 || $nights > 365) json_out(['error' => 'Nights must be between 1 and 365.'], 422);
            if ($guests < 1) json_out(['error' => 'Number of guests must be at least 1.'], 422);
            if (!in_array($method, PAY_METHODS, true)) json_out(['error' => 'Choose a payment method.'], 422);
            if ($paid < 0) json_out(['error' => 'Amount paid cannot be negative.'], 422);

            $pdo->beginTransaction();
            $rs = $pdo->prepare("SELECT room_id, room_number, short_price, long_price, capacity, status FROM rooms WHERE room_id = ? AND is_active = 1 FOR UPDATE");
            $rs->execute([$rid]);
            $room = $rs->fetch();
            
            if (!$room) $fail('That room no longer exists.', 404);
            if ($room['status'] !== 'available') $fail("Room {$room['room_number']} is not available.", 409);
            if ($guests > (int)$room['capacity']) $fail("Room {$room['room_number']} sleeps only {$room['capacity']}.");
            
            $rate = $type === 'short' ? (float)$room['short_price'] : (float)$room['long_price'];
            $total = round($rate * $nights, 2);
            if ($paid > $total) $fail('Amount paid cannot be more than the total (' . number_format($total) . ').');

            $pdo->prepare("INSERT INTO room_bookings (booking_no, room_id, guest_name, guest_phone, guest_id_no, guests, booking_type, nights, price_per_night, total_amount, booked_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute(['TMP', $rid, $guest, $phone, $idno ?: null, $guests, $type, $nights, $rate, $total, $user['user_id']]);
            $bid = (int)$pdo->lastInsertId();
            $no = 'B' . str_pad((string)$bid, 6, '0', STR_PAD_LEFT);
            $pdo->prepare("UPDATE room_bookings SET booking_no = ? WHERE booking_id = ?")->execute([$no, $bid]);
            
            if ($paid > 0) {
                $pdo->prepare("INSERT INTO room_payments (booking_id, amount, method, received_by) VALUES (?,?,?,?)")->execute([$bid, $paid, $method, $user['user_id']]);
            }
            $pdo->prepare("UPDATE rooms SET status = 'occupied', status_note = NULL WHERE room_id = ?")->execute([$rid]);
            $pdo->commit();
            
            log_activity('room_book', "$no: room {$room['room_number']}, $guest, " . ($type === 'short' ? 'short stay' : "long stay $nights night(s)") . ", total " . number_format($total) . ", paid " . number_format($paid));
            json_out(['ok' => true, 'booking_id' => $bid, 'booking_no' => $no, 'total' => $total, 'paid' => $paid, 'balance' => $total - $paid]);
            break;

        case 'pay':
            $bid = (int)($in['booking_id'] ?? 0);
            $amt = round((float)($in['amount'] ?? 0), 2);
            $method = (string)($in['method'] ?? 'Cash');
            if ($amt <= 0) json_out(['error' => 'Enter an amount above 0.'], 422);
            if (!in_array($method, PAY_METHODS, true)) json_out(['error' => 'Choose a payment method.'], 422);
            $pdo->beginTransaction();
            $bs = $pdo->prepare("SELECT b.booking_id, b.booking_no, b.total_amount, b.status, r.room_number FROM room_bookings b JOIN rooms r ON r.room_id = b.room_id WHERE b.booking_id = ? FOR UPDATE");
            $bs->execute([$bid]);
            $b = $bs->fetch();
            if (!$b || $b['status'] === 'cancelled') $fail('Booking not found.', 404);
            $balance = round((float)$b['total_amount'] - paid_for($pdo, $bid), 2);
            if ($balance <= 0) $fail('This booking is already fully paid.');
            if ($amt > $balance) $fail('That is more than the balance owed (' . number_format($balance) . ').');
            
            $pdo->prepare("INSERT INTO room_payments (booking_id, amount, method, received_by) VALUES (?,?,?,?)")->execute([$bid, $amt, $method, $user['user_id']]);
            $pdo->commit();
            log_activity('room_payment', "{$b['booking_no']} (room {$b['room_number']}): received " . number_format($amt) . " ($method)");
            json_out(['ok' => true, 'message' => 'Payment saved.', 'balance' => $balance - $amt]);
            break;

        case 'checkout':
            $bid = (int)($in['booking_id'] ?? 0);
            $pdo->beginTransaction();
            $bs = $pdo->prepare("SELECT b.booking_id, b.booking_no, b.room_id, b.guest_name, b.total_amount, b.status, r.room_number FROM room_bookings b JOIN rooms r ON r.room_id = b.room_id WHERE b.booking_id = ? FOR UPDATE");
            $bs->execute([$bid]);
            $b = $bs->fetch();
            if (!$b || $b['status'] !== 'active') $fail('This guest is not checked in.', 409);
            $owed = max(0, round((float)$b['total_amount'] - paid_for($pdo, $bid), 2));
            
            $pdo->prepare("UPDATE room_bookings SET status = 'checked_out', checked_out_at = NOW(), checked_out_by = ? WHERE booking_id = ?")->execute([$user['user_id'], $bid]);
            $pdo->prepare("UPDATE rooms SET status = 'maintenance', status_note = 'Guest left - clean and inspect' WHERE room_id = ?")->execute([$b['room_id']]);
            $pdo->commit();
            log_activity('room_checkout', "{$b['booking_no']}: room {$b['room_number']}, {$b['guest_name']} left" . ($owed > 0 ? ", balance owed " . number_format($owed) : ''));
            json_out(['ok' => true, 'message' => "Room {$b['room_number']} is now under maintenance.", 'owed' => $owed]);
            break;

        case 'ready':
            $rid = (int)($in['room_id'] ?? 0);
            $n = $pdo->prepare("SELECT room_number FROM rooms WHERE room_id = ?");
            $n->execute([$rid]);
            $num = (string)$n->fetchColumn();
            $s = $pdo->prepare("UPDATE rooms SET status = 'available', status_note = NULL WHERE room_id = ? AND status = 'maintenance' AND is_active = 1");
            $s->execute([$rid]);
            if (!$s->rowCount()) json_out(['error' => 'That room is not under maintenance.'], 409);
            log_activity('room_ready', "Room $num is ready again");
            json_out(['ok' => true, 'message' => "Room $num is available."]);
            break;

        case 'add':
            [$num, $cap, $short, $long, $desc] = room_fields($in);
            $pdo->beginTransaction();
            $f = $pdo->prepare("SELECT room_id, is_active FROM rooms WHERE room_number = ? FOR UPDATE");
            $f->execute([$num]);
            $old = $f->fetch();
            if ($old && $old['is_active']) $fail('A room with that number already exists.', 409);
            
            if ($old) { 
                $pdo->prepare("UPDATE rooms SET is_active = 1, capacity = ?, short_price = ?, long_price = ?, description = ?, status = 'available', status_note = NULL WHERE room_id = ?")
                    ->execute([$cap, $short, $long, $desc ?: null, $old['room_id']]);
            } else {
                $pdo->prepare("INSERT INTO rooms (room_number, capacity, short_price, long_price, description) VALUES (?,?,?,?,?)")
                    ->execute([$num, $cap, $short, $long, $desc ?: null]);
            }
            $pdo->commit();
            log_activity('room_add', "Room $num, sleeps $cap, short " . number_format($short) . ' / long ' . number_format($long));
            json_out(['ok' => true, 'message' => "Room $num added."]);
            break;

        case 'update':
            $id = (int)($in['room_id'] ?? 0);
            [$num, $cap, $short, $long, $desc] = room_fields($in);
            $o = $pdo->prepare("SELECT room_number, capacity, short_price, long_price FROM rooms WHERE room_id = ? AND is_active = 1");
            $o->execute([$id]);
            $old = $o->fetch();
            if (!$old) json_out(['error' => 'Room not found.'], 404);
            
            $pdo->prepare("UPDATE rooms SET room_number = ?, capacity = ?, short_price = ?, long_price = ?, description = ? WHERE room_id = ?")
                ->execute([$num, $cap, $short, $long, $desc ?: null, $id]);
            
            $ch = [];
            if ($old['room_number'] !== $num) $ch[] = "number ({$old['room_number']} -> $num)";
            if ((int)$old['capacity'] !== $cap) $ch[] = "sleeps ({$old['capacity']} -> $cap)";
            if ((float)$old['short_price'] !== $short) $ch[] = 'short price ' . number_format((float)$old['short_price']) . ' -> ' . number_format($short);
            if ((float)$old['long_price'] !== $long) $ch[] = 'long price ' . number_format((float)$old['long_price']) . ' -> ' . number_format($long);
            
            log_activity('room_update', "Room {$old['room_number']}: " . ($ch ? implode('; ', $ch) : 'details updated'));
            json_out(['ok' => true, 'message' => 'Room updated.']);
            break;

        case 'set_status':
            $id = (int)($in['room_id'] ?? 0);
            $to = (string)($in['status'] ?? '');
            $note = mb_substr(trim((string)($in['note'] ?? '')), 0, 255);
            if (!in_array($to, ['available', 'maintenance'], true)) json_out(['error' => 'Invalid status.'], 422);
            
            $pdo->beginTransaction();
            $rs = $pdo->prepare("SELECT room_number, status FROM rooms WHERE room_id = ? AND is_active = 1 FOR UPDATE");
            $rs->execute([$id]);
            $room = $rs->fetch();
            if (!$room) $fail('Room not found.', 404);
            if ($room['status'] === 'occupied') $fail('This room has a guest. Check the guest out first.', 409);
            
            $pdo->prepare("UPDATE rooms SET status = ?, status_note = ? WHERE room_id = ?")->execute([$to, $to === 'maintenance' ? ($note ?: 'Under maintenance') : null, $id]);
            $pdo->commit();
            log_activity('room_status', "Room {$room['room_number']}: {$room['status']} -> $to" . ($note ? " ($note)" : ''));
            json_out(['ok' => true, 'message' => 'Room status changed.']);
            break;

        case 'delete':
            $id = (int)($in['room_id'] ?? 0);
            $pdo->beginTransaction();
            $rs = $pdo->prepare("SELECT room_number, status FROM rooms WHERE room_id = ? AND is_active = 1 FOR UPDATE");
            $rs->execute([$id]);
            $room = $rs->fetch();
            if (!$room) $fail('Room not found.', 404);
            if ($room['status'] === 'occupied') $fail('This room has a guest. Check the guest out first.', 409);
            
            $pdo->prepare("UPDATE rooms SET is_active = 0 WHERE room_id = ?")->execute([$id]);
            $pdo->commit();
            log_activity('room_delete', "Room {$room['room_number']} deleted");
            json_out(['ok' => true, 'message' => 'Room deleted.']);
            break;

        default:
            json_out(['error' => 'Unknown action.'], 400);
    }
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($ex->getCode() == '23000') json_out(['error' => 'That room number already exists.'], 409);
    json_out(['error' => 'Database error.'], 500);
}
?>