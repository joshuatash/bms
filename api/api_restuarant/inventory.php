<?php
// bms/api/api_restuarant/inventory.php
declare(strict_types=1);
require_once '../../restuarant_config.php';

require_method('GET', 'POST', 'PUT');
enforce_same_origin();

try {
    switch ($_SERVER['REQUEST_METHOD']) {

        // ─────────────────────────────────────────────
        // GET — all inventory rows
        // ─────────────────────────────────────────────
        case 'GET':
            $stmt = $pdo->query("
                SELECT inventory_id, item_name, category, quantity, unit,
                       low_stock_threshold, last_restocked_date
                FROM restaurant_inventory
                ORDER BY item_name ASC
            ");
            json_ok(['data' => $stmt->fetchAll()]);
            break;

        // ─────────────────────────────────────────────
        // POST — add raw material
        // ─────────────────────────────────────────────
        case 'POST':
            $data = read_json_body();

            $name  = clean_str($data['item_name'] ?? '', 100);
            $cat   = clean_str($data['category']  ?? 'Raw Materials', 50);
            $unit  = clean_str($data['unit']      ?? '', 30);
            $qty   = nonneg_float($data['quantity'] ?? 0);
            $thr   = nonneg_float($data['low_stock_threshold'] ?? 5.00);

            if ($name === '' || $unit === '' || $qty === null || $thr === null) {
                json_error('Item name, quantity, unit, and threshold are required.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO restaurant_inventory
                    (item_name, category, quantity, unit, low_stock_threshold, last_restocked_date)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $cat, $qty, $unit, $thr]);

            json_ok([
                'message'      => 'Inventory item added successfully.',
                'inventory_id' => (int)$pdo->lastInsertId(),
            ]);
            break;

        // ─────────────────────────────────────────────
        // PUT — restock (add) or set exact physical count
        // ─────────────────────────────────────────────
        case 'PUT':
            $data   = read_json_body();
            $invId  = pos_int($data['inventory_id'] ?? null);
            $qty    = nonneg_float($data['quantity'] ?? null);
            $mode   = one_of($data['mode'] ?? 'add', ['add', 'set'], 'add');

            if (!$invId || $qty === null) {
                json_error('Inventory ID and a valid quantity are required.');
            }

            if ($mode === 'add') {
                $stmt = $pdo->prepare("
                    UPDATE restaurant_inventory
                    SET quantity = quantity + ?, last_restocked_date = NOW()
                    WHERE inventory_id = ?
                ");
            } else {
                $stmt = $pdo->prepare("
                    UPDATE restaurant_inventory
                    SET quantity = ?, last_restocked_date = NOW()
                    WHERE inventory_id = ?
                ");
            }
            $stmt->execute([$qty, $invId]);

            if ($stmt->rowCount() === 0) json_error('Inventory item not found.', 404);
            json_ok(['message' => 'Inventory updated successfully.']);
            break;
    }
} catch (PDOException $e) {
    error_log('[inventory.php] ' . $e->getMessage());
    json_error('Database error', 500);
}