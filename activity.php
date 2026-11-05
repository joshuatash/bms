<?php 
require __DIR__ . '/config.php'; 
require_login();
if (!in_array(current_user()['role'], ['admin', 'manager'], true)) { header('Location: pos.php'); exit; }
$active = 'activity'; 
$pdo = db(); 

$isAdmin = current_user()['role'] === 'admin';   // only admin may reset (clear) the history
$resetError = '';
$flash = $_SESSION['flash_activity'] ?? '';
unset($_SESSION['flash_activity']);

/* ---------- RESET (admin only) ---------- */
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'reset') {
    $scope    = (string)($_POST['scope'] ?? '');
    $range    = (string)($_POST['range'] ?? '');
    $backView = ($_POST['view'] ?? '') === 'activity' ? 'activity' : 'sessions';

    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        $resetError = 'Your session expired. Reload the page and try again.';
    } elseif (!in_array($scope, ['activity', 'sessions', 'both'], true) || !in_array($range, ['all', '30', '90'], true)) {
        $resetError = 'Choose what to clear.';
    } else {
        $currentUserId = current_user()['user_id'] ?? current_user()['id'] ?? 0;

        $ps = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $ps->execute([$currentUserId]);
        $hash = (string)$ps->fetchColumn();

        if (!$hash || !password_verify((string)($_POST['password'] ?? ''), $hash)) {
            $resetError = 'Wrong password. Nothing was deleted.';
            log_activity('reset_denied', 'Wrong password when trying to reset the log');
        } else {
            try {
                $days = $range === 'all' ? 0 : (int)$range;
                $nAct = 0; $nSes = 0;
                $pdo->beginTransaction();
                if ($scope !== 'sessions') {
                    $nAct = (int)$pdo->exec("DELETE FROM activity_log" . ($days ? " WHERE created_at < NOW() - INTERVAL $days DAY" : ''));
                }
                if ($scope !== 'activity') {
                    $nSes = (int)$pdo->exec("DELETE FROM login_sessions WHERE NOT (logout_time IS NULL AND last_activity >= NOW() - INTERVAL 15 MINUTE)"
                                            . ($days ? " AND login_time < NOW() - INTERVAL $days DAY" : ''));
                }
                $pdo->commit();
                log_activity('activity_reset', "Cleared $nAct activity record(s) and $nSes login session(s)");
                $_SESSION['flash_activity'] = "Done. Removed $nAct activity record(s) and $nSes login session(s).";
                header('Location: activity.php?view=' . $backView);
                exit;
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $resetError = 'Could not clear the log. Nothing was deleted.';
            }
        }
    }
}

/* action => [label, colour group] */
$labels = [
    'login' => ['Login', 'auth'],
    'logout' => ['Logout', 'auth'],
    'login_failed' => ['Failed login', 'bad'],
    'login_blocked' => ['Blocked login', 'bad'],
    'register' => ['Registration', 'acct'],
    'product_add' => ['Product added', 'stock'],
    'product_restock' => ['Restock', 'stock'],
    'product_restore' => ['Product restored', 'stock'],
    'product_update' => ['Product edited', 'stock'],
    'product_delete' => ['Product deleted', 'bad'],
    'sale' => ['Sale', 'sale'],
    'room_add' => ['Room added', 'room'],
    'room_update' => ['Room edited', 'room'],
    'room_delete' => ['Room deleted', 'bad'],
    'room_status' => ['Room status', 'room'],
    'room_book' => ['Room booked', 'room'],
    'room_payment' => ['Room payment', 'room'],
    'room_checkout' => ['Guest checked out', 'room'],
    'room_ready' => ['Room ready', 'room'],
    'account_create' => ['Account created', 'acct'],
    'account_enable' => ['Account enabled', 'acct'],
    'account_disable' => ['Account disabled', 'acct'],
    'account_delete' => ['Account deleted', 'bad'],
    'export_products' => ['Products report', 'info'],
    'view_sales_report' => ['Sales report', 'info'],
    'expense_add' => ['Expense recorded', 'stock'],
    'activity_reset' => ['Log reset', 'bad'],
    'reset_denied' => ['Reset refused', 'bad']
];

function device(string $ua): string {
    $os = preg_match('/Android/i', $ua) ? 'Android' : (preg_match('/iPhone|iPad/i', $ua) ? 'iOS' : 
          (preg_match('/Windows/i', $ua) ? 'Windows' : (preg_match('/Mac OS/i', $ua) ? 'Mac' : 
          (preg_match('/Linux/i', $ua) ? 'Linux' : 'Unknown'))));
    $br = preg_match('/Edg\//i', $ua) ? 'Edge' : (preg_match('/OPR|Opera/i', $ua) ? 'Opera' : 
          (preg_match('/Firefox/i', $ua) ? 'Firefox' : (preg_match('/Chrome/i', $ua) ? 'Chrome' : 
          (preg_match('/Safari/i', $ua) ? 'Safari' : 'Browser'))));
    return "$br on $os";
}

function dur(int $m): string { 
    return $m < 1 ? 'under 1 min' : ($m < 60 ? "{$m}m" : intdiv($m, 60) . 'h ' . ($m % 60) . 'm'); 
}

function when(?string $t): string { 
    return $t ? '<span class="local-time" data-time="'.htmlspecialchars($t).'">'.date('d M Y, h:i A', strtotime($t)).'</span>' : '—'; 
}

// Helper: Live lookup if the database has a placeholder location
function resolve_live_location($ip, $storedLocation) {
    if (!$ip) return 'Unknown';
    if ($ip === '::1' || $ip === '127.0.0.1') return 'Local Network';
    
    // If it's already a good location, return it
    if ($storedLocation && !in_array(strtolower($storedLocation), ['unknown', 'local network', 'kampala central', ''])) {
        return $storedLocation;
    }
    
    // Otherwise, do a live lookup
    $ctx = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
    $json = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city", false, $ctx);
    if ($json) {
        $d = json_decode($json, true);
        if ($d && $d['status'] === 'success') {
            $parts = array_filter([$d['city'], $d['regionName'], $d['country']]);
            if ($parts) return implode(', ', $parts);
        }
    }
    return $storedLocation ?: 'Unknown Location';
}

$view = ($_GET['view'] ?? 'sessions') === 'activity' ? 'activity' : 'sessions';
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 50; $offset = ($page - 1) * $per;

$uid = (int)($_GET['user'] ?? 0);
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : '';
$to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : '';
$actF = isset($labels[$_GET['action'] ?? '']) ? $_GET['action'] : '';
$qtext = trim($_GET['q'] ?? '');

$setupError = false; $rows = []; $total = 0; $users = [];
$stats = ['online' => 0, 'logins_today' => 0, 'failed_today' => 0];

try {
    $users = $pdo->query("SELECT user_id, username FROM users ORDER BY username")->fetchAll();
    $stats['online'] = (int)$pdo->query("SELECT COUNT(*) FROM login_sessions WHERE logout_time IS NULL AND last_activity >= NOW() - INTERVAL 15 MINUTE")->fetchColumn();
    $stats['logins_today'] = (int)$pdo->query("SELECT COUNT(*) FROM login_sessions WHERE login_time >= CURDATE()")->fetchColumn();
    $stats['failed_today'] = (int)$pdo->query("SELECT COUNT(*) FROM activity_log WHERE action = 'login_failed' AND created_at >= CURDATE()")->fetchColumn();

    $w = []; $pr = [];
    $t = $view === 'sessions' ? 'ls' : 'a';
    $tc = $view === 'sessions' ? 'ls.login_time' : 'a.created_at';
    
    if ($uid) { $w[] = "{$t}.user_id = :u"; $pr[':u'] = $uid; }
    if ($from) { $w[] = "{$tc} >= :f"; $pr[':f'] = "$from 00:00:00"; }
    if ($to) { $w[] = "{$tc} < :t"; $pr[':t'] = date('Y-m-d', strtotime("$to +1 day")) . ' 00:00:00'; }
    
    if ($view === 'activity') {
        if ($actF) { $w[] = "a.action = :a"; $pr[':a'] = $actF; }
        if ($qtext !== '') { $w[] = "(a.details LIKE :q1 OR a.username LIKE :q2)"; $pr[':q1'] = "%$qtext%"; $pr[':q2'] = "%$qtext%"; }
    }
    
    $wsql = $w ? 'WHERE ' . implode(' AND ', $w) : '';

    if ($view === 'sessions') {
        $c = $pdo->prepare("SELECT COUNT(*) FROM login_sessions ls $wsql"); $c->execute($pr); $total = (int)$c->fetchColumn();
        $q = $pdo->prepare("SELECT ls.*, u.full_name,
            TIMESTAMPDIFF(MINUTE, ls.last_activity, NOW()) AS idle_min,
            TIMESTAMPDIFF(MINUTE, ls.login_time, COALESCE(ls.logout_time, ls.last_activity)) AS dur_min
            FROM login_sessions ls LEFT JOIN users u ON u.user_id = ls.user_id
            $wsql ORDER BY ls.login_time DESC LIMIT $per OFFSET $offset");
    } else {
        $c = $pdo->prepare("SELECT COUNT(*) FROM activity_log a $wsql"); $c->execute($pr); $total = (int)$c->fetchColumn();
        $q = $pdo->prepare("SELECT a.*, u.full_name FROM activity_log a LEFT JOIN users u ON u.user_id = a.user_id
            $wsql ORDER BY a.created_at DESC, a.log_id DESC LIMIT $per OFFSET $offset");
    }
    $q->execute($pr);
    $rows = $q->fetchAll();
} catch (PDOException $ex) {
    $setupError = true;
}

$pages = max(1, (int)ceil($total / $per));
$pageUrl = fn(array $over) => 'activity.php?' . http_build_query(array_merge($_GET, $over));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Activity</title>
<style>
:root{
    --navy:#0f172a;--slate:#334155;--muted:#64748b;--line:#e2e8f0;--amber:#f59e0b;--green:#16a34a;--red:#dc2626;
    /* Dark theme (matches POS) */
    --surface:#111c2e;
    --surface-2:#16243a;
    --surface-3:#1e2d47;
    --text:#e2e8f0;
    --text-dim:#94a3b8;
    --text-mute:#64748b;
    --border:#243349;
}
*{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',system-ui,sans-serif;background:#0b1220;color:var(--text)}
main{max-width:1200px;margin:24px auto;padding:0 18px;display:grid;gap:18px}
.head{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between}
.head h2{margin:0;font-size:1.4rem;color:var(--text)}
.head p{margin:2px 0 0;color:var(--text-dim)}
.acts{display:flex;gap:8px;flex-wrap:wrap}
.btn{display:inline-block;border:0;border-radius:8px;padding:10px 16px;font-weight:700;cursor:pointer;font-size:.9rem;text-decoration:none;font-family:inherit;transition:.15s}
.btn.primary{background:var(--amber);color:#0b1220;box-shadow:0 4px 6px -1px rgba(245,158,11,.3)}
.btn.primary:hover{background:#d97706;color:#fff}
.btn.ghost{background:var(--surface-2);color:var(--text-dim)}
.btn.ghost:hover{background:var(--surface-3);color:var(--text)}
.btn.danger{background:#dc2626;color:#fff}
.btn.danger:hover{background:#b91c1c}
.ok{background:rgba(22,163,74,.15);color:#4ade80;padding:14px 16px;border-radius:10px;font-weight:600;border:1px solid rgba(22,163,74,.3)}
.tabs{display:flex;gap:8px;flex-wrap:wrap}
.tab{padding:10px 18px;border-radius:8px;background:var(--surface);border:1px solid var(--border);color:var(--text-dim);text-decoration:none;font-weight:700;font-size:.9rem;transition:.15s}
.tab:hover{background:var(--surface-2);color:var(--text)}
.tab.on{background:var(--amber);color:#0b1220;border-color:var(--amber)}
.tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px}
.tile{background:var(--surface);border-radius:12px;padding:16px 18px;border-left:5px solid var(--text-dim);border-top:1px solid var(--border);border-right:1px solid var(--border);border-bottom:1px solid var(--border);box-shadow:0 4px 6px -1px rgba(0,0,0,.3)}
.tile.on{border-left-color:var(--green)}
.tile.bad{border-left-color:var(--red)}
.tile small{color:var(--text-dim)}
.tile b{display:block;font-size:1.6rem;color:var(--text)}
.card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:0 4px 6px -1px rgba(0,0,0,.3)}
.filters{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;align-items:end}
label{display:block;font-size:.72rem;font-weight:700;color:var(--text-dim);margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px}
input,select{width:100%;padding:9px 10px;border:1px solid var(--border);border-radius:8px;font-size:.92rem;background:var(--surface-2);color:var(--text)}
input::placeholder{color:var(--text-mute)}
input:focus,select:focus{outline:2px solid var(--amber);border-color:var(--amber);background:var(--surface-3)}
.tablewrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:.88rem;color:var(--text)}
th{text-align:left;background:var(--surface-2);color:var(--text-dim);padding:10px 12px;font-size:.7rem;text-transform:uppercase;letter-spacing:.4px;border-bottom:2px solid var(--border);white-space:nowrap}
td{padding:10px 12px;border-bottom:1px solid var(--border);vertical-align:top}
.badge{padding:3px 10px;border-radius:999px;font-size:.72rem;font-weight:700;white-space:nowrap}
.b-auth{background:rgba(37,99,235,.2);color:#60a5fa}
.b-bad{background:rgba(220,38,38,.2);color:#f87171}
.b-acct{background:rgba(139,92,246,.2);color:#a78bfa}
.b-stock{background:rgba(245,158,11,.2);color:#fbbf24}
.b-sale{background:rgba(22,163,74,.2);color:#4ade80}
.b-info{background:var(--surface-2);color:var(--text-dim)}
.b-room{background:rgba(6,182,212,.2);color:#22d3ee}
.s-active{background:rgba(22,163,74,.2);color:#4ade80}
.s-out{background:var(--surface-2);color:var(--text-dim)}
.s-lost{background:rgba(234,88,12,.2);color:#fb923c}
.muted{color:var(--text-dim);font-size:.8rem}
.empty{text-align:center;color:var(--text-mute);padding:34px}
.pager{display:flex;justify-content:space-between;align-items:center;margin-top:14px;font-size:.8rem;color:var(--text-dim)}
.printonly{display:none}
.warn{background:rgba(245,158,11,.15);color:#fbbf24;padding:16px;border-radius:10px;line-height:1.6;border:1px solid rgba(245,158,11,.3)}
.warn b{color:#fcd34d}

/* modal styling */
.modal{position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);display:none;place-items:center;z-index:60;padding:12px}
.modal.open{display:grid}
.modal .box{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:24px;width:min(440px,100%);max-height:94vh;overflow:auto;color:var(--text);box-shadow:0 25px 50px -12px rgba(0,0,0,.6)}
.modal h3{margin:0 0 8px;color:#f87171}
.modal .warnbox{background:rgba(220,38,38,.15);color:#f87171;border:1px solid rgba(220,38,38,.3);border-radius:8px;padding:10px 12px;font-size:.85rem;line-height:1.5;margin-bottom:10px}
.modal .r{margin-bottom:12px}
.modal .err{color:#f87171;font-weight:600;font-size:.88rem;min-height:1.2em}
.modal .actions{display:flex;gap:10px;justify-content:flex-end;margin-top:14px}

@media(max-width: 640px) {
    main { padding: 0 12px; margin: 14px auto; gap: 14px; }
    .head { flex-direction: column; align-items: stretch; text-align: center; }
    .acts { width: 100%; } .acts .btn { flex: 1; padding: 14px; font-size: 1rem; }
    .filters { grid-template-columns: 1fr; }
    .filters .btn { width: 100%; padding: 12px; }
    .tiles { grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .tile { padding: 12px; text-align: center; }
    .tile b { font-size: 1.3rem; }
    .tile small { font-size: 0.65rem; line-height: 1.1; display: block; margin-bottom: 4px; }
    .rt thead { display: none; }
    .rt, .rt tbody, .rt tr, .rt td { display: block; width: 100%; }
    .rt tr { border: 1px solid var(--border); border-radius: 10px; margin-bottom: 12px; padding: 8px 12px; background: var(--surface-2); }
    .rt td { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; border-bottom: 1px solid var(--border); padding: 8px 2px; text-align: right; }
    .rt td:last-child { border-bottom: 0; padding-bottom: 0; }
    .rt td::before { content: attr(data-label); font-weight: 700; font-size: .68rem; text-transform: uppercase; color: var(--text-dim); text-align: left; flex-shrink: 0; }
    .pager { flex-direction: column; gap: 10px; text-align: center; }
}

@media print {
    .sidebar, .mbar, .scrim, .noprint { display: none !important; }
    body { padding: 0 !important; background: #fff; color: #0f172a; } 
    main { margin: 0; padding: 0; max-width: none; }
    .card, .tile { box-shadow: none; border: 1px solid #cbd5e1; background: #fff; }
    .printonly { display: block; }
    th { background: #0f172a !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    td { color: #0f172a; border-bottom: 1px solid #cbd5e1; }
    .muted { color: #64748b; }
    tr { break-inside: avoid; } @page { size: A4 landscape; margin: 10mm; }
}
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
    <div class="head">
        <div><h2>Sessions &amp; Activity</h2><p>Who logged in, when, and where they connected from</p></div>
        <p class="printonly muted">Generated <?= date('d M Y, h:i A') ?></p>
        <div class="acts noprint">
            <button class="btn primary" onclick="window.print()">Export to PDF</button>
            <?php if ($isAdmin): ?><button class="btn danger" id="resetBtn" type="button">Reset</button><?php endif; ?>
        </div>
    </div>

    <?php if ($flash): ?><div class="ok noprint"><?= e($flash) ?></div><?php endif; ?>

    <?php if ($setupError): ?>
        <div class="warn"><b>The activity tables are not set up yet.</b><br>Open phpMyAdmin, click <b>bms_db</b>, go to the <b>SQL</b> tab, paste the contents of <b>module2_audit.sql</b> and click <b>Go</b>. Then refresh this page.</div>
    <?php else: ?>
        <section class="tiles noprint">
            <div class="tile on"><small>Online now</small><b><?= $stats['online'] ?></b></div>
            <div class="tile"><small>Logins today</small><b><?= $stats['logins_today'] ?></b></div>
            <div class="tile bad"><small>Failed logins</small><b><?= $stats['failed_today'] ?></b></div>
        </section>

        <div class="tabs noprint">
            <a class="tab <?= $view === 'sessions' ? 'on' : '' ?>" href="activity.php?view=sessions">Login Sessions</a>
            <a class="tab <?= $view === 'activity' ? 'on' : '' ?>" href="activity.php?view=activity">Activity Log</a>
        </div>

        <form class="card filters noprint" method="get">
            <input type="hidden" name="view" value="<?= e($view) ?>">
            <div>
                <label>User</label>
                <select name="user">
                    <option value="0">All users</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int)$u['user_id'] ?>" <?= $uid === (int)$u['user_id'] ? 'selected' : '' ?>><?= e($u['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($view === 'activity'): ?>
                <div>
                    <label>Action</label>
                    <select name="action">
                        <option value="">All actions</option>
                        <?php foreach ($labels as $k => $l): ?>
                            <option value="<?= e($k) ?>" <?= $actF === $k ? 'selected' : '' ?>><?= e($l[0]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label>Search details</label><input name="q" value="<?= e($qtext) ?>" placeholder="e.g. Pilsner, R000012"></div>
            <?php endif; ?>
            <div><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div>
            <div><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div>
            <div style="display:flex;gap:8px"><button class="btn primary" style="flex:1">Apply</button><a class="btn ghost" href="activity.php?view=<?= e($view) ?>">Reset</a></div>
        </form>

        <section class="card">
            <?php if (!$rows): ?>
                <div class="empty">Nothing recorded for these filters yet.</div>
            <?php elseif ($view === 'sessions'): ?>
                <div class="tablewrap">
                    <table class="rt">
                        <thead><tr><th>User</th><th>Logged in</th><th>Last activity</th><th>Logged out</th><th>Duration</th><th>Device</th><th>Location</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($rows as $r): 
                                if ($r['logout_time']) { $st = ['Logged out', 's-out']; } 
                                elseif ((int)$r['idle_min'] <= 15) { $st = ['Active', 's-active']; } 
                                else { $st = ['Ended without logout', 's-lost']; } 
                                
                                // LIVE LOCATION LOOKUP
                                $liveLoc = resolve_live_location($r['ip_address'], $r['location']);
                            ?>
                                <tr>
                                    <td data-label="User"><b><?= e($r['username']) ?></b><?php if ($r['full_name']): ?><br><span class="muted"><?= e($r['full_name']) ?></span><?php endif; ?></td>
                                    <td data-label="Logged in"><?= when($r['login_time']) ?></td>
                                    <td data-label="Last activity"><?= when($r['last_activity']) ?></td>
                                    <td data-label="Logged out"><?= when($r['logout_time']) ?></td>
                                    <td data-label="Duration"><?= e(dur((int)$r['dur_min'])) ?></td>
                                    <td data-label="Device"><?= e(device((string)$r['user_agent'])) ?></td>
                                    <td data-label="Location">
                                        <span style="font-weight: 600;"><?= e($liveLoc) ?></span><br>
                                        <small class="muted">IP: <?= e($r['ip_address']) ?></small>
                                    </td>
                                    <td data-label="Status"><span class="badge <?= $st[1] ?>"><?= $st[0] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="tablewrap">
                    <table class="rt">
                        <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th><th>Location</th></tr></thead>
                        <tbody>
                            <?php foreach ($rows as $r): $l = $labels[$r['action']] ?? [$r['action'], 'info']; 
                                // LIVE LOCATION LOOKUP
                                $liveLoc = resolve_live_location($r['ip_address'], $r['location']);
                            ?>
                                <tr>
                                    <td data-label="Time"><?= when($r['created_at']) ?></td>
                                    <td data-label="User"><b><?= e($r['username'] ?? '—') ?></b></td>
                                    <td data-label="Action"><span class="badge b-<?= $l[1] ?>"><?= e($l[0]) ?></span></td>
                                    <td data-label="Details"><?= e($r['details']) ?></td>
                                    <td data-label="Location">
                                        <span style="font-weight: 600;"><?= e($liveLoc) ?></span><br>
                                        <small class="muted">IP: <?= e($r['ip_address']) ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($total > $per): ?>
                <div class="pager noprint">
                    <span>Page <?= $page ?> of <?= $pages ?> (<?= number_format($total) ?> records)</span>
                    <span>
                        <?php if ($page > 1): ?><a class="btn ghost" href="<?= e($pageUrl(['page' => $page - 1])) ?>">&larr; Newer</a><?php endif; ?>
                        <?php if ($page < $pages): ?><a class="btn ghost" href="<?= e($pageUrl(['page' => $page + 1])) ?>">Older &rarr;</a><?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<?php if ($isAdmin): ?>
<div class="modal noprint" id="resetModal">
    <form class="box" method="post" id="resetForm">
        <h3>Reset activity</h3>
        <div class="warnbox"><b>This cannot be undone.</b> Export to PDF first if you want a copy. People who are signed in right now are kept. The reset itself is recorded in the log.</div>
        <input type="hidden" name="do" value="reset">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="view" value="<?= e($view) ?>">
        <div class="r"><label>What to clear</label>
            <select name="scope">
                <option value="both" selected>Both: activity log and login sessions</option>
                <option value="activity">Activity log only (what people did)</option>
                <option value="sessions">Login sessions only</option>
            </select></div>
        <div class="r"><label>Which records</label>
            <select name="range">
                <option value="all">Everything</option>
                <option value="30">Only records older than 30 days</option>
                <option value="90">Only records older than 90 days</option>
            </select></div>
        <div class="r"><label>Your password</label><input type="password" name="password" autocomplete="current-password" required></div>
        <div class="err"><?= e($resetError) ?></div>
        <div class="actions"><button type="button" class="btn ghost" id="resetCancel">Cancel</button><button class="btn danger">Reset now</button></div>
    </form>
</div>
<?php endif; ?>

<script>
    document.querySelectorAll('.local-time').forEach(el => {
        const d = new Date(el.dataset.time.replace(' ', 'T'));
        if (!isNaN(d.getTime())) {
            el.textContent = d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) + ', ' + 
                             d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: true });
        }
    });

    const rm = document.getElementById('resetModal');
    if (rm) {
        const openReset = () => { rm.classList.add('open'); setTimeout(() => rm.querySelector('input[type=password]').focus(), 50); };
        document.getElementById('resetBtn').addEventListener('click', openReset);
        document.getElementById('resetCancel').addEventListener('click', () => rm.classList.remove('open'));
        <?php if ($resetError): ?>openReset();<?php endif; ?>
    }
</script>
</body>
</html>