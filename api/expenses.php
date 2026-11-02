<?php
ob_start();
require __DIR__ . '/../config.php';
require_login();
$user = current_user();
if (!$user) json_out(['error' => 'Please log in again.'], 401);
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
            
            if ($user['role'] === 'admin') {
                json_out(['error' => 'Administrators are not allowed to record expenses. Please use a Manager account.'], 403);
            }
            
            $title = trim((string)($in['title'] ?? ''));
            $category = trim((string)($in['category'] ?? 'General'));
            $department = trim((string)($in['department'] ?? 'Administration'));
            $amount = round((float)($in['amount'] ?? 0), 2);
            $description = trim((string)($in['description'] ?? ''));
            
            if (mb_strlen($title) < 2) json_out(['error' => 'Enter a valid expense title.'], 422);
            if ($amount <= 0) json_out(['error' => 'Amount must be above 0.'], 422);
            
            $userId = $user['user_id'] ?? $user['id'] ?? null;
            
            $pdo->prepare("INSERT INTO expenses (title, category, department, amount, description, recorded_by, expense_date) VALUES (?,?,?,?,?,?,CURDATE())")
                ->execute([$title, $category, $department, $amount, $description ?: null, $userId]);
            
            log_activity('expense_add', "Recorded expense ($department): $title (" . number_format($amount) . ")");
            json_out(['ok' => true, 'message' => 'Expense recorded successfully.']);
            break;

        case 'list':
            if (!in_array($user['role'], ['admin', 'manager'], true)) {
                json_out(['error' => 'Only admin and managers can view expenses.'], 403);
            }
            
            // Determine the date filter based on the selected period
            $period = $_GET['period'] ?? 'today';
            $dateCondition = "e.expense_date = CURDATE()"; // Default: Today
            
            if ($period === 'week') {
                // Shows the last 7 days (including today)
                $dateCondition = "e.expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
            } elseif ($period === 'month') {
                // Shows the last 30 days (including today)
                $dateCondition = "e.expense_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)";
            } elseif ($period === 'all') {
                // No date restriction
                $dateCondition = "1=1";
            }

            $sql = "SELECT e.*, u.full_name AS recorded_by_name 
                    FROM expenses e 
                    JOIN users u ON u.user_id = e.recorded_by 
                    WHERE $dateCondition 
                    ORDER BY e.created_at DESC";
            $rows = $pdo->query($sql)->fetchAll();
            $total = array_sum(array_column($rows, 'amount'));
            
            // Department breakdown totals for the selected period
            $deptSql = "SELECT department, SUM(amount) as dept_total FROM expenses e WHERE $dateCondition GROUP BY department";
            $deptRows = $pdo->query($deptSql)->fetchAll(PDO::FETCH_KEY_PAIR);

            json_out(['ok' => true, 'rows' => $rows, 'total' => $total, 'by_department' => $deptRows]);
            break;
            
        default:
            json_out(['error' => 'Unknown action.'], 400);
    }
} catch (PDOException $ex) {
    json_out(['error' => 'Database error: ' . $ex->getMessage()], 500);
}
?>