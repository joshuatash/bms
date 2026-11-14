<?php
// bms/api/api_restuarant/reports.php
declare(strict_types=1);
require_once '../../restuarant_config.php';

require_method('GET', 'POST');
enforce_same_origin();

function business_range(string $period): array
{
    $now = new DateTimeImmutable('now');
    $dayAnchor = $now->setTime(7, 0, 0);
    if ($now < $dayAnchor) {
        $dayAnchor = $dayAnchor->modify('-1 day');
    }
    switch ($period) {
        case 'today':
            $start = $dayAnchor;
            $end   = $dayAnchor->modify('+1 day');
            break;
        case 'week':
            $end   = $dayAnchor->modify('+1 day');
            $start = $end->modify('-7 days');
            break;
        case 'month':
            $end   = $dayAnchor->modify('+1 day');
            $start = $end->modify('-30 days');
            break;
        default:
            $start = $dayAnchor;
            $end   = $dayAnchor->modify('+1 day');
    }
    return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
}

function sum_scalar(PDO $pdo, string $sql, array $params): float
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $v = $stmt->fetchColumn();
    return $v === false || $v === null ? 0.0 : (float)$v;
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {

        case 'GET':
            $mode   = $_GET['mode'] ?? 'snapshot';
            $period = $_GET['period'] ?? 'today';
            if (!in_array($period, ['today', 'week', 'month'], true)) {
                $period = 'today';
            }
            [$start, $end] = business_range($period);

            if ($mode === 'details') {
                $type = $_GET['type'] ?? '';
                if (!in_array($type, ['sales', 'stock', 'expenses'], true)) {
                    json_error('Invalid detail type.');
                }

                if ($type === 'sales') {
                    $stmt = $pdo->prepare(
                        "SELECT o.order_id, o.customer_name, o.order_type, o.table_id,
                                t.table_number, o.total_amount, o.payment_status, o.order_status, o.created_at
                         FROM restaurant_orders o
                         LEFT JOIN restaurant_tables t ON t.table_id = o.table_id
                         WHERE o.payment_status = 'Paid' AND o.created_at >= ? AND o.created_at < ?
                         ORDER BY o.created_at DESC"
                    );
                    $stmt->execute([$start, $end]);
                    $rows = $stmt->fetchAll();

                    if ($rows) {
                        $ids = array_column($rows, 'order_id');
                        $ph  = implode(',', array_fill(0, count($ids), '?'));
                        $itemStmt = $pdo->prepare(
                            "SELECT oi.order_id, oi.quantity, oi.unit_price, oi.subtotal, m.item_name
                             FROM restaurant_order_items oi
                             JOIN restaurant_menu m ON m.item_id = oi.item_id
                             WHERE oi.order_id IN ($ph)"
                        );
                        $itemStmt->execute($ids);
                        $byOrder = [];
                        foreach ($itemStmt->fetchAll() as $r) {
                            $byOrder[(int)$r['order_id']][] = [
                                'item_name' => $r['item_name'],
                                'quantity'  => (int)$r['quantity'],
                                'unit_price'=> (float)$r['unit_price'],
                                'subtotal'  => (float)$r['subtotal'],
                            ];
                        }
                        foreach ($rows as &$r) {
                            $r['order_id']     = (int)$r['order_id'];
                            $r['table_number'] = $r['table_number'] !== null ? (int)$r['table_number'] : null;
                            $r['total_amount'] = (float)$r['total_amount'];
                            $r['items']        = $byOrder[$r['order_id']] ?? [];
                        }
                    }
                    json_ok(['type' => 'sales', 'period' => $period, 'range' => ['start' => $start, 'end' => $end], 'rows' => $rows]);
                    break;
                }

                if ($type === 'stock') {
                    $stmt = $pdo->prepare(
                        "SELECT purchase_id, item_name, category, quantity, unit, unit_cost,
                                total_cost, supplier, purchase_date
                         FROM restaurant_stock_purchases
                         WHERE purchase_date >= ? AND purchase_date < ?
                         ORDER BY purchase_date DESC"
                    );
                    $stmt->execute([$start, $end]);
                    $rows = $stmt->fetchAll();
                    foreach ($rows as &$r) {
                        $r['purchase_id'] = (int)$r['purchase_id'];
                        $r['quantity']    = (float)$r['quantity'];
                        $r['unit_cost']   = (float)$r['unit_cost'];
                        $r['total_cost']  = (float)$r['total_cost'];
                    }
                    json_ok(['type' => 'stock', 'period' => $period, 'range' => ['start' => $start, 'end' => $end], 'rows' => $rows]);
                    break;
                }

                $stmt = $pdo->prepare(
                    "SELECT expense_id, expense_name, category, amount, notes, expense_date
                     FROM restaurant_expenses
                     WHERE expense_date >= ? AND expense_date < ?
                     ORDER BY expense_date DESC"
                );
                $stmt->execute([$start, $end]);
                $rows = $stmt->fetchAll();
                foreach ($rows as &$r) {
                    $r['expense_id'] = (int)$r['expense_id'];
                    $r['amount']     = (float)$r['amount'];
                }
                json_ok(['type' => 'expenses', 'period' => $period, 'range' => ['start' => $start, 'end' => $end], 'rows' => $rows]);
                break;
            }

            // --- SNAPSHOT MODE ---
            // Only counts PAID sales
            $revenue = sum_scalar(
                $pdo,
                "SELECT COALESCE(SUM(total_amount),0) FROM restaurant_orders
                 WHERE payment_status = 'Paid' AND created_at >= ? AND created_at < ?",
                [$start, $end]
            );

            $orderCount = (int)sum_scalar(
                $pdo,
                "SELECT COUNT(*) FROM restaurant_orders
                 WHERE payment_status = 'Paid' AND created_at >= ? AND created_at < ?",
                [$start, $end]
            );

            $stockSpend = sum_scalar(
                $pdo,
                "SELECT COALESCE(SUM(total_cost),0) FROM restaurant_stock_purchases
                 WHERE purchase_date >= ? AND purchase_date < ?",
                [$start, $end]
            );

            $expenses = sum_scalar(
                $pdo,
                "SELECT COALESCE(SUM(amount),0) FROM restaurant_expenses
                 WHERE expense_date >= ? AND expense_date < ?",
                [$start, $end]
            );

            $stmt = $pdo->prepare(
                "SELECT category, COALESCE(SUM(total_cost),0) AS spent
                 FROM restaurant_stock_purchases
                 WHERE purchase_date >= ? AND purchase_date < ?
                 GROUP BY category ORDER BY spent DESC"
            );
            $stmt->execute([$start, $end]);
            $stockByCategory = $stmt->fetchAll();

            $stmt = $pdo->prepare(
                "SELECT category, COALESCE(SUM(amount),0) AS spent
                 FROM restaurant_expenses
                 WHERE expense_date >= ? AND expense_date < ?
                 GROUP BY category ORDER BY spent DESC"
            );
            $stmt->execute([$start, $end]);
            $expensesByCategory = $stmt->fetchAll();

            // Best sellers from PAID sales only
            $stmt = $pdo->prepare(
                "SELECT m.item_name, SUM(oi.quantity) AS qty, SUM(oi.subtotal) AS revenue
                 FROM restaurant_order_items oi
                 JOIN restaurant_orders o ON o.order_id = oi.order_id
                 JOIN restaurant_menu   m ON m.item_id  = oi.item_id
                 WHERE o.payment_status = 'Paid' AND o.created_at >= ? AND o.created_at < ?
                 GROUP BY m.item_id, m.item_name
                 ORDER BY revenue DESC LIMIT 10"
            );
            $stmt->execute([$start, $end]);
            $bestSellers = $stmt->fetchAll();

            // Order types from PAID sales only
            $stmt = $pdo->prepare(
                "SELECT order_type, COUNT(*) AS orders, COALESCE(SUM(total_amount),0) AS revenue
                 FROM restaurant_orders
                 WHERE payment_status = 'Paid' AND created_at >= ? AND created_at < ?
                 GROUP BY order_type"
            );
            $stmt->execute([$start, $end]);
            $byType = $stmt->fetchAll();

            $totalCost = $stockSpend + $expenses;
            $net = $revenue - $totalCost;

            json_ok([
                'period'       => $period,
                'range'        => ['start' => $start, 'end' => $end],
                'revenue'      => round($revenue, 2),
                'order_count'  => $orderCount,
                'stock_spend'  => round($stockSpend, 2),
                'expenses'     => round($expenses, 2),
                'total_cost'   => round($totalCost, 2),
                'net'          => round($net, 2),
                'stock_by_category'    => $stockByCategory,
                'expenses_by_category' => $expensesByCategory,
                'best_sellers'         => $bestSellers,
                'by_type'              => $byType
            ]);
            break;

        case 'POST':
            $data = read_json_body();
            $type = one_of($data['type'] ?? '', ['purchase', 'expense'], null);
            if ($type === null) json_error('Invalid record type.');

            if ($type === 'purchase') {
                $itemName = clean_str($data['item_name'] ?? '', 100);
                $category = clean_str($data['category'] ?? 'Raw Materials', 50);
                $unit     = clean_str($data['unit'] ?? '', 30);
                $qty      = (float)($data['quantity'] ?? 0);
                $unitCost = (float)($data['unit_cost'] ?? 0);
                $supplier = clean_str($data['supplier'] ?? '', 100) ?: null;
                $inventoryId = isset($data['inventory_id']) && $data['inventory_id'] !== '' ? (int)$data['inventory_id'] : null;

                if ($itemName === '' || $unit === '' || $qty <= 0 || $unitCost <= 0) {
                    json_error('Item name, quantity, unit, and unit cost are required.');
                }
                $totalCost = round($qty * $unitCost, 2);

                $pdo->beginTransaction();
                $stmt = $pdo->prepare(
                    "INSERT INTO restaurant_stock_purchases
                     (inventory_id, item_name, category, quantity, unit, unit_cost, total_cost, supplier, purchase_date)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
                );
                $stmt->execute([$inventoryId, $itemName, $category, $qty, $unit, $unitCost, $totalCost, $supplier]);
                $purchaseId = (int)$pdo->lastInsertId();

                if ($inventoryId) {
                    $pdo->prepare(
                        "UPDATE restaurant_inventory
                         SET quantity = quantity + ?, last_restocked_date = NOW()
                         WHERE inventory_id = ?"
                    )->execute([$qty, $inventoryId]);
                }
                $pdo->commit();
                json_ok(['message' => 'Stock purchase recorded.', 'purchase_id' => $purchaseId, 'total_cost' => $totalCost]);
            }

            $name   = clean_str($data['expense_name'] ?? '', 100);
            $cat    = clean_str($data['category'] ?? 'Other', 50);
            $amount = (float)($data['amount'] ?? 0);
            $notes  = clean_str($data['notes'] ?? '', 500) ?: null;

            if ($name === '' || $amount <= 0) {
                json_error('Expense name and a positive amount are required.');
            }
            
            $stmt = $pdo->prepare(
                "INSERT INTO restaurant_expenses (expense_name, category, amount, notes, expense_date)
                 VALUES (?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$name, $cat, $amount, $notes]);

            json_ok(['message' => 'Expense recorded.', 'expense_id' => (int)$pdo->lastInsertId()]);
            break;
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[reports.php] ' . $e->getMessage());
    json_error($e->getMessage(), 500);
}