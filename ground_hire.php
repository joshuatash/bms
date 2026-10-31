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

        /* Modal / Popup styling */
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
            <h2>Ground Hire Collections</h2>
            <p>Record payments, time intervals, and grounds taken for hires.</p>
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
        
        <div class="grid" style="margin-bottom: 14px;">
            <div><label>Customer / Event Name</label><input id="pCustomer" required></div>
            <div><label>Amount (<?= CURRENCY ?>)</label><input id="pAmount" type="number" min="1" step="500"></div>
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
                        <th>Received By</th>
                        <th class="num">Amount (<?= CURRENCY ?>)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="pTbody">
                    <tr><td colspan="7" style="text-align:center; color:#64748b; padding:20px;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- SUCCESS MODAL -->
<div class="modal" id="done"><div class="box">
    <h3>Ground Hire Saved</h3>
    <div>Customer: <b id="dCustomer"></b></div>
    <div class="big" id="dTotal"></div>
    <div class="row">
        <button class="btn ghost" id="dClose" style="margin:0">Done</button>
        <button class="btn primary" id="dPrint" style="margin:0">Print Receipt</button>
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

// Format time to 12hr (e.g., 14:30 -> 02:30 PM)
const fmtTime = (timeStr) => {
    if (!timeStr) return '';
    const [h, m] = timeStr.split(':');
    const d = new Date(); d.setHours(h, m);
    return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: true });
};

async function loadData() {
    try {
        const r = await (await fetch(apiUrl + '?action=list')).json();
        if (r.error) return;
        
        $('statToday').textContent = fmt(r.today);
        $('statMonth').textContent = fmt(r.month);

        if (!r.rows.length) {
            $('pTbody').innerHTML = '<tr><td colspan="7" style="text-align:center; color:#64748b; padding:20px;">No ground hires recorded yet.</td></tr>';
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

            return `
            <tr>
                <td data-label="Date Recorded">${new Date(x.created_at.replace(' ', 'T')).toLocaleDateString(undefined, {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit', hour12:true})}</td>
                <td data-label="Interval">${interval}</td>
                <td data-label="Customer"><b>${esc(x.customer_name)}</b></td>
                <td data-label="Details / Grounds">${desc}</td>
                <td data-label="Received By">${esc(x.full_name)}</td>
                <td class="num" data-label="Amount"><b style="color:var(--green)">${fmt(x.amount)}</b></td>
                <td data-label="Action">
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
    $('pStartDate').value = '';
    $('pStartTime').value = '';
    $('pEndDate').value = '';
    $('pEndTime').value = '';
    hint('');
    $('pCustomer').focus();
    showToast('Form cleared.');
};

$('pSave').onclick = async () => {
    const body = {
        customer_name: $('pCustomer').value,
        description: $('pDesc').value, // Ensure this matches the textarea ID
        amount: $('pAmount').value,
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
            $('pCustomer').value = ''; $('pDesc').value = ''; $('pAmount').value = '';
            $('pStartDate').value = ''; $('pStartTime').value = ''; $('pEndDate').value = ''; $('pEndTime').value = '';
            
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
    $('done').classList.add('open');
}

$('dClose').onclick = () => {
    $('done').classList.remove('open');
};

$('dPrint').onclick = () => {
    printReceipt(currentPrintId);
    $('done').classList.remove('open');
};

// Actual Print Function (Styled like POS Receipt)
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
                body { font-family: 'Segoe UI', Arial, sans-serif; margin: 20px; color: #0f172a; display: flex; flex-direction: column; align-items: center; }
                .rc { width: 340px; border: 1px dashed #94a3b8; padding: 18px; font-size: 13px; }
                .rc h1 { margin: 0; text-align: center; font-size: 18px; } 
                .rc .c { text-align: center; color: #64748b; margin: 2px 0; }
                hr { border: 0; border-top: 1px dashed #94a3b8; margin: 10px 0; }
                table { width: 100%; border-collapse: collapse; } td { padding: 4px 0; vertical-align: top; } .r { text-align: right; }
                .tot { display: flex; justify-content: space-between; font-weight: 700; font-size: 16px; margin-top: 8px; }
                .desc { white-space: pre-wrap; font-size: 13px; line-height: 1.4; margin: 10px 0; }
                @media print { .bar { display: none; } body { margin: 0; } .rc { border: 0; } }
            </style>
        </head>
        <body>
            <div class="rc">
                <h1>Business Manager</h1>
                <div class="c">Ground Hire Receipt</div>
                <div class="c">Date: ${new Date(data.created_at.replace(' ', 'T')).toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit', hour12:true})}</div>
                <hr>
                <table>
                    <tr><td>Customer / Event:</td><td class="r"><b>${esc(data.customer_name)}</b></td></tr>
                    <tr><td>Received By:</td><td class="r">${esc(data.full_name)}</td></tr>
                    <tr><td>Method:</td><td class="r">${esc(data.payment_method)}</td></tr>
                    <tr><td>Time Interval:</td><td class="r"><b>${intervalStr}</b></td></tr>
                </table>
                <hr>
                <div><b>Details / Grounds Taken:</b></div>
                <div class="desc">${esc(data.description || 'No description provided.')}</div>
                <hr>
                <div class="tot"><span>TOTAL</span><span>${CUR} ${fmt(data.amount)}</span></div>
                <hr>
                <div class="c">Thank you!</div>
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