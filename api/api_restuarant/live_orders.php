<?php
// bms/api/api_restuarant/live_orders.php
require_once '../../restuarant_config.php';

// Prevent timeouts and buffering
set_time_limit(0);
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Get the last order ID the client saw
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

$lastCheck = time();

while (true) {
    // If the connection drops, stop the script
    if (connection_aborted()) break;

    // Check for new orders every 2 seconds
    if (time() - $lastCheck >= 2) {
        $lastCheck = time();
        
        try {
            // Fetch only orders created in the last 10 seconds that are newer than the last seen ID
            $stmt = $pdo->prepare("
                SELECT MAX(order_id) as max_id 
                FROM restaurant_orders 
                WHERE order_id > ? AND created_at >= NOW() - INTERVAL 10 SECOND
            ");
            $stmt->execute([$lastId]);
            $row = $stmt->fetch();
            
            if ($row && $row['max_id'] > $lastId) {
                $newMax = (int)$row['max_id'];
                
                // Send a ping to the browser
                echo "data: " . json_encode(['new_order' => true, 'max_id' => $newMax]) . "\n\n";
                ob_flush();
                flush();
                
                // Update lastId so we don't send the same order twice
                $lastId = $newMax;
            } else {
                // Just keep the connection alive
                echo ": keep-alive\n\n";
                ob_flush();
                flush();
            }
        } catch (Exception $e) {
            // Ignore DB errors in the loop
        }
    }
    
    usleep(500000); // Sleep for 0.5 seconds to save CPU
}