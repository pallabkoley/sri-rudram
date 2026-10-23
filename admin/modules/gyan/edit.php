<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$id = (int) get('id', 0);
if ($id <= 0) { flash_set('error','Invalid category ID.'); redirect('admin/modules/gyan/index.php'); }

$stmt = $pdo->prepare('SELECT * FROM gyan_categories WHERE id = ?');
$stmt->execute([$id]);
$cat = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cat) { flash_set('error','Category not found.'); redirect('admin/modules/gyan/index.php'); }

$adminPageTitle = 'Edit Gyan Category';
$adminPageSub   = 'Update category details';
$errors         = [];

$data = [
    'name'        => $cat['name'],
    'slug'        => $cat['slug'],
    'description' => (string) ($cat['description'] ?? ''),
    'icon_svg'    => (string) ($cat['icon_svg'] ?? ''),
    'sort_order'  => (int) $cat['sort_order'],
    'is_active'   => (int) $cat['is_active'],
];

$iconPresets = [
    '🕉️' => 'Om',
    '📖' => 'Book',
    '🔔' => 'Bell',
    '🪔' => 'Diya',
    '🌸' => 'Flower',
    '🙏' => 'Namaste',
    '📿' => 'Mala',
    '🎵' => 'Music',
    '⭐' => 'Star',
    '🌺' => 'Hibiscus',
];

/* Article count for this category */
$ac = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE category_id = ?');
$ac->execute([$id]);
$articleCount = (int) $ac->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));

    foreach (['name','slug','description','icon_svg'] as $k) {
        $data[$k] = trim((string) post($k, ''));
    }
    $data['sort_order'] = (int) post('sort_order', 0);
    $data['is_active']  = (int) post('is_active', 0);

    if ($data['name'] === '') $errors['name'] = 'Category name is required.';
    if ($data['slug'] === '') $data['slug'] = function_exists('slugify')
        ? slugify($data['name'])
        : trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $data['name'])), '-');

    if (!isset($errors['slug'])) {
        $c = $pdo->prepare('SELECT id FROM gyan_categories WHERE slug = ? AND id <> ?');
        $c->execute([$data['slug'], $id]);
        if ($c->fetch()) $errors['slug'] = 'That slug is already in use.';
    }

    if (!$errors) {
        try {
            $pdo->prepare(
                'UPDATE gyan_categories SET
                   name = ?, slug = ?, description = ?, icon_svg = ?,
                   sort_order = ?, is_active = ?
                 WHERE id = ?'
            )->execute([
                $data['name'],
                $data['slug'],
                $data['description'] ?: null,
                $data['icon_svg'] ?: null,
                $data['sort_order'],
                $data['is_active'],
                $id,
            ]);

            flash_set('success', 'Category updated successfully.');
            redirect('admin/modules/gyan/index.php');

        } catch (PDOException $e) {
            error_log('[gyan.edit] ' . $e->getMessage());
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
          <div><h4>Basic Information</h4><p>Name, slug and description</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">

            <div class="col-md-8">
              <label class="f-label" for="f-name">Name <span class="req">*</span></label>
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

            <div class="col-md-12">
              <label class="f-label" for="f-desc">Description</label>
              <textarea id="f-desc" name="description" class="f-textarea" rows="3"
                        maxlength="300"><?= e($data['description']) ?></textarea>
            </div>

          </div>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-palette"></i></div>
          <div><h4>Icon</h4><p>Emoji or SVG code</p></div>
        </div>
        <div class="form-card-body">

          <label class="f-label">Icon</label>
          <input type="text" id="f-icon" name="icon_svg" value="<?= e($data['icon_svg']) ?>"
                 class="f-input" placeholder="🕉️  or  &lt;svg&gt;…&lt;/svg&gt;">

          <div class="f-hint" style="margin-top:10px">
            <i class="bi bi-lightbulb"></i> Click a preset:
          </div>

          <div id="iconPresets" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
            <?php foreach ($iconPresets as $emoji => $label): ?>
              <button type="button" class="icon-preset"
                      data-icon="<?= e($emoji) ?>"
                      title="<?= e($label) ?>"
                      style="width:44px;height:44px;border-radius:10px;border:1px solid #E5E7EB;
                             background:#fff;cursor:pointer;font-size:22px;
                             display:grid;place-items:center;transition:all .15s">
                <?= $emoji ?>
              </button>
            <?php endforeach; ?>
          </div>

          <div id="iconPreview" style="margin-top:14px;padding:14px 18px;background:#FAF6EF;
               border-radius:10px;display:inline-flex;align-items:center;gap:10px;font-size:13.5px">
            <span style="font-size:22px" id="iconPreviewValue"><?= $data['icon_svg'] ?: '—' ?></span>
            <span style="color:#6B7280">Icon preview</span>
          </div>

        </div>
      </div>

    </div>

    <div class="form-side">
      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-sliders"></i></div>
          <div><h4>Display</h4><p>Ordering &amp; visibility</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label" for="f-sort">Sort Order</label>
          <input type="number" id="f-sort" name="sort_order"
                 value="<?= (int) $data['sort_order'] ?>" class="f-input">

          <label class="f-toggle" for="f-act" style="margin-top:14px">
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
        <a href="<?= url('admin/modules/gyan/index.php') ?>" class="form-btn form-btn-cancel">Cancel</a>

        <div class="meta">
          <?php if ($articleCount > 0): ?>
            Referenced by <b><?= $articleCount ?></b> article(s)
          <?php else: ?>
            No articles — safe to delete
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</form>

<script>
(function () {
  'use strict';
  var input   = document.getElementById('f-icon');
  var preview = document.getElementById('iconPreviewValue');

  function updatePreview() {
    var v = (input.value || '').trim();
    preview.textContent = v || '—';
  }

  if (input) {
    input.addEventListener('input', updatePreview);

    document.querySelectorAll('.icon-preset').forEach(function (btn) {
      btn.addEventListener('click', function () {
        input.value = btn.dataset.icon || '';
        updatePreview();
      });

      btn.addEventListener('mouseenter', function () {
        btn.style.transform = 'translateY(-2px)';
        btn.style.borderColor = '#B76146';
      });
      btn.addEventListener('mouseleave', function () {
        btn.style.transform = '';
        btn.style.borderColor = '#E5E7EB';
      });
    });
  }
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>