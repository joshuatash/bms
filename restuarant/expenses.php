<?php
// bms/restuarant/expenses.php
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
    <title>Record Expenses &amp; Purchases</title>
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
        
        /* Reusable Form Cards */
        .form-card { background-color: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; height: 100%; }
        .form-card-header { padding: 1rem 1.5rem; background-color: var(--sidebar); border-bottom: 1px solid var(--border); }
        .form-card-title { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); margin: 0; display: flex; align-items: center; gap: 8px; }
        .form-card-body { padding: 1.5rem; }
        
        /* --- FORMS AND PLACEHOLDERS --- */
        .form-control, .form-select { 
            background-color: var(--sidebar); 
            border: 1px solid var(--border); 
            color: #FFFFFF; /* Ensures typed text is white */
            border-radius: 8px; 
            padding: 0.6rem 0.9rem; 
        }
        .form-control::placeholder { 
            color: #94A3B8 !important; /* Makes placeholders visible */
            opacity: 1; 
        }
        .form-select { 
            color: #FFFFFF; /* Makes dropdown text white */
        }
        .form-control:focus, .form-select:focus { 
            background-color: var(--sidebar); 
            border-color: var(--primary); 
            color: #FFFFFF; 
            box-shadow: 0 0 0 3px rgba(212, 163, 115, 0.15); 
        }
        .form-label { font-size: 0.82rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;}
        
        .btn-brand { background-color: var(--primary); color: var(--sidebar); font-weight: 700; font-size: 0.9rem; padding: 0.65rem 1.25rem; border-radius: 8px; border: none; width: 100%; transition: all 0.2s; }
        .btn-brand:hover { background-color: var(--primary-hover); color: var(--sidebar); }
        .btn-brand:disabled { opacity: .6; }

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
        <div class="mb-4">
            <h1 class="h3 fw-bold mb-1" style="color: var(--text);">Record Financials</h1>
            <p class="mb-0" style="font-size: 0.88rem; color: #FFFFFF;">Log expenses, rent, or raw material purchases here. They will appear on the Revenue Dashboard.</p>
        </div>

        <div class="row g-4">
            <!-- Record Stock Purchase -->
            <div class="col-lg-6">
                <div class="form-card">
                    <div class="form-card-header">
                        <h2 class="form-card-title"><i class="fa-solid fa-boxes-packing"></i> Stock Purchase (Raw Materials)</h2>
                    </div>
                    <div class="form-card-body">
                        <div class="mb-3">
                            <label class="form-label">Item Name</label>
                            <input type="text" id="sp-item" class="form-control" placeholder="e.g. Cooking Oil, Chicken, Spices">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select id="sp-category" class="form-select">
                                <option value="Oils & Fats">Oils &amp; Fats</option>
                                <option value="Meat & Poultry">Meat &amp; Poultry</option>
                                <option value="Spices & Condiments">Spices &amp; Condiments</option>
                                <option value="Grains & Dry Goods">Grains &amp; Dry Goods</option>
                                <option value="Packaging">Packaging</option>
                                <option value="Raw Materials">Other Raw Materials</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" step="0.01" id="sp-qty" class="form-control" placeholder="0">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Unit</label>
                                <input type="text" id="sp-unit" class="form-control" placeholder="Litres, Kg">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Unit Cost (UGX)</label>
                                <input type="number" step="1" id="sp-unitcost" class="form-control" placeholder="0">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Supplier</label>
                                <input type="text" id="sp-supplier" class="form-control" placeholder="Optional">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded" style="background: var(--surface-2);">
                            <span class="text-muted fw-bold text-uppercase" style="font-size: 0.75rem;">Total Cost:</span>
                            <strong id="sp-total" style="color: var(--primary); font-size: 1.2rem;">UGX 0</strong>
                        </div>
                        <button class="btn-brand" id="sp-save-btn" onclick="savePurchase()">Record Purchase</button>
                    </div>
                </div>
            </div>

            <!-- Record Expense -->
            <div class="col-lg-6">
                <div class="form-card">
                    <div class="form-card-header">
                        <h2 class="form-card-title"><i class="fa-solid fa-receipt"></i> General Expense</h2>
                    </div>
                    <div class="form-card-body">
                        <div class="mb-3">
                            <label class="form-label">Expense Name</label>
                            <input type="text" id="ex-name" class="form-control" placeholder="e.g. Monthly Rent, Electricity Bill, Salaries">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select id="ex-category" class="form-select">
                                <option value="Rent">Rent</option>
                                <option value="Salaries">Salaries</option>
                                <option value="Utilities">Utilities</option>
                                <option value="Transport">Transport</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount (UGX)</label>
                            <input type="number" step="1" id="ex-amount" class="form-control" placeholder="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea id="ex-notes" class="form-control" rows="3" placeholder="Optional details..."></textarea>
                        </div>
                        
                        <div class="mt-4 pt-2">
                            <button class="btn-brand" id="ex-save-btn" onclick="saveExpense()">Record Expense</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

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

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('sp-qty').addEventListener('input', updateSpTotal);
            document.getElementById('sp-unitcost').addEventListener('input', updateSpTotal);
        });

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

        function updateSpTotal() {
            var qty = parseFloat(document.getElementById('sp-qty').value) || 0;
            var cost = parseFloat(document.getElementById('sp-unitcost').value) || 0;
            document.getElementById('sp-total').innerText = ugx(qty * cost);
        }

        function savePurchase() {
            var name = document.getElementById('sp-item').value.trim();
            var category = document.getElementById('sp-category').value;
            var qty = parseFloat(document.getElementById('sp-qty').value);
            var unit = document.getElementById('sp-unit').value.trim();
            var unitCost = parseFloat(document.getElementById('sp-unitcost').value);
            var supplier = document.getElementById('sp-supplier').value.trim();

            if (!name) { toast('Enter item name', 'warning'); return; }
            if (!unit) { toast('Enter unit (Kg, Litres, etc.)', 'warning'); return; }
            if (!(qty > 0)) { toast('Enter a valid quantity', 'warning'); return; }
            if (!(unitCost > 0)) { toast('Enter a valid unit cost', 'warning'); return; }

            var btn = document.getElementById('sp-save-btn');
            btn.disabled = true; btn.innerText = 'Saving...';

            safeFetch('../api/api_restuarant/reports.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: 'purchase',
                    item_name: name,
                    category: category,
                    quantity: qty,
                    unit: unit,
                    unit_cost: unitCost,
                    supplier: supplier
                })
            }).then(function (data) {
                if (data.status === 'success') {
                    toast('Purchase recorded', 'success');
                    document.getElementById('sp-item').value = '';
                    document.getElementById('sp-qty').value = '';
                    document.getElementById('sp-unit').value = '';
                    document.getElementById('sp-unitcost').value = '';
                    document.getElementById('sp-supplier').value = '';
                    updateSpTotal();
                } else {
                    toast(data.message || 'Could not save', 'error');
                }
            }).catch(function (err) { 
                if (err.message !== 'Invalid JSON') toast('Network error', 'error'); 
            }).finally(function () { 
                btn.disabled = false; 
                btn.innerText = 'Record Purchase'; 
            });
        }

        function saveExpense() {
            var name = document.getElementById('ex-name').value.trim();
            var category = document.getElementById('ex-category').value;
            var amount = parseFloat(document.getElementById('ex-amount').value);
            var notes = document.getElementById('ex-notes').value.trim();

            if (!name) { toast('Enter expense name', 'warning'); return; }
            if (!(amount > 0)) { toast('Enter a valid amount', 'warning'); return; }

            var btn = document.getElementById('ex-save-btn');
            btn.disabled = true; btn.innerText = 'Saving...';

            safeFetch('../api/api_restuarant/reports.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: 'expense',
                    expense_name: name,
                    category: category,
                    amount: amount,
                    notes: notes
                })
            }).then(function (data) {
                if (data.status === 'success') {
                    toast('Expense recorded', 'success');
                    document.getElementById('ex-name').value = '';
                    document.getElementById('ex-amount').value = '';
                    document.getElementById('ex-notes').value = '';
                } else {
                    toast(data.message || 'Could not save', 'error');
                }
            }).catch(function (err) { 
                if (err.message !== 'Invalid JSON') toast('Network error', 'error'); 
            }).finally(function () { 
                btn.disabled = false; 
                btn.innerText = 'Record Expense'; 
            });
        }
    </script>
</body>
</html>