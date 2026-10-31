<?php require __DIR__ . '/config.php'; require_login(); $active = 'rooms'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rooms</title>
    <style>
        :root { --navy:#0f172a; --slate:#334155; --muted:#64748b; --line:#e2e8f0; --amber:#f59e0b; --green:#16a34a; --red:#dc2626; --blue:#2563eb; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); color: var(--navy); }
        main { max-width: 1000px; margin: 24px auto; padding: 0 18px; display: grid; gap: 20px; }
        .card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .card h2 { margin: 0 0 14px; font-size: 1.05rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
        .room-box { background: #f8fafc; border-radius: 10px; padding: 15px; border-left: 4px solid var(--slate); }
        .room-box.available { border-color: var(--green); }
        .room-box.occupied { border-color: var(--blue); }
        .room-box.maintenance { border-color: var(--amber); }
        .room-box h3 { margin: 0 0 4px; font-size: 1.2rem; }
        .room-box p { margin: 2px 0; color: var(--muted); font-size: 0.9rem; }
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
    `).join('') : '<p>No rooms available.</p>';
}
load();
</script>
</body>
</html>