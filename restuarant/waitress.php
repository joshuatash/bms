<?php
// bms/restuarant/waitress.php
require_once __DIR__ . '/../config.php';
require_login();

$role = strtolower(current_user()['role'] ?? 'cashier');
$isCashier = ($role === 'cashier');
$isWaitress = ($role === 'waitress');

// Allow cashiers, waitresses, admin, and managers
if (!in_array($role, ['admin', 'manager', 'cashier', 'waitress'], true)) {
    header('Location: ' . home_for($role));
    exit;
}

// Hide the Bar Side link for cashiers
$showBarSide = !$isCashier;

// Hide the top nav links if they are a cashier or waitress
$hideTopNav = ($isCashier || $isWaitress);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Waitress Dashboard</title>
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
        .desktop-nav .nav-link { color: var(--text-muted); font-weight: 600; font-size: 0.83rem; padding: 0.45rem 0.85rem; border-radius: 6px; text-decoration: none; transition: 0.2s; }
        .desktop-nav .nav-link:hover { color: var(--text); background-color: var(--surface); }
        .desktop-nav .nav-link.active { color: var(--sidebar); background-color: var(--active-green); }
        .desktop-nav .nav-link.bar-link { color: var(--primary); border: 1px solid var(--primary); }
        .desktop-nav .nav-link.bar-link:hover { background-color: var(--primary); color: var(--sidebar); }
        .desktop-nav .nav-link.logout-link { color: var(--danger); border: 1px solid var(--danger); }
        .desktop-nav .nav-link.logout-link:hover { background-color: var(--danger); color: #fff; }

        .main-container { max-width: 1400px; margin: 0 auto; padding: 2rem 1.75rem; }
        
        /* Make page header wrap perfectly on mobile */
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 1.5rem; }
        
        /* Responsive Subtitle banner */
        .sub-outstanding { 
            display: inline-flex; align-items: center; gap: 8px; margin: 8px 0 0; padding: 8px 16px; 
            background: linear-gradient(135deg, rgba(96,165,250,.15) 0%, rgba(96,165,250,.05) 100%); 
            border: 1px solid rgba(96,165,250,.3); border-left: 4px solid var(--blue); border-radius: 8px; 
            color: var(--blue); font-size: .85rem; font-weight: 700; letter-spacing: .3px; 
            text-shadow: 0 0 12px rgba(96,165,250,.4); box-shadow: 0 4px 12px rgba(0,0,0,.2); 
            animation: pulse-blue 2s infinite alternate; 
            max-width: 100%; 
            white-space: normal; 
            word-break: break-word;
        }
        @keyframes pulse-blue { 0% { border-color: rgba(96,165,250,.3); box-shadow: 0 4px 12px rgba(0,0,0,.2); } 100% { border-color: rgba(96,165,250,.6); box-shadow: 0 4px 15px rgba(96,165,250,.15); } }
        .sub-outstanding svg { flex-shrink: 0; width: 16px; height: 16px; }
        
        .btn-refresh { background: var(--surface-2); color: var(--text); border: 1px solid var(--border); font-weight: 700; font-size: 0.85rem; padding: 0.6rem 1.5rem; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: 0.2s; }
        .btn-refresh:hover { background: var(--border); color: #fff; }
        .btn-refresh:active { transform: scale(0.97); }
        .btn-refresh i { transition: transform 0.3s; }
        .btn-refresh:hover i { transform: rotate(180deg); }

        /* Metrics Grid */
        .metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; }
        
        /* CLICKABLE METRIC CARDS */
        .metric-card { 
            background: var(--surface); border: 1px solid var(--border); border-radius: 12px; 
            padding: 1.25rem 1.5rem; display: flex; align-items: center; gap: 16px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); position: relative; overflow: hidden; 
            cursor: pointer; transition: transform 0.2s, border-color 0.2s;
        }
        .metric-card:hover { transform: translateY(-3px); border-color: var(--primary); }
        .metric-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; }
        .metric-card.m-green::before { background: var(--success); }
        .metric-card.m-blue::before { background: var(--blue); }
        .metric-card.m-red::before { background: var(--danger); }
        
        .metric-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
        .m-green .metric-icon { background: rgba(74,222,128,.15); color: var(--success); }
        .m-blue .metric-icon { background: rgba(96,165,250,.15); color: var(--blue); }
        .m-red .metric-icon { background: rgba(248,113,113,.15); color: var(--danger); }
        
        .metric-content { display: flex; flex-direction: column; flex-grow: 1; }
        .metric-value { font-size: 1.8rem; font-weight: 800; line-height: 1; margin-bottom: 4px; color: var(--text); }
        .metric-label { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        
        .metric-arrow { font-size: 1rem; color: var(--text-muted); transition: transform 0.2s, color 0.2s; }
        .metric-card:hover .metric-arrow { color: var(--primary); transform: translateY(2px); }

        .section-title { font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; margin: 24px 0 16px; display: flex; align-items: center; gap: 8px; }
        .section-title.ready { color: var(--blue); }
        .section-title.pending { color: var(--text-muted); }
        .section-title.tabs { color: var(--danger); }

        /* Status Grid & Cards (Responsive) */
        .status-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; }
        .status-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; position: relative; transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; }
        .status-card.ready { border-color: rgba(96,165,250,.4); box-shadow: 0 4px 15px rgba(96,165,250,.1); }
        .status-card.ready::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--blue), #a78bfa); }
        .status-card.pending { opacity: 0.85; }
        .status-card.open-tab { border-color: rgba(248, 113, 113, 0.3); background: rgba(248, 113, 113, 0.02); }
        .status-card.open-tab::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--danger); }

        .card-head { padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.15); border-bottom: 1px solid var(--border); }
        .order-id { font-size: 18px; font-weight: 800; color: var(--primary); }
        .badge-status { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; }
        .badge-Ready { background: rgba(96,165,250,.2); color: var(--blue); }
        .badge-Cooking { background: rgba(251,191,36,.2); color: var(--warning); }
        .badge-Received { background: rgba(148,163,184,.2); color: var(--text-muted); }
        .badge-Unpaid { background: rgba(248,113,113,.2); color: var(--danger); }

        .card-body { padding: 18px; display: flex; flex-direction: column; flex-grow: 1; }
        .meta-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 13px; color: var(--text-muted); }
        .meta-row strong { color: var(--text); font-size: 14px; }
        .items-list { border-top: 1px dashed var(--border); border-bottom: 1px dashed var(--border); padding: 12px 0; margin: 12px 0 16px; flex-grow: 1; }
        .item-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 15px; }
        .item-row strong { color: var(--warning); font-size: 16px; margin-right: 8px; }

        .btn-serve { background: linear-gradient(135deg, #16a34a, #15803d); color: #fff; font-weight: 800; font-size: 15px; padding: 14px; border-radius: 10px; width: 100%; border: 0; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(22,163,74,.3); transition: all 0.2s; }
        .btn-serve:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(22,163,74,.4); }
        .btn-serve:active { transform: translateY(0); }
        
        .btn-settle { background: var(--surface-2); color: var(--text); font-weight: 800; font-size: 15px; padding: 14px; border-radius: 10px; width: 100%; border: 1px solid var(--border); cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-settle:hover { background: var(--primary); color: var(--sidebar); border-color: var(--primary); }

        /* View Details Link */
        .view-details-link { 
            display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding: 10px 12px;
            background-color: var(--surface-2); border-radius: 8px; border: 1px solid var(--border);
            font-size: 12px; font-weight: 800; color: var(--primary); cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.2s; 
        }
        .view-details-link:hover { background-color: var(--border); color: #fff; }
        .view-details-link i { transition: transform 0.2s; }
        .view-details-link:hover i { transform: translateY(2px); }

        .empty-state { padding: 3rem 1rem; text-align: center; color: var(--text-muted); background: var(--surface); border: 1px dashed var(--border); border-radius: 12px; grid-column: 1 / -1; }
        .empty-state i { font-size: 2rem; margin-bottom: 1rem; opacity: 0.5; }

        /* Details Bottom Sheet */
        .detail-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.55); z-index: 1060; display: none; align-items: flex-end; }
        .detail-overlay.open { display: flex; animation: fadein .15s ease; }
        .detail-sheet { background: var(--surface); width: 100%; max-width: 700px; margin: 0 auto; border-radius: 20px 20px 0 0; padding: 12px 16px calc(20px + env(safe-area-inset-bottom, 0px)); max-height: 88vh; overflow-y: auto; animation: slideup .25s ease; }
        @keyframes fadein { from { opacity: 0 } }
        @keyframes slideup { from { transform: translateY(100%) } }
        .detail-handle { width: 40px; height: 4px; border-radius: 2px; background: var(--border); margin: 0 auto 12px; }
        .detail-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .detail-title { font-size: 18px; font-weight: 800; margin: 0; }
        .detail-close { background: var(--surface-2); border: 1px solid var(--border); color: var(--text); width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        
        .detail-meta { background: var(--surface-2); border-radius: 12px; padding: 16px; margin-bottom: 16px; }
        .detail-meta-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .detail-meta-row:last-child { margin-bottom: 0; }
        .detail-meta-label { color: var(--text-muted); font-weight: 600; }
        .detail-meta-value { color: var(--text); font-weight: 700; }
        
        .detail-items-title { font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); margin-bottom: 12px; }
        .detail-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed var(--border); }
        .detail-item:last-child { border-bottom: none; }
        .detail-item-name { font-weight: 600; color: var(--text); }
        .detail-item-qty { color: var(--text-muted); font-size: 13px; margin-left: 8px; }
        .detail-item-subtotal { font-weight: 700; color: var(--warning); }
        .detail-total-line { display: flex; justify-content: space-between; padding-top: 16px; margin-top: 8px; border-top: 2px solid var(--border); font-size: 18px; font-weight: 800; color: var(--text); }
        .detail-total-line strong { color: var(--primary); }

        /* Payment Modal */
        .modal-content { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; color: var(--text); }
        .modal-header, .modal-footer { border-color: var(--border) !important; }
        .btn-close { filter: invert(1); }
        .btn-pay { background: var(--primary); color: var(--sidebar); font-weight: 800; font-size: 15px; padding: 14px; border-radius: 10px; width: 100%; border: 0; cursor: pointer; margin-bottom: 10px; }
        .btn-pay:hover { background: var(--primary-hover); }
        .btn-pay.outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-pay.outline:hover { background: var(--surface-2); }

        .mobile-bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background-color: var(--sidebar); border-top: 1px solid var(--border); display: none; justify-content: space-around; padding: 0.5rem 0 calc(0.5rem + env(safe-area-inset-bottom, 0px)); z-index: 1050; }
        .mobile-nav-link { color: var(--text-muted); text-align: center; text-decoration: none; font-size: 0.68rem; font-weight: 600; flex-grow: 1; padding: 0.3rem 0; position: relative; -webkit-tap-highlight-color: transparent; }
        .mobile-nav-link i { display: block; font-size: 1.1rem; margin-bottom: 0.2rem; }
        .mobile-nav-link.active { color: var(--blue); }
        .mobile-nav-link.active::before { content: ''; position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 40px; height: 3px; border-radius: 0 0 3px 3px; background: var(--blue); }
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

        @media (min-width: 992px) { .mobile-bottom-nav { display: none !important; } body { padding-bottom: 0; } }
        @media (max-width: 991.98px) {
            .app-header { display: none; }
            .mobile-bottom-nav { display: flex; }
            body { padding-bottom: calc(75px + env(safe-area-inset-bottom, 0px)); }
            .main-container { padding: 1.25rem 1rem; }
            .page-header { flex-direction: column; align-items: stretch; gap: 1rem; }
            .btn-refresh { width: 100%; justify-content: center; }
            .metrics-grid { grid-template-columns: 1fr; }
            .sub-outstanding { width: 100%; justify-content: center; } /* Cover full width on mobile */
        }
        
        @media (max-width: 480px) {
            .sub-outstanding { font-size: 0.78rem; padding: 8px 12px; }
            .metric-value { font-size: 1.5rem; }
            .section-title { margin: 16px 0 12px; }
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
                <?php if ($hideTopNav): ?>
                    <!-- SHOW ONLY THESE FOR CASHIER / WAITRESS -->
                    <li><a class="nav-link" href="pos.php">Restaurant POS</a></li>
                    <li><a class="nav-link active" href="waitress.php">Restaurant Waitress</a></li>
                    <li class="ms-2"><a class="nav-link bar-link" href="../pos.php">Bar POS</a></li>
                    <li class="ms-2"><a class="nav-link logout-link" href="../logout.php"><i class="fa-solid fa-power-off me-1"></i> Logout</a></li>
                <?php else: ?>
                    <!-- FULL NAV FOR ADMINS & MANAGERS -->
                    <li><a class="nav-link" href="index.php">Dashboard</a></li>
                    <li><a class="nav-link" href="pos.php">Counter POS</a></li>
                    <li><a class="nav-link" href="kitchen.php">Kitchen KDS</a></li>
                    <li><a class="nav-link" href="inventory.php">Inventory</a></li>
                    <li><a class="nav-link" href="admin.php">Menu &amp; Prices</a></li>
                    <li><a class="nav-link" href="reports.php">Revenue</a></li>
                    <li><a class="nav-link active" href="waitress.php">Waitress</a></li>
                    <?php if ($showBarSide): ?>
                    <li class="ms-2"><a class="nav-link bar-link" href="../dashboard.php">Bar Side &rarr;</a></li>
                    <?php endif; ?>
                    <li class="ms-2"><a class="nav-link logout-link" href="../logout.php"><i class="fa-solid fa-power-off me-1"></i> Logout</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <main class="main-container">
        <div class="page-header">
            <div>
                <h1 class="h3 fw-bold mb-1" style="color: var(--text);">Waitress Station</h1>
                <div class="sub-outstanding">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Delivering ready orders to tables
                </div>
            </div>
            <button class="btn-refresh" onclick="loadWaitressOrders()">
                <i class="fa-solid fa-rotate"></i> Refresh Queue
            </button>
        </div>

        <!-- METRICS DASHBOARD (NOW CLICKABLE) -->
        <div class="metrics-grid">
            <div class="metric-card m-green" onclick="openHistoryDetails('delivered')">
                <div class="metric-icon"><i class="fa-solid fa-bell-concierge"></i></div>
                <div class="metric-content">
                    <div class="metric-value" id="delivery-count">0</div>
                    <div class="metric-label">Total Delivered</div>
                </div>
                <i class="fa-solid fa-chevron-down metric-arrow"></i>
            </div>
            <div class="metric-card m-blue" onclick="openHistoryDetails('paid')">
                <div class="metric-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div class="metric-content">
                    <div class="metric-value" id="paid-count">0</div>
                    <div class="metric-label">Total Paid</div>
                </div>
                <i class="fa-solid fa-chevron-down metric-arrow"></i>
            </div>
            <div class="metric-card m-red" onclick="openHistoryDetails('unpaid')">
                <div class="metric-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <div class="metric-content">
                    <div class="metric-value" id="unpaid-count">0</div>
                    <div class="metric-label">Open Tabs (Unpaid)</div>
                </div>
                <i class="fa-solid fa-chevron-down metric-arrow"></i>
            </div>
        </div>

        <div class="section-title ready">
            <i class="fa-solid fa-bell"></i> Ready to Serve
        </div>
        <div id="ready-orders-container" class="status-grid mb-5">
            <div class="empty-state">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <div>Checking for ready orders...</div>
            </div>
        </div>

        <div class="section-title pending">
            <i class="fa-solid fa-fire-burner"></i> Pending Orders (In Kitchen)
        </div>
        <div id="pending-orders-container" class="status-grid mb-5">
            <div class="empty-state">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <div>Checking for pending orders...</div>
            </div>
        </div>

        <!-- OPEN TABS SECTION -->
        <div class="section-title tabs">
            <i class="fa-solid fa-file-invoice-dollar"></i> Open Tabs (Pay Later)
        </div>
        <div id="open-tabs-container" class="status-grid">
            <div class="empty-state">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <div>Checking for open tabs...</div>
            </div>
        </div>
    </main>

    <!-- DETAILS BOTTOM SHEET (For Active Cards) -->
    <div class="detail-overlay" id="detail-overlay" onclick="closeDetails(event)">
        <div class="detail-sheet" onclick="event.stopPropagation()">
            <div class="detail-handle"></div>
            <div class="detail-head">
                <h3 class="detail-title" id="detail-title">Order Details</h3>
                <button class="detail-close" onclick="closeDetails()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <div class="detail-meta">
                <div class="detail-meta-row"><span class="detail-meta-label">Order ID</span><span class="detail-meta-value" id="det-order-id">-</span></div>
                <div class="detail-meta-row"><span class="detail-meta-label">Customer</span><span class="detail-meta-value" id="det-customer">-</span></div>
                <div class="detail-meta-row"><span class="detail-meta-label">Location</span><span class="detail-meta-value" id="det-table">-</span></div>
                <div class="detail-meta-row"><span class="detail-meta-label">Status</span><span class="detail-meta-value" id="det-status">-</span></div>
            </div>

            <div class="detail-items-title">Items Ordered</div>
            <div id="detail-items-container"></div>

            <div class="detail-total-line">
                <span>Total Bill</span>
                <strong id="det-total">UGX 0</strong>
            </div>

            <!-- ACTION BUTTON INSIDE DETAILS SHEET -->
            <div id="detail-action-container" style="margin-top: 20px; display: none;">
                <button class="btn-serve" id="btn-detail-action" style="width: 100%; height: 52px; border-radius: 12px;">
                    <i class="fa-solid fa-check-double"></i> Deliver & Settle
                </button>
            </div>
        </div>
    </div>

    <!-- HISTORY BOTTOM SHEET (For Metric Cards) -->
    <div class="detail-overlay" id="history-overlay" onclick="closeHistoryDetails(event)">
        <div class="detail-sheet" style="max-width: 800px;" onclick="event.stopPropagation()">
            <div class="detail-handle"></div>
            <div class="detail-head">
                <h3 class="detail-title" id="history-title">History</h3>
                <button class="detail-close" onclick="closeHistoryDetails()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="table-responsive" style="border: 1px solid var(--border); border-radius: 10px; margin-bottom: 20px;">
                <table class="table-custom" style="margin: 0;">
                    <thead>
                        <tr>
                            <th class="ps-3">Order / Table</th>
                            <th>Items</th>
                            <th class="text-end pe-3">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="history-tbody">
                        <tr><td colspan="3" class="text-center py-4 text-muted">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PAYMENT MODAL -->
    <div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-uppercase" style="font-size: 0.9rem; letter-spacing: 1px;">Complete Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="closePaymentModal()"></button>
                </div>
                <div class="modal-body text-center pt-4">
                    <div class="mb-4">
                        <i class="fa-solid fa-money-bill-wave text-muted mb-3" style="font-size: 3rem;"></i>
                        <h3 class="fw-bold mb-1" style="color: var(--text);">UGX <span id="modal-total">0</span></h3>
                        <p class="text-muted mb-0">Order #<span id="modal-order-id">0</span> • Table <span id="modal-table">0</span></p>
                    </div>
                    
                    <input type="hidden" id="pay-order-id">
                    <input type="hidden" id="pay-action-type"> <!-- 'serve' or 'settle' -->
                    
                    <div class="text-start mt-4">
                        <label class="form-label text-muted fw-bold" style="font-size: 0.75rem;">CUSTOMER PAYMENT</label>
                        
                        <button class="btn-pay" onclick="processPayment('Paid')">
                            <i class="fa-solid fa-check-circle me-2"></i> Customer Paid Now
                        </button>
                        
                        <button class="btn-pay outline" onclick="processPayment('Unpaid')">
                            <i class="fa-solid fa-clock me-2"></i> Add to Open Tab (Pay Later)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <nav class="mobile-bottom-nav">
        <?php if ($hideTopNav): ?>
            <!-- MOBILE NAV FOR CASHIER / WAITRESS -->
            <a href="pos.php" class="mobile-nav-link"><i class="fa-solid fa-cash-register"></i>Rest. POS</a>
            <a href="waitress.php" class="mobile-nav-link active"><i class="fa-solid fa-bell-concierge"></i>Waitress</a>
            <a href="../pos.php" class="mobile-nav-link bar-link"><i class="fa-solid fa-martini-glass"></i>Bar POS</a>
            <a href="../logout.php" class="mobile-nav-link text-danger"><i class="fa-solid fa-power-off"></i>Logout</a>
        <?php else: ?>
            <!-- MOBILE NAV FOR ADMINS / MANAGERS -->
            <a href="index.php" class="mobile-nav-link"><i class="fa-solid fa-chart-line"></i>Dashboard</a>
            <a href="pos.php" class="mobile-nav-link"><i class="fa-solid fa-cash-register"></i>POS</a>
            <a href="kitchen.php" class="mobile-nav-link"><i class="fa-solid fa-fire-burner"></i>Kitchen</a>
            <a href="inventory.php" class="mobile-nav-link"><i class="fa-solid fa-boxes-stacked"></i>Stock</a>
            <a href="admin.php" class="mobile-nav-link"><i class="fa-solid fa-gear"></i>Menu</a>
            <a href="reports.php" class="mobile-nav-link"><i class="fa-solid fa-coins"></i>Revenue</a>
            <a href="waitress.php" class="mobile-nav-link active"><i class="fa-solid fa-bell-concierge"></i>Waitress</a>
            <?php if ($showBarSide): ?>
            <a href="../dashboard.php" class="mobile-nav-link bar-link"><i class="fa-solid fa-arrow-left"></i>Bar Side</a>
            <?php endif; ?>
            <a href="../logout.php" class="mobile-nav-link text-danger"><i class="fa-solid fa-power-off"></i>Logout</a>
        <?php endif; ?>
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
        var availableVoices = [];
        var lastAnnouncedOrder = 0;
        var paymentModalInstance;
        var voiceInterval = null;
        var latestOrdersList = [];

        function loadVoices() { availableVoices = window.speechSynthesis.getVoices(); }
        if (typeof speechSynthesis !== 'undefined' && speechSynthesis.onvoiceschanged !== undefined) {
            speechSynthesis.onvoiceschanged = loadVoices;
        }
        loadVoices();

        document.addEventListener("DOMContentLoaded", function() {
            paymentModalInstance = new bootstrap.Modal(document.getElementById('paymentModal'));
            loadWaitressOrders();
            startLiveUpdates();
        });

        function loadWaitressOrders() {
            fetch('../api/api_restuarant/orders.php')
                .then(function (r) { return r.json(); })
                .then(function (res) { 
                    if (res.status === 'success') {
                        latestOrdersList = res.data;
                        renderWaitressOrders(res.data);
                        if (res.data.length > 0) {
                            var ids = res.data.map(function(o) { return o.order_id; });
                            currentMaxId = Math.max.apply(Math, ids);
                        }
                    } 
                })
                .catch(function () { toast('Could not load orders', 'error'); });
        }

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
                                    latestOrdersList = res.data;
                                    renderWaitressOrders(res.data); 
                                    currentMaxId = data.max_id;
                                }
                            });
                    }
                };
                source.onerror = function() {
                    source.close();
                    setTimeout(function() { startLiveUpdates(); }, 3000);
                };
            } else {
                setInterval(loadWaitressOrders, 7000);
            }
        }

        function vibrate() {
            if ("vibrate" in navigator) {
                navigator.vibrate([200, 100, 200]); 
            }
        }

        function speakMessage(message) {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                var utterance = new SpeechSynthesisUtterance(message);
                utterance.rate = 1.0; // NORMAL VOICE SPEED
                utterance.pitch = 1.0; 
                utterance.volume = 1.0;
                
                if (availableVoices.length === 0) availableVoices = window.speechSynthesis.getVoices();
                
                var maleKeywords = ['david', 'mark', 'george', 'male', 'alex', 'daniel', 'james', 'fred'];
                var maleVoice = availableVoices.find(function(voice) {
                    var vName = voice.name.toLowerCase();
                    return voice.lang.startsWith('en') && maleKeywords.some(function(kw) { return vName.includes(kw); });
                });
                
                if (!maleVoice) {
                    maleVoice = availableVoices.find(function(voice) { 
                        return voice.lang.startsWith('en'); 
                    });
                }
                
                if (maleVoice) utterance.voice = maleVoice;
                window.speechSynthesis.speak(utterance);
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

        function updateMetrics(totalOrders) {
            var deliveredCount = 0;
            var paidCount = 0;
            var unpaidCount = 0;

            totalOrders.forEach(function(o) {
                if (o.order_status === 'Disposed') deliveredCount++;
                if (o.payment_status === 'Paid') paidCount++;
                if (o.payment_status === 'Unpaid') unpaidCount++;
            });

            document.getElementById('delivery-count').innerText = deliveredCount;
            document.getElementById('paid-count').innerText = paidCount;
            document.getElementById('unpaid-count').innerText = unpaidCount;
        }

        function renderWaitressOrders(orders) {
            var readyContainer = document.getElementById('ready-orders-container');
            var pendingContainer = document.getElementById('pending-orders-container');
            var openTabsContainer = document.getElementById('open-tabs-container');
            
            readyContainer.innerHTML = ''; 
            pendingContainer.innerHTML = '';
            openTabsContainer.innerHTML = '';

            var readyOrders = [], pendingOrders = [], openTabs = [];

            orders.forEach(function (order) {
                if (order.order_status === 'Ready') {
                    readyOrders.push(order);
                } else if (order.order_status === 'Received' || order.order_status === 'Cooking') {
                    pendingOrders.push(order);
                } else if (order.order_status === 'Disposed' && order.payment_status === 'Unpaid') {
                    openTabs.push(order);
                }
            });

            readyOrders.sort(function(a, b) { return a.order_id - b.order_id; });
            pendingOrders.sort(function(a, b) { return a.order_id - b.order_id; });
            openTabs.sort(function(a, b) { return a.order_id - b.order_id; });

            updateMetrics(orders);

            if (voiceInterval) { clearInterval(voiceInterval); voiceInterval = null; }

            if (readyOrders.length === 0) {
                readyContainer.innerHTML = '<div class="empty-state"><i class="fa-solid fa-mug-hot"></i><div>No orders ready for delivery right now.</div></div>';
            } else {
                var oldestReady = readyOrders[0];
                
                // VOICE ANNOUNCEMENT: "Order number [ID], is ready for delivery at [Table Number]"
                var table = oldestReady.table_number ? 'Table ' + oldestReady.table_number : 'Counter';
                var msg = "Order number " + oldestReady.order_id + ", is ready for delivery at " + table;
                
                if (lastAnnouncedOrder !== oldestReady.order_id) {
                    lastAnnouncedOrder = oldestReady.order_id;
                    speakMessage(msg);
                    playBeep();
                    vibrate();

                    if (voiceInterval) { clearInterval(voiceInterval); }
                    voiceInterval = setInterval(function() {
                        speakMessage(msg);
                        playBeep();
                        vibrate();
                    }, 15000);
                }

                readyOrders.forEach(function(order) { readyContainer.appendChild(createCard(order, 'ready')); });
            }

            if (pendingOrders.length === 0) {
                pendingContainer.innerHTML = '<div class="empty-state"><i class="fa-solid fa-check-circle"></i><div>No pending orders. All caught up!</div></div>';
            } else {
                pendingOrders.forEach(function(order) { pendingContainer.appendChild(createCard(order, 'pending')); });
            }

            if (openTabs.length === 0) {
                openTabsContainer.innerHTML = '<div class="empty-state"><i class="fa-solid fa-check-circle" style="color:var(--success)"></i><div>No open tabs. All bills settled!</div></div>';
            } else {
                openTabs.forEach(function(order) { openTabsContainer.appendChild(createCard(order, 'tab')); });
            }
        }

        function createCard(order, type) {
            var itemsHtml = order.items.map(function (i) {
                return '<div class="item-row"><span><strong>' + i.quantity + 'x</strong> ' + escapeHtml(i.item_name) + '</span></div>';
            }).join('');

            // DISPLAY Logic: Show Table Name. If no table, show "Paying Now"
            var tableDisplay = order.table_number ? ('Table ' + order.table_number) : 'Paying Now';
            var btn = '';
            
            if (type === 'ready') {
                var isCounter = (order.order_type === 'Counter');
                var btnText = isCounter ? 'Mark as Served' : 'Deliver & Settle';
                btn = '<button class="btn-serve"><i class="fa-solid fa-check-double"></i> ' + btnText + '</button>';
            } else if (type === 'tab') {
                btn = '<button class="btn-settle"><i class="fa-solid fa-money-bill"></i> Settle Bill (UGX ' + Number(order.total_amount).toLocaleString() + ')</button>';
            }

            var badgeClass = '';
            var badgeText = '';
            if (type === 'ready') { badgeClass = 'badge-Ready'; badgeText = 'Ready'; }
            if (type === 'pending') { badgeClass = 'badge-' + order.order_status; badgeText = order.order_status; }
            if (type === 'tab') { badgeClass = 'badge-Unpaid'; badgeText = 'Open Tab'; }

            var col = document.createElement('div');
            col.className = 'status-card ' + (type === 'ready' ? 'ready' : type === 'tab' ? 'open-tab' : 'pending');
            col.innerHTML =
                '<div class="card-head">' +
                    '<span class="order-id">#' + order.order_id + '</span>' +
                    '<span class="badge-status ' + badgeClass + '">' + badgeText + '</span>' +
                '</div>' +
                '<div class="card-body">' +
                    '<div class="meta-row"><span>Customer</span><strong>' + escapeHtml(order.customer_name) + '</strong></div>' +
                    '<div class="meta-row"><span>Location</span><strong>' + tableDisplay + '</strong></div>' +
                    '<div class="items-list">' + itemsHtml + '</div>' +
                    btn +
                    '<div class="view-details-link">View Details <i class="fa-solid fa-chevron-down"></i></div>' +
                '</div>';
            
            if (type === 'ready') {
                var b = col.querySelector('.btn-serve');
                b.onclick = function(e) { 
                    e.stopPropagation();
                    if (order.order_type === 'Counter') {
                        directServe(order.order_id, 'Paid');
                    } else {
                        openPaymentModal(order.order_id, order.total_amount, order.table_number || 0, 'serve'); 
                    }
                };
            } else if (type === 'tab') {
                var b2 = col.querySelector('.btn-settle');
                b2.onclick = function(e) {
                    e.stopPropagation();
                    openPaymentModal(order.order_id, order.total_amount, order.table_number || 0, 'settle');
                };
            }

            var detailsLink = col.querySelector('.view-details-link');
            var openDetailHandler = function(e) {
                e.stopPropagation();
                openOrderDetails(order);
            };
            detailsLink.onclick = openDetailHandler;
            col.querySelector('.card-body').onclick = openDetailHandler;
            col.querySelector('.card-head').onclick = openDetailHandler;

            return col;
        }

        // --- HISTORY LOGIC FOR METRIC CARDS ---
        function openHistoryDetails(type) {
            var historyTitle = "History";
            var filtered = [];
            
            if (type === 'delivered') {
                historyTitle = "Total Delivered Orders";
                filtered = latestOrdersList.filter(function(o) { return o.order_status === 'Disposed'; });
            } else if (type === 'paid') {
                historyTitle = "Total Paid Orders";
                filtered = latestOrdersList.filter(function(o) { return o.payment_status === 'Paid'; });
            } else if (type === 'unpaid') {
                historyTitle = "Open Tabs (Unpaid)";
                filtered = latestOrdersList.filter(function(o) { return o.payment_status === 'Unpaid'; });
            }

            document.getElementById('history-title').innerText = historyTitle;

            var tbody = document.getElementById('history-tbody');
            tbody.innerHTML = '';

            if (filtered.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-muted">No records found.</td></tr>';
            } else {
                // Sort oldest first
                filtered.sort(function(a, b) { return a.order_id - b.order_id; });
                
                filtered.forEach(function(order) {
                    var itemsList = order.items.map(function (i) { return i.quantity + 'x ' + i.item_name; }).join(', ');
                    var tableDisplay = order.table_number ? ('Table ' + order.table_number) : 'Paying Now';

                    var tr = document.createElement('tr');
                    tr.innerHTML = 
                        '<td class="ps-3 fw-bold" style="color: var(--primary);">#' + order.order_id + '<br><small style="color: var(--text-muted); font-weight:400;">' + tableDisplay + '</small></td>' +
                        '<td><small style="color: var(--text-muted);">' + escapeHtml(itemsList) + '</small></td>' +
                        '<td class="text-end pe-3 fw-bold">UGX ' + Number(order.total_amount).toLocaleString() + '</td>';
                    tbody.appendChild(tr);
                });
            }

            document.getElementById('history-overlay').classList.add('open');
        }

        function closeHistoryDetails(e) {
            if (e && e.target !== e.currentTarget) return;
            document.getElementById('history-overlay').classList.remove('open');
        }

        // --- DETAILS LOGIC FOR ACTIVE CARDS ---
        function openOrderDetails(order) {
            document.getElementById('det-order-id').innerText = '#' + order.order_id;
            document.getElementById('det-customer').innerText = order.customer_name;
            document.getElementById('det-table').innerText = order.table_number ? ('Table ' + order.table_number) : 'Paying Now';
            document.getElementById('det-status').innerText = order.order_status + ' / ' + order.payment_status;
            
            var itemsContainer = document.getElementById('detail-items-container');
            itemsContainer.innerHTML = '';
            
            order.items.forEach(function(item) {
                var itemRow = document.createElement('div');
                itemRow.className = 'detail-item';
                itemRow.innerHTML = 
                    '<div>' +
                        '<span class="detail-item-name">' + escapeHtml(item.item_name) + '</span>' +
                        '<span class="detail-item-qty">x' + item.quantity + '</span>' +
                    '</div>' +
                    '<div class="detail-item-subtotal">UGX ' + Number(item.subtotal).toLocaleString() + '</div>';
                itemsContainer.appendChild(itemRow);
            });

            document.getElementById('det-total').innerText = 'UGX ' + Number(order.total_amount).toLocaleString();
            
            // Add action button in details sheet
            var actionContainer = document.getElementById('detail-action-container');
            var actionBtn = document.getElementById('btn-detail-action');
            
            if (order.order_status === 'Ready') {
                actionContainer.style.display = 'block';
                actionBtn.innerHTML = '<i class="fa-solid fa-check-double"></i> Deliver & Settle';
                actionBtn.onclick = function(e) {
                    e.stopPropagation();
                    closeDetails();
                    if (order.order_type === 'Counter') {
                        directServe(order.order_id, 'Paid');
                    } else {
                        openPaymentModal(order.order_id, order.total_amount, order.table_number || 0, 'serve');
                    }
                };
            } else if (order.order_status === 'Disposed' && order.payment_status === 'Unpaid') {
                actionContainer.style.display = 'block';
                actionBtn.innerHTML = '<i class="fa-solid fa-money-bill"></i> Settle Bill';
                actionBtn.onclick = function(e) {
                    e.stopPropagation();
                    closeDetails();
                    openPaymentModal(order.order_id, order.total_amount, order.table_number || 0, 'settle');
                };
            } else {
                actionContainer.style.display = 'none';
            }

            document.getElementById('detail-overlay').classList.add('open');
        }

        function closeDetails(e) {
            if (e && e.target !== e.currentTarget) return;
            document.getElementById('detail-overlay').classList.remove('open');
        }

        function directServe(orderId, paymentStatus) {
            if (voiceInterval) { clearInterval(voiceInterval); voiceInterval = null; }
            
            fetch('../api/api_restuarant/orders.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    order_id: orderId, 
                    order_status: 'Disposed',
                    payment_status: paymentStatus 
                })
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data.status === 'success') {
                    toast('Order delivered', 'success');
                    loadWaitressOrders();
                } else {
                    toast(data.message || 'Update failed', 'error');
                }
            }).catch(function () { toast('Network error', 'error'); });
        }

        function openPaymentModal(orderId, total, table, actionType) {
            if (voiceInterval) { clearInterval(voiceInterval); voiceInterval = null; }
            document.getElementById('pay-order-id').value = orderId;
            document.getElementById('pay-action-type').value = actionType;
            document.getElementById('modal-order-id').innerText = orderId;
            document.getElementById('modal-total').innerText = Number(total).toLocaleString();
            document.getElementById('modal-table').innerText = table > 0 ? table : 'Paying Now';
            paymentModalInstance.show();
        }

        function closePaymentModal() {
            paymentModalInstance.hide();
            loadWaitressOrders(); 
        }

        function processPayment(paymentStatus) {
            var orderId = parseInt(document.getElementById('pay-order-id').value, 10);
            var actionType = document.getElementById('pay-action-type').value;
            paymentModalInstance.hide();

            var payload = { 
                order_id: orderId, 
                payment_status: paymentStatus 
            };
            
            if (actionType === 'serve') {
                payload.order_status = 'Disposed';
            }

            fetch('../api/api_restuarant/orders.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data.status === 'success') {
                    var msg = paymentStatus === 'Paid' ? 'Payment received' : 'Order delivered (Open Tab)';
                    toast(msg, 'success');
                    loadWaitressOrders();
                } else {
                    toast(data.message || 'Update failed', 'error');
                }
            }).catch(function () {
                toast('Network error', 'error');
            });
        }
    </script>
</body>
</html>