<?php require __DIR__ . '/config.php'; require_login();
if (!in_array(current_user()['role'], ['admin', 'manager'], true)) { header('Location: pos.php'); exit; } $active = 'dashboard'; $pdo = db(); 
$period = $_GET['period'] ?? 'today';
$isToday = $period === 'today';
$where = $isToday ? "DATE(p.paid_at) = CURDATE()" : "p.paid_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
$title = $isToday ? "Rooms Collection Today" : "Rooms Collection This Month";
$label = $isToday ? date('d M Y') : date('F Y');

$sql = "SELECT p.payment_id, p.amount, p.method, p.paid_at, b.booking_no, b.guest_name, b.booking_type, r.room_number, u.full_name AS received_by_name FROM room_payments p JOIN room_bookings b ON b.booking_id = p.booking_id JOIN rooms r ON r.room_id = b.room_id JOIN users u ON u.user_id = p.received_by WHERE $where ORDER BY p.paid_at DESC";
$rows = $pdo->query($sql)->fetchAll();
$total = 0; $byMethod = [];
foreach ($rows as $r) { $total += (float)$r['amount']; $m = $r['method']; $byMethod[$m] = ($byMethod[$m] ?? 0) + (float)$r['amount']; }
$n = fn($x) => number_format((float)$x);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($title) ?></title>
    <style>
        :root{
            --navy:#0f172a;--slate:#334155;--muted:#64748b;--line:#e2e8f0;--amber:#f59e0b;--green:#16a34a;
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
        body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; background: #0b1220; color: var(--text); }
        main { max-width: 1000px; margin: 24px auto; padding: 0 18px; display: grid; gap: 18px; }
        
        .head { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; }
        .head h2 { margin: 0; font-size: 1.4rem; color: var(--text); }
        .head p { margin: 2px 0 0; color: var(--text-dim); }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        
        .btn { display: inline-block; border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 700; cursor: pointer; font-size: .9rem; text-decoration: none; transition: .15s; }
        .btn.ghost { background: var(--surface-2); color: var(--text-dim); }
        .btn.ghost:hover { background: var(--surface-3); color: var(--text); }
        .btn.tab { background: var(--surface-2); color: var(--text-dim); border: 1px solid var(--border); }
        .btn.tab:hover { background: var(--surface-3); color: var(--text); }
        .btn.tab.on { background: var(--amber); color: #0b1220; border-color: var(--amber); }
        .btn.primary { background: var(--amber); color: #0b1220; box-shadow: 0 4px 6px -1px rgba(245,158,11,.3); }
        .btn.primary:hover { background: #d97706; color: #fff; }
        
        .tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; }
        .tile { background: var(--surface); border-radius: 12px; padding: 16px 18px; border-left: 5px solid var(--text-dim); border-top: 1px solid var(--border); border-right: 1px solid var(--border); border-bottom: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,.3); }
        .tile.money { border-left-color: var(--green); }
        .tile small { color: var(--text-dim); }
        .tile b { display: block; font-size: 1.5rem; color: var(--text); }
        
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.3); }
        .tablewrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .92rem; color: var(--text); }
        th { text-align: left; background: var(--surface-2); color: var(--text-dim); padding: 10px 12px; font-size: .72rem; text-transform: uppercase; border-bottom: 2px solid var(--border); }
        td { padding: 10px 12px; border-bottom: 1px solid var(--border); }
        .num { text-align: right; }
        .muted { color: var(--text-dim); font-size: .82rem; }
        .badge { padding: 3px 8px; border-radius: 999px; font-size: .7rem; font-weight: 700; background: rgba(37,99,235,.2); color: #60a5fa; }
        .badge.short { background: rgba(245,158,11,.2); color: #fbbf24; }
        .empty { text-align: center; color: var(--text-mute); padding: 34px; }
        
        /* Print specific styles */
        .printonly { display: none; }
        
        @media(max-width: 640px) {
            main { padding: 0 12px; margin: 14px auto; }
            .btn { flex: 1; text-align: center; }
            .rt thead { display: none; }
            .rt, .rt tbody, .rt tr, .rt td { display: block; width: 100%; }
            .rt tr { border: 1px solid var(--border); border-radius: 10px; margin-bottom: 10px; padding: 6px 10px; background: var(--surface-2); }
            .rt td { display: flex; justify-content: space-between; align-items: center; gap: 12px; border: 0; padding: 7px 2px; text-align: right; }
            .rt td::before { content: attr(data-label); font-weight: 700; font-size: .68rem; text-transform: uppercase; color: var(--text-dim); text-align: left; }
            .rt td[colspan]::before { content: none; } .rt td[colspan] { display: block; text-align: center; }
        }
        
        @media print {
            .sidebar, .mbar, .scrim, .noprint { display: none !important; }
            body { padding: 0 !important; background: #fff; color: #0f172a; }
            main { margin: 0; padding: 0; max-width: none; }
            .card, .tile { box-shadow: none; border: 1px solid #cbd5e1; background: #fff; }
            .head h2, .tile b { color: #0f172a; }
            .head p, .tile small, td { color: #334155; }
            .printonly { display: block; }
            th { background: #0f172a !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tr { break-inside: avoid; }
            @page { size: A4 landscape; margin: 10mm; }
            .rt thead { display: table-header-group; }
            .rt tr { display: table-row; background: #fff; }
            .rt td { display: table-cell; text-align: left; border-bottom: 1px solid #cbd5e1; padding: 8px; }
            .rt td::before { content: none; }
            .rt td.num { text-align: right; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
    <div class="head">
        <div>
            <h2><?= e($title) ?></h2>
            <p class="noprint"><?= e($label) ?></p>
            <p class="printonly" style="color: #64748b; font-size: 0.85rem;">Period: <?= e($label) ?> | Generated: <?= date('d M Y, H:i') ?></p>
        </div>
        <div class="actions noprint">
            <a class="btn ghost" href="dashboard.php">&larr; Back to Dashboard</a>
            <a class="btn tab <?= $period === 'today' ? 'on' : '' ?>" href="?period=today">Today</a>
            <a class="btn tab <?= $period === 'month' ? 'on' : '' ?>" href="?period=month">This Month</a>
            <button class="btn primary" onclick="window.print()">Export to PDF</button>
        </div>
    </div>

    <section class="tiles">
        <div class="tile money">
            <small>Total Collected</small><b><?= CURRENCY ?> <?= $n($total) ?></b>
        </div>
        <?php foreach ($byMethod as $method => $amt): ?>
            <div class="tile">
                <small>Via <?= e($method) ?></small><b><?= CURRENCY ?> <?= $n($amt) ?></b>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="card">
        <?php if (!$rows): ?>
            <div class="empty">No room payments recorded for this period yet.</div>
        <?php else: ?>
            <div class="tablewrap">
                <table class="rt">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Room / Guest</th>
                            <th>Booking No</th>
                            <th>Type</th>
                            <th>Method</th>
                            <th>Received By</th>
                            <th class="num">Amount (<?= CURRENCY ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td data-label="Time"><span class="local-time" data-time="<?= e($r['paid_at']) ?>">...</span></td>
                                <td data-label="Room / Guest"><b>Room <?= e($r['room_number']) ?></b><br><span class="muted"><?= e($r['guest_name']) ?></span></td>
                                <td data-label="Booking No"><?= e($r['booking_no']) ?></td>
                                <td data-label="Type"><span class="badge <?= $r['booking_type'] ?>"><?= ucfirst($r['booking_type']) ?></span></td>
                                <td data-label="Method"><?= e($r['method']) ?></td>
                                <td data-label="Received By"><?= e($r['received_by_name']) ?></td>
                                <td class="num" data-label="Amount"><b><?= CURRENCY ?> <?= $n($r['amount']) ?></b></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<script>
    document.querySelectorAll('.local-time').forEach(el => {
        const d = new Date(el.dataset.time.replace(' ', 'T'));
        if (!isNaN(d.getTime())) {
            el.textContent = d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) + ', ' + 
                             d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
        }
    });
</script>
</body>
</html>