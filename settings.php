<?php
require __DIR__ . '/config.php';
require_admin();
$user = current_user();
$active = 'settings';

$resetError = '';
$flash = $_SESSION['flash_settings'] ?? '';
unset($_SESSION['flash_settings']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'reset_system') {
    $scope = (string)($_POST['scope'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        $resetError = 'Your session expired. Reload the page and try again.';
    } elseif (!in_array($scope, ['all', 'sales', 'inventory', 'rooms', 'expenses', 'logs', 'pa_system', 'ground_hire'], true)) {
        $resetError = 'Choose a valid category to reset.';
    } else {
        $pdo = db();
        $ps = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $ps->execute([current_user()['user_id']]);
        $hash = (string)$ps->fetchColumn();

        if (!$hash || !password_verify((string)($_POST['password'] ?? ''), $hash)) {
            $resetError = 'Wrong password. Nothing was deleted.';
            log_activity('reset_denied', 'Wrong password when trying to reset system data');
        } else {
            try {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
                $deletedSummary = [];
                
                if ($scope === 'all' || $scope === 'sales') {
                    $pdo->exec("TRUNCATE TABLE sales_record_items");
                    $pdo->exec("TRUNCATE TABLE sales_record");
                    $deletedSummary[] = "All Sales";
                }
                if ($scope === 'all' || $scope === 'inventory') {
                    $pdo->exec("TRUNCATE TABLE stock_entries");
                    $deletedSummary[] = "Stock Entries (Products kept)";
                }
                if ($scope === 'all' || $scope === 'rooms') {
                    $pdo->exec("TRUNCATE TABLE room_payments");
                    $pdo->exec("TRUNCATE TABLE room_bookings");
                    $pdo->exec("TRUNCATE TABLE rooms");
                    $deletedSummary[] = "All Rooms & Bookings";
                }
                if ($scope === 'all' || $scope === 'expenses') {
                    $pdo->exec("TRUNCATE TABLE expenses");
                    $deletedSummary[] = "All Expenses";
                }
                if ($scope === 'all' || $scope === 'logs') {
                    $pdo->exec("TRUNCATE TABLE activity_log");
                    $pdo->exec("TRUNCATE TABLE login_sessions");
                    $deletedSummary[] = "All Activity Logs";
                }
                // --- PA SYSTEM RESET ---
                if ($scope === 'all' || $scope === 'pa_system') {
                    $pdo->exec("TRUNCATE TABLE pa_collections"); 
                    $deletedSummary[] = "All PA System Hires";
                }
                // --- GROUND HIRE RESET ---
                if ($scope === 'all' || $scope === 'ground_hire') {
                    $pdo->exec("TRUNCATE TABLE ground_hire");
                    $deletedSummary[] = "All Ground Hires";
                }

                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
                $summaryText = implode(', ', $deletedSummary);
                log_activity('system_reset', "Reset system category [{$scope}]: removed {$summaryText}. User accounts kept.");
                $_SESSION['flash_settings'] = "Success! Cleared: {$summaryText}. User accounts were safely kept.";
                header('Location: settings.php');
                exit;
            } catch (PDOException $ex) {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
                $resetError = 'Could not complete the reset. Database error: ' . $ex->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Settings</title>
    <style>
        :root{
            --navy:#0f172a;--slate:#334155;--muted:#64748b;--line:#e2e8f0;--amber:#f59e0b;--green:#16a34a;--red:#dc2626;--radius:14px;
            /* Dark theme (matches POS) */
            --surface:#111c2e;
            --surface-2:#16243a;
            --surface-3:#1e2d47;
            --text:#e2e8f0;
            --text-dim:#94a3b8;
            --text-mute:#64748b;
            --border:#243349;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; background: #0b1220; color: var(--text); min-height: 100vh; }
        main { max-width: 800px; margin: 28px auto; padding: 0 18px 40px; display: grid; gap: 22px; }
        .head h2 { margin: 0; font-size: 1.6rem; letter-spacing: -.2px; color: var(--text); }
        .head .sub { margin: 4px 0 0; color: var(--text-dim); font-size: .95rem; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 22px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.3); }
        .btn { border: 0; border-radius: 10px; padding: 11px 18px; min-height: 42px; font-weight: 700; cursor: pointer; font-size: .9rem; font-family: inherit; text-decoration: none; display: inline-block; text-align: center; transition: background .15s, transform .05s, box-shadow .15s; }
        .btn:active:not(:disabled) { transform: translateY(1px); }
        .btn.ghost { background: var(--surface-2); color: var(--text-dim); }
        .btn.ghost:hover:not(:disabled) { background: var(--surface-3); color: var(--text); }
        .btn.danger { background: var(--red); color: #fff; }
        .btn.danger:hover:not(:disabled) { background: #b91c1c; box-shadow: 0 4px 12px rgba(185,28,28,.3); }
        .ok { background: rgba(22,163,74,.15); color: #4ade80; padding: 14px 16px; border-radius: 10px; font-weight: 600; margin-bottom: 16px; border: 1px solid rgba(22,163,74,.3); }
        .danger-zone { border: 2px solid rgba(220,38,38,.4); background: linear-gradient(180deg, rgba(220,38,38,.08) 0%, var(--surface) 100%); }
        .dz-head { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 14px; }
        .dz-icon { flex-shrink: 0; width: 38px; height: 38px; border-radius: 50%; background: var(--red); color: #fff; display: grid; place-items: center; font-weight: 800; font-size: 1.15rem; }
        .dz-head h2 { margin: 0; font-size: 1.1rem; color: #f87171; }
        .dz-head p { margin: 4px 0 0; font-size: .9rem; color: var(--text-dim); line-height: 1.5; }
        .dz-head p b { color: var(--text); }
        .chips { list-style: none; margin: 0 0 14px; padding: 0; display: flex; flex-wrap: wrap; gap: 8px; }
        .chips li { background: rgba(220,38,38,.2); color: #f87171; border-radius: 999px; padding: 4px 12px; font-size: .78rem; font-weight: 700; }
        .chips li.safe { background: rgba(22,163,74,.2); color: #4ade80; }
        #resetBtn { min-width: 220px; }
        .modal { position: fixed; inset: 0; background: rgba(0,0,0,.7); backdrop-filter: blur(4px); display: none; place-items: center; z-index: 60; padding: 12px; }
        .modal.open { display: grid; }
        .modal .box { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 24px; width: min(440px, 100%); max-height: 94vh; overflow: auto; color: var(--text); box-shadow: 0 25px 50px -12px rgba(0,0,0,.6); }
        .modal h3 { margin: 0 0 8px; color: #f87171; }
        .modal .warnbox { background: rgba(220,38,38,.15); color: #f87171; border: 1px solid rgba(220,38,38,.3); border-radius: 8px; padding: 10px 12px; font-size: .85rem; line-height: 1.5; margin-bottom: 10px; }
        .modal .r { margin-bottom: 12px; }
        .modal .err { color: #f87171; font-weight: 600; font-size: .88rem; min-height: 1.2em; }
        .modal .actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 14px; }
        label { display: block; font-size: .72rem; font-weight: 700; color: var(--text-dim); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .45px; }
        input, select { width: 100%; padding: 11px 12px; border: 1px solid var(--border); border-radius: 10px; font-size: .95rem; background: var(--surface-2); color: var(--text); transition: border-color .15s, box-shadow .15s; }
        input::placeholder { color: var(--text-mute); }
        input:focus, select:focus { outline: none; border-color: var(--amber); box-shadow: 0 0 0 3px rgba(245,158,11,.25); background: var(--surface-3); }
        @media(max-width: 640px) {
            main { padding: 0 12px 32px; margin: 14px auto; gap: 14px; }
            .head h2 { font-size: 1.35rem; }
            .card { padding: 16px; }
            .btn { min-height: 46px; }
            #resetBtn { width: 100%; }
            .modal .actions { flex-direction: column-reverse; }
            .modal .actions .btn { width: 100%; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
    <div class="head">
        <h2>System Settings</h2>
        <p class="sub">Manage system maintenance and data resets.</p>
    </div>

    <?php if ($flash): ?><div class="ok"><?= e($flash) ?></div><?php endif; ?>

    <section class="card danger-zone">
        <div class="dz-head">
            <span class="dz-icon" aria-hidden="true">!</span>
            <div>
                <h2>Danger Zone: Reset System</h2>
                <p>Permanently delete records like sales, stock entries, rooms, or clear everything for a fresh start. <b>User accounts and products are always kept safe.</b></p>
            </div>
        </div>
        <ul class="chips">
            <li>Sales</li><li>Stock entries</li><li>Rooms</li><li>Bookings</li><li>Expenses</li><li>Activity logs</li><li>PA System</li><li>Ground Hire</li><li class="safe">User accounts are kept</li><li class="safe">Products are kept</li>
        </ul>
        <button class="btn danger" id="resetBtn" type="button">Reset System Data</button>
    </section>
</main>

<div class="modal" id="resetModal">
    <form class="box" method="post" id="resetForm">
        <h3>Reset System Data</h3>
        <div class="warnbox"><b>This cannot be undone.</b> Choose which category to reset below. Your user login accounts and product catalog will remain intact.</div>
        <input type="hidden" name="do" value="reset_system">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="r">
            <label>What to clear</label>
            <select name="scope">
                <option value="all" selected>Everything (All sales, stock entries, rooms, expenses, logs, PA, Ground)</option>
                <option value="sales">Sales only</option>
                <option value="inventory">Inventory only (Stock entries — products kept)</option>
                <option value="rooms">Rooms & Bookings only</option>
                <option value="expenses">Expenses only</option>
                <option value="logs">Activity logs only</option>
                <option value="pa_system">PA System Hires only</option>
                <option value="ground_hire">Ground Hire Bookings only</option>
            </select>
        </div>
        <div class="r">
            <label>Your Admin Password</label>
            <input type="password" name="password" autocomplete="current-password" required placeholder="Enter password to confirm">
        </div>
        <div class="err"><?= e($resetError) ?></div>
        <div class="actions">
            <button type="button" class="btn ghost" id="resetCancel">Cancel</button>
            <button class="btn danger" type="submit">Proceed with Reset</button>
        </div>
    </form>
</div>

<script>
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