<?php 
require __DIR__ . '/config.php'; 
require_login(); 
if (!in_array(current_user()['role'], ['admin', 'manager'], true)) { header('Location: pos.php'); exit; }
$active = 'pa_system'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PA System</title>
    <style>
        :root { --navy:#0f172a; --slate:#334155; --muted:#64748b; --line:#e2e8f0; --amber:#f59e0b; --green:#16a34a; --red:#dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); color: var(--navy); }
        main { max-width: 1000px; margin: 24px auto; padding: 0 18px; display: grid; gap: 20px; }
        .head { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .head h2 { margin: 0; font-size: 1.4rem; } .head p { margin: 2px 0 0; color: var(--muted); }
        .tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
        .tile { background: #fff; border-radius: 12px; padding: 16px 18px; border-left: 5px solid var(--slate); box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .tile small { color: var(--slate); font-weight: 600; } .tile b { display: block; font-size: 1.5rem; margin-top: 4px; }
        .tile.money { border-color: var(--green); }
        .card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .card h2 { margin: 0 0 14px; font-size: 1.05rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; align-items: end; }
        label { display: block; font-size: .75rem; font-weight: 700; color: var(--slate); margin-bottom: 5px; text-transform: uppercase; letter-spacing: .4px; }
        input, select, textarea { width: 100%; padding: 10px 11px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: .95rem; background: #fff; font-family: inherit; }
        textarea { resize: vertical; min-height: 100px; line-height: 1.5; }
        input:focus, select:focus, textarea:focus { outline: 2px solid var(--amber); border-color: var(--amber); }
        .btn { border: 0; border-radius: 8px; padding: 10px 18px; font-weight: 700; cursor: pointer; font-size: .9rem; text-decoration: none; display: inline-block; text-align: center; }
        .btn.primary { background: var(--amber); color: var(--navy); } .btn.primary:hover:not(:disabled) { background: #d97706; color: #fff; }
        .btn.dark { background: var(--navy); color: #fff; } .btn.dark:hover { background: #1e293b; }
        .btn.ghost { background: #e2e8f0; color: var(--slate); } .btn.ghost:hover { background: #cbd5e1; }
        .btn.sm { padding: 6px 12px; font-size: .8rem; }
        .hint { font-size: .85rem; margin-top: 10px; min-height: 1.2em; font-weight: 600; }
        .hint.good { color: var(--green); } .hint.bad { color: var(--red); }
        .tablewrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .92rem; }
        th { text-align: left; background: #f8fafc; color: var(--slate); padding: 11px 12px; font-size: .75rem; text-transform: uppercase; border-bottom: 2px solid var(--line); }
        td { padding: 11px 12px; border-bottom: 1px solid var(--line); vertical-align: top; }
        .num { text-align: right; }
        .pre-wrap { white-space: pre-wrap; font-family: inherit; font-size: 0.9rem; color: var(--slate); margin-top: 4px; }
        .modal { position: fixed; inset: 0; background: rgba(15,23,42,.55); display: none; place-items: center; z-index: 60; padding: 12px; }
        .modal.open { display: grid; } 
        .modal .box { background: #fff; border-radius: 14px; padding: 24px; width: min(400px,100%); text-align: center; }
        .modal h3 { margin: 0 0 6px; color: var(--green); font-size: 1.4rem; } 
        .modal .big { font-size: 1.7rem; font-weight: 800; margin: 10px 0; color: var(--navy); }
        .modal .row { display: flex; gap: 10px; margin-top: 18px; } 
        .modal .row .btn { flex: 1; text-align: center; padding: 12px; }
        @media(max-width: 640px) {
            main { padding: 0 12px; margin: 14px auto; gap: 14px; }
            .tiles { grid-template-columns: 1fr; }
            .grid { grid-template-columns: 1fr; }
            .btn.primary, .btn.ghost { width: 100%; padding: 14px; font-size: 1rem; }
            .head { flex-direction: column; align-items: stretch; text-align: center; }
            .btn.dark { width: 100%; }
            .rt thead { display: none; }
            .rt, .rt tbody, .rt tr, .rt td { display: block; width: 100%; }
            .rt tr { border: 1px solid var(--line); border-radius: 10px; margin-bottom: 12px; padding: 8px 12px; background: #f8fafc; }
            .rt td { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; border-bottom: 1px solid #f1f5f9; padding: 8px 2px; text-align: left; }
            .rt td:last-child { border-bottom: 0; padding-bottom: 0; }
            .rt td::before { content: attr(data-label); font-weight: 700; font-size: .68rem; text-transform: uppercase; color: var(--muted); text-align: left; flex-shrink: 0; }
            .rt td[colspan]::before { content: none; } .rt td[colspan] { display: block; text-align: center; }
            .btn.sm { width: 100%; margin-top: 8px; padding: 10px; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
    <div class="head">
        <div>
            <h2>PA System Collections</h2>
            <p>Record payments, deposits, and items taken for PA system hires.</p>
        </div>
    </div>

    <section class="tiles">
        <div class="tile money">
            <small>Collected Today</small>
            <b><?= CURRENCY ?> <span id="statToday">0</span></b>
        </div>
        <div class="tile money">
            <small>Collected This Month</small>
            <b><?= CURRENCY ?> <span id="statMonth">0</span></b>
        </div>
    </section>

    <section class="card">
        <h2>Record a New Collection</h2>
        <div class="grid">
            <div><label>Customer / Event Name</label><input id="pCustomer" required></div>
            <div><label>Total Amount (<?= CURRENCY ?>)</label><input id="pAmount" type="number" min="1" step="500"></div>
            <div><label>Deposit Paid (<?= CURRENCY ?>)</label><input id="pDeposit" type="number" min="0" step="500" value="0"></div>
            <div><label>Balance Due (<?= CURRENCY ?>)</label><input id="pBalance" type="number" readonly style="background:#f8fafc; font-weight:bold; color:var(--red);"></div>
            <div><label>Method</label><select id="pMethod"><option>Cash</option><option>Mobile Money</option><option>Card</option></select></div>
            <div><label>Expected Return Date</label><input id="pReturn" type="date"></div>
            
            <div style="grid-column: 1 / -1;">
                <label>Items Taken / Description</label>
                <textarea id="pDesc" placeholder="e.g.&#10;2x Microphones&#10;1x Mixer&#10;2x Speakers"></textarea>
            </div>
            
            <div style="grid-column: 1 / -1; display: flex; gap: 10px;">
                <button class="btn ghost" id="pCancel" style="flex: 1;">Cancel</button>
                <button class="btn primary" id="pSave" style="flex: 2;">Save & Print Receipt</button>
            </div>
        </div>
        <div class="hint" id="pHint"></div>
    </section>

    <section class="card">
        <h3>Recent PA Collections</h3>
        <div class="tablewrap">
            <table class="rt">
                <thead>
                    <tr>
                        <th>Date Recorded</th>
                        <th>Return Date</th>
                        <th>Customer / Event</th>
                        <th>Items Taken</th>
                        <th>Received By</th>
                        <th class="num">Total (<?= CURRENCY ?>)</th>
                        <th class="num">Deposit (<?= CURRENCY ?>)</th>
                        <th class="num">Balance (<?= CURRENCY ?>)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="pTbody">
                    <tr><td colspan="9" style="text-align:center; color:#64748b; padding:20px;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</main>

<div class="modal" id="done"><div class="box">
    <h3>Collection Saved</h3>
    <div>Customer: <b id="dCustomer"></b></div>
    <div style="font-size: 0.95rem; margin-top: 6px;">Total: <b id="dTotal"></b></div>
    <div style="font-size: 0.95rem;">Deposit Paid: <b id="dDeposit" style="color:var(--green);"></b></div>
    <div class="big" id="dBalance" style="color:var(--red);"></div>
    <div class="row">
        <button class="btn ghost" id="dClose" style="margin:0">Done</button>
        <button class="btn primary" id="dPrint" style="margin:0">Print Receipt</button>
    </div>
</div></div>

<div class="modal" id="clearModal"><div class="box">
    <h3>Clear Pending Balance</h3>
    <div>Customer: <b id="cbCustomer"></b></div>
    <div class="big" id="cbBalance" style="color:var(--red);"></div>
    <p style="font-size:0.85rem; color:var(--muted);">Clearing this will record the remaining balance as a deposit payment and settle the balance to 0.</p>
    <div class="row">
        <button class="btn ghost" onclick="$('clearModal').classList.remove('open')" style="margin:0">Cancel</button>
        <button class="btn primary" id="cbConfirm" style="margin:0">Confirm Clearance</button>
    </div>
</div></div>

<script>
const CSRF = <?= json_encode(csrf_token()) ?>;
const CUR = <?= json_encode(CURRENCY) ?>;
const $ = id => document.getElementById(id);
const fmt = n => Number(n).toLocaleString();
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const apiUrl = './api/pa_system.php';

const hint = (t, k='') => { $('pHint').textContent = t; $('pHint').className = 'hint ' + k; };

function calculateBalance() {
    const total = parseFloat($('pAmount').value) || 0;
    const deposit = parseFloat($('pDeposit').value) || 0;
    const balance = total - deposit;
    $('pBalance').value = balance >= 0 ? balance.toFixed(2) : '0.00';
}

$('pAmount').addEventListener('input', calculateBalance);
$('pDeposit').addEventListener('input', calculateBalance);

async function loadData() {
    try {
        const r = await (await fetch(apiUrl + '?action=list')).json();
        if (r.error) return;
        
        $('statToday').textContent = fmt(r.today);
        $('statMonth').textContent = fmt(r.month);

        if (!r.rows.length) {
            $('pTbody').innerHTML = '<tr><td colspan="9" style="text-align:center; color:#64748b; padding:20px;">No collections recorded yet.</td></tr>';
            return;
        }

        $('pTbody').innerHTML = r.rows.map(x => {
            const desc = x.description ? `<div class="pre-wrap">${esc(x.description)}</div>` : '—';
            const retDate = x.return_date ? new Date(x.return_date).toLocaleDateString(undefined, {month:'short', day:'numeric', year:'numeric'}) : '—';
            const clearBtn = Number(x.balance) > 0 
                ? `<button class="btn sm primary" onclick="openClearModal(${x.id}, '${esc(x.customer_name)}', ${x.balance})" style="margin-right:4px;">Clear Balance</button>` 
                : '';
            
            return `
            <tr>
                <td data-label="Date Recorded">${new Date(x.created_at.replace(' ', 'T')).toLocaleDateString(undefined, {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit', hour12:true})}</td>
                <td data-label="Return Date"><b>${retDate}</b></td>
                <td data-label="Customer"><b>${esc(x.customer_name)}</b></td>
                <td data-label="Items Taken">${desc}</td>
                <td data-label="Received By">${esc(x.full_name)}</td>
                <td class="num" data-label="Total"><b>${fmt(x.amount)}</b></td>
                <td class="num" data-label="Deposit"><b style="color:var(--green)">${fmt(x.deposit)}</b></td>
                <td class="num" data-label="Balance"><b style="color:${x.balance > 0 ? 'var(--red)' : 'inherit'}">${fmt(x.balance)}</b></td>
                <td data-label="Action">
                    ${clearBtn}
                    <button class="btn sm dark" onclick="showModal(${x.id})">Print</button>
                </td>
            </tr>
            `;
        }).join('');
    } catch (e) { console.error(e); }
}

$('pCancel').onclick = () => {
    $('pCustomer').value = '';
    $('pDesc').value = '';
    $('pAmount').value = '';
    $('pDeposit').value = '0';
    $('pBalance').value = '';
    $('pReturn').value = '';
    hint('');
    $('pCustomer').focus();
};

$('pSave').onclick = async () => {
    const body = {
        customer_name: $('pCustomer').value,
        description: $('pDesc').value,
        amount: $('pAmount').value,
        deposit: $('pDeposit').value || 0,
        balance: $('pBalance').value || 0,
        payment_method: $('pMethod').value,
        return_date: $('pReturn').value
    };

    $('pSave').disabled = true;
    $('pSave').textContent = 'Saving...';
    hint('');

    try {
        const res = await fetch(apiUrl + '?action=add', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF }, body: JSON.stringify(body)
        });
        
        const text = await res.text();
        let r;
        try { r = JSON.parse(text); } catch (err) { throw new Error("Server Error: " + text.substring(0, 100)); }

        if (r.error) { 
            hint(r.error, 'bad'); 
        } else {
            hint('', '');
            $('pCustomer').value = ''; 
            $('pDesc').value = ''; 
            $('pAmount').value = ''; 
            $('pDeposit').value = '0';
            $('pBalance').value = '';
            $('pReturn').value = '';
            
            await loadData();
            showModal(r.id);
        }
    } catch (e) {
        hint(e.message || 'Network error.', 'bad');
    }

    $('pSave').disabled = false;
    $('pSave').textContent = 'Save & Print Receipt';
};

let currentPrintId = null;

async function showModal(id) {
    currentPrintId = id;
    const r = await (await fetch(apiUrl + '?action=list')).json();
    const data = r.rows.find(x => x.id == id);
    if (!data) return;

    $('dCustomer').textContent = data.customer_name;
    $('dTotal').textContent = CUR + ' ' + fmt(data.amount);
    $('dDeposit').textContent = CUR + ' ' + fmt(data.deposit);
    $('dBalance').textContent = 'Balance Due: ' + CUR + ' ' + fmt(data.balance);
    $('done').classList.add('open');
}

$('dClose').onclick = () => {
    $('done').classList.remove('open');
};

$('dPrint').onclick = () => {
    printReceipt(currentPrintId);
    $('done').classList.remove('open');
};

let activeClearId = null;

function openClearModal(id, customer, balance) {
    activeClearId = id;
    $('cbCustomer').textContent = customer;
    $('cbBalance').textContent = CUR + ' ' + fmt(balance);
    $('clearModal').classList.add('open');
}

$('cbConfirm').onclick = async () => {
    $('cbConfirm').disabled = true;
    $('cbConfirm').textContent = 'Processing...';

    try {
        const res = await fetch(apiUrl + '?action=clear_balance', {
            method: 'POST', 
            headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF }, 
            body: JSON.stringify({ id: activeClearId })
        });
        const r = await res.json();
        if (r.error) {
            alert(r.error);
        } else {
            $('clearModal').classList.remove('open');
            loadData();
        }
    } catch (e) {
        alert('Network error.');
    }

    $('cbConfirm').disabled = false;
    $('cbConfirm').textContent = 'Confirm Clearance';
};

async function printReceipt(id) {
    const r = await (await fetch(apiUrl + '?action=list')).json();
    if (r.error) return;
    
    const data = r.rows.find(x => x.id == id);
    if (!data) return;

    const retDate = data.return_date ? new Date(data.return_date).toLocaleDateString(undefined, {month:'short', day:'numeric', year:'numeric'}) : 'Not specified';

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>PA Receipt - ${esc(data.customer_name)}</title>
            <style>
                @page { size: 80mm auto; margin: 0; }
                body { 
                    font-family: 'Segoe UI', Arial, sans-serif; 
                    margin: 0; 
                    padding: 10px; 
                    color: #0f172a; 
                    background: #fff; 
                    display: flex; 
                    justify-content: center; 
                    align-items: flex-start;
                }
                .rc { 
                    width: 76mm; 
                    padding: 12px; 
                    font-size: 12px; 
                    background: #fff; 
                    border: 1px solid #e2e8f0;
                    border-radius: 6px;
                }
                .logo-container { text-align: center; margin-bottom: 8px; }
                .logo-container img { 
                    width: 60px; 
                    height: 60px; 
                    border-radius: 50%; 
                    object-fit: cover; 
                    border: 2px solid #cbd5e1; 
                }
                .rc h1 { margin: 0 0 2px; text-align: center; font-size: 15px; font-weight: 800; color: #0f172a; } 
                .rc .sub-header { text-align: center; font-size: 10px; color: #475569; line-height: 1.4; margin-bottom: 8px; }
                .rc .badge { text-align: center; font-weight: 700; font-size: 11px; background: #f1f5f9; padding: 4px; border-radius: 4px; margin: 6px 0; color: #334155; text-transform: uppercase; }
                .rc .c { text-align: center; color: #64748b; margin: 2px 0; font-size: 10px; }
                hr { border: 0; border-top: 1px dashed #cbd5e1; margin: 8px 0; }
                table { width: 100%; border-collapse: collapse; } td { padding: 3px 0; vertical-align: top; font-size: 11px; } .r { text-align: right; }
                .tot { display: flex; justify-content: space-between; font-weight: 700; font-size: 12px; margin-top: 5px; }
                .desc { white-space: pre-wrap; font-size: 11px; line-height: 1.4; margin: 5px 0; background: #f8fafc; padding: 6px; border-radius: 4px; border: 1px solid #e2e8f0; }
                .footer-note { text-align: center; font-size: 10px; color: #64748b; margin-top: 10px; font-style: italic; }
                @media print { 
                    body { padding: 0; background: #fff; display: block; } 
                    .rc { border: none; border-radius: 0; width: 72mm; padding: 0; margin: 0 auto; box-shadow: none; } 
                }
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
                <div class="badge">PA System Hire Receipt</div>
                <div class="c">Date: ${new Date(data.created_at.replace(' ', 'T')).toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit', hour12:true})}</div>
                <hr>
                <table>
                    <tr><td>Customer:</td><td class="r"><b>${esc(data.customer_name)}</b></td></tr>
                    <tr><td>Staff:</td><td class="r">${esc(data.full_name)}</td></tr>
                    <tr><td>Method:</td><td class="r">${esc(data.payment_method)}</td></tr>
                    <tr><td>Return:</td><td class="r"><b>${retDate}</b></td></tr>
                </table>
                <hr>
                <div style="font-weight:700; margin-bottom:2px; font-size:11px;">Items Taken / Details:</div>
                <div class="desc">${esc(data.description || 'No description provided.')}</div>
                <hr>
                <div class="tot"><span>TOTAL AMOUNT:</span><span>${CUR} ${fmt(data.amount)}</span></div>
                <div class="tot"><span>DEPOSIT PAID:</span><span style="color:#16a34a;">${CUR} ${fmt(data.deposit)}</span></div>
                <div class="tot" style="color:#dc2626;"><span>BALANCE DUE:</span><span>${CUR} ${fmt(data.balance)}</span></div>
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

loadData();
</script>
</body>
</html>