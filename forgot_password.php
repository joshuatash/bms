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
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:'Segoe UI',system-ui,sans-serif;background:linear-gradient(135deg,#0f172a,#1e293b 60%,#334155)}
.card{background:linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);width:min(400px,92vw);padding:36px 32px;border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.35);text-align:center;}
h1{margin:0 0 4px;font-size:1.5rem;color:#0f172a}p{margin:0 0 22px;color:#64748b;font-size:.9rem}
label{display:block;font-size:.8rem;font-weight:600;color:#334155;margin:14px 0 6px;text-align:left;}
input{width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:1rem}
input:focus{outline:2px solid #f59e0b;border-color:#f59e0b}
button{width:100%;margin-top:22px;padding:12px;border:0;border-radius:8px;background:#f59e0b;color:#1e293b;font-weight:700;font-size:1rem;cursor:pointer}
button:hover{background:#d97706;color:#fff}
.err{background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;font-size:.85rem;margin-bottom:6px}
.ok{background:#dcfce7;color:#166534;padding:10px;border-radius:8px;font-size:.85rem;margin-bottom:6px; border: 1px solid #bbf7d0;}
.links{display:flex;justify-content:space-between;margin-top:16px;font-size:.85rem}.links a{color:#64748b;text-decoration:none}.links a:hover{color:#d97706}
.status-icon { font-size: 3rem; margin-bottom: 10px; }
.status-title { font-size: 1.2rem; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
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
    <h1>Reset Password</h1><p>Enter your account details and new password.</p>
    
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