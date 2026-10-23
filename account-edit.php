<?php
require_once __DIR__ . '/config/config.php';

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'account-edit.php';
    flash_set('info', 'Please login to edit your profile.');
    redirect('login.php');
}

$userId = current_user_id();

/* ==================================================================
 |  Load current user
 * ================================================================== */
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    redirect('login.php');
}

$errors = [];

/* ==================================================================
 |  Handle POST
 * ================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));
    $action = (string) post('action', 'profile');

    /* ── Update profile ─────────────────────────────── */
    if ($action === 'profile') {
        $name  = trim((string) post('name', ''));
        $email = trim((string) post('email', ''));
        $phone = trim((string) post('phone', ''));
        $gotra = trim((string) post('gotra', ''));

        if ($name === '')  $errors['name']  = 'Name is required.';
        if ($email === '') $errors['email'] = 'Email is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if (!isset($errors['email'])) {
            $c = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
            $c->execute([$email, $userId]);
            if ($c->fetch()) {
                $errors['email'] = 'That email is already in use.';
            }
        }

        if (!$errors) {
            try {
                $pdo->prepare(
                    'UPDATE users SET name = ?, email = ?, phone = ?, gotra = ? WHERE id = ?'
                )->execute([
                    $name,
                    $email,
                    $phone ?: null,
                    $gotra ?: null,
                    $userId,
                ]);

                /* Keep session name in sync if your app uses it */
                if (isset($_SESSION['user_name'])) {
                    $_SESSION['user_name'] = $name;
                }

                flash_set('success', 'Profile updated successfully.');
                redirect('account-edit.php');

            } catch (PDOException $e) {
                error_log('[account-edit] ' . $e->getMessage());
                $errors['_'] = 'Could not save your details. Please try again.';
            }
        }

        /* Keep submitted values on error */
        $user['name']  = $name;
        $user['email'] = $email;
        $user['phone'] = $phone;
        $user['gotra'] = $gotra;
    }

    /* ── Change password ────────────────────────────── */
    if ($action === 'password') {
        $current = (string) post('current_password', '');
        $new     = (string) post('new_password', '');
        $confirm = (string) post('confirm_password', '');

        if ($current === '') {
            $errors['current_password'] = 'Current password is required.';
        }
        if ($new === '') {
            $errors['new_password'] = 'New password is required.';
        } elseif (strlen($new) < 6) {
            $errors['new_password'] = 'Password must be at least 6 characters.';
        }
        if ($new !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if (!isset($errors['current_password'])
            && !password_verify($current, $user['password_hash'])) {
            $errors['current_password'] = 'Current password is incorrect.';
        }

        if (!$errors) {
            try {
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);

                flash_set('success', 'Password updated successfully.');
                redirect('account-edit.php');

            } catch (PDOException $e) {
                error_log('[account-edit:pwd] ' . $e->getMessage());
                $errors['_'] = 'Could not update your password. Please try again.';
            }
        }
    }
}

$pageTitle = 'Edit Profile — Divya Aastha';
require BASE_PATH . '/includes/header.php';
?>

<!-- ═══════════════ HERO ═══════════════ -->
<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <a href="<?= url('account.php') ?>">My Account</a>
      <span class="sep">›</span>
      <span class="current">Edit Profile</span>
    </nav>
    <span class="eyebrow">Your Details</span>
    <h1>Edit Profile</h1>
    <p>Update your personal information and password.</p>
  </div>
</section>

<!-- ═══════════════ ACCOUNT SHELL ═══════════════ -->
<style>
.ac-shell{max-width:1200px;margin:0 auto;padding:36px 22px 80px;background:#FAF6EF}
.ac-layout{display:grid;grid-template-columns:240px 1fr;gap:26px}
@media(max-width:900px){.ac-layout{grid-template-columns:1fr;gap:18px}}
.ac-nav{background:#fff;border:1px solid #EFE6D9;border-radius:16px;padding:14px;height:fit-content;
  position:sticky;top:90px;box-shadow:0 1px 3px rgba(53,27,47,.05)}
@media(max-width:900px){.ac-nav{position:static;display:flex;overflow-x:auto;gap:8px;padding:10px}}
.ac-nav a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;
  font-size:13.5px;font-weight:600;color:#4B5563;text-decoration:none;transition:all .15s;margin-bottom:4px}
@media(max-width:900px){.ac-nav a{flex-shrink:0;margin-bottom:0;white-space:nowrap}}
.ac-nav a:hover{background:#FAF6EF;color:#B76146}
.ac-nav a.active{background:#351B2F;color:#fff}
.ac-nav a i{font-size:16px;width:18px;text-align:center}
.ac-nav a.logout{color:#DC2626;margin-top:8px;border-top:1px solid #F3EEE6;padding-top:16px}
.ac-nav a.logout:hover{background:#FEF2F2;color:#DC2626}

.ac-content{min-width:0}
.ac-h1{font-family:var(--font-serif,Georgia,serif);font-size:26px;color:#351B2F;margin:0 0 6px;font-weight:600}
.ac-sub{color:#6B7280;font-size:13.5px;margin:0 0 24px;line-height:1.6}

.ac-flash{padding:14px 18px;border-radius:12px;font-size:13.5px;margin-bottom:18px;line-height:1.55}
.ac-flash.success{background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0}
.ac-flash.error{background:#FEF2F2;color:#991B1B;border:1px solid #FECACA}
.ac-flash.info{background:#EFF6FF;color:#1E40AF;border:1px solid #BFDBFE}

.ac-card{background:#fff;border:1px solid #EFE6D9;border-radius:16px;overflow:hidden;
  box-shadow:0 1px 3px rgba(53,27,47,.05);margin-bottom:20px}
.ac-card__head{padding:16px 22px;border-bottom:1px solid #EFE6D9}
.ac-card__head h3{margin:0;font-family:var(--font-serif,Georgia,serif);font-size:16px;color:#351B2F;font-weight:600}
.ac-card__body{padding:22px}

.ac-field{display:flex;flex-direction:column;gap:6px}
.ac-field label{font-size:12px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:#6B7280}
.ac-field input{width:100%;padding:11px 14px;border:1px solid #E5E7EB;border-radius:10px;
  font-family:inherit;font-size:14px;color:#1F2937;background:#fff;
  transition:border-color .18s,box-shadow .18s}
.ac-field input:focus{outline:none;border-color:#B76146;
  box-shadow:0 0 0 3px rgba(183,97,70,.12)}
.ac-field .err{color:#DC2626;font-size:12px;margin-top:2px}

.ac-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:600px){.ac-row{grid-template-columns:1fr}}

.ac-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;
  padding:11px 22px;font-size:13px;font-weight:600;border-radius:10px;
  text-decoration:none;border:1px solid transparent;cursor:pointer;
  font-family:inherit;transition:all .18s;white-space:nowrap}
.ac-btn-primary{background:#B76146;color:#fff;border-color:#B76146}
.ac-btn-primary:hover{background:#9B4C34;color:#fff}
</style>

<div class="ac-shell">
  <div class="ac-layout">

    <!-- ── SIDEBAR (inline) ───────────────────────────── -->
    <aside class="ac-nav">
      <a href="<?= url('account.php') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="<?= url('account-orders.php') ?>"><i class="bi bi-box-seam"></i> My Orders</a>
      <a href="<?= url('account-bookings.php') ?>"><i class="bi bi-calendar-check"></i> Puja Bookings</a>
      <a href="<?= url('account-addresses.php') ?>"><i class="bi bi-geo-alt"></i> Addresses</a>
      <a href="<?= url('account-edit.php') ?>" class="active"><i class="bi bi-person"></i> Edit Profile</a>
      <a href="<?= url('wishlist.php') ?>"><i class="bi bi-heart"></i> Wishlist</a>
      <a href="<?= url('logout.php') ?>" class="logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </aside>

    <!-- ── CONTENT ─────────────────────────────────────── -->
    <div class="ac-content">
      <h1 class="ac-h1">Edit Profile</h1>
      <p class="ac-sub">Keep your details up to date for seamless puja bookings and delivery.</p>

      <?php foreach (flash_get() as $f): ?>
        <div class="ac-flash <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
      <?php endforeach; ?>

      <?php if (!empty($errors['_'])): ?>
        <div class="ac-flash error"><?= e($errors['_']) ?></div>
      <?php endif; ?>

      <!-- ── Personal Information ────────────────────── -->
      <div class="ac-card">
        <div class="ac-card__head"><h3>Personal Information</h3></div>
        <div class="ac-card__body">
          <form method="POST" novalidate autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="profile">

            <div class="ac-row">
              <div class="ac-field">
                <label for="f-name">Full Name *</label>
                <input type="text" id="f-name" name="name"
                       value="<?= e($user['name']) ?>" required autofocus>
                <?php if (!empty($errors['name'])): ?>
                  <div class="err"><?= e($errors['name']) ?></div>
                <?php endif; ?>
              </div>

              <div class="ac-field">
                <label for="f-email">Email Address *</label>
                <input type="email" id="f-email" name="email"
                       value="<?= e($user['email']) ?>" required>
                <?php if (!empty($errors['email'])): ?>
                  <div class="err"><?= e($errors['email']) ?></div>
                <?php endif; ?>
              </div>

              <div class="ac-field">
                <label for="f-phone">Phone Number</label>
                <input type="tel" id="f-phone" name="phone"
                       value="<?= e($user['phone'] ?? '') ?>"
                       placeholder="+91 90000 00000">
              </div>

              <div class="ac-field">
                <label for="f-gotra">Gotra</label>
                <input type="text" id="f-gotra" name="gotra"
                       value="<?= e($user['gotra'] ?? '') ?>"
                       placeholder="e.g. Kashyap">
              </div>
            </div>

            <div style="margin-top:22px">
              <button type="submit" class="ac-btn ac-btn-primary">
                <i class="bi bi-check2-circle"></i> Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ── Change Password ──────────────────────────── -->
      <div class="ac-card">
        <div class="ac-card__head"><h3>Change Password</h3></div>
        <div class="ac-card__body">
          <form method="POST" novalidate autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">

            <div class="ac-field" style="margin-bottom:16px">
              <label for="f-cur">Current Password *</label>
              <input type="password" id="f-cur" name="current_password" required>
              <?php if (!empty($errors['current_password'])): ?>
                <div class="err"><?= e($errors['current_password']) ?></div>
              <?php endif; ?>
            </div>

            <div class="ac-row">
              <div class="ac-field">
                <label for="f-new">New Password *</label>
                <input type="password" id="f-new" name="new_password"
                       required minlength="6">
                <?php if (!empty($errors['new_password'])): ?>
                  <div class="err"><?= e($errors['new_password']) ?></div>
                <?php endif; ?>
              </div>

              <div class="ac-field">
                <label for="f-conf">Confirm New Password *</label>
                <input type="password" id="f-conf" name="confirm_password"
                       required minlength="6">
                <?php if (!empty($errors['confirm_password'])): ?>
                  <div class="err"><?= e($errors['confirm_password']) ?></div>
                <?php endif; ?>
              </div>
            </div>

            <div style="margin-top:22px">
              <button type="submit" class="ac-btn ac-btn-primary">
                <i class="bi bi-shield-lock"></i> Update Password
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>

  </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>