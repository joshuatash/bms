<?php
// bms/restuarant/admin.php
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
    <title>Menu &amp; Price Management</title>
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

        .card-custom { background-color: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
        .table-custom { width: 100%; margin-bottom: 0; color: var(--text); border-collapse: collapse; }
        .table-custom th { background-color: var(--surface-2); color: var(--text-muted); font-weight: 700; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; padding: 0.85rem 1.25rem; border-bottom: 1px solid var(--border); }
        .table-custom td { padding: 1rem 1.25rem; vertical-align: middle; border-bottom: 1px solid var(--border); font-size: 0.88rem; }
        .form-control, .form-select { background-color: var(--sidebar); border: 1px solid var(--border); color: var(--text); border-radius: 8px; padding: 0.6rem 0.9rem; }
        .form-control:focus, .form-select:focus { background-color: var(--sidebar); border-color: var(--primary); color: var(--text); box-shadow: 0 0 0 3px rgba(212, 163, 115, 0.15); }
        .form-label { font-size: 0.82rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .modal-content { background-color: var(--surface); border: 1px solid var(--border); color: var(--text); border-radius: 12px; }
        .modal-header, .modal-footer { border-color: var(--border) !important; }
        .btn-close { filter: invert(1); }
        .btn-brand { background-color: var(--primary); color: var(--sidebar); font-weight: 700; font-size: 0.85rem; padding: 0.55rem 1.25rem; border-radius: 8px; border: none; }
        .btn-brand:hover { background-color: var(--primary-hover); color: var(--sidebar); }
        .btn-brand:disabled { opacity: .6; }
        .btn-subtle { background-color: var(--surface-2); color: var(--text); border: 1px solid var(--border); font-weight: 600; font-size: 0.78rem; padding: 0.35rem 0.85rem; border-radius: 6px; }
        .btn-subtle:hover { background-color: var(--border); color: var(--text); }
        .btn-action-edit { background-color: rgba(96, 165, 250, 0.12); color: var(--blue); border: 1px solid rgba(96, 165, 250, 0.25); font-weight: 600; font-size: 0.75rem; padding: 0.35rem 0.8rem; border-radius: 6px; }
        .btn-action-del { background-color: rgba(248, 113, 113, 0.12); color: var(--danger); border: 1px solid rgba(248, 113, 113, 0.25); font-weight: 600; font-size: 0.75rem; padding: 0.35rem 0.8rem; border-radius: 6px; }
        .badge-cat { background-color: var(--surface-2); color: var(--text-muted); padding: 4px 10px; border-radius: 6px; font-size: 11px; }

        /* Mobile menu cards */
        .menu-item-card { background: var(--surface); border-radius: 12px; padding: 14px; margin-bottom: 10px; border-left: 4px solid var(--primary); }
        .menu-item-id { color: var(--primary); font-weight: 700; font-size: 12px; }
        .menu-item-name { font-weight: 700; font-size: 15px; color: var(--text); }
        .menu-item-cat { font-size: 11px; color: var(--text-muted); margin-bottom: 8px; }
        .menu-item-price { font-size: 16px; font-weight: 800; color: var(--primary); margin-bottom: 12px; }
        .menu-item-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .menu-item-actions button { height: 42px; border-radius: 8px; font-size: 13px; font-weight: 700; }

        .fab { position: fixed; right: 16px; bottom: calc(70px + env(safe-area-inset-bottom, 0px) + 80px); width: 56px; height: 56px; border-radius: 50%; background: var(--primary); color: var(--sidebar); box-shadow: 0 8px 20px rgba(212,163,115,.4); font-size: 22px; border: 0; z-index: 1040; display: none; align-items: center; justify-content: center; }
        .fab:active { transform: scale(0.94); }

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

        .confirm-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.55); display: flex; align-items: center; justify-content: center; padding: 20px; z-index: 10000; }
        .confirm-overlay[hidden] { display: none; }
        .confirm-card { background: var(--surface); color: var(--text); border-radius: 16px; padding: 20px; max-width: 340px; width: 100%; }
        .confirm-card h3 { font-size: 16px; font-weight: 700; margin: 0 0 6px; }
        .confirm-card p { font-size: 14px; color: var(--text-muted); margin: 0 0 20px; }
        .confirm-actions { display: flex; gap: 10px; }
        .confirm-actions button { flex: 1; height: 44px; border-radius: 10px; font-weight: 700; font-size: 14px; border: 0; cursor: pointer; }
        .btn-flat { background: var(--surface-2); color: var(--text); }

        @media (min-width: 992px) {
            .mobile-bottom-nav, .fab { display: none !important; }
            body { padding-bottom: 0; }
            .mobile-menu { display: none; }
        }
        @media (max-width: 991.98px) {
            .app-header { display: none; }
            .mobile-bottom-nav { display: flex; }
            .fab { display: flex; }
            body { padding-bottom: calc(75px + env(safe-area-inset-bottom, 0px)); }
            .main-container { padding: 1rem; }
            .desktop-menu { display: none; }
            .add-btn-top { display: none; }
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
                <h1 class="h3 fw-bold mb-1" style="color: var(--text);">Menu &amp; Live Price Control</h1>
                
                <!-- NEW ATTENTION GRABBING BADGE -->
                <div class="sub-outstanding">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    Add dishes or update prices. Active tabs remain locked to original order prices.
                </div>
                
            </div>
            <div class="add-btn-top">
                <button class="btn-brand" data-bs-toggle="modal" data-bs-target="#addMenuModal"><i class="fa-solid fa-plus me-1"></i> Add Menu Item</button>
            </div>
        </div>

        <div class="card-custom desktop-menu mt-4">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background-color: var(--sidebar) !important; border-color: var(--border) !important;">
                <h5 class="mb-0 fw-bold text-uppercase" style="font-size: 0.78rem; letter-spacing: 0.08em; color: var(--text-muted);">Active Restaurant Menu</h5>
                <button class="btn-subtle" onclick="loadMenu()">Refresh Menu</button>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr><th class="ps-4">ID</th><th>Dish / Item Name</th><th>Category</th><th>Current Price (UGX)</th><th class="text-end pe-4">Actions</th></tr>
                    </thead>
                    <tbody id="menu-tbody">
                        <tr><td colspan="5" class="text-center py-5 text-muted">Loading menu items...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mobile-menu mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fw-bold text-uppercase" style="font-size: 0.78rem; letter-spacing: 0.08em; color: var(--text-muted); margin: 0;">Active Menu</h2>
                <button class="btn-subtle" onclick="loadMenu()">Refresh</button>
            </div>
            <div id="menu-mobile"></div>
        </div>
    </main>

    <button class="fab" data-bs-toggle="modal" data-bs-target="#addMenuModal" aria-label="Add"><i class="fa-solid fa-plus"></i></button>

    <div class="modal fade" id="addMenuModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold fs-6 text-uppercase">Add New Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Item / Dish Name</label><input type="text" id="add-item-name" class="form-control" placeholder="e.g. Fried Chicken & Chips"></div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select id="add-item-category" class="form-select">
                            <option value="Main Meals">Main Meals</option>
                            <option value="Breakfast & Snacks">Breakfast &amp; Snacks</option>
                            <option value="Grills & Roasts">Grills &amp; Roasts</option>
                            <option value="Sides & Extras">Sides &amp; Extras</option>
                            <option value="Soft Drinks & Juices">Soft Drinks &amp; Juices</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Selling Price (UGX)</label><input type="number" step="100" id="add-item-price" class="form-control" placeholder="15000"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-subtle" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-brand" id="add-menu-btn" onclick="addMenuItem()">Save Item</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editMenuModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold fs-6 text-uppercase">Edit Menu Item &amp; Price</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-item-id">
                    <div class="mb-3"><label class="form-label">Item / Dish Name</label><input type="text" id="edit-item-name" class="form-control"></div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select id="edit-item-category" class="form-select">
                            <option value="Main Meals">Main Meals</option>
                            <option value="Breakfast & Snacks">Breakfast &amp; Snacks</option>
                            <option value="Grills & Roasts">Grills &amp; Roasts</option>
                            <option value="Sides & Extras">Sides &amp; Extras</option>
                            <option value="Soft Drinks & Juices">Soft Drinks &amp; Juices</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Updated Price (UGX)</label><input type="number" step="100" id="edit-item-price" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-subtle" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-brand" id="edit-menu-btn" onclick="saveMenuEdit()">Update Price</button>
                </div>
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

    <div id="confirm-overlay" class="confirm-overlay" hidden>
        <div class="confirm-card" role="dialog" aria-modal="true">
            <h3 id="confirm-title">Are you sure?</h3>
            <p id="confirm-message"></p>
            <div class="confirm-actions">
                <button class="btn-flat" id="confirm-cancel">Cancel</button>
                <button class="btn-brand" id="confirm-ok" style="width:auto;">Confirm</button>
            </div>
        </div>
    </div>

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
        function confirmDialog(opts) {
            opts = opts || {};
            return new Promise(function (resolve) {
                var ov = document.getElementById('confirm-overlay');
                ov.querySelector('#confirm-title').textContent = opts.title || 'Confirm';
                ov.querySelector('#confirm-message').textContent = opts.message || '';
                var ok = ov.querySelector('#confirm-ok');
                ok.textContent = opts.okText || 'Confirm';
                ok.style.background = opts.danger ? '#dc2626' : '#D4A373';
                ok.style.color = opts.danger ? '#fff' : '#151719';
                ov.hidden = false;
                function close(val) { ov.hidden = true; resolve(val); }
                ov.querySelector('#confirm-ok').onclick = function () { close(true); };
                ov.querySelector('#confirm-cancel').onclick = function () { close(false); };
                ov.onclick = function (e) { if (e.target === ov) close(false); };
            });
        }

        var addMenuModal, editMenuModal;

        document.addEventListener("DOMContentLoaded", function() {
            addMenuModal = new bootstrap.Modal(document.getElementById('addMenuModal'));
            editMenuModal = new bootstrap.Modal(document.getElementById('editMenuModal'));
            loadMenu();
        });

        function loadMenu() {
            fetch('../api/api_restuarant/menu.php')
                .then(function (r) { return r.json(); })
                .then(function (res) { if (res.status === 'success') renderMenuTable(res.data); })
                .catch(function () { toast('Could not load menu', 'error'); });
        }

        function renderMenuTable(items) {
            var tbody = document.getElementById('menu-tbody');
            var mobile = document.getElementById('menu-mobile');
            tbody.innerHTML = '';
            mobile.innerHTML = '';

            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">No menu items yet.</td></tr>';
                mobile.innerHTML = '<div class="text-center py-5 text-muted">No menu items yet.</div>';
                return;
            }

            items.forEach(function (item) {
                var tr = document.createElement('tr');
                tr.innerHTML =
                    '<td class="ps-4 fw-bold" style="color: var(--primary);">#' + item.item_id + '</td>' +
                    '<td class="fw-bold" style="color: var(--text);">' + escapeHtml(item.item_name) + '</td>' +
                    '<td><span class="badge-cat">' + escapeHtml(item.category) + '</span></td>' +
                    '<td class="fw-bold" style="color: var(--text);">UGX ' + Number(item.price).toLocaleString() + '</td>' +
                    '<td class="text-end pe-4">' +
                        '<button class="btn btn-sm btn-action-edit me-1" data-edit="' + item.item_id + '">Edit Price</button>' +
                        '<button class="btn btn-sm btn-action-del" data-del="' + item.item_id + '" data-name="' + escapeHtml(item.item_name) + '">Remove</button>' +
                    '</td>';
                tbody.appendChild(tr);

                var card = document.createElement('div');
                card.className = 'menu-item-card';
                card.innerHTML =
                    '<div class="menu-item-id">#' + item.item_id + '</div>' +
                    '<div class="menu-item-name">' + escapeHtml(item.item_name) + '</div>' +
                    '<div class="menu-item-cat">' + escapeHtml(item.category) + '</div>' +
                    '<div class="menu-item-price">UGX ' + Number(item.price).toLocaleString() + '</div>' +
                    '<div class="menu-item-actions">' +
                        '<button class="btn-action-edit" data-edit="' + item.item_id + '">Edit</button>' +
                        '<button class="btn-action-del" data-del="' + item.item_id + '" data-name="' + escapeHtml(item.item_name) + '">Remove</button>' +
                    '</div>';
                mobile.appendChild(card);
            });

            document.querySelectorAll('[data-edit]').forEach(function (b) {
                var id = parseInt(b.getAttribute('data-edit'), 10);
                var item = items.find(function (i) { return i.item_id === id; });
                b.onclick = function () { openEditModal(item); };
            });
            document.querySelectorAll('[data-del]').forEach(function (b) {
                b.onclick = function () {
                    deactivateItem(parseInt(b.getAttribute('data-del'), 10), b.getAttribute('data-name'));
                };
            });
        }

        function addMenuItem() {
            var name = document.getElementById('add-item-name').value.trim();
            var category = document.getElementById('add-item-category').value;
            var price = parseFloat(document.getElementById('add-item-price').value);

            if (!name) { toast('Please enter item name', 'warning'); return; }
            if (isNaN(price) || price < 0) { toast('Please enter a valid price', 'warning'); return; }

            var btn = document.getElementById('add-menu-btn');
            btn.disabled = true; btn.innerText = 'Saving...';

            fetch('../api/api_restuarant/menu.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ item_name: name, category: category, price: price })
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data.status === 'success') {
                    toast('Item added', 'success');
                    addMenuModal.hide();
                    document.getElementById('add-item-name').value = '';
                    document.getElementById('add-item-price').value = '';
                    loadMenu();
                } else {
                    toast(data.message || 'Could not add item', 'error');
                }
            }).catch(function () { toast('Network error', 'error'); })
              .finally(function () { btn.disabled = false; btn.innerText = 'Save Item'; });
        }

        function openEditModal(item) {
            document.getElementById('edit-item-id').value = item.item_id;
            document.getElementById('edit-item-name').value = item.item_name;
            document.getElementById('edit-item-category').value = item.category;
            document.getElementById('edit-item-price').value = item.price;
            editMenuModal.show();
        }

        function saveMenuEdit() {
            var id = document.getElementById('edit-item-id').value;
            var name = document.getElementById('edit-item-name').value.trim();
            var category = document.getElementById('edit-item-category').value;
            var price = parseFloat(document.getElementById('edit-item-price').value);

            if (!name) { toast('Please enter item name', 'warning'); return; }
            if (isNaN(price) || price < 0) { toast('Please enter a valid price', 'warning'); return; }

            var btn = document.getElementById('edit-menu-btn');
            btn.disabled = true; btn.innerText = 'Updating...';

            fetch('../api/api_restuarant/menu.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ item_id: parseInt(id, 10), item_name: name, category: category, price: price })
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data.status === 'success') {
                    toast('Item updated', 'success');
                    editMenuModal.hide();
                    loadMenu();
                } else {
                    toast(data.message || 'Could not update', 'error');
                }
            }).catch(function () { toast('Network error', 'error'); })
              .finally(function () { btn.disabled = false; btn.innerText = 'Update Price'; });
        }

        function deactivateItem(id, name) {
            confirmDialog({
                title: 'Remove item?',
                message: 'Remove "' + name + '" from the active menu?',
                okText: 'Remove',
                danger: true
            }).then(function (ok) {
                if (!ok) return;
                fetch('../api/api_restuarant/menu.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ item_id: id, is_active: 0 })
                }).then(function (r) { return r.json(); }).then(function (data) {
                    if (data.status === 'success') {
                        toast('Item removed', 'success');
                        loadMenu();
                    } else {
                        toast(data.message || 'Could not remove', 'error');
                    }
                }).catch(function () { toast('Network error', 'error'); });
            });
        }
    </script>
</body>
</html>