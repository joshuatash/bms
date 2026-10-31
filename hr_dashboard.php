<?php
require __DIR__ . '/config.php';
require_hr(); // Restricts this page to ONLY the HR user

$user = current_user();
$active = 'hr_dashboard';

$passError = '';
$passSuccess = '';
$logSuccess = '';

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
        $logSuccess = 'Activity logs cleared successfully.';
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HR Dashboard | Big Wallet</title>
    <style>
        :root { --navy:#0f172a; --slate:#334155; --muted:#64748b; --line:#e2e8f0; --amber:#f59e0b; --green:#16a34a; --red:#dc2626; --radius:14px; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); color: var(--navy); min-height: 100vh; }
        main { max-width: 1000px; margin: 28px auto; padding: 0 18px 40px; display: flex; flex-direction: column; gap: 22px; }
        .head h2 { margin: 0; font-size: 1.6rem; letter-spacing: -.2px; }
        .head .sub { margin: 4px 0 0; color: var(--muted); font-size: .95rem; }
        
        /* Cards */
        .card { background: #fff; border-radius: var(--radius); padding: 22px; box-shadow: 0 1px 2px rgba(15,23,42,.06), 0 6px 18px rgba(15,23,42,.05); }
        .card-head { margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 14px; flex-wrap: wrap; gap: 10px; }
        .card-head h2 { margin: 0; font-size: 1.15rem; }
        .card-head p { margin: 4px 0 0; color: var(--muted); font-size: .88rem; }
        
        /* Forms & Buttons */
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .grid-full { grid-column: span 2; }
        label { display: block; font-size: .72rem; font-weight: 700; color: var(--slate); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .45px; }
        input, select { width: 100%; padding: 11px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: .95rem; background: #fff; color: var(--navy); transition: border-color .15s, box-shadow .15s; }
        input:focus, select:focus { outline: none; border-color: var(--amber); box-shadow: 0 0 0 3px rgba(245,158,11,.25); }
        .btn { border: 0; border-radius: 10px; padding: 11px 18px; min-height: 42px; font-weight: 700; cursor: pointer; font-size: .9rem; font-family: inherit; text-decoration: none; display: inline-block; text-align: center; transition: background .15s, transform .05s; }
        .btn:active:not(:disabled) { transform: translateY(1px); }
        .btn.primary { background: var(--amber); color: var(--navy); } .btn.primary:hover:not(:disabled) { background: #d97706; color: #fff; }
        .btn.ghost { background: #e2e8f0; color: var(--slate); } .btn.ghost:hover:not(:disabled) { background: #cbd5e1; }
        .btn.danger { background: var(--red); color: #fff; } .btn.danger:hover:not(:disabled) { background: #b91c1c; }
        .btn.sm { padding: 7px 13px; min-height: 34px; font-size: .8rem; border-radius: 8px; }
        #save, #btnChangePass { width: 100%; margin-top: 4px; }
        .hint { font-size: .88rem; margin-top: 12px; min-height: 1.2em; color: var(--muted); }
        .hint.good { color: var(--green); font-weight: 600; } .hint.bad { color: var(--red); font-weight: 600; }
        .alert { padding: 14px 16px; border-radius: 10px; font-weight: 600; margin-bottom: 16px; font-size: 0.92rem; }
        .alert.ok { background: #dcfce7; color: #166534; }
        .alert.err { background: #fee2e2; color: #991b1b; }

        /* ── Standard table (User Management) ── */
        .tablewrap { overflow-x: auto; margin: 0 -22px -22px; padding: 0 22px; }
        table { width: 100%; border-collapse: collapse; font-size: .9rem; }
        th { text-align: left; background: #f8fafc; color: var(--slate); padding: 12px 10px; font-size: .72rem; text-transform: uppercase; letter-spacing: .45px; border-bottom: 2px solid var(--line); white-space: nowrap; }
        td { padding: 12px 10px; border-bottom: 1px solid var(--line); vertical-align: middle; }
        tbody tr:hover td { background: #fafbfd; }
        .badge { padding: 4px 11px; border-radius: 999px; font-size: .74rem; font-weight: 700; white-space: nowrap; text-transform: capitalize; }
        .badge.admin { background: #dbeafe; color: #1e40af; } .badge.cashier { background: #fef3c7; color: #92400e; }
        .badge.manager { background: #ede9fe; color: #5b21b6; } .badge.hr { background: #fce7f3; color: #be185d; }
        .badge.on { background: #dcfce7; color: #166534; } .badge.off { background: #fee2e2; color: #991b1b; }
        
        .actions-cell { display: flex; flex-direction: column; gap: 6px; align-items: flex-start; }
        .action-group { display: flex; gap: 6px; flex-wrap: wrap; }
        .pending-alert { background: #fef3c7; border: 1px solid #fde68a; padding: 8px; border-radius: 6px; width: 100%; margin-bottom: 4px; }
        .pending-alert p { margin: 0 0 6px; font-size: 0.75rem; color: #92400e; font-weight: 700; text-transform: uppercase; }

        /* ── ONLINE DOT ANIMATION ── */
        .online-dot { display: inline-block; width: 10px; height: 10px; background-color: var(--green); border-radius: 50%; margin-right: 6px; box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); animation: pulse 1.5s infinite; flex-shrink: 0; }
        @keyframes pulse { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(22, 163, 74, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); } }

        /* ── ACTIVE SESSIONS — desktop table ── */
        .sessions-table th { background: #0f172a; color: #fff; border-bottom: none; }
        .sessions-table td { font-size: 0.85rem; }

        /* ── ACTIVE SESSIONS — mobile card stack ── */
        .session-cards { display: none; flex-direction: column; gap: 12px; }
        .session-card { background: #f8fafc; border: 1px solid var(--line); border-radius: 12px; padding: 14px 16px; }
        .session-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .session-card-username { display: flex; align-items: center; font-weight: 700; font-size: .95rem; }
        .session-card-rows { display: grid; grid-template-columns: auto 1fr; gap: 6px 10px; font-size: .82rem; margin-bottom: 12px; }
        .session-card-label { color: var(--muted); font-weight: 600; white-space: nowrap; padding-top: 1px; }
        .session-card-value { color: var(--slate); word-break: break-all; }

        /* ── MODERN TIMELINE ── */
        .timeline { position: relative; padding-left: 10px; margin-top: 10px; }
        .timeline::before { content: ''; position: absolute; top: 0; bottom: 0; left: 20px; width: 2px; background: var(--line); }
        .timeline-item { position: relative; padding-left: 45px; padding-bottom: 24px; }
        .timeline-item:last-child { padding-bottom: 0; }
        .timeline-dot { position: absolute; left: 12px; top: 4px; width: 18px; height: 18px; border-radius: 50%; background: #fff; border: 3px solid var(--line); z-index: 2; }
        .timeline-dot.login { border-color: var(--green); background: #dcfce7; }
        .timeline-dot.logout { border-color: #64748b; background: #f1f5f9; }
        .timeline-dot.delete, .timeline-dot.reset, .timeline-dot.rejected { border-color: var(--red); background: #fee2e2; }
        .timeline-dot.create, .timeline-dot.register, .timeline-dot.approved { border-color: #3b82f6; background: #dbeafe; }
        .timeline-dot.update, .timeline-dot.enable { border-color: var(--amber); background: #fef3c7; }
        .timeline-content { background: #f8fafc; border: 1px solid var(--line); border-radius: 12px; padding: 16px; transition: transform 0.2s, box-shadow 0.2s; }
        .timeline-content:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(15,23,42,.08); background: #fff; }
        .timeline-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; flex-wrap: wrap; gap: 10px; }
        .timeline-user { font-size: 1rem; font-weight: 700; color: var(--navy); display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .timeline-user small { font-weight: 500; color: var(--muted); font-size: 0.85rem; }
        .timeline-meta { font-size: 0.78rem; color: var(--muted); display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .timeline-body { font-size: 0.95rem; color: var(--slate); line-height: 1.5; }
        .action-tag { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .action-tag.login { background: #dcfce7; color: #166534; }
        .action-tag.logout { background: #e2e8f0; color: #475569; }
        .action-tag.reset, .action-tag.delete, .action-tag.rejected { background: #fee2e2; color: #991b1b; }
        .action-tag.create, .action-tag.register, .action-tag.approved { background: #dbeafe; color: #1e40af; }
        .action-tag.update, .action-tag.enable { background: #fef3c7; color: #92400e; }
        .action-tag.default { background: #e2e8f0; color: var(--slate); }

        /* ── RESPONSIVE BREAKPOINTS ── */
        @media (max-width: 640px) {
            main { padding: 0 12px 32px; margin: 14px auto; gap: 14px; }
            .head h2 { font-size: 1.3rem; }

            /* Form grid collapses to 1 column */
            .grid { grid-template-columns: 1fr; }
            .grid-full { grid-column: span 1; }

            /* Card inner padding tighter */
            .card { padding: 16px; }
            .card-head { margin-bottom: 14px; padding-bottom: 12px; }

            /* Standard table — keep horizontal scroll but tighten */
            .tablewrap { margin: 0 -16px -16px; padding: 0 16px; }
            th, td { padding: 10px 8px; }

            /* Sessions: hide table, show cards */
            .sessions-tablewrap { display: none; }
            .session-cards { display: flex; }

            /* Timeline tighter on mobile */
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
        .toast { display: flex; align-items: center; gap: 12px; min-width: 280px; max-width: 380px; padding: 14px 18px; border-radius: 14px; font-size: .92rem; font-weight: 600; color: #fff; box-shadow: 0 8px 28px rgba(15,23,42,.18); pointer-events: all; animation: toast-in .3s cubic-bezier(.34,1.56,.64,1) forwards; }
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
        .modal-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.55); backdrop-filter: blur(3px); z-index: 10000; display: grid; place-items: center; padding: 20px; opacity: 0; animation: fade-in .2s ease forwards; }
        .modal-backdrop.hiding { animation: fade-out .2s ease forwards; }
        .modal { background: #fff; border-radius: 18px; padding: 28px 28px 22px; max-width: 400px; width: 100%; box-shadow: 0 24px 60px rgba(15,23,42,.25); transform: scale(.92) translateY(16px); animation: modal-in .25s cubic-bezier(.34,1.4,.64,1) forwards; }
        .modal-backdrop.hiding .modal { animation: modal-out .2s ease forwards; }
        .modal-icon { width: 52px; height: 52px; border-radius: 50%; display: grid; place-items: center; margin: 0 auto 16px; }
        .modal-icon.danger { background: #fee2e2; }
        .modal-icon.warning { background: #fef3c7; }
        .modal-title { font-size: 1.1rem; font-weight: 800; color: var(--navy); text-align: center; margin: 0 0 8px; }
        .modal-body { font-size: .92rem; color: var(--muted); text-align: center; line-height: 1.55; margin: 0 0 22px; }
        .modal-body strong { color: var(--slate); }
        .modal-actions { display: flex; gap: 10px; }
        .modal-actions .btn { flex: 1; justify-content: center; }
        @keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fade-out { from { opacity: 1; } to { opacity: 0; } }
        @keyframes modal-in { from { transform: scale(.92) translateY(16px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }
        @keyframes modal-out { from { transform: scale(1) translateY(0); opacity: 1; } to { transform: scale(.92) translateY(16px); opacity: 0; } }

        /* Tick animation */
        .tick-circle { stroke: #fff; stroke-width: 2.5; fill: none; stroke-dasharray: 50; stroke-dashoffset: 50; animation: tick-draw .4s .1s ease forwards; }
        @keyframes tick-draw { to { stroke-dashoffset: 0; } }

        @media (max-width: 480px) {
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
        <p class="sub">Manage system accounts, monitor activity, and track live sessions.</p>
    </div>

    <!-- 1. MANAGE USERS -->
    <section class="card">
        <div class="card-head">
            <div>
                <h2>User Management</h2>
                <p>Create accounts, approve requests, disable, or delete users.</p>
            </div>
        </div>
        
        <!-- Create Form -->
        <div class="grid" style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px dashed var(--line);">
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
                <tr><td colspan="4" style="text-align:center; color:var(--muted); padding:30px;">Loading users...</td></tr>
            </tbody>
        </table></div>
    </section>

    <!-- 2. ACTIVE SESSIONS (WHO IS ONLINE) -->
    <section class="card">
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
                    <tr><td colspan="6" style="text-align:center; color:var(--muted); padding:20px;">Loading sessions...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile card stack -->
        <div class="session-cards" id="sessionCards">
            <p style="color:var(--muted); text-align:center; padding:16px 0;">Loading sessions...</p>
        </div>
    </section>

    <!-- 3. CHANGE PASSWORD -->
    <section class="card">
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

    <!-- 4. ACTIVITY LOGS (MODERN TIMELINE) -->
    <section class="card">
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

        <div class="timeline">
            <?php if (empty($logs)): ?>
                <p style="color: var(--muted); text-align: center; padding: 20px 0;">No activity recorded yet.</p>
            <?php else: ?>
                <?php foreach ($logs as $log): 
                    $actionClass = 'default';
                    if (strpos($log['action'], 'login') !== false) $actionClass = 'login';
                    if (strpos($log['action'], 'logout') !== false) $actionClass = 'logout';
                    if (strpos($log['action'], 'reset') !== false || strpos($log['action'], 'delete') !== false || strpos($log['action'], 'rejected') !== false) $actionClass = 'reset';
                    if (strpos($log['action'], 'create') !== false || strpos($log['action'], 'register') !== false || strpos($log['action'], 'approved') !== false) $actionClass = 'create';
                    if (strpos($log['action'], 'update') !== false || strpos($log['action'], 'enable') !== false) $actionClass = 'update';
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
            $('tbody').innerHTML = `<tr><td colspan="4" style="color:red; text-align:center;">${rows.error}</td></tr>`;
            return;
        }

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
                normalActions = '<span style="font-size:.8rem; color:var(--muted); font-weight:600;">Your Account</span>';
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
                    <span style="font-size:.78rem; color:var(--muted);">@${esc(u.username)}</span>
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
        $('tbody').innerHTML = `<tr><td colspan="4" style="color:red; text-align:center;">Failed to load users: ${err.message}</td></tr>`;
    }
}

async function loadSessions() {
    $('sessionsBody').innerHTML = `<tr><td colspan="6" style="text-align:center; color:var(--muted); padding:20px;">Fetching live locations... (may take 1-2s)</td></tr>`;
    $('sessionCards').innerHTML = `<p style="color:var(--muted); text-align:center; padding:16px 0;">Fetching live locations...</p>`;

    try {
        const res = await fetch('api/users.php?action=active_sessions');
        const rows = await res.json();
        
        if (rows.error) {
            $('sessionsBody').innerHTML = `<tr><td colspan="6" style="color:red; text-align:center;">${rows.error}</td></tr>`;
            $('sessionCards').innerHTML = `<p style="color:red; text-align:center;">${rows.error}</p>`;
            return;
        }

        // Desktop table rows
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
                        <code style="background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:0.8rem;">${esc(s.ip_address || 'Unknown')}</code><br>
                        <small style="color:var(--muted);">📍 ${esc(s.location)}</small>
                    </td>
                    <td style="font-size:.85rem;">${esc(device)}</td>
                    <td>${dateFmt(s.login_time)}</td>
                    <td>${dateFmt(s.last_activity)}</td>
                    <td><button class="btn sm danger" data-kick="${s.session_id}" data-name="${esc(s.username)}">Force Logout</button></td>
                </tr>
            `;
        }).join('');

        // Mobile card stack
        $('sessionCards').innerHTML = rows.length === 0
            ? `<p style="color:var(--muted); text-align:center; padding:16px 0;">No active sessions.</p>`
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
                        <span class="session-card-value"><code style="background:#f1f5f9; padding:2px 5px; border-radius:4px; font-size:.8rem;">${esc(s.ip_address || 'Unknown')}</code></span>
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
        $('sessionsBody').innerHTML = `<tr><td colspan="6" style="color:red; text-align:center;">Failed to load sessions.</td></tr>`;
        $('sessionCards').innerHTML = `<p style="color:red; text-align:center;">Failed to load sessions.</p>`;
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

// Shared handler for both desktop tbody and mobile cards
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