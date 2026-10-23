<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = 'Add User';
$adminPageSub   = 'Create a new user account';
$errors         = [];

$data = [
    'name'     => '',
    'email'    => '',
    'phone'    => '',
    'gotra'    => '',
    'role'     => 'devotee',
    'status'   => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));

    $data['name']   = trim((string) post('name', ''));
    $data['email']  = trim((string) post('email', ''));
    $data['phone']  = trim((string) post('phone', ''));
    $data['gotra']  = trim((string) post('gotra', ''));
    $data['role']   = (string) post('role', 'devotee');
    $data['status'] = (int) post('status', 1);

    $password = (string) post('password', '');
    $confirm  = (string) post('confirm_password', '');

    /* Validation */
    if ($data['name'] === '')  $errors['name']  = 'Name is required.';
    if ($data['email'] === '') $errors['email'] = 'Email is required.';
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format.';
    if ($password === '')      $errors['password'] = 'Password is required.';
    elseif (strlen($password) < 6) $errors['password'] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';
    if (!in_array($data['role'], ['devotee','admin'], true)) $data['role'] = 'devotee';

    if (!isset($errors['email'])) {
        $c = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $c->execute([$data['email']]);
        if ($c->fetch()) $errors['email'] = 'That email is already registered.';
    }

    if (!$errors) {
        try {
            $pdo->prepare(
                'INSERT INTO users (name, email, phone, password_hash, gotra, role, status)
                 VALUES (?,?,?,?,?,?,?)'
            )->execute([
                $data['name'],
                $data['email'],
                $data['phone'] ?: null,
                password_hash($password, PASSWORD_DEFAULT),
                $data['gotra'] ?: null,
                $data['role'],
                $data['status'],
            ]);

            flash_set('success', 'User created successfully.');
            redirect('admin/modules/users/index.php');
        } catch (PDOException $e) {
            error_log('[users.create] ' . $e->getMessage());
            $errors['_'] = 'Could not create user: ' . $e->getMessage();
        }
    }
}

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<?php if (!empty($errors['_'])): ?>
  <div class="form-alert"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($errors['_']) ?></div>
<?php endif; ?>

<form method="post" novalidate autocomplete="off">
  <?= csrf_field() ?>
  <div class="form-page">
    <div class="form-main">

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-person-plus"></i></div>
          <div><h4>User Details</h4><p>Name, email and phone</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label class="f-label">Full Name <span class="req">*</span></label>
              <input type="text" name="name" value="<?= e($data['name']) ?>"
                     class="f-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                     required autofocus>
              <?php if (isset($errors['name'])): ?><div class="f-err"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
              <label class="f-label">Email <span class="req">*</span></label>
              <input type="email" name="email" value="<?= e($data['email']) ?>"
                     class="f-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>" required>
              <?php if (isset($errors['email'])): ?><div class="f-err"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
              <label class="f-label">Phone</label>
              <input type="tel" name="phone" value="<?= e($data['phone']) ?>"
                     class="f-input" placeholder="+91 90000 00000">
            </div>

            <div class="col-md-6">
              <label class="f-label">Gotra</label>
              <input type="text" name="gotra" value="<?= e($data['gotra']) ?>"
                     class="f-input" placeholder="e.g. Kashyap">
            </div>

          </div>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-shield-lock"></i></div>
          <div><h4>Password</h4><p>Set the account password</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label class="f-label">Password <span class="req">*</span></label>
              <input type="password" name="password"
                     class="f-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required minlength="6">
              <?php if (isset($errors['password'])): ?><div class="f-err"><?= e($errors['password']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
              <label class="f-label">Confirm Password <span class="req">*</span></label>
              <input type="password" name="confirm_password"
                     class="f-input <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" required minlength="6">
              <?php if (isset($errors['confirm_password'])): ?><div class="f-err"><?= e($errors['confirm_password']) ?></div><?php endif; ?>
            </div>

          </div>
        </div>
      </div>

    </div>

    <div class="form-side">
      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-person-badge"></i></div>
          <div><h4>Role &amp; Status</h4><p>Permissions</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label">Role</label>
          <select name="role" class="f-input">
            <option value="devotee" <?= $data['role'] === 'devotee' ? 'selected' : '' ?>>Devotee</option>
            <option value="admin"   <?= $data['role'] === 'admin'   ? 'selected' : '' ?>>Admin</option>
          </select>
          <div class="f-hint">Admins can access this admin panel.</div>

          <label class="f-toggle" for="f-status" style="margin-top:14px">
            <input type="checkbox" id="f-status" name="status" value="1"
                   <?= $data['status'] ? 'checked' : '' ?>>
            <span class="txt"><b>Active</b><span>User can log in</span></span>
          </label>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="form-btn form-btn-save">
          <i class="bi bi-check2-circle"></i> Create User
        </button>
        <a href="<?= url('admin/modules/users/index.php') ?>" class="form-btn form-btn-cancel">Cancel</a>
      </div>
    </div>
  </div>
</form>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>