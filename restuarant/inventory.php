<?php
// bms/restuarant/inventory.php
require_once __DIR__ . '/../config.php';
require_login();

$role = strtolower(current_user()['role'] ?? 'cashier');
$isCashier = ($role === 'cashier');

// If a cashier somehow lands here, send them to POS
if ($isCashier) {
    header('Location: pos.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Kitchen Inventory &amp; Restocking</title>
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
        .card-custom { background-color: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
        .table-custom { width: 100%; margin-bottom: 0; color: var(--text); border-collapse: collapse; }
        .table-custom th { background-color: var(--surface-2); color: var(--text-muted); font-weight: 700; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; padding: 0.85rem 1.25rem; border-bottom: 1px solid var(--border); }
        .table-custom td { padding: 1rem 1.25rem; vertical-align: middle; border-bottom: 1px solid var(--border); font-size: 0.88rem; }
        .low-stock-row { background-color: rgba(248, 113, 113, 0.04) !important; box-shadow: inset 3px 0 0 var(--danger); }
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
        .btn-action-refill { background-color: rgba(74, 222, 128, 0.12); color: var(--success); border: 1px solid rgba(74, 222, 128, 0.25); font-weight: 600; font-size: 0.75rem; padding: 0.3rem 0.75rem; border-radius: 6px; }

        .badge-ok { background: rgba(74,222,128,.15); color: var(--success); padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; }
        .badge-low { background: rgba(248,113,113,.15); color: var(--danger); padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; }
        .badge-neutral { background: var(--surface-2); color: var(--text-muted); padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; }

        /* Mobile inventory cards */
        .inv-card { background: var(--surface); border-radius: 12px; padding: 14px; margin-bottom: 10px; border-left: 4px solid var(--border); }
        .inv-card.low { border-left-color: var(--danger); }
        .inv-name { font-weight: 700; font-size: 15px; color: var(--text); }
        .inv-cat { font-size: 11px; color: var(--text-muted); margin-bottom: 8px; }
        .inv-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; }
        .inv-qty { color: var(--text); font-weight: 700; }
        .inv-actions { margin-top: 10px; }
        .inv-actions button { width: 100%; height: 42px; border-radius: 8px; font-size: 13px; }

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

        @media (min-width: 992px) {
            .mobile-bottom-nav, .fab { display: none !important; }
            body { padding-bottom: 0; }
            .mobile-inv { display: none; }
        }
        @media (max-width: 991.98px) {
            .app-header { display: none; }
            .mobile-bottom-nav { display: flex; }
            .fab { display: flex; }
            body { padding-bottom: calc(75px + env(safe-area-inset-bottom, 0px)); }
            .main-container { padding: 1rem; }
            .desktop-inv { display: none; }
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
                    <li><a class="nav-link" href="index.php">Dashboard</a></li>
                <?php endif; ?>
                <li><a class="nav-link" href="pos.php">Counter POS</a></li>
                <?php if (!$isCashier): ?>
                    <li><a class="nav-link" href="kitchen.php">Kitchen KDS</a></li>
                    <li><a class="nav-link active" href="inventory.php">Inventory</a></li>
                    <li><a class="nav-link" href="admin.php">Menu &amp; Prices</a></li>
                    <li><a class="nav-link" href="reports.php">Revenue</a></li>
                <?php endif; ?>
                <li><a class="nav-link" href="waitress.php">Waitress</a></li>
                <li class="ms-2"><a class="nav-link bar-link" href="../dashboard.php">Bar Side &rarr;</a></li>
            </ul>
        </div>
    </header>

    <main class="main-container">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="h3 fw-bold mb-0" style="color: var(--text);">Kitchen Inventory &amp; Raw Materials</h1>
            </div>
            <div class="add-btn-top">
                <button class="btn-brand" data-bs-toggle="modal" data-bs-target="#addItemModal"><i class="fa-solid fa-plus me-1"></i> Add Raw Material</button>
            </div>
        </div>

        <div class="card-custom desktop-inv">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background-color: var(--sidebar) !important; border-color: var(--border) !important;">
                <h5 class="mb-0 fw-bold text-uppercase" style="font-size: 0.78rem; letter-spacing: 0.08em; color: var(--text-muted);">Current Kitchen Stock</h5>
                <button class="btn-subtle" onclick="loadInventory()">Refresh Stock</button>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr><th class="ps-4">Item Name</th><th>Category</th><th>Current Stock</th><th>Alert Level</th><th>Status</th><th>Last Restocked</th><th class="text-end pe-4">Actions</th></tr>
                    </thead>
                    <tbody id="inventory-tbody">
                        <tr><td colspan="7" class="text-center py-5 text-muted">Loading inventory...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mobile-inv">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fw-bold text-uppercase" style="font-size: 0.78rem; letter-spacing: 0.08em; color: var(--text-muted); margin: 0;">Current Stock</h2>
                <button class="btn-subtle" onclick="loadInventory()">Refresh</button>
            </div>
            <div id="inventory-mobile"></div>
        </div>
    </main>

    <button class="fab" data-bs-toggle="modal" data-bs-target="#addItemModal" aria-label="Add"><i class="fa-solid fa-plus"></i></button>

    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold fs-6 text-uppercase">Add New Kitchen Raw Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Item Name</label><input type="text" id="new-item-name" class="form-control" placeholder="e.g. Cooking Oil, Chicken Packages"></div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select id="new-item-category" class="form-select">
                            <option value="Oils & Fats">Oils &amp; Fats</option>
                            <option value="Meat & Poultry">Meat &amp; Poultry</option>
                            <option value="Spices & Condiments">Spices &amp; Condiments</option>
                            <option value="Grains & Dry Goods">Grains &amp; Dry Goods</option>
                            <option value="Packaging">Packaging</option>
                            <option value="Raw Materials">Other Raw Materials</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Initial Quantity</label><input type="number" step="0.01" id="new-item-qty" class="form-control" value="0"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Unit of Measure</label><input type="text" id="new-item-unit" class="form-control" placeholder="e.g. Litres, Kg"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Low-Stock Warning Threshold</label><input type="number" step="0.01" id="new-item-threshold" class="form-control" value="5"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-subtle" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-brand" id="save-material-btn" onclick="addNewMaterial()">Save Material</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="restockModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold fs-6 text-uppercase">Refill / Update Kitchen Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="restock-id">
                    <p class="mb-3">Updating: <strong id="restock-name" style="color: var(--primary);"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Action Type</label>
                        <select id="restock-mode" class="form-select"><option value="add">Add New Restock Delivery (+)</option><option value="set">Set Exact Physical Count (=)</option></select>
                    </div>
                    <div class="mb-3"><label class="form-label">Quantity (<span id="restock-unit"></span>)</label><input type="number" step="0.01" id="restock-qty" class="form-control" placeholder="Enter amount"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-subtle" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-brand" id="restock-btn" onclick="submitRestock()">Confirm Update</button>
                </div>
            </div>
        </div>
    </div>

    <nav class="mobile-bottom-nav">
        <?php if (!$isCashier): ?>
            <a href="index.php" class="mobile-nav-link"><i class="fa-solid fa-chart-line"></i>Dashboard</a>
        <?php endif; ?>
        <a href="pos.php" class="mobile-nav-link"><i class="fa-solid fa-cash-register"></i>POS</a>
        <?php if (!$isCashier): ?>
            <a href="kitchen.php" class="mobile-nav-link"><i class="fa-solid fa-fire-burner"></i>Kitchen</a>
            <a href="inventory.php" class="mobile-nav-link active"><i class="fa-solid fa-boxes-stacked"></i>Stock</a>
            <a href="admin.php" class="mobile-nav-link"><i class="fa-solid fa-gear"></i>Menu</a>
            <a href="reports.php" class="mobile-nav-link"><i class="fa-solid fa-coins"></i>Revenue</a>
        <?php endif; ?>
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

        var restockModalInstance, addModalInstance;

        document.addEventListener("DOMContentLoaded", function() {
            restockModalInstance = new bootstrap.Modal(document.getElementById('restockModal'));
            addModalInstance = new bootstrap.Modal(document.getElementById('addItemModal'));
            loadInventory();
        });

        function loadInventory() {
            fetch('../api/api_restuarant/inventory.php')
                .then(function (r) { return r.json(); })
                .then(function (res) { if (res.status === 'success') renderInventory(res.data); })
                .catch(function () { toast('Could not load inventory', 'error'); });
        }

        function renderInventory(items) {
            var tbody = document.getElementById('inventory-tbody');
            var mobile = document.getElementById('inventory-mobile');
            tbody.innerHTML = '';
            mobile.innerHTML = '';

            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">No raw materials recorded yet.</td></tr>';
                mobile.innerHTML = '<div class="text-center py-5 text-muted">No raw materials recorded yet.</div>';
                return;
            }

            items.forEach(function (item) {
                var qty = parseFloat(item.quantity);
                var threshold = parseFloat(item.low_stock_threshold);
                var isLow = qty <= threshold;
                var statusBadge = isLow ? '<span class="badge-low">Low Stock</span>' : '<span class="badge-ok">In Stock</span>';

                var tr = document.createElement('tr');
                tr.className = isLow ? 'low-stock-row' : '';
                tr.innerHTML =
                    '<td class="ps-4 fw-bold">' + escapeHtml(item.item_name) + '</td>' +
                    '<td><span class="badge-neutral">' + escapeHtml(item.category) + '</span></td>' +
                    '<td class="fw-bold">' + qty + ' <small class="text-muted">' + escapeHtml(item.unit) + '</small></td>' +
                    '<td>' + threshold + ' ' + escapeHtml(item.unit) + '</td>' +
                    '<td>' + statusBadge + '</td>' +
                    '<td><small class="text-muted">' + (item.last_restocked_date || 'N/A') + '</small></td>' +
                    '<td class="text-end pe-4"><button class="btn btn-sm btn-action-refill" data-restock="' + item.inventory_id + '" data-name="' + escapeHtml(item.item_name) + '" data-unit="' + escapeHtml(item.unit) + '">Refill / Adjust</button></td>';
                tbody.appendChild(tr);

                var card = document.createElement('div');
                card.className = 'inv-card' + (isLow ? ' low' : '');
                card.innerHTML =
                    '<div class="d-flex justify-content-between align-items-start mb-1">' +
                        '<div>' +
                            '<div class="inv-name">' + escapeHtml(item.item_name) + '</div>' +
                            '<div class="inv-cat">' + escapeHtml(item.category) + '</div>' +
                        '</div>' +
                        statusBadge +
                    '</div>' +
                    '<div class="inv-row"><span>Current</span><span class="inv-qty">' + qty + ' ' + escapeHtml(item.unit) + '</span></div>' +
                    '<div class="inv-row"><span>Alert at</span><span>' + threshold + ' ' + escapeHtml(item.unit) + '</span></div>' +
                    '<div class="inv-row"><span>Restocked</span><span>' + (item.last_restocked_date || 'N/A') + '</span></div>' +
                    '<div class="inv-actions"><button class="btn-action-refill" data-restock="' + item.inventory_id + '" data-name="' + escapeHtml(item.item_name) + '" data-unit="' + escapeHtml(item.unit) + '">Refill / Adjust</button></div>';
                mobile.appendChild(card);
            });

            document.querySelectorAll('[data-restock]').forEach(function (b) {
                b.onclick = function () {
                    openRestockModal(
                        parseInt(b.getAttribute('data-restock'), 10),
                        b.getAttribute('data-name'),
                        b.getAttribute('data-unit')
                    );
                };
            });
        }

        function addNewMaterial() {
            var name = document.getElementById('new-item-name').value.trim();
            var category = document.getElementById('new-item-category').value;
            var qty = parseFloat(document.getElementById('new-item-qty').value) || 0;
            var unit = document.getElementById('new-item-unit').value.trim();
            var threshold = parseFloat(document.getElementById('new-item-threshold').value) || 5;

            if (!name || !unit) { toast('Please enter item name and unit', 'warning'); return; }

            var btn = document.getElementById('save-material-btn');
            btn.disabled = true; btn.innerText = 'Saving...';

            fetch('../api/api_restuarant/inventory.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ item_name: name, category: category, quantity: qty, unit: unit, low_stock_threshold: threshold })
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data.status === 'success') {
                    toast('Item added', 'success');
                    addModalInstance.hide();
                    document.getElementById('new-item-name').value = '';
                    document.getElementById('new-item-qty').value = '0';
                    document.getElementById('new-item-unit').value = '';
                    document.getElementById('new-item-threshold').value = '5';
                    loadInventory();
                } else {
                    toast(data.message || 'Could not add item', 'error');
                }
            }).catch(function () {
                toast('Network error', 'error');
            }).finally(function () {
                btn.disabled = false; btn.innerText = 'Save Material';
            });
        }

        function openRestockModal(id, name, unit) {
            document.getElementById('restock-id').value = id;
            document.getElementById('restock-name').innerText = name;
            document.getElementById('restock-unit').innerText = unit;
            document.getElementById('restock-qty').value = '';
            restockModalInstance.show();
        }

        function submitRestock() {
            var id = document.getElementById('restock-id').value;
            var mode = document.getElementById('restock-mode').value;
            var qty = parseFloat(document.getElementById('restock-qty').value);

            if (isNaN(qty) || qty < 0) { toast('Please enter a valid quantity', 'warning'); return; }

            var btn = document.getElementById('restock-btn');
            btn.disabled = true; btn.innerText = 'Updating...';

            fetch('../api/api_restuarant/inventory.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ inventory_id: parseInt(id, 10), quantity: qty, mode: mode })
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data.status === 'success') {
                    toast('Stock updated', 'success');
                    restockModalInstance.hide();
                    loadInventory();
                } else {
                    toast(data.message || 'Could not update', 'error');
                }
            }).catch(function () {
                toast('Network error', 'error');
            }).finally(function () {
                btn.disabled = false; btn.innerText = 'Confirm Update';
            });
        }
    </script>
</body>
</html>