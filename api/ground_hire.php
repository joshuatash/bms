<?php
ob_start();
require __DIR__ . '/../config.php';
require_login();

$user = current_user();
if (!$user) json_out(['error' => 'Please log in again.'], 401);
if (!in_array($user['role'], ['admin', 'manager'], true)) json_out(['error' => 'Access denied.'], 403);

$pdo = db();
$action = $_GET['action'] ?? '';
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$in = $isPost ? (json_decode(file_get_contents('php://input'), true) ?: []) : [];

if ($isPost) {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')) {
        json_out(['error' => 'Invalid session token. Please refresh the page.'], 403);
    }
}

try {
    switch ($action) {
        case 'add':
            if (!$isPost) json_out(['error' => 'POST required.'], 405);
            
            $customer = trim((string)($in['customer_name'] ?? ''));
            // Check both 'description' and 'event_name' just in case the form sends it differently
            $desc = trim((string)($in['description'] ?? $in['event_name'] ?? ''));
            $amount = round((float)($in['amount'] ?? 0), 2);
            $method = (string)($in['payment_method'] ?? 'Cash');
            $start_date = trim((string)($in['start_date'] ?? ''));
            $start_time = trim((string)($in['start_time'] ?? ''));
            $end_date = trim((string)($in['end_date'] ?? ''));
            $end_time = trim((string)($in['end_time'] ?? ''));
            
            if (mb_strlen($customer) < 2) json_out(['error' => 'Enter a valid customer or event name.'], 422);
            if ($amount <= 0) json_out(['error' => 'Amount must be above 0.'], 422);
            
            $userId = $user['user_id'] ?? $user['id'] ?? null;
            if (!$userId) json_out(['error' => 'Could not identify the logged-in user. Please log in again.'], 401);

            $pdo->prepare("INSERT INTO ground_hire (customer_name, description, amount, payment_method, start_date, start_time, end_date, end_time, recorded_by) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$customer, $desc ?: null, $amount, $method, $start_date ?: null, $start_time ?: null, $end_date ?: null, $end_time ?: null, $userId]);
            
            $id = $pdo->lastInsertId();
            log_activity('ground_hire', "Collected " . CURRENCY . " " . number_format($amount) . " from $customer");
            json_out(['ok' => true, 'message' => 'Ground Hire recorded successfully.', 'id' => $id]);
            break;

        case 'list':
            $sql = "SELECT p.*, u.full_name 
                    FROM ground_hire p 
                    JOIN users u ON u.user_id = p.recorded_by 
                    ORDER BY p.created_at DESC LIMIT 50";
            $rows = $pdo->query($sql)->fetchAll();
            
            $monthToday = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM ground_hire WHERE DATE(created_at) = CURDATE()")->fetchColumn();
            $monthTotal = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM ground_hire WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
            
            json_out(['ok' => true, 'rows' => $rows, 'today' => $monthToday, 'month' => $monthTotal]);
            break;

        default:
            json_out(['error' => 'Unknown action.'], 400);
    }
} catch (PDOException $ex) {
    json_out(['error' => 'Database error: ' . $ex->getMessage()], 500);
}
?>