<?php require __DIR__ . '/config.php'; require_login(); if (!in_array(current_user()['role'], ['admin', 'manager'], true)) { header('Location: pos.php'); exit; } $active = 'add_room'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Room</title>
    <style>
        :root { --navy:#0f172a; --slate:#334155; --muted:#64748b; --line:#e2e8f0; --amber:#f59e0b; --green:#16a34a; --red:#dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); color: var(--navy); min-height: 100vh; }
        main { max-width: 1000px; margin: 24px auto; padding: 0 18px; display: grid; gap: 20px; }
        
        .card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .card h2 { margin: 0 0 14px; font-size: 1.05rem; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; align-items: end; }
        label { display: block; font-size: .75rem; font-weight: 700; color: var(--slate); margin-bottom: 5px; text-transform: uppercase; letter-spacing: .4px; }
        input, select { width: 100%; padding: 10px 11px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: .95rem; background: #fff; }
        
        /* Force uppercase visually */
        input#num, input#type { text-transform: uppercase; }
        
        input:focus, select:focus { outline: 2px solid var(--amber); border-color: var(--amber); }
        
        .btn { border: 0; border-radius: 8px; padding: 10px 18px; font-weight: 700; cursor: pointer; font-size: .9rem; text-decoration: none; display: inline-block; text-align: center; }
        .btn.primary { background: var(--amber); color: var(--navy); }
        .btn.primary:hover:not(:disabled) { background: #d97706; color: #fff; }
        .btn:disabled { opacity: .55; cursor: not-allowed; }
        
        .hint { font-size: .85rem; margin-top: 10px; min-height: 1.2em; color: var(--muted); }
        .hint.good { color: var(--green); } .hint.bad { color: var(--red); }
        
        .tablewrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .92rem; }
        th { text-align: left; background: #f8fafc; color: var(--slate); padding: 11px 12px; font-size: .75rem; text-transform: uppercase; border-bottom: 2px solid var(--line); }
        td { padding: 11px 12px; border-bottom: 1px solid var(--line); }
        .num { text-align: right; }
        
        .badge { padding: 4px 10px; border-radius: 999px; font-size: .75rem; font-weight: 700; white-space: nowrap; }
        .badge.available { background: #dcfce7; color: #166534; }
        .badge.occupied { background: #dbeafe; color: #1e40af; }
        .badge.maintenance { background: #ffedd5; color: #9a3412; }
        
        .row { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 12px; }
        
        /* --- SMALL PHONE RESPONSIVENESS --- */
        @media(max-width: 640px) {
            main { padding: 0 12px; margin: 14px auto; gap: 14px; }
            .card { padding: 16px; }
            
            /* Stack form inputs */
            .grid { grid-template-columns: 1fr; }
            .grid > div { grid-column: auto !important; }
            .btn.primary { width: 100%; padding: 14px; font-size: 1rem; }
            
            /* Adjust Header */
            .row { flex-direction: column; align-items: stretch; text-align: center; }
            
            /* Turn Tables into Cards */
            .rt thead { display: none; }
            .rt, .rt tbody, .rt tr, .rt td { display: block; width: 100%; }
            .rt tr { border: 1px solid var(--line); border-radius: 10px; margin-bottom: 12px; padding: 8px 12px; background: #f8fafc; }
            .rt td { display: flex; justify-content: space-between; align-items: center; gap: 12px; border-bottom: 1px solid #f1f5f9; padding: 8px 2px; text-align: right; }
            .rt td:last-child { border-bottom: 0; padding-bottom: 0; }
            .rt td::before { content: attr(data-label); font-weight: 700; font-size: .68rem; text-transform: uppercase; color: var(--muted); text-align: left; flex-shrink: 0; }
            .rt td[colspan]::before { content: none; } .rt td[colspan] { display: block; text-align: center; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
    <section class="card">
        <h2>Add New Room</h2>
        <div class="grid">
            <div><label>Room Number</label><input id="num" placeholder="e.g. 101 or A1" maxlength="20" autocomplete="off"></div>
            <div><label>Room Type</label><input id="type" list="types" placeholder="Single, Double..." maxlength="40" autocomplete="off">
                <datalist id="types"></datalist>
            </div>
            <div><label>Sleeps (people)</label><input id="cap" type="number" min="1" max="20" value="2"></div>
            <div><label>Short Stay Price (<?= CURRENCY ?>)</label><input id="short" type="number" min="0" step="500" value="7000"></div>
            <div><label>Long Stay Price (<?= CURRENCY ?>)</label><input id="long" type="number" min="0" step="500" value="15000"></div>
            <div style="grid-column:1/-1"><label>Description (optional)</label><input id="desc" maxlength="255" placeholder="e.g. Self-contained, TV, first floor"></div>
            <div><button class="btn primary" id="save" style="width:100%">Save Room</button></div>
        </div>
        <div class="hint" id="hint"></div>
    </section>

    <section class="card">
        <div class="row"><h2>Rooms So Far</h2><a class="btn primary" href="manage_rooms.php">Manage Rooms</a></div>
        <div class="tablewrap"><table class="rt">
            <thead><tr><th>Room</th><th>Type</th><th class="num">Sleeps</th><th class="num">Short</th><th class="num">Long</th><th>Status</th></tr></thead>
            <tbody id="tbody"></tbody>
        </table></div>
    </section>
</main>

<script>
const CSRF = <?= json_encode(csrf_token()) ?>;
const $ = id => document.getElementById(id);
const fmt = n => Number(n).toLocaleString();
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const LABEL = { available:'Available', occupied:'Occupied', maintenance:'Under Maintenance' };
const hint = (t, k = '') => { $('hint').textContent = t; $('hint').className = 'hint ' + k; };

// Force Uppercase on Room Number and Type
function forceUppercase(el) {
    el.value = el.value.toUpperCase();
}
$('num').addEventListener('input', e => forceUppercase(e.target));
$('type').addEventListener('input', e => forceUppercase(e.target));

// A datalist only shows options that match what is already typed, so a filled-in type
// (e.g. VIP) hides all the others. Clear it while the field is open, put it back if nothing is picked.
$('type').addEventListener('focus', function () { this.dataset.prev = this.value; this.value = ''; });
$('type').addEventListener('blur',  function () { if (!this.value.trim()) this.value = this.dataset.prev || ''; });

// Suggestions = the standard types + every type already used by your rooms
function fillTypes(rows) {
    const set = new Set(['SINGLE', 'DOUBLE', 'TWIN', 'DELUXE', 'SUITE', 'VIP']);
    (rows || []).forEach(r => set.add(String(r.room_type).toUpperCase()));
    $('types').innerHTML = [...set].sort().map(t => '<option value="' + esc(t) + '">').join('');
}
fillTypes([]);

// Always asks the server for fresh data (never a saved copy) and reports unreadable replies
async function fetchJSON(url, opts) {
    const res = await fetch(url + (url.includes('?') ? '&' : '?') + '_=' + Date.now(), Object.assign({ cache: 'no-store' }, opts || {}));
    const text = await res.text();
    try { return JSON.parse(text); }
    catch (e) { throw new Error('Unexpected server reply: ' + text.replace(/<[^>]*>/g, ' ').trim().substring(0, 140)); }
}

async function load() {
    try {
        const r = await fetchJSON('api/rooms.php?action=list');
        if (r.error) { location.href = 'login.php'; return; }
        fillTypes(r.rows);
        $('tbody').innerHTML = r.rows.length ? r.rows.map(x => `
            <tr>
                <td data-label="Room"><b>${esc(x.room_number)}</b></td>
                <td data-label="Type">${esc(x.room_type)}</td>
                <td class="num" data-label="Sleeps">${x.capacity}</td>
                <td class="num" data-label="Short">${fmt(x.short_price)}</td>
                <td class="num" data-label="Long">${fmt(x.long_price)}</td>
                <td data-label="Status"><span class="badge ${x.status}">${LABEL[x.status]}</span></td>
            </tr>`).join('') : '<tr><td colspan="6" style="text-align:center;color:#64748b;padding:26px">No rooms yet. Add the first one above.</td></tr>';
    } catch (e) {
        $('tbody').innerHTML = '<tr><td colspan="6" style="text-align:center;color:#dc2626;padding:26px">' + esc(e.message) + '</td></tr>';
    }
}

$('save').onclick = async () => {
    const body = {
        room_number: $('num').value, room_type: $('type').value, capacity: $('cap').value,
        short_price: $('short').value, long_price: $('long').value, description: $('desc').value
    };
    $('save').disabled = true; hint('Saving...');
    try {
        const r = await fetchJSON('api/rooms.php?action=add', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF }, body: JSON.stringify(body)
        });
        if (r.error) { hint(r.error, 'bad'); }
        else { hint(r.message, 'good'); $('num').value = ''; $('desc').value = ''; $('num').focus(); }
    } catch (e) {
        hint(e.message, 'bad');      // the room may still have been saved; the list below is refreshed either way
    }
    $('save').disabled = false;
    await load();                    // always refresh the list: no manual page refresh needed
};
load();
</script>
</body>
</html>