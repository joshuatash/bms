<?php 
require __DIR__ . '/config.php'; 
require_admin(); 
$active = 'dashboard'; 
$pdo = db(); 

// Fetch Admin Name from Database
$adminId = current_user()['user_id'] ?? current_user()['id'];
$stmtName = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
$stmtName->execute([$adminId]);
$adminName = $stmtName->fetchColumn() ?: current_user()['name'];

// Helper function
$money = fn($n) => number_format((float)$n);

// Stock Stats
$p = $pdo->query("SELECT COUNT(*) total, COALESCE(SUM(bottles_available > 0 AND bottles_available <= low_stock_threshold),0) low, COALESCE(SUM(bottles_available = 0),0) out_of_stock FROM products WHERE is_active = 1")->fetch();

// Sales Stats
$salesToday = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales_record WHERE DATE(sale_date) = CURDATE()")->fetchColumn();
$salesMonth = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales_record WHERE sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();

// Profit Stats (Computed safely using product buying prices directly)
$profitToday = (float)$pdo->query("SELECT COALESCE(SUM(i.line_total - (COALESCE(p.buying_price, 0) * i.qty_bottles)), 0) FROM sales_record_items i JOIN sales_record s ON s.sale_id = i.sale_id LEFT JOIN products p ON p.product_id = i.product_id WHERE DATE(s.sale_date) = CURDATE()")->fetchColumn();
$profitMonth = (float)$pdo->query("SELECT COALESCE(SUM(i.line_total - (COALESCE(p.buying_price, 0) * i.qty_bottles)), 0) FROM sales_record_items i JOIN sales_record s ON s.sale_id = i.sale_id LEFT JOIN products p ON p.product_id = i.product_id WHERE s.sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();

$users = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();

// Expenses Stats
$expensesToday = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date = CURDATE()")->fetchColumn();
$expensesMonth = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <style>
        :root { --navy:#0f172a; --slate:#334155; --muted:#64748b; --line:#e2e8f0; --amber:#f59e0b; --green:#16a34a; --red:#dc2626; --blue:#2563eb; --purple:#8b5cf6; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); color: var(--navy); }
        main { max-width: 1100px; margin: 26px auto; padding: 0 18px; }
        h2 { margin: 0 0 4px; } .sub { color: var(--muted); margin: 0 0 20px; }
        .group { font-size: .75rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .8px; margin: 22px 0 10px; }
        
        .tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
        .tile { background: #fff; border-radius: 12px; padding: 18px; border-left: 5px solid var(--slate); box-shadow: 0 1px 3px rgba(0,0,0,0.06); position: relative; }
        .tile b { display: block; font-size: 1.7rem; } .tile small { color: var(--muted); }
        
        .tile.low { border-color: #ea580c; } .tile.out { border-color: var(--red); } 
        .tile.spend { border-color: #f59e0b; } .tile.money { border-color: #16a34a; }
        .tile.profit { border-color: #2563eb; }
        
        .tile.link { display: block; text-decoration: none; color: inherit; cursor: pointer; transition: .15s; }
        .tile.link:hover { transform: translateY(-3px); box-shadow: 0 8px 18px rgba(0,0,0,.1); }
        .tile .more { display: block; margin-top: 8px; font-size: .78rem; font-weight: 700; color: #16a34a; }
        .tile.profit .more { color: #2563eb; }
        
        .tile.soon::after { content: 'Soon'; position: absolute; top: 10px; right: 12px; font-size: .62rem; background: #f1f5f9; color: #64748b; padding: 2px 8px; border-radius: 999px; }

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
    <h2>Welcome, <?= e($adminName) ?></h2>
    <p class="sub">Main dashboard</p>

    <div class="group">Money &amp; Profit (<?= CURRENCY ?>) - Today</div>
    <section class="tiles">
        <a class="tile money link" href="sales_report.php?period=today">
            <small>Total Sales Today</small><b><?= $money($salesToday) ?></b><span class="more">View details &rarr;</span>
        </a>
        <a class="tile profit link" href="sales_report.php?period=today">
            <small>Gross Profit Today (Sales Only)</small><b style="color:#2563eb;"><?= $money($profitToday) ?></b><span class="more">View details &rarr;</span>
        </a>
        <a class="tile spend link" href="expenses.php">
            <small>Expenses Today</small><b><?= $money($expensesToday) ?></b><span class="more">View details &rarr;</span>
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
            <small>Gross Profit This Month (Sales Only)</small><b style="color:#2563eb;"><?= $money($profitMonth) ?></b><span class="more">View details &rarr;</span>
        </a>
        <a class="tile spend link" href="expenses.php">
            <small>Expenses This Month</small><b><?= $money($expensesMonth) ?></b><span class="more">View details &rarr;</span>
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
        <a class="tile link" href="products.php">
            <small>Products</small><b><?= (int)$p['total'] ?></b><span class="more">View details &rarr;</span>
        </a>
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

<script>
    document.querySelectorAll('.local-time').forEach(el => {
        const d = new Date(el.dataset.time.replace(' ', 'T'));
        el.textContent = d.toLocaleString();
    });
</script>
</body>
</html>