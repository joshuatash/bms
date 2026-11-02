<?php 
require __DIR__ . '/config.php'; 
require_login(); 
if (!in_array(current_user()['role'], ['admin', 'manager'], true)) { header('Location: pos.php'); exit; }
$active = 'ground_hire'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ground Hire</title>
    <style>
        :root{
            --navy:#0f172a;--slate:#334155;--muted:#64748b;--line:#e2e8f0;--amber:#f59e0b;--green:#16a34a;--red:#dc2626;--blue:#2563eb;
            /* Dark theme (matches POS) */
            --surface:#111c2e;
            --surface-2:#16243a;
            --surface-3:#1e2d47;
            --text:#e2e8f0;
            --text-dim:#94a3b8;
            --text-mute:#64748b;
            --border:#243349;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; background: #0b1220; color: var(--text); }
        main { max-width: 1000px; margin: 24px auto; padding: 0 18px; display: grid; gap: 20px; }
        
        .head { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .head h2 { margin: 0; font-size: 1.4rem; color: var(--text); }
        .head p { margin: 2px 0 0; color: var(--text-dim); }
        .tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
        .tile { background: var(--surface); border-radius: 12px; padding: 16px 18px; border-left: 5px solid var(--text-dim); border-top: 1px solid var(--border); border-right: 1px solid var(--border); border-bottom: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,.3); }
        .tile small { color: var(--text-dim); font-weight: 600; }
        .tile b { display: block; font-size: 1.5rem; margin-top: 4px; color: var(--text); }
        .tile.money { border-left-color: var(--green); }
        
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.3); }
        .card h2 { margin: 0 0 14px; font-size: 1.05rem; color: var(--text); }
        .card h3 { color: var(--text); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; align-items: end; }
        label { display: block; font-size: .75rem; font-weight: 700; color: var(--text-dim); margin-bottom: 5px; text-transform: uppercase; letter-spacing: .4px; }
        input, select, textarea { width: 100%; padding: 10px 11px; border: 1px solid var(--border); border-radius: 8px; font-size: .95rem; background: var(--surface-2); color: var(--text); font-family: inherit; }
        input::placeholder, textarea::placeholder { color: var(--text-mute); }
        textarea { resize: vertical; min-height: 100px; line-height: 1.5; }
        input:focus, select:focus, textarea:focus { outline: 2px solid var(--amber); border-color: var(--amber); background: var(--surface-3); }
        .btn { border: 0; border-radius: 8px; padding: 10px 18px; font-weight: 700; cursor: pointer; font-size: .9rem; text-decoration: none; display: inline-block; text-align: center; transition: .15s; }
        .btn.primary { background: var(--amber); color: #0b1220; box-shadow: 0 4px 6px -1px rgba(245,158,11,.3); }
        .btn.primary:hover:not(:disabled) { background: #d97706; color: #fff; }
        .btn.dark { background: var(--text-dim); color: var(--text); }
        .btn.dark:hover { background: var(--text); color: #0b1220; }
        .btn.ghost { background: var(--surface-2); color: var(--text-dim); }
        .btn.ghost:hover { background: var(--surface-3); color: var(--text); }
        .btn.sm { padding: 6px 12px; font-size: .8rem; }
        .hint { font-size: .85rem; margin-top: 10px; min-height: 1.2em; font-weight: 600; }
        .hint.good { color: #4ade80; } .hint.bad { color: #f87171; }
        
        /* Balance Preview Box */
        .calc-box { background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; font-size: 0.92rem; color: var(--text); }
        .calc-box b { font-size: 1.1rem; }

        .tablewrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .92rem; color: var(--text); }
        th { text-align: left; background: var(--surface-2); color: var(--text-dim); padding: 11px 12px; font-size: .75rem; text-transform: uppercase; border-bottom: 2px solid var(--border); }
        td { padding: 11px 12px; border-bottom: 1px solid var(--border); vertical-align: top; }
        .num { text-align: right; }
        .pre-wrap { white-space: pre-wrap; font-family: inherit; font-size: 0.9rem; color: var(--text-dim); margin-top: 4px; }
        
        /* Badges */
        .badge { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
        .badge.completed { background: rgba(22,163,74,.2); color: #4ade80; }
        .badge.pending { background: rgba(220,38,38,.2); color: #f87171; }

        /* Modal */
        .modal { position: fixed; inset: 0; background: rgba(0,0,0,.7); backdrop-filter: blur(4px); display: none; place-items: center; z-index: 60; padding: 12px; }
        .modal.open { display: grid; } 
        .modal .box { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 24px; width: min(420px,100%); text-align: left; color: var(--text); box-shadow: 0 25px 50px -12px rgba(0,0,0,.6); }
        .modal h3 { margin: 0 0 6px; color: var(--text); font-size: 1.3rem; text-align: center; } 
        .modal .big { font-size: 1.5rem; font-weight: 800; margin: 10px 0; color: #4ade80; text-align: center; }
        .modal .row { display: flex; gap: 10px; margin-top: 18px; } 
        .modal .row .btn { flex: 1; text-align: center; padding: 12px; }
        @media(max-width: 640px) {
            main { padding: 0 12px; margin: 14px auto; gap: 14px; }
            .tiles { grid-template-columns: 1fr; }
            .grid { grid-template-columns: 1fr; }
            .calc-box { flex-direction: column; gap: 6px; text-align: center; }
            .btn.primary, .btn.ghost { width: 100%; padding: 14px; font-size: 1rem; }
            .head { flex-direction: column; align-items: stretch; text-align: center; }
            .btn.dark { width: 100%; }
            .rt thead { display: none; }
            .rt, .rt tbody, .rt tr, .rt td { display: block; width: 100%; }
            .rt tr { border: 1px solid var(--border); border-radius: 10px; margin-bottom: 12px; padding: 8px 12px; background: var(--surface-2); }
            .rt td { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; border-bottom: 1px solid var(--border); padding: 8px 2px; text-align: left; }
            .rt td:last-child { border-bottom: 0; padding-bottom: 0; }
            .rt td::before { content: attr(data-label); font-weight: 700; font-size: .68rem; text-transform: uppercase; color: var(--text-dim); text-align: left; flex-shrink: 0; }
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
            <h2>Ground Hire Collections</h2>
            <p>Record bookings, deposits, pending balances, and grounds taken for hires.</p>
        </div>
    </div>
    <section class="tiles">
        <div class="tile money">
            <small>Deposits Collected Today</small>
            <b><?= CURRENCY ?> <span id="statToday">0</span></b>
        </div>
        <div class="tile money">
            <small>Deposits Collected This Month</small>
            <b><?= CURRENCY ?> <span id="statMonth">0</span></b>
        </div>
    </section>
    <section class="card">
        <h2>Record a New Ground Hire</h2>
        
        <div class="grid" style="margin-bottom: 14px;">
            <div><label>Customer / Event Name</label><input id="pCustomer" required></div>
            <div><label>Total Fee (<?= CURRENCY ?>)</label><input id="pAmount" type="number" min="1" step="500"></div>
            <div><label>Deposit Paid (<?= CURRENCY ?>)</label><input id="pDeposit" type="number" min="0" step="500" placeholder="Leave empty for full payment"></div>
            <div><label>Method</label><select id="pMethod"><option>Cash</option><option>Mobile Money</option><option>Card</option></select></div>
            
            <div><label>Start Date</label><input id="pStartDate" type="date"></div>
            <div><label>Start Time</label><input id="pStartTime" type="time"></div>
            <div><label>End Date</label><input id="pEndDate" type="date"></div>
            <div><label>End Time</label><input id="pEndTime" type="time"></div>
            
            <div style="grid-column: 1 / -1;">
                <label>Details / Grounds Taken</label>
                <textarea id="pDesc" placeholder="e.g.&#10;1. Main Ground&#10;2. 100 Chairs&#10;3. 2 Tents"></textarea>
            </div>
        </div>

        <!-- Real-time Balance Preview Box -->
        <div class="calc-box">
            <span>Calculated Balance Due:</span>
            <b id="previewBalance" style="color: #4ade80;"><?= CURRENCY ?> 0</b>
        </div>

        <div style="display: flex; gap: 10px;">
            <button class="btn ghost" id="pCancel" style="flex: 1;">Cancel</button>
            <button class="btn primary" id="pSave" style="flex: 2;">Save & Print Receipt</button>
        </div>
        
        <div class="hint" id="pHint"></div>
    </section>
    <section class="card">
        <h3>Recent Ground Hires</h3>
        <div class="tablewrap">
            <table class="rt">
                <thead>
                    <tr>
                        <th>Date Recorded</th>
                        <th>Interval (Start → End)</th>
                        <th>Customer / Event</th>
                        <th>Details / Grounds</th>
                        <th class="num">Total (<?= CURRENCY ?>)</th>
                        <th class="num">Deposit (<?= CURRENCY ?>)</th>
                        <th class="num">Balance (<?= CURRENCY ?>)</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="pTbody">
                    <tr><td colspan="9" style="text-align:center; color:var(--text-mute); padding:20px;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- SUCCESS / PRINT MODAL -->
<div class="modal" id="done"><div class="box" style="text-align:center;">
    <h3>Ground Hire Saved</h3>
    <div>Customer: <b id="dCustomer"></b></div>
    <div class="big" id="dTotal" style="color:var(--text);"></div>
    <div id="dBalanceInfo" style="margin-bottom: 12px; font-size: 0.9rem; color: #f87171;"></div>
    <div class="row">
        <button class="btn ghost" id="dClose" style="margin:0">Done</button>
        <button class="btn primary" id="dPrint" style="margin:0">Print Receipt</button>
    </div>
</div></div>

<!-- PAY BALANCE MODAL -->
<div class="modal" id="payModal"><div class="box">
    <h3>Clear Pending Balance</h3>
    <div style="margin-bottom: 10px; font-size: 0.9rem; color: var(--text-dim);">Customer: <b id="bCustomerName" style="color: var(--text);"></b></div>
    <div style="margin-bottom: 14px; font-size: 0.95rem; color: var(--text);">Remaining Balance: <b id="bRemainingBal" style="color: #f87171;"></b></div>
    
    <div style="margin-bottom: 12px;">
        <label>Payment Amount (<?= CURRENCY ?>)</label>
        <input id="bPayAmount" type="number" min="1" step="500">
    </div>
    <div style="margin-bottom: 18px;">
        <label>Payment Method</label>
        <select id="bPayMethod"><option>Cash</option><option>Mobile Money</option><option>Card</option></select>
    </div>
    <div class="row">
        <button class="btn ghost" id="bPayCancel" style="margin:0">Cancel</button>
        <button class="btn primary" id="bPaySubmit" style="margin:0">Confirm Payment</button>
    </div>
</div></div>

<script>
const CSRF = <?= json_encode(csrf_token()) ?>;
const CUR = <?= json_encode(CURRENCY) ?>;
const $ = id => document.getElementById(id);
const fmt = n => Number(n).toLocaleString();
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const apiUrl = './api/ground_hire.php';
const hint = (t, k='') => { $('pHint').textContent = t; $('pHint').className = 'hint ' + k; };

const fmtTime = (timeStr) => {
    if (!timeStr) return '';
    const [h, m] = timeStr.split(':');
    const d = new Date(); d.setHours(h, m);
    return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: true });
};

// Real-time Balance Calculation as you type
function updateBalancePreview() {
    const total = parseFloat($('pAmount').value) || 0;
    const depositInput = $('pDeposit').value;
    const deposit = depositInput === '' ? total : (parseFloat(depositInput) || 0);
    const balance = Math.max(0, total - deposit);
    
    const preview = $('previewBalance');
    preview.textContent = CUR + ' ' + fmt(balance);
    preview.style.color = balance > 0 ? '#f87171' : '#4ade80';
}

$('pAmount').addEventListener('input', updateBalancePreview);
$('pDeposit').addEventListener('input', updateBalancePreview);

async function loadData() {
    try {
        const r = await (await fetch(apiUrl + '?action=list')).json();
        if (r.error) return;
        
        $('statToday').textContent = fmt(r.today);
        $('statMonth').textContent = fmt(r.month);
        if (!r.rows.length) {
            $('pTbody').innerHTML = '<tr><td colspan="9" style="text-align:center; color:var(--text-mute); padding:20px;">No ground hires recorded yet.</td></tr>';
            return;
        }
        $('pTbody').innerHTML = r.rows.map(x => {
            const desc = x.description ? `<div class="pre-wrap">${esc(x.description)}</div>` : '—';
            let interval = '—';
            if (x.start_date && x.end_date) {
                const sD = new Date(x.start_date).toLocaleDateString(undefined, {month:'short', day:'numeric'});
                const eD = new Date(x.end_date).toLocaleDateString(undefined, {month:'short', day:'numeric'});
                const sT = fmtTime(x.start_time);
                const eT = fmtTime(x.end_time);
                interval = `<b>${sD} ${sT}</b> &rarr; <b>${eD} ${eT}</b>`;
            }
            
            const statusBadge = x.status === 'Completed' 
                ? '<span class="badge completed">Paid</span>' 
                : `<span class="badge pending">Balance: ${fmt(x.balance)}</span>`;
                
            let actionBtns = `<button class="btn sm dark" onclick="showModal(${x.id})">Print</button>`;
            if (Number(x.balance) > 0) {
                actionBtns += ` <button class="btn sm primary" style="margin-top:4px;" onclick="openPayModal(${x.id}, '${esc(x.customer_name)}', ${x.balance})">Pay Bal</button>`;
            }

            return `
            <tr>
                <td data-label="Date Recorded">${new Date(x.created_at.replace(' ', 'T')).toLocaleDateString(undefined, {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit', hour12:true})}</td>
                <td data-label="Interval">${interval}</td>
                <td data-label="Customer"><b>${esc(x.customer_name)}</b></td>
                <td data-label="Details / Grounds">${desc}</td>
                <td class="num" data-label="Total">${fmt(x.amount)}</td>
                <td class="num" data-label="Deposit"><b style="color:#4ade80">${fmt(x.deposit)}</b></td>
                <td class="num" data-label="Balance"><b style="color:${x.balance > 0 ? '#f87171' : 'var(--text-dim)'}">${fmt(x.balance)}</b></td>
                <td data-label="Status">${statusBadge}</td>
                <td data-label="Action">${actionBtns}</td>
            </tr>
            `;
        }).join('');
    } catch (e) { console.error(e); }
}

$('pCancel').onclick = () => {
    $('pCustomer').value = '';
    $('pDesc').value = '';
    $('pAmount').value = '';
    $('pDeposit').value = '';
    $('pStartDate').value = '';
    $('pStartTime').value = '';
    $('pEndDate').value = '';
    $('pEndTime').value = '';
    updateBalancePreview();
    hint('');
    $('pCustomer').focus();
    showToast('Form cleared.');
};

$('pSave').onclick = async () => {
    const totalFee = $('pAmount').value;
    const depositVal = $('pDeposit').value;
    
    const body = {
        customer_name: $('pCustomer').value,
        description: $('pDesc').value,
        total_fee: totalFee,
        deposit: depositVal === '' ? totalFee : depositVal,
        payment_method: $('pMethod').value,
        start_date: $('pStartDate').value,
        start_time: $('pStartTime').value,
        end_date: $('pEndDate').value,
        end_time: $('pEndTime').value
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
            showToast('Record saved successfully!');
            $('pCustomer').value = ''; $('pDesc').value = ''; $('pAmount').value = ''; $('pDeposit').value = '';
            $('pStartDate').value = ''; $('pStartTime').value = ''; $('pEndDate').value = ''; $('pEndTime').value = '';
            updateBalancePreview();
            
            await loadData();
            showModal(r.id);
        }
    } catch (e) {
        hint(e.message || 'Network error.', 'bad');
    }
    $('pSave').disabled = false;
    $('pSave').textContent = 'Save & Print Receipt';
};

// Modal Logic
let currentPrintId = null;
async function showModal(id) {
    currentPrintId = id;
    const r = await (await fetch(apiUrl + '?action=list')).json();
    const data = r.rows.find(x => x.id == id);
    if (!data) return;
    $('dCustomer').textContent = data.customer_name;
    $('dTotal').textContent = CUR + ' ' + fmt(data.amount);
    if (Number(data.balance) > 0) {
        $('dBalanceInfo').textContent = `Pending Balance: ${CUR} ${fmt(data.balance)}`;
    } else {
        $('dBalanceInfo').textContent = 'Fully Paid';
    }
    $('done').classList.add('open');
}
$('dClose').onclick = () => $('done').classList.remove('open');
$('dPrint').onclick = () => {
    printReceipt(currentPrintId);
    $('done').classList.remove('open');
};

// Pay Balance Modal Logic
let activeBalanceId = null;
function openPayModal(id, customerName, remainingBal) {
    activeBalanceId = id;
    $('bCustomerName').textContent = customerName;
    $('bRemainingBal').textContent = CUR + ' ' + fmt(remainingBal);
    $('bPayAmount').value = remainingBal;
    $('payModal').classList.add('open');
}
$('bPayCancel').onclick = () => $('payModal').classList.remove('open');

$('bPaySubmit').onclick = async () => {
    const payAmt = $('bPayAmount').value;
    const method = $('bPayMethod').value;
    
    $('bPaySubmit').disabled = true;
    $('bPaySubmit').textContent = 'Processing...';
    try {
        const res = await fetch(apiUrl + '?action=pay_balance', {
            method: 'POST', 
            headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF }, 
            body: JSON.stringify({ id: activeBalanceId, amount: payAmt, payment_method: method })
        });
        const r = await res.json();
        if (r.error) {
            showToast(r.error, 'error');
        } else {
            showToast('Balance payment recorded successfully!');
            $('payModal').classList.remove('open');
            await loadData();
        }
    } catch(e) {
        showToast('Network error.', 'error');
    }
    $('bPaySubmit').disabled = false;
    $('bPaySubmit').textContent = 'Confirm Payment';
};

// Receipt Print Function (Matched exactly to PA System Design Style)
async function printReceipt(id) {
    const r = await (await fetch(apiUrl + '?action=list')).json();
    if (r.error) return;
    
    const data = r.rows.find(x => x.id == id);
    if (!data) return;

    let intervalStr = 'Not specified';
    if (data.start_date && data.end_date) {
        const sD = new Date(data.start_date).toLocaleDateString(undefined, {month:'short', day:'numeric', year:'numeric'});
        const eD = new Date(data.end_date).toLocaleDateString(undefined, {month:'short', day:'numeric', year:'numeric'});
        intervalStr = `${sD} ${fmtTime(data.start_time)} to ${eD} ${fmtTime(data.end_time)}`;
    }
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Ground Hire Receipt - ${esc(data.customer_name)}</title>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 20px; color: #0f172a; display: flex; justify-content: center; background: #fff; }
                .receipt { width: 340px; border: 1px solid #cbd5e1; padding: 22px; border-radius: 12px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
                .center { text-align: center; }
                .logo-img { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; margin-bottom: 6px; background: #0f172a; }
                h1 { margin: 0; font-size: 1.1rem; font-weight: 800; letter-spacing: 0.3px; color: #0f172a; }
                .sub-header { font-size: 0.73rem; color: #64748b; line-height: 1.3; margin-top: 2px; }
                .badge-title { display: inline-block; background: #f1f5f9; color: #0f172a; font-weight: 700; font-size: 0.75rem; padding: 5px 14px; border-radius: 6px; margin: 10px 0 6px; text-transform: uppercase; letter-spacing: 0.5px; }
                .date-str { font-size: 0.78rem; color: #64748b; margin-bottom: 12px; }
                hr { border: 0; border-top: 1px dashed #cbd5e1; margin: 10px 0; }
                .info-table { width: 100%; font-size: 0.88rem; margin-bottom: 6px; }
                .info-table td { padding: 4px 0; vertical-align: top; }
                .info-table td.val { text-align: right; font-weight: 700; color: #0f172a; }
                .section-label { font-size: 0.8rem; font-weight: 700; color: #0f172a; margin-top: 10px; margin-bottom: 4px; text-transform: uppercase; }
                .desc-box { font-size: 0.85rem; background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 10px; border-radius: 6px; margin: 6px 0; white-space: pre-wrap; color: #334155; }
                .totals-table { width: 100%; font-size: 0.92rem; margin-top: 6px; font-weight: 800; }
                .totals-table td { padding: 5px 0; }
                .totals-table td.val { text-align: right; }
                .footer-note { text-align: center; font-size: 0.76rem; font-style: italic; color: #64748b; margin-top: 16px; }
                @media print { body { padding: 0; background: #fff; } .receipt { border: none; padding: 0; width: 100%; box-shadow: none; } }
            </style>
        </head>
        <body>
            <div class="receipt">
                <div class="center">
                    <img src="logo.png" class="logo-img" alt="Logo">
                    <h1>BIG WALLET LOUNGE - BALA</h1>
                    <div class="sub-header">
                        Te-Cambia B, Bala Town Council, Kole District<br>
                        WhatsApp: +211925830541 | Tel: 0787072542<br>
                        Email: info@bigwalletlounge.com
                    </div>
                    <div class="badge-title">Ground Hire Receipt</div>
                    <div class="date-str">Date: ${new Date(data.created_at.replace(' ', 'T')).toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit', hour12:true})}</div>
                </div>
                <hr>
                <table class="info-table">
                    <tr><td>Customer:</td><td class="val">${esc(data.customer_name)}</td></tr>
                    <tr><td>Staff:</td><td class="val">${esc(data.full_name)}</td></tr>
                    <tr><td>Method:</td><td class="val">${esc(data.payment_method)}</td></tr>
                    <tr><td>Interval:</td><td class="val" style="font-size: 0.8rem; font-weight: 600;">${esc(intervalStr)}</td></tr>
                </table>
                <hr>
                <div class="section-label">Details / Grounds Taken:</div>
                <div class="desc-box">${esc(data.description || 'No description provided.')}</div>
                <hr>
                <table class="totals-table">
                    <tr><td>TOTAL AMOUNT:</td><td class="val"><?= CURRENCY ?> ${fmt(data.amount)}</td></tr>
                    <tr><td>DEPOSIT PAID:</td><td class="val" style="color: #16a34a;"><?= CURRENCY ?> ${fmt(data.deposit)}</td></tr>
                    <tr><td style="color: #dc2626;">BALANCE DUE:</td><td class="val" style="color: #dc2626;"><?= CURRENCY ?> ${fmt(data.balance)}</td></tr>
                </table>
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