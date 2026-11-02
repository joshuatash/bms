<?php 
require __DIR__ . '/config.php'; 
require_manager(); // Uses central security function
$active = 'dashboard'; 
$pdo = db(); 
// Fetch Manager Name
$managerId = current_user()['user_id'] ?? current_user()['id'];
$stmtName = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
$stmtName->execute([$managerId]);
$managerName = $stmtName->fetchColumn() ?: current_user()['name'];
// Get Stats
$p = $pdo->query("SELECT COUNT(*) total, COALESCE(SUM(bottles_available > 0 AND bottles_available <= low_stock_threshold),0) low, COALESCE(SUM(bottles_available = 0),0) out_of_stock FROM products WHERE is_active = 1")->fetch();
$salesToday = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales_record WHERE DATE(sale_date) = CURDATE()")->fetchColumn();
$salesMonth = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales_record WHERE sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
// Profit Stats (Computed safely using product buying prices directly)
$profitToday = (float)$pdo->query("SELECT COALESCE(SUM(i.line_total - (COALESCE(p.buying_price, 0) * i.qty_bottles)), 0) FROM sales_record_items i JOIN sales_record s ON s.sale_id = i.sale_id LEFT JOIN products p ON p.product_id = i.product_id WHERE DATE(s.sale_date) = CURDATE()")->fetchColumn();
$profitMonth = (float)$pdo->query("SELECT COALESCE(SUM(i.line_total - (COALESCE(p.buying_price, 0) * i.qty_bottles)), 0) FROM sales_record_items i JOIN sales_record s ON s.sale_id = i.sale_id LEFT JOIN products p ON p.product_id = i.product_id WHERE s.sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
$users = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$expensesToday = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date = CURDATE()")->fetchColumn();
$expensesMonth = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();

// Net Profit Calculation (Gross Profit - Expenses)
$netProfitToday = $profitToday - $expensesToday;
$netProfitMonth = $profitMonth - $expensesMonth;

// Rooms Revenue Stats
$roomsToday = $roomsMonth = 0.0; 
try {
    $roomsToday = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM room_payments WHERE paid_at >= CURDATE()")->fetchColumn();
    $roomsMonth = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM room_payments WHERE paid_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
} catch (PDOException $e) {}
// PA System Stats
$paToday = $paMonth = 0.0;
try {
    $paToday = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM pa_collections WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $paMonth = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM pa_collections WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
} catch (PDOException $e) {}
// Ground Hire Stats
$ghToday = $ghMonth = 0.0;
try {
    $ghToday = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM ground_hire WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $ghMonth = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM ground_hire WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
} catch (PDOException $e) {}
$money = fn($n) => number_format((float)$n);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manager Dashboard</title>
    <style>
        :root{
            --navy:#0f172a;--slate:#334155;--muted:#64748b;--line:#e2e8f0;--amber:#f59e0b;--green:#16a34a;--red:#dc2626;--blue:#2563eb;--purple:#8b5cf6;
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
        main { max-width: 1100px; margin: 26px auto; padding: 0 18px; }
        h2 { margin: 0 0 4px; color: var(--text); }
        .sub { color: var(--text-dim); margin: 0 0 20px; }
        .group { font-size: .75rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase; letter-spacing: .8px; margin: 22px 0 10px; }
        
        .tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
        .tile { background: var(--surface); border-radius: 12px; padding: 18px; border-left: 5px solid var(--text-dim); border-top: 1px solid var(--border); border-right: 1px solid var(--border); border-bottom: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,.3); position: relative; }
        .tile b { display: block; font-size: 1.7rem; color: var(--text); }
        .tile small { color: var(--text-dim); }
        
        .tile.low { border-left-color: #ea580c; }
        .tile.out { border-left-color: var(--red); }
        .tile.spend { border-left-color: #f59e0b; }
        .tile.money { border-left-color: #16a34a; }
        .tile.profit { border-left-color: #2563eb; }
        .tile.net { border-left-color: #16a34a; }
        
        .tile.link { display: block; text-decoration: none; color: inherit; cursor: pointer; transition: .15s; }
        .tile.link:hover { transform: translateY(-3px); box-shadow: 0 8px 18px rgba(0,0,0,.5); }
        .tile .more { display: block; margin-top: 8px; font-size: .78rem; font-weight: 700; color: #4ade80; }
        .tile.profit .more { color: #60a5fa; }
        .tile.net .more { color: #4ade80; }
        @media(max-width: 640px) {
            main { margin: 14px auto; padding: 0 12px; } 
            h2 { font-size: 1.2rem; }
            .tiles { grid-template-columns: repeat(2, 1fr); gap: 10px; } 
            .tile { padding: 14px; } 
            .tile b { font-size: 1.25rem; word-break: break-word; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
    <h2>Welcome, <?= e($managerName) ?></h2>
    <p class="sub">Manager Dashboard</p>
    <div class="group">Money &amp; Profit (<?= CURRENCY ?>) - Today</div>
    <section class="tiles">
        <a class="tile money link" href="sales_report.php?period=today">
            <small>Total Sales Today</small><b><?= $money($salesToday) ?></b><span class="more">View details &rarr;</span>
        </a>
        <a class="tile profit link" href="sales_report.php?period=today">
            <small>Gross Profit Today</small><b style="color:#60a5fa;"><?= $money($profitToday) ?></b><span class="more">View details &rarr;</span>
        </a>
        <a class="tile spend link" href="expenses.php">
            <small>Expenses Today</small><b><?= $money($expensesToday) ?></b><span class="more">View details &rarr;</span>
        </a>
        <a class="tile net link" href="sales_report.php?period=today">
            <small>Net Profit Today</small>
            <b style="color: <?= $netProfitToday < 0 ? '#f87171' : '#4ade80' ?>; font-weight: <?= $netProfitToday < 0 ? '800' : '700' ?>;"><?= $money($netProfitToday) ?></b>
            <span class="more">View details &rarr;</span>
        </a>
        <a class="tile money link" href="room_collection.php?period=today">
            <small>Rooms Collection Today</small><b><?= $money($roomsToday) ?></b><span class="more">View details &rarr;</span>
        </a>
        <a class="tile money link" href="pa_system.php">
            <small>PA System Today</small><b><?= $money($paToday) ?></b><span class="more">View details &rarr;</span>
        </a>
        <a class="tile money link" href="ground_hire.php">
            <small>Ground Hire Today</small><b><?= $money($ghToday) ?></b><span class="more">View details &rarr;</span>
        </a>
    </section>
    <div class="group">Money &amp; Profit (<?= CURRENCY ?>) - This Month</div>
    <section class="tiles">
        <a class="tile money link" href="sales_report.php?period=month">
            <small>Total Sales This Month</small><b><?= $money($salesMonth) ?></b><span class="more">View details &rarr;</span>
        </a>
        <a class="tile profit link" href="sales_report.php?period=month">
            <small>Gross Profit This Month</small><b style="color:#60a5fa;"><?= $money($profitMonth) ?></b><span class="more">View details &rarr;</span>
        </a>
        <a class="tile spend link" href="expenses.php">
            <small>Expenses This Month</small><b><?= $money($expensesMonth) ?></b><span class="more">View details &rarr;</span>
        </a>
        <a class="tile net link" href="sales_report.php?period=month">
            <small>Net Profit This Month</small>
            <b style="color: <?= $netProfitMonth < 0 ? '#f87171' : '#4ade80' ?>; font-weight: <?= $netProfitMonth < 0 ? '800' : '700' ?>;"><?= $money($netProfitMonth) ?></b>
            <span class="more">View details &rarr;</span>
        </a>
        <a class="tile money link" href="room_collection.php?period=month">
            <small>Rooms Collection This Month</small><b><?= $money($roomsMonth) ?></b><span class="more">View details &rarr;</span>
        </a>
        <a class="tile money link" href="pa_system.php">
            <small>PA System This Month</small><b><?= $money($paMonth) ?></b><span class="more">View details &rarr;</span>
        </a>
        <a class="tile money link" href="ground_hire.php">
            <small>Ground Hire This Month</small><b><?= $money($ghMonth) ?></b><span class="more">View details &rarr;</span>
        </a>
    </section>
    <div class="group">Stock &amp; Accounts</div>
    <section class="tiles">
        <div class="tile">
            <small>Total Products</small><b><?= (int)$p['total'] ?></b>
        </div>
        <a class="tile low link" href="products.php?status=low">
            <small>Low Stock</small><b><?= (int)$p['low'] ?></b><span class="more">View items &rarr;</span>
        </a>
        <a class="tile out link" href="products.php?status=out">
            <small>Out of Stock</small><b><?= (int)$p['out_of_stock'] ?></b><span class="more">View items &rarr;</span>
        </a>
        <div class="tile">
            <small>Active Accounts</small><b><?= $users ?></b>
        </div>
    </section>
</main>
</body>
</html>