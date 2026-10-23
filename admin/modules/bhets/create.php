<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* ==================================================================
 |  INLINE IMAGE HELPERS — guaranteed to work
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

if (!function_exists('bhet_admin_delete_image')) {
    function bhet_admin_delete_image(?string $pathOrUrl): void {
        if (!$pathOrUrl) return;
        if (preg_match('#^https?://#i', $pathOrUrl)) {
            $prefix = (defined('APP_URL') ? APP_URL : '') . '/assets/img/';
            if (strpos($pathOrUrl, $prefix) !== 0) return;
            $pathOrUrl = substr($pathOrUrl, strlen((defined('APP_URL') ? APP_URL : '') . '/assets/'));
        }
        $rel = $pathOrUrl;
        if (strpos($rel, '/assets/img/') === 0) $rel = substr($rel, strlen('/assets/'));
        elseif (strpos($rel, 'assets/img/') === 0) $rel = substr($rel, strlen('assets/'));
        elseif (strpos($rel, 'img/') === 0) $rel = substr($rel, 4);

        $rel = ltrim(str_replace(['..', '\\'], ['', '/'], $rel), '/');
        if (!defined('ASSETS_PATH')) return;
        $path = ASSETS_PATH . '/img/' . $rel;
        $real = realpath($path);
        $root = realpath(ASSETS_PATH . '/img');
        if ($real && $root && strpos($real, $root) === 0 && is_file($real)) {
            @unlink($real);
        }
    }
}

if (!function_exists('bhet_admin_upload_image')) {
    function bhet_admin_upload_image(string $field, string $subfolder, ?string $existing = null): ?string
    {
        if (empty($_FILES[$field]) ||
            ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $existing;
        }

        $file = $_FILES[$field];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            if (function_exists('flash_set')) flash_set('error', 'Upload failed (code ' . (int) $file['error'] . ').');
            return $existing;
        }
        if ($file['size'] > 3 * 1024 * 1024) {
            if (function_exists('flash_set')) flash_set('error', 'Image must be under 3 MB.');
            return $existing;
        }

        $mime = null;
        if (function_exists('finfo_open')) {
            $fi = @finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) { $mime = @finfo_file($fi, $file['tmp_name']); @finfo_close($fi); }
        }
        if (!$mime && function_exists('mime_content_type')) {
            $mime = @mime_content_type($file['tmp_name']);
        }
        if (!$mime) {
            $info = @getimagesize($file['tmp_name']);
            if ($info && !empty($info['mime'])) $mime = $info['mime'];
        }

        $allowed = [
            'image/jpeg'  => 'jpg',
            'image/jpg'   => 'jpg',
            'image/pjpeg' => 'jpg',
            'image/png'   => 'png',
            'image/webp'  => 'webp',
        ];

        if (!$mime || !isset($allowed[$mime])) {
            if (function_exists('flash_set')) flash_set('error', 'Only JPG, PNG or WEBP images are allowed.');
            return $existing;
        }
        if (@getimagesize($file['tmp_name']) === false) {
            if (function_exists('flash_set')) flash_set('error', 'File is not a valid image.');
            return $existing;
        }

        if (!defined('ASSETS_PATH')) {
            if (function_exists('flash_set')) flash_set('error', 'ASSETS_PATH is not defined.');
            return $existing;
        }

        $subfolder = trim($subfolder, '/');
        $dir = ASSETS_PATH . '/img' . ($subfolder !== '' ? '/' . $subfolder : '');

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            if (function_exists('flash_set')) flash_set('error', 'Cannot create upload folder: ' . $dir);
            return $existing;
        }
        if (!is_writable($dir)) {
            if (function_exists('flash_set')) flash_set('error', 'Upload folder is not writable: ' . $dir);
            return $existing;
        }

        $ext  = $allowed[$mime];
        $name = date('Y-m-d') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $dir . '/' . $name;

        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            if (function_exists('flash_set')) flash_set('error', 'Could not save the file. Please try again.');
            return $existing;
        }
        @chmod($dest, 0644);

        if ($existing && $existing !== $subfolder . '/' . $name) {
            bhet_admin_delete_image($existing);
        }
        return $subfolder . '/' . $name;
    }
}

/* ==================================================================
 |  Type resolver (auto-create)
 * ================================================================== */
if (!function_exists('bhet_resolve_type_id')) {
    function bhet_resolve_type_id(PDO $pdo, string $name): ?int {
        $name = trim($name);
        if ($name === '') return null;

        $s = $pdo->prepare('SELECT id FROM bhet_types WHERE LOWER(name) = LOWER(?) LIMIT 1');
        $s->execute([$name]);
        $id = $s->fetchColumn();
        if ($id) return (int) $id;

        $slug = function_exists('slugify')
            ? slugify($name)
            : trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)), '-');
        if ($slug === '') $slug = 'seva-' . bin2hex(random_bytes(3));

        $base = $slug; $i = 2;
        while (true) {
            $c = $pdo->prepare('SELECT id FROM bhet_types WHERE slug = ?');
            $c->execute([$slug]);
            if (!$c->fetch()) break;
            $slug = $base . '-' . $i++;
        }

        $pdo->prepare(
            'INSERT INTO bhet_types (name, slug, sort_order, is_active)
             VALUES (?, ?, 0, 1)'
        )->execute([$name, $slug]);

        return (int) $pdo->lastInsertId();
    }
}

/* ==================================================================
 |  Page setup
 * ================================================================== */
$adminPageTitle = 'Add Bhet';
$adminPageSub   = 'Create a new bhet / daan seva';
$errors         = [];

$data = [
    'name' => '', 'slug' => '', 'bhet_type_name' => '',
    'temple_id' => 0, 'short_desc' => '', 'description' => '',
    'price' => 0, 'image_url' => '', 'is_featured' => 0,
    'sort_order' => 0, 'is_active' => 1,
];

$existingTypes = $pdo->query(
    'SELECT id, name FROM bhet_types ORDER BY sort_order ASC, name ASC'
)->fetchAll();

$temples = $pdo->query('SELECT id, name FROM temples ORDER BY name ASC')->fetchAll();

/* ==================================================================
 |  POST handler
 * ================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));

    $data['name']           = trim((string) post('name', ''));
    $data['slug']           = trim((string) post('slug', ''));
    $data['bhet_type_name'] = trim((string) post('bhet_type_name', ''));
    $data['short_desc']     = trim((string) post('short_desc', ''));
    $data['description']    = trim((string) post('description', ''));
    $data['price']          = (float) post('price', 0);
    $data['temple_id']      = (int) post('temple_id', 0);
    $data['is_featured']    = (int) post('is_featured', 0);
    $data['sort_order']     = (int) post('sort_order', 0);
    $data['is_active']      = (int) post('is_active', 0);

    /* Image upload */
    if (!empty($_FILES['image_file']) &&
        ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $newPath = bhet_admin_upload_image('image_file', 'bhet', null);
            if ($newPath) $data['image_url'] = $newPath;
        } catch (Throwable $e) {
            error_log('[bhets.create:image_upload] ' . $e->getMessage());
            $errors['_'] = 'Image upload failed: ' . $e->getMessage();
        }
    }

    /* Validation */
    if ($data['name'] === '') $errors['name']  = 'Bhet name is required.';
    if ($data['price'] <= 0)  $errors['price'] = 'Price must be greater than 0.';
    if ($data['slug'] === '') $data['slug'] = function_exists('slugify')
        ? slugify($data['name'])
        : trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $data['name'])), '-');

    if (!isset($errors['slug'])) {
        $c = $pdo->prepare('SELECT id FROM bhets WHERE slug = ?');
        $c->execute([$data['slug']]);
        if ($c->fetch()) $errors['slug'] = 'That slug is already in use.';
    }

    if ($data['temple_id'] > 0) {
        $t = $pdo->prepare('SELECT id FROM temples WHERE id = ?');
        $t->execute([$data['temple_id']]);
        if (!$t->fetch()) {
            $errors['temple_id'] = 'Selected temple not found.';
            $data['temple_id']   = 0;
        }
    }

    /* INSERT */
    if (!$errors) {
        try {
            $typeId = bhet_resolve_type_id($pdo, $data['bhet_type_name']);

            $pdo->prepare(
                'INSERT INTO bhets
                   (bhet_type_id, temple_id, name, slug, short_desc, description,
                    price, image_url, is_featured, sort_order, is_active)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $typeId,
                $data['temple_id'] ?: null,
                $data['name'],
                $data['slug'],
                $data['short_desc']  ?: null,
                $data['description'] ?: null,
                $data['price'],
                $data['image_url']   ?: null,
                $data['is_featured'],
                $data['sort_order'],
                $data['is_active'],
            ]);

            flash_set('success', 'Bhet created successfully.');
            redirect('admin/modules/bhets/index.php');

        } catch (PDOException $e) {
            error_log('[bhets.create] ' . $e->getMessage());
            $errors['_'] = 'Could not create bhet: ' . $e->getMessage();
        }
    }
}

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<?php if (!empty($errors['_'])): ?>
  <div class="form-alert">
    <i class="bi bi-exclamation-triangle-fill"></i> <?= e($errors['_']) ?>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" novalidate autocomplete="off">
  <?= csrf_field() ?>

  <div class="form-page">
    <div class="form-main">

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-info-circle"></i></div>
          <div><h4>Basic Information</h4><p>Name, slug, seva type and temple</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">

            <div class="col-md-8">
              <label class="f-label" for="f-name">Bhet Name <span class="req">*</span></label>
              <input type="text" id="f-name" name="name" value="<?= e($data['name']) ?>"
                     class="f-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                     placeholder="e.g. Deergh Vishnu Mandir Vishesh Daan Seva" required autofocus>
              <?php if (isset($errors['name'])): ?>
                <div class="f-err"><i class="bi bi-exclamation-circle"></i> <?= e($errors['name']) ?></div>
              <?php endif; ?>
            </div>

            <div class="col-md-4">
              <label class="f-label" for="f-slug">Slug</label>
              <input type="text" id="f-slug" name="slug" value="<?= e($data['slug']) ?>"
                     class="f-input <?= isset($errors['slug']) ? 'is-invalid' : '' ?>"
                     placeholder="auto-generate">
              <?php if (isset($errors['slug'])): ?>
                <div class="f-err"><i class="bi bi-exclamation-circle"></i> <?= e($errors['slug']) ?></div>
              <?php else: ?>
                <div class="f-hint">Leave empty to auto-generate.</div>
              <?php endif; ?>
            </div>

            <div class="col-md-6">
              <label class="f-label" for="f-type">Seva Type</label>
              <input type="text" id="f-type" name="bhet_type_name"
                     value="<?= e($data['bhet_type_name']) ?>"
                     class="f-input" list="bhetTypesList"
                     placeholder="Pick or type a new one…"
                     autocomplete="off">
              <datalist id="bhetTypesList">
                <?php foreach ($existingTypes as $t): ?>
                  <option value="<?= e($t['name']) ?>">
                <?php endforeach; ?>
              </datalist>
              <div class="f-hint">
                <i class="bi bi-lightbulb"></i>
                Existing type select করুন অথবা নতুন type লিখুন — অটো যোগ হবে।
              </div>
            </div>

            <div class="col-md-6">
              <label class="f-label" for="f-temple">Temple</label>
              <select id="f-temple" name="temple_id" class="f-input <?= isset($errors['temple_id']) ? 'is-invalid' : '' ?>">
                <option value="0">— No specific temple —</option>
                <?php foreach ($temples as $t): ?>
                  <option value="<?= (int) $t['id'] ?>"
                    <?= $data['temple_id'] == $t['id'] ? 'selected' : '' ?>>
                    <?= e($t['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if (isset($errors['temple_id'])): ?>
                <div class="f-err"><i class="bi bi-exclamation-circle"></i> <?= e($errors['temple_id']) ?></div>
              <?php endif; ?>
            </div>

            <div class="col-md-4">
              <label class="f-label" for="f-price">Price (₹) <span class="req">*</span></label>
              <input type="number" step="0.01" min="0" id="f-price" name="price"
                     value="<?= e($data['price']) ?>"
                     class="f-input <?= isset($errors['price']) ? 'is-invalid' : '' ?>">
              <?php if (isset($errors['price'])): ?>
                <div class="f-err"><i class="bi bi-exclamation-circle"></i> <?= e($errors['price']) ?></div>
              <?php endif; ?>
            </div>

          </div>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-image"></i></div>
          <div><h4>Featured Image</h4><p>JPG · PNG · WEBP — max 3 MB</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label">Upload Image</label>
          <div class="f-upload" id="uploadBox">
            <input type="file" id="f-file" name="image_file"
                   accept="image/jpeg,image/png,image/webp">
            <label for="f-file" class="f-upload-btn">
              <i class="bi bi-cloud-arrow-up"></i> Choose File
            </label>
            <div class="f-upload-info" id="fileName">No file chosen</div>
          </div>

          <div class="f-imgprev" id="imgPreview">
            <?php if ($data['image_url']): ?>
              <img src="<?= e('https://divyaaastha.xinkpayroll.co.in/assets/img/' . ltrim($data['image_url'], '/')) ?>"
                   alt="preview">
            <?php else: ?>
              <div class="ph"><i class="bi bi-image"></i>Preview appears here</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-text-paragraph"></i></div>
          <div><h4>Descriptions</h4><p>Short summary + full details</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label" for="f-short">Short Description</label>
          <input type="text" id="f-short" name="short_desc" value="<?= e($data['short_desc']) ?>"
                 class="f-input" maxlength="400"
                 placeholder="One line summary shown on listing cards">

          <div style="height:16px"></div>

          <label class="f-label" for="f-desc">Full Description</label>
          <textarea id="f-desc" name="description" class="f-textarea"
                    placeholder="Significance, what's included, how the bhet is used…"><?= e($data['description']) ?></textarea>
        </div>
      </div>

    </div>

    <div class="form-side">

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-sliders"></i></div>
          <div><h4>Display</h4><p>Ordering on listing pages</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label" for="f-sort">Sort Order</label>
          <input type="number" id="f-sort" name="sort_order"
                 value="<?= (int) $data['sort_order'] ?>" class="f-input">
          <div class="f-hint">Lower numbers appear first.</div>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-star"></i></div>
          <div><h4>Placement</h4><p>Highlight and visibility</p></div>
        </div>
        <div class="form-card-body" style="display:flex;flex-direction:column;gap:10px">
          <label class="f-toggle" for="f-feat">
            <input type="checkbox" id="f-feat" name="is_featured" value="1"
                   <?= $data['is_featured'] ? 'checked' : '' ?>>
            <span class="txt"><b>Featured</b><span>Highlight on bhet listing</span></span>
          </label>

          <label class="f-toggle" for="f-act">
            <input type="checkbox" id="f-act" name="is_active" value="1"
                   <?= $data['is_active'] ? 'checked' : '' ?>>
            <span class="txt"><b>Active</b><span>Visible on the site</span></span>
          </label>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="form-btn form-btn-save">
          <i class="bi bi-check2-circle"></i> Create Bhet
        </button>
        <a href="<?= url('admin/modules/bhets/index.php') ?>" class="form-btn form-btn-cancel">Cancel</a>
      </div>

    </div>
  </div>
</form>

<script>
(function () {
  var fileInput  = document.getElementById('f-file');
  var fileName   = document.getElementById('fileName');
  var imgPreview = document.getElementById('imgPreview');
  var placeholder = '<div class="ph"><i class="bi bi-image"></i>Preview appears here</div>';

  function showPreview(src) {
    if (!src) { imgPreview.innerHTML = placeholder; return; }
    imgPreview.innerHTML = '<div class="ph"><i class="bi bi-arrow-repeat"></i>Loading…</div>';
    var img = new Image();
    img.onload  = function () { imgPreview.innerHTML = ''; imgPreview.appendChild(img); };
    img.onerror = function () {
      imgPreview.innerHTML = '<div class="ph"><i class="bi bi-exclamation-triangle"></i>Invalid image</div>';
    };
    img.src = src;
  }

  if (fileInput) fileInput.addEventListener('change', function () {
    var f = fileInput.files && fileInput.files[0];
    if (!f) { fileName.innerHTML = 'No file chosen'; return; }
    fileName.innerHTML = '<span class="name">' + f.name.replace(/[<>&"]/g,'') + '</span>';
    var r = new FileReader();
    r.onload = function (e) { showPreview(e.target.result); };
    r.readAsDataURL(f);
  });
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>