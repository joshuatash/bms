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
            
            // Fetch real location based on IP
            foreach ($sessions as &$s) {
                $ip = $s['ip_address'];
                
                // Get the real location (City, Region, Country)
                $location = get_ip_location($ip);
                
                if ($location === 'Local Network') {
                    $s['location'] = 'Local Network';
                } else {
                    // ONLY the location, no IP address added
                    $s['location'] = $location;
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

        // ... (keep your create, toggle, approve_reset, reject_reset, delete, reset_system cases here) ...

        default:
            json_out(['error' => 'Unknown action.'], 400);
    }
} catch (PDOException $ex) {
    if ($ex->getCode() === '23000') json_out(['error' => 'That username is already taken.'], 409);
    json_out(['error' => 'Database error: ' . $ex->getMessage()], 500);
}

/**
 * Fetch real location from IP address
 */
function get_ip_location(string $ip): string {
    if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
        return 'Local Network';
    }

    $context = stream_context_create(['http' => ['timeout' => 2, 'header' => "User-Agent: BigWallet/1.0\r\n"]]);

    $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,district", false, $context);

    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['status']) && $data['status'] === 'success') {
            $parts = array_filter([
                $data['city']       ?? '',
                $data['regionName'] ?? '',
                $data['country']    ?? ''
            ]);
            if ($parts) return implode(', ', array_unique($parts));
        }
    }

    $fallback = @file_get_contents("https://ipapi.co/{$ip}/json/", false, $context);
    if ($fallback) {
        $data = json_decode($fallback, true);
        if (!empty($data['city']) || !empty($data['country_name'])) {
            return implode(', ', array_filter([$data['city'] ?? '', $data['country_name'] ?? '']));
        }
    }

    return 'Location Unavailable';
}
?>