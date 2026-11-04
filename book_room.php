<?php require __DIR__ . '/config.php'; require_login(); $active = 'book_room'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Room</title>
    <style>
        :root{
            --navy:#0f172a;--slate:#334155;--muted:#64748b;--line:#e2e8f0;--amber:#f59e0b;--green:#16a34a;--red:#dc2626;--orange:#ea580c;--blue:#2563eb;
            /* Dark theme surfaces (matches POS) */
            --bg:#0b1220;
            --surface:#111c2e;
            --surface-2:#16243a;
            --surface-3:#1e2d47;
            --text:#e2e8f0;
            --text-dim:#94a3b8;
            --text-mute:#64748b;
            --border:#243349;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); color: var(--text); }
        .wrap { max-width: 1300px; margin: 20px auto; padding: 0 18px; display: grid; grid-template-columns: 1fr 380px; gap: 18px; align-items: start; }
        .left { display: grid; gap: 18px; min-width: 0; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.3); }
        .card h2 { margin: 0 0 12px; font-size: 1.05rem; display: flex; justify-content: space-between; align-items: center; gap: 10px; color: var(--text); }
        .card h2 small { font-weight: 600; color: var(--text-dim); font-size: .8rem; }
        label { display: block; font-size: .72rem; font-weight: 700; color: var(--text-dim); margin: 12px 0 5px; text-transform: uppercase; letter-spacing: .4px; }
        input, select { width: 100%; padding: 10px 11px; border: 1px solid var(--border); border-radius: 8px; font-size: .95rem; background: var(--surface-2); color: var(--text); }
        input::placeholder { color: var(--text-mute); }
        input#fGuest, input#fIdNo { text-transform: uppercase; }
        input:focus, select:focus { outline: 2px solid var(--amber); border-color: var(--amber); background: var(--surface-3); }
        .search { margin-bottom: 12px; }
        .rooms { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
        .room { border: 2px solid var(--border); background: var(--surface-2); border-radius: 12px; padding: 12px; text-align: left; cursor: pointer; font-family: inherit; transition: .12s; color: var(--text); }
        .room:hover { border-color: var(--amber); transform: translateY(-2px); }
        .room.sel { border-color: var(--green); background: rgba(22,163,74,.15); box-shadow: 0 0 0 3px rgba(22,163,74,.18); }
        .room b { display: block; font-size: 1.5rem; color: var(--text); }
        .room small { display: block; color: var(--text-dim); margin-top: 4px; }
        .room em { display: block; font-style: normal; font-weight: 700; margin-top: 6px; color: #4ade80; font-size: .9rem; }
        .empty { color: var(--text-mute); text-align: center; padding: 24px 8px; font-size: .92rem; }
        .tablewrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .9rem; color: var(--text); }
        th { text-align: left; background: var(--surface-2); color: var(--text-dim); padding: 10px 12px; font-size: .7rem; text-transform: uppercase; border-bottom: 2px solid var(--border); white-space: nowrap; }
        td { padding: 10px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .num { text-align: right; }
        .muted { color: var(--text-dim); font-size: .8rem; }
        .tag { display: inline-block; margin-left: 6px; padding: 2px 8px; border-radius: 999px; background: rgba(220,38,38,.2); color: #f87171; font-size: .68rem; font-weight: 700; }
        .owe { color: #f87171; font-weight: 700; }
        .btn { border: 0; border-radius: 8px; padding: 11px 16px; font-weight: 700; cursor: pointer; font-size: .95rem; text-decoration: none; display: inline-block; font-family: inherit; transition: .15s; }
        .btn.primary { background: var(--amber); color: #0b1220; box-shadow: 0 4px 6px -1px rgba(245,158,11,.3); }
        .btn.primary:hover:not(:disabled) { background: #d97706; color: #fff; }
        .btn:disabled { opacity: .35; cursor: not-allowed; box-shadow: none; }
        .btn.ghost { background: var(--surface-2); color: var(--text-dim); }
        .btn.ghost:hover { background: var(--surface-3); color: var(--text); }
        .btn.sm { padding: 7px 12px; font-size: .8rem; }
        .btn.out { background: rgba(220,38,38,.2); color: #f87171; }
        .btn.pay { background: rgba(22,163,74,.2); color: #4ade80; }
        .btn.ready { background: rgba(37,99,235,.2); color: #60a5fa; }
        .form { position: sticky; top: 20px; }
        .types { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .type { border: 2px solid var(--border); background: var(--surface-2); border-radius: 10px; padding: 10px; cursor: pointer; font-family: inherit; color: var(--text); text-align: center; transition: .12s; }
        .type b { display: block; }
        .type span { color: var(--text-dim); font-size: .85rem; }
        .type.on { border-color: var(--green); background: rgba(22,163,74,.15); }
        .kind { display: inline-block; margin-left: 6px; padding: 2px 8px; border-radius: 999px; background: rgba(37,99,235,.2); color: #60a5fa; font-size: .68rem; font-weight: 700; }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .picked { background: rgba(22,163,74,.12); border: 1px solid rgba(22,163,74,.3); border-radius: 10px; padding: 10px 12px; font-size: .9rem; color: var(--text); }
        .sum { border-top: 2px solid var(--amber); margin-top: 14px; padding-top: 10px; display: grid; gap: 6px; color: var(--text); }
        .sum div { display: flex; justify-content: space-between; }
        .sum .t b { font-size: 1.4rem; color: #4ade80; }
        .hint { font-size: .85rem; margin-top: 10px; min-height: 1.2em; color: var(--text-dim); }
        .hint.bad { color: #f87171; }
        .modal { position: fixed; inset: 0; background: rgba(0,0,0,.7); backdrop-filter: blur(4px); display: none; place-items: center; z-index: 60; padding: 12px; }
        .modal.open { display: grid; }
        .modal .box { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 24px; width: min(400px,100%); text-align: center; color: var(--text); box-shadow: 0 25px 50px -12px rgba(0,0,0,.6); }
        .modal h3 { margin: 0 0 6px; color: #4ade80; }
        .modal h3.plain { color: var(--text); }
        .modal .big { font-size: 1.7rem; font-weight: 800; margin: 10px 0; color: var(--text); }
        .modal .row { display: flex; gap: 10px; margin-top: 18px; }
        .modal .row .btn { flex: 1; text-align: center; }
        .modal .left-in { text-align: left; }
        @media(max-width: 950px) { .wrap { grid-template-columns: 1fr; } .form { position: static; order: -1; } }
        @media(max-width: 640px) {
            .wrap { padding: 0 10px; margin: 12px auto; gap: 12px; }
            .card { padding: 14px; }
            .rooms { grid-template-columns: repeat(2, 1fr); }
            .rt thead { display: none; }
            .rt, .rt tbody, .rt tr, .rt td { display: block; width: 100%; }
            .rt tr { border: 1px solid var(--border); border-radius: 10px; margin-bottom: 10px; padding: 6px 10px; background: var(--surface-2); }
            .rt td { display: flex; justify-content: space-between; align-items: center; gap: 12px; border: 0; padding: 7px 2px; text-align: right; }
            .rt td::before { content: attr(data-label); font-weight: 700; font-size: .66rem; text-transform: uppercase; color: var(--text-dim); text-align: left; flex-shrink: 0; }
            .rt td[colspan]::before { content: none; }
            .rt td[colspan] { display: block; text-align: center; }
            .btn.sm { padding: 10px 14px; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="wrap">
    <div class="left">
        <section class="card">
            <h2>Available Rooms <small id="avCount"></small></h2>
            <input class="search" id="q" type="search" placeholder="Search by room number..." autocomplete="off">
            <div class="rooms" id="avail"></div>
        </section>

        <section class="card">
            <h2>Occupied Rooms <small id="ocCount"></small></h2>
            <div class="tablewrap"><table class="rt">
                <thead><tr><th>Room</th><th>Guest</th><th>Checked in</th><th>Due out</th><th class="num">Total</th><th class="num">Paid</th><th class="num">Balance</th><th>Actions</th></tr></thead>
                <tbody id="occ"></tbody>
            </table></div>
        </section>

        <section class="card">
            <h2>Under Maintenance <small id="mtCount"></small></h2>
            <div class="tablewrap"><table class="rt">
                <thead><tr><th>Room</th><th>Note</th><th>Actions</th></tr></thead>
                <tbody id="mnt"></tbody>
            </table></div>
        </section>
    </div>

    <aside class="card form">
        <h2>Book a Room</h2>
        <div class="empty" id="noRoom">Tap an available room to start a booking.</div>
        <div id="formBody" style="display:none">
            <div class="picked" id="picked"></div>
            <label>Guest Name</label><input id="fGuest" maxlength="100" autocomplete="off">
            <div class="two">
                <div><label>Phone</label><input id="fPhone" type="tel" maxlength="25" autocomplete="off"></div>
                <div><label>ID / Passport No.</label><input id="fIdNo" maxlength="40" autocomplete="off"></div>
            </div>
            <label>Booking Type</label>
            <div class="types">
                <button type="button" class="type on" data-type="short"><b>Short</b><span id="tShort"></span></button>
                <button type="button" class="type" data-type="long"><b>Long</b><span id="tLong"></span></button>
            </div>
            <div class="two">
                <div><label>Guests</label><input id="fGuests" type="number" min="1" value="1"></div>
                <div id="nightsWrap" style="display:none"><label>Nights</label><input id="fNights" type="number" min="1" max="365" value="1"></div>
            </div>
            <div class="two">
                <div><label>Payment Method</label><select id="fMethod"><option>Cash</option><option>Mobile Money</option><option>Card</option></select></div>
                <div><label>Amount Paid (<?= CURRENCY ?>)</label><input id="fPaid" type="number" min="0" step="500"></div>
            </div>
            <div class="sum">
                <div class="t"><span>Total</span><b id="tTotal">0</b></div>
                <div><span>Balance to pay later</span><span id="sBal" class="owe"></span></div>
            </div>
            <button class="btn primary" id="bookBtn" style="width:100%;margin-top:14px">Book Room</button>
            <button class="btn ghost" id="cancelSel" style="width:100%;margin-top:8px;padding:9px;font-size:.85rem">Cancel</button>
            <div class="hint" id="hint"></div>
        </div>
    </aside>
</div>

<div class="modal" id="done"><div class="box">
    <h3>Room Booked</h3>
    <div>Booking <b id="dNo"></b></div>
    <div class="big" id="dTotal"></div>
    <div id="dBal" class="muted"></div>
    <div class="row">
        <button class="btn ghost" id="dPrint">Print Receipt</button>
        <button class="btn primary" id="dClose">Done</button>
    </div>
</div></div>

<div class="modal" id="payModal"><div class="box left-in">
    <h3 class="plain">Receive Payment</h3>
    <div id="pInfo" class="muted"></div>
    <label>Amount (<?= CURRENCY ?>)</label><input id="pAmt" type="number" min="1">
    <label>Method</label><select id="pMethod"><option>Cash</option><option>Mobile Money</option><option>Card</option></select>
    <div class="hint bad" id="pErr"></div>
    <div class="row">
        <button class="btn ghost" id="pCancel">Cancel</button>
        <button class="btn primary" id="pSave">Save Payment</button>
    </div>
</div></div>

<script>
const CSRF = <?= json_encode(csrf_token()) ?>, CUR = <?= json_encode(CURRENCY) ?>;
const $ = id => document.getElementById(id);
const fmt = n => Number(n).toLocaleString();
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const debounce = (fn, ms = 250) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

const localDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr.replace(' ', 'T'));
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
};
const todayStr = () => {
    const d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
};

function forceUppercase(el) { el.value = el.value.toUpperCase(); }
$('fGuest').addEventListener('input', e => forceUppercase(e.target));
$('fIdNo').addEventListener('input', e => forceUppercase(e.target));

let all = [], sel = null, paidTouched = false, payFor = null, btype = 'short';
let lastBookingData = null; 

const rate = () => btype === 'short' ? +sel.short_price : +sel.long_price;

async function api(action, body) {
    return (await fetch('api/rooms.php?action=' + action, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF }, body: JSON.stringify(body) })).json();
}

async function load() {
    const r = await (await fetch('api/rooms.php?action=list')).json();
    if (r.error) { location.href = 'login.php'; return; }
    all = r.rows;
    if (sel && !all.find(x => x.room_id == sel.room_id && x.status === 'available')) clearSel();
    render();
}

function render() {
    const q = $('q').value.trim().toLowerCase();
    const av = all.filter(x => x.status === 'available' && x.room_number.toLowerCase().includes(q));
    const oc = all.filter(x => x.status === 'occupied');
    const mt = all.filter(x => x.status === 'maintenance');
    
    $('avCount').textContent = av.length + ' free';
    $('ocCount').textContent = oc.length;
    $('mtCount').textContent = mt.length;

    $('avail').innerHTML = av.length ? av.map(x => `
        <button class="room ${sel && sel.room_id == x.room_id ? 'sel' : ''}" data-room="${x.room_id}">
            <b>${esc(x.room_number)}</b>
            <small>Sleeps ${x.capacity}</small>
            <em>Short ${fmt(x.short_price)} | Long ${fmt(x.long_price)}</em>
        </button>`).join('') : `<div class="empty" style="grid-column:1/-1">${q ? 'No matching free rooms.' : 'No rooms are free right now.'}</div>`;

    $('occ').innerHTML = oc.length ? oc.map(x => {
        const overdue = localDate(x.due_out) < localDate(todayStr()) ? '<span class="tag">Overdue</span>' : '';
        const bal = +x.balance;
        // Pass the entire JSON string object safely into printReceipt
        const safeJson = encodeURIComponent(JSON.stringify(x));
        return `<tr>
            <td data-label="Room"><b>${esc(x.room_number)}</b><span class="kind">${x.booking_type === 'short' ? 'Short' : 'Long'}</span></td>
            <td data-label="Guest">${esc(x.guest_name)}${x.guest_phone ? `<br><span class="muted">${esc(x.guest_phone)}</span>` : ''}</td>
            <td data-label="Checked in">${localDate(x.check_in)}</td>
            <td data-label="Due out">${localDate(x.due_out)}${overdue}</td>
            <td class="num" data-label="Total">${fmt(x.total_amount)}</td>
            <td class="num" data-label="Paid">${fmt(x.paid)}</td>
            <td class="num ${bal > 0 ? 'owe' : ''}" data-label="Balance">${fmt(bal)}</td>
            <td data-label="Actions">
                ${bal > 0 ? `<button class="btn sm pay" data-pay="${x.booking_id}">Receive Payment</button> ` : ''}
                <button class="btn sm out" data-out="${x.booking_id}">Check Out</button>
                <button class="btn sm ghost" onclick="printReceiptFromData('${safeJson}')">Receipt</button>
            </td>
        </tr>`;
    }).join('') : `<tr><td colspan="8" class="empty">No guests checked in.</td></tr>`;

    $('mnt').innerHTML = mt.length ? mt.map(x => `
        <tr>
            <td data-label="Room"><b>${esc(x.room_number)}</b></td>
            <td data-label="Note"><span class="muted">${esc(x.status_note || '')}</span></td>
            <td data-label="Actions"><button class="btn sm ready" data-ready="${x.room_id}">Mark Ready</button></td>
        </tr>`).join('') : `<tr><td colspan="3" class="empty">No rooms under maintenance.</td></tr>`;
}

function clearSel() {
    sel = null;
    $('formBody').style.display = 'none';
    $('noRoom').style.display = 'block';
}

function recal() {
    if (!sel) return;
    const n = btype === 'short' ? 1 : Math.max(1, parseInt($('fNights').value, 10) || 1);
    const total = rate() * n;
    if (!paidTouched) $('fPaid').value = total;
    const paid = Math.max(0, parseFloat($('fPaid').value) || 0);
    $('tTotal').textContent = CUR + ' ' + fmt(total);
    $('sBal').textContent = fmt(Math.max(0, total - paid));
}

$('avail').addEventListener('click', e => {
    const b = e.target.closest('[data-room]'); if (!b) return;
    sel = all.find(x => x.room_id == b.dataset.room);
    paidTouched = false;
    $('picked').innerHTML = `<b>Room ${esc(sel.room_number)}</b><br><span class="muted">Sleeps ${sel.capacity}</span>`;
    $('tShort').textContent = fmt(sel.short_price);
    $('tLong').textContent = fmt(sel.long_price) + ' / night';
    setType('short');
    $('fGuests').max = sel.capacity; $('fGuests').value = 1;
    $('fNights').value = 1;
    ['fGuest', 'fPhone', 'fIdNo'].forEach(id => $(id).value = '');
    $('hint').textContent = '';
    $('noRoom').style.display = 'none'; $('formBody').style.display = 'block';
    recal();
    render();
    $('fGuest').focus();
    if (innerWidth <= 950) $('formBody').scrollIntoView({ behavior: 'smooth', block: 'start' });
});

function setType(t) {
    btype = t;
    document.querySelectorAll('.type').forEach(b => b.classList.toggle('on', b.dataset.type === t));
    $('nightsWrap').style.display = t === 'long' ? 'block' : 'none';
    paidTouched = false; if (sel) recal();
}

document.querySelectorAll('.type').forEach(b => b.addEventListener('click', () => setType(b.dataset.type)));
$('fNights').addEventListener('input', recal);
$('fPaid').addEventListener('input', () => { paidTouched = true; recal(); });
$('cancelSel').onclick = () => { clearSel(); render(); };
$('q').addEventListener('input', debounce(render));

$('bookBtn').onclick = async () => {
    if (!sel) return;
    $('bookBtn').disabled = true; $('bookBtn').textContent = 'Saving...';
    $('hint').textContent = ''; $('hint').className = 'hint';
    try {
        const r = await api('book', {
            room_id: sel.room_id, guest_name: $('fGuest').value, guest_phone: $('fPhone').value, guest_id_no: $('fIdNo').value,
            guests: $('fGuests').value, booking_type: btype, nights: btype === 'short' ? 1 : $('fNights').value,
            method: $('fMethod').value, paid: $('fPaid').value || 0
        });
        if (r.error) { $('hint').textContent = r.error; $('hint').className = 'hint bad'; load(); }
        else {
            showToast('Room booked successfully!');
            $('dNo').textContent = r.booking_no; $('dTotal').textContent = CUR + ' ' + fmt(r.total);
            $('dBal').textContent = r.balance > 0 ? 'Balance still owed: ' + CUR + ' ' + fmt(r.balance) : 'Fully paid';
            
            // Save data for the receipt button after fresh booking
            lastBookingData = {
                booking_no: r.booking_no,
                guest_name: $('fGuest').value,
                guest_phone: $('fPhone').value,
                room_number: sel.room_number,
                booking_type: btype,
                nights: btype === 'short' ? 1 : $('fNights').value,
                total_amount: r.total,
                paid: $('fPaid').value || 0,
                balance: r.balance
            };

            $('done').classList.add('open'); clearSel(); load();
        }
    } catch (err) { $('hint').textContent = 'Network problem. Nothing was saved.'; $('hint').className = 'hint bad'; }
    $('bookBtn').disabled = false; $('bookBtn').textContent = 'Book Room';
};

$('dClose').onclick = () => $('done').classList.remove('open');
$('dPrint').onclick = () => {
    if (lastBookingData) {
        printReceipt(lastBookingData);
    }
    $('done').classList.remove('open');
};

$('occ').addEventListener('click', async e => {
    const d = e.target.dataset;
    if (d.out) {
        const r = await api('checkout', { booking_id: d.out }); 
        if (r.error) showToast(r.error, 'error'); 
        else showToast('Guest checked out successfully.');
        load();
    } else if (d.pay) {
        const x = all.find(r => r.booking_id == d.pay); payFor = x;
        $('pInfo').textContent = `Room ${x.room_number} - ${x.guest_name} - balance ${CUR} ${fmt(x.balance)}`;
        $('pAmt').value = x.balance; $('pAmt').max = x.balance; $('pErr').textContent = '';
        $('payModal').classList.add('open'); $('pAmt').select();
    }
});
$('pCancel').onclick = () => $('payModal').classList.remove('open');
$('pSave').onclick = async () => {
    const r = await api('pay', { booking_id: payFor.booking_id, amount: $('pAmt').value, method: $('pMethod').value });
    if (r.error) { $('pErr').textContent = r.error; return; }
    $('payModal').classList.remove('open'); 
    showToast('Payment received successfully.');
    load();
};
$('mnt').addEventListener('click', async e => {
    if (!e.target.dataset.ready) return;
    const r = await api('ready', { room_id: e.target.dataset.ready }); 
    if (r.error) showToast(r.error, 'error'); 
    else showToast('Room marked as available.');
    load();
});

// Helper parser for table buttons
function printReceiptFromData(encodedJson) {
    const data = JSON.parse(decodeURIComponent(encodedJson));
    printReceipt(data);
}

// Universal Receipt Generator
function printReceipt(data) {
    const dateStr = new Date().toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit', hour12:true});
    const typeStr = data.booking_type === 'short' ? 'Short Stay' : `Long Stay (${data.nights} night${data.nights > 1 ? 's' : ''})`;

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Room Receipt - ${esc(data.booking_no)}</title>
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
                    <div class="badge-title">Room Booking Receipt</div>
                    <div class="date-str">Date: ${dateStr}</div>
                </div>
                <hr>
                <table class="info-table">
                    <tr><td>Booking No:</td><td class="val">${esc(data.booking_no)}</td></tr>
                    <tr><td>Guest Name:</td><td class="val">${esc(data.guest_name)}</td></tr>
                    ${data.guest_phone ? `<tr><td>Phone:</td><td class="val">${esc(data.guest_phone)}</td></tr>` : ''}
                    <tr><td>Room:</td><td class="val">${esc(data.room_number)}</td></tr>
                    <tr><td>Type:</td><td class="val">${typeStr}</td></tr>
                </table>
                <hr>
                <table class="totals-table">
                    <tr><td>TOTAL AMOUNT:</td><td class="val">${CUR} ${fmt(data.total_amount)}</td></tr>
                    <tr><td>AMOUNT PAID:</td><td class="val" style="color: #16a34a;">${CUR} ${fmt(data.paid)}</td></tr>
                    <tr><td style="color: #dc2626;">BALANCE DUE:</td><td class="val" style="color: #dc2626;">${CUR} ${fmt(data.balance)}</td></tr>
                </table>
                <hr>
                <div class="footer-note">Thank you for choosing Big Wallet Lounge!</div>
            </div>
            <script>
                window.onload = function() { window.print(); }
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

load();
</script>
</body>
</html>