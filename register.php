<?php
require __DIR__ . '/config.php';
if (current_user()) { header('Location: ' . home_for(current_user()['role'])); exit; }
$error = ''; $done = false; $old = ['full_name' => '', 'username' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['username']  = trim($_POST['username'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password2'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? ''))             $error = 'Session expired. Reload the page and try again.';
    elseif (($_POST['website'] ?? '') !== '')                                   $done = true; // honeypot
    elseif (mb_strlen($old['full_name']) < 2 || mb_strlen($old['full_name']) > 100) $error = 'Enter your full name.';
    elseif (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $old['username']))              $error = 'Username: 3 to 30 letters, numbers, dot or underscore.';
    elseif (strlen($pass) < 8)                                                   $error = 'Password must be at least 8 characters.';
    elseif ($pass !== $pass2)                                                    $error = 'Passwords do not match.';
    else {
        try {
            db()->prepare("INSERT INTO users (full_name, username, password_hash, role, is_active) VALUES (?,?,?, 'cashier', 0)")
                ->execute([$old['full_name'], $old['username'], password_hash($pass, PASSWORD_DEFAULT)]);
            log_activity('register', 'New account requested: ' . $old['username'], ['id' => (int)db()->lastInsertId(), 'username' => $old['username']]);
            $done = true;
        } catch (PDOException $ex) {
            $error = $ex->getCode() === '23000' ? 'That username is already taken.' : 'Could not create the account. Try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register - Business Manager</title>
<style>
*{box-sizing:border-box}
body{
    margin:0;
    min-height:100vh;
    display:grid;
    place-items:center;
    padding:20px;
    font-family:'Segoe UI',system-ui,sans-serif;
    background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
}
.card{background:#fff;width:min(420px,94vw);padding:34px 30px;border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.35)}
h1{margin:0 0 4px;font-size:1.5rem;color:#0f172a}p{margin:0 0 18px;color:#64748b;font-size:.9rem}
label{display:block;font-size:.8rem;font-weight:600;color:#334155;margin:13px 0 6px}
input{width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:1rem}
input:focus{outline:2px solid #f59e0b;border-color:#f59e0b}
button,.btn{display:block;width:100%;margin-top:20px;padding:12px;border:0;border-radius:8px;background:#f59e0b;color:#1e293b;font-weight:700;font-size:1rem;cursor:pointer;text-align:center;text-decoration:none}
button:hover,.btn:hover{background:#d97706;color:#fff}
.err{background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;font-size:.85rem;margin-bottom:6px}
.ok{background:#dcfce7;color:#166534;padding:14px;border-radius:8px;font-size:.92rem;line-height:1.5}
.links{display:flex;justify-content:space-between;margin-top:16px;font-size:.85rem}.links a{color:#64748b;text-decoration:none}
.hp{position:absolute;left:-9999px;height:0;overflow:hidden}
.pw{position:relative}.pw input{padding-right:48px}
.pw .eye{position:absolute;right:6px;top:50%;transform:translateY(-50%);width:38px;height:38px;margin:0;padding:0;background:transparent;border:0;border-radius:8px;color:#64748b;display:grid;place-items:center;cursor:pointer}
.pw .eye:hover{background:#f1f5f9;color:#0f172a}
.pw .eye .i-hide{display:none}.pw .eye.on .i-hide{display:block}.pw .eye.on .i-show{display:none}
</style></head><body>
<div class="card">
<?php if ($done): ?>
  <h1>Account created</h1>
  <p>One more step.</p>
  <div class="ok">Your account is waiting for approval. Ask the administrator to enable it, then you can log in.</div>
  <a class="btn" href="login.php">Go to Login</a>
<?php else: ?>
  <form method="post" autocomplete="off">
    <h1>Create Account</h1><p>Register to use the Point of Sale</p>
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="hp"><label>Leave empty</label><input name="website" tabindex="-1"></div>
    
    <label>Full Name</label>
    <input name="full_name" value="<?= e($old['full_name']) ?>" autocomplete="name" required autofocus>
    
    <label>Username</label>
    <input name="username" value="<?= e($old['username']) ?>" autocomplete="username" required>
    
    <label>Password (min 8 characters)</label>
    <div class="pw">
        <input name="password" type="password" autocomplete="new-password" required>
        <button type="button" class="eye" aria-label="Show password" aria-pressed="false"><svg class="i-show" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg><svg class="i-hide" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.9 10.9 0 0 1 12 19c-7 0-11-7-11-7a19.8 19.8 0 0 1 5.06-5.94M9.9 4.24A10.9 10.9 0 0 1 12 4c7 0 11 7 11 7a19.7 19.7 0 0 1-3.17 4.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg></button>
    </div>
    
    <label>Confirm Password</label>
    <div class="pw">
        <input name="password2" type="password" autocomplete="new-password" required>
        <button type="button" class="eye" aria-label="Show password" aria-pressed="false"><svg class="i-show" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg><svg class="i-hide" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.9 10.9 0 0 1 12 19c-7 0-11-7-11-7a19.8 19.8 0 0 1 5.06-5.94M9.9 4.24A10.9 10.9 0 0 1 12 4c7 0 11 7 11 7a19.7 19.7 0 0 1-3.17 4.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg></button>
    </div>
    
    <button>Register</button>
    <div class="links"><a href="login.php">Already have an account? Login</a><a href="index.html">Home</a></div>
  </form>
<?php endif; ?>
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