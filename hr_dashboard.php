<?php
require __DIR__ . '/config.php';
require_hr(); // Restricts this page to ONLY the HR user
$user = current_user();
$active = 'hr_dashboard';
$passError = '';
$passSuccess = '';
$logSuccess = '';
$backupError = '';
$activePanel = 'users';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedAction = (string)($_POST['do'] ?? '');
    if ($submittedAction === 'backup_database') $activePanel = 'backup';
    elseif ($submittedAction === 'change_my_password') $activePanel = 'settings';
    elseif ($submittedAction === 'clear_logs') $activePanel = 'activity';
}
// Handle HR Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'change_my_password') {
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $csrf = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', $csrf)) {
        $passError = 'Your session expired. Reload the page and try again.';
    } elseif ($new !== $confirm) {
        $passError = 'New passwords do not match.';
    } elseif (strlen($new) < 8) {
        $passError = 'New password must be at least 8 characters.';
    } else {
        $pdo = db();
        $ps = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $ps->execute([$user['user_id']]);
        $hash = (string)$ps->fetchColumn();
        if (!$hash || !password_verify($current, $hash)) {
            $passError = 'Your current password is incorrect.';
            log_activity('password_change_failed', 'Wrong current password provided');
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")->execute([$newHash, $user['user_id']]);
            log_activity('password_change', 'Changed own account password');
            $passSuccess = 'Your password has been successfully updated!';
        }
    }
}
// Handle Clear Activity Logs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'clear_logs') {
    $csrf = (string)($_POST['csrf'] ?? '');
    if (hash_equals($_SESSION['csrf'] ?? '', $csrf)) {
        $pdo = db();
        $pdo->exec("TRUNCATE TABLE activity_log");
        log_activity('clear_logs', 'Cleared all system activity logs');
        $logSuccess = 'Activity logs cleared successfully.';
    }
}
// Handle Database Backup Export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'backup_database') {
    $csrf = (string)($_POST['csrf'] ?? '');
    if (hash_equals($_SESSION['csrf'] ?? '', $csrf)) {
        try {
            $pdo = db();
            $tables = [];
            $result = $pdo->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            $sql = "-- Big Wallet System Database Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Authorized HR: " . $user['username'] . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            foreach ($tables as $table) {
                $row = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
                $sql .= "\n\n" . $row[1] . ";\n\n";
                $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    $cols = array_keys($r);
                    $vals = array_values($r);
                    $escaped_cols = array_map(fn($c) => "`$c`", $cols);
                    $escaped_vals = array_map(function($v) use ($pdo) {
                        if ($v === null) return "NULL";
                        return $pdo->quote($v);
                    }, $vals);
                    $sql .= "INSERT INTO `$table` (" . implode(', ', $escaped_cols) . ") VALUES (" . implode(', ', $escaped_vals) . ");\n";
                }
            }
            $sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
            log_activity('database_backup', 'HR downloaded database backup file');
            $filename = 'bigwallet_backup_' . date('Y-m-d_H-i-s') . '.sql';
            header('Content-Type: application/sql; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($sql));
            echo $sql;
            exit;
        } catch (Exception $e) {
            $backupError = 'Failed to generate backup: ' . $e->getMessage();
        }
    } else {
        $backupError = 'Your session expired. Reload the page and try again.';
    }
}
// Fetch recent activity logs
$pdo = db();
$logs = $pdo->query("
    SELECT username, action, details, ip_address, created_at
    FROM activity_log
    ORDER BY created_at DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);
// NEW: Fetch the latest 10 failed logins specifically for the reasons list
$stmtFailed = $pdo->query("
    SELECT username, action, details, ip_address, created_at
    FROM activity_log
    WHERE action IN ('login_failed', 'login_blocked')
    ORDER BY created_at DESC
    LIMIT 10
");
$failedLoginsList = $stmtFailed->fetchAll(PDO::FETCH_ASSOC);
// Helper function to resolve IP to location if it's not in the logs
if (!function_exists('resolve_ip_location')) {
    function resolve_ip_location($ip) {
        if (!$ip || $ip === '::1' || $ip === '127.0.0.1') return 'Local Network';
        if (!filter_var($ip, FILTER_VALIDATE_IP) || preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/i', $ip)) return 'Local Network';
        
        $ctx = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
        $json = @file_get_contents('http://ip-api.com/json/' . urlencode($ip) . '?fields=status,country,regionName,city', false, $ctx);
        if ($json) {
            $d = json_decode($json, true);
            if (is_array($d) && ($d['status'] ?? '') === 'success') {
                $parts = array_filter([$d['city'] ?? '', $d['regionName'] ?? '', $d['country'] ?? '']);
                if ($parts) return implode(', ', $parts);
            }
        }
        return 'Unknown Location';
    }
}
// Counts for the top stat cards (based on the latest 50)
$failedLogins = 0;
$totalLogins = 0;
foreach ($logs as $log) {
    $action = strtolower((string)($log['action'] ?? ''));
    if (strpos($action, 'login') !== false) {
        if (strpos($action, 'failed') !== false || strpos($action, 'blocked') !== false) {
            $failedLogins++;
        } else {
            $totalLogins++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HR Dashboard | Big Wallet</title>
    <style>
        :root {
            --navy:#0f172a; --slate:#334155; --muted:#64748b; --line:#e2e8f0;
            --amber:#f59e0b; --green:#16a34a; --red:#dc2626; --radius:14px;
            /* Dark theme (matches POS) */
            --surface:#111c2e;
            --surface-muted:#16243a;
            --surface-3:#1e2d47;
            --text:#e2e8f0;
            --text-dim:#94a3b8;
            --text-mute:#64748b;
            --border:#243349;
            --shadow-sm:0 1px 2px rgba(0,0,0,.3);
            --shadow-md:0 4px 12px rgba(0,0,0,.4), 0 1px 3px rgba(0,0,0,.25);
            --shadow-lg:0 12px 28px rgba(0,0,0,.5), 0 2px 6px rgba(0,0,0,.3);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0b1220;
            color: var(--text);
            min-height: 100vh;
        }
        main { max-width: 1000px; margin: 28px auto; padding: 0 18px 40px; display: flex; flex-direction: column; gap: 20px; }
        .head h2 { margin: 0; font-size: 1.6rem; letter-spacing: -.2px; color: var(--text); }
        .head .sub { margin: 4px 0 0; color: var(--text-dim); font-size: .95rem; }
        /* ── Dashboard summary cards ── */
        .stats-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin:2px 0}
        .stat-card{
            position:relative;
            overflow:hidden;
            background:var(--surface);
            border:1px solid var(--border);
            border-radius:var(--radius);
            padding:18px 18px 16px;
            box-shadow:var(--shadow-sm);
            transition:transform .18s ease, box-shadow .18s ease;
        }
        .stat-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-md)}
        .stat-card::before{
            content:"";
            position:absolute;
            left:0; top:0; bottom:0;
            width:4px;
            background:var(--stat-accent,var(--text-dim));
        }
        .stat-card::after{
            content:"";
            position:absolute;
            width:70px;height:70px;right:-24px;top:-24px;
            border-radius:50%;
            background:var(--stat-accent,var(--text-dim));
            opacity:.12;
        }
        .stat-label{color:var(--text-dim);font-size:.74rem;font-weight:700;letter-spacing:.4px;text-transform:uppercase}
        .stat-value{display:block;margin-top:10px;color:var(--text);font-size:1.85rem;line-height:1;font-weight:800;font-variant-numeric:tabular-nums}
        .stat-note{display:block;margin-top:8px;color:var(--text-dim);font-size:.72rem;line-height:1.3}
        /* Each card gets one accent color, used consistently in the bar, blob, and value */
        .stat-card.accounts{--stat-accent:#3b82f6}
        .stat-card.active{--stat-accent:#16a34a}
        .stat-card.online{--stat-accent:#0d9488}
        .stat-card.failed{--stat-accent:#dc2626}
        .stat-card.logins{--stat-accent:#d97706}
        .stat-card.accounts .stat-value{color:#60a5fa}
        .stat-card.active .stat-value{color:#4ade80}
        .stat-card.online .stat-value{color:#2dd4bf}
        .stat-card.failed .stat-value{color:#f87171}
        .stat-card.logins .stat-value{color:#fbbf24}
        @media(max-width:900px){.stats-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media(max-width:640px){.stats-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.stat-card{padding:14px}.stat-value{font-size:1.5rem}}
        /* ── Cards ── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px;
            box-shadow: var(--shadow-sm);
        }
        .card-head { margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 14px; flex-wrap: wrap; gap: 10px; }
        .card-head h2 { margin: 0; font-size: 1.15rem; letter-spacing: -.1px; color: var(--text); }
        .card-head p { margin: 4px 0 0; color: var(--text-dim); font-size: .88rem; }
        /* Forms & Buttons */
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .grid-full { grid-column: span 2; }
        label { display: block; font-size: .72rem; font-weight: 700; color: var(--text-dim); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .45px; }
        input, select { width: 100%; padding: 11px 12px; border: 1px solid var(--border); border-radius: 10px; font-size: .95rem; background: var(--surface-muted); color: var(--text); transition: border-color .15s, box-shadow .15s; }
        input::placeholder { color: var(--text-mute); }
        input:focus, select:focus { outline: none; border-color: var(--amber); box-shadow: 0 0 0 3px rgba(245,158,11,.22); background: var(--surface-3); }
        .btn { border: 0; border-radius: 10px; padding: 11px 18px; min-height: 42px; font-weight: 700; cursor: pointer; font-size: .9rem; font-family: inherit; text-decoration: none; display: inline-block; text-align: center; transition: background .15s, transform .05s, box-shadow .15s; }
        .btn:active:not(:disabled) { transform: translateY(1px); }
        .btn.primary { background: var(--amber); color: #0b1220; }
        .btn.primary:hover:not(:disabled) { background: #d97706; color: #fff; box-shadow: 0 4px 12px rgba(217,119,6,.3); }
        .btn.ghost { background: var(--surface-muted); color: var(--text-dim); }
        .btn.ghost:hover:not(:disabled) { background: var(--surface-3); color: var(--text); }
        .btn.danger { background: var(--red); color: #fff; }
        .btn.danger:hover:not(:disabled) { background: #b91c1c; box-shadow: 0 4px 12px rgba(185,28,28,.3); }
        .btn.sm { padding: 7px 13px; min-height: 34px; font-size: .8rem; border-radius: 8px; }
        #save, #btnChangePass { width: 100%; margin-top: 4px; }
        .hint { font-size: .88rem; margin-top: 12px; min-height: 1.2em; color: var(--text-dim); }
        .hint.good { color: #4ade80; font-weight: 600; }
        .hint.bad { color: #f87171; font-weight: 600; }
        .alert { padding: 14px 16px; border-radius: 10px; font-weight: 600; margin-bottom: 16px; font-size: 0.92rem; border: 1px solid transparent; }
        .alert.ok { background: rgba(22,163,74,.15); color: #4ade80; border-color: rgba(22,163,74,.3); }
        .alert.err { background: rgba(220,38,38,.15); color: #f87171; border-color: rgba(220,38,38,.3); }
        /* ── Standard table (User Management) ── */
        .tablewrap { overflow-x: auto; margin: 0 -22px -22px; padding: 0 22px; }
        table { width: 100%; border-collapse: collapse; font-size: .9rem; color: var(--text); }
        th { text-align: left; background: var(--surface-muted); color: var(--text-dim); padding: 12px 10px; font-size: .72rem; text-transform: uppercase; letter-spacing: .45px; border-bottom: 2px solid var(--border); white-space: nowrap; }
        td { padding: 12px 10px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tbody tr:hover td { background: var(--surface-muted); }
        .badge { padding: 4px 11px; border-radius: 999px; font-size: .74rem; font-weight: 700; white-space: nowrap; text-transform: capitalize; }
        .badge.admin { background: rgba(37,99,235,.2); color: #60a5fa; }
        .badge.cashier { background: rgba(245,158,11,.2); color: #fbbf24; }
        .badge.manager { background: rgba(139,92,246,.2); color: #a78bfa; }
        .badge.hr { background: rgba(236,72,153,.2); color: #f472b6; }
        .badge.on { background: rgba(22,163,74,.2); color: #4ade80; }
        .badge.off { background: rgba(220,38,38,.2); color: #f87171; }
        .actions-cell { display: flex; flex-direction: column; gap: 6px; align-items: flex-start; }
        .action-group { display: flex; gap: 6px; flex-wrap: wrap; }
        .pending-alert { background: rgba(245,158,11,.12); border: 1px solid rgba(245,158,11,.35); padding: 8px; border-radius: 8px; width: 100%; margin-bottom: 4px; }
        .pending-alert p { margin: 0 0 6px; font-size: 0.75rem; color: #fbbf24; font-weight: 700; text-transform: uppercase; }
        /* ── ONLINE DOT ANIMATION ── */
        .online-dot { display: inline-block; width: 10px; height: 10px; background-color: var(--green); border-radius: 50%; margin-right: 6px; box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); animation: pulse 1.5s infinite; flex-shrink: 0; }
        @keyframes pulse { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(22, 163, 74, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); } }
        /* ── ACTIVE SESSIONS — desktop table ── */
        .sessions-table th { background: var(--surface-3); color: var(--text); border-bottom: none; }
        .sessions-table td { font-size: 0.85rem; }
        /* ── ACTIVE SESSIONS — mobile card stack ── */
        .session-cards { display: none; flex-direction: column; gap: 12px; }
        .session-card { background: var(--surface-muted); border: 1px solid var(--border); border-radius: 12px; padding: 14px 16px; }
        .session-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .session-card-username { display: flex; align-items: center; font-weight: 700; font-size: .95rem; }
        .session-card-rows { display: grid; grid-template-columns: auto 1fr; gap: 6px 10px; font-size: .82rem; margin-bottom: 12px; }
        .session-card-label { color: var(--text-dim); font-weight: 600; white-space: nowrap; padding-top: 1px; }
        .session-card-value { color: var(--text); word-break: break-all; }
        /* ── MODERN TIMELINE ── */
        .timeline { position: relative; padding-left: 10px; margin-top: 10px; }
        .timeline::before { content: ''; position: absolute; top: 0; bottom: 0; left: 20px; width: 2px; background: var(--border); }
        .timeline-item { position: relative; padding-left: 45px; padding-bottom: 24px; }
        .timeline-item:last-child { padding-bottom: 0; }
        .timeline-dot { position: absolute; left: 12px; top: 4px; width: 18px; height: 18px; border-radius: 50%; background: var(--surface); border: 3px solid var(--border); z-index: 2; }
        .timeline-dot.login { border-color: var(--green); background: rgba(22,163,74,.2); }
        .timeline-dot.logout { border-color: #64748b; background: var(--surface-muted); }
        .timeline-dot.delete, .timeline-dot.reset, .timeline-dot.rejected { border-color: var(--red); background: rgba(220,38,38,.2); }
        .timeline-dot.create, .timeline-dot.register, .timeline-dot.approved { border-color: #3b82f6; background: rgba(37,99,235,.2); }
        .timeline-dot.update, .timeline-dot.enable { border-color: var(--amber); background: rgba(245,158,11,.2); }
        .timeline-content { background: var(--surface-muted); border: 1px solid var(--border); border-radius: 12px; padding: 16px; transition: transform 0.2s, box-shadow 0.2s, background .2s; }
        .timeline-content:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); background: var(--surface); }
        .timeline-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; flex-wrap: wrap; gap: 10px; }
        .timeline-user { font-size: 1rem; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .timeline-user small { font-weight: 500; color: var(--text-dim); font-size: 0.85rem; }
        .timeline-meta { font-size: 0.78rem; color: var(--text-dim); display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .timeline-body { font-size: 0.95rem; color: var(--text-dim); line-height: 1.5; }
        .action-tag { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .action-tag.login { background: rgba(22,163,74,.2); color: #4ade80; }
        .action-tag.logout { background: var(--surface-3); color: var(--text-dim); }
        .action-tag.reset, .action-tag.delete, .action-tag.rejected, .action-tag.login_failed { background: rgba(220,38,38,.2); color: #f87171; }
        .action-tag.create, .action-tag.register, .action-tag.approved { background: rgba(37,99,235,.2); color: #60a5fa; }
        .action-tag.update, .action-tag.enable { background: rgba(245,158,11,.2); color: #fbbf24; }
        .action-tag.default { background: var(--surface-3); color: var(--text-dim); }
        /* ── FAILED LOGIN REASONS ── */
        .failed-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
        .failed-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 14px;
            background: rgba(220,38,38,.08);
            border: 1px solid rgba(220,38,38,.25);
            border-radius: 10px;
        }
        .failed-row .f-icon {
            flex-shrink: 0;
            width: 28px; height: 28px;
            border-radius: 50%;
            background: rgba(220,38,38,.2);
            color: #f87171;
            display: grid; place-items: center;
            font-weight: 800;
            font-size: .9rem;
        }
        .failed-row .f-body { flex: 1; min-width: 0; }
        .failed-row .f-user { font-weight: 700; color: #f87171; font-size: .92rem; }
        
        .failed-row .f-reason-label { font-size: .75rem; color: var(--text-dim); margin-top: 4px; text-transform: uppercase; font-weight: 700; letter-spacing: .4px;}
        .failed-row .f-reason { color: #f87171; font-size: .88rem; margin-top: 2px; word-break: break-word; }
        
        .failed-row .f-location-label { font-size: .75rem; color: var(--text-dim); margin-top: 6px; text-transform: uppercase; font-weight: 700; letter-spacing: .4px;}
        .failed-row .f-location { color: var(--text); font-size: .88rem; margin-top: 2px; }
        
        .failed-row .f-meta { color: var(--text-dim); font-size: .78rem; margin-top: 6px; display: flex; flex-wrap: wrap; gap: 10px; }
        .failed-empty {
            text-align: center;
            color: var(--text-mute);
            font-size: .88rem;
            padding: 18px 0;
        }
        /* ── RESPONSIVE BREAKPOINTS ── */
        @media (max-width: 640px) {
            main { padding: 0 12px 32px; margin: 14px auto; gap: 14px; }
            .head h2 { font-size: 1.3rem; }
            .grid { grid-template-columns: 1fr; }
            .grid-full { grid-column: span 1; }
            .card { padding: 16px; }
            .card-head { margin-bottom: 14px; padding-bottom: 12px; }
            .tablewrap { margin: 0 -16px -16px; padding: 0 16px; }
            th, td { padding: 10px 8px; }
            .sessions-tablewrap { display: none; }
            .session-cards { display: flex; }
            .timeline::before { left: 16px; }
            .timeline-item { padding-left: 38px; }
            .timeline-dot { left: 8px; }
            .timeline-header { flex-direction: column; gap: 4px; }
            .timeline-meta { flex-direction: row; flex-wrap: wrap; gap: 8px; }
            .timeline-content { padding: 12px; }
            .timeline-body { font-size: .88rem; }
        }
        @media (max-width: 400px) {
            main { padding: 0 8px 24px; margin: 8px auto; }
            .card { padding: 12px; }
            .session-card-rows { grid-template-columns: 1fr; }
            .session-card-label { font-weight: 700; }
        }
        /* ── TOAST NOTIFICATIONS ── */
        #toast-container { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
        .toast { display: flex; align-items: center; gap: 12px; min-width: 280px; max-width: 380px; padding: 14px 18px; border-radius: 14px; font-size: .92rem; font-weight: 600; color: #fff; box-shadow: 0 8px 28px rgba(0,0,0,.4); pointer-events: all; animation: toast-in .3s cubic-bezier(.34,1.56,.64,1) forwards; }
        .toast.hiding { animation: toast-out .3s ease forwards; }
        .toast.success { background: #166534; }
        .toast.error { background: #991b1b; }
        .toast-icon { flex-shrink: 0; width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; }
        .toast.success .toast-icon { background: rgba(255,255,255,.2); }
        .toast.error .toast-icon { background: rgba(255,255,255,.2); }
        .toast-msg { flex: 1; line-height: 1.4; }
        .toast-close { flex-shrink: 0; background: none; border: none; color: rgba(255,255,255,.7); cursor: pointer; font-size: 1.1rem; line-height: 1; padding: 2px 4px; border-radius: 4px; }
        .toast-close:hover { color: #fff; background: rgba(255,255,255,.15); }
        @keyframes toast-in { from { opacity: 0; transform: translateX(60px) scale(.9); } to { opacity: 1; transform: translateX(0) scale(1); } }
        @keyframes toast-out { from { opacity: 1; transform: translateX(0) scale(1); } to { opacity: 0; transform: translateX(60px) scale(.9); } }
        /* ── CONFIRM MODAL ── */
        .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.7); backdrop-filter: blur(4px); z-index: 10000; display: grid; place-items: center; padding: 20px; opacity: 0; animation: fade-in .2s ease forwards; }
        .modal-backdrop.hiding { animation: fade-out .2s ease forwards; }
        .modal { background: var(--surface); border: 1px solid var(--border); border-radius: 18px; padding: 28px 28px 22px; max-width: 400px; width: 100%; box-shadow: 0 24px 60px rgba(0,0,0,.6); transform: scale(.92) translateY(16px); animation: modal-in .25s cubic-bezier(.34,1.4,.64,1) forwards; }
        .modal-backdrop.hiding .modal { animation: modal-out .2s ease forwards; }
        .modal-icon { width: 52px; height: 52px; border-radius: 50%; display: grid; place-items: center; margin: 0 auto 16px; }
        .modal-icon.danger { background: rgba(220,38,38,.15); }
        .modal-icon.warning { background: rgba(245,158,11,.15); }
        .modal-title { font-size: 1.1rem; font-weight: 800; color: var(--text); text-align: center; margin: 0 0 8px; }
        .modal-body { font-size: .92rem; color: var(--text-dim); text-align: center; line-height: 1.55; margin: 0 0 22px; }
        .modal-body strong { color: var(--text); }
        .modal-actions { display: flex; gap: 10px; }
        .modal-actions .btn { flex: 1; justify-content: center; }
        @keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fade-out { from { opacity: 1; } to { opacity: 0; } }
        @keyframes modal-in { from { transform: scale(.92) translateY(16px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }
        @keyframes modal-out { from { transform: scale(1) translateY(0); opacity: 1; } to { transform: scale(.92) translateY(16px); opacity: 0; } }
        .tick-circle { stroke: #fff; stroke-width: 2.5; fill: none; stroke-dasharray: 50; stroke-dashoffset: 50; animation: tick-draw .4s .1s ease forwards; }
        @keyframes tick-draw { to { stroke-dashoffset: 0; } }
        /* ── Dashboard Nav ── */
        .dashboard-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 8px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
        }
        .dashboard-nav button {
            border: 0;
            background: transparent;
            color: var(--text-dim);
            border-radius: 9px;
            padding: 11px 16px;
            font: inherit;
            font-size: .9rem;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s ease, color .2s ease;
        }
        .dashboard-nav button:hover { background: var(--surface-muted); color: var(--text); }
        .dashboard-nav button.active {
            background: var(--amber);
            color: #0b1220;
            box-shadow: 0 4px 10px rgba(245,158,11,.3);
        }
        .dashboard-panel[hidden] { display: none !important; }
        .dashboard-panel { scroll-margin-top:18px; }
        @media (max-width: 480px) {
            .dashboard-nav { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); }
            .dashboard-nav button { text-align:center; padding:10px 8px; }
            #toast-container { bottom: 16px; right: 12px; left: 12px; }
            .toast { min-width: unset; max-width: 100%; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
    <div class="head">
        <h2>HR &amp; Security Dashboard</h2>
        <p class="sub">Manage system accounts, database backups, monitor activity, and track live sessions.</p>
    </div>
    <!-- Dashboard overview -->
    <section class="stats-grid" aria-label="System account overview">
        <article class="stat-card accounts"><span class="stat-label">Available Accounts</span><strong class="stat-value" id="statAvailable">—</strong><span class="stat-note">All registered accounts</span></article>
        <article class="stat-card active"><span class="stat-label">Active Accounts</span><strong class="stat-value" id="statActive">—</strong><span class="stat-note">Enabled user accounts</span></article>
        <article class="stat-card online"><span class="stat-label">Online Accounts</span><strong class="stat-value" id="statOnline">0</strong><span class="stat-note">Currently active sessions</span></article>
        <article class="stat-card failed"><span class="stat-label">Failed Login</span><strong class="stat-value" id="statFailed"><?= (int)$failedLogins ?></strong><span class="stat-note">Within latest 50 activity records</span></article>
        <article class="stat-card logins"><span class="stat-label">Total Login</span><strong class="stat-value" id="statLogins"><?= (int)$totalLogins ?></strong><span class="stat-note">Successful logins in latest 50 records</span></article>
    </section>
    <nav class="dashboard-nav" aria-label="HR dashboard sections" role="tablist">
        <button type="button" class="active" data-panel="users" role="tab" aria-controls="panel-users">User Management</button>
        <button type="button" data-panel="backup" role="tab" aria-controls="panel-backup">Database Backup</button>
        <button type="button" data-panel="sessions" role="tab" aria-controls="panel-sessions">Active Sessions</button>
        <button type="button" data-panel="settings" role="tab" aria-controls="panel-settings">Settings</button>
        <button type="button" data-panel="activity" role="tab" aria-controls="panel-activity">Recent Activities</button>
    </nav>
    <!-- 1. MANAGE USERS -->
    <section class="card dashboard-panel" id="panel-users" data-panel-section="users">
        <div class="card-head">
            <div>
                <h2>User Management</h2>
                <p>Create accounts, approve requests, disable, or delete users.</p>
            </div>
        </div>
        <!-- Create Form -->
        <div class="grid" style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px dashed var(--border);">
            <div class="grid-full"><label for="fName">Full Name</label><input id="fName" autocomplete="off"></div>
            <div><label for="fUser">Username</label><input id="fUser" autocomplete="off"></div>
            <div><label for="fRole">Role</label>
                <select id="fRole">
                    <option value="cashier">Cashier</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin (CEO)</option>
                    <option value="hr">HR</option>
                </select>
            </div>
            <div><label for="fPass">Password (min 8)</label><input id="fPass" type="password" autocomplete="new-password"></div>
            <div><label for="fPass2">Confirm Password</label><input id="fPass2" type="password" autocomplete="new-password"></div>
            <div class="grid-full"><button class="btn primary" id="save">Create Account</button></div>
        </div>
        <div class="hint" id="hint" style="margin-top:-10px; margin-bottom:10px;"></div>
        <!-- User List -->
        <div class="tablewrap"><table>
            <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="tbody">
                <tr><td colspan="4" style="text-align:center; color:var(--text-mute); padding:30px;">Loading users...</td></tr>
            </tbody>
        </table></div>
    </section>
    <!-- 2. DATABASE BACKUP (HR ONLY) -->
    <section class="card dashboard-panel" id="panel-backup" data-panel-section="backup">
        <div class="card-head">
            <div>
                <h2>Database Backup</h2>
                <p>Download a secure SQL export of the entire system database.</p>
            </div>
        </div>
        <?php if ($backupError): ?><div class="alert err"><?= e($backupError) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="do" value="backup_database">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <p style="color:var(--text-dim); font-size:0.92rem; margin-bottom:16px; line-height:1.5;">
                Clicking the button below will generate and download an <code>.sql</code> file containing all users, sales, inventory, and system logs. Store this backup securely.
            </p>
            <button type="submit" class="btn primary">Download Database Backup (.sql)</button>
        </form>
    </section>
    <!-- 3. ACTIVE SESSIONS (WHO IS ONLINE) -->
    <section class="card dashboard-panel" id="panel-sessions" data-panel-section="sessions">
        <div class="card-head">
            <div>
                <h2>Active Sessions (Who is Online)</h2>
                <p>Live view of users currently logged into the system.</p>
            </div>
            <button class="btn primary sm" id="refreshSessionsBtn">Refresh</button>
        </div>
        <!-- Desktop table -->
        <div class="tablewrap sessions-tablewrap">
            <table class="sessions-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>IP Address &amp; Location</th>
                        <th>Device / Browser</th>
                        <th>Login Time</th>
                        <th>Last Activity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="sessionsBody">
                    <tr><td colspan="6" style="text-align:center; color:var(--text-mute); padding:20px;">Loading sessions...</td></tr>
                </tbody>
            </table>
        </div>
        <!-- Mobile card stack -->
        <div class="session-cards" id="sessionCards">
            <p style="color:var(--text-mute); text-align:center; padding:16px 0;">Loading sessions...</p>
        </div>
    </section>
    <!-- 4. CHANGE PASSWORD -->
    <section class="card dashboard-panel" id="panel-settings" data-panel-section="settings">
        <div class="card-head">
            <div>
                <h2>Change My Password</h2>
                <p>Update the password for your own HR account.</p>
            </div>
        </div>
        <?php if ($passSuccess): ?><div class="alert ok"><?= e($passSuccess) ?></div><?php endif; ?>
        <?php if ($passError): ?><div class="alert err"><?= e($passError) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="do" value="change_my_password">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <div class="grid">
                <div class="grid-full"><label>Current Password</label><input type="password" name="current_password" required autocomplete="current-password"></div>
                <div><label>New Password (min 8)</label><input type="password" name="new_password" required autocomplete="new-password"></div>
                <div><label>Confirm New Password</label><input type="password" name="confirm_password" required autocomplete="new-password"></div>
                <div class="grid-full"><button class="btn primary" type="submit" id="btnChangePass">Update Password</button></div>
            </div>
        </form>
    </section>
    <!-- 5. ACTIVITY LOGS (MODERN TIMELINE) -->
    <section class="card dashboard-panel" id="panel-activity" data-panel-section="activity">
        <div class="card-head">
            <div>
                <h2>Recent Activity</h2>
                <p>Live feed of logins, sales, and system actions.</p>
            </div>
            <form id="clearLogsForm" method="post">
                <input type="hidden" name="do" value="clear_logs">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <button type="button" class="btn danger sm" id="clearLogsBtn">Clear Logs</button>
            </form>
        </div>
        <?php if ($logSuccess): ?><div class="alert ok" style="margin-bottom:20px;"><?= e($logSuccess) ?></div><?php endif; ?>
        <!-- Failed login reasons (Always shows the latest 10, regardless of the 50-record timeline) -->
        <div style="margin-bottom:24px;">
            <h3 style="margin:0 0 4px; font-size:1rem; color:#f87171;">Failed Login Details</h3>
            <p style="margin:0 0 12px; color:var(--text-dim); font-size:.85rem;">Latest failed attempts with reasons and locations.</p>
            <?php if (empty($failedLoginsList)): ?>
                <p class="failed-empty">No failed logins recorded recently.</p>
            <?php else: ?>
                <ul class="failed-list">
                    <?php foreach ($failedLoginsList as $fl): 
                        // Extract location from details if appended, otherwise resolve from IP
                        $reason = $fl['details'] ?: 'Unknown Reason';
                        $location = 'Unknown Location';
                        
                        if (strpos($reason, ' — attempted from ') !== false) {
                            list($reasonText, $locationText) = explode(' — attempted from ', $reason, 2);
                            $reason = $reasonText;
                            $location = $locationText;
                        } else {
                            $location = resolve_ip_location($fl['ip_address']);
                        }
                    ?>
                        <li class="failed-row">
                            <span class="f-icon" aria-hidden="true">!</span>
                            <div class="f-body">
                                <div class="f-user">@<?= htmlspecialchars($fl['username'] ?: 'unknown') ?></div>
                                
                                <div class="f-reason-label">Reason</div>
                                <div class="f-reason"><?= htmlspecialchars($reason) ?></div>
                                
                                <div class="f-location-label">Location</div>
                                <div class="f-location"><?= htmlspecialchars($location) ?></div>
                                
                                <div class="f-meta">
                                    <span>🕒 <?= date('M j, g:i A', strtotime($fl['created_at'])) ?></span>
                                    <span>🌐 IP: <?= htmlspecialchars($fl['ip_address'] ?: 'N/A') ?></span>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="timeline">
            <?php if (empty($logs)): ?>
                <p style="color: var(--text-mute); text-align: center; padding: 20px 0;">No activity recorded yet.</p>
            <?php else: ?>
                <?php foreach ($logs as $log):
                    $actionClass = 'default';
                    if (strpos($log['action'], 'login') !== false) $actionClass = 'login';
                    if (strpos($log['action'], 'logout') !== false) $actionClass = 'logout';
                    if (strpos($log['action'], 'reset') !== false || strpos($log['action'], 'delete') !== false || strpos($log['action'], 'rejected') !== false) $actionClass = 'reset';
                    if (strpos($log['action'], 'create') !== false || strpos($log['action'], 'register') !== false || strpos($log['action'], 'approved') !== false) $actionClass = 'create';
                    if (strpos($log['action'], 'update') !== false || strpos($log['action'], 'enable') !== false || strpos($log['action'], 'backup') !== false) $actionClass = 'update';
                ?>
                <div class="timeline-item">
                    <div class="timeline-dot <?= $actionClass ?>"></div>
                    <div class="timeline-content">
                        <div class="timeline-header">
                            <div class="timeline-user">
                                <?= htmlspecialchars($log['username'] ?: 'System / Guest') ?>
                                <small>@<?= htmlspecialchars($log['username'] ?: 'system') ?></small>
                            </div>
                            <div class="timeline-meta">
                                <span>🕒 <?= date('M j, g:i A', strtotime($log['created_at'])) ?></span>
                                <span>🌐 <?= htmlspecialchars($log['ip_address'] ?: 'N/A') ?></span>
                            </div>
                        </div>
                        <div class="timeline-body">
                            <span class="action-tag <?= $actionClass ?>"><?= htmlspecialchars(str_replace('_', ' ', $log['action'])) ?></span>
                            <span style="margin-left: 10px;"><?= htmlspecialchars($log['details']) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>
<div id="toast-container"></div>
<script>
(function initDashboardNavigation() {
    const initialPanel = <?= json_encode($activePanel) ?>;
    const navButtons = document.querySelectorAll('.dashboard-nav [data-panel]');
    const panels = document.querySelectorAll('[data-panel-section]');
    function showPanel(panelName) {
        panels.forEach(panel => { panel.hidden = panel.dataset.panelSection !== panelName; });
        navButtons.forEach(button => {
            const selected = button.dataset.panel === panelName;
            button.classList.toggle('active', selected);
            button.setAttribute('aria-selected', selected ? 'true' : 'false');
            button.tabIndex = selected ? 0 : -1;
        });
    }
    navButtons.forEach(button => button.addEventListener('click', () => showPanel(button.dataset.panel)));
    showPanel(initialPanel);
})();
const CSRF = <?= json_encode(csrf_token()) ?>, ME = <?= (int)current_user()['user_id'] ?>;
const $ = id => document.getElementById(id);
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const hint = (t, k='') => { $('hint').textContent = t; $('hint').className = 'hint ' + k; };
async function post(action, body) {
    return (await fetch('api/users.php?action=' + action, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF':CSRF}, body:JSON.stringify(body)})).json();
}
function dateFmt(str) {
    if (!str) return 'N/A';
    const d = new Date(str.replace(' ', 'T'));
    return d.toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
}
function parseDevice(ua) {
    if (!ua) return "Unknown Device";
    let device = "Unknown Device";
    if (ua.includes("Windows")) device = "🖥️ Windows PC";
    else if (ua.includes("Macintosh")) device = "💻 Mac";
    else if (ua.includes("Android")) device = "📱 Android Phone";
    else if (ua.includes("iPhone")) device = "📱 iPhone";
    else if (ua.includes("iPad")) device = "📱 iPad";
    if (ua.includes("Chrome")) device += " (Chrome)";
    else if (ua.includes("Safari") && !ua.includes("Chrome")) device += " (Safari)";
    else if (ua.includes("Firefox")) device += " (Firefox)";
    return device;
}
async function loadUsers() {
    try {
        const res = await fetch('api/users.php?action=list');
        if (!res.ok) throw new Error("HTTP error " + res.status);
        const rows = await res.json();
        if (rows.error) {
            $('tbody').innerHTML = `<tr><td colspan="4" style="color:#f87171; text-align:center;">${rows.error}</td></tr>`;
            return;
        }
        // Summary counts use the same account data shown in this table.
        $('statAvailable').textContent = rows.length;
        $('statActive').textContent = rows.filter(u => Number(u.is_active) === 1).length;
        $('tbody').innerHTML = rows.map(u => {
            let pendingBox = '';
            if (u.pending_password_hash) {
                pendingBox = `
                <div class="pending-alert">
                    <p>⚠️ Password Request</p>
                    <div style="display:flex; gap:6px;">
                        <button class="btn sm primary" data-approve="${u.user_id}" style="flex:1; padding:4px;">Approve</button>
                        <button class="btn sm danger" data-reject="${u.user_id}" style="flex:1; padding:4px;">Reject</button>
                    </div>
                </div>`;
            }
            let normalActions = '';
            if (u.user_id == ME) {
                normalActions = '<span style="font-size:.8rem; color:var(--text-dim); font-weight:600;">Your Account</span>';
            } else {
                normalActions = `
                <div class="action-group">
                    <button class="btn sm ${u.is_active == 1 ? 'ghost' : 'primary'}" data-id="${u.user_id}">${u.is_active == 1 ? 'Disable' : 'Enable'}</button>
                    <button class="btn sm danger" data-del="${u.user_id}" data-name="${esc(u.username)}">Delete</button>
                </div>`;
            }
            return `
            <tr>
                <td>
                    <b>${esc(u.full_name)}</b><br>
                    <span style="font-size:.78rem; color:var(--text-dim);">@${esc(u.username)}</span>
                </td>
                <td><span class="badge ${u.role}">${u.role}</span></td>
                <td><span class="badge ${u.is_active == 1 ? 'on' : 'off'}">${u.is_active == 1 ? 'Active' : 'Disabled'}</span></td>
                <td>
                    <div class="actions-cell">
                        ${pendingBox}
                        ${normalActions}
                    </div>
                </td>
            </tr>
        `}).join('');
    } catch (err) {
        $('tbody').innerHTML = `<tr><td colspan="4" style="color:#f87171; text-align:center;">Failed to load users: ${err.message}</td></tr>`;
    }
}
async function loadSessions() {
    $('sessionsBody').innerHTML = `<tr><td colspan="6" style="text-align:center; color:var(--text-mute); padding:20px;">Fetching live locations... (may take 1-2s)</td></tr>`;
    $('sessionCards').innerHTML = `<p style="color:var(--text-mute); text-align:center; padding:16px 0;">Fetching live locations...</p>`;
    try {
        const res = await fetch('api/users.php?action=active_sessions');
        const rows = await res.json();
        if (rows.error) {
            $('sessionsBody').innerHTML = `<tr><td colspan="6" style="color:#f87171; text-align:center;">${rows.error}</td></tr>`;
            $('sessionCards').innerHTML = `<p style="color:#f87171; text-align:center;">${rows.error}</p>`;
            return;
        }
        $('statOnline').textContent = rows.length;
        $('sessionsBody').innerHTML = rows.map(s => {
            const device = parseDevice(s.user_agent);
            return `
                <tr>
                    <td>
                        <div style="display:flex; align-items:center;">
                            <span class="online-dot"></span>
                            <b>${esc(s.username)}</b>
                        </div>
                    </td>
                    <td>
                        <code style="background:var(--surface-3); color:var(--text); padding:2px 6px; border-radius:4px; font-size:0.8rem;">${esc(s.ip_address || 'Unknown')}</code><br>
                        <small style="color:var(--text-dim);">📍 ${esc(s.location)}</small>
                    </td>
                    <td style="font-size:.85rem;">${esc(device)}</td>
                    <td>${dateFmt(s.login_time)}</td>
                    <td>${dateFmt(s.last_activity)}</td>
                    <td><button class="btn sm danger" data-kick="${s.session_id}" data-name="${esc(s.username)}">Force Logout</button></td>
                </tr>
            `;
        }).join('');
        $('sessionCards').innerHTML = rows.length === 0
            ? `<p style="color:var(--text-mute); text-align:center; padding:16px 0;">No active sessions.</p>`
            : rows.map(s => {
                const device = parseDevice(s.user_agent);
                return `
                <div class="session-card">
                    <div class="session-card-header">
                        <div class="session-card-username">
                            <span class="online-dot"></span>
                            <b>${esc(s.username)}</b>
                        </div>
                        <button class="btn sm danger" data-kick="${s.session_id}" data-name="${esc(s.username)}">Force Logout</button>
                    </div>
                    <div class="session-card-rows">
                        <span class="session-card-label">IP</span>
                        <span class="session-card-value"><code style="background:var(--surface-3); color:var(--text); padding:2px 5px; border-radius:4px; font-size:.8rem;">${esc(s.ip_address || 'Unknown')}</code></span>
                        <span class="session-card-label">Location</span>
                        <span class="session-card-value">📍 ${esc(s.location)}</span>
                        <span class="session-card-label">Device</span>
                        <span class="session-card-value">${esc(device)}</span>
                        <span class="session-card-label">Login</span>
                        <span class="session-card-value">${dateFmt(s.login_time)}</span>
                        <span class="session-card-label">Last Active</span>
                        <span class="session-card-value">${dateFmt(s.last_activity)}</span>
                    </div>
                </div>
            `}).join('');
    } catch (err) {
        $('sessionsBody').innerHTML = `<tr><td colspan="6" style="color:#f87171; text-align:center;">Failed to load sessions.</td></tr>`;
        $('sessionCards').innerHTML = `<p style="color:#f87171; text-align:center;">Failed to load sessions.</p>`;
    }
}
$('tbody').addEventListener('click', async e => {
    const d = e.target.dataset;
    if (d.approve) {
        const r = await post('approve_reset', {user_id: d.approve});
        if (r.error) showToast(r.error, 'error');
        else showToast(r.message);
        loadUsers();
    } else if (d.reject) {
        const r = await post('reject_reset', {user_id: d.reject});
        if (r.error) showToast(r.error, 'error');
        else showToast(r.message);
        loadUsers();
    } else if (d.del) {
        showConfirm({
            type: 'danger',
            title: 'Delete Account',
            message: `This will permanently delete <strong>${d.name}</strong>'s account. This cannot be undone.`,
            confirmText: 'Yes, Delete',
            onConfirm: async () => {
                const r = await post('delete', {user_id: d.del});
                if (r.error) showToast(r.error, 'error');
                else showToast('Account deleted successfully.');
                loadUsers();
            }
        });
    } else if (d.id) {
        const r = await post('toggle', {user_id: d.id});
        if (r.error) showToast(r.error, 'error');
        else showToast('Account status updated.');
        loadUsers();
    }
});
function handleSessionClick(e) {
    const d = e.target.dataset;
    if (d.kick) {
        showConfirm({
            type: 'warning',
            title: 'Force Logout',
            message: `<strong>${d.name}</strong> will be immediately signed out of the system.`,
            confirmText: 'Yes, Log Out',
            onConfirm: () => {
                post('force_logout', {session_id: d.kick}).then(r => {
                    if (r.error) showToast(r.error, 'error');
                    else {
                        showToast(`${d.name} has been logged out.`);
                        loadSessions();
                    }
                });
            }
        });
    }
}
$('sessionsBody').addEventListener('click', handleSessionClick);
$('sessionCards').addEventListener('click', handleSessionClick);
$('save').onclick = async () => {
    if ($('fPass').value !== $('fPass2').value) { hint('Passwords do not match.', 'bad'); return; }
    if ($('fPass').value.length < 8) { hint('Password must be at least 8 characters.', 'bad'); return; }
    const r = await post('create', {full_name: $('fName').value, username: $('fUser').value, password: $('fPass').value, role: $('fRole').value});
    if (r.error) { hint(r.error, 'bad'); return; }
    hint('', '');
    showToast('Account created successfully!');
    ['fName','fUser','fPass','fPass2'].forEach(id => $(id).value = '');
    loadUsers();
};
$('refreshSessionsBtn').onclick = loadSessions;
function showConfirm({ type = 'danger', title, message, confirmText = 'Confirm', onConfirm }) {
    const icons = {
        danger: `<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>`,
        warning: `<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`
    };
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    backdrop.innerHTML = `
        <div class="modal" role="dialog" aria-modal="true">
            <div class="modal-icon ${type}">${icons[type] || icons.danger}</div>
            <p class="modal-title">${title}</p>
            <p class="modal-body">${message}</p>
            <div class="modal-actions">
                <button class="btn ghost" id="modalCancel">Cancel</button>
                <button class="btn ${type === 'warning' ? 'primary' : 'danger'}" id="modalConfirm">${confirmText}</button>
            </div>
        </div>
    `;
    document.body.appendChild(backdrop);
    const close = () => {
        backdrop.classList.add('hiding');
        backdrop.addEventListener('animationend', () => backdrop.remove(), { once: true });
    };
    backdrop.querySelector('#modalCancel').addEventListener('click', close);
    backdrop.querySelector('#modalConfirm').addEventListener('click', () => { close(); onConfirm(); });
    backdrop.addEventListener('click', e => { if (e.target === backdrop) close(); });
    document.addEventListener('keydown', function esc(e) { if (e.key === 'Escape') { close(); document.removeEventListener('keydown', esc); } });
}
function showToast(msg, type = 'success') {
    const container = document.getElementById('toast-container');
    const successIcon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none">
        <circle cx="12" cy="12" r="10" stroke="rgba(255,255,255,0.4)" stroke-width="2" fill="none"/>
        <polyline points="6,12 10,16 18,8" class="tick-circle" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>`;
    const errorIcon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none">
        <circle cx="12" cy="12" r="10" stroke="rgba(255,255,255,0.4)" stroke-width="2" fill="none"/>
        <line x1="8" y1="8" x2="16" y2="16" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/>
        <line x1="16" y1="8" x2="8" y2="16" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/>
    </svg>`;
    const toast = document.createElement('div');
    toast.className = `toast ${type === 'error' ? 'error' : 'success'}`;
    toast.innerHTML = `
        <div class="toast-icon">${type === 'error' ? errorIcon : successIcon}</div>
        <span class="toast-msg">${msg}</span>
        <button class="toast-close" aria-label="Dismiss">&times;</button>
    `;
    container.appendChild(toast);
    const dismiss = () => {
        toast.classList.add('hiding');
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
    };
    toast.querySelector('.toast-close').addEventListener('click', dismiss);
    setTimeout(dismiss, 4000);
}
document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
    loadSessions();
    document.getElementById('clearLogsBtn').addEventListener('click', () => {
        showConfirm({
            type: 'danger',
            title: 'Clear Activity Logs',
            message: 'This will permanently delete <strong>all activity logs</strong>. This cannot be undone.',
            confirmText: 'Yes, Clear All',
            onConfirm: () => document.getElementById('clearLogsForm').submit()
        });
    });
});
</script>
</body>
</html>