<?php
// bms/api/api_restuarant/orders.php
declare(strict_types=1);
require_once '../../restuarant_config.php';

require_method('GET', 'POST', 'PUT');
enforce_same_origin();

/**
 * Fetch orders (with items) in the exact shape the UI expects.
 */
function fetch_orders(PDO $pdo, ?int $orderId = null): array
{
    $sql = "
        SELECT o.order_id, o.table_id, o.customer_name, o.order_type,
               o.order_status, o.payment_status, o.total_amount, o.created_at,
               t.table_number
        FROM restaurant_orders o
        LEFT JOIN restaurant_tables t ON t.table_id = o.table_id
    ";
    $params = [];
    if ($orderId !== null) {
        $sql .= " WHERE o.order_id = ?";
        $params[] = $orderId;
    }
    $sql .= " ORDER BY o.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    if (!$orders) return [];

    $ids = array_column($orders, 'order_id');
    $ph  = implode(',', array_fill(0, count($ids), '?'));

    $itemStmt = $pdo->prepare("
        SELECT oi.order_id, oi.item_id, oi.quantity, oi.unit_price, oi.subtotal,
               m.item_name
        FROM restaurant_order_items oi
        JOIN restaurant_menu m ON m.item_id = oi.item_id
        WHERE oi.order_id IN ($ph)
        ORDER BY oi.order_item_id ASC
    ");
    $itemStmt->execute($ids);

    $itemsByOrder = [];
    foreach ($itemStmt->fetchAll() as $r) {
        $itemsByOrder[(int)$r['order_id']][] = [
            'item_id'    => (int)$r['item_id'],
            'item_name'  => $r['item_name'],
            'quantity'   => (int)$r['quantity'],
            'unit_price' => (float)$r['unit_price'],
            'subtotal'   => (float)$r['subtotal'],
        ];
    }

    foreach ($orders as &$o) {
        $o['order_id']     = (int)$o['order_id'];
        $o['table_id']     = $o['table_id']     !== null ? (int)$o['table_id']     : null;
        $o['table_number'] = $o['table_number'] !== null ? (int)$o['table_number'] : null;
        $o['total_amount'] = (float)$o['total_amount'];
        $o['items']        = $itemsByOrder[$o['order_id']] ?? [];
    }
    return $orders;
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {

        case 'GET':
            $orderId = isset($_GET['order_id']) ? pos_int($_GET['order_id']) : null;
            json_ok(['data' => fetch_orders($pdo, $orderId)]);
            break;

        case 'POST':
            $data = read_json_body();

            $customer = clean_str($data['customer_name'] ?? '', 100);
            $tableId  = !empty($data['table_id']) ? pos_int($data['table_id']) : null;
            $type     = one_of($data['order_type'] ?? 'Dine-In', ['Counter', 'Dine-In'], 'Dine-In');
            $rawItems = $data['items'] ?? [];

            if ($customer === '') json_error('Customer name is required.');
            if (!is_array($rawItems) || count($rawItems) === 0) {
                json_error('At least one item is required.');
            }
            if (count($rawItems) > 100) json_error('Too many line items (max 100).');

            $items = [];
            foreach ($rawItems as $line) {
                $id  = pos_int($line['item_id']  ?? null);
                $qty = pos_int($line['quantity'] ?? null);
                if (!$id || !$qty || $qty > 999) {
                    json_error('Invalid line item (item_id / quantity).');
                }
                $items[] = ['item_id' => $id, 'quantity' => $qty];
            }

            $pdo->beginTransaction();

            if ($tableId !== null) {
                $chk = $pdo->prepare("
                    SELECT status FROM restaurant_tables
                    WHERE table_id = ? FOR UPDATE
                ");
                $chk->execute([$tableId]);
                $row = $chk->fetch();
                if (!$row) throw new RuntimeException('Table not found.');
                if ($row['status'] === 'Occupied') throw new RuntimeException('That table is already occupied.');
            }

            $ids = array_column($items, 'item_id');
            $ph  = implode(',', array_fill(0, count($ids), '?'));
            $menuStmt = $pdo->prepare("
                SELECT item_id, price FROM restaurant_menu
                WHERE item_id IN ($ph) AND is_active = 1
                FOR UPDATE
            ");
            $menuStmt->execute($ids);
            $priceMap = [];
            foreach ($menuStmt->fetchAll() as $m) {
                $priceMap[(int)$m['item_id']] = (float)$m['price'];
            }

            $total = 0.0;
            $lines = [];
            foreach ($items as $it) {
                if (!isset($priceMap[$it['item_id']])) {
                    throw new RuntimeException("Menu item #{$it['item_id']} is inactive or missing.");
                }
                $unit = $priceMap[$it['item_id']];
                $sub  = round($unit * $it['quantity'], 2);
                $total += $sub;
                $lines[] = [
                    'item_id'    => $it['item_id'],
                    'quantity'   => $it['quantity'],
                    'unit_price' => $unit,
                    'subtotal'   => $sub,
                ];
            }
            $total = round($total, 2);

            // Check if Paying Now (Counter) or Paying Later (Dine-In)
            $paymentStatus = ($type === 'Counter') ? 'Paid' : 'Unpaid';

            $ins = $pdo->prepare("
                INSERT INTO restaurant_orders
                    (table_id, customer_name, order_type, order_status, payment_status, total_amount)
                VALUES (?, ?, ?, 'Received', ?, ?)
            ");
            $ins->execute([$tableId, $customer, $type, $paymentStatus, $total]);
            $orderId = (int)$pdo->lastInsertId();

            $insItem = $pdo->prepare("
                INSERT INTO restaurant_order_items
                    (order_id, item_id, quantity, unit_price, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($lines as $l) {
                $insItem->execute([$orderId, $l['item_id'], $l['quantity'], $l['unit_price'], $l['subtotal']]);
            }

            if ($tableId !== null) {
                $pdo->prepare("
                    UPDATE restaurant_tables
                    SET status = 'Occupied', current_customer_name = ?
                    WHERE table_id = ?
                ")->execute([$customer, $tableId]);
            }

            $pdo->commit();
            json_ok([
                'message'  => 'Order placed successfully.',
                'order_id' => $orderId,
                'total'    => $total,
            ]);
            break;

        case 'PUT':
            $data    = read_json_body();
            $orderId = pos_int($data['order_id'] ?? null);
            if (!$orderId) json_error('Order ID is required.');

            $fields = [];
            $params = [];

            if (array_key_exists('order_status', $data)) {
                $s = one_of($data['order_status'],
                    ['Received', 'Cooking', 'Ready', 'Disposed'], null);
                if ($s === null) json_error('Invalid order status.');
                $fields[] = 'order_status = ?';
                $params[] = $s;
            }

            if (array_key_exists('payment_status', $data)) {
                $p = one_of($data['payment_status'], ['Unpaid', 'Paid'], null);
                if ($p === null) json_error('Invalid payment status.');
                $fields[] = 'payment_status = ?';
                $params[] = $p;
            }

            if (!$fields) json_error('No fields to update.');

            $pdo->beginTransaction();

            $params[] = $orderId;
            $pdo->prepare("UPDATE restaurant_orders SET " . implode(', ', $fields) . " WHERE order_id = ?")
                ->execute($params);

            // Release table if the order is Served (Disposed) OR Paid
            $shouldReleaseTable = false;
            
            if (isset($data['order_status']) && $data['order_status'] === 'Disposed') {
                $shouldReleaseTable = true;
            }
            if (isset($data['payment_status']) && $data['payment_status'] === 'Paid') {
                $shouldReleaseTable = true;
            }

            if ($shouldReleaseTable) {
                $q = $pdo->prepare("SELECT table_id FROM restaurant_orders WHERE order_id = ?");
                $q->execute([$orderId]);
                $row = $q->fetch();
                if ($row && $row['table_id']) {
                    $pdo->prepare("
                        UPDATE restaurant_tables
                        SET status = 'Needs Cleaning', current_customer_name = NULL
                        WHERE table_id = ?
                    ")->execute([(int)$row['table_id']]);
                }
            }

            $pdo->commit();
            json_ok(['message' => 'Order updated successfully.']);
            break;
    }
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_error($e->getMessage(), 400);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[orders.php] ' . $e->getMessage());
    json_error('Database error', 500);
}