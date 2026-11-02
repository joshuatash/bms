<?php
require __DIR__ . '/config.php';
require_login();
if (!in_array(current_user()['role'], ['admin', 'manager'], true)) { header('Location: pos.php'); exit; }

$user = current_user();
$isManager = $user['role'] === 'manager';
$isAdmin = $user['role'] === 'admin' || $isManager; 

$cats = db()->query("SELECT category_id, name FROM categories ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Products</title>
<style>
:root{
    --navy:#0f172a;--slate:#334155;--muted:#64748b;--line:#e2e8f0;--bg:#f1f5f9;--amber:#f59e0b;--green:#16a34a;--red:#dc2626;--orange:#ea580c;
    /* Dark theme (matches POS) */
    --surface:#111c2e;
    --surface-2:#16243a;
    --surface-3:#1e2d47;
    --text:#e2e8f0;
    --text-dim:#94a3b8;
    --text-mute:#64748b;
    --border:#243349;
}
*{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',system-ui,sans-serif;background:#0b1220;color:var(--text);min-height:100vh}
main{max-width:1200px;margin:24px auto;padding:0 18px;display:grid;gap:20px}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px}
.stat{background:var(--surface);border-radius:12px;padding:16px 18px;border-left:5px solid var(--text-dim);box-shadow:0 4px 6px -1px rgba(0,0,0,.3);border-top:1px solid var(--border);border-right:1px solid var(--border);border-bottom:1px solid var(--border)}
.stat[data-s]{cursor:pointer;transition:.15s}.stat[data-s]:hover{transform:translateY(-2px);box-shadow:0 6px 14px rgba(0,0,0,.4)}
.stat b{display:block;font-size:1.8rem;color:var(--text)}.stat small{color:var(--text-dim)}
.stat.low{border-left-color:var(--orange)}.stat.out{border-left-color:var(--red)}.stat.ok{border-left-color:var(--green)}
.card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px;box-shadow:0 4px 6px -1px rgba(0,0,0,.3)}
.card h2{margin:0 0 14px;font-size:1.05rem;color:var(--text)}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;align-items:end}
label{display:block;font-size:.75rem;font-weight:700;color:var(--text-dim);margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px}
input,select{width:100%;padding:10px 11px;border:1px solid var(--border);border-radius:8px;font-size:.95rem;background:var(--surface-2);color:var(--text)}
input::placeholder{color:var(--text-mute)}
input:focus,select:focus{outline:2px solid var(--amber);border-color:var(--amber);background:var(--surface-3)}
.btn{border:0;border-radius:8px;padding:10px 18px;font-weight:700;cursor:pointer;font-size:.9rem;transition:.15s}
.btn.primary{background:var(--amber);color:#0b1220;box-shadow:0 4px 6px -1px rgba(245,158,11,.3)}.btn.primary:hover{background:#d97706;color:#fff}
.btn.dark{background:var(--text-dim);color:var(--text)}.btn.ghost{background:var(--surface-2);color:var(--text-dim)}
.btn.ghost:hover{background:var(--surface-3);color:var(--text)}
.btn.sm{padding:6px 11px;font-size:.8rem}.btn.danger{background:rgba(220,38,38,.2);color:#f87171}.btn.edit{background:rgba(37,99,235,.2);color:#60a5fa}
.hint{font-size:.82rem;margin-top:10px;min-height:1.2em;color:var(--text-dim)}.hint.good{color:#4ade80}.hint.bad{color:#f87171}
.toolbar{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:14px;align-items:center}
.toolbar input{max-width:300px}.toolbar select{max-width:200px}.toolbar .sp{flex:1}

/* --- SUGGESTION DROPDOWN --- */
.autocomplete-wrap { position: relative; }
.suggestions-box {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 8px;
    box-shadow: 0 6px 16px rgba(0,0,0,0.5);
    display: none;
    z-index: 50;
    max-height: 220px;
    overflow-y: auto;
}
.suggestion-item {
    padding: 10px 14px;
    font-size: 0.9rem;
    color: var(--text);
    cursor: pointer;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.suggestion-item:last-child { border-bottom: none; }
.suggestion-item:hover { background: var(--surface-3); color: var(--amber); }
.suggestion-item small { color: var(--text-dim); font-size: 0.75rem; }

/* --- COUNT TAGS --- */
.count-wrapper { display: flex; gap: 6px; flex-wrap: wrap; }
.count-tag { 
    font-size: .8rem; 
    font-weight: 700; 
    padding: 6px 12px; 
    border-radius: 8px; 
    white-space: nowrap; 
    border: 1px solid transparent;
}
.count-tag.total { background: var(--surface-2); color: var(--text-dim); border-color: var(--border); }
.count-tag.avail { background: rgba(22,163,74,.15); color: #4ade80; border-color: rgba(22,163,74,.3); }
.count-tag.low   { background: rgba(234,88,12,.15); color: #fb923c; border-color: rgba(234,88,12,.3); }
.count-tag.out   { background: rgba(220,38,38,.15); color: #f87171; border-color: rgba(220,38,38,.3); }

.tablewrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:.92rem;color:var(--text)}
th{text-align:left;background:var(--surface-2);color:var(--text-dim);padding:11px 12px;font-size:.75rem;text-transform:uppercase;border-bottom:2px solid var(--border)}
td{padding:11px 12px;border-bottom:1px solid var(--border)}
tr:hover td{background:var(--surface-2)}
.badge{padding:4px 10px;border-radius:999px;font-size:.75rem;font-weight:700;white-space:nowrap}
.badge.Available{background:rgba(22,163,74,.2);color:#4ade80}
.badge.Low{background:rgba(234,88,12,.2);color:#fb923c}
.badge.Out{background:rgba(220,38,38,.2);color:#f87171}
.num{text-align:right}.empty{text-align:center;color:var(--text-mute);padding:30px}
.modal{position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);display:none;place-items:center;z-index:20;padding:12px}
.modal.open{display:grid}
.modal .box{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:24px;width:min(460px,94vw);color:var(--text);box-shadow:0 25px 50px -12px rgba(0,0,0,.6)}
.modal h3{margin:0 0 16px;color:var(--text)}.modal .row{margin-bottom:12px}.modal .actions{display:flex;gap:10px;justify-content:flex-end;margin-top:18px}

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

/* --- SMALL PHONE RESPONSIVENESS --- */
@media(max-width: 640px) {
    main { padding: 0 12px; margin: 14px auto; gap: 14px; }
    .card { padding: 16px; }
    .grid { grid-template-columns: 1fr; }
    .grid > div { grid-column: auto !important; }
    .btn.primary { width: 100%; padding: 14px; font-size: 1rem; }
    .toolbar input, .toolbar select { max-width: none; flex: 1 1 100%; }
    .toolbar .sp { display: none; }
    .toolbar .btn { width: 100%; padding: 12px; }
    .count-wrapper { width: 100%; justify-content: space-between; margin-bottom: 4px; }
    .count-tag { flex: 1; text-align: center; padding: 8px 4px; font-size: 0.7rem; }
    .stats { grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .stat { padding: 12px; }
    .stat b { font-size: 1.3rem; }
    .rt thead { display: none; }
    .rt, .rt tbody, .rt tr, .rt td { display: block; width: 100%; }
    .rt tr { border: 1px solid var(--border); border-radius: 10px; margin-bottom: 12px; padding: 8px 12px; background: var(--surface-2); }
    .rt td { display: flex; justify-content: space-between; align-items: center; gap: 12px; border-bottom: 1px solid var(--border); padding: 8px 2px; text-align: right; }
    .rt td:last-child { border-bottom: 0; padding-bottom: 0; }
    .rt td::before { content: attr(data-label); font-weight: 700; font-size: .68rem; text-transform: uppercase; color: var(--text-dim); text-align: left; flex-shrink: 0; }
    .rt td[colspan]::before { content: none; }
    .rt td[colspan] { display: block; text-align: center; }
    .rt tr:hover td { background: transparent; }
    .btn.sm { padding: 10px 14px; width: 100%; margin-top: 6px; text-align: center; }
    .back-top{bottom:16px;right:14px}
    .back-top .tip{display:none}
}
</style></head><body>

<?php $active = 'products'; include __DIR__ . '/includes/header.php'; ?>

<main>
    <section class="stats">
        <div class="stat" data-s="" title="Show all"><small>Total Products</small><b id="sTotal">0</b></div>
        <div class="stat low" data-s="low" title="Show low stock only"><small>Low Stock</small><b id="sLow">0</b></div>
        <div class="stat out" data-s="out" title="Show out of stock only"><small>Out of Stock</small><b id="sOut">0</b></div>
    </section>

    <?php if ($isAdmin): ?>
    <section class="card">
        <h2>Add Product / Restock</h2>
        <div class="grid">
            <div style="grid-column:span 2" class="autocomplete-wrap">
                <label>Product Name</label>
                <input id="aName" autocomplete="off" placeholder="Type to search or enter new">
                <div id="nameSuggestions" class="suggestions-box"></div>
            </div>
            <div>
                <label>Category</label>
                <select id="aCat"><?php foreach ($cats as $c): ?><option value="<?= $c['category_id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select>
            </div>
            <div>
                <label>Buying Price (Per Bottle)</label>
                <input id="aBuyingPrice" type="number" min="0" step="100" inputmode="decimal">
            </div>
            <div>
                <label>Selling Price (Per Bottle)</label>
                <input id="aPrice" type="number" min="0" step="100" inputmode="decimal">
            </div>
            <div>
                <label>Number of Bottles</label>
                <input id="aBottles" type="number" min="1" step="1" inputmode="numeric" placeholder="e.g. 48">
            </div>
            <div>
                <label>Low Stock Alert Level</label>
                <input id="aThreshold" type="number" min="0" step="1" value="20" inputmode="numeric">
            </div>
            <div><button class="btn primary" id="aSave" style="width:100%">Save Stock</button></div>
        </div>
        <div class="hint" id="aHint"></div>
    </section>
    <?php endif; ?>

    <section class="card">
        <div class="toolbar">
            <input id="q" type="search" placeholder="Search products...">
            <select id="fCat"><option value="0">All categories</option><?php foreach ($cats as $c): ?><option value="<?= $c['category_id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select>
            <select id="fStatus"><option value="">All statuses</option><option value="available">Available</option><option value="low">Low Stock</option><option value="out">Out of Stock</option></select>
            
            <div class="count-wrapper">
                <span class="count-tag total" id="cTotal">0 Total</span>
                <span class="count-tag avail" id="cAvail">0 Available</span>
                <span class="count-tag low" id="cLow">0 Low</span>
                <span class="count-tag out" id="cOut">0 Out</span>
            </div>
            
            <span class="sp"></span>
        </div>
        <div class="tablewrap"><table class="rt">
            <thead><tr><th>#</th><th>Name</th><th>Category</th><th class="num">Bottles</th><th class="num">Buy Price (Bottle)</th><th class="num">Sell Price (Bottle)</th><th>Status</th><?php if ($isAdmin): ?><th>Actions</th><?php endif; ?></tr></thead>
            <tbody id="tbody"></tbody>
        </table></div>
    </section>
</main>

<div class="modal" id="editModal"><div class="box">
    <h3>Edit Product</h3>
    <div class="row"><label>Name</label><input id="eName"></div>
    <div class="row"><label>Category</label><select id="eCat"><?php foreach ($cats as $c): ?><option value="<?= $c['category_id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div class="row"><label>Buying Price (Per Bottle)</label><input id="eBuyingPrice" type="number" min="0"></div>
    <div class="row"><label>Selling Price (Per Bottle)</label><input id="ePrice" type="number" min="0"></div>
    <div class="row"><label>Stock (bottles)</label><input id="eStock" type="number" min="0"></div>
    <div class="row"><label>Low Stock Threshold</label><input id="eThreshold" type="number" min="0"></div>
    <div class="hint bad" id="eErr"></div>
    <div class="actions"><button class="btn ghost" id="eCancel">Cancel</button><button class="btn primary" id="eSave">Save Changes</button></div>
</div></div>

<!-- BACK TO TOP -->
<button class="back-top" id="backTop" type="button" aria-label="Move Up" title="Move Up">
    <span class="tip">Move Up</span>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 19V5"/>
        <path d="M5 12l7-7 7 7"/>
    </svg>
</button>

<script>
const CSRF = <?= json_encode(csrf_token()) ?>, IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
const $ = id => document.getElementById(id);
const fmt = n => {
    const num = Number(n);
    return isNaN(num) ? '0' : num.toLocaleString();
};
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let products = {}, editingId = null, existing = null, cachedSuggestions = [];

/* Back-to-top */
const backTop = $('backTop');
const toggleBackTop = () => backTop.classList.toggle('show', window.scrollY > 200);
window.addEventListener('scroll', toggleBackTop, { passive: true });
backTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
toggleBackTop();

const sentence = s => { s = s.trim().replace(/\s+/g, ' '); return s ? s.charAt(0).toUpperCase() + s.slice(1).toLowerCase() : ''; };
['aName', 'eName'].forEach(id => { if ($(id)) $(id).addEventListener('blur', e => { e.target.value = sentence(e.target.value); }); });

async function api(action, body){
    const opt = body ? {method:'POST', headers:{'Content-Type':'application/json','X-CSRF':CSRF}, body:JSON.stringify(body)} : {};
    let text = '';
    try {
        const res = await fetch('api/products.php?action=' + action, opt);
        text = await res.text();
        return JSON.parse(text);
    } catch (e) {
        const seen = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 220);
        return { error: seen ? 'Server problem: ' + seen : 'Could not reach the server. Check your connection and try again.' };
    }
}
const debounce = (fn, ms=250) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

async function load() {
    const q = encodeURIComponent($('q').value.trim()), c = $('fCat').value, st = $('fStatus').value;
    const r = await (await fetch(`api/products.php?action=list&q=${q}&category=${c}&status=${st}`)).json();
    if (r.error) { location.href = 'login.php'; return; }
    
    products = {};
    
    $('sTotal').textContent = r.stats.total || 0; 
    $('sLow').textContent = r.stats.low || 0; 
    $('sOut').textContent = r.stats.out_of_stock || 0;
    
    const availableCount = (r.stats.total || 0) - (r.stats.low || 0) - (r.stats.out_of_stock || 0);
    $('cTotal').textContent = (r.stats.total || 0) + ' Total';
    $('cAvail').textContent = availableCount + ' Available';
    $('cLow').textContent = (r.stats.low || 0) + ' Low';
    $('cOut').textContent = (r.stats.out_of_stock || 0) + ' Out';

    const cols = IS_ADMIN ? 8 : 7;
    
    $('tbody').innerHTML = r.rows.length ? r.rows.map((p, i) => {
        products[p.product_id] = p;
        const cls = p.status.split(' ')[0];
        return `<tr>
            <td data-label="#">${i+1}</td>
            <td data-label="Name"><b>${esc(p.product_name)}</b></td>
            <td data-label="Category">${esc(p.category)}</td>
            <td class="num" data-label="Bottles">${fmt(p.bottles_available)}</td>
            <td class="num" data-label="Buy Price (Bottle)">${fmt(p.buying_price)}</td>
            <td class="num" data-label="Sell Price (Bottle)">${fmt(p.price)}</td>
            <td data-label="Status"><span class="badge ${cls}">${p.status}</span></td>` +
            (IS_ADMIN ? `<td data-label="Actions"><button class="btn sm edit" data-edit="${p.product_id}">Edit</button> <button class="btn sm danger" data-del="${p.product_id}">Delete</button></td>` : '') + `</tr>`;
    }).join('') : `<tr><td class="empty" colspan="${cols}">No products found.</td></tr>`;
}

$('q').addEventListener('input', debounce(load)); $('fCat').addEventListener('change', load); $('fStatus').addEventListener('change', load);
document.querySelectorAll('.stat[data-s]').forEach(t => t.addEventListener('click', () => { $('fStatus').value = t.dataset.s; load(); }));

$('tbody').addEventListener('click', async e => {
    const ed = e.target.dataset.edit, del = e.target.dataset.del;
    if (ed) {
        const p = products[ed]; editingId = ed;
        $('eName').value = p.product_name; $('eCat').value = p.category_id; $('ePrice').value = p.price; 
        $('eBuyingPrice').value = p.buying_price; $('eStock').value = p.bottles_available; 
        $('eThreshold').value = p.low_stock_threshold ?? 20; $('eErr').textContent = '';
        $('editModal').classList.add('open');
    }
    if (del && confirm(`Delete "${products[del].product_name}"? Past sales stay in the records.`)) {
        const r = await api('delete', { product_id: del }); if (r.error) alert(r.error); load();
    }
});
$('eCancel').onclick = () => $('editModal').classList.remove('open');
$('eSave').onclick = async () => {
    $('eSave').disabled = true; $('eErr').textContent = 'Saving...';
    const r = await api('update', { 
        product_id: editingId, 
        product_name: $('eName').value, 
        category_id: $('eCat').value, 
        price: $('ePrice').value, 
        buying_price: $('eBuyingPrice').value, 
        bottles_available: $('eStock').value,
        low_stock_threshold: $('eThreshold').value 
    });
    $('eSave').disabled = false;
    if (r.error) { $('eErr').textContent = r.error; return; }
    $('eErr').textContent = '';
    $('editModal').classList.remove('open'); load();
};

if (IS_ADMIN) {
    const hint = (t,k='') => { $('aHint').textContent = t; $('aHint').className = 'hint ' + k; };
    const box = $('nameSuggestions');

    const preview = () => {
        const n = parseInt($('aBottles').value, 10) || 0;
        const buy = parseFloat($('aBuyingPrice').value), sell = parseFloat($('aPrice').value);
        let msg = existing
            ? `Existing product. ${fmt(n)} bottle(s) will be ADDED to current stock.`
            : `${fmt(n)} bottle(s) will be added.` + ($('aName').value.trim() ? ' (New product)' : '');
        let kind = existing ? 'good' : '';
        if (!isNaN(buy) && !isNaN(sell) && sell > 0) {
            const profit = sell - buy;
            msg += ` Profit per bottle: ${fmt(profit)}` + (buy > 0 ? ` (${(profit / buy * 100).toFixed(0)}% on cost).` : '.');
            if (profit < 0) { msg += ' Warning: selling below cost!'; kind = 'bad'; }
        }
        hint(msg, kind);
    };

    const suggest = debounce(async () => {
        const q = $('aName').value.trim(); 
        if (q.length < 1) { 
            existing = null; 
            box.style.display = 'none'; 
            preview(); 
            return; 
        }
        const list = await (await fetch('api/products.php?action=suggest&q=' + encodeURIComponent(q))).json();
        cachedSuggestions = list;
        
        if (list.length > 0) {
            box.innerHTML = list.map((p, idx) => `
                <div class="suggestion-item" data-index="${idx}">
                    <span><b>${esc(p.product_name)}</b></span>
                    <small>Sell: ${fmt(p.price)}</small>
                </div>
            `).join('');
            box.style.display = 'block';
        } else {
            box.style.display = 'none';
        }

        // FIX: Strictly trim and lowercase both sides to prevent false "New Product" states
        const normalizedQ = q.toLowerCase();
        existing = list.find(p => p.product_name.trim().toLowerCase() === normalizedQ) || null;
        
        if (existing) { 
            $('aCat').value = existing.category_id; 
            $('aPrice').value = existing.price; 
            $('aBuyingPrice').value = existing.buying_price; 
            $('aThreshold').value = existing.low_stock_threshold ?? 20; 
        }
        preview();
    });

    $('aName').addEventListener('input', suggest);
    
    box.addEventListener('click', e => {
        const item = e.target.closest('.suggestion-item');
        if (!item) return;
        const p = cachedSuggestions[item.dataset.index];
        if (p) {
            $('aName').value = p.product_name;
            $('aCat').value = p.category_id;
            $('aPrice').value = p.price;
            $('aBuyingPrice').value = p.buying_price;
            $('aThreshold').value = p.low_stock_threshold ?? 20;
            existing = p;
            box.style.display = 'none';
            preview();
        }
    });

    document.addEventListener('click', e => {
        if (!e.target.closest('.autocomplete-wrap')) {
            box.style.display = 'none';
        }
    });

    $('aBottles').addEventListener('input', preview);
    $('aBuyingPrice').addEventListener('input', preview);
    $('aPrice').addEventListener('input', preview);
    $('aThreshold').addEventListener('input', preview);
    
    $('aSave').onclick = async () => {
        $('aSave').disabled = true; hint('Saving...');
        const r = await api('add', { 
            product_name: $('aName').value, 
            category_id: $('aCat').value, 
            price: $('aPrice').value, 
            buying_price: $('aBuyingPrice').value, 
            bottles: $('aBottles').value,
            low_stock_threshold: $('aThreshold').value 
        });
        $('aSave').disabled = false;
        if (r.error) { hint(r.error, 'bad'); return; }
        hint(r.message, 'good'); $('aName').value = ''; $('aPrice').value = ''; $('aBuyingPrice').value = ''; $('aBottles').value = ''; $('aThreshold').value = '20'; existing = null; box.style.display = 'none'; load();
    }
    preview();
}

const pre = new URLSearchParams(location.search).get('status');
if (['available', 'low', 'out'].includes(pre)) $('fStatus').value = pre;
load();
</script>
</body></html>