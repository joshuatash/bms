<?php
// bms/restuarant/index.php
require_once __DIR__ . '/../config.php';
require_login();

$role = strtolower(current_user()['role'] ?? 'cashier');
$isCashier = ($role === 'cashier');

// If a cashier somehow lands on the dashboard, send them to POS
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
    <title>Restaurant Operations Dashboard</title>
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
        .page-header-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.75rem; border-bottom: 1px solid var(--border); padding-bottom: 1.25rem; }
        
        /* TOP REVENUE STATS */
        .stats-top-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.75rem; }
        .stat-tile { 
            background: var(--surface); border: 1px solid var(--border); border-radius: 12px; 
            padding: 1.15rem 1.25rem; display: flex; flex-direction: column; position: relative; 
            cursor: pointer; transition: transform 0.15s, border-color 0.15s;
        }
        .stat-tile:hover { transform: translateY(-2px); border-color: var(--primary); }
        .stat-tile .label { color: var(--text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
        .stat-tile .value { font-size: 1.5rem; font-weight: 800; color: var(--text); }
        .stat-tile .value.green { color: var(--success); }
        .stat-tile .value.blue { color: var(--blue); }
        .stat-tile .value.primary { color: var(--primary); }
        
        .stat-tile .metric-arrow { position: absolute; bottom: 12px; right: 16px; font-size: 1rem; color: var(--text-muted); transition: transform 0.2s, color 0.2s; }
        .stat-tile:hover .metric-arrow { color: var(--primary); transform: translateY(2px); }

        /* METRICS GRID */
        .metrics-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 1.75rem; }
        .metric-card { background-color: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.15rem 1.25rem; position: relative; overflow: hidden; cursor: pointer; transition: transform 0.15s, border-color 0.15s; }
        .metric-card:hover { transform: translateY(-2px); border-color: var(--primary); }
        .metric-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; }
        .metric-card.danger-strip::before { background-color: var(--danger); }
        .metric-card.warning-strip::before { background-color: var(--warning); }
        .metric-card.blue-strip::before { background-color: var(--blue); }
        .metric-card.success-strip::before { background-color: var(--success); }
        .metric-label { color: var(--text-muted); font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.4rem; display: block; }
        .metric-value { font-size: 1.8rem; font-weight: 800; letter-spacing: -0.02em; line-height: 1; margin: 0; }
        .metric-arrow { position: absolute; bottom: 12px; right: 16px; font-size: 1rem; color: var(--text-muted); transition: transform 0.2s, color 0.2s; }
        .metric-card:hover .metric-arrow { color: var(--primary); transform: translateY(2px); }

        /* DETAILS BOTTOM SHEET */
        .sheet-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.55); z-index: 1060; display: none; align-items: flex-end; }
        .sheet-overlay.open { display: flex; animation: fadein .15s ease; }
        .bottom-sheet { background: var(--surface); width: 100%; max-width: 900px; margin: 0 auto; border-radius: 20px 20px 0 0; padding: 12px 16px calc(20px + env(safe-area-inset-bottom, 0px)); max-height: 88vh; overflow-y: auto; animation: slideup .25s ease; }
        @keyframes fadein { from { opacity: 0 } }
        @keyframes slideup { from { transform: translateY(100%) } }
        .sheet-handle { width: 40px; height: 4px; border-radius: 2px; background: var(--border); margin: 0 auto 12px; }
        .sheet-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .sheet-title { font-size: 16px; font-weight: 800; margin: 0; }
        .sheet-total-line { padding: 12px 14px; border-radius: 10px; background: var(--surface-2); display: flex; justify-content: space-between; align-items: center; font-weight: 700; margin-bottom: 14px; }
        .sheet-total-line strong { font-size: 18px; color: var(--primary); }
        .detail-close { background: var(--surface-2); border: 1px solid var(--border); color: var(--text); width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        
        /* Table styles */
        .table-custom { width: 100%; margin-bottom: 0; color: var(--text); border-collapse: collapse; }
        .table-custom th { background-color: var(--surface-2); color: var(--text-muted); font-weight: 700; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; padding: 0.85rem 1.25rem; border-bottom: 1px solid var(--border); white-space: nowrap; }
        .table-custom td { padding: 1rem 1.25rem; vertical-align: middle; border-bottom: 1px solid var(--border); font-size: 0.88rem; }
        .unpaid-row { background-color: rgba(248, 113, 113, 0.04); box-shadow: inset 3px 0 0 var(--danger); }
        
        .badge-pill { display: inline-flex; align-items: center; padding: 0.25rem 0.65rem; font-weight: 600; font-size: 0.7rem; border-radius: 6px; letter-spacing: 0.02em; }
        .badge-received, .badge-cooking { background-color: rgba(251, 191, 36, 0.12); color: var(--warning); border: 1px solid rgba(251, 191, 36, 0.25); }
        .badge-ready { background-color: rgba(96, 165, 250, 0.12); color: var(--blue); border: 1px solid rgba(96, 165, 250, 0.25); }
        .badge-served, .badge-paid { background-color: rgba(74, 222, 128, 0.12); color: var(--success); border: 1px solid rgba(74, 222, 128, 0.25); }
        .badge-unpaid { background-color: rgba(248, 113, 113, 0.12); color: var(--danger); border: 1px solid rgba(248, 113, 113, 0.25); }
        
        .btn-action-clear { background-color: rgba(74, 222, 128, 0.12); color: var(--success); border: 1px solid rgba(74, 222, 128, 0.25); font-weight: 600; font-size: 0.75rem; padding: 0.3rem 0.75rem; border-radius: 6px; }
        .btn-action-advance { background-color: var(--surface-2); color: var(--text); border: 1px solid var(--border); font-weight: 600; font-size: 0.75rem; padding: 0.3rem 0.75rem; border-radius: 6px; }

        /* Mobile bottom nav */
        .mobile-bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background-color: var(--sidebar); border-top: 1px solid var(--border); display: none; justify-content: space-around; padding: 0.5rem 0 calc(0.5rem + env(safe-area-inset-bottom, 0px)); z-index: 1050; }
        .mobile-nav-link { color: var(--text-muted); text-align: center; text-decoration: none; font-size: 0.68rem; font-weight: 600; flex-grow: 1; padding: 0.3rem 0; position: relative; -webkit-tap-highlight-color: transparent; }
        .mobile-nav-link i { display: block; font-size: 1.1rem; margin-bottom: 0.2rem; }
        .mobile-nav-link.active { color: var(--active-green); }
        .mobile-nav-link.active::before { content: ''; position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 40px; height: 3px; border-radius: 0 0 3px 3px; background: var(--active-green); }
        .mobile-nav-link.bar-link { color: var(--primary); }

        /* Toasts */
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

        @media (min-width: 992px) { .mobile-bottom-nav { display: none !important; } body { padding-bottom: 0; } }
        @media (max-width: 991.98px) {
            .app-header { display: none; }
            .mobile-bottom-nav { display: flex; }
            body { padding-bottom: calc(75px + env(safe-area-inset-bottom, 0px)); }
            .stats-top-row { grid-template-columns: 1fr 1fr; }
            .metrics-grid { grid-template-columns: 1fr 1fr; }
            .main-container { padding: 1.25rem 1rem; }
            .page-header-row { flex-direction: column; align-items: flex-start; gap: 1rem; }
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
        <div class="page-header-row">
            <div>
                <h1 class="h3 fw-bold mb-1" style="color: var(--text);">Operations Dashboard</h1>
            </div>
        </div>

        <!-- TOP REVENUE STATS -->
        <div class="stats-top-row">
            <div class="stat-tile" onclick="openDetails('revenue')">
                <span class="label">Revenue Today</span>
                <span class="value green" id="top-revenue">UGX 0</span>
                <i class="fa-solid fa-chevron-down metric-arrow"></i>
            </div>
            <div class="stat-tile" onclick="openDetails('all_orders')">
                <span class="label">Total Orders</span>
                <span class="value blue" id="top-orders">0</span>
                <i class="fa-solid fa-chevron-down metric-arrow"></i>
            </div>
            <div class="stat-tile" onclick="openDetails('aov')">
                <span class="label">Avg Order Value</span>
                <span class="value primary" id="top-aov">UGX 0</span>
                <i class="fa-solid fa-chevron-down metric-arrow"></i>
            </div>
            <div class="stat-tile" onclick="openDetails('best_seller')">
                <span class="label">Best Seller Today</span>
                <span class="value" style="font-size: 1.1rem; margin-top: 6px;" id="top-best-seller">-</span>
                <i class="fa-solid fa-chevron-down metric-arrow"></i>
            </div>
        </div>

        <!-- METRIC CARDS -->
        <div class="metrics-grid">
            <div class="metric-card danger-strip" onclick="openDetails('unpaid')">
                <span class="metric-label">Unpaid Tabs</span>
                <p id="stat-unpaid" class="metric-value" style="color: var(--danger);">0</p>
                <i class="fa-solid fa-chevron-down metric-arrow"></i>
            </div>
            <div class="metric-card warning-strip" onclick="openDetails('received')">
                <span class="metric-label">Received</span>
                <p id="stat-received" class="metric-value" style="color: var(--warning);">0</p>
                <i class="fa-solid fa-chevron-down metric-arrow"></i>
            </div>
            <div class="metric-card warning-strip" onclick="openDetails('cooking')">
                <span class="metric-label">Cooking</span>
                <p id="stat-cooking" class="metric-value" style="color: var(--warning);">0</p>
                <i class="fa-solid fa-chevron-down metric-arrow"></i>
            </div>
            <div class="metric-card blue-strip" onclick="openDetails('ready')">
                <span class="metric-label">Ready</span>
                <p id="stat-ready" class="metric-value" style="color: var(--blue);">0</p>
                <i class="fa-solid fa-chevron-down metric-arrow"></i>
            </div>
            <div class="metric-card success-strip" onclick="openDetails('delivered')">
                <span class="metric-label">Delivered</span>
                <p id="stat-delivered" class="metric-value" style="color: var(--success);">0</p>
                <i class="fa-solid fa-chevron-down metric-arrow"></i>
            </div>
        </div>
    </main>

    <!-- BOTTOM SHEET FOR DETAILS -->
    <div class="sheet-overlay" id="detail-overlay" onclick="closeDetails(event)">
        <div class="bottom-sheet" onclick="event.stopPropagation()">
            <div class="sheet-handle"></div>
            <div class="sheet-head">
                <h3 class="sheet-title" id="detail-title">Details</h3>
                <button class="detail-close" onclick="closeDetails()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <div class="sheet-total-line">
                <span>Total Amount</span>
                <strong id="detail-total">UGX 0</strong>
            </div>

            <div class="table-responsive" style="border: 1px solid var(--border); border-radius: 10px;">
                <table class="table-custom" style="margin: 0;">
                    <thead>
                        <tr>
                            <th class="ps-3">Order</th>
                            <th>Customer / Table</th>
                            <th>Items</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="detail-tbody">
                        <tr><td colspan="4" class="text-center py-4 text-muted">Loading...</td></tr>
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

        var currentMaxId = 0;
        var currentDetailsFilter = '';
        var latestOrders = [];
        var availableVoices = [];

        function loadVoices() { availableVoices = window.speechSynthesis.getVoices(); }
        if (typeof speechSynthesis !== 'undefined' && speechSynthesis.onvoiceschanged !== undefined) {
            speechSynthesis.onvoiceschanged = loadVoices;
        }
        loadVoices();

        document.addEventListener("DOMContentLoaded", function() {
            loadDashboardStats();
            startLiveUpdates();
        });

        function startLiveUpdates() {
            if (typeof(EventSource) !== "undefined") {
                var source = new EventSource("../api/api_restuarant/live_orders.php?last_id=" + currentMaxId);
                
                source.onmessage = function(event) {
                    var data = JSON.parse(event.data);
                    
                    if (data.new_order) {
                        fetch('../api/api_restuarant/orders.php')
                            .then(function (r) { return r.json(); })
                            .then(function (res) { 
                                if (res.status === 'success') {
                                    latestOrders = res.data;
                                    updateDashboardStats(res.data);
                                    
                                    if (res.data.length > 0) {
                                        var ids = res.data.map(function(o) { return o.order_id; });
                                        currentMaxId = Math.max.apply(Math, ids);
                                        
                                        var newestOrder = res.data[0];
                                        var itemsText = newestOrder.items.map(function(i) {
                                            return i.quantity + ' ' + i.item_name.toUpperCase();
                                        }).join(', and ');
                                        
                                        var table = newestOrder.table_number ? 'Table ' + newestOrder.table_number : 'Counter';
                                        var speech = "Order number " + newestOrder.order_id + ", " + itemsText + " for " + table;
                                        speakMessage(speech);
                                        playBeep();
                                    }
                                }
                            });
                            
                        toast('New order #' + data.max_id + ' received!', 'success', 5000);
                    }
                };
                
                source.onerror = function() {
                    source.close();
                    setTimeout(function() { startLiveUpdates(); }, 3000);
                };
            } else {
                setInterval(loadDashboardStats, 10000);
            }
        }

        function playBeep() {
            try {
                var audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                var oscillator = audioCtx.createOscillator();
                oscillator.type = 'sine'; oscillator.frequency.setValueAtTime(800, audioCtx.currentTime);
                oscillator.connect(audioCtx.destination); oscillator.start(); oscillator.stop(audioCtx.currentTime + 0.3);
            } catch(e) {}
        }

        function speakMessage(message) {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                var utterance = new SpeechSynthesisUtterance(message);
                utterance.rate = 1.5; // Fast 1.5X speed
                utterance.pitch = 1.6; // Female pitch
                utterance.volume = 1.0;
                
                if (availableVoices.length === 0) availableVoices = window.speechSynthesis.getVoices();
                
                var femaleKeywords = ['female', 'samantha', 'zira', 'karen', 'victoria', 'tessa', 'fiona', 'moira', 'veena', 'google uk english female', 'google us english', 'aria', 'jenny'];
                var femaleVoice = availableVoices.find(function(voice) {
                    var vName = voice.name.toLowerCase();
                    return voice.lang.startsWith('en') && femaleKeywords.some(function(kw) { return vName.includes(kw); });
                });
                
                if (femaleVoice) utterance.voice = femaleVoice;
                window.speechSynthesis.speak(utterance);
            }
        }

        function loadDashboardStats() {
            fetch('../api/api_restuarant/orders.php')
                .then(function (r) { return r.json(); })
                .then(function (res) { 
                    if (res.status === 'success') {
                        latestOrders = res.data;
                        updateDashboardStats(res.data); 
                        if (res.data.length > 0) {
                            var ids = res.data.map(function(o) { return o.order_id; });
                            currentMaxId = Math.max.apply(Math, ids);
                        }
                    }
                })
                .catch(function () { toast('Could not load orders', 'error'); });
        }

        function updateDashboardStats(orders) {
            var unpaid = 0, received = 0, cooking = 0, ready = 0, delivered = 0;
            var revenue = 0, paidCount = 0;
            var itemCounts = {};

            orders.forEach(function (order) {
                // Status counts
                if (order.order_status === 'Received') received++;
                else if (order.order_status === 'Cooking') cooking++;
                else if (order.order_status === 'Ready') ready++;
                else if (order.order_status === 'Disposed') delivered++;

                // Unpaid counts
                if (order.payment_status === 'Unpaid') unpaid++;

                // Revenue & Best Seller (Only Paid today)
                if (order.payment_status === 'Paid') {
                    revenue += Number(order.total_amount);
                    paidCount++;
                    
                    order.items.forEach(function(item) {
                        itemCounts[item.item_name] = (itemCounts[item.item_name] || 0) + item.quantity;
                    });
                }
            });

            // Best seller logic
            var bestSellerName = '-';
            var maxQty = 0;
            for (var name in itemCounts) {
                if (itemCounts[name] > maxQty) {
                    maxQty = itemCounts[name];
                    bestSellerName = name;
                }
            }

            // Average Order Value
            var aov = paidCount > 0 ? (revenue / paidCount) : 0;

            // Update Top Stats
            document.getElementById('top-revenue').innerText = 'UGX ' + revenue.toLocaleString();
            document.getElementById('top-orders').innerText = paidCount;
            document.getElementById('top-aov').innerText = 'UGX ' + Math.round(aov).toLocaleString();
            document.getElementById('top-best-seller').innerText = bestSellerName;

            // Update Metric Cards
            document.getElementById('stat-unpaid').innerText = unpaid;
            document.getElementById('stat-received').innerText = received;
            document.getElementById('stat-cooking').innerText = cooking;
            document.getElementById('stat-ready').innerText = ready;
            document.getElementById('stat-delivered').innerText = delivered;
        }

        // === DETAILS PANEL LOGIC ===
        function openDetails(filterType) {
            currentDetailsFilter = filterType;
            
            var title = "Details";
            if (filterType === 'unpaid') title = "Unpaid Tabs";
            if (filterType === 'received') title = "Received Orders";
            if (filterType === 'cooking') title = "Orders Cooking";
            if (filterType === 'ready') title = "Ready to Serve";
            if (filterType === 'delivered') title = "Delivered Orders";
            if (filterType === 'revenue') title = "Today's Revenue (Paid)";
            if (filterType === 'all_orders') title = "All Orders Today";
            
            document.getElementById('detail-title').innerText = title;

            // Handle stats tiles differently
            if (filterType === 'aov' || filterType === 'best_seller') {
                document.getElementById('detail-total').innerText = '-';
                document.getElementById('detail-tbody').innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Summary metric. No specific orders to list.</td></tr>';
                document.getElementById('detail-overlay').classList.add('open');
                return;
            }

            var filtered = latestOrders.filter(function(o) {
                if (filterType === 'unpaid') return o.payment_status === 'Unpaid';
                if (filterType === 'received') return o.order_status === 'Received';
                if (filterType === 'cooking') return o.order_status === 'Cooking';
                if (filterType === 'ready') return o.order_status === 'Ready';
                if (filterType === 'delivered') return o.order_status === 'Disposed';
                if (filterType === 'revenue') return o.payment_status === 'Paid';
                if (filterType === 'all_orders') return true;
                return false;
            });

            var tbody = document.getElementById('detail-tbody');
            var totalAmount = 0;
            tbody.innerHTML = '';

            if (filtered.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">No orders found in this category.</td></tr>';
            } else {
                filtered.forEach(function(order) {
                    totalAmount += Number(order.total_amount);
                    var itemsList = order.items.map(function (i) { return i.quantity + 'x ' + i.item_name; }).join(', ');
                    var tableDisplay = order.table_number ? ('Table ' + order.table_number) : 'Counter';
                    
                    var tr = document.createElement('tr');
                    tr.innerHTML = 
                        '<td class="ps-3 fw-bold" style="color: var(--primary);">#' + order.order_id + '<br><small style="color: var(--text-muted); font-weight:400;">UGX ' + Number(order.total_amount).toLocaleString() + '</small></td>' +
                        '<td><div class="fw-bold">' + escapeHtml(order.customer_name) + '</div><small style="color: var(--text-muted);">' + tableDisplay + '</small></td>' +
                        '<td><small style="color: var(--text-muted);">' + escapeHtml(itemsList) + '</small></td>' +
                        '<td class="text-end pe-3">' +
                            (order.payment_status === 'Unpaid' ? '<button class="btn btn-sm me-1 btn-action-clear" data-clear="' + order.order_id + '">Clear</button>' : '') +
                            '<button class="btn btn-sm btn-action-advance" data-advance="' + order.order_id + '" data-status="' + order.order_status + '">Advance</button>' +
                        '</td>';
                    tbody.appendChild(tr);
                });
            }

            document.getElementById('detail-total').innerText = 'UGX ' + totalAmount.toLocaleString();

            document.querySelectorAll('#detail-tbody [data-clear]').forEach(function (b) {
                b.onclick = function () { clearPayment(parseInt(b.getAttribute('data-clear'), 10)); };
            });
            document.querySelectorAll('#detail-tbody [data-advance]').forEach(function (b) {
                b.onclick = function () { advanceStatus(parseInt(b.getAttribute('data-advance'), 10), b.getAttribute('data-status')); };
            });

            document.getElementById('detail-overlay').classList.add('open');
        }

        function closeDetails(e) {
            if (e && e.target !== e.currentTarget) return;
            document.getElementById('detail-overlay').classList.remove('open');
        }

        function clearPayment(orderId) {
            if (!confirm('Confirm payment received and settle this customer tab?')) return;
            fetch('../api/api_restuarant/orders.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, payment_status: 'Paid' })
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data.status === 'success') { 
                    toast('Payment cleared', 'success'); 
                    loadDashboardStats();
                    openDetails(currentDetailsFilter); 
                }
                else { toast(data.message || 'Update failed', 'error'); }
            }).catch(function () { toast('Network error', 'error'); });
        }

        function advanceStatus(orderId, currentStatus) {
            var next = 'Received';
            if (currentStatus === 'Received') next = 'Cooking';
            else if (currentStatus === 'Cooking') next = 'Ready';
            else if (currentStatus === 'Ready') next = 'Disposed';
            else if (currentStatus === 'Disposed') return;

            fetch('../api/api_restuarant/orders.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, order_status: next })
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data.status === 'success') { 
                    toast('Order moved to ' + next, 'success'); 
                    loadDashboardStats();
                    openDetails(currentDetailsFilter);
                }
                else { toast(data.message || 'Update failed', 'error'); }
            }).catch(function () { toast('Network error', 'error'); });
        }
    </script>
</body>
</html>