<?php require __DIR__ . '/config.php'; require_login(); if (!in_array(current_user()['role'], ['admin', 'manager'], true)) { header('Location: pos.php'); exit; } $active = 'manage_rooms'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Rooms</title>
    <style>
        :root { --navy:#0f172a; --slate:#334155; --muted:#64748b; --line:#e2e8f0; --amber:#f59e0b; --green:#16a34a; --red:#dc2626; --orange:#ea580c; --blue:#2563eb; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); color: var(--navy); }
        main { max-width: 1200px; margin: 24px auto; padding: 0 18px; display: grid; gap: 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; }
        .stat { background: #fff; border-radius: 12px; padding: 16px 18px; border-left: 5px solid var(--slate); box-shadow: 0 1px 3px rgba(0,0,0,0.06); cursor: pointer; transition: .15s; }
        .stat:hover { transform: translateY(-2px); box-shadow: 0 6px 14px rgba(0,0,0,.1); }
        .stat b { display: block; font-size: 1.8rem; } .stat small { color: var(--muted); }
        .stat.av { border-color: var(--green); } .stat.oc { border-color: var(--blue); } .stat.mt { border-color: var(--orange); }
        .card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .toolbar { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; align-items: center; }
        .toolbar input { max-width: 300px; } .toolbar select { max-width: 200px; } .toolbar .sp { flex: 1; }
        label { display: block; font-size: .75rem; font-weight: 700; color: var(--slate); margin-bottom: 5px; text-transform: uppercase; letter-spacing: .4px; }
        input, select { width: 100%; padding: 10px 11px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: .95rem; background: #fff; }
        input:focus, select:focus { outline: 2px solid var(--amber); border-color: var(--amber); }
        .btn { border: 0; border-radius: 8px; padding: 10px 18px; font-weight: 700; cursor: pointer; font-size: .9rem; text-decoration: none; display: inline-block; }
        .btn.primary { background: var(--amber); color: var(--navy); } .btn.primary:hover { background: #d97706; color: #fff; }
        .btn.ghost { background: #e2e8f0; color: var(--slate); } .btn.sm { padding: 6px 11px; font-size: .8rem; }
        .btn.edit { background: #dbeafe; color: #1e40af; } .btn.danger { background: #fee2e2; color: #991b1b; } .btn.warn { background: #ffedd5; color: #9a3412; } .btn.ok { background: #dcfce7; color: #166534; }
        .tablewrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .92rem; }
        th { text-align: left; background: #f8fafc; color: var(--slate); padding: 11px 12px; font-size: .75rem; text-transform: uppercase; border-bottom: 2px solid var(--line); }
        td { padding: 11px 12px; border-bottom: 1px solid var(--line); vertical-align: middle; }
        tr:hover td { background: #fafafa; }
        .badge { padding: 4px 10px; border-radius: 999px; font-size: .75rem; font-weight: 700; white-space: nowrap; }
        .badge.available { background: #dcfce7; color: #166534; } .badge.occupied { background: #dbeafe; color: #1e40af; } .badge.maintenance { background: #ffedd5; color: #9a3412; }
        .muted { color: var(--muted); font-size: .82rem; }
        .modal { position: fixed; inset: 0; background: rgba(15,23,42,.55); display: none; place-items: center; z-index: 60; padding: 12px; }
        .modal.open { display: grid; } .modal .box { background: #fff; border-radius: 14px; padding: 24px; width: min(460px,100%); max-height: 94vh; overflow: auto; }
        .modal h3 { margin: 0 0 16px; } .modal .r { margin-bottom: 12px; } .modal .actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
        .hint { font-size: .85rem; color: var(--red); min-height: 1.2em; }
        @media(max-width: 640px) {
            main { padding: 0 12px; margin: 14px auto; gap: 14px; }
            .toolbar input, .toolbar select { max-width: none; flex: 1 1 100%; }
            .toolbar .sp { display: none; } .toolbar .btn { width: 100%; text-align: center; }
            .stats { grid-template-columns: repeat(2,1fr); gap: 8px; } .stat { padding: 12px; } .stat b { font-size: 1.4rem; }
            .rt thead { display: none; }
            .rt, .rt tbody, .rt tr, .rt td { display: block; width: 100%; }
            .rt tr { border: 1px solid var(--line); border-radius: 10px; margin-bottom: 10px; padding: 6px 10px; }
            .rt td { display: flex; justify-content: space-between; align-items: center; gap: 12px; border: 0; padding: 7px 2px; text-align: right; }
            .rt td::before { content: attr(data-label); font-weight: 700; font-size: .68rem; text-transform: uppercase; color: var(--muted); text-align: left; flex-shrink: 0; }
            .rt td[colspan]::before { content: none; } .rt td[colspan] { display: block; text-align: center; }
            .btn.sm { padding: 9px 14px; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
    <section class="stats">
        <div class="stat" data-s=""><small>Total Rooms</small><b id="sTotal">0</b></div>
        <div class="stat av" data-s="available"><small>Available</small><b id="sAv">0</b></div>
        <div class="stat oc" data-s="occupied"><small>Occupied</small><b id="sOc">0</b></div>
        <div class="stat mt" data-s="maintenance"><small>Under Maintenance</small><b id="sMt">0</b></div>
    </section>

    <section class="card">
        <div class="toolbar">
            <input id="q" type="search" placeholder="Search room or guest...">
            <select id="fStatus">
                <option value="">All statuses</option>
                <option value="available">Available</option>
                <option value="occupied">Occupied</option>
                <option value="maintenance">Under Maintenance</option>
            </select>
            <span class="sp"></span>
            <a class="btn primary" href="addrooms.php">+ Add Room</a>
        </div>
        <div class="tablewrap"><table class="rt">
            <thead><tr><th>Room</th><th class="num">Sleeps</th><th class="num">Short</th><th class="num">Long</th><th>Status</th><th>Details</th><th>Actions</th></tr></thead>
            <tbody id="tbody"></tbody>
        </table></div>
    </section>
</main>

<div class="modal" id="editModal"><div class="box">
    <h3>Edit Room</h3>
    <div class="r"><label>Room Number</label><input id="eNum" maxlength="20"></div>
    <div class="r"><label>Sleeps (people)</label><input id="eCap" type="number" min="1" max="20"></div>
    <div class="r"><label>Short Stay Price (<?= CURRENCY ?>)</label><input id="eShort" type="number" min="0" step="500"></div>
    <div class="r"><label>Long Stay Price (<?= CURRENCY ?>)</label><input id="eLong" type="number" min="0" step="500"></div>
    <div class="r"><label>Description</label><input id="eDesc" maxlength="255"></div>
    <div class="hint" id="eErr"></div>
    <div class="actions"><button class="btn ghost" id="eCancel">Cancel</button><button class="btn primary" id="eSave">Save Changes</button></div>
</div></div>

<script>
const CSRF = <?= json_encode(csrf_token()) ?>;
const $ = id => document.getElementById(id);
const fmt = n => Number(n).toLocaleString();
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const LABEL = { available:'Available', occupied:'Occupied', maintenance:'Under Maintenance' };
const debounce = (fn, ms=250) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
let rooms = {}, editingId = null;

async function api(action, body) {
    return (await fetch('api/rooms.php?action=' + action, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF':CSRF}, body:JSON.stringify(body)})).json();
}

async function load() {
    const q = encodeURIComponent($('q').value.trim());
    const st = encodeURIComponent($('fStatus').value);
    const r = await (await fetch(`api/rooms.php?action=list&q=${q}&status=${st}`)).json();
    if (r.error) { location.href = 'login.php'; return; }
    
    $('sTotal').textContent = r.stats.total || 0;
    $('sAv').textContent = r.stats.available || 0;
    $('sOc').textContent = r.stats.occupied || 0;
    $('sMt').textContent = r.stats.maintenance || 0;
    
    rooms = {};
    $('tbody').innerHTML = r.rows.length ? r.rows.map(x => {
        rooms[x.room_id] = x;
        let detail = '';
        if (x.status === 'occupied') {
            detail = `<b>${esc(x.guest_name)}</b><br><span class="muted">until ${new Date(x.due_out).toLocaleDateString(undefined, {month:'short', day:'numeric'})}</span>`;
        } else if (x.status === 'maintenance') {
            detail = `<span class="muted">${esc(x.status_note || '')}</span>`;
        }

        let acts = `<button class="btn sm edit" data-edit="${x.room_id}">Edit</button>`;
        if (x.status === 'available') acts += ` <button class="btn sm warn" data-ms="maintenance" data-id="${x.room_id}">Set Maintenance</button>`;
        if (x.status === 'maintenance') acts += ` <button class="btn sm ok" data-ms="available" data-id="${x.room_id}">Mark Available</button>`;
        if (x.status !== 'occupied') acts += ` <button class="btn sm danger" data-del="${x.room_id}">Delete</button>`;

        return `<tr>
            <td data-label="Room"><b>${esc(x.room_number)}</b></td>
            <td class="num" data-label="Sleeps">${x.capacity}</td>
            <td class="num" data-label="Short">${fmt(x.short_price)}</td>
            <td class="num" data-label="Long">${fmt(x.long_price)}</td>
            <td data-label="Status"><span class="badge ${x.status}">${LABEL[x.status]}</span></td>
            <td data-label="Details">${detail}</td>
            <td data-label="Actions">${acts}</td>
        </tr>`;
    }).join('') : '<tr><td colspan="7" style="text-align:center;color:#64748b;padding:30px">No rooms found.</td></tr>';
}

$('q').addEventListener('input', debounce(load));
$('fStatus').addEventListener('change', load);
document.querySelectorAll('.stat[data-s]').forEach(t => t.addEventListener('click', () => { $('fStatus').value = t.dataset.s; load(); }));

$('tbody').addEventListener('click', async e => {
    const d = e.target.dataset;
    if (d.edit) {
        const x = rooms[d.edit]; editingId = d.edit;
        $('eNum').value = x.room_number; $('eCap').value = x.capacity;
        $('eShort').value = x.short_price; $('eLong').value = x.long_price; $('eDesc').value = x.description || '';
        $('eErr').textContent = '';
        $('editModal').classList.add('open');
    } else if (d.ms) {
        let note = d.ms === 'maintenance' ? 'Scheduled maintenance' : '';
        const r = await api('set_status', { room_id: d.id, status: d.ms, note });
        if (r.error) showToast(r.error, 'error'); 
        else showToast('Room status updated successfully.');
        load();
    } else if (d.del) {
        const r = await api('delete', { room_id: d.del }); 
        if (r.error) showToast(r.error, 'error'); 
        else showToast('Room deleted successfully.');
        load();
    }
});

$('eCancel').onclick = () => $('editModal').classList.remove('open');
$('eSave').onclick = async () => {
    const r = await api('update', {
        room_id: editingId, room_number: $('eNum').value, capacity: $('eCap').value,
        short_price: $('eShort').value, long_price: $('eLong').value, description: $('eDesc').value
    });
    if (r.error) { $('eErr').textContent = r.error; return; }
    $('editModal').classList.remove('open'); 
    showToast('Room details updated.');
    load();
};

const pre = new URLSearchParams(location.search).get('status');
if (['available', 'occupied', 'maintenance'].includes(pre)) $('fStatus').value = pre;
load();
</script>
</body>
</html>