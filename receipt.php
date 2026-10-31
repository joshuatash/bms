<?php 
require __DIR__ . '/config.php'; 
require_login(); 
$user = current_user(); 
$pdo = db(); 

$booking_id = (int)($_GET['id'] ?? 0);
$sale_id = (int)($_GET['id'] ?? 0);

// Try fetching as a ROOM BOOKING first
$b_stmt = $pdo->prepare("SELECT b.*, r.room_number, u.full_name as booked_by_name FROM room_bookings b JOIN rooms r ON r.room_id = b.room_id JOIN users u ON u.user_id = b.booked_by WHERE b.booking_id = ?");
$b_stmt->execute([$booking_id]);
$booking = $b_stmt->fetch();

$sale = null;
if (!$booking) {
    // Try fetching as a POS SALE
    $st = $pdo->prepare("SELECT s.sale_id, s.receipt_no, s.total_amount, s.sale_date, s.user_id, u.full_name FROM sales_record s JOIN users u ON u.user_id = s.user_id WHERE s.sale_id = ?");
    $st->execute([$sale_id]);
    $sale = $st->fetch();
}

if (!$booking && !$sale) {
    http_response_code(404); exit('Receipt not found.');
}

if ($user['role'] !== 'admin') {
    if ($booking && (int)$booking['booked_by'] !== (int)$user['id']) { http_response_code(403); exit('Unauthorized.'); }
    if ($sale && (int)$sale['user_id'] !== (int)$user['id']) { http_response_code(403); exit('Unauthorized.'); }
}

$items = [];
if ($sale) {
    $it = $pdo->prepare("SELECT product_name, qty_bottles, unit_price, line_total FROM sales_record_items WHERE sale_id = ? ORDER BY item_id");
    $it->execute([$sale['sale_id']]);
    $items = $it->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 20px; color: #0f172a; display: flex; flex-direction: column; align-items: center; }
        .bar { display: flex; gap: 10px; margin-bottom: 16px; }
        .bar a, .bar button { padding: 9px 18px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; border: 0; }
        .bar a { background: #e2e8f0; color: #334155; } .bar button { background: #f59e0b; color: #0f172a; }
        .rc { width: 340px; border: 1px dashed #94a3b8; padding: 18px; font-size: 13px; }
        .rc h1 { margin: 0; text-align: center; font-size: 18px; } .rc .c { text-align: center; color: #64748b; margin: 2px 0; }
        hr { border: 0; border-top: 1px dashed #94a3b8; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; } td { padding: 4px 0; vertical-align: top; } .r { text-align: right; }
        .tot { display: flex; justify-content: space-between; font-weight: 700; font-size: 16px; margin-top: 8px; }
        @media print { .bar { display: none; } body { margin: 0; } .rc { border: 0; } }
    </style>
</head>
<body>
    <div class="bar">
        <a href="<?= $booking ? 'book_room.php' : 'pos.php' ?>">&larr; Back</a>
        <button onclick="window.print()">Print</button>
    </div>
    <div class="rc">
        <h1>Business Manager</h1>
        <?php if ($booking): ?>
            <div class="c">Booking No: <?= e($booking['booking_no']) ?></div>
            <div class="c" id="rc-time"></div>
            <div class="c">Room: <?= e($booking['room_number']) ?> (<?= ucfirst($booking['booking_type']) ?> Stay)</div>
            <hr>
            <table>
                <tr><td>Guest:</td><td class="r"><?= e($booking['guest_name']) ?></td></tr>
                <tr><td>Guests:</td><td class="r"><?= (int)$booking['guests'] ?></td></tr>
                <tr><td>Nights:</td><td class="r"><?= (int)$booking['nights'] ?></td></tr>
                <tr><td>Rate:</td><td class="r"><?= CURRENCY ?> <?= number_format($booking['price_per_night']) ?></td></tr>
            </table>
            <hr>
            <div class="tot"><span>TOTAL</span><span><?= CURRENCY ?> <?= number_format($booking['total_amount']) ?></span></div>
            <div class="c" style="margin-top:8px">Served by: <?= e($booking['booked_by_name']) ?></div>
        <?php else: ?>
            <div class="c">Receipt No: <?= e($sale['receipt_no']) ?></div>
            <div class="c" id="rc-time"></div>
            <div class="c">Served by: <?= e($sale['full_name']) ?></div>
            <hr>
            <table>
                <?php foreach ($items as $i): ?>
                    <tr><td colspan="2"><?= e($i['product_name']) ?></td></tr>
                    <tr><td><?= (int)$i['qty_bottles'] ?> x <?= number_format($i['unit_price']) ?></td><td class="r"><?= number_format($i['line_total']) ?></td></tr>
                <?php endforeach; ?>
            </table>
            <hr>
            <div class="tot"><span>TOTAL (<?= CURRENCY ?>)</span><span><?= number_format($sale['total_amount']) ?></span></div>
        <?php endif; ?>
        <hr>
        <div class="c">Thank you!</div>
    </div>

    <!-- FIXED: 12-hour clock format -->
    <script>
        const rawDate = "<?= $booking ? $booking['check_in'] : $sale['sale_date'] ?>";
        const dateObj = new Date(rawDate.replace(' ', 'T'));
        if (!isNaN(dateObj.getTime())) {
            document.getElementById('rc-time').textContent = dateObj.toLocaleDateString(undefined, { 
                year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true
            });
        }
    </script>
</body>
</html>