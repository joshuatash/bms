<?php
require __DIR__ . '/config.php';
if (current_user()) { header('Location: ' . home_for(current_user()['role'])); exit; }

$error = '';

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
        if (!$u) {
            $reason = 'Unknown username';
        } elseif (!$u['is_active']) {
            $reason = 'Account is disabled';
        } else {
            $reason = 'Wrong password';
        }
        log_activity('login_failed', $reason, ['user_id' => $u['user_id'] ?? null, 'username' => $u['username'] ?? mb_substr(trim($_POST['username'] ?? ''), 0, 50)]);
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login - Big Wallet Lounge</title>
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
body{
    margin:0;
    min-height:100vh;
    display:grid;
    place-items:center;
    font-family:'Segoe UI',system-ui,sans-serif;
    background:var(--bg);
    padding: 20px;
}
.login-wrapper{
    width:100%;
    display:flex;
    flex-direction:column;
    align-items:center;
}
.card{
    background:var(--surface);
    border:1px solid var(--border);
    width:min(380px,100%);
    padding:36px 32px;
    border-radius:16px;
    box-shadow:0 20px 50px rgba(0,0,0,.5);
}
h1{margin:0 0 4px;font-size:1.5rem;color:var(--text)}
p{margin:0 0 22px;color:var(--text-dim);font-size:.9rem}
label{display:block;font-size:.8rem;font-weight:600;color:var(--text-dim);margin:14px 0 6px}
input{width:100%;padding:11px 12px;border:1px solid var(--border);border-radius:8px;font-size:1rem;background:var(--surface-2);color:var(--text)}
input::placeholder{color:var(--text-mute)}
input:focus{outline:2px solid var(--amber);border-color:var(--amber);background:var(--surface-3)}
button{width:100%;margin-top:22px;padding:12px;border:0;border-radius:8px;background:var(--amber);color:#0b1220;font-weight:700;font-size:1rem;cursor:pointer;transition:.15s;box-shadow:0 4px 6px -1px rgba(245,158,11,.3)}
button:hover{background:#d97706;color:#fff}
.err{background:rgba(220,38,38,.15);color:#f87171;padding:10px;border-radius:8px;font-size:.85rem;margin-bottom:6px;font-weight:600;border:1px solid rgba(220,38,38,.3)}
.links{display:flex;justify-content:space-between;margin-top:16px;font-size:.85rem}
.links a{color:var(--text-dim);text-decoration:none}
.links a:hover{color:var(--amber)}
.pw{position:relative}
.pw input{padding-right:48px}
.pw .eye{position:absolute;right:6px;top:50%;transform:translateY(-50%);width:38px;height:38px;margin:0;padding:0;background:transparent;border:0;border-radius:8px;color:var(--text-dim);display:grid;place-items:center;cursor:pointer;transition:.15s}
.pw .eye:hover{background:var(--surface-3);color:var(--text)}
.pw .eye .i-hide{display:none}
.pw .eye.on .i-hide{display:block}
.pw .eye.on .i-show{display:none}
.brand{text-align:center;margin-bottom:18px}
.brand-logo{width:64px;height:64px;border-radius:50%;object-fit:cover;margin:0 auto 10px;display:block;border:2px solid var(--border);background:var(--surface-2)}
.public-footer{
    margin-top:24px;
    text-align:center;
    font-size:0.78rem;
    color:var(--text-mute);
    line-height:1.6;
}
.public-footer a{color:var(--text-dim);text-decoration:none;transition:.15s}
.public-footer a:hover{color:var(--amber)}
</style></head><body>

<div class="login-wrapper">
    <!-- Added autocomplete="off" to the form -->
    <form class="card" method="post" autocomplete="off">
      <div class="brand">
        <img src="logo.png" alt="Big Wallet Lounge" class="brand-logo">
        <h1>Big Wallet Lounge</h1>
      </div>
      <p>Sign in to continue</p>
      <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
      
      <!-- Added autocomplete="off" to the username -->
      <label>Username</label>
      <input name="username" autocomplete="off" required autofocus>
      
      <!-- Changed autocomplete to "new-password" to block auto-fill -->
      <label>Password</label>
      <div class="pw">
          <input name="password" type="password" autocomplete="new-password" required>
          <button type="button" class="eye" aria-label="Show password" aria-pressed="false"><svg class="i-show" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg><svg class="i-hide" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.9 10.9 0 0 1 12 19c-7 0-11-7-11-7a19.8 19.8 0 0 1 5.06-5.94M9.9 4.24A10.9 10.9 0 0 1 12 4c7 0 11 7 11 7a19.7 19.7 0 0 1-3.17 4.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg></button>
      </div>
      
      <div style="text-align: right; margin-top: 8px;">
          <a href="forgot_password.php" style="font-size: 0.8rem; color: var(--text-dim); text-decoration: none;">Forgot Password?</a>
      </div>
      <button>Sign In</button>
      <div class="links"><a href="register.php">Create an account</a><a href="index.html">&larr; Home</a></div>
    </form>

    <div class="public-footer">
        <p>Private staff portal for <strong>Big Wallet Lounge</strong>.</p>
        <p><a href="privacy.php">Privacy Policy</a> | <a href="terms.php">Terms of Service</a> | <a href="manual.php">Help</a></p>
    </div>
</div>

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