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
            $desc = trim((string)($in['description'] ?? ''));
            $amount = round((float)($in['amount'] ?? 0), 2);
            $deposit = round((float)($in['deposit'] ?? 0), 2);
            $balance = round($amount - $deposit, 2);
            $method = (string)($in['payment_method'] ?? 'Cash');
            $returnDate = trim((string)($in['return_date'] ?? ''));
            
            if (mb_strlen($customer) < 2) json_out(['error' => 'Enter a valid customer or event name.'], 422);
            if ($amount <= 0) json_out(['error' => 'Amount must be above 0.'], 422);
            if ($deposit < 0) json_out(['error' => 'Deposit cannot be negative.'], 422);
            if ($deposit > $amount) json_out(['error' => 'Deposit cannot exceed the total amount.'], 422);
            if (!in_array($method, ['Cash', 'Mobile Money', 'Card'], true)) json_out(['error' => 'Invalid payment method.'], 422);
            
            $userId = $user['user_id'] ?? $user['id'] ?? null;
            if (!$userId) json_out(['error' => 'Could not identify the logged-in user. Please log out and log in again.'], 401);

            $stmt = $pdo->prepare("INSERT INTO pa_collections (customer_name, description, amount, deposit, balance, payment_method, return_date, recorded_by) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$customer, $desc ?: null, $amount, $deposit, $balance, $method, $returnDate ?: null, $userId]);
            
            $id = $pdo->lastInsertId();
            log_activity('pa_collection', "Collected " . CURRENCY . " " . number_format($amount) . " (Deposit: " . number_format($deposit) . ") from $customer");
            json_out(['ok' => true, 'message' => 'Collection recorded successfully.', 'id' => $id]);
            break;

        case 'clear_balance':
            if (!$isPost) json_out(['error' => 'POST required.'], 405);
            
            $id = (int)($in['id'] ?? 0);
            $userId = $user['user_id'] ?? $user['id'] ?? null;
            if (!$userId) json_out(['error' => 'Could not identify user.'], 401);

            $stmt = $pdo->prepare("SELECT * FROM pa_collections WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();

            if (!$row) json_out(['error' => 'Collection record not found.'], 404);
            if ($row['balance'] <= 0) json_out(['error' => 'This record has no pending balance.'], 422);

            $clearingAmount = (float)$row['balance'];
            $newDeposit = (float)$row['deposit'] + $clearingAmount;
            $newBalance = 0.00;

            $update = $pdo->prepare("UPDATE pa_collections SET deposit = ?, balance = ? WHERE id = ?");
            $update->execute([$newDeposit, $newBalance, $id]);

            log_activity('pa_collection', "Cleared balance of " . CURRENCY . " " . number_format($clearingAmount) . " for " . $row['customer_name']);
            json_out(['ok' => true, 'message' => 'Balance cleared successfully.']);
            break;

        case 'list':
            $sql = "SELECT p.*, u.full_name 
                    FROM pa_collections p 
                    JOIN users u ON u.user_id = p.recorded_by 
                    ORDER BY p.created_at DESC LIMIT 50";
            $rows = $pdo->query($sql)->fetchAll();
            
            // Daily and monthly stats sum up actual collections (deposits) received
            $monthToday = (float)$pdo->query("SELECT COALESCE(SUM(deposit),0) FROM pa_collections WHERE DATE(created_at) = CURDATE()")->fetchColumn();
            $monthTotal = (float)$pdo->query("SELECT COALESCE(SUM(deposit),0) FROM pa_collections WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
            
            json_out(['ok' => true, 'rows' => $rows, 'today' => $monthToday, 'month' => $monthTotal]);
            break;

        default:
            json_out(['error' => 'Unknown action.'], 400);
    }
} catch (PDOException $ex) {
    json_out(['error' => 'Database error: ' . $ex->getMessage()], 500);
}