<?php
// bms/restuarant/pos.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Counter POS &amp; Ordering</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #1A1C1E; --sidebar: #151719; --surface: #232629; --surface-2: #2E3236;
            --border: #3A3F45; --text: #E2E8F0; --text-muted: #94A3B8;
            --primary: #D4A373; --primary-hover: #E2B587;
            --success: #4ADE80; --warning: #FBBF24; --danger: #F87171;
            --active-green: #4ADE80;
        }
        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg); color: var(--text); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px) + 80px); -webkit-tap-highlight-color: transparent; }
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
        .card-custom { background-color: var(--surface); border: 1px solid var(--border); border-radius: 12px; }
        .menu-card { background: linear-gradient(145deg, var(--surface-2), var(--surface)); border: 1px solid var(--border); border-radius: 10px; cursor: pointer; transition: all 0.15s ease; height: 100%; padding: 1.25rem; }
        .menu-card:hover { border-color: var(--primary); transform: translateY(-2px); }
        .menu-card:active { transform: scale(0.98); }
        
        /* FORMS & PLACEHOLDERS */
        .form-control, .form-select { 
            background-color: var(--sidebar); 
            border: 1px solid var(--border); 
            color: #FFFFFF; 
            border-radius: 8px; 
            padding: 0.6rem 0.9rem; 
        }
        .form-control::placeholder { 
            color: #94A3B8 !important; 
            opacity: 1; 
        }
        .form-control:focus, .form-select:focus { 
            background-color: var(--sidebar); 
            border-color: var(--primary); 
            color: #FFFFFF; 
            box-shadow: 0 0 0 3px rgba(212, 163, 115, 0.15); 
        }
        .form-label { font-size: 0.82rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        
        .btn-brand { background-color: var(--primary); color: var(--sidebar); font-weight: 700; font-size: 0.9rem; padding: 0.7rem 1.25rem; border-radius: 8px; border: none; width: 100%; }
        .btn-brand:hover { background-color: var(--primary-hover); color: var(--sidebar); }
        .btn-brand:disabled { opacity: .6; }
        .table-custom { width: 100%; margin-bottom: 0; color: var(--text); border-collapse: collapse; }
        .table-custom th { background-color: var(--surface-2); color: var(--text-muted); font-weight: 700; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); }
        .table-custom td { padding: 0.85rem 1rem; vertical-align: middle; border-bottom: 1px solid var(--border); font-size: 0.85rem; }
        .btn-remove { background: none; border: 0; color: var(--danger); font-size: 13px; font-weight: 600; padding: 4px 6px; cursor: pointer; }

        /* SEARCH & FILTER UI */
        .search-wrapper { position: relative; margin-bottom: 1rem; }
        .search-wrapper i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .search-wrapper input { padding-left: 40px; border-radius: 10px; height: 48px; }
        
        /* SCROLLABLE CATEGORY FILTER */
        .category-scroll { 
            display: flex; 
            gap: 8px; 
            overflow-x: auto; 
            padding-bottom: 10px; 
            margin-bottom: 1.25rem; 
            scroll-behavior: smooth;
            
            /* Show the scrollbar clearly on desktop */
            scrollbar-width: thin;
            scrollbar-color: var(--border) var(--surface);
        }
        
        /* Custom scrollbar for Chrome, Edge, Safari */
        .category-scroll::-webkit-scrollbar {
            height: 6px;
        }
        .category-scroll::-webkit-scrollbar-track {
            background: var(--surface);
            border-radius: 10px;
        }
        .category-scroll::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 10px;
        }
        .category-scroll::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }
        
        .category-chip { 
            background: var(--surface-2); color: var(--text-muted); border: 1px solid var(--border); 
            padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; 
            white-space: nowrap; cursor: pointer; transition: 0.2s;
            user-select: none;
        }
        .category-chip:hover { border-color: var(--primary); color: var(--text); }
        .category-chip.active { background: var(--primary); color: var(--sidebar); border-color: var(--primary); }

        /* Mobile cart bar */
        .cart-bar { position: fixed; left: 12px; right: 12px; bottom: calc(70px + env(safe-area-inset-bottom, 0px) + 10px); height: 56px; background: var(--primary); color: var(--sidebar); border-radius: 14px; display: none; align-items: center; gap: 12px; padding: 0 16px; box-shadow: 0 10px 30px rgba(212,163,115,.35); z-index: 1040; cursor: pointer; -webkit-tap-highlight-color: transparent; }
        .cart-bar-left { display: flex; align-items: center; gap: 10px; }
        .cart-count { background: var(--sidebar); color: var(--primary); border-radius: 10px; min-width: 24px; height: 24px; font-size: 12px; font-weight: 800; display: flex; align-items: center; justify-content: center; padding: 0 6px; }
        .cart-bar-middle { flex: 1; display: flex; flex-direction: column; line-height: 1.1; }
        .cart-bar-middle small { font-size: 11px; opacity: .7; font-weight: 600; }
        .cart-bar-middle strong { font-size: 14px; font-weight: 800; }
        .cart-bar .chev { font-size: 13px; transition: transform .2s ease; }
        .cart-bar.is-open .chev { transform: rotate(180deg); }

        /* Bottom sheet */
        .sheet-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.55); z-index: 1051; display: none; align-items: flex-end; }
        .sheet-overlay.open { display: flex; animation: fadein .15s ease; }
        .bottom-sheet { background: var(--surface); width: 100%; border-radius: 20px 20px 0 0; padding: 12px 16px calc(20px + env(safe-area-inset-bottom, 0px)); max-height: 88vh; overflow-y: auto; animation: slideup .25s ease; }
        @keyframes fadein { from { opacity: 0 } }
        @keyframes slideup { from { transform: translateY(100%) } }
        .sheet-handle { width: 40px; height: 4px; border-radius: 2px; background: var(--border); margin: 0 auto 12px; }
        .bottom-sheet h3 { font-size: 16px; font-weight: 800; margin: 0 0 14px; }
        
        /* BOTTOM SHEET FORMS & PLACEHOLDERS */
        .bottom-sheet input, .bottom-sheet select { 
            width: 100%; height: 44px; margin-bottom: 10px; background: var(--sidebar); 
            border: 1px solid var(--border); color: #FFFFFF; border-radius: 10px; padding: 0 12px; 
        }
        .bottom-sheet input::placeholder { color: #94A3B8 !important; opacity: 1; }
        
        .sheet-total { display: flex; justify-content: space-between; padding: 14px 0; border-top: 1px solid var(--border); font-size: 15px; font-weight: 700; }
        .sheet-total strong { color: var(--primary); font-size: 18px; font-weight: 800; }

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

        @media (min-width: 992px) {
            .mobile-bottom-nav, .cart-bar, .sheet-overlay { display: none !important; }
            body { padding-bottom: 0; }
        }
        @media (max-width: 991.98px) {
            .app-header { display: none; }
            .mobile-bottom-nav { display: flex; }
            .cart-bar { display: flex; }
            .main-container { padding: 1rem; padding-bottom: 90px; }
            .desktop-cart { display: none; }
            .menu-card { padding: 1rem; }
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
                <li><a class="nav-link" href="index.php">Dashboard</a></li>
                <li><a class="nav-link active" href="pos.php">Counter POS</a></li>
                <li><a class="nav-link" href="kitchen.php">Kitchen KDS</a></li>
                <li><a class="nav-link" href="inventory.php">Inventory</a></li>
                <li><a class="nav-link" href="admin.php">Menu &amp; Prices</a></li>
                <li><a class="nav-link" href="reports.php">Revenue</a></li>
                <li><a class="nav-link" href="waitress.php">Waitress</a></li>
                <li class="ms-2"><a class="nav-link bar-link" href="../dashboard.php">Bar Side &rarr;</a></li>
            </ul>
        </div>
    </header>

    <main class="main-container">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card-custom p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-3 text-uppercase" style="color: var(--text-muted); font-size: 0.78rem; letter-spacing: 0.1em;">Available Menu</h2>
                    
                    <!-- SEARCH BAR -->
                    <div class="search-wrapper">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="menu-search" class="form-control" placeholder="Search menu items...">
                    </div>

                    <!-- CATEGORY FILTERS -->
                    <div class="category-scroll" id="category-filters">
                        <div class="category-chip active" data-cat="all">All Items</div>
                        <!-- Options populated by JS -->
                    </div>

                    <div id="menu-container" class="row g-2 g-md-3">
                        <div class="col-12 text-center py-5 text-muted">Loading menu items...</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 desktop-cart">
                <div class="card-custom p-4 sticky-top" style="top: 85px;">
                    <h2 class="h5 fw-bold mb-3 text-uppercase" style="color: var(--text-muted); font-size: 0.78rem; letter-spacing: 0.1em;">Current Order</h2>
                    <div class="mb-3">
                        <label class="form-label">Customer Name *</label>
                        <input type="text" id="customer-name" class="form-control" placeholder="e.g. Okuta Joshua" autocomplete="name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign Table</label>
                        <select id="table-select" class="form-select"></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Order Type</label>
                        <select id="order-type" class="form-select">
                            <option value="Counter">Paying Now</option>
                            <option value="Dine-In">Paying Later</option>
                        </select>
                    </div>
                    <div class="table-responsive mb-3" style="max-height: 220px; overflow-y: auto;">
                        <table class="table-custom">
                            <thead>
                                <tr><th>Item</th><th style="width: 70px;">Qty</th><th class="text-end">Subtotal</th><th></th></tr>
                            </thead>
                            <tbody id="cart-tbody">
                                <tr><td colspan="4" class="text-center text-muted py-3">Cart is empty</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 pt-2 border-top" style="border-color: var(--border) !important;">
                        <span class="fw-bold text-muted text-uppercase" style="font-size: 0.78rem;">Total:</span>
                        <h4 class="fw-bold mb-0" style="color: var(--primary);" id="cart-total">UGX 0</h4>
                    </div>
                    <button class="btn-brand" id="submit-btn" onclick="submitOrder(false)">Submit Order to Kitchen</button>
                </div>
            </div>
        </div>
    </main>

    <div class="cart-bar" id="cart-bar" onclick="toggleCartSheet()">
        <div class="cart-bar-left">
            <span class="cart-count" id="cart-count">0</span>
            <span style="font-weight: 800; font-size: 13px;">Items</span>
        </div>
        <div class="cart-bar-middle">
            <small id="cart-bar-hint">Tap to review</small>
            <strong id="cart-total-bar">UGX 0</strong>
        </div>
        <i class="fa-solid fa-chevron-up chev"></i>
    </div>

    <div class="sheet-overlay" id="cart-overlay" onclick="closeCartSheet(event)">
        <div class="bottom-sheet" onclick="event.stopPropagation()">
            <div class="sheet-handle"></div>
            <h3>Review Order</h3>

            <label class="form-label">Customer Name *</label>
            <input type="text" id="customer-name-m" placeholder="Customer name" autocomplete="name">

            <label class="form-label">Assign Table</label>
            <select id="table-select-m"></select>

            <label class="form-label">Order Type</label>
            <select id="order-type-m">
                <option value="Counter">Paying Now</option>
                <option value="Dine-In">Paying Later</option>
            </select>

            <div class="table-responsive mb-2" style="max-height: 220px; overflow-y: auto;">
                <table class="table-custom">
                    <thead><tr><th>Item</th><th style="width:70px;">Qty</th><th class="text-end">Subtotal</th><th></th></tr></thead>
                    <tbody id="cart-tbody-m"><tr><td colspan="4" class="text-center text-muted py-3">Cart is empty</td></tr></tbody>
                </table>
            </div>

            <div class="sheet-total">
                <span>Total</span>
                <strong id="cart-total-m">UGX 0</strong>
            </div>

            <button class="btn-brand" id="submit-btn-m" onclick="submitOrder(true)">Send to Kitchen</button>
        </div>
    </div>

    <nav class="mobile-bottom-nav">
        <a href="index.php" class="mobile-nav-link"><i class="fa-solid fa-chart-line"></i>Dashboard</a>
        <a href="pos.php" class="mobile-nav-link active"><i class="fa-solid fa-cash-register"></i>POS</a>
        <a href="kitchen.php" class="mobile-nav-link"><i class="fa-solid fa-fire-burner"></i>Kitchen</a>
        <a href="inventory.php" class="mobile-nav-link"><i class="fa-solid fa-boxes-stacked"></i>Stock</a>
        <a href="admin.php" class="mobile-nav-link"><i class="fa-solid fa-gear"></i>Menu</a>
        <a href="reports.php" class="mobile-nav-link"><i class="fa-solid fa-coins"></i>Revenue</a>
        <a href="waitress.php" class="mobile-nav-link"><i class="fa-solid fa-bell-concierge"></i>Waitress</a>
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

        var menuItems = [], cart = [], tables = [];
        var currentCategory = 'all';
        var currentSearch = '';

        document.addEventListener("DOMContentLoaded", function() {
            loadMenu();
            loadTables();
            
            // Sync customer inputs
            document.getElementById('customer-name').addEventListener('input', function () {
                document.getElementById('customer-name-m').value = this.value;
            });
            document.getElementById('customer-name-m').addEventListener('input', function () {
                document.getElementById('customer-name').value = this.value;
            });

            // Search listener
            document.getElementById('menu-search').addEventListener('input', function() {
                currentSearch = this.value.toLowerCase().trim();
                filterAndRenderMenu();
            });
        });

        function toggleCartSheet() {
            var ov = document.getElementById('cart-overlay');
            var bar = document.getElementById('cart-bar');
            if (ov.classList.contains('open')) {
                closeCartSheet();
            } else {
                document.getElementById('customer-name-m').value = document.getElementById('customer-name').value;
                ov.classList.add('open');
                bar.classList.add('is-open');
                document.getElementById('cart-bar-hint').innerText = 'Tap to hide';
            }
        }

        function closeCartSheet(e) {
            if (e && e.target !== e.currentTarget) return;
            document.getElementById('cart-overlay').classList.remove('open');
            var bar = document.getElementById('cart-bar');
            bar.classList.remove('is-open');
            document.getElementById('cart-bar-hint').innerText = 'Tap to review';
        }

        function loadMenu() {
            fetch('../api/api_restuarant/menu.php')
                .then(function (r) { return r.json(); })
                .then(function (res) { 
                    if (res.status === 'success') { 
                        menuItems = res.data; 
                        buildCategoryFilters();
                        filterAndRenderMenu(); 
                    } 
                })
                .catch(function () { toast('Could not load menu', 'error'); });
        }

        function loadTables() {
            fetch('../api/api_restuarant/tables.php')
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.status !== 'success') return;
                    tables = res.data;
                    var opts = '<option value="">Counter / No Table</option>';
                    tables.forEach(function (t) {
                        var extra = t.status === 'Occupied' ? (' (Occupied' + (t.current_customer_name ? ' by ' + t.current_customer_name : '') + ')') : '';
                        opts += '<option value="' + t.table_id + '">Table ' + t.table_number + extra + '</option>';
                    });
                    document.getElementById('table-select').innerHTML = opts;
                    document.getElementById('table-select-m').innerHTML = opts;
                })
                .catch(function () {});
        }

        function buildCategoryFilters() {
            var categories = ['all'];
            menuItems.forEach(function(item) {
                if (!categories.includes(item.category)) {
                    categories.push(item.category);
                }
            });

            var container = document.getElementById('category-filters');
            container.innerHTML = '';
            categories.forEach(function(cat) {
                var chip = document.createElement('div');
                chip.className = 'category-chip' + (cat === currentCategory ? ' active' : '');
                chip.innerText = cat === 'all' ? 'All Items' : cat;
                chip.onclick = function() {
                    currentCategory = cat;
                    document.querySelectorAll('.category-chip').forEach(function(c) { c.classList.remove('active'); });
                    chip.classList.add('active');
                    filterAndRenderMenu();
                };
                container.appendChild(chip);
            });
        }

        function filterAndRenderMenu() {
            var filtered = menuItems.filter(function(item) {
                var matchCategory = (currentCategory === 'all' || item.category === currentCategory);
                var matchSearch = item.item_name.toLowerCase().includes(currentSearch);
                return matchCategory && matchSearch;
            });
            renderMenu(filtered);
        }

        function renderMenu(items) {
            var container = document.getElementById('menu-container');
            container.innerHTML = '';
            if (items.length === 0) {
                container.innerHTML = '<div class="col-12 text-center py-4 text-muted">No items match your search.</div>';
                return;
            }
            items.forEach(function (item) {
                var col = document.createElement('div');
                col.className = 'col-6 col-md-6';
                col.innerHTML =
                    '<div class="menu-card" data-id="' + item.item_id + '">' +
                        '<span class="badge" style="background-color: var(--surface); color: var(--text-muted); font-size: 10px; padding: 3px 8px; border-radius: 6px; margin-bottom: 6px; display:inline-block;">' + escapeHtml(item.category) + '</span>' +
                        '<div class="fw-bold mb-1" style="color: var(--text); font-size: 0.92rem;">' + escapeHtml(item.item_name) + '</div>' +
                        '<div class="fw-bold" style="color: var(--primary); font-size: 0.9rem;">UGX ' + Number(item.price).toLocaleString() + '</div>' +
                    '</div>';
                container.appendChild(col);
            });
            container.querySelectorAll('.menu-card').forEach(function (c) {
                c.onclick = function () { addToCart(parseInt(c.getAttribute('data-id'), 10)); };
            });
        }

        function addToCart(itemId) {
            var item = menuItems.find(function (m) { return m.item_id == itemId; });
            if (!item) return;
            var existing = cart.find(function (c) { return c.item_id == itemId; });
            if (existing) {
                existing.quantity++;
                existing.subtotal = existing.quantity * existing.unit_price;
            } else {
                cart.push({ item_id: item.item_id, item_name: item.item_name, unit_price: Number(item.price), quantity: 1, subtotal: Number(item.price) });
            }
            renderCart();
            toast(item.item_name + ' added', 'success', 1500);
        }

        function updateQuantity(itemId, qty) {
            var q = parseInt(qty, 10);
            var item = cart.find(function (c) { return c.item_id == itemId; });
            if (!item) return;
            if (isNaN(q) || q <= 0) { removeFromCart(itemId); return; }
            item.quantity = q;
            item.subtotal = item.quantity * item.unit_price;
            renderCart();
        }

        function removeFromCart(itemId) {
            cart = cart.filter(function (c) { return c.item_id != itemId; });
            renderCart();
        }

        function renderCart() {
            var total = 0;
            var rowsHtml = '';
            if (cart.length === 0) {
                rowsHtml = '<tr><td colspan="4" class="text-center text-muted py-3">Cart is empty</td></tr>';
            } else {
                cart.forEach(function (c) {
                    total += c.subtotal;
                    rowsHtml +=
                        '<tr>' +
                            '<td class="fw-semibold">' + escapeHtml(c.item_name) + '</td>' +
                            '<td><input type="number" class="form-control form-control-sm" style="padding: 4px 8px; min-height: 32px;" value="' + c.quantity + '" min="1" data-qty="' + c.item_id + '"></td>' +
                            '<td class="fw-bold text-end">UGX ' + c.subtotal.toLocaleString() + '</td>' +
                            '<td class="text-end"><button class="btn-remove" data-del="' + c.item_id + '">&times;</button></td>' +
                        '</tr>';
                });
            }

            document.getElementById('cart-tbody').innerHTML = rowsHtml;
            document.getElementById('cart-tbody-m').innerHTML = rowsHtml;
            document.getElementById('cart-total').innerText = 'UGX ' + total.toLocaleString();
            document.getElementById('cart-total-m').innerText = 'UGX ' + total.toLocaleString();
            document.getElementById('cart-total-bar').innerText = 'UGX ' + total.toLocaleString();
            document.getElementById('cart-count').innerText = cart.reduce(function (s, c) { return s + c.quantity; }, 0);

            document.querySelectorAll('[data-qty]').forEach(function (inp) {
                inp.onchange = function () { updateQuantity(parseInt(inp.getAttribute('data-qty'), 10), inp.value); };
            });
            document.querySelectorAll('[data-del]').forEach(function (b) {
                b.onclick = function () { removeFromCart(parseInt(b.getAttribute('data-del'), 10)); };
            });
        }

        function submitOrder(fromMobile) {
            var nameEl = fromMobile ? document.getElementById('customer-name-m') : document.getElementById('customer-name');
            var tableEl = fromMobile ? document.getElementById('table-select-m') : document.getElementById('table-select');
            var typeEl = fromMobile ? document.getElementById('order-type-m') : document.getElementById('order-type');
            var btn = fromMobile ? document.getElementById('submit-btn-m') : document.getElementById('submit-btn');

            var customerName = nameEl.value.trim();
            var tableId = tableEl.value;
            var orderType = typeEl.value;

            if (!customerName) { toast('Please enter the customer name', 'warning'); nameEl.focus(); return; }
            if (cart.length === 0) { toast('Please add at least one item', 'warning'); return; }

            btn.disabled = true;
            var original = btn.innerText;
            btn.innerText = 'Sending...';

            fetch('../api/api_restuarant/orders.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    customer_name: customerName,
                    table_id: tableId ? parseInt(tableId, 10) : null,
                    order_type: orderType,
                    items: cart.map(function (c) { return { item_id: c.item_id, quantity: c.quantity }; })
                })
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data.status === 'success') {
                    toast('Order #' + data.order_id + ' sent to kitchen', 'success');
                    cart = [];
                    document.getElementById('customer-name').value = '';
                    document.getElementById('customer-name-m').value = '';
                    document.getElementById('table-select').value = '';
                    document.getElementById('table-select-m').value = '';
                    renderCart();
                    closeCartSheet();
                    loadTables();
                } else {
                    toast(data.message || 'Could not place order', 'error');
                }
            }).catch(function () {
                toast('Network error. Please try again.', 'error');
            }).finally(function () {
                btn.disabled = false;
                btn.innerText = original;
            });
        }
    </script>
</body>
</html>