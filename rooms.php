<?php require __DIR__ . '/config.php'; require_login(); $active = 'rooms'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rooms</title>
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
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.3); }
        .card h2 { margin: 0 0 14px; font-size: 1.05rem; color: var(--text); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
        .room-box { background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; padding: 15px; border-left: 4px solid var(--text-dim); }
        .room-box.available { border-left-color: var(--green); }
        .room-box.occupied { border-left-color: var(--blue); }
        .room-box.maintenance { border-left-color: var(--amber); }
        .room-box h3 { margin: 0 0 4px; font-size: 1.2rem; color: var(--text); }
        .room-box p { margin: 2px 0; color: var(--text-dim); font-size: 0.9rem; }
        .room-box p b { color: var(--text); }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
    <section class="card">
        <h2>All Rooms</h2>
        <div class="grid" id="roomList"></div>
    </section>
</main>

<script>
const $ = id => document.getElementById(id);
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fmt = n => Number(n).toLocaleString();

async function load() {
    const r = await (await fetch('api/rooms.php?action=list')).json();
    if (r.error) { location.href = 'login.php'; return; }
    
    $('roomList').innerHTML = r.rows.length ? r.rows.map(x => `
        <div class="room-box ${x.status}">
            <h3>Room ${esc(x.room_number)}</h3>
            <p>Sleeps ${x.capacity}</p>
            <p>Short: ${fmt(x.short_price)} | Long: ${fmt(x.long_price)}</p>
            <p><b>Status: ${x.status}</b></p>
        </div>
    `).join('') : '<p style="color:var(--text-mute)">No rooms available.</p>';
}
load();
</script>
</body>
</html>