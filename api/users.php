<?php
require __DIR__ . '/../config.php';

$user = current_user();
if (!$user) json_out(['error' => 'Please log in again.'], 401);

// Allow HR, Admin, and Manager
if (!in_array($user['role'], ['admin', 'manager', 'hr'], true)) json_out(['error' => 'Access denied.'], 403);

$pdo = db();
$action = $_GET['action'] ?? '';
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$in = $isPost ? (json_decode(file_get_contents('php://input'), true) ?: []) : [];

if ($isPost && !hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')) {
    json_out(['error' => 'Invalid session token.'], 403);
}

try {
    switch ($action) {
        case 'list':
            json_out($pdo->query("SELECT user_id, full_name, username, role, is_active, created_at, pending_password_hash FROM users ORDER BY created_at DESC")->fetchAll());
            break;

        case 'active_sessions':
            // Only HR and Admin can see this
            if (!in_array($user['role'], ['hr', 'admin'])) json_out(['error' => 'Access denied.'], 403);
            
            $sessions = $pdo->query("
                SELECT session_id, username, ip_address, user_agent, login_time, last_activity 
                FROM login_sessions 
                WHERE logout_time IS NULL 
                ORDER BY last_activity DESC
            ")->fetchAll();
            
            // Format location safely without external blocking HTTP calls
            foreach ($sessions as &$s) {
                $ip = $s['ip_address'];
                if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
                    $s['location'] = 'Local Network';
                } else {
                    $s['location'] = 'IP: ' . e($ip);
                }
            }
            
            json_out($sessions);
            break;

        case 'force_logout':
            if (!in_array($user['role'], ['hr', 'admin'])) json_out(['error' => 'Access denied.'], 403);

            $session_id = (int)($in['session_id'] ?? 0);
            if (!$session_id) json_out(['error' => 'Invalid session ID.'], 422);

            if ($session_id === (int)($_SESSION['sess_id'] ?? 0)) {
                json_out(['error' => 'You cannot force-logout your own session.'], 422);
            }

            $t = $pdo->prepare("SELECT username FROM login_sessions WHERE session_id = ? AND logout_time IS NULL");
            $t->execute([$session_id]);
            $target = $t->fetchColumn();

            if (!$target) {
                json_out(['error' => 'Session not found or already ended.'], 404);
            }

            $pdo->prepare("UPDATE login_sessions SET logout_time = NOW(), last_activity = NOW() WHERE session_id = ? AND logout_time IS NULL")
                ->execute([$session_id]);

            log_activity('force_logout', "Forced logout for user: $target (Session #$session_id)");
            json_out(['ok' => true, 'message' => "$target has been logged out successfully."]);
            break;

        case 'create':
            if (!in_array($user['role'], ['hr', 'admin'])) json_out(['error' => 'Only HR can create users.'], 403);

            $name = trim($in['full_name'] ?? '');
            $uname = trim($in['username'] ?? '');
            $pass = (string)($in['password'] ?? '');
            $role = $in['role'] ?? '';
            
            if (mb_strlen($name) < 2 || mb_strlen($name) > 100) json_out(['error' => 'Enter the full name.'], 422);
            if (!preg_match('/^[A-Za-z0-9_\.]{3,30}$/', $uname)) json_out(['error' => 'Username: 3 to 30 letters, numbers, dot or underscore.'], 422);
            if (strlen($pass) < 8) json_out(['error' => 'Password must be at least 8 characters.'], 422);
            if (!in_array($role, ['admin', 'cashier', 'manager', 'hr'], true)) json_out(['error' => 'Choose a valid role.'], 422);
            
            $pdo->prepare("INSERT INTO users (full_name, username, password_hash, role) VALUES (?,?,?,?)")
                ->execute([$name, $uname, password_hash($pass, PASSWORD_DEFAULT), $role]);
            log_activity('account_create', "$name ($role)");
            json_out(['ok' => true, 'message' => "Account '$name' created."]);
            break;

        case 'toggle':
            $id = (int)($in['user_id'] ?? 0);
            if ($id === (int)$user['user_id']) json_out(['error' => 'You cannot disable your own account.'], 422);
            
            $t = $pdo->prepare("SELECT username, is_active, role FROM users WHERE user_id = ?");
            $t->execute([$id]);
            $row = $t->fetch();
            if (!$row) json_out(['error' => 'Account not found.'], 404);
            
            if ($user['role'] === 'admin' && $row['role'] === 'hr') {
                json_out(['error' => 'Admins cannot disable HR accounts.'], 403);
            }
            
            $pdo->prepare("UPDATE users SET is_active = 1 - is_active WHERE user_id = ?")->execute([$id]);
            log_activity($row['is_active'] ? 'account_disabled' : 'account_enabled', "Account: {$row['username']}");
            json_out(['ok' => true]);
            break;

        case 'approve_reset':
            if ($user['role'] !== 'hr') json_out(['error' => 'Only HR can approve password resets.'], 403);
            
            $id = (int)($in['user_id'] ?? 0);
            $t = $pdo->prepare("SELECT username, pending_password_hash FROM users WHERE user_id = ?");
            $t->execute([$id]);
            $row = $t->fetch();
            if (!$row) json_out(['error' => 'User not found.'], 404);
            if (!$row['pending_password_hash']) json_out(['error' => 'No pending password request for this user.'], 400);

            $pdo->prepare("UPDATE users SET password_hash = pending_password_hash, pending_password_hash = NULL WHERE user_id = ?")->execute([$id]);
            log_activity('password_approved', "Password reset approved for {$row['username']}");
            json_out(['ok' => true, 'message' => 'Password updated successfully.']);
            break;

        case 'reject_reset':
            if ($user['role'] !== 'hr') json_out(['error' => 'Only HR can reject password resets.'], 403);

            $id = (int)($in['user_id'] ?? 0);
            $t = $pdo->prepare("SELECT username, pending_password_hash FROM users WHERE user_id = ?");
            $t->execute([$id]);
            $row = $t->fetch();
            if (!$row) json_out(['error' => 'User not found.'], 404);

            $pdo->prepare("UPDATE users SET pending_password_hash = NULL WHERE user_id = ?")->execute([$id]);
            log_activity('password_rejected', "Password reset rejected for {$row['username']}");
            json_out(['ok' => true, 'message' => 'Password request rejected.']);
            break;

        case 'delete':
            $id = (int)($in['user_id'] ?? 0);
            if ($id === (int)$user['user_id']) json_out(['error' => 'You cannot delete your own account.'], 422);
            
            $nm = $pdo->prepare("SELECT username, role FROM users WHERE user_id = ?");
            $nm->execute([$id]);
            $targetUser = $nm->fetch();
            if (!$targetUser) json_out(['error' => 'Account not found.'], 404);

            if ($user['role'] === 'admin' && $targetUser['role'] === 'hr') {
                json_out(['error' => 'Admins cannot delete HR accounts.'], 403);
            }

            try {
                $st = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $st->execute([$id]);
            } catch (PDOException $ex) {
                json_out(['error' => 'This account has stock, sales or room records and cannot be deleted. Disable it instead.'], 409);
            }
            
            if ($st->rowCount()) log_activity('account_delete', "Account: {$targetUser['username']} (Role: {$targetUser['role']})");
            json_out($st->rowCount() ? ['ok' => true, 'message' => 'Account deleted.'] : ['error' => 'Account not found.'], $st->rowCount() ? 200 : 404);
            break;

        case 'reset_system':
            if ($user['role'] !== 'hr') json_out(['error' => 'Only the HR administrator can reset the database.'], 403);
            $tables = ['sales_record_items','sales_record','room_payments','room_bookings','rooms','expenses','stock_entries','products','activity_log','login_sessions'];
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            foreach ($tables as $t) $pdo->exec("TRUNCATE TABLE `$t`;");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            json_out(['ok' => true, 'message' => 'System reset successfully.']);
            break;

        default:
            json_out(['error' => 'Unknown action.'], 400);
    }
} catch (PDOException $ex) {
    if ($ex->getCode() === '23000') json_out(['error' => 'That username is already taken.'], 409);
    json_out(['error' => 'Database error: ' . $ex->getMessage()], 500);
}
?>