<?php
require_once __DIR__ . '/config/config.php';

if (is_logged_in()) redirect('account.php');

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* CSRF verify */
    csrf_verify(post('_csrf', ''));

    $email = trim((string) post('email', ''));
    $pass  = (string) post('password', '');

    if ($email === '' || $pass === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, name, email, password_hash, role
             FROM users
             WHERE email = ? AND status = 1
             LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password_hash'])) {

            /* ==================================================
             |  ADAPTIVE login_user() CALL
             |  Inspects the function signature via reflection and
             |  passes the exact arguments it needs.
             * ================================================== */
            try {
                $ref    = new ReflectionFunction('login_user');
                $params = $ref->getParameters();
                $args   = [];

                foreach ($params as $p) {
                    $pName = $p->getName();
                    $value = null;

                    /* 1) Exact match in $user array */
                    if (isset($user[$pName])) {
                        $value = $user[$pName];
                    }
                    /* 2) Common aliases for user id */
                    elseif (in_array($pName, ['userId','user_id','id','uid'], true)) {
                        $value = (int) $user['id'];
                    }
                    /* 3) Common aliases for email */
                    elseif (in_array($pName, ['email','userEmail','user_email'], true)) {
                        $value = (string) $user['email'];
                    }
                    /* 4) Common aliases for name */
                    elseif (in_array($pName, ['name','userName','user_name','username'], true)) {
                        $value = (string) $user['name'];
                    }
                    /* 5) Common aliases for role */
                    elseif (in_array($pName, ['role','userRole','user_role'], true)) {
                        $value = (string) ($user['role'] ?? 'devotee');
                    }
                    /* 6) Anything called "user" gets the whole array */
                    elseif ($pName === 'user') {
                        $value = $user;
                    }
                    /* 7) Otherwise pass null (only if optional) */
                    else {
                        if ($p->isOptional()) continue;
                        $value = null;
                    }

                    /* Cast to declared type */
                    if ($p->hasType()) {
                        $t = (string) $p->getType();
                        if ($t === 'int')         $value = (int) $value;
                        elseif ($t === 'string')  $value = (string) $value;
                        elseif ($t === 'bool')    $value = (bool) $value;
                        elseif ($t === 'array')   $value = is_array($value) ? $value : $user;
                        /* Leave other types as-is */
                    }

                    $args[] = $value;
                }

                call_user_func_array('login_user', $args);

            } catch (Throwable $e) {
                /* Fallback: try common patterns if reflection fails */
                error_log('[login] reflection failed: ' . $e->getMessage());

                $called = false;

                /* Attempt 1: (id, email, role) */
                try {
                    login_user((int) $user['id'], (string) $user['email'], (string) ($user['role'] ?? 'devotee'));
                    $called = true;
                } catch (Throwable $e1) {
                    /* Attempt 2: (id, email, name) */
                    try {
                        login_user((int) $user['id'], (string) $user['email'], (string) $user['name']);
                        $called = true;
                    } catch (Throwable $e2) {
                        /* Attempt 3: (id, name, email) */
                        try {
                            login_user((int) $user['id'], (string) $user['name'], (string) $user['email']);
                            $called = true;
                        } catch (Throwable $e3) {
                            /* Attempt 4: whole array */
                            try {
                                login_user($user);
                                $called = true;
                            } catch (Throwable $e4) {
                                error_log('[login] all fallback attempts failed');
                            }
                        }
                    }
                }

                if (!$called) {
                    $error = 'Login failed due to a server configuration issue. Please contact support.';
                }
            }

            if ($error === '') {
                $redirect = $_SESSION['redirect_after_login'] ?? 'account.php';
                unset($_SESSION['redirect_after_login']);

                flash_set('success', 'Welcome back, ' . $user['name'] . '!');
                redirect($redirect);
            }

        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login — Divya Aastha';
require BASE_PATH . '/includes/header.php';
?>

<section class="section">
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="auth-head">
        <span class="brand-mark">ॐ</span>
        <h1>Welcome Back</h1>
        <p>Login to your Divya Aastha account</p>
      </div>

      <?php if ($error): ?>
        <div class="flash flash-error"><?= e($error) ?></div>
      <?php endif; ?>

      <?php foreach (flash_get() as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
      <?php endforeach; ?>

      <form method="POST">
        <?= csrf_field() ?>

        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" required
                 value="<?= e($email) ?>" autofocus>
        </div>

        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-terra btn-block">Login</button>
      </form>

      <div class="auth-foot">
        New to Divya Aastha?
        <a href="<?= url('register.php') ?>">Create an account</a>
      </div>
    </div>
  </div>
</section>

<?php require BASE_PATH . '/includes/footer.php'; ?>