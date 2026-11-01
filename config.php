<?php 
declare(strict_types=1); 
session_start(); 

define('DB_HOST', 'localhost'); 
define('DB_NAME', 'bms_db'); 
define('DB_USER', 'root'); 
define('DB_PASS', ''); 
define('CURRENCY', 'UGX');

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        $pdo->exec("SET time_zone = '+03:00'");
    }
    return $pdo;
}

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function current_user(): ?array { return $_SESSION['user'] ?? null; }

function require_login(): void { 
    if (!current_user()) { 
        header('Location: login.php'); 
        exit; 
    }
    
    if (!empty($_SESSION['sess_id'])) {
        // Throttle database check to once every 30 seconds to minimize DB overhead
        $last_check = $_SESSION['last_logout_check'] ?? 0;
        if (time() - $last_check > 30) {
            try {
                $st = db()->prepare("SELECT logout_time FROM login_sessions WHERE session_id = ?");
                $st->execute([(int)$_SESSION['sess_id']]);
                $logout = $st->fetchColumn();
                
                if ($logout !== null) {
                    session_destroy();
                    header('Location: login.php?msg=force_logout');
                    exit;
                }
                $_SESSION['last_logout_check'] = time();
            } catch (Throwable $e) {}
        }
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function json_out($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

const STATUS_SQL = "CASE WHEN p.bottles_available = 0 THEN 'Out of Stock' 
    WHEN p.bottles_available <= p.low_stock_threshold THEN 'Low Stock' 
    ELSE 'Available' END";

function fetch_products(PDO $pdo, string $q = '', int $cat = 0, string $status = ''): array {
    $sql = "SELECT p.product_id, p.product_name, p.category_id, c.name AS category,
            p.bottles_available, p.price_per_bottle AS price, 
            COALESCE(p.buying_price, 0) AS buying_price, 
            p.low_stock_threshold,
            " . STATUS_SQL . " AS status
            FROM products p JOIN categories c ON c.category_id = p.category_id
            WHERE p.is_active = 1";
    $params = [];
    if ($q != '') { $sql .= " AND p.product_name LIKE :q"; $params[":q"] = "%$q%"; }
    if ($cat > 0) { $sql .= " AND p.category_id = :c"; $params[":c"] = $cat; }
    if ($status == "out") $sql .= " AND p.bottles_available = 0";
    elseif ($status == "low") $sql .= " AND p.bottles_available > 0 AND p.bottles_available <= p.low_stock_threshold";
    elseif ($status == "available") $sql .= " AND p.bottles_available > p.low_stock_threshold";
    
    $sql .= " ORDER BY p.product_name ASC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function home_for(string $role): string { 
    if ($role === 'admin') return 'dashboard.php';
    if ($role === 'hr') return 'hr_dashboard.php';
    if ($role === 'manager') return 'manager_dashboard.php';
    return 'pos.php'; 
}

function require_admin(): void {
    require_login();
    $role = current_user()['role'];
    if (!in_array($role, ['admin', 'hr'], true)) { 
        header('Location: ' . home_for($role)); 
        exit; 
    }
}

function require_hr(): void {
    require_login();
    if (current_user()['role'] !== 'hr') {
        header('Location: ' . home_for(current_user()['role']));
        exit;
    }
}

function require_manager(): void {
    require_login();
    $role = current_user()['role'];
    if (!in_array($role, ['manager', 'admin', 'hr'], true)) {
        header('Location: ' . home_for($role));
        exit;
    }
}

/* --------- Login session tracking + activity log --------- */
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45); }

// --- ADDED LOCATION HELPER FUNCTION HERE ---
function get_ip_location(string $ip): string {
    if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
        return 'Local Network';
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 2,
            'header'  => "User-Agent: BigWallet/1.0\r\n"
        ]
    ]);

    // Try ip-api.com first
    $response = @file_get_contents(
        "http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,district", 
        false, 
        $context
    );

    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['status']) && $data['status'] === 'success') {
            $parts = array_filter([
                $data['city']       ?? '',
                $data['district']   ?? '',
                $data['regionName'] ?? '',
                $data['country']    ?? ''
            ]);
            if ($parts) {
                return implode(', ', array_unique($parts));
            }
        }
    }

    // Fallback: try ipapi.co
    $fallback = @file_get_contents("https://ipapi.co/{$ip}/json/", false, $context);
    if ($fallback) {
        $data = json_decode($fallback, true);
        if (!empty($data['city']) || !empty($data['region']) || !empty($data['country_name'])) {
            $parts = array_filter([
                $data['city']         ?? '',
                $data['region']       ?? '',
                $data['country_name'] ?? ''
            ]);
            if ($parts) return implode(', ', $parts);
        }
    }

    return 'Unknown Location';
}
// ------------------------------------------

function start_login_session(array $u): void {
    try {
        $ip = client_ip();
        $location = get_ip_location($ip); // Calculate location ONLY once at login
        
        db()->prepare("INSERT INTO login_sessions (user_id, username, ip_address, user_agent, location) VALUES (?,?,?,?,?)")
            ->execute([(int)$u['user_id'], $u['username'], $ip, substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255), $location]);
        $_SESSION['sess_id'] = (int)db()->lastInsertId();
        $_SESSION['touched'] = time();
    } catch (Throwable $e) {}
}

function end_login_session(): void {
    if (empty($_SESSION['sess_id'])) return;
    try {
        db()->prepare("UPDATE login_sessions SET logout_time = NOW(), last_activity = NOW() WHERE session_id = ? AND logout_time IS NULL")
            ->execute([(int)$_SESSION['sess_id']]);
    } catch (Throwable $e) {}
}

function log_activity(string $action, string $details = '', ?array $actor = null): void {
    try {
        $u = $actor ?? current_user() ?? [];
        db()->prepare("INSERT INTO activity_log (session_id, user_id, username, action, details, ip_address) VALUES (?,?,?,?,?,?)")
            ->execute([$_SESSION['sess_id'] ?? null, $u['user_id'] ?? null, $u['username'] ?? ($u['name'] ?? null), $action, mb_substr($details, 0, 500), client_ip()]);
    } catch (Throwable $e) {}
}

if (!empty($_SESSION['user']) && !empty($_SESSION['sess_id']) && (time() - (int)($_SESSION['touched'] ?? 0)) > 60) {
    try {
        db()->prepare("UPDATE login_sessions SET last_activity = NOW() WHERE session_id = ? AND logout_time IS NULL")->execute([(int)$_SESSION['sess_id']]);
    } catch (Throwable $e) {}
    $_SESSION['touched'] = time();
}