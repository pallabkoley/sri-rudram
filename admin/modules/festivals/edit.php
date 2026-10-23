<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* Load festival */
$id = (int) get('id', 0);
if ($id <= 0) { flash_set('error','Invalid festival ID.'); redirect('admin/modules/festivals/index.php'); }

$stmt = $pdo->prepare('SELECT * FROM festivals WHERE id = ?');
$stmt->execute([$id]);
$fest = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$fest) { flash_set('error','Festival not found.'); redirect('admin/modules/festivals/index.php'); }

$adminPageTitle = 'Edit Festival';
$adminPageSub   = 'Update festival details';
$errors         = [];

$data = [
    'name'          => $fest['name'],
    'slug'          => $fest['slug'],
    'festival_date' => $fest['festival_date'],
    'short_desc'    => (string) ($fest['short_desc'] ?? ''),
    'description'   => (string) ($fest['description'] ?? ''),
    'is_active'     => (int) $fest['is_active'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));

    foreach (['name','slug','short_desc','description'] as $k) {
        $data[$k] = trim((string) post($k, ''));
    }
    $data['festival_date'] = trim((string) post('festival_date', ''));
    $data['is_active']     = (int) post('is_active', 0);

    /* Validation */
    if ($data['name'] === '') $errors['name'] = 'Festival name is required.';
    if ($data['festival_date'] === '' || !strtotime($data['festival_date'])) {
        $errors['festival_date'] = 'Please provide a valid date.';
    }
    if ($data['slug'] === '') $data['slug'] = function_exists('slugify')
        ? slugify($data['name'])
        : trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $data['name'])), '-');

    if (!isset($errors['slug'])) {
        $c = $pdo->prepare('SELECT id FROM festivals WHERE slug = ? AND id <> ?');
        $c->execute([$data['slug'], $id]);
        if ($c->fetch()) $errors['slug'] = 'That slug is already in use.';
    }

    if (!$errors) {
        try {
            $pdo->prepare(
                'UPDATE festivals SET
                   name = ?, slug = ?, festival_date = ?, short_desc = ?,
                   description = ?, is_active = ?
                 WHERE id = ?'
            )->execute([
                $data['name'],
                $data['slug'],
                date('Y-m-d', strtotime($data['festival_date'])),
                $data['short_desc'] ?: null,
                $data['description'] ?: null,
                $data['is_active'],
                $id,
            ]);

            flash_set('success', 'Festival updated successfully.');
            redirect('admin/modules/festivals/index.php');

        } catch (PDOException $e) {
            error_log('[festivals.edit] ' . $e->getMessage());
            $errors['_'] = 'Could not save: ' . $e->getMessage();
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
          <div class="ico"><i class="bi bi-info-circle"></i></div>
          <div><h4>Basic Information</h4><p>Name, slug and date</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">

            <div class="col-md-8">
              <label class="f-label" for="f-name">Festival Name <span class="req">*</span></label>
              <input type="text" id="f-name" name="name" value="<?= e($data['name']) ?>"
                     class="f-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>" required autofocus>
              <?php if (isset($errors['name'])): ?><div class="f-err"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
              <label class="f-label" for="f-slug">Slug</label>
              <input type="text" id="f-slug" name="slug" value="<?= e($data['slug']) ?>"
                     class="f-input <?= isset($errors['slug']) ? 'is-invalid' : '' ?>">
              <?php if (isset($errors['slug'])): ?>
                <div class="f-err"><?= e($errors['slug']) ?></div>
              <?php else: ?>
                <div class="f-hint">Changing this breaks existing links.</div>
              <?php endif; ?>
            </div>

            <div class="col-md-4">
              <label class="f-label" for="f-date">Festival Date <span class="req">*</span></label>
              <input type="date" id="f-date" name="festival_date"
                     value="<?= e(date('Y-m-d', strtotime($data['festival_date']))) ?>"
                     class="f-input <?= isset($errors['festival_date']) ? 'is-invalid' : '' ?>" required>
              <?php if (isset($errors['festival_date'])): ?>
                <div class="f-err"><?= e($errors['festival_date']) ?></div>
              <?php endif; ?>
            </div>

            <div class="col-md-12">
              <label class="f-label" for="f-short">Short Description</label>
              <input type="text" id="f-short" name="short_desc"
                     value="<?= e($data['short_desc']) ?>" class="f-input" maxlength="400">
            </div>

          </div>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-text-paragraph"></i></div>
          <div><h4>Full Description</h4><p>Optional detailed information</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label" for="f-desc">Description</label>
          <textarea id="f-desc" name="description" class="f-textarea"
                    rows="6"><?= e($data['description']) ?></textarea>
        </div>
      </div>

    </div>

    <div class="form-side">
      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-eye"></i></div>
          <div><h4>Visibility</h4><p>Show on site</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-toggle" for="f-act">
            <input type="checkbox" id="f-act" name="is_active" value="1"
                   <?= $data['is_active'] ? 'checked' : '' ?>>
            <span class="txt"><b>Active</b><span>Visible on the site</span></span>
          </label>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="form-btn form-btn-save">
          <i class="bi bi-check2-circle"></i> Save Changes
        </button>
        <a href="<?= url('admin/modules/festivals/index.php') ?>" class="form-btn form-btn-cancel">Cancel</a>
      </div>
    </div>
  </div>
</form>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>