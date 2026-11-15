<?php
require_once __DIR__ . '/../config.php';
require_login();

header('Content-Type: application/json');

try {
    $pdo = db();
    
    // Join the tables table to get the real table_number instead of just the table_id
    $stmt = $pdo->query("
        SELECT o.order_id, o.customer_name, t.table_number 
        FROM restaurant_orders o
        LEFT JOIN restaurant_tables t ON o.table_id = t.table_id
        WHERE o.order_status = 'Ready' 
        ORDER BY o.order_id DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $orders]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}