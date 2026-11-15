<?php
// bms/restuarant/kitchen.php
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
    <title>Kitchen Display System (KDS)</title>
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
        .btn-outline { background: transparent; border: 1px solid var(--primary); color: var(--primary); font-weight: 600; font-size: 0.85rem; padding: 0.5rem 1.1rem; border-radius: 8px; }
        .btn-outline:hover { background: var(--primary); color: var(--sidebar); }

        .sub-outstanding {
            display: inline-flex; align-items: center; gap: 8px; margin: 6px 0 0; padding: 8px 16px;
            background: linear-gradient(135deg, rgba(245,158,11,.15) 0%, rgba(245,158,11,.05) 100%);
            border: 1px solid rgba(245,158,11,.3); border-left: 4px solid var(--warning);
            border-radius: 8px; color: var(--warning); font-size: .85rem; font-weight: 700;
            letter-spacing: .3px; text-shadow: 0 0 12px rgba(245,158,11,.4);
            box-shadow: 0 4px 12px rgba(0,0,0,.2); animation: pulse-border 2s infinite alternate;
        }
        @keyframes pulse-border {
            0% { border-color: rgba(245,158,11,.3); box-shadow: 0 4px 12px rgba(0,0,0,.2); }
            100% { border-color: rgba(245,158,11,.6); box-shadow: 0 4px 15px rgba(245,158,11,.15); }
        }
        .sub-outstanding svg { width: 16px; height: 16px; flex-shrink: 0; }

        .kds-card { background: var(--surface); border-radius: 12px; overflow: hidden; border-left: 6px solid var(--border); height: 100%; }
        .kds-card.status-Received { border-left-color: var(--text-muted); }
        .kds-card.status-Cooking { border-left-color: var(--warning); background: rgba(251,191,36,.04); }
        .kds-card.status-Ready { border-left-color: var(--blue); background: rgba(96,165,250,.04); }
        .kds-card.status-Disposed { border-left-color: var(--success); opacity: .7; }
        .kds-head { padding: 12px 14px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); }
        .kds-order-id { font-size: 16px; font-weight: 800; color: var(--primary); }
        .badge-status { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
        .badge-status.s-Received { background: rgba(148,163,184,.15); color: var(--text-muted); }
        .badge-status.s-Cooking { background: rgba(251,191,36,.15); color: var(--warning); }
        .badge-status.s-Ready { background: rgba(96,165,250,.15); color: var(--blue); }
        .badge-status.s-Disposed { background: rgba(74,222,128,.15); color: var(--success); }

        .kds-body { padding: 14px; }
        .kds-customer { font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 2px; }
        .kds-meta { font-size: 12px; color: var(--text-muted); margin-bottom: 12px; }
        .kds-items { border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 10px 0; margin-bottom: 12px; }
        .kds-item { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; }
        .kds-item strong { color: var(--warning); font-size: 15px; }
        .kds-action { width: 100%; height: 48px; border-radius: 10px; font-weight: 800; font-size: 14px; border: 0; cursor: pointer; }
        .btn-Received { background: var(--warning); color: #151719; }
        .btn-Cooking { background: var(--blue); color: #151719; }
        .btn-Ready { background: #16a34a; color: #fff; }
        .btn-Disposed { background: var(--surface-2); color: var(--text-muted); }

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

        @media (min-width: 992px) { .mobile-bottom-nav { display: none !important; } body { padding-bottom: 0; } }
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
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1" style="color: var(--text);">Live Kitchen Queue</h1>
                
                <div class="sub-outstanding">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Manage orders from Received to Served (First Come, First Serve)
                </div>

            </div>
            <button class="btn-outline" onclick="loadKitchenOrders()"><i class="fa-solid fa-rotate me-1"></i> Refresh</button>
        </div>

        <div id="kitchen-orders-container" class="row g-3">
            <div class="col-12 text-center py-5 text-muted">Loading kitchen queue...</div>
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
        let lastPaidId = 0;
        let isInitialLoad = true;

        function loadVoices() {
            availableVoices = window.speechSynthesis.getVoices();
        }
        if (typeof speechSynthesis !== 'undefined' && speechSynthesis.onvoiceschanged !== undefined) {
            speechSynthesis.onvoiceschanged = loadVoices;
        }
        loadVoices();

        document.addEventListener("DOMContentLoaded", function() {
            loadKitchenOrders();
            startLiveUpdates();
            
            // Auto-refresh the queue every 10 seconds so it perfectly reflects the waitress clearing orders
            setInterval(loadKitchenOrders, 10000);
            
            // Check for paid alerts every 4 seconds
            setInterval(checkKitchenAlerts, 4000);
        });

        function loadKitchenOrders() {
            fetch('../api/api_restuarant/orders.php')
                .then(function (r) { return r.json(); })
                .then(function (res) { 
                    if (res.status === 'success') {
                        renderKitchenOrders(res.data);
                        if (res.data.length > 0) {
                            var ids = res.data.map(function(o) { return o.order_id; });
                            currentMaxId = Math.max.apply(Math, ids);
                        }
                    } 
                })
                .catch(function () {});
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
                                    renderKitchenOrders(res.data); 
                                    
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
                    }
                };
                
                source.onerror = function() {
                    source.close();
                    setTimeout(function() {
                        startLiveUpdates();
                    }, 3000);
                };
            }
        }

        function playBeep() {
            try {
                var audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                var oscillator = audioCtx.createOscillator();
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(800, audioCtx.currentTime);
                oscillator.connect(audioCtx.destination);
                oscillator.start();
                oscillator.stop(audioCtx.currentTime + 0.3);
            } catch(e) {}
        }

        function speakMessage(message) {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                
                var utterance = new SpeechSynthesisUtterance(message);
                utterance.rate = 1.0; // NORMAL SPEED
                utterance.pitch = 1.0; 
                utterance.volume = 1.0;
                
                if (availableVoices.length === 0) {
                    availableVoices = window.speechSynthesis.getVoices();
                }
                
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
                
                if (maleVoice) {
                    utterance.voice = maleVoice;
                }
                
                window.speechSynthesis.speak(utterance);
            }
        }

        function renderKitchenOrders(orders) {
            var container = document.getElementById('kitchen-orders-container');
            container.innerHTML = '';

            var activeOrders = orders.filter(function (o) {
                return o.order_status !== 'Disposed';
            });

            activeOrders.sort(function(a, b) {
                return a.order_id - b.order_id;
            });

            if (activeOrders.length === 0) {
                container.innerHTML = '<div class="col-12 text-center py-5" style="color: #ff0800;"><h4>No active orders in the queue</h4></div>';
                return;
            }

            activeOrders.forEach(function (order) {
                var itemsHtml = order.items.map(function (i) {
                    return '<div class="kds-item"><span><strong>' + i.quantity + 'x</strong> ' + escapeHtml(i.item_name) + '</span></div>';
                }).join('');

                var tableDisplay = order.table_number ? ('Table ' + order.table_number) : 'Counter Takeaway';
                
                // TRANSLATE DATABASE TYPES TO FRIENDLY NAMES
                var orderTypeDisplay = order.order_type;
                if (order.order_type === 'Dine-In') orderTypeDisplay = 'Paying Later';
                if (order.order_type === 'Counter') orderTypeDisplay = 'Paying Now';

                var actionBtn = '';
                if (order.order_status === 'Received') {
                    actionBtn = '<button class="kds-action btn-Received" data-id="' + order.order_id + '" data-next="Cooking"><i class="fa-solid fa-fire me-1"></i> Start Cooking</button>';
                } else if (order.order_status === 'Cooking') {
                    actionBtn = '<button class="kds-action btn-Cooking" data-id="' + order.order_id + '" data-next="Ready"><i class="fa-solid fa-bell me-1"></i> Mark Ready</button>';
                } else if (order.order_status === 'Ready') {
                    actionBtn = '<button class="kds-action btn-Ready" data-id="' + order.order_id + '" data-next="Disposed"><i class="fa-solid fa-check-double me-1"></i> Mark Served</button>';
                }

                var col = document.createElement('div');
                col.className = 'col-12 col-md-6 col-lg-4';
                col.innerHTML =
                    '<div class="kds-card status-' + order.order_status + '">' +
                        '<div class="kds-head">' +
                            '<span class="kds-order-id">#' + order.order_id + '</span>' +
                            '<span class="badge-status s-' + order.order_status + '">' + order.order_status + '</span>' +
                        '</div>' +
                        '<div class="kds-body">' +
                            '<div class="kds-customer"><i class="fa-solid fa-user me-1"></i>' + escapeHtml(order.customer_name) + '</div>' +
                            '<div class="kds-meta"><i class="fa-solid fa-chair me-1"></i>' + tableDisplay + ' &middot; ' + orderTypeDisplay + '</div>' +
                            '<div class="kds-items">' + itemsHtml + '</div>' +
                            actionBtn +
                        '</div>' +
                    '</div>';
                container.appendChild(col);
            });

            container.querySelectorAll('.kds-action[data-id]').forEach(function (b) {
                b.onclick = function () {
                    updateOrderStatus(parseInt(b.getAttribute('data-id'), 10), b.getAttribute('data-next'), b);
                };
            });
        }

        function updateOrderStatus(orderId, nextStatus, btn) {
            btn.disabled = true;
            var original = btn.innerHTML;
            btn.innerHTML = 'Updating...';

            fetch('../api/api_restuarant/orders.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, order_status: nextStatus })
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data.status === 'success') {
                    toast('Order #' + orderId + ' moved to ' + nextStatus, 'success');
                    loadKitchenOrders();
                } else {
                    toast(data.message || 'Update failed', 'error');
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            }).catch(function () {
                toast('Network error', 'error');
                btn.disabled = false;
                btn.innerHTML = original;
            });
        }

        /* --- KITCHEN PAID NOTIFICATION ALERT --- */
        function showKitchenAlert(order) {
            const toastEl = document.createElement('div');
            toastEl.style.cssText = `
                position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999;
                background: #16243a; border: 3px solid #4ade80; border-radius: 12px;
                padding: 20px 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.6);
                display: flex; flex-direction: column; align-items: center; gap: 8px;
                animation: slideDown 0.4s ease-out forwards;
            `;
            
            if (!document.getElementById('kitchen-alert-styles')) {
                const style = document.createElement('style');
                style.id = 'kitchen-alert-styles';
                style.innerHTML = `
                    @keyframes slideDown {
                        from { transform: translate(-50%, -100%); opacity: 0; }
                        to { transform: translate(-50%, 0); opacity: 1; }
                    }
                    @keyframes slideUp {
                        from { transform: translate(-50%, 0); opacity: 1; }
                        to { transform: translate(-50%, -100%); opacity: 0; }
                    }
                `;
                document.head.appendChild(style);
            }

            const tableText = order.table_number ? `Table ${order.table_number}` : 'Counter';

            toastEl.innerHTML = `
                <div style="color:#4ade80; font-size:1.5rem; font-weight:900; text-align:center;">
                    💰 ORDER PAID & SETTLED
                </div>
                <div style="color:#e2e8f0; font-weight:700; font-size:1.2rem; text-align:center;">
                    Order #${order.order_id} (${tableText})
                </div>
                <div style="color:#94a3b8; font-size:0.9rem;">Customer has paid. Ready to clear.</div>
            `;

            document.body.appendChild(toastEl);
            
            if ("vibrate" in navigator) navigator.vibrate([200, 100, 200, 100, 400]);

            setTimeout(() => {
                if (toastEl.parentElement) {
                    toastEl.style.animation = 'slideUp 0.4s ease-in forwards';
                    setTimeout(() => toastEl.remove(), 400);
                }
            }, 8000);
        }

        async function checkKitchenAlerts() {
            try {
                const res = await fetch('../api/api_restuarant/check_kitchen_notifications.php');
                if (!res.ok) return;
                const data = await res.json();
                
                if (data.status === 'success' && data.latest_paid) {
                    const order = data.latest_paid;
                    
                    // Strict check for new order ID
                    if (order.order_id > lastPaidId) {
                        if (isInitialLoad) {
                            lastPaidId = order.order_id;
                            isInitialLoad = false;
                        } else {
                            lastPaidId = order.order_id;
                            
                            const tableText = order.table_number ? `Table ${order.table_number}` : 'Counter';
                            const speech = `Order number ${order.order_id} for ${tableText} has been paid and settled successfully.`;
                            
                            speakMessage(speech);
                            showKitchenAlert(order);
                            
                            // Auto refresh the KDS list to remove the cleared order immediately
                            loadKitchenOrders();
                        }
                    }
                }
            } catch (e) {
                console.log("Kitchen poll error:", e);
            }
        }
    </script>
</body>
</html>