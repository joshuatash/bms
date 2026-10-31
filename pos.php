<?php require __DIR__ . '/config.php'; require_login(); $active = 'pos'; $user = current_user(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Point of Sale</title>
    <style>
        :root{--navy:#0f172a;--slate:#334155;--muted:#64748b;--line:#e2e8f0;--amber:#f59e0b;--green:#16a34a;--red:#dc2626;--orange:#ea580c}
        *{box-sizing:border-box}
        body{margin:0;font-family:'Segoe UI',system-ui,sans-serif;background:linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); color:var(--navy); min-height:100vh}
        
        .wrap{max-width:1400px;margin:20px auto;padding:0 18px;display:grid;grid-template-columns:1fr 400px;gap:20px;align-items:start}
        
        /* Main Left Panel */
        .card{background:#fff;border-radius:16px;padding:20px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05); border:1px solid rgba(255,255,255,0.6)}
        .card h2{margin:0 0 16px;font-size:1.1rem; display:flex; justify-content:space-between; align-items:center}
        
        .toolbar{display:flex;gap:12px;align-items:center;margin-bottom:16px}
        .toolbar input{flex:1;padding:14px 16px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem; background:#f8fafc}
        input:focus{outline:2px solid var(--amber);border-color:var(--amber); background:#fff}
        .count{color:var(--slate);font-size:.85rem;font-weight:600;white-space:nowrap}
        
        /* Table */
        .tablewrap{overflow:auto;max-height:calc(100vh - 200px); border-radius:10px; border:1px solid var(--line)}
        table{width:100%;border-collapse:collapse;font-size:.93rem}
        th{position:sticky;top:0;text-align:left;background:#f8fafc;color:var(--slate);padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid var(--line); z-index:10}
        td{padding:12px 16px;border-bottom:1px solid var(--line);vertical-align:middle}
        tr.sel td{background:#fffbeb}
        tr.out td{opacity:.5}
        tr:hover:not(.sel) td { background: #f8fafc; }
        
        .num{text-align:right}.ctr{text-align:center}
        .badge{padding:4px 10px;border-radius:999px;font-size:.72rem;font-weight:700;white-space:nowrap}
        .badge.Available{background:#dcfce7;color:#166534}.badge.Low{background:#ffedd5;color:#9a3412}.badge.Out{background:#fee2e2;color:#991b1b}
        
        td input[type=checkbox]{width:22px;height:22px;accent-color:var(--amber);cursor:pointer; border-radius:4px}
        td input[type=checkbox]:disabled{cursor:not-allowed}
        td input.qty{width:80px;padding:8px;border:1px solid #cbd5e1;border-radius:8px;font-size:.95rem;text-align:center; font-weight:600}
        td input.qty:disabled{background:#f1f5f9;color:#94a3b8}

        /* Right Side Cart */
        .cart{position:sticky;top:20px; background:#fff; border-radius:16px; padding:20px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.1); border:1px solid rgba(255,255,255,0.6); display:flex; flex-direction:column; max-height:calc(100vh - 40px)}
        .cart h2 { margin:0 0 10px; font-size:1.2rem; }
        .meta{font-size:.85rem;color:var(--slate);padding-bottom:14px;border-bottom:1px dashed var(--line); margin-bottom:10px}
        
        .items{flex:1; overflow:auto; padding:8px 0; min-height:150px}
        .items .empty{color:var(--slate);text-align:center;padding:40px 0;font-size:.9rem; display:flex; flex-direction:column; align-items:center; gap:10px}
        
        .line{display:flex;justify-content:space-between;gap:10px;align-items:center;padding:12px 0;border-bottom:1px solid #f1f5f9;font-size:.95rem}
        .line .nm{font-weight:700; color:var(--navy)}.line small{display:block;color:var(--slate); margin-top:2px}
        .line .rm{border:0;background:#fee2e2;color:#991b1b;border-radius:6px;cursor:pointer;font-weight:700;width:28px;height:28px;margin-left:12px; display:flex; align-items:center; justify-content:center; transition:0.2s}
        .line .rm:hover{background:#fca5a5}
        .line .amt{white-space:nowrap;font-weight:800; font-size:1rem; color:var(--navy)}
        
        .total{display:flex;justify-content:space-between;align-items:center;padding:18px 0 14px;border-top:2px solid var(--navy);margin-top:10px;font-weight:800; font-size:1.1rem}
        .total b{font-size:1.8rem; color:var(--green)}
        
        .btn{border:0;border-radius:10px;padding:16px 18px;font-weight:800;cursor:pointer;font-size:1rem;width:100%; transition:0.2s}
        .btn.primary{background:var(--amber);color:var(--navy); box-shadow:0 4px 6px -1px rgba(245,158,11,0.3)}
        .btn.primary:hover:not(:disabled){background:#d97706;color:#fff; transform:translateY(-1px)}
        .btn:disabled{opacity:.45;cursor:not-allowed; box-shadow:none}
        .btn.ghost{background:#f1f5f9;color:var(--slate);margin-top:10px;padding:12px;font-size:.9rem}
        .btn.ghost:hover{background:#e2e8f0}
        
        .hint{font-size:.85rem;margin-top:12px;min-height:1.2em;color:var(--slate); text-align:center; font-weight:600}
        .hint.bad{color:var(--red)}
        
        /* Modals */
        .modal{position:fixed;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(4px);display:none;place-items:center;z-index:50}
        .modal.open{display:grid}
        .modal .box{background:#fff;border-radius:20px;padding:30px;width:min(400px,94vw);text-align:center; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25)}
        .modal h3{margin:0 0 8px;color:var(--green); font-size:1.4rem}
        .modal .big{font-size:2.2rem;font-weight:900;margin:16px 0; color:var(--navy)}
        .modal .row{display:flex;gap:12px;margin-top:24px}
        .modal .btn{padding:14px}
        a.btn{display:block;text-decoration:none;text-align:center}
        
        /* Mobile Cart Bar */
        .cartbar{display:none}
        
        /* --- RESPONSIVE FIXES --- */
        @media(max-width: 1050px){
            .wrap{grid-template-columns:1fr; padding-bottom: 80px;}
            .cart{position:static; max-height:none; margin-bottom: 20px;}
            .tablewrap{max-height:none}
            .cartbar.show{display:flex;position:fixed;left:0;right:0;bottom:0;z-index:40;background:#0f172a;color:#fff;padding:16px 20px;justify-content:space-between;align-items:center;font-weight:800;box-shadow:0 -10px 25px -5px rgba(0,0,0,0.3)}
            .cartbar button{background:var(--amber);color:var(--navy);border:0;border-radius:8px;padding:12px 20px;font-weight:800;font-size:.95rem; cursor:pointer}
        }
        
        @media(max-width: 640px){
            .rt thead{display:none}
            .rt,.rt tbody,.rt tr,.rt td{display:block;width:100%}
            .rt tr{border:1px solid #e2e8f0;border-radius:12px;margin-bottom:12px;padding:12px;background:#fff; position:relative}
            .rt td{display:flex;justify-content:space-between;align-items:center;gap:12px;border:0;padding:8px 0;text-align:right}
            .rt td::before{content:attr(data-label);font-weight:700;font-size:.72rem;text-transform:uppercase;color:var(--slate);text-align:left;flex-shrink:0}
            .rt td[colspan]::before{content:none}
            .rt td[colspan]{display:block;text-align:center}
            .rt tr:hover td{background:transparent}
            .wrap{padding:0 12px;margin:14px auto}
            .card{padding:16px}
            td input.qty{width:100px;padding:10px; font-size:1rem}
            td input[type=checkbox]{width:28px;height:28px}
            .toolbar { flex-wrap: wrap; }
            .toolbar input { font-size: 0.95rem; padding: 12px; }
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
        <button class="btn ghost" id="clear">Clear Cart</button>
        <div class="hint" id="hint"></div>
    </aside>
</div>

<!-- MOBILE CART BAR -->
<div class="cartbar" id="cartBar">
    <span id="cbText"></span>
    <button id="cbBtn">View Cart</button>
</div>

<!-- SUCCESS MODAL -->
<div class="modal" id="done"><div class="box">
    <h3>Payment Complete</h3>
    <div>Receipt: <b id="dRec"></b></div>
    <div class="big" id="dTotal"></div>
    <div class="row">
        <a class="btn ghost" id="dPrint" target="_blank" style="margin:0">Print Receipt</a>
        <button class="btn primary" id="dNew">New Sale</button>
    </div>
</div></div>

<script>
const CSRF = <?= json_encode(csrf_token()) ?>, CUR = <?= json_encode(CURRENCY) ?>;
const $ = id => document.getElementById(id);
const fmt = n => Number(n).toLocaleString();
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const debounce = (fn, ms=250) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

const cart = new Map(); 
let rows = [];

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
    renderTable(); renderCart();
}

function renderTable() {
    $('count').textContent = rows.length + ' item' + (rows.length === 1 ? '' : 's');
    $('tbody').innerHTML = rows.length ? rows.map(p => {
        const id = String(p.product_id), out = p.status === 'Out of Stock', c = cart.get(id);
        return `<tr class="${c?'sel':''} ${out?'out':''}">
            <td data-label="Item">
                <div style="text-align:left">
                    <b style="font-size:1.05rem">${esc(p.product_name)}</b><br>
                    <small style="color:#64748b">${esc(p.category)} | ${fmt(p.bottles_available)} left</small>
                </div>
            </td>
            <td class="num" data-label="Price"><b>${fmt(p.price)}</b></td>
            <td data-label="Status"><span class="badge ${p.status.split(' ')[0]}">${p.status}</span></td>
            <td class="ctr" data-label="Select"><input type="checkbox" data-id="${id}" ${c?'checked':''} ${out?'disabled':''}></td>
            <td class="ctr" data-label="Bottles"><input class="qty" type="number" min="1" max="${p.bottles_available}" value="${c?c.qty:1}" data-q="${id}" ${c?'':'disabled'}></td>
        </tr>`;
    }).join('') : `<tr><td colspan="5" style="text-align:center;color:#64748b;padding:40px">No drinks found.</td></tr>`;
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
    $('cbText').textContent = cart.size + ' item' + (cart.size === 1 ? '' : 's') + ' | ' + CUR + ' ' + fmt(total);
    $('cartBar').classList.toggle('show', cart.size > 0);
}

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

$('clear').onclick = () => { cart.clear(); $('hint').textContent = ''; renderTable(); renderCart(); };
$('q').addEventListener('input', debounce(load));
$('cbBtn').onclick = () => document.querySelector('.cart').scrollIntoView({behavior: 'smooth'});

$('pay').onclick = async () => {
    const items = [...cart.values()].map(c => ({product_id: c.id, qty: c.qty}));
    if (!items.length) return;
    $('pay').disabled = true; $('pay').textContent = 'Saving...'; $('hint').textContent = '';
    try {
        const r = await (await fetch('api/pos.php', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF':CSRF}, body:JSON.stringify({items})})).json();
        if (r.error) { $('hint').textContent = r.error; $('hint').className = 'hint bad'; load(); }
        else {
            $('dRec').textContent = r.receipt_no;
            $('dTotal').textContent = CUR + ' ' + fmt(r.total);
            $('dPrint').href = 'receipt.php?id=' + r.sale_id;
            $('done').classList.add('open'); cart.clear(); load();
        }
    } catch (err) { $('hint').textContent = 'Network problem. Nothing was charged.'; $('hint').className = 'hint bad'; }
    $('pay').textContent = 'Complete Payment'; $('pay').disabled = cart.size === 0;
};

$('dNew').onclick = () => { $('done').classList.remove('open'); $('q').value = ''; load(); $('q').focus(); };

load();
</script></body></html>