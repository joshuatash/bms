<?php 
require __DIR__ . '/config.php'; 
if (current_user()) { header('Location: ' . home_for(current_user()['role'])); exit; }

$message = '';
$error = '';
$submitted = false;

// Basic session rate-limiting
$_SESSION['last_reset_attempt'] = $_SESSION['last_reset_attempt'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prevent request spamming (limit 1 attempt per 5 seconds)
    if (time() - $_SESSION['last_reset_attempt'] < 5) {
        $error = 'Please wait a few seconds before trying again.';
    } else {
        $_SESSION['last_reset_attempt'] = time();

        $username = trim($_POST['username'] ?? '');
        $current_pass = (string)($_POST['current_password'] ?? '');
        $pass = (string)($_POST['new_password'] ?? '');
        $pass2 = (string)($_POST['confirm_password'] ?? '');

        if (strlen($pass) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($pass !== $pass2) {
            $error = 'Passwords do not match.';
        } else {
            // Verify username AND current password before accepting request
            $st = db()->prepare("SELECT user_id, password_hash FROM users WHERE username = ? AND is_active = 1");
            $st->execute([$username]);
            $u = $st->fetch();

            if ($u && password_verify($current_pass, $u['password_hash'])) {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                db()->prepare("UPDATE users SET pending_password_hash = ? WHERE user_id = ?")->execute([$hash, $u['user_id']]);
                log_activity('password_request', "Requested password reset for $username", ['id' => $u['user_id'], 'username' => $username]);
                $submitted = true;
            } else {
                $error = 'Invalid username or current password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot Password</title>
<style>
:root{
    --bg:#0b1220;
    --surface:#111c2e;
    --surface-2:#16243a;
    --surface-3:#1e2d47;
    --text:#e2e8f0;
    --text-dim:#94a3b8;
    --text-mute:#64748b;
    --border:#243349;
    --amber:#f59e0b;
}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:'Segoe UI',system-ui,sans-serif;background:#0b1220;color:var(--text)}
.card{background:var(--surface);border:1px solid var(--border);width:min(400px,92vw);padding:36px 32px;border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.5);text-align:center;}
h1{margin:0 0 4px;font-size:1.5rem;color:var(--text)}
p{margin:0 0 22px;color:var(--text-dim);font-size:.9rem}
label{display:block;font-size:.8rem;font-weight:600;color:var(--text-dim);margin:14px 0 6px;text-align:left;}
input{width:100%;padding:11px 12px;border:1px solid var(--border);border-radius:8px;font-size:1rem;background:var(--surface-2);color:var(--text)}
input::placeholder{color:var(--text-mute)}
input:focus{outline:2px solid var(--amber);border-color:var(--amber);background:var(--surface-3)}
button{width:100%;margin-top:22px;padding:12px;border:0;border-radius:8px;background:var(--amber);color:#0b1220;font-weight:700;font-size:1rem;cursor:pointer;transition:.15s;box-shadow:0 4px 6px -1px rgba(245,158,11,.3)}
button:hover{background:#d97706;color:#fff}
.err{background:rgba(220,38,38,.15);color:#f87171;padding:10px;border-radius:8px;font-size:.85rem;margin-bottom:6px;border:1px solid rgba(220,38,38,.3)}
.ok{background:rgba(22,163,74,.15);color:#4ade80;padding:10px;border-radius:8px;font-size:.85rem;margin-bottom:6px;border:1px solid rgba(22,163,74,.3)}
.links{display:flex;justify-content:space-between;margin-top:16px;font-size:.85rem}
.links a{color:var(--text-dim);text-decoration:none}
.links a:hover{color:var(--amber)}
.status-icon { font-size: 3rem; margin-bottom: 10px; }
.status-title { font-size: 1.2rem; font-weight: 800; color: var(--text); margin-bottom: 8px; }
.brand{text-align:center;margin-bottom:18px}
.brand-logo{width:64px;height:64px;border-radius:50%;object-fit:cover;margin:0 auto 10px;display:block;border:2px solid var(--border);background:var(--surface-2)}
</style></head><body>
<form class="card" method="post">
  
  <?php if ($submitted): ?>
    <div class="status-icon">⏳</div>
    <div class="status-title">Request Submitted</div>
    <p class="ok" style="font-size: 1rem; padding: 16px; margin-top: 10px;">
        Password reset is in progress.<br>Please wait for admin approval.
    </p>
    <div class="links" style="justify-content: center;">
        <a href="login.php">&larr; Back to Login</a>
    </div>

  <?php else: ?>
    <div class="brand">
      <img src="logo.png" alt="Big Wallet Lounge" class="brand-logo">
      <h1>Big Wallet Lounge</h1>
    </div>
    <p>Enter your account details and new password.</p>
    
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>

    <label>Username</label><input name="username" required autofocus>
    <label>Current Password</label><input name="current_password" type="password" required>
    <label>New Password</label><input name="new_password" type="password" required>
    <label>Confirm New Password</label><input name="confirm_password" type="password" required>
    <button>Submit Request</button>

    <div class="links">
      <a href="login.php">&larr; Back to Login</a>
      <a href="index.html">Home</a>
    </div>
  <?php endif; ?>

</form>
</body></html>