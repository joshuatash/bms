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

// --- TIME SHIFT LOGIC (Resets at 7:00 AM) ---
// We subtract 7 hours from the current server time.
// Any transaction after midnight but before 7am is treated as yesterday.
$shift = "DATE_SUB(NOW(), INTERVAL 7 HOUR)";
$shiftToday = "DATE(DATE_SUB(NOW(), INTERVAL 7 HOUR))";
$shiftMonthStart = "DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 7 HOUR), '%Y-%m-01')";

// Stock Stats
$p = $pdo->query("SELECT COUNT(*) total, COALESCE(SUM(bottles_available > 0 AND bottles_available <= low_stock_threshold),0) low, COALESCE(SUM(bottles_available = 0),0) out_of_stock FROM products WHERE is_active = 1")->fetch();
// Overall Stock Balance — total retail value of available stock
$stockBalance = (float)$pdo->query("SELECT COALESCE(SUM(bottles_available * price_per_bottle), 0) FROM products WHERE is_active = 1 AND bottles_available > 0")->fetchColumn();
// Expected Profit — total potential profit from available stock (retail value − cost value)
$expectedProfit = (float)$pdo->query("SELECT COALESCE(SUM(bottles_available * (price_per_bottle - buying_price)), 0) FROM products WHERE is_active = 1 AND bottles_available > 0")->fetchColumn();

// Sales Stats (Shifted)
$salesToday = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales_record WHERE DATE(sale_date) = $shiftToday")->fetchColumn();
$salesMonth = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales_record WHERE sale_date >= $shiftMonthStart")->fetchColumn();

// Profit Stats (Shifted)
$profitToday = (float)$pdo->query("SELECT COALESCE(SUM(i.line_total - (COALESCE(p.buying_price, 0) * i.qty_bottles)), 0) FROM sales_record_items i JOIN sales_record s ON s.sale_id = i.sale_id LEFT JOIN products p ON p.product_id = i.product_id WHERE DATE(s.sale_date) = $shiftToday")->fetchColumn();
$profitMonth = (float)$pdo->query("SELECT COALESCE(SUM(i.line_total - (COALESCE(p.buying_price, 0) * i.qty_bottles)), 0) FROM sales_record_items i JOIN sales_record s ON s.sale_id = i.sale_id LEFT JOIN products p ON p.product_id = i.product_id WHERE s.sale_date >= $shiftMonthStart")->fetchColumn();

$users = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();

// Expenses Stats (Shifted)
$expensesToday = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE(expense_date) = $shiftToday")->fetchColumn();
$expensesMonth = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date >= $shiftMonthStart")->fetchColumn();

// Net Profit Calculation (Gross Profit - Expenses)
$netProfitToday = $profitToday - $expensesToday;
$netProfitMonth = $profitMonth - $expensesMonth;

// Rooms Revenue Stats (Shifted)
$roomsToday = $roomsMonth = 0.0; 
try {
    $roomsToday = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM room_payments WHERE DATE(paid_at) = $shiftToday")->fetchColumn();
    $roomsMonth = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM room_payments WHERE paid_at >= $shiftMonthStart")->fetchColumn();
} catch (PDOException $e) {}

// PA System Stats (Shifted)
$paToday = $paMonth = 0.0;
try {
    $paToday = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM pa_collections WHERE DATE(created_at) = $shiftToday")->fetchColumn();
    $paMonth = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM pa_collections WHERE created_at >= $shiftMonthStart")->fetchColumn();
} catch (PDOException $e) {}

// Ground Hire Stats (Shifted)
$ghToday = $ghMonth = 0.0;
try {
    $ghToday = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM ground_hire WHERE DATE(created_at) = $shiftToday")->fetchColumn();
    $ghMonth = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM ground_hire WHERE created_at >= $shiftMonthStart")->fetchColumn();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
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
        
        /* NEW: Outstanding Subtitle Style */
        .sub-outstanding {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 6px 0 24px;
            padding: 8px 16px;
            background: linear-gradient(135deg, rgba(245,158,11,.15) 0%, rgba(245,158,11,.05) 100%);
            border: 1px solid rgba(245,158,11,.3);
            border-left: 4px solid var(--amber);
            border-radius: 8px;
            color: var(--amber);
            font-size: .92rem;
            font-weight: 700;
            letter-spacing: .3px;
            text-shadow: 0 0 12px rgba(245,158,11,.4);
            box-shadow: 0 4px 12px rgba(0,0,0,.2);
            animation: pulse-border 2s infinite alternate;
        }
        
        @keyframes pulse-border {
            0% { border-color: rgba(245,158,11,.3); box-shadow: 0 4px 12px rgba(0,0,0,.2); }
            100% { border-color: rgba(245,158,11,.6); box-shadow: 0 4px 15px rgba(245,158,11,.15); }
        }

        .sub-outstanding svg { width: 18px; height: 18px; flex-shrink: 0; }

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
        
        /* Stock Balance tile - outstanding glowing green value */
        .tile.stock-balance { 
            border-left-color: #22c55e; 
            background: linear-gradient(135deg, var(--surface) 0%, rgba(34,197,94,.08) 100%);
            grid-column: span 1;
        }
        .tile.stock-balance b {
            color: #4ade80;
            font-size: 1.9rem;
            text-shadow: 0 0 20px rgba(74,222,128,.5);
            letter-spacing: -.5px;
        }

        /* Expected Profit tile - outstanding glowing blue value */
        .tile.expected-profit {
            border-left-color: #3b82f6;
            background: linear-gradient(135deg, var(--surface) 0%, rgba(59,130,246,.08) 100%);
            grid-column: span 1;
        }
        .tile.expected-profit b {
            color: #60a5fa;
            font-size: 1.9rem;
            text-shadow: 0 0 20px rgba(96,165,250,.5);
            letter-spacing: -.5px;
        }
        
        @media(max-width: 640px) {
            .tile.stock-balance, .tile.expected-profit { grid-column: span 1; }
            .tile.stock-balance b, .tile.expected-profit b { font-size: 1.5rem; }
            .sub-outstanding { width: 100%; justify-content: center; }
        }
        
        .tile.link { display: block; text-decoration: none; color: inherit; cursor: pointer; transition: .15s; }
        .tile.link:hover { transform: translateY(-3px); box-shadow: 0 8px 18px rgba(0,0,0,.5); }
        .tile .more { display: block; margin-top: 8px; font-size: .78rem; font-weight: 700; color: #4ade80; }
        .tile.profit .more, .tile.expected-profit .more { color: #60a5fa; }
        .tile.net .more { color: #4ade80; }
        
        .tile.soon::after { content: 'Soon'; position: absolute; top: 10px; right: 12px; font-size: .62rem; background: var(--surface-3); color: var(--text-dim); padding: 2px 8px; border-radius: 999px; }
        
        /* Public footer for Safe Browsing compliance */
        .public-footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid var(--border); text-align: center; font-size: 0.8rem; color: var(--text-mute); padding-bottom: 20px; }
        .public-footer p { margin: 4px 0; }
        .public-footer a { color: var(--text-dim); text-decoration: none; transition: 0.2s; }
        .public-footer a:hover { color: var(--amber); }
        
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
    
    <!-- Outstanding Subtitle -->
    <div class="sub-outstanding">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
        Business Day resets daily at 7:00 AM
    </div>

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
        <a class="tile stock-balance link" href="products.php">
            <small>Overall Stock Balance (<?= CURRENCY ?>)</small>
            <b><?= $money($stockBalance) ?></b>
            <span class="more">View inventory &rarr;</span>
        </a>
        <a class="tile expected-profit link" href="products.php">
            <small>Expected Profit on Stock (<?= CURRENCY ?>)</small>
            <b><?= $money($expectedProfit) ?></b>
            <span class="more">View inventory &rarr;</span>
        </a>
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
    
    <footer class="public-footer">
        <p><strong>Big Wallet Lounge Staff Portal</strong></p>
        <p>Private business management system. Authorized personnel only.</p>
        <p>
            <a href="index.html">Home</a> | 
            <a href="privacy.php">Privacy Policy</a> | 
            <a href="terms.php">Terms of Service</a> | 
            <a href="manual.php">Help Center</a>
        </p>
    </footer>
</main>
<script>
    document.querySelectorAll('.local-time').forEach(el => {
        const d = new Date(el.dataset.time.replace(' ', 'T'));
        el.textContent = d.toLocaleString();
    });
</script>
</body>
</html>