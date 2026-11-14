<?php
// bms/api/api_restuarant/tables.php
declare(strict_types=1);
require_once '../../restuarant_config.php';

require_method('GET', 'PUT');
enforce_same_origin();

try {
    switch ($_SERVER['REQUEST_METHOD']) {

        // ─────────────────────────────────────────────
        // GET — list tables; auto-seed 1–20 on first run
        // ─────────────────────────────────────────────
        case 'GET':
            // Use MySQL lock to prevent race during seeding
            $pdo->beginTransaction();
            $pdo->query("SELECT GET_LOCK('seed_tables', 5)")->fetchColumn();

            $count = (int)$pdo->query("SELECT COUNT(*) FROM restaurant_tables")->fetchColumn();
            if ($count === 0) {
                $ins = $pdo->prepare("
                    INSERT INTO restaurant_tables (table_number, status)
                    VALUES (?, 'Available')
                ");
                for ($i = 1; $i <= 20; $i++) {
                    $ins->execute([$i]);
                }
            }

            $pdo->query("SELECT RELEASE_LOCK('seed_tables')")->fetchColumn();
            $pdo->commit();

            $stmt = $pdo->query("
                SELECT table_id, table_number, status, current_customer_name, updated_at
                FROM restaurant_tables
                ORDER BY table_number ASC
            ");
            json_ok(['data' => $stmt->fetchAll()]);
            break;

        // ─────────────────────────────────────────────
        // PUT — update status and/or customer name
        // ─────────────────────────────────────────────
        case 'PUT':
            $data    = read_json_body();
            $tableId = pos_int($data['table_id'] ?? null);
            if (!$tableId) json_error('Table ID is required.');

            $fields = [];
            $params = [];

            if (array_key_exists('status', $data)) {
                $status = one_of($data['status'],
                    ['Available', 'Occupied', 'Needs Cleaning'], null);
                if ($status === null) json_error('Invalid table status.');
                $fields[] = 'status = ?';
                $params[] = $status;
            }

            if (array_key_exists('current_customer_name', $data)) {
                $name = $data['current_customer_name'] === null
                      ? null
                      : clean_str($data['current_customer_name'], 100);
                $fields[] = 'current_customer_name = ?';
                $params[] = $name;
            }

            if (!$fields) json_error('No fields to update.');

            $params[] = $tableId;
            $sql = "UPDATE restaurant_tables SET " . implode(', ', $fields) . " WHERE table_id = ?";
            $pdo->prepare($sql)->execute($params);

            json_ok(['message' => 'Table updated successfully.']);
            break;
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[tables.php] ' . $e->getMessage());
    json_error('Database error', 500);
}