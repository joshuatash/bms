<?php require __DIR__ . '/config.php'; require_login(); $active = 'pos'; $user = current_user(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Point of Sale</title>
    <style>
        :root{
            --navy:#0f172a;--slate:#334155;--muted:#64748b;--line:#e2e8f0;--amber:#f59e0b;--green:#16a34a;--red:#dc2626;--orange:#ea580c;
            /* Dark theme surfaces */
            --bg:#0b1220;
            --surface:#111c2e;
            --surface-2:#16243a;
            --surface-3:#1e2d47;
            --text:#e2e8f0;
            --text-dim:#94a3b8;
            --text-mute:#64748b;
            --border:#243349;
        }
        *{box-sizing:border-box}
        body{margin:0;font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
        
        .wrap{max-width:1400px;margin:20px auto;padding:0 18px;display:grid;grid-template-columns:1fr 400px;gap:20px;align-items:start}
        
        /* Main Left Panel */
        .card{background:var(--surface);border-radius:16px;padding:20px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.3); border:1px solid var(--border)}
        .card h2{margin:0 0 16px;font-size:1.1rem; display:flex; justify-content:space-between; align-items:center; color:var(--text)}
        
        .toolbar{display:flex;gap:12px;align-items:center;margin-bottom:12px}
        .toolbar input{flex:1;padding:14px 16px;border:1px solid var(--border);border-radius:10px;font-size:1rem; background:var(--surface-2); color:var(--text)}
        .toolbar input::placeholder{color:var(--text-mute)}
        input:focus{outline:2px solid var(--amber);border-color:var(--amber); background:var(--surface-3)}
        .count{color:var(--text-dim);font-size:.85rem;font-weight:600;white-space:nowrap}
        
        /* Quick Sort Filter Buttons */
        .filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
        .filter-btn{
            display:inline-flex;align-items:center;gap:8px;
            border:1px solid var(--border);
            background:var(--surface-2);
            color:var(--text-dim);
            border-radius:10px;
            padding:9px 14px;
            font-size:.85rem;
            font-weight:700;
            cursor:pointer;
            font-family:inherit;
            transition:.15s;
            white-space:nowrap;
        }
        .filter-btn:hover{background:var(--surface-3);color:var(--text)}
        .filter-btn .fc{
            display:inline-block;
            min-width:22px;
            padding:2px 7px;
            border-radius:999px;
            font-size:.72rem;
            font-weight:800;
            text-align:center;
            background:var(--surface-3);
            color:var(--text);
        }
        .filter-btn.on{background:var(--amber);color:#0b1220;border-color:var(--amber);box-shadow:0 4px 6px -1px rgba(245,158,11,.3)}
        .filter-btn.on .fc{background:rgba(0,0,0,.18);color:#0b1220}
        .filter-btn.avail .fc{background:rgba(22,163,74,.25);color:#4ade80}
        .filter-btn.low .fc{background:rgba(234,88,12,.25);color:#fb923c}
        .filter-btn.out .fc{background:rgba(220,38,38,.25);color:#f87171}
        .filter-btn.avail.on .fc,.filter-btn.low.on .fc,.filter-btn.out.on .fc{background:rgba(0,0,0,.18);color:#0b1220}
        
        /* Table */
        .tablewrap{overflow:auto;max-height:calc(100vh - 200px); border-radius:10px; border:1px solid var(--border)}
        table{width:100%;border-collapse:collapse;font-size:.93rem;color:var(--text)}
        th{position:sticky;top:0;text-align:left;background:var(--surface-2);color:var(--text-dim);padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid var(--border); z-index:10}
        td{padding:12px 16px;border-bottom:1px solid var(--border);vertical-align:middle}
        tr.sel td{background:rgba(245,158,11,.12)}
        tr.out td{opacity:.4}
        tr:hover:not(.sel) td { background: var(--surface-2); }
        
        .num{text-align:right}.ctr{text-align:center}
        .badge{padding:4px 10px;border-radius:999px;font-size:.72rem;font-weight:700;white-space:nowrap}
        .badge.Available{background:rgba(22,163,74,.2);color:#4ade80}
        .badge.Low{background:rgba(234,88,12,.2);color:#fb923c}
        .badge.Out{background:rgba(220,38,38,.2);color:#f87171}
        
        td input[type=checkbox]{width:22px;height:22px;accent-color:var(--amber);cursor:pointer; border-radius:4px}
        td input[type=checkbox]:disabled{cursor:not-allowed}
        td input.qty{width:80px;padding:8px;border:1px solid var(--border);border-radius:8px;font-size:.95rem;text-align:center; font-weight:600; background:var(--surface-2); color:var(--text)}
        td input.qty:disabled{background:var(--surface);color:var(--text-mute)}

        /* Right Side Cart */
        .cart{position:sticky;top:20px; background:var(--surface); border-radius:16px; padding:20px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.4); border:1px solid var(--border); display:flex; flex-direction:column; max-height:calc(100vh - 40px)}
        .cart h2 { margin:0 0 10px; font-size:1.2rem; color:var(--text) }
        .meta{font-size:.85rem;color:var(--text-dim);padding-bottom:14px;border-bottom:1px dashed var(--border); margin-bottom:10px}
        .meta b{color:var(--text)}
        
        .items{flex:1; overflow:auto; padding:8px 0; min-height:150px}
        .items .empty{color:var(--text-mute);text-align:center;padding:40px 0;font-size:.9rem; display:flex; flex-direction:column; align-items:center; gap:10px}
        
        .line{display:flex;justify-content:space-between;gap:10px;align-items:center;padding:12px 0;border-bottom:1px solid var(--border);font-size:.95rem}
        .line .nm{font-weight:700; color:var(--text)}.line small{display:block;color:var(--text-dim); margin-top:2px}
        .line .rm{border:0;background:rgba(220,38,38,.15);color:#f87171;border-radius:6px;cursor:pointer;font-weight:700;width:28px;height:28px;margin-left:12px; display:flex; align-items:center; justify-content:center; transition:0.2s}
        .line .rm:hover{background:rgba(220,38,38,.3)}
        .line .amt{white-space:nowrap;font-weight:800; font-size:1rem; color:var(--text)}
        
        .total{display:flex;justify-content:space-between;align-items:center;padding:18px 0 14px;border-top:2px solid var(--amber);margin-top:10px;font-weight:800; font-size:1.1rem; color:var(--text)}
        .total b{font-size:1.8rem; color:#4ade80}
        
        .btn{border:0;border-radius:10px;padding:16px 18px;font-weight:800;cursor:pointer;font-size:1rem;width:100%; transition:0.2s; font-family:inherit}
        .btn.primary{background:var(--amber);color:#0b1220; box-shadow:0 4px 6px -1px rgba(245,158,11,0.3)}
        .btn.primary:hover:not(:disabled){background:#d97706;color:#fff; transform:translateY(-1px)}
        .btn:disabled{opacity:.35;cursor:not-allowed; box-shadow:none}
        .btn.ghost{background:var(--surface-2);color:var(--text-dim);margin-top:10px;padding:12px;font-size:.9rem}
        .btn.ghost:hover{background:var(--surface-3);color:var(--text)}
        
        .hint{font-size:.85rem;margin-top:12px;min-height:1.2em;color:var(--text-dim); text-align:center; font-weight:600}
        .hint.bad{color:#f87171}
        
        /* Held Bills Styles */
        .held-section{margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--border);}
        .held-section h3{font-size:.85rem; color:var(--amber); text-transform:uppercase; margin:0 0 10px; letter-spacing:.5px;}
        .held-list{display:flex; flex-direction:column; gap:8px; max-height:150px; overflow-y:auto; margin-bottom:10px;}
        .held-item{display:flex; justify-content:space-between; align-items:center; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:10px; transition:.15s;}
        .held-item:hover{border-color:var(--amber); background:var(--surface-3);}
        .held-info { flex: 1; cursor: pointer; }
        .held-item .h-name{font-weight:700; font-size:.9rem; color:var(--text);}
        .held-item .h-total{font-size:.8rem; color:#4ade80; font-weight:700;}
        .held-item .h-time{font-size:.7rem; color:var(--text-mute); display:block;}
        .held-print { background: var(--surface-3); border: 1px solid var(--border); color: var(--text); padding: 6px 10px; border-radius: 6px; cursor: pointer; margin-left: 8px; display: grid; place-items: center; transition: .15s; }
        .held-print:hover { background: var(--amber); color: #0b1220; border-color: var(--amber); }
        .held-empty{font-size:.8rem; color:var(--text-mute); text-align:center;}

        /* MODALS - Upgraded Modern Design */
        .modal{position:fixed;inset:0;background:rgba(0,0,0,.8);backdrop-filter:blur(8px);display:none;place-items:center;z-index:50;opacity:0;transition:opacity .2s ease;}
        .modal.open{display:grid;opacity:1;}
        
        .modal .box{
            background:var(--surface-2);
            border:1px solid var(--border);
            border-radius:24px;
            padding:40px 30px;
            width:min(420px,94vw);
            text-align:center;
            box-shadow: 0 30px 60px -12px rgba(0,0,0,0.8), inset 0 1px 0 rgba(255,255,255,0.05);
            color:var(--text);
            transform:translateY(20px) scale(.95);
            transition:transform .3s cubic-bezier(0.34,1.56,0.64,1);
        }
        .modal.open .box{transform:translateY(0) scale(1);}
        
        .modal .success-icon {
            width:72px; height:72px;
            background:rgba(22,163,74,.15);
            color:#4ade80;
            border-radius:50%;
            display:grid; place-items:center;
            margin:0 auto 20px;
            border:2px solid rgba(22,163,74,.3);
        }
        .modal .success-icon svg { width:36px; height:36px; }
        
        .modal h3{margin:0 0 6px;color:var(--text); font-size:1.4rem; font-weight:800;}
        .modal .subtitle{font-size:.9rem; color:var(--text-dim); margin-bottom:24px;}
        
        .modal .receipt-box {
            background:var(--surface-3);
            border:1px dashed var(--border);
            border-radius:12px;
            padding:16px;
            margin-bottom:24px;
            text-align:left;
        }
        .modal .receipt-row { display:flex; justify-content:space-between; margin-bottom:8px; font-size:.9rem; color:var(--text-dim); }
        .modal .receipt-row:last-child { margin-bottom:0; padding-top:8px; border-top:1px solid var(--border); margin-top:8px;}
        .modal .receipt-row strong { color:var(--text); }
        .modal .big-total { font-size:1.6rem; font-weight:900; color:#4ade80; }
        
        .modal .row{display:flex;flex-direction:column;gap:10px;}
        .modal .btn{padding:14px;}
        .modal .btn.ghost { background:var(--surface); border:1px solid var(--border); color:var(--text); }
        .modal .btn.ghost:hover { background:var(--surface-3); }

        /* Hold Bill Modal Specific */
        .modal .box.hold-box { padding: 30px; text-align: left; }
        .modal .box.hold-box h3 { text-align: center; }
        .modal .box.hold-box p { color: var(--text-dim); font-size: .9rem; text-align: center; margin-bottom: 20px; }
        .modal .box.hold-box input { width: 100%; padding: 14px; border: 2px solid var(--amber); border-radius: 10px; background: var(--surface-3); color: var(--text); font-size: 1.1rem; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .modal .box.hold-box input:focus { outline: none; box-shadow: 0 0 0 4px rgba(245,158,11,.2); }
        .modal .box.hold-box .actions { display: flex; gap: 10px; }
        
        /* Mobile Cart Bar */
        .cartbar{display:none}
        
        /* Back to top */
        .back-top{
            position:fixed;right:20px;bottom:20px;z-index:60;
            width:52px;height:52px;border-radius:50%;border:0;
            background:var(--green);color:#fff;font-size:1.4rem;font-weight:900;
            cursor:pointer;display:grid;place-items:center;
            box-shadow:0 8px 20px rgba(22,163,74,.45);
            opacity:0;visibility:hidden;transform:translateY(12px);
            transition:opacity .25s ease,transform .25s ease,background .2s ease;
        }
        .back-top.show{opacity:1;visibility:visible;transform:translateY(0)}
        .back-top:hover{background:#15803d}
        .back-top:active{transform:translateY(1px) scale(.96)}
        .back-top .tip{
            position:absolute;right:calc(100% + 10px);top:50%;transform:translateY(-50%);
            background:#052e16;color:#bbf7d0;font-size:.78rem;font-weight:700;
            padding:6px 10px;border-radius:8px;white-space:nowrap;
            opacity:0;pointer-events:none;transition:opacity .2s ease;border:1px solid #166534;
        }
        .back-top:hover .tip,.back-top:focus-visible .tip{opacity:1}
        .back-top svg{width:22px;height:22px}
        
        /* --- RESPONSIVE FIXES --- */
        @media(max-width: 1050px){
            .wrap{grid-template-columns:1fr; padding-bottom: 80px;}
            .cart{position:static; max-height:none; margin-bottom: 20px;}
            .tablewrap{max-height:none}
            .cartbar.show{display:flex;position:fixed;left:0;right:0;bottom:0;z-index:40;background:var(--amber);color:#0b1220;padding:16px 20px;justify-content:space-between;align-items:center;font-weight:800;box-shadow:0 -10px 25px -5px rgba(0,0,0,0.5)}
            .cartbar button{background:#0b1220;color:var(--amber);border:0;border-radius:8px;padding:12px 20px;font-weight:800;font-size:.95rem; cursor:pointer}
            .back-top{bottom:82px;right:14px}
        }
        
        @media(max-width: 640px){
            .rt thead{display:none}
            .rt,.rt tbody,.rt tr,.rt td{display:block;width:100%}
            .rt tr{border:1px solid var(--border);border-radius:12px;margin-bottom:12px;padding:12px;background:var(--surface-2); position:relative}
            .rt td{display:flex;justify-content:space-between;align-items:center;gap:12px;border:0;padding:8px 0;text-align:right}
            .rt td::before{content:attr(data-label);font-weight:700;font-size:.72rem;text-transform:uppercase;color:var(--text-dim);text-align:left;flex-shrink:0}
            .rt td[colspan]::before{content:none}
            .rt td[colspan]{display:block;text-align:center}
            .rt tr:hover td{background:transparent}
            .wrap{padding:0 12px;margin:14px auto}
            .card{padding:16px}
            td input.qty{width:100px;padding:10px; font-size:1rem}
            td input[type=checkbox]{width:28px;height:28px}
            .toolbar { flex-wrap: wrap; }
            .toolbar input { font-size: 0.95rem; padding: 12px; }
            .filters { gap:6px; }
            .filter-btn { flex:1 1 calc(33.33% - 6px); justify-content:center; padding:10px 6px; font-size:.78rem; }
            .back-top .tip{display:none}
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="wrap">
    <!-- PRODUCT LIST -->
    <section class="card">
        <div class="toolbar">
            <input id="q" type="search" placeholder="Search drinks by name or category..." autocomplete="off" autofocus>
            <span class="count" id="count"></span>
        </div>
        <!-- Quick Sort Filter Buttons -->
        <div class="filters" role="group" aria-label="Quick filters">
            <button type="button" class="filter-btn all on" data-filter="all">
                All <span class="fc" id="fcAll">0</span>
            </button>
            <button type="button" class="filter-btn avail" data-filter="available">
                Available <span class="fc" id="fcAvail">0</span>
            </button>
            <button type="button" class="filter-btn low" data-filter="low">
                Low Stock <span class="fc" id="fcLow">0</span>
            </button>
            <button type="button" class="filter-btn out" data-filter="out">
                Out of Stock <span class="fc" id="fcOut">0</span>
            </button>
        </div>
        <div class="tablewrap">
            <table class="rt">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="num">Price (<?= CURRENCY ?>)</th>
                        <th>Status</th>
                        <th class="ctr">Select</th>
                        <th class="ctr">Bottles</th>
                    </tr>
                </thead>
                <tbody id="tbody"></tbody>
            </table>
        </div>
    </section>

    <!-- CART -->
    <aside class="card cart">
        <h2>Current Sale</h2>
        <div class="meta">Cashier: <b><?= e($user['name']) ?></b></div>
        
        <div class="items" id="items"></div>
        
        <div class="total"><span>Total To Pay</span><b id="total"><?= CURRENCY ?> 0</b></div>
        
        <button class="btn primary" id="pay" disabled>Complete Payment</button>
        <button class="btn ghost" id="holdBtn" style="color:var(--amber); border:1px solid var(--amber); margin-top:8px;">Hold Bill (Tab)</button>
        <button class="btn ghost" id="clear">Clear Cart</button>
        
        <!-- HELD BILLS SECTION -->
        <div class="held-section">
            <h3>Held Bills</h3>
            <div class="held-list" id="heldList">
                <div class="held-empty">No bills on hold</div>
            </div>
        </div>

        <div class="hint" id="hint"></div>
    </aside>
</div>

<!-- MOBILE CART BAR -->
<div class="cartbar" id="cartBar">
    <span id="cbText"></span>
    <button id="cbBtn">View Cart</button>
</div>

<!-- BACK TO TOP -->
<button class="back-top" id="backTop" type="button" aria-label="Move Up" title="Move Up">
    <span class="tip">Move Up</span>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 19V5"/>
        <path d="M5 12l7-7 7 7"/>
    </svg>
</button>

<!-- SUCCESS MODAL -->
<div class="modal" id="done">
    <div class="box">
        <div class="success-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <h3>Payment Received</h3>
        <p class="subtitle">The sale has been recorded successfully.</p>
        
        <div class="receipt-box">
            <div class="receipt-row">
                <span>Receipt No</span>
                <strong id="dRec">R000000</strong>
            </div>
            <div class="receipt-row">
                <span>Total Amount</span>
                <span class="big-total" id="dTotal">0</span>
            </div>
        </div>

        <div class="row">
            <button class="btn ghost" id="dPrint">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:text-bottom; margin-right:6px;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Print Invoice for Customer
            </button>
            <button class="btn primary" id="dNew">Start New Sale</button>
        </div>
    </div>
</div>

<!-- HOLD BILL MODAL -->
<div class="modal" id="holdModal">
    <div class="box hold-box">
        <h3>Hold Current Bill</h3>
        <p>Enter a name to identify this tab (e.g., "Table 4", "John").</p>
        <input type="text" id="holdName" placeholder="e.g. Table 4" autocomplete="off">
        <div class="actions">
            <button class="btn ghost" id="holdCancel">Cancel</button>
            <button class="btn primary" id="holdConfirm">Save Bill</button>
        </div>
    </div>
</div>

<script>
const CSRF = <?= json_encode(csrf_token()) ?>, CUR = <?= json_encode(CURRENCY) ?>;
const CASHIER_NAME = <?= json_encode($user['name']) ?>;
const $ = id => document.getElementById(id);
const fmt = n => Number(n).toLocaleString();
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const debounce = (fn, ms=250) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

const cart = new Map(); 
let rows = [];
let lastCompletedSale = null;
let currentFilter = 'all';
let heldBills = JSON.parse(localStorage.getItem('bw_held_bills') || '[]');
let currentHeldBillName = null; 

/* Back-to-top */
const backTop = $('backTop');
const toggleBackTop = () => backTop.classList.toggle('show', window.scrollY > 200);
window.addEventListener('scroll', toggleBackTop, { passive: true });
backTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
toggleBackTop();

async function load() {
    const r = await (await fetch('api/products.php?action=list&q=' + encodeURIComponent($('q').value.trim()))).json();
    if (r.error) { location.href = 'login.php'; return; }
    rows = r.rows;
    
    // Sync cart with latest stock
    for (const p of rows) { 
        const c = cart.get(String(p.product_id));
        if (c) {
            c.max = +p.bottles_available; c.price = +p.price;
            if (c.max < 1) cart.delete(String(p.product_id));
            else c.qty = Math.min(c.qty, c.max);
        }
    }
    updateFilterCounts();
    renderTable(); renderCart();
}

function bucketOf(p) {
    if (p.status === 'Out of Stock') return 'out';
    if (p.status === 'Low Stock') return 'low';
    return 'available';
}

function updateFilterCounts() {
    let a = 0, l = 0, o = 0;
    for (const p of rows) {
        const b = bucketOf(p);
        if (b === 'available') a++;
        else if (b === 'low') l++;
        else o++;
    }
    $('fcAll').textContent = rows.length;
    $('fcAvail').textContent = a;
    $('fcLow').textContent = l;
    $('fcOut').textContent = o;
}

function renderTable() {
    const filtered = currentFilter === 'all' ? rows : rows.filter(p => bucketOf(p) === currentFilter);
    $('count').textContent = filtered.length + ' item' + (filtered.length === 1 ? '' : 's') +
        (currentFilter !== 'all' && filtered.length !== rows.length ? ' (of ' + rows.length + ')' : '');
    $('tbody').innerHTML = filtered.length ? filtered.map(p => {
        const id = String(p.product_id), out = p.status === 'Out of Stock', c = cart.get(id);
        return `<tr class="${c?'sel':''} ${out?'out':''}">
            <td data-label="Item">
                <div style="text-align:left">
                    <b style="font-size:1.05rem">${esc(p.product_name)}</b><br>
                    <small style="color:var(--text-dim)">${esc(p.category)} | ${fmt(p.bottles_available)} left</small>
                </div>
            </td>
            <td class="num" data-label="Price"><b>${fmt(p.price)}</b></td>
            <td data-label="Status"><span class="badge ${p.status.split(' ')[0]}">${p.status}</span></td>
            <td class="ctr" data-label="Select"><input type="checkbox" data-id="${id}" ${c?'checked':''} ${out?'disabled':''}></td>
            <td class="ctr" data-label="Bottles"><input class="qty" type="number" min="1" max="${p.bottles_available}" value="${c?c.qty:1}" data-q="${id}" ${c?'':'disabled'}></td>
        </tr>`;
    }).join('') : `<tr><td colspan="5" style="text-align:center;color:var(--text-mute);padding:40px">No drinks match this filter.</td></tr>`;
}

function renderCart() {
    let total = 0;
    const html = [...cart.values()].map(c => {
        const line = c.price * c.qty; total += line;
        return `<div class="line">
            <div>
                <span class="nm">${esc(c.name)}</span>
                <small>${c.qty} x ${fmt(c.price)}</small>
            </div>
            <div style="display:flex;align-items:center">
                <span class="amt">${fmt(line)}</span>
                <button class="rm" data-rm="${c.id}" title="Remove">&times;</button>
            </div>
        </div>`;
    }).join('');
    
    $('items').innerHTML = html || '<div class="empty"><svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:0.3"><path d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"></path></svg> No items yet. Tick a drink to add.</div>';
    $('total').textContent = CUR + ' ' + fmt(total);
    
    $('pay').disabled = cart.size === 0;
    $('holdBtn').disabled = cart.size === 0;
    
    $('cbText').textContent = cart.size + ' item' + (cart.size === 1 ? '' : 's') + ' | ' + CUR + ' ' + fmt(total);
    $('cartBar').classList.toggle('show', cart.size > 0);
    
    renderHeldBills();
}

/* Held Bills Logic */
function renderHeldBills() {
    if (heldBills.length === 0) {
        $('heldList').innerHTML = '<div class="held-empty">No bills on hold</div>';
        return;
    }
    
    $('heldList').innerHTML = heldBills.map((bill, index) => {
        const total = bill.items.reduce((sum, item) => sum + (item.price * item.qty), 0);
        const time = new Date(bill.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        return `
            <div class="held-item">
                <div class="held-info" onclick="resumeBill(${index})">
                    <div class="h-name">${esc(bill.name)}</div>
                    <span class="h-time">${time}</span>
                    <div class="h-total">${CUR} ${fmt(total)}</div>
                </div>
                <button class="held-print" onclick="printHeldBill(${index})" title="Print Pro-forma Bill">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                </button>
            </div>
        `;
    }).join('');
}

$('holdBtn').onclick = () => {
    if (cart.size === 0) return;
    
    // Open Custom Modal
    $('holdName').value = currentHeldBillName || "Tab " + (heldBills.length + 1);
    $('holdModal').classList.add('open');
    setTimeout(() => $('holdName').select(), 100);
};

$('holdCancel').onclick = () => $('holdModal').classList.remove('open');

$('holdConfirm').onclick = () => {
    const billName = $('holdName').value.trim();
    if (!billName) {
        alert("Please enter a name for the bill.");
        return;
    }
    
    // If we are currently editing a held bill, remove the old one first to prevent duplicates
    if (currentHeldBillName) {
        const oldIndex = heldBills.findIndex(b => b.name === currentHeldBillName);
        if (oldIndex !== -1) heldBills.splice(oldIndex, 1);
        currentHeldBillName = null;
    }
    
    heldBills.push({
        name: billName,
        items: [...cart.values()],
        timestamp: Date.now()
    });
    
    localStorage.setItem('bw_held_bills', JSON.stringify(heldBills));
    
    cart.clear();
    $('hint').textContent = '';
    $('holdModal').classList.remove('open');
    renderTable(); 
    renderCart();
    showToast('Bill held successfully.');
};

function resumeBill(index) {
    if (cart.size > 0 && !currentHeldBillName) {
        if (!confirm("Your current cart has items. Loading this bill will overwrite them. Continue?")) return;
    }
    
    const bill = heldBills[index];
    heldBills.splice(index, 1);
    localStorage.setItem('bw_held_bills', JSON.stringify(heldBills));
    
    cart.clear();
    bill.items.forEach(item => {
        cart.set(String(item.id), { ...item });
    });
    
    currentHeldBillName = bill.name; // Remember which bill we are editing
    renderTable();
    renderCart();
    showToast(`Loaded bill: ${bill.name}`);
}

/* Clear logic puts the bill back if it was held */
$('clear').onclick = () => { 
    if (currentHeldBillName) {
        // Automatically save it back to held bills
        heldBills.push({
            name: currentHeldBillName,
            items: [...cart.values()],
            timestamp: Date.now()
        });
        localStorage.setItem('bw_held_bills', JSON.stringify(heldBills));
        showToast(`Bill "${currentHeldBillName}" returned to Held Bills.`);
        currentHeldBillName = null;
    } else {
        showToast('Cart cleared.');
    }
    
    cart.clear(); 
    $('hint').textContent = ''; 
    renderTable(); 
    renderCart(); 
};

/* Print function for Held Bills */
function printHeldBill(index) {
    const bill = heldBills[index];
    const total = bill.items.reduce((sum, item) => sum + (item.price * item.qty), 0);
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Bill - ${esc(bill.name)}</title>
            <style>
                @page { size: 80mm auto; margin: 0; }
                body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 10px; color: #0f172a; background: #fff; display: flex; justify-content: center; align-items: flex-start; }
                .rc { width: 76mm; padding: 12px; font-size: 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; }
                .logo-container { text-align: center; margin-bottom: 8px; }
                .logo-container img { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #cbd5e1; }
                .rc h1 { margin: 0 0 2px; text-align: center; font-size: 15px; font-weight: 800; color: #0f172a; } 
                .rc .sub-header { text-align: center; font-size: 10px; color: #475569; line-height: 1.4; margin-bottom: 8px; }
                .rc .badge { text-align: center; font-weight: 700; font-size: 11px; background: #f1f5f9; padding: 4px; border-radius: 4px; margin: 6px 0; color: #334155; text-transform: uppercase; }
                .rc .c { text-align: center; color: #64748b; margin: 2px 0; font-size: 10px; }
                hr { border: 0; border-top: 1px dashed #cbd5e1; margin: 8px 0; }
                table { width: 100%; border-collapse: collapse; } td { padding: 3px 0; vertical-align: top; font-size: 11px; } .r { text-align: right; }
                .tot { display: flex; justify-content: space-between; font-weight: 700; font-size: 13px; margin-top: 6px; }
                .footer-note { text-align: center; font-size: 10px; color: #64748b; margin-top: 10px; font-style: italic; }
                @media print { body { padding: 0; background: #fff; display: block; } .rc { border: none; border-radius: 0; width: 72mm; padding: 0; margin: 0 auto; box-shadow: none; } }
            </style>
        </head>
        <body>
            <div class="rc">
                <h1>BIG WALLET LOUNGE - BALA</h1>
                <div class="sub-header">
                    Te-Cambia B, Bala Town Council, Kole District<br>
                    WhatsApp: +211925830541 | Tel: 0787072542<br>
                    Email: info@bigwalletlounge.com
                </div>
                <div class="logo-container">
                    <img src="logo.png" alt="Logo">
                </div>
                <div class="badge">INTERIM BILL</div>
                <div class="c">Name: <b>${esc(bill.name)}</b></div>
                <div class="c">Date: ${new Date(bill.timestamp).toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit', hour12:true})}</div>
                <hr>
                <table>
                    ${bill.items.map(i => `
                        <tr><td colspan="2"><b>${esc(i.name)}</b></td></tr>
                        <tr><td>${i.qty} x ${fmt(i.price)}</td><td class="r">${fmt(i.price * i.qty)}</td></tr>
                    `).join('')}
                </table>
                <hr>
                <div class="tot"><span>TOTAL:</span><span>${CUR} ${fmt(total)}</span></div>
                <hr>
                <div class="footer-note">This is NOT a receipt. Please pay at the counter.</div>
            </div>
            <script>
                window.onload = function() { window.print(); }
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        currentFilter = btn.dataset.filter;
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.toggle('on', b === btn));
        renderTable();
    });
});

$('tbody').addEventListener('change', e => {
    const t = e.target, row = t.closest('tr');
    if (t.dataset.id) { 
        const p = rows.find(x => String(x.product_id) === t.dataset.id), q = row.querySelector('.qty');
        if (t.checked) {
            const qty = Math.max(1, Math.min(+q.value || 1, +p.bottles_available));
            cart.set(t.dataset.id, {id: p.product_id, name: p.product_name, price: +p.price, qty, max: +p.bottles_available});
            q.value = qty; q.disabled = false; q.focus(); q.select();
        } else {
            cart.delete(t.dataset.id); q.disabled = true; q.value = 1;
        }
        row.classList.toggle('sel', t.checked);
        renderCart();
    }
});

$('tbody').addEventListener('input', e => {
    const t = e.target; if (!t.dataset.q) return;
    const c = cart.get(t.dataset.q); if (!c) return;
    let v = parseInt(t.value, 10); if (isNaN(v) || v < 1) v = 1;
    if (v > c.max) { v = c.max; t.value = v; }
    c.qty = v; renderCart();
});

$('items').addEventListener('click', e => {
    if (!e.target.dataset.rm) return;
    cart.delete(String(e.target.dataset.rm)); renderTable(); renderCart();
});

$('q').addEventListener('input', debounce(load));
$('cbBtn').onclick = () => document.querySelector('.cart').scrollIntoView({behavior: 'smooth'});

$('pay').onclick = async () => {
    const rawItems = [...cart.values()];
    const items = rawItems.map(c => ({product_id: c.id, qty: c.qty}));
    if (!items.length) return;
    $('pay').disabled = true; $('pay').textContent = 'Saving...'; $('hint').textContent = '';
    try {
        const res = await fetch('api/pos.php', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF':CSRF}, body:JSON.stringify({items})});
        const r = await res.json();
        if (r.error) { 
            $('hint').textContent = r.error; 
            $('hint').className = 'hint bad'; 
            // Do not auto-load, so the user can read the error
            $('pay').textContent = 'Complete Payment'; 
            $('pay').disabled = false;
        }
        else {
            lastCompletedSale = {
                receipt_no: r.receipt_no,
                total: r.total,
                date: new Date(),
                cashier: CASHIER_NAME,
                items: rawItems
            };
            $('dRec').textContent = r.receipt_no;
            $('dTotal').textContent = CUR + ' ' + fmt(r.total);
            $('done').classList.add('open'); cart.clear(); currentHeldBillName = null; load();
            $('pay').textContent = 'Complete Payment'; $('pay').disabled = true;
        }
    } catch (err) { 
        $('hint').textContent = 'Network problem. Nothing was charged.'; 
        $('hint').className = 'hint bad'; 
        $('pay').textContent = 'Complete Payment'; $('pay').disabled = false;
    }
};

$('dPrint').onclick = () => {
    if (!lastCompletedSale) return;
    printReceipt(lastCompletedSale);
};

function printReceipt(data) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Sales Receipt - ${esc(data.receipt_no)}</title>
            <style>
                @page { size: 80mm auto; margin: 0; }
                body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 10px; color: #0f172a; background: #fff; display: flex; justify-content: center; align-items: flex-start; }
                .rc { width: 76mm; padding: 12px; font-size: 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; }
                .logo-container { text-align: center; margin-bottom: 8px; }
                .logo-container img { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #cbd5e1; }
                .rc h1 { margin: 0 0 2px; text-align: center; font-size: 15px; font-weight: 800; color: #0f172a; } 
                .rc .sub-header { text-align: center; font-size: 10px; color: #475569; line-height: 1.4; margin-bottom: 8px; }
                .rc .badge { text-align: center; font-weight: 700; font-size: 11px; background: #f1f5f9; padding: 4px; border-radius: 4px; margin: 6px 0; color: #334155; text-transform: uppercase; }
                .rc .c { text-align: center; color: #64748b; margin: 2px 0; font-size: 10px; }
                hr { border: 0; border-top: 1px dashed #cbd5e1; margin: 8px 0; }
                table { width: 100%; border-collapse: collapse; } td { padding: 3px 0; vertical-align: top; font-size: 11px; } .r { text-align: right; }
                .tot { display: flex; justify-content: space-between; font-weight: 700; font-size: 13px; margin-top: 6px; }
                .footer-note { text-align: center; font-size: 10px; color: #64748b; margin-top: 10px; font-style: italic; }
                @media print { body { padding: 0; background: #fff; display: block; } .rc { border: none; border-radius: 0; width: 72mm; padding: 0; margin: 0 auto; box-shadow: none; } }
            </style>
        </head>
        <body>
            <div class="rc">
                <h1>BIG WALLET LOUNGE - BALA</h1>
                <div class="sub-header">
                    Te-Cambia B, Bala Town Council, Kole District<br>
                    WhatsApp: +211925830541 | Tel: 0787072542<br>
                    Email: info@bigwalletlounge.com
                </div>
                <div class="logo-container">
                    <img src="logo.png" alt="Logo">
                </div>
                <div class="badge">Sales Receipt</div>
                <div class="c">Receipt No: ${esc(data.receipt_no)}</div>
                <div class="c">Date: ${data.date.toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit', hour12:true})}</div>
                <div class="c">Served by: ${esc(data.cashier)}</div>
                <hr>
                <table>
                    ${data.items.map(i => `
                        <tr><td colspan="2"><b>${esc(i.name)}</b></td></tr>
                        <tr><td>${i.qty} x ${fmt(i.price)}</td><td class="r">${fmt(i.price * i.qty)}</td></tr>
                    `).join('')}
                </table>
                <hr>
                <div class="tot"><span>TOTAL AMOUNT:</span><span>${CUR} ${fmt(data.total)}</span></div>
                <hr>
                <div class="footer-note">Thank you for doing business with Big Wallet Lounge!</div>
            </div>
            <script>
                window.onload = function() { window.print(); }
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

$('dNew').onclick = () => { $('done').classList.remove('open'); $('q').value = ''; load(); $('q').focus(); };

load();
</script></body></html>