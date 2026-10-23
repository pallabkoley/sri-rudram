<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$id = (int) get('id', 0);
if ($id <= 0) { flash_set('error','Invalid user ID.'); redirect('admin/modules/users/index.php'); }

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { flash_set('error','User not found.'); redirect('admin/modules/users/index.php'); }

$adminPageTitle = 'Edit User';
$adminPageSub   = 'Update account details';
$errors         = [];

$data = [
    'name'   => $user['name'],
    'email'  => $user['email'],
    'phone'  => (string) ($user['phone'] ?? ''),
    'gotra'  => (string) ($user['gotra'] ?? ''),
    'role'   => (string) $user['role'],
    'status' => (int) $user['status'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));

    $data['name']   = trim((string) post('name', ''));
    $data['email']  = trim((string) post('email', ''));
    $data['phone']  = trim((string) post('phone', ''));
    $data['gotra']  = trim((string) post('gotra', ''));
    $data['role']   = (string) post('role', 'devotee');
    $data['status'] = (int) post('status', 0);

    $newPassword = (string) post('new_password', '');
    $confirm     = (string) post('confirm_password', '');

    /* Validation */
    if ($data['name'] === '')  $errors['name']  = 'Name is required.';
    if ($data['email'] === '') $errors['email'] = 'Email is required.';
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format.';
    if (!in_array($data['role'], ['devotee','admin'], true)) $data['role'] = 'devotee';

    if (!isset($errors['email'])) {
        $c = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $c->execute([$data['email'], $id]);
        if ($c->fetch()) $errors['email'] = 'That email is already registered.';
    }

    /* Password is optional in edit mode */
    if ($newPassword !== '' || $confirm !== '') {
        if (strlen($newPassword) < 6) $errors['new_password'] = 'Password must be at least 6 characters.';
        if ($newPassword !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (!$errors) {
        try {
            if ($newPassword !== '') {
                $pdo->prepare(
                    'UPDATE users SET name=?, email=?, phone=?, gotra=?, role=?, status=?, password_hash=?
                     WHERE id=?'
                )->execute([
                    $data['name'],
                    $data['email'],
                    $data['phone'] ?: null,
                    $data['gotra'] ?: null,
                    $data['role'],
                    $data['status'],
                    password_hash($newPassword, PASSWORD_DEFAULT),
                    $id,
                ]);
            } else {
                $pdo->prepare(
                    'UPDATE users SET name=?, email=?, phone=?, gotra=?, role=?, status=? WHERE id=?'
                )->execute([
                    $data['name'],
                    $data['email'],
                    $data['phone'] ?: null,
                    $data['gotra'] ?: null,
                    $data['role'],
                    $data['status'],
                    $id,
                ]);
            }

            flash_set('success', 'User updated successfully.');
            redirect('admin/modules/users/index.php');

        } catch (PDOException $e) {
            error_log('[users.edit] ' . $e->getMessage());
            $errors['_'] = 'Could not save: ' . $e->getMessage();
        }
    }
}

/* Stats for the side panel */
$orderCount   = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE user_id = ' . (int) $id)->fetchColumn();
$bookingCount = (int) $pdo->query('SELECT COUNT(*) FROM puja_bookings WHERE user_id = ' . (int) $id)->fetchColumn();

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
          <div class="ico"><i class="bi bi-person"></i></div>
          <div><h4>User Details</h4><p>Name, email and phone</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label class="f-label">Full Name <span class="req">*</span></label>
              <input type="text" name="name" value="<?= e($data['name']) ?>"
                     class="f-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>" required autofocus>
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
              <input type="tel" name="phone" value="<?= e($data['phone']) ?>" class="f-input">
            </div>

            <div class="col-md-6">
              <label class="f-label">Gotra</label>
              <input type="text" name="gotra" value="<?= e($data['gotra']) ?>" class="f-input">
            </div>

          </div>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-shield-lock"></i></div>
          <div><h4>Change Password</h4><p>Leave blank to keep current password</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label class="f-label">New Password</label>
              <input type="password" name="new_password"
                     class="f-input <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
                     minlength="6" placeholder="Leave empty to keep unchanged">
              <?php if (isset($errors['new_password'])): ?><div class="f-err"><?= e($errors['new_password']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
              <label class="f-label">Confirm New Password</label>
              <input type="password" name="confirm_password"
                     class="f-input <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                     minlength="6">
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

          <label class="f-toggle" for="f-status" style="margin-top:14px">
            <input type="checkbox" id="f-status" name="status" value="1"
                   <?= $data['status'] ? 'checked' : '' ?>>
            <span class="txt"><b>Active</b><span>User can log in</span></span>
          </label>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-activity"></i></div>
          <div><h4>Activity</h4><p>User's purchases &amp; bookings</p></div>
        </div>
        <div class="form-card-body" style="font-size:13.5px;color:#4B5563;line-height:1.9">
          <b style="color:#351B2F">Orders:</b> <?= $orderCount ?><br>
          <b style="color:#351B2F">Puja Bookings:</b> <?= $bookingCount ?><br>
          <b style="color:#351B2F">Member since:</b> <?= date('d M Y', strtotime($user['created_at'])) ?>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="form-btn form-btn-save">
          <i class="bi bi-check2-circle"></i> Save Changes
        </button>
        <a href="<?= url('admin/modules/users/index.php') ?>" class="form-btn form-btn-cancel">Cancel</a>
      </div>
    </div>
  </div>
</form>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>