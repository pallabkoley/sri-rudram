<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* ==================================================================
 |  Inline image upload helper
 * ================================================================== */
if (!defined('ASSETS_PATH')) {
    foreach ([
        dirname(__DIR__, 3) . '/assets',
        dirname(__DIR__, 2) . '/assets',
        ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets',
    ] as $__p) {
        if ($__p && is_dir($__p)) { define('ASSETS_PATH', $__p); break; }
    }
}

if (!function_exists('pj_admin_upload')) {
    function pj_admin_upload(string $field, string $sub = 'pujas', ?string $ex = null): ?string {
        if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? 4) === 4) return $ex;
        $f = $_FILES[$field];
        if ($f['error'] !== UPLOAD_ERR_OK) { flash_set('error','Upload failed.'); return $ex; }
        if ($f['size'] > 3 * 1024 * 1024)  { flash_set('error','Max 3 MB.'); return $ex; }

        $mime = null;
        if (function_exists('finfo_open')) {
            $fi = @finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) { $mime = @finfo_file($fi, $f['tmp_name']); @finfo_close($fi); }
        }
        if (!$mime && function_exists('mime_content_type')) $mime = @mime_content_type($f['tmp_name']);
        if (!$mime) { $i = @getimagesize($f['tmp_name']); if ($i && !empty($i['mime'])) $mime = $i['mime']; }

        $ok = ['image/jpeg'=>'jpg','image/jpg'=>'jpg','image/pjpeg'=>'jpg',
               'image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
        if (!$mime || !isset($ok[$mime])) { flash_set('error','Only JPG/PNG/WEBP/GIF.'); return $ex; }
        if (@getimagesize($f['tmp_name']) === false) { flash_set('error','Invalid image.'); return $ex; }
        if (!defined('ASSETS_PATH')) return $ex;

        $sub = trim($sub, '/');
        $dir = ASSETS_PATH . '/img' . ($sub ? '/' . $sub : '');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) { flash_set('error','Cannot create folder.'); return $ex; }
        if (!is_writable($dir)) { flash_set('error','Not writable.'); return $ex; }

        $ext  = $ok[$mime];
        $name = date('Y-m-d') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $dir . '/' . $name;
        if (!@move_uploaded_file($f['tmp_name'], $dest)) { flash_set('error','Could not save.'); return $ex; }
        @chmod($dest, 0644);
        return $sub . '/' . $name;
    }
}

$adminPageTitle = 'Add Puja';
$adminPageSub   = 'Create a new sacred ritual';
$errors         = [];

$data = [
    'name' => '', 'slug' => '', 'temple_id' => 0, 'custom_temple_name' => '',
    'short_desc' => '', 'description' => '',
    'price' => 0, 'duration_mins' => 0, 'badge' => '', 'badge_style' => 'gold',
    'image_url' => '', 'is_popular' => 0, 'sort_order' => 0, 'is_active' => 1,
];

$temples = $pdo->query('SELECT id, name FROM temples ORDER BY name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));

    foreach (['name','slug','short_desc','description','badge','badge_style','custom_temple_name'] as $k) {
        $data[$k] = trim((string) post($k, ''));
    }
    $data['temple_id']     = (int) post('temple_id', 0);
    $data['price']         = (float) post('price', 0);
    $data['duration_mins'] = (int) post('duration_mins', 0);
    $data['is_popular']    = (int) post('is_popular', 0);
    $data['sort_order']    = (int) post('sort_order', 0);
    $data['is_active']     = (int) post('is_active', 0);

    /* Image */
    if (!empty($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? 4) !== 4) {
        try {
            $newPath = pj_admin_upload('image_file', 'pujas', null);
            if ($newPath) $data['image_url'] = $newPath;
        } catch (Throwable $e) {
            error_log('[pujas.create:upload] ' . $e->getMessage());
            $errors['_'] = 'Image upload failed: ' . $e->getMessage();
        }
    }

    /* Validation */
    if ($data['name'] === '') $errors['name']  = 'Puja name is required.';
    if ($data['price'] <= 0)  $errors['price'] = 'Price must be greater than 0.';
    if ($data['slug'] === '') $data['slug'] = function_exists('slugify')
        ? slugify($data['name'])
        : trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $data['name'])), '-');

    if (!isset($errors['slug'])) {
        $c = $pdo->prepare('SELECT id FROM pujas WHERE slug = ?');
        $c->execute([$data['slug']]);
        if ($c->fetch()) $errors['slug'] = 'That slug is already in use.';
    }

    /* Temple resolution */
    $finalTempleId   = null;
    $finalCustomName = null;

    if ($data['temple_id'] === -1) {
        $name = trim($data['custom_temple_name']);
        if ($name === '') {
            $errors['custom_temple_name'] = 'Please enter the custom temple name.';
        } else {
            $finalCustomName = $name;
        }
    } elseif ($data['temple_id'] > 0) {
        $t = $pdo->prepare('SELECT id FROM temples WHERE id = ?');
        $t->execute([$data['temple_id']]);
        if (!$t->fetch()) {
            $errors['temple_id'] = 'Selected temple not found.';
        } else {
            $finalTempleId = $data['temple_id'];
        }
    }

    if (!$errors) {
        try {
            $pdo->prepare(
                'INSERT INTO pujas
                   (temple_id, custom_temple_name, name, slug, short_desc, description,
                    price, duration_mins, badge, badge_style, image_url,
                    is_popular, sort_order, is_active)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $finalTempleId,
                $finalCustomName,
                $data['name'],
                $data['slug'],
                $data['short_desc']  ?: null,
                $data['description'] ?: null,
                $data['price'],
                $data['duration_mins'] ?: null,
                $data['badge'] ?: null,
                $data['badge_style'] ?: 'gold',
                $data['image_url'] ?: null,
                $data['is_popular'],
                $data['sort_order'],
                $data['is_active'],
            ]);

            $newId = (int) $pdo->lastInsertId();
            flash_set('success', 'Puja created! Now add packages, benefits, procedures and FAQs below.');
            redirect('admin/modules/pujas/edit.php?id=' . $newId . '#sub-content');

        } catch (PDOException $e) {
            error_log('[pujas.create] ' . $e->getMessage());
            $errors['_'] = 'Could not create: ' . $e->getMessage();
        }
    }
}

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<?php if (!empty($errors['_'])): ?>
  <div class="form-alert"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($errors['_']) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" novalidate autocomplete="off">
  <?= csrf_field() ?>
  <div class="form-page">
    <div class="form-main">

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-info-circle"></i></div>
          <div><h4>Basic Information</h4><p>Name, slug and temple</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">

            <div class="col-md-8">
              <label class="f-label">Puja Name <span class="req">*</span></label>
              <input type="text" name="name" value="<?= e($data['name']) ?>"
                     class="f-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                     placeholder="e.g. Ganesh Puja" required autofocus>
              <?php if (isset($errors['name'])): ?><div class="f-err"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
              <label class="f-label">Slug</label>
              <input type="text" name="slug" value="<?= e($data['slug']) ?>"
                     class="f-input <?= isset($errors['slug']) ? 'is-invalid' : '' ?>"
                     placeholder="auto-generate">
              <?php if (isset($errors['slug'])): ?><div class="f-err"><?= e($errors['slug']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
              <label class="f-label">Temple</label>
              <select id="f-temple" name="temple_id" class="f-input">
                <option value="0"  <?= $data['temple_id'] === 0  ? 'selected' : '' ?>>— No specific temple —</option>
                <?php foreach ($temples as $t): ?>
                  <option value="<?= (int) $t['id'] ?>" <?= $data['temple_id'] == $t['id'] ? 'selected' : '' ?>>
                    <?= e($t['name']) ?>
                  </option>
                <?php endforeach; ?>
                <option value="-1" <?= $data['temple_id'] === -1 ? 'selected' : '' ?>>＋ Custom Temple (enter below)</option>
              </select>
              <div class="f-hint">Select existing or add a custom name.</div>
            </div>

            <div class="col-md-6" id="customTempleWrap"
                 style="<?= $data['temple_id'] === -1 ? '' : 'display:none' ?>">
              <label class="f-label">Custom Temple Name <span class="req">*</span></label>
              <input type="text" id="f-custom-temple" name="custom_temple_name"
                     value="<?= e($data['custom_temple_name']) ?>"
                     class="f-input <?= isset($errors['custom_temple_name']) ? 'is-invalid' : '' ?>"
                     placeholder="e.g. Deergh Vishnu Mandir">
              <?php if (isset($errors['custom_temple_name'])): ?>
                <div class="f-err"><?= e($errors['custom_temple_name']) ?></div>
              <?php endif; ?>
            </div>

            <div class="col-md-4">
              <label class="f-label">Price (₹) <span class="req">*</span></label>
              <input type="number" step="0.01" min="0" name="price"
                     value="<?= e($data['price']) ?>"
                     class="f-input <?= isset($errors['price']) ? 'is-invalid' : '' ?>">
              <?php if (isset($errors['price'])): ?><div class="f-err"><?= e($errors['price']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
              <label class="f-label">Duration (mins)</label>
              <input type="number" min="0" name="duration_mins"
                     value="<?= e($data['duration_mins']) ?>" class="f-input">
            </div>

            <div class="col-md-4">
              <label class="f-label">Badge Text</label>
              <input type="text" name="badge" value="<?= e($data['badge']) ?>"
                     class="f-input" placeholder="Most Popular…">
            </div>

            <div class="col-md-4">
              <label class="f-label">Badge Style</label>
              <select name="badge_style" class="f-input">
                <?php foreach (['gold','plum','terra','green','blue','amber'] as $s): ?>
                  <option value="<?= $s ?>" <?= $data['badge_style'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

          </div>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-image"></i></div>
          <div><h4>Featured Image</h4><p>JPG · PNG · WEBP · GIF — max 3 MB</p></div>
        </div>
        <div class="form-card-body">
          <div class="f-upload" id="uploadBox">
            <input type="file" id="f-file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif">
            <label for="f-file" class="f-upload-btn"><i class="bi bi-cloud-arrow-up"></i> Choose File</label>
            <div class="f-upload-info" id="fileName">No file chosen</div>
          </div>
          <div class="f-imgprev" id="imgPreview" style="margin-top:16px">
            <div class="ph"><i class="bi bi-image"></i>Preview appears here</div>
          </div>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-text-paragraph"></i></div>
          <div><h4>Descriptions</h4><p>Short summary + full details</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label">Short Description</label>
          <input type="text" name="short_desc" value="<?= e($data['short_desc']) ?>"
                 class="f-input" maxlength="400">
          <div style="height:16px"></div>
          <label class="f-label">Full Description</label>
          <textarea name="description" class="f-textarea" rows="6"><?= e($data['description']) ?></textarea>
        </div>
      </div>

    </div>

    <div class="form-side">
      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-sliders"></i></div>
          <div><h4>Display</h4><p>Ordering</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label">Sort Order</label>
          <input type="number" name="sort_order" value="<?= (int) $data['sort_order'] ?>" class="f-input">
          <div class="f-hint">Lower numbers appear first.</div>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-star"></i></div>
          <div><h4>Placement</h4><p>Highlight and visibility</p></div>
        </div>
        <div class="form-card-body" style="display:flex;flex-direction:column;gap:10px">
          <label class="f-toggle" for="f-pop">
            <input type="checkbox" id="f-pop" name="is_popular" value="1" <?= $data['is_popular'] ? 'checked' : '' ?>>
            <span class="txt"><b>Popular</b><span>Highlight on homepage</span></span>
          </label>
          <label class="f-toggle" for="f-act">
            <input type="checkbox" id="f-act" name="is_active" value="1" <?= $data['is_active'] ? 'checked' : '' ?>>
            <span class="txt"><b>Active</b><span>Visible for booking</span></span>
          </label>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="form-btn form-btn-save"><i class="bi bi-check2-circle"></i> Create Puja</button>
        <a href="<?= url('admin/modules/pujas/index.php') ?>" class="form-btn form-btn-cancel">Cancel</a>
      </div>
    </div>
  </div>
</form>

<script>
(function () {
  var templeSelect = document.getElementById('f-temple');
  var customWrap   = document.getElementById('customTempleWrap');
  var customInput  = document.getElementById('f-custom-temple');

  function toggleCustom() {
    var v = templeSelect.value;
    if (v === '-1') {
      customWrap.style.display = '';
      if (customInput) customInput.setAttribute('required', 'required');
    } else {
      customWrap.style.display = 'none';
      if (customInput) customInput.removeAttribute('required');
    }
  }
  if (templeSelect) { templeSelect.addEventListener('change', toggleCustom); toggleCustom(); }

  var fileInput  = document.getElementById('f-file');
  var fileName   = document.getElementById('fileName');
  var imgPreview = document.getElementById('imgPreview');
  var ph = '<div class="ph"><i class="bi bi-image"></i>Preview appears here</div>';

  function show(src) {
    if (!src) { imgPreview.innerHTML = ph; return; }
    imgPreview.innerHTML = '<div class="ph">Loading…</div>';
    var img = new Image();
    img.onload  = function () { imgPreview.innerHTML = ''; imgPreview.appendChild(img); };
    img.onerror = function () { imgPreview.innerHTML = '<div class="ph">Invalid image</div>'; };
    img.src = src;
  }
  if (fileInput) fileInput.addEventListener('change', function () {
    var f = fileInput.files && fileInput.files[0];
    if (!f) { fileName.innerHTML = 'No file chosen'; return; }
    fileName.innerHTML = f.name.replace(/[<>&"]/g, '');
    var r = new FileReader();
    r.onload = function (e) { show(e.target.result); };
    r.readAsDataURL(f);
  });
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>