<?php
require_once __DIR__ . '/config/config.php';

if (is_logged_in()) redirect('account.php');

$error = '';
$name = $email = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_token'] ?? null)) {
        $error = 'Session expired. Please try again.';
    } else {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $pass  = $_POST['password'] ?? '';

        if (!$name || !$email || !$pass) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($pass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);

            if ($stmt->fetchColumn()) {
                $error = 'An account with this email already exists.';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    'INSERT INTO users (name, email, phone, password_hash, role, status)
                     VALUES (?, ?, ?, ?, "devotee", 1)'
                );
                $stmt->execute([$name, $email, $phone ?: null, $hash]);

                $userId = (int) $pdo->lastInsertId();
                login_user($userId, $name, $email, 'devotee');
                flash_set('success', 'Welcome to Divya Aastha, ' . $name . '!');
                redirect('account.php');
            }
        }
    }
}

$pageTitle = 'Create Account — Divya Aastha';
require BASE_PATH . '/includes/header.php';
?>

<section class="section">
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="auth-head">
        <span class="brand-mark">ॐ</span>
        <h1>Create Account</h1>
        <p>Join thousands of devotees on Divya Aastha</p>
      </div>

      <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

      <form method="POST">
        <?= csrf_field() ?>
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="name" required value="<?= e($name) ?>" autofocus>
        </div>
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" required value="<?= e($email) ?>">
        </div>
        <div class="form-group">
          <label>Phone (optional)</label>
          <input type="tel" name="phone" value="<?= e($phone) ?>">
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" required minlength="6">
          <p class="hint">At least 6 characters</p>
        </div>
        <button type="submit" class="btn btn-terra btn-block">Create Account</button>
      </form>

      <div class="auth-foot">
        Already have an account? <a href="<?= url('login.php') ?>">Login here</a>
      </div>
    </div>
  </div>
</section>

<?php require BASE_PATH . '/includes/footer.php'; ?>