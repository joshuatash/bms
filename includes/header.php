<?php
// Side navigation (drawer on phones). Expects config.php loaded and a login check done.
$hu = current_user();
$active = $active ?? '';
$isAdminHdr = $hu['role'] === 'admin';
$isManagerHdr = $hu['role'] === 'manager';
$isCashierHdr = $hu['role'] === 'cashier';
$canManage = $isAdminHdr || $isManagerHdr;

// Fallback title if the page didn't define one
$pageTitle = $pageTitle ?? 'Big Wallet';

$link = fn(string $key, string $href, string $label) =>
    '<a href="' . $href . '" class="' . ($active === $key ? 'active' : '') . '">' . $label . '</a>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- PAGE TITLE & FAVICON -->
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="shortcut icon" type="image/png" href="logo.png">
    
    <!-- PWA Manifest & Meta Tags -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Big Wallet">
    <link rel="apple-touch-icon" href="logo.png">
    
    <style>
        body{padding-left:240px}
        .sidebar{position:fixed;top:0;left:0;bottom:0;width:240px;background:#0f172a;color:#cbd5e1;display:flex;flex-direction:column;padding:18px 12px;overflow-y:auto;z-index:50;font-family:'Segoe UI',system-ui,sans-serif;transition:transform .25s ease}
        .sidebar .brand{display:flex;align-items:center;gap:10px;color:#fff;text-decoration:none;font-weight:800;font-size:1.15rem;padding:6px 10px 16px;letter-spacing:.3px}
        .sidebar .brand img{width:32px;height:32px;border-radius:50%;background:#fff;object-fit:cover}
        .sidebar .brand span{color:#f59e0b}
        .sidebar .label{font-size:.68rem;letter-spacing:1px;text-transform:uppercase;color:#64748b;padding:12px 10px 6px;font-weight:700}
        .sidebar nav{display:flex;flex-direction:column;gap:3px}
        .sidebar nav a,.sidebar nav .soon{display:flex;justify-content:space-between;align-items:center;padding:11px 14px;border-radius:8px;color:#cbd5e1;text-decoration:none;font-size:.93rem}
        .sidebar nav a:hover{background:#1e293b;color:#fff}
        .sidebar nav a.active{background:#f59e0b;color:#0f172a;font-weight:700}
        .sidebar nav .soon{opacity:.4;cursor:default}
        .sidebar nav .soon em{font-style:normal;font-size:.62rem;background:#334155;color:#cbd5e1;padding:2px 7px;border-radius:999px}
        .sidebar .bottom{margin-top:auto;border-top:1px solid #1e293b;padding-top:14px}
        .sidebar .who{padding:4px 14px 10px;font-size:.85rem;color:#e2e8f0}.sidebar .who small{display:block;color:#94a3b8;text-transform:capitalize}
        .sidebar .logout{display:block;padding:11px 14px;border-radius:8px;color:#f59e0b;text-decoration:none;font-weight:700;font-size:.93rem}
        .sidebar .logout:hover{background:#1e293b}
        .mbar,.scrim{display:none}

        /* --- CUSTOM NOTIFICATION TOAST --- */
        .toast-container {
            position: fixed; bottom: 20px; right: 20px; z-index: 9999;
            display: flex; flex-direction: column; gap: 10px; pointer-events: none;
        }
        .toast {
            background: #fff; border-radius: 12px; padding: 16px 20px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2); border-left: 5px solid var(--green);
            display: flex; align-items: center; gap: 12px; min-width: 280px;
            transform: translateX(120%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: auto;
        }
        .toast.show { transform: translateX(0); }
        .toast.error { border-left-color: var(--red); }
        .toast-icon {
            width: 28px; height: 28px; border-radius: 50%; background: var(--green); color: #fff;
            display: grid; place-items: center; font-weight: bold; font-size: 16px; flex-shrink: 0;
        }
        .toast.error .toast-icon { background: var(--red); }
        .toast-msg { font-size: 0.95rem; font-weight: 600; color: var(--navy); line-height: 1.4; }

        /* PWA Install Button */
        #installBtn { display: none; background: var(--amber); color: var(--navy); border: 0; padding: 6px 12px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; margin-left: auto; cursor: pointer; }

        @media(max-width:900px){
            body{padding-left:0;padding-top:56px}
            .mbar{display:flex;align-items:center;gap:12px;position:fixed;top:0;left:0;right:0;height:56px;background:#0f172a;color:#fff;padding:0 14px;z-index:30;font-family:'Segoe UI',system-ui,sans-serif}
            .mbar button#navToggle{background:#1e293b;border:0;color:#fff;font-size:1.4rem;line-height:1;width:42px;height:42px;border-radius:8px;cursor:pointer}
            .mbar .mbar-brand{display:flex;align-items:center;gap:8px;font-size:1.05rem;font-weight:800;}
            .mbar .mbar-brand img{width:28px;height:28px;border-radius:50%;background:#fff}
            .mbar .mbar-brand span{color:#f59e0b}
            .sidebar{transform:translateX(-100%);width:260px;box-shadow:6px 0 24px rgba(0,0,0,.35)}
            .sidebar.open{transform:translateX(0)}
            .scrim.show{display:block;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:40}
            .toast-container { left: 12px; right: 12px; bottom: 12px; align-items: center; }
            .toast { width: 100%; max-width: none; }
        }
    </style>
</head>
<body>

<!-- Mobile Top Bar -->
<div class="mbar">
    <button id="navToggle" aria-label="Open menu" aria-expanded="false">&#9776;</button>
    <div class="mbar-brand">
        <img src="logo.png" alt="Big Wallet Logo">
        Big <span>Wallet</span>
    </div>
    <button id="installBtn">Install App</button>
</div>
<div class="scrim" id="scrim"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <a class="brand" href="<?= e(home_for($hu['role'])) ?>">
      <img src="logo.png" alt="Big Wallet Logo">
      Big <span>Wallet</span>
  </a>
  <div class="label">Menu</div>
  <nav>
    
    <?php if ($canManage): ?>
      <?= $link('dashboard', $isManagerHdr ? 'manager_dashboard.php' : 'dashboard.php', 'Dashboard') ?>
      <?= $link('products', 'products.php', 'Products') ?>
    <?php endif; ?>
    
    <!-- Display Point of Sale for Admin, Cashier, and Manager roles -->
    <?= $link('pos', 'pos.php', 'Point of Sale') ?>

    <?php if ($isCashierHdr): ?>
      <?= $link('book_room', 'book_room.php', 'Book Room') ?>
    <?php endif; ?>

    <?php if ($canManage): ?>
      <?= $link('manage_rooms', 'manage_rooms.php', 'Rooms') ?>
      <?= $link('pa_system', 'pa_system.php', 'PA System') ?>
      <?= $link('ground_hire', 'ground_hire.php', 'Ground Hire') ?>
      <?= $link('expenses', 'expenses.php', 'View Expenses') ?>
      <?= $link('activity', 'activity.php', 'Activity') ?>
      <?= $link('analytics', 'analytics.php', 'Analytics') ?>
    <?php endif; ?>

    <?php if ($isAdminHdr): ?>
      <?= $link('hr_dashboard', 'hr_dashboard.php', 'HR Dashboard') ?>
      <?= $link('settings', 'settings.php', 'Settings') ?>
    <?php endif; ?>
    
  </nav>
  <div class="bottom">
    <div class="who"><?= e($hu['name']) ?><small><?= e($hu['role']) ?></small></div>
    <a class="logout" href="logout.php">Logout</a>
  </div>
</aside>

<div class="toast-container" id="toastContainer"></div>

<script>
// Universal Notification System
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    const icon = type === 'success' ? '&#10003;' : '&#10005;';
    
    toast.innerHTML = `
        <div class="toast-icon">${icon}</div>
        <div class="toast-msg">${message}</div>
    `;
    
    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Register Service Worker for PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('service-worker.js')
            .then(reg => console.log('Service Worker Registered'))
            .catch(err => console.log('Service Worker Failed', err));
    });
}

// Handle PWA Install Prompt
let deferredPrompt;
const installBtn = document.getElementById('installBtn');

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    if(installBtn) installBtn.style.display = 'block';
});

if(installBtn) {
    installBtn.addEventListener('click', async () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`User response to the install prompt: ${outcome}`);
        deferredPrompt = null;
        installBtn.style.display = 'none';
    });
}

window.addEventListener('appinstalled', () => {
    if(installBtn) installBtn.style.display = 'none';
    console.log('PWA was installed');
});

// Sidebar Toggle Logic
(function () {
  const sb = document.getElementById('sidebar'), sc = document.getElementById('scrim'), bt = document.getElementById('navToggle');
  if(!sb) return;
  function set(o) { sb.classList.toggle('open', o); sc.classList.toggle('show', o); document.body.style.overflow = o ? 'hidden' : ''; bt.setAttribute('aria-expanded', o); }
  bt.onclick = () => set(!sb.classList.contains('open'));
  sc.onclick = () => set(false);
  sb.addEventListener('click', e => { if (e.target.closest('a')) set(false); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') set(false); });
  window.addEventListener('resize', () => { if (innerWidth > 900) set(false); });
})();
</script>
</body>
</html>