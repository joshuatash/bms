<?php
require_once __DIR__ . '/../../config.php';
require_login();

header('Content-Type: application/json');

try {
    $pdo = db();
    
    // Fetch the latest paid order, ignoring order_status entirely.
    // If payment_status hits 'Paid', we want to know about it.
    $stmt = $pdo->query("
        SELECT o.order_id, o.customer_name, t.table_number 
        FROM restaurant_orders o
        LEFT JOIN restaurant_tables t ON o.table_id = t.table_id
        WHERE o.payment_status = 'Paid' 
        ORDER BY o.order_id DESC
        LIMIT 1
    ");
    
    $latest = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'latest_paid' => $latest ?: null]);
    exit;
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}