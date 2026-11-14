<?php
// bms/api/api_restuarant/menu.php
declare(strict_types=1);
require_once '../../restuarant_config.php';

require_method('GET', 'POST', 'PUT');
enforce_same_origin();

try {
    switch ($_SERVER['REQUEST_METHOD']) {

        // ─────────────────────────────────────────────
        // GET — active menu (public, used by POS + admin)
        // ─────────────────────────────────────────────
        case 'GET':
            $stmt = $pdo->query("
                SELECT item_id, item_name, category, price, is_active, created_at
                FROM restaurant_menu
                WHERE is_active = 1
                ORDER BY category ASC, item_name ASC
            ");
            json_ok(['data' => $stmt->fetchAll()]);
            break;

        // ─────────────────────────────────────────────
        // POST — add a new menu item
        // ─────────────────────────────────────────────
        case 'POST':
            $data = read_json_body();

            $name     = clean_str($data['item_name'] ?? '', 100);
            $category = clean_str($data['category']  ?? '', 50);
            $price    = nonneg_float($data['price']  ?? null);

            if ($name === '' || $category === '' || $price === null || $price > 99999999) {
                json_error('Item name, category, and a valid price are required.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO restaurant_menu (item_name, category, price, is_active)
                VALUES (?, ?, ?, 1)
            ");
            $stmt->execute([$name, $category, $price]);

            json_ok(['message' => 'Menu item added successfully.', 'item_id' => (int)$pdo->lastInsertId()]);
            break;

        // ─────────────────────────────────────────────
        // PUT — update fields (whitelisted) or deactivate
        // ─────────────────────────────────────────────
        case 'PUT':
            $data   = read_json_body();
            $itemId = pos_int($data['item_id'] ?? null);
            if (!$itemId) json_error('Item ID is required.');

            $fields = [];
            $params = [];

            if (array_key_exists('item_name', $data)) {
                $name = clean_str($data['item_name'], 100);
                if ($name === '') json_error('Item name cannot be empty.');
                $fields[] = 'item_name = ?'; $params[] = $name;
            }
            if (array_key_exists('category', $data)) {
                $cat = clean_str($data['category'], 50);
                if ($cat === '') json_error('Category cannot be empty.');
                $fields[] = 'category = ?'; $params[] = $cat;
            }
            if (array_key_exists('price', $data)) {
                $p = nonneg_float($data['price']);
                if ($p === null) json_error('Invalid price.');
                $fields[] = 'price = ?'; $params[] = $p;
            }
            if (array_key_exists('is_active', $data)) {
                $fields[] = 'is_active = ?';
                $params[] = (int)(bool)$data['is_active'];
            }

            if (!$fields) json_error('No fields provided for update.');

            $params[] = $itemId;
            $sql = "UPDATE restaurant_menu SET " . implode(', ', $fields) . " WHERE item_id = ?";
            $pdo->prepare($sql)->execute($params);

            json_ok(['message' => 'Menu item updated successfully.']);
            break;
    }
} catch (PDOException $e) {
    error_log('[menu.php] ' . $e->getMessage());
    json_error('Database error', 500);
}