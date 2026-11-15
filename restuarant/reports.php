<?php
// bms/restuarant/reports.php
require_once __DIR__ . '/../config.php';
require_login();

$role = strtolower(current_user()['role'] ?? 'cashier');
$isCashier = ($role === 'cashier');

// If a cashier somehow lands here, send them to POS
if ($isCashier) {
    header('Location: pos.php');
    exit;
}

// Automatically detect the current page to set the 'active' class
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Revenue Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #1A1C1E; --sidebar: #151719; --surface: #232629; --surface-2: #2E3236;
            --border: #3A3F45; --text: #E2E8F0; --text-muted: #94A3B8;
            --primary: #D4A373; --primary-hover: #E2B587;
            --success: #4ADE80; --warning: #FBBF24; --danger: #F87171; --blue: #60A5FA;
            --active-green: #4ADE80;
        }
        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg); color: var(--text); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); -webkit-tap-highlight-color: transparent; }
        input, select, textarea, button { font-size: 16px; }
        .app-header { background-color: var(--sidebar); border-bottom: 1px solid var(--border); padding: 0.75rem 1.75rem; position: sticky; top: 0; z-index: 1020; }
        
        .brand-logo { font-weight: 800; font-size: 0.95rem; letter-spacing: 0.05em; color: var(--text); text-decoration: none; display: flex; align-items: center; gap: 0.6rem; }
        .brand-logo img { width: 36px; height: 36px; border-radius: 50%; background: #fff; object-fit: cover; }
        .brand-logo span { color: var(--primary); font-weight: 800; }

        .desktop-nav { display: flex; align-items: center; gap: 0.35rem; list-style: none; margin: 0; padding: 0; }
        .desktop-nav .nav-link { color: var(--text-muted); font-weight: 600; font-size: 0.83rem; padding: 0.45rem 0.85rem; border-radius: 6px; text-decoration: none; }
        .desktop-nav .nav-link:hover { color: var(--text); background-color: var(--surface); }
        .desktop-nav .nav-link.active { color: var(--sidebar); background-color: var(--active-green); }
        .desktop-nav .nav-link.bar-link { color: var(--primary); border: 1px solid var(--primary); }
        .desktop-nav .nav-link.bar-link:hover { background-color: var(--primary); color: var(--sidebar); }

        .main-container { max-width: 1400px; margin: 0 auto; padding: 2rem 1.75rem; }

        /* ATTENTION GRABBING SUBTITLE */
        .sub-outstanding {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 6px 0 0;
            padding: 8px 16px;
            background: linear-gradient(135deg, rgba(245,158,11,.15) 0%, rgba(245,158,11,.05) 100%);
            border: 1px solid rgba(245,158,11,.3);
            border-left: 4px solid var(--warning);
            border-radius: 8px;
            color: var(--warning);
            font-size: .85rem;
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
        .sub-outstanding svg { width: 16px; height: 16px; flex-shrink: 0; }

        .period-tabs { display: flex; gap: 6px; background: var(--surface); padding: 6px; border-radius: 10px; border: 1px solid var(--border); }
        .period-tab { flex: 1; background: transparent; color: var(--text-muted); border: 0; padding: 10px 14px; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; }
        .period-tab.active { background: var(--active-green); color: var(--sidebar); }

        .metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.75rem; }
        .metric-card { background-color: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.15rem 1.25rem; position: relative; overflow: hidden; cursor: pointer; transition: transform .15s, border-color .15s; }
        .metric-card:hover { transform: translateY(-2px); border-color: var(--primary); }
        .metric-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; }
        .metric-card.m-green::before { background-color: var(--success); }
        .metric-card.m-red::before { background-color: var(--danger); }
        .metric-card.m-orange::before { background-color: var(--warning); }
        .metric-card.m-blue::before { background-color: var(--blue); }
        .metric-card.m-blue { cursor: default; }
        .metric-card.m-blue:hover { transform: none; border-color: var(--border); }
        .metric-label { color: var(--text-muted); font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.4rem; display: block; }
        .metric-value { font-size: 1.65rem; font-weight: 800; letter-spacing: -0.02em; line-height: 1.1; margin: 0; }
        .metric-sub { font-size: 0.72rem; color: var(--text-muted); margin-top: 4px; }
        .metric-view-link { font-size: 0.7rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 6px; display: inline-block; }

        .data-panel { background-color: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; margin-bottom: 1rem; }
        .data-panel-header { padding: 0.85rem 1.25rem; background-color: var(--sidebar); border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .data-panel-title { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); margin: 0; }
        .table-custom { width: 100%; margin-bottom: 0; color: var(--text); border-collapse: collapse; }
        .table-custom th { background-color: var(--surface-2); color: var(--text-muted); font-weight: 700; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; padding: 0.7rem 1rem; border-bottom: 1px solid var(--border); }
        .table-custom td { padding: 0.75rem 1rem; vertical-align: middle; border-bottom: 1px solid var(--border); font-size: 0.85rem; }
        .btn-brand { background-color: var(--primary); color: var(--sidebar); font-weight: 700; font-size: 0.85rem; padding: 0.55rem 1.25rem; border-radius: 8px; border: none; }
        .btn-brand:hover { background-color: var(--primary-hover); color: var(--sidebar); }
        .btn-action { background-color: var(--surface-2); color: var(--text); border: 1px solid var(--border); font-weight: 600; font-size: 0.78rem; padding: 0.4rem 0.9rem; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;}
        .btn-action:hover { background-color: var(--border); color: var(--text); }
        .net-positive { color: var(--success); }
        .net-negative { color: var(--danger); }
        .empty-row { text-align: center; padding: 2.5rem 1rem; color: var(--text-muted); font-size: 0.85rem; }

        /* Details panel (bottom sheet) */
        .detail-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.55); z-index: 1060; display: none; align-items: flex-end; }
        .detail-overlay.open { display: flex; animation: fadein .15s ease; }
        .detail-panel { background: var(--surface); width: 100%; max-width: 900px; margin: 0 auto; border-radius: 20px 20px 0 0; padding: 12px 16px calc(20px + env(safe-area-inset-bottom, 0px)); max-height: 90vh; overflow-y: auto; animation: slideup .25s ease; }
        .sheet-handle { width: 40px; height: 4px; border-radius: 2px; background: var(--border); margin: 0 auto 12px; }
        .detail-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .detail-title { font-size: 16px; font-weight: 800; margin: 0; }
        .detail-sub { color: var(--text-muted); font-size: 12px; margin-top: 2px; }
        .detail-close { background: var(--surface-2); border: 1px solid var(--border); color: var(--text); width: 36px; height: 36px; border-radius: 8px; }
        .detail-total-line { padding: 12px 14px; border-radius: 10px; background: var(--surface-2); display: flex; justify-content: space-between; align-items: center; font-weight: 700; margin-bottom: 14px; }
        .detail-total-line strong { font-size: 18px; color: var(--primary); }
        .order-item-list { font-size: 12px; color: var(--text-muted); line-height: 1.5; }

        @keyframes fadein { from { opacity: 0 } }
        @keyframes slideup { from { transform: translateY(100%) } }

        .mobile-bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background-color: var(--sidebar); border-top: 1px solid var(--border); display: none; justify-content: space-around; padding: 0.5rem 0 calc(0.5rem + env(safe-area-inset-bottom, 0px)); z-index: 1050; }
        .mobile-nav-link { color: var(--text-muted); text-align: center; text-decoration: none; font-size: 0.68rem; font-weight: 600; flex-grow: 1; padding: 0.3rem 0; position: relative; -webkit-tap-highlight-color: transparent; }
        .mobile-nav-link i { display: block; font-size: 1.1rem; margin-bottom: 0.2rem; }
        .mobile-nav-link.active { color: var(--active-green); }
        .mobile-nav-link.active::before { content: ''; position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 40px; height: 3px; border-radius: 0 0 3px 3px; background: var(--active-green); }
        .mobile-nav-link.bar-link { color: var(--primary); }

        .toast-stack { position: fixed; top: calc(env(safe-area-inset-top, 0px) + 12px); left: 12px; right: 12px; display: flex; flex-direction: column; gap: 8px; z-index: 9999; pointer-events: none; }
        .toast-item { pointer-events: auto; display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; border-radius: 12px; color: #fff; font-size: 14px; font-weight: 500; box-shadow: 0 8px 24px rgba(0,0,0,0.35); transform: translateY(-20px); opacity: 0; animation: toast-in .25s ease forwards; }
        .toast-item.hide { animation: toast-out .2s ease forwards; }
        .toast-item.success { background: #16a34a; }
        .toast-item.error { background: #dc2626; }
        .toast-item.info { background: #2563eb; }
        .toast-item.warning { background: #d97706; }
        .toast-item .toast-close { margin-left: auto; background: none; border: 0; color: #fff; opacity: .7; cursor: pointer; padding: 0; font-size: 16px; }
        @keyframes toast-in { to { transform: translateY(0); opacity: 1; } }
        @keyframes toast-out { to { transform: translateY(-20px); opacity: 0; } }

        @media (min-width: 992px) {
            .mobile-bottom-nav { display: none !important; }
            body { padding-bottom: 0; }
        }
        @media (max-width: 991.98px) {
            .app-header { display: none; }
            .mobile-bottom-nav { display: flex; }
            body { padding-bottom: calc(75px + env(safe-area-inset-bottom, 0px)); }
            .main-container { padding: 1rem; }
            .metrics-grid { grid-template-columns: 1fr 1fr; }
            .metric-value { font-size: 1.25rem; }
        }
    </style>
</head>
<body>
    <header class="app-header">
        <div class="d-flex justify-content-between align-items-center">
            <a class="brand-logo" href="index.php">
                <img src="../logo.png" alt="Logo">
                <div>Big Wallet Lounge <span>Restaurant</span></div>
            </a>
            <ul class="desktop-nav">
                <?php if (!$isCashier): ?>
                    <li><a class="nav-link <?= ($current_page === 'index.php') ? 'active' : '' ?>" href="index.php">Dashboard</a></li>
                <?php endif; ?>
                
                <li><a class="nav-link <?= ($current_page === 'pos.php') ? 'active' : '' ?>" href="pos.php">Counter POS</a></li>
                
                <?php if (!$isCashier): ?>
                    <li><a class="nav-link <?= ($current_page === 'kitchen.php') ? 'active' : '' ?>" href="kitchen.php">Kitchen KDS</a></li>
                    <li><a class="nav-link <?= ($current_page === 'inventory.php') ? 'active' : '' ?>" href="inventory.php">Inventory</a></li>
                    <li><a class="nav-link <?= ($current_page === 'admin.php') ? 'active' : '' ?>" href="admin.php">Menu &amp; Prices</a></li>
                    <li><a class="nav-link <?= ($current_page === 'reports.php') ? 'active' : '' ?>" href="reports.php">Revenue</a></li>
                <?php endif; ?>
                
                <li><a class="nav-link <?= ($current_page === 'waitress.php') ? 'active' : '' ?>" href="waitress.php">Waitress</a></li>
                
                <li class="ms-2"><a class="nav-link bar-link" href="../dashboard.php">Bar Side &rarr;</a></li>
            </ul>
        </div>
    </header>

    <main class="main-container">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1" style="color: var(--text);">Revenue Dashboard</h1>
                
                <!-- NEW ATTENTION GRABBING BADGE -->
                <div class="sub-outstanding">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Daily totals reset at 07:00. Tap any card to see where the money came from.
                </div>
                
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="expenses.php" class="btn-action"><i class="fa-solid fa-plus me-1"></i> Record Expense / Purchase</a>
                <button class="btn-brand" onclick="loadReport()"><i class="fa-solid fa-rotate me-1"></i> Refresh</button>
            </div>
        </div>

        <div class="period-tabs mb-4 mt-2">
            <button class="period-tab active" data-period="today" onclick="setPeriod('today', this)">Today</button>
            <button class="period-tab" data-period="week" onclick="setPeriod('week', this)">This Week</button>
            <button class="period-tab" data-period="month" onclick="setPeriod('month', this)">This Month</button>
        </div>

        <div class="metrics-grid">
            <div class="metric-card m-green" onclick="openDetails('sales')">
                <span class="metric-label">Sales</span>
                <p class="metric-value net-positive" id="stat-revenue">UGX 0</p>
                <div class="metric-sub" id="stat-orders">0 paid orders</div>
                <span class="metric-view-link">View details →</span>
            </div>
            <div class="metric-card m-orange" onclick="openDetails('stock')">
                <span class="metric-label">Raw Materials &amp; Spices</span>
                <p class="metric-value" id="stat-stock" style="color: var(--warning);">UGX 0</p>
                <div class="metric-sub">Stock purchases</div>
                <span class="metric-view-link">View details →</span>
            </div>
            <div class="metric-card m-red" onclick="openDetails('expenses')">
                <span class="metric-label">Other Expenses</span>
                <p class="metric-value" style="color: var(--danger);" id="stat-expenses">UGX 0</p>
                <div class="metric-sub">Rent, salaries, utilities</div>
                <span class="metric-view-link">View details →</span>
            </div>
            <div class="metric-card m-blue">
                <span class="metric-label">Net</span>
                <p class="metric-value" id="stat-net" style="color: var(--blue);">UGX 0</p>
                <div class="metric-sub" id="stat-net-note">Sales − costs</div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="data-panel">
                    <div class="data-panel-header">
                        <h2 class="data-panel-title">Where Sales Came From</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead><tr><th>Order Type</th><th>Orders</th><th class="text-end">Revenue</th></tr></thead>
                            <tbody id="by-type-tbody">
                                <tr><td colspan="3" class="empty-row">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="data-panel">
                    <div class="data-panel-header">
                        <h2 class="data-panel-title">Best Selling Items</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead><tr><th>Item</th><th>Qty</th><th class="text-end">Revenue</th></tr></thead>
                            <tbody id="best-sellers-tbody">
                                <tr><td colspan="3" class="empty-row">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="data-panel">
                    <div class="data-panel-header">
                        <h2 class="data-panel-title">Stock Spend by Category</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead><tr><th>Category</th><th class="text-end">Spent</th></tr></thead>
                            <tbody id="stock-cat-tbody">
                                <tr><td colspan="2" class="empty-row">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="data-panel">
                    <div class="data-panel-header">
                        <h2 class="data-panel-title">Expenses by Category</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead><tr><th>Category</th><th class="text-end">Spent</th></tr></thead>
                            <tbody id="expense-cat-tbody">
                                <tr><td colspan="2" class="empty-row">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Details panel -->
    <div class="detail-overlay" id="detail-overlay" onclick="closeDetails(event)">
        <div class="detail-panel" onclick="event.stopPropagation()">
            <div class="sheet-handle"></div>
            <div class="detail-head">
                <div>
                    <h3 class="detail-title" id="detail-title">Details</h3>
                    <div class="detail-sub" id="detail-sub"></div>
                </div>
                <button class="detail-close" onclick="closeDetails()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="detail-total-line">
                <span id="detail-total-label">Total</span>
                <strong id="detail-total">UGX 0</strong>
            </div>
            <div class="table-responsive" style="border: 1px solid var(--border); border-radius: 10px;">
                <table class="table-custom" style="margin: 0;">
                    <thead id="detail-thead"></thead>
                    <tbody id="detail-tbody">
                        <tr><td class="empty-row">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <nav class="mobile-bottom-nav">
        <?php if (!$isCashier): ?>
            <a href="index.php" class="mobile-nav-link <?= ($current_page === 'index.php') ? 'active' : '' ?>"><i class="fa-solid fa-chart-line"></i>Dashboard</a>
        <?php endif; ?>
        
        <a href="pos.php" class="mobile-nav-link <?= ($current_page === 'pos.php') ? 'active' : '' ?>"><i class="fa-solid fa-cash-register"></i>POS</a>
        
        <?php if (!$isCashier): ?>
            <a href="kitchen.php" class="mobile-nav-link <?= ($current_page === 'kitchen.php') ? 'active' : '' ?>"><i class="fa-solid fa-fire-burner"></i>Kitchen</a>
            <a href="inventory.php" class="mobile-nav-link <?= ($current_page === 'inventory.php') ? 'active' : '' ?>"><i class="fa-solid fa-boxes-stacked"></i>Stock</a>
            <a href="admin.php" class="mobile-nav-link <?= ($current_page === 'admin.php') ? 'active' : '' ?>"><i class="fa-solid fa-gear"></i>Menu</a>
            <a href="reports.php" class="mobile-nav-link <?= ($current_page === 'reports.php') ? 'active' : '' ?>"><i class="fa-solid fa-coins"></i>Revenue</a>
        <?php endif; ?>
        
        <a href="waitress.php" class="mobile-nav-link <?= ($current_page === 'waitress.php') ? 'active' : '' ?>"><i class="fa-solid fa-bell-concierge"></i>Waitress</a>
        
        <a href="../dashboard.php" class="mobile-nav-link bar-link"><i class="fa-solid fa-arrow-left"></i>Bar Side</a>
    </nav>

    <div id="toast-stack" class="toast-stack" aria-live="polite"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function escapeHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
        function ugx(n) { return 'UGX ' + Number(n || 0).toLocaleString(); }

        function toast(message, type, duration) {
            type = type || 'info';
            duration = (duration === undefined) ? 3000 : duration;
            var stack = document.getElementById('toast-stack');
            var el = document.createElement('div');
            el.className = 'toast-item ' + type;
            el.innerHTML = '<span>' + escapeHtml(message) + '</span><button class="toast-close" aria-label="Close">&times;</button>';
            el.querySelector('.toast-close').onclick = function () { removeToast(el); };
            stack.appendChild(el);
            if (duration > 0) setTimeout(function () { removeToast(el); }, duration);
        }
        function removeToast(el) {
            if (!el || !el.parentNode) return;
            el.classList.add('hide');
            setTimeout(function () { el.remove(); }, 200);
        }

        var currentPeriod = 'today';
        var lastMaxId = 0;

        document.addEventListener('DOMContentLoaded', function () {
            loadReport();
            startLiveUpdates();
        });

        function startLiveUpdates() {
            if (typeof(EventSource) !== "undefined") {
                var source = new EventSource("../api/api_restuarant/live_orders.php?last_id=" + lastMaxId);
                
                source.onmessage = function(event) {
                    var data = JSON.parse(event.data);
                    
                    if (data.new_order) {
                        lastMaxId = data.max_id;
                        // Instantly refresh the report without a page reload
                        loadReport(); 
                        toast('Revenue updated live', 'info', 2000);
                    }
                };
                
                source.onerror = function() {
                    source.close();
                    setTimeout(function() { startLiveUpdates(); }, 3000);
                };
            } else {
                // Fallback just in case
                setInterval(loadReport, 15000);
            }
        }

        function setPeriod(p, btn) {
            currentPeriod = p;
            document.querySelectorAll('.period-tab').forEach(function (t) { t.classList.remove('active'); });
            btn.classList.add('active');
            loadReport();
        }

        function safeFetch(url, options) {
            return fetch(url, options).then(async function (r) {
                var text = await r.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("RAW SERVER RESPONSE:", text);
                    alert("SERVER ERROR:\n\n" + text);
                    throw new Error("Invalid JSON");
                }
            });
        }

        function loadReport() {
            safeFetch('../api/api_restuarant/reports.php?period=' + currentPeriod)
                .then(function (res) {
                    if (res.status !== 'success') { toast(res.message || 'Failed to load', 'error'); return; }
                    renderReport(res);
                })
                .catch(function (err) {
                    if (err.message !== 'Invalid JSON') toast('Network error', 'error');
                });
        }

        function renderReport(d) {
            document.getElementById('stat-revenue').innerText = ugx(d.revenue);
            document.getElementById('stat-orders').innerText = d.order_count + ' paid order' + (d.order_count === 1 ? '' : 's');
            document.getElementById('stat-stock').innerText = ugx(d.stock_spend);
            document.getElementById('stat-expenses').innerText = ugx(d.expenses);
            document.getElementById('stat-net').innerText = ugx(d.net);
            document.getElementById('stat-net').className = 'metric-value ' + (d.net >= 0 ? 'net-positive' : 'net-negative');
            document.getElementById('stat-net-note').innerText =
                d.net >= 0 ? 'Profit for ' + labelForPeriod(d.period) : 'Loss for ' + labelForPeriod(d.period);

            var byTypeTbody = document.getElementById('by-type-tbody');
            byTypeTbody.innerHTML = '';
            if (!d.by_type || d.by_type.length === 0) {
                byTypeTbody.innerHTML = '<tr><td colspan="3" class="empty-row">No paid orders in this period.</td></tr>';
            } else {
                d.by_type.forEach(function (r) {
                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + escapeHtml(r.order_type) + '</td>' +
                        '<td>' + r.orders + '</td>' +
                        '<td class="text-end fw-bold net-positive">' + ugx(r.revenue) + '</td>';
                    byTypeTbody.appendChild(tr);
                });
            }

            var bsTbody = document.getElementById('best-sellers-tbody');
            bsTbody.innerHTML = '';
            if (!d.best_sellers || d.best_sellers.length === 0) {
                bsTbody.innerHTML = '<tr><td colspan="3" class="empty-row">No sales in this period.</td></tr>';
            } else {
                d.best_sellers.forEach(function (r) {
                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + escapeHtml(r.item_name) + '</td>' +
                        '<td>' + r.qty + '</td>' +
                        '<td class="text-end fw-bold net-positive">' + ugx(r.revenue) + '</td>';
                    bsTbody.appendChild(tr);
                });
            }

            var catTbody = document.getElementById('stock-cat-tbody');
            catTbody.innerHTML = '';
            if (!d.stock_by_category || d.stock_by_category.length === 0) {
                catTbody.innerHTML = '<tr><td colspan="2" class="empty-row">No stock purchases in this period.</td></tr>';
            } else {
                d.stock_by_category.forEach(function (c) {
                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + escapeHtml(c.category) + '</td>' +
                        '<td class="text-end fw-bold" style="color: var(--warning);">' + ugx(c.spent) + '</td>';
                    catTbody.appendChild(tr);
                });
            }

            var expTbody = document.getElementById('expense-cat-tbody');
            expTbody.innerHTML = '';
            if (!d.expenses_by_category || d.expenses_by_category.length === 0) {
                expTbody.innerHTML = '<tr><td colspan="2" class="empty-row">No expenses in this period.</td></tr>';
            } else {
                d.expenses_by_category.forEach(function (c) {
                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + escapeHtml(c.category) + '</td>' +
                        '<td class="text-end fw-bold text-danger">' + ugx(c.spent) + '</td>';
                    expTbody.appendChild(tr);
                });
            }
        }

        function labelForPeriod(p) {
            return p === 'today' ? 'today' : (p === 'week' ? 'this week' : 'this month');
        }

        function openDetails(type) {
            var ov = document.getElementById('detail-overlay');
            document.getElementById('detail-title').innerText = titleForType(type);
            document.getElementById('detail-sub').innerText = 'Period: ' + labelForPeriod(currentPeriod);
            document.getElementById('detail-total-label').innerText = 'Total';
            document.getElementById('detail-total').innerText = 'Loading...';
            document.getElementById('detail-thead').innerHTML = '';
            document.getElementById('detail-tbody').innerHTML = '<tr><td class="empty-row">Loading...</td></tr>';
            ov.classList.add('open');

            safeFetch('../api/api_restuarant/reports.php?mode=details&type=' + type + '&period=' + currentPeriod)
                .then(function (res) {
                    if (res.status !== 'success') {
                        toast(res.message || 'Failed to load details', 'error');
                        closeDetails();
                        return;
                    }
                    renderDetails(type, res.rows);
                })
                .catch(function (err) { 
                    if (err.message !== 'Invalid JSON') toast('Network error', 'error'); 
                    closeDetails(); 
                });
        }

        function titleForType(type) {
            return type === 'sales' ? 'Sales — Order by Order'
                 : type === 'stock' ? 'Stock Purchases — Item by Item'
                 : 'Expenses — Line by Line';
        }

        function renderDetails(type, rows) {
            var total = 0;
            var thead = document.getElementById('detail-thead');
            var tbody = document.getElementById('detail-tbody');
            var totalEl = document.getElementById('detail-total');

            if (!rows || rows.length === 0) {
                thead.innerHTML = '';
                tbody.innerHTML = '<tr><td class="empty-row">Nothing recorded in this period.</td></tr>';
                totalEl.innerText = ugx(0);
                return;
            }

            if (type === 'sales') {
                thead.innerHTML = '<tr><th>Order</th><th>Customer / Table</th><th>Items</th><th class="text-end">Amount</th></tr>';
                tbody.innerHTML = '';
                rows.forEach(function (o) {
                    total += o.total_amount;
                    var itemsHtml = o.items.map(function (i) {
                        return i.quantity + '× ' + escapeHtml(i.item_name);
                    }).join('<br>');
                    var where = o.table_number ? ('Table ' + o.table_number) : 'Counter';
                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td><strong style="color: var(--primary);">#' + o.order_id + '</strong><br><small class="text-muted">' + escapeHtml(o.created_at) + '</small></td>' +
                        '<td>' + escapeHtml(o.customer_name) + '<br><small class="text-muted">' + where + ' · ' + escapeHtml(o.order_type) + '</small></td>' +
                        '<td class="order-item-list">' + (itemsHtml || '<em>none</em>') + '</td>' +
                        '<td class="text-end fw-bold net-positive">' + ugx(o.total_amount) + '</td>';
                    tbody.appendChild(tr);
                });
            } else if (type === 'stock') {
                thead.innerHTML = '<tr><th>Item</th><th>Category</th><th>Qty</th><th>Unit Cost</th><th class="text-end">Total</th></tr>';
                tbody.innerHTML = '';
                rows.forEach(function (s) {
                    total += s.total_cost;
                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + escapeHtml(s.item_name) + '<br><small class="text-muted">' + escapeHtml(s.purchase_date) + '</small>' + (s.supplier ? '<br><small class="text-muted">Supplier: ' + escapeHtml(s.supplier) + '</small>' : '') + '</td>' +
                        '<td>' + escapeHtml(s.category) + '</td>' +
                        '<td>' + s.quantity + ' ' + escapeHtml(s.unit) + '</td>' +
                        '<td>' + ugx(s.unit_cost) + '</td>' +
                        '<td class="text-end fw-bold" style="color: var(--warning);">' + ugx(s.total_cost) + '</td>';
                    tbody.appendChild(tr);
                });
            } else {
                thead.innerHTML = '<tr><th>Expense</th><th>Category</th><th>Notes</th><th class="text-end">Amount</th></tr>';
                tbody.innerHTML = '';
                rows.forEach(function (e) {
                    total += e.amount;
                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + escapeHtml(e.expense_name) + '<br><small class="text-muted">' + escapeHtml(e.expense_date) + '</small></td>' +
                        '<td>' + escapeHtml(e.category) + '</td>' +
                        '<td><small class="text-muted">' + (e.notes ? escapeHtml(e.notes) : '—') + '</small></td>' +
                        '<td class="text-end fw-bold text-danger">' + ugx(e.amount) + '</td>';
                    tbody.appendChild(tr);
                });
            }

            totalEl.innerText = ugx(total);
        }

        function closeDetails(e) {
            if (e && e.target !== e.currentTarget) return;
            document.getElementById('detail-overlay').classList.remove('open');
        }
    </script>
</body>
</html>