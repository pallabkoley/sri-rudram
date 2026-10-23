<?php
require_once dirname(__DIR__) . '/config/config.php';

if (!empty($_SESSION['admin_id'])) redirect('admin/dashboard.php');

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_token'] ?? null)) {
        $error = 'Session expired. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';

        if (!$email || !$pass) {
            $error = 'Please fill in all fields.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT id, name, password_hash FROM users
                 WHERE email = ? AND role = "admin" AND status = 1 LIMIT 1'
            );
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($pass, $admin['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']   = (int) $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                redirect('admin/dashboard.php');
            }
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════════════════
   DIVYA AASTHA — ADMIN LOGIN (Self-contained, fit-to-screen)
   ═══════════════════════════════════════════════════════ */

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root{
  --plum:#351B2F;
  --deep-plum:#24141F;
  --terracotta:#B76146;
  --terracotta-dark:#9B4C34;
  --sand:#F3E8D8;
  --cream:#FCF8F2;
  --champagne:#D5B56A;
  --text:#342823;
  --muted:#7A6A5D;
  --muted-2:#9A8B7C;
  --line:rgba(52,40,35,.14);
}

html,body{height:100%}

body{
  font-family:'Inter',system-ui,-apple-system,'Segoe UI',sans-serif;
  font-size:14px;
  line-height:1.5;
  color:var(--text);
  -webkit-font-smoothing:antialiased;
  background:linear-gradient(135deg,#1B1019 0%,#2B1728 40%,#351B2F 70%,#24141F 100%);
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:20px 16px;
  position:relative;
  overflow-x:hidden;
}

/* Mandala background */
body::before{
  content:"";
  position:fixed;
  top:-25%;
  right:-12%;
  width:600px;
  height:600px;
  border-radius:50%;
  pointer-events:none;
  background:repeating-conic-gradient(from 0deg,rgba(213,181,106,.10) 0deg .8deg,transparent .8deg 10deg);
  -webkit-mask-image:radial-gradient(circle,#000 10%,transparent 62%);
  mask-image:radial-gradient(circle,#000 10%,transparent 62%);
  animation:spinSlow 200s linear infinite;
  z-index:0;
}
body::after{
  content:"";
  position:fixed;
  bottom:-30%;
  left:-15%;
  width:500px;
  height:500px;
  border-radius:50%;
  pointer-events:none;
  background:radial-gradient(circle,rgba(183,97,70,.22),transparent 65%);
  opacity:.7;
  z-index:0;
}
@keyframes spinSlow{to{transform:rotate(360deg)}}

/* Card */
.login-box{
  position:relative;
  z-index:2;
  width:100%;
  max-width:400px;
  background:var(--cream);
  border-radius:20px;
  padding:32px 32px 26px;
  border:1px solid rgba(213,181,106,.4);
  box-shadow:0 30px 70px -24px rgba(0,0,0,.55),
             inset 0 1px 0 rgba(255,255,255,.5);
}
.login-box::before{
  content:"";
  position:absolute;
  top:-1px;
  left:20%;
  right:20%;
  height:2px;
  background:linear-gradient(90deg,transparent,var(--champagne),transparent);
  border-radius:2px;
}

/* Brand */
.login-brand{
  text-align:center;
  margin-bottom:22px;
}
.login-brand .brand-mark{
  width:52px;
  height:52px;
  border-radius:15px;
  margin:0 auto 12px;
  display:grid;
  place-items:center;
  font-size:24px;
  line-height:1;
  background:linear-gradient(140deg,#3A2136,#24141F);
  color:var(--champagne);
  border:1px solid rgba(214,181,106,.55);
  font-family:'Playfair Display',Georgia,serif;
  box-shadow:0 12px 24px -12px rgba(0,0,0,.75),
             inset 0 1px 0 rgba(214,181,106,.22);
  position:relative;
}
.login-brand .brand-mark::after{
  content:"";
  position:absolute;
  top:-3px;
  right:-3px;
  width:10px;
  height:10px;
  border-radius:50%;
  background:var(--terracotta);
  border:2px solid var(--cream);
}
.login-brand h1{
  font-family:'Playfair Display',Georgia,serif;
  font-size:22px;
  font-weight:700;
  color:var(--plum);
  letter-spacing:-.4px;
  margin-bottom:3px;
}
.login-brand p{
  color:var(--muted);
  font-size:10.5px;
  letter-spacing:2px;
  text-transform:uppercase;
  font-weight:600;
}

/* Flash */
.flash{
  display:flex;
  align-items:center;
  gap:8px;
  padding:10px 14px;
  border-radius:10px;
  font-size:12.4px;
  line-height:1.5;
  margin-bottom:16px;
}
.flash-error{
  background:rgba(178,58,46,.1);
  color:#B23A2E;
  border:1px solid rgba(178,58,46,.25);
}
.flash i{font-size:14px;flex-shrink:0}

/* Form */
form{display:block;width:100%}

.login-field{
  display:block;
  width:100%;
  margin-bottom:14px;
}

.login-field label{
  display:block;
  width:100%;
  font-size:10.8px;
  font-weight:700;
  color:var(--plum);
  letter-spacing:1.3px;
  text-transform:uppercase;
  margin-bottom:6px;
}

.login-input-wrap{
  position:relative;
  display:block;
  width:100%;
}

.login-input-wrap .input-icon{
  position:absolute;
  left:14px;
  top:50%;
  transform:translateY(-50%);
  font-size:15px;
  color:var(--muted-2);
  pointer-events:none;
  transition:color .2s ease;
  line-height:1;
  z-index:2;
}

.login-input-wrap input{
  display:block;
  width:100%;
  height:46px;
  padding:0 14px 0 42px;
  background:var(--sand);
  border:1px solid var(--line);
  border-radius:11px;
  font-family:inherit;
  font-size:14px;
  color:var(--text);
  outline:none;
  transition:all .22s cubic-bezier(.22,.68,.32,1);
  -webkit-appearance:none;
  appearance:none;
}
.login-input-wrap input::placeholder{
  color:var(--muted-2);
  font-weight:400;
}
.login-input-wrap input:focus{
  background:var(--cream);
  border-color:var(--terracotta);
  box-shadow:0 0 0 4px rgba(183,97,70,.13);
}
.login-input-wrap:focus-within .input-icon{
  color:var(--terracotta);
}

/* Remember me */
.login-extras{
  display:flex;
  align-items:center;
  gap:12px;
  margin:2px 0 16px;
  font-size:12.4px;
}
.login-extras label{
  display:flex;
  align-items:center;
  gap:8px;
  color:var(--muted);
  cursor:pointer;
  user-select:none;
  font-weight:500;
}
.login-extras input[type="checkbox"]{
  width:15px;
  height:15px;
  accent-color:var(--terracotta);
  cursor:pointer;
  margin:0;
}

/* Submit */
.btn-login-submit{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:9px;
  width:100%;
  height:48px;
  padding:0 20px;
  background:var(--terracotta);
  color:#fff;
  border:none;
  border-radius:11px;
  font-family:inherit;
  font-size:14px;
  font-weight:600;
  letter-spacing:.3px;
  cursor:pointer;
  transition:all .25s cubic-bezier(.22,.68,.32,1);
  box-shadow:0 12px 26px -12px rgba(183,97,70,.85);
}
.btn-login-submit:hover{
  background:var(--terracotta-dark);
  transform:translateY(-1px);
  box-shadow:0 18px 34px -12px rgba(183,97,70,.95);
}
.btn-login-submit:active{transform:translateY(0)}
.btn-login-submit i{font-size:15px}

/* Divider */
.login-divider{
  display:flex;
  align-items:center;
  gap:12px;
  margin:16px 0 12px;
  color:var(--muted-2);
  font-size:10px;
  letter-spacing:1.5px;
  text-transform:uppercase;
  font-weight:600;
}
.login-divider::before,
.login-divider::after{
  content:"";
  flex:1;
  height:1px;
  background:linear-gradient(90deg,transparent,var(--line),transparent);
}

/* Back link */
.login-back{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:7px;
  width:100%;
  height:42px;
  background:transparent;
  border:1px solid var(--line);
  border-radius:11px;
  color:var(--plum);
  font-size:12.6px;
  font-weight:600;
  text-decoration:none;
  transition:all .22s;
}
.login-back:hover{
  background:var(--sand);
  border-color:var(--champagne);
  color:var(--terracotta);
  transform:translateY(-1px);
}
.login-back i{font-size:13px}

/* Hint */
.login-hint{
  text-align:center;
  margin-top:16px;
  padding-top:14px;
  border-top:1px solid var(--line);
  font-size:11px;
  color:var(--muted);
  line-height:1.7;
}
.login-hint b{
  color:var(--terracotta);
  font-weight:600;
  background:rgba(183,97,70,.09);
  padding:2px 7px;
  border-radius:5px;
  font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
  font-size:10.8px;
  letter-spacing:.2px;
}

/* Height-based adjustments for laptops */
@media (max-height:760px){
  .login-box{padding:26px 28px 22px}
  .login-brand{margin-bottom:18px}
  .login-brand .brand-mark{width:46px;height:46px;font-size:22px;margin-bottom:10px}
  .login-brand h1{font-size:20px}
  .login-brand p{font-size:10px;letter-spacing:1.8px}
  .login-field{margin-bottom:12px}
  .login-input-wrap input{height:44px}
  .btn-login-submit{height:46px}
  .login-divider{margin:14px 0 10px}
  .login-back{height:40px}
  .login-hint{margin-top:14px;padding-top:12px}
}

/* Very short viewports */
@media (max-height:680px){
  body{padding:14px 12px}
  .login-box{padding:22px 24px 18px;border-radius:18px}
  .login-brand .brand-mark{width:42px;height:42px;font-size:20px;margin-bottom:8px}
  .login-brand h1{font-size:18px}
  .login-brand{margin-bottom:14px}
  .login-field{margin-bottom:10px}
  .login-field label{font-size:10.4px;margin-bottom:5px}
  .login-input-wrap input{height:42px;font-size:13.6px}
  .btn-login-submit{height:44px;font-size:13.6px}
  .login-extras{margin:0 0 12px;font-size:12px}
  .login-divider{margin:12px 0 8px}
  .login-back{height:38px;font-size:12px}
  .login-hint{margin-top:10px;padding-top:10px;font-size:10.6px}
  .login-hint b{font-size:10.4px;padding:2px 6px}
}

/* Mobile width */
@media (max-width:480px){
  body{padding:16px 14px}
  .login-box{padding:26px 22px 20px;border-radius:18px;max-width:100%}
}

/* Allow scroll if very short */
@media (max-height:600px){
  body{align-items:flex-start;padding-top:20px;padding-bottom:20px}
}
</style>
</head>
<body>

<div class="login-box">

  <div class="login-brand">
    <div class="brand-mark">ॐ</div>
    <h1>Divya Aastha</h1>
    <p>Admin Panel</p>
  </div>

  <?php if ($error): ?>
    <div class="flash flash-error">
      <i class="bi bi-exclamation-circle-fill"></i>
      <span><?= e($error) ?></span>
    </div>
  <?php endif; ?>

  <form method="POST" autocomplete="on">
    <?= csrf_field() ?>

    <div class="login-field">
      <label for="adminEmail">Email Address</label>
      <div class="login-input-wrap">
        <i class="bi bi-envelope input-icon"></i>
        <input type="email"
               id="adminEmail"
               name="email"
               required
               autofocus
               autocomplete="username"
               value="<?= e($email) ?>"
               placeholder="admin@divyaaastha.in">
      </div>
    </div>

    <div class="login-field">
      <label for="adminPassword">Password</label>
      <div class="login-input-wrap">
        <i class="bi bi-lock input-icon"></i>
        <input type="password"
               id="adminPassword"
               name="password"
               required
               autocomplete="current-password"
               placeholder="Enter your password">
      </div>
    </div>

    <div class="login-extras">
      <label>
        <input type="checkbox" name="remember">
        Remember me
      </label>
    </div>

    <button type="submit" class="btn-login-submit">
      <i class="bi bi-box-arrow-in-right"></i>
      Login to Admin Panel
    </button>
  </form>

  <div class="login-divider">or</div>

  <a href="<?= url('index.php') ?>" class="login-back">
    <i class="bi bi-arrow-left"></i>
    Back to Website
  </a>

  <p class="login-hint">
    Default credentials<br>
    <b>admin@divyaaastha.in</b> / <b>Admin@123</b>
  </p>

</div>

</body>
</html>