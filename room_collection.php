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
        :root { --navy:#0f172a; --slate:#334155; --muted:#64748b; --line:#e2e8f0; --amber:#f59e0b; --green:#16a34a; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);; color: var(--navy); }
        main { max-width: 1000px; margin: 24px auto; padding: 0 18px; display: grid; gap: 18px; }
        
        .head { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; }
        .head h2 { margin: 0; font-size: 1.4rem; } .head p { margin: 2px 0 0; color: var(--muted); }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        
        .btn { display: inline-block; border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 700; cursor: pointer; font-size: .9rem; text-decoration: none; }
        .btn.ghost { background: #e2e8f0; color: var(--slate); } .btn.ghost:hover { background: #cbd5e1; }
        .btn.tab { background: #fff; color: var(--slate); border: 1px solid var(--line); } .btn.tab.on { background: var(--navy); color: #fff; border-color: var(--navy); }
        .btn.primary { background: var(--amber); color: var(--navy); } .btn.primary:hover { background: #d97706; color: #fff; }
        
        .tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; }
        .tile { background: #fff; border-radius: 12px; padding: 16px 18px; border-left: 5px solid var(--slate); box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .tile.money { border-color: var(--green); } .tile small { color: var(--muted); } .tile b { display: block; font-size: 1.5rem; }
        
        .card { background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .tablewrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .92rem; }
        th { text-align: left; background: #f8fafc; color: var(--slate); padding: 10px 12px; font-size: .72rem; text-transform: uppercase; border-bottom: 2px solid var(--line); }
        td { padding: 10px 12px; border-bottom: 1px solid var(--line); } .num { text-align: right; }
        .badge { padding: 3px 8px; border-radius: 999px; font-size: .7rem; font-weight: 700; background: #dbeafe; color: #1e40af; } .badge.short { background: #fef3c7; color: #92400e; }
        .empty { text-align: center; color: var(--muted); padding: 34px; }
        
        /* Print specific styles */
        .printonly { display: none; }
        
        @media(max-width: 640px) {
            main { padding: 0 12px; margin: 14px auto; }
            .btn { flex: 1; text-align: center; }
            .rt thead { display: none; }
            .rt, .rt tbody, .rt tr, .rt td { display: block; width: 100%; }
            .rt tr { border: 1px solid var(--line); border-radius: 10px; margin-bottom: 10px; padding: 6px 10px; }
            .rt td { display: flex; justify-content: space-between; align-items: center; gap: 12px; border: 0; padding: 7px 2px; text-align: right; }
            .rt td::before { content: attr(data-label); font-weight: 700; font-size: .68rem; text-transform: uppercase; color: var(--muted); text-align: left; }
            .rt td[colspan]::before { content: none; } .rt td[colspan] { display: block; text-align: center; }
        }
        
        @media print {
            .sidebar, .mbar, .scrim, .noprint { display: none !important; }
            body { padding: 0 !important; background: #fff; }
            main { margin: 0; padding: 0; max-width: none; }
            .card, .tile { box-shadow: none; border: 1px solid #cbd5e1; }
            .printonly { display: block; }
            th { background: #0f172a !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tr { break-inside: avoid; }
            @page { size: A4 landscape; margin: 10mm; }
            .rt thead { display: table-header-group; }
            .rt tr { display: table-row; }
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