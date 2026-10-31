<?php
require __DIR__ . '/config.php';
if (current_user()) { header('Location: ' . home_for(current_user()['role'])); exit; }

$error = '';

// NEW: Check if HR forced a logout
if (isset($_GET['msg']) && $_GET['msg'] === 'force_logout') {
    $error = 'You have been logged out by the System Administrator.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = db()->prepare("SELECT user_id, full_name, username, password_hash, pending_password_hash, role, is_active FROM users WHERE username = ?");
    $st->execute([trim($_POST['username'] ?? '')]);
    $u = $st->fetch();
    
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        if ($u['pending_password_hash'] !== null) {
            $error = 'Your password reset is in progress. Please wait for admin approval.';
            log_activity('login_blocked', 'Password reset pending', ['user_id' => $u['user_id'], 'username' => $u['username']]);
        } 
        elseif (!$u['is_active']) {
            $error = 'Your account is not active yet. Ask the administrator to enable it.';
            log_activity('login_blocked', 'Account not active', ['user_id' => $u['user_id'], 'username' => $u['username']]);
        } 
        else {
            session_regenerate_id(true);
            
            $_SESSION['user'] = [
                'user_id' => $u['user_id'], 
                'name' => $u['full_name'], 
                'username' => $u['username'], 
                'role' => $u['role']
            ];
            
            start_login_session($u);
            log_activity('login', 'Signed in');
            header('Location: ' . home_for($u['role'])); exit;
        }
    } else {
        $error = 'Wrong username or password.';
        log_activity('login_failed', 'Attempted username: ' . mb_substr(trim($_POST['username'] ?? ''), 0, 50), ['user_id' => $u['user_id'] ?? null, 'username' => $u['username'] ?? mb_substr(trim($_POST['username'] ?? ''), 0, 50)]);
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login - Business Manager</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:'Segoe UI',system-ui,sans-serif;background:linear-gradient(135deg,#0f172a,#1e293b 60%,#334155)}
.card{background:linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);width:min(380px,92vw);padding:36px 32px;border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.35)}
h1{margin:0 0 4px;font-size:1.5rem;color:#0f172a}p{margin:0 0 22px;color:#64748b;font-size:.9rem}
label{display:block;font-size:.8rem;font-weight:600;color:#334155;margin:14px 0 6px}
input{width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:1rem}
input:focus{outline:2px solid #f59e0b;border-color:#f59e0b}
button{width:100%;margin-top:22px;padding:12px;border:0;border-radius:8px;background:#f59e0b;color:#1e293b;font-weight:700;font-size:1rem;cursor:pointer}
button:hover{background:#d97706;color:#fff}.err{background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;font-size:.85rem;margin-bottom:6px; font-weight:600;}
.links{display:flex;justify-content:space-between;margin-top:16px;font-size:.85rem}.links a{color:#64748b;text-decoration:none}.links a:hover{color:#d97706}
.pw{position:relative}.pw input{padding-right:48px}
.pw .eye{position:absolute;right:6px;top:50%;transform:translateY(-50%);width:38px;height:38px;margin:0;padding:0;background:transparent;border:0;border-radius:8px;color:#64748b;display:grid;place-items:center;cursor:pointer}
.pw .eye:hover{background:#f1f5f9;color:#0f172a}
.pw .eye .i-hide{display:none}.pw .eye.on .i-hide{display:block}.pw .eye.on .i-show{display:none}
</style></head><body>
<form class="card" method="post">
  <h1>Business Manager</h1><p>Sign in to continue</p>
  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  
  <label>Username</label>
  <input name="username" autocomplete="username" required autofocus>
  
  <label>Password</label>
  <div class="pw">
      <input name="password" type="password" autocomplete="current-password" required>
      <button type="button" class="eye" aria-label="Show password" aria-pressed="false"><svg class="i-show" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg><svg class="i-hide" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.9 10.9 0 0 1 12 19c-7 0-11-7-11-7a19.8 19.8 0 0 1 5.06-5.94M9.9 4.24A10.9 10.9 0 0 1 12 4c7 0 11 7 11 7a19.7 19.7 0 0 1-3.17 4.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg></button>
  </div>
  
  <div style="text-align: right; margin-top: 8px;">
      <a href="forgot_password.php" style="font-size: 0.8rem; color: #64748b; text-decoration: none;">Forgot Password?</a>
  </div>
  <button>Sign In</button>
  <div class="links"><a href="register.php">Create an account</a><a href="index.html">&larr; Home</a></div>
</form>
<script>
document.querySelectorAll('.pw .eye').forEach(b => b.addEventListener('click', () => {
  const inp = b.parentElement.querySelector('input'), show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  b.classList.toggle('on', show);
  b.setAttribute('aria-pressed', show);
  b.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  inp.focus();
}));
</script>
</body></html>