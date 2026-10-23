<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* ==================================================================
 |  Inline image upload helpers
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

if (!function_exists('art_admin_upload')) {
    function art_admin_upload(string $field, string $sub = 'articles', ?string $ex = null): ?string
    {
        if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? 4) === 4) return $ex;
        $f = $_FILES[$field];

        if ($f['error'] !== UPLOAD_ERR_OK) {
            flash_set('error', 'Upload failed (code ' . (int) $f['error'] . ').');
            return $ex;
        }
        if ($f['size'] > 3 * 1024 * 1024) {
            flash_set('error', 'Image must be under 3 MB.');
            return $ex;
        }

        $mime = null;
        if (function_exists('finfo_open')) {
            $fi = @finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) { $mime = @finfo_file($fi, $f['tmp_name']); @finfo_close($fi); }
        }
        if (!$mime && function_exists('mime_content_type')) $mime = @mime_content_type($f['tmp_name']);
        if (!$mime) {
            $i = @getimagesize($f['tmp_name']);
            if ($i && !empty($i['mime'])) $mime = $i['mime'];
        }

        $ok = ['image/jpeg'=>'jpg','image/jpg'=>'jpg','image/pjpeg'=>'jpg',
               'image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];

        if (!$mime || !isset($ok[$mime])) {
            flash_set('error', 'Only JPG, PNG, WEBP or GIF allowed.');
            return $ex;
        }
        if (@getimagesize($f['tmp_name']) === false) {
            flash_set('error', 'File is not a valid image.');
            return $ex;
        }
        if (!defined('ASSETS_PATH')) return $ex;

        $sub = trim($sub, '/');
        $dir = ASSETS_PATH . '/img' . ($sub ? '/' . $sub : '');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            flash_set('error', 'Cannot create folder: ' . $dir);
            return $ex;
        }
        if (!is_writable($dir)) {
            flash_set('error', 'Folder not writable: ' . $dir);
            return $ex;
        }

        $ext  = $ok[$mime];
        $name = date('Y-m-d') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $dir . '/' . $name;

        if (!@move_uploaded_file($f['tmp_name'], $dest)) {
            flash_set('error', 'Could not save the file.');
            return $ex;
        }
        @chmod($dest, 0644);

        return $sub . '/' . $name;
    }
}

$adminPageTitle = 'Add Article';
$adminPageSub   = 'Write a new Dharmik Gyan article';
$errors         = [];

$data = [
    'title'        => '', 'slug' => '', 'category_id' => 0,
    'excerpt'      => '', 'body' => '', 'image_url' => '',
    'read_minutes' => 5, 'is_trending' => 0, 'is_featured' => 0,
    'published_at' => date('Y-m-d\TH:i'),
    'is_active'    => 1,
];

$categories = $pdo->query('SELECT id, name FROM article_categories ORDER BY sort_order ASC, name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));

    foreach (['title','slug','excerpt','body','image_url'] as $k) {
        $data[$k] = trim((string) post($k, ''));
    }
    $data['category_id']  = (int) post('category_id', 0);
    $data['read_minutes'] = max(1, (int) post('read_minutes', 5));
    $data['is_trending']  = (int) post('is_trending', 0);
    $data['is_featured']  = (int) post('is_featured', 0);
    $data['is_active']    = (int) post('is_active', 0);

    $pub = trim((string) post('published_at', ''));
    $data['published_at'] = $pub !== '' ? date('Y-m-d H:i:s', strtotime($pub)) : date('Y-m-d H:i:s');

    /* Image upload */
    if (!empty($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? 4) !== 4) {
        try {
            $new = art_admin_upload('image_file', 'articles', null);
            if ($new) $data['image_url'] = $new;
        } catch (Throwable $e) {
            error_log('[articles.create:upload] ' . $e->getMessage());
            $errors['_'] = 'Image upload failed: ' . $e->getMessage();
        }
    }

    /* Validation */
    if ($data['title'] === '') $errors['title'] = 'Title is required.';
    if ($data['slug'] === '')  $data['slug'] = slugify($data['title']);

    if (!isset($errors['slug'])) {
        $c = $pdo->prepare('SELECT id FROM articles WHERE slug = ?');
        $c->execute([$data['slug']]);
        if ($c->fetch()) $errors['slug'] = 'That slug is already in use.';
    }
    if ($data['category_id'] > 0) {
        $t = $pdo->prepare('SELECT id FROM article_categories WHERE id = ?');
        $t->execute([$data['category_id']]);
        if (!$t->fetch()) { $errors['category_id'] = 'Category not found.'; $data['category_id'] = 0; }
    }

    if (!$errors) {
        try {
            $pdo->prepare(
                'INSERT INTO articles
                   (category_id, title, slug, excerpt, body, image_url, read_minutes,
                    is_trending, is_featured, published_at, is_active)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $data['category_id'] ?: null,
                $data['title'],
                $data['slug'],
                $data['excerpt'] ?: null,
                $data['body']    ?: null,
                $data['image_url'] ?: null,
                $data['read_minutes'],
                $data['is_trending'],
                $data['is_featured'],
                $data['published_at'],
                $data['is_active'],
            ]);
            flash_set('success', 'Article created successfully.');
            redirect('admin/modules/articles/index.php');
        } catch (PDOException $e) {
            error_log('[articles.create] ' . $e->getMessage());
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
          <div><h4>Article Details</h4><p>Title, slug and category</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="f-label">Title <span class="req">*</span></label>
              <input type="text" name="title" value="<?= e($data['title']) ?>"
                     class="f-input <?= isset($errors['title']) ? 'is-invalid' : '' ?>" required autofocus>
              <?php if (isset($errors['title'])): ?><div class="f-err"><?= e($errors['title']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-4">
              <label class="f-label">Slug</label>
              <input type="text" name="slug" value="<?= e($data['slug']) ?>"
                     class="f-input <?= isset($errors['slug']) ? 'is-invalid' : '' ?>"
                     placeholder="auto-generate">
              <?php if (isset($errors['slug'])): ?><div class="f-err"><?= e($errors['slug']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
              <label class="f-label">Category</label>
              <select name="category_id" class="f-input">
                <option value="0">— No category —</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= (int) $c['id'] ?>" <?= $data['category_id'] == $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="f-label">Read Time (mins)</label>
              <input type="number" min="1" name="read_minutes"
                     value="<?= (int) $data['read_minutes'] ?>" class="f-input">
            </div>
            <div class="col-md-3">
              <label class="f-label">Publish Date</label>
              <input type="datetime-local" name="published_at"
                     value="<?= e(date('Y-m-d\TH:i', strtotime($data['published_at']))) ?>"
                     class="f-input">
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
          <label class="f-label">Upload Image</label>
          <div class="f-upload" id="uploadBox">
            <input type="file" id="f-file" name="image_file"
                   accept="image/jpeg,image/png,image/webp,image/gif">
            <label for="f-file" class="f-upload-btn">
              <i class="bi bi-cloud-arrow-up"></i> Choose File
            </label>
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
          <div><h4>Content</h4><p>Excerpt + full body</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label">Excerpt</label>
          <textarea name="excerpt" class="f-textarea" rows="3" maxlength="500"
                    placeholder="Short summary shown on listing cards…"><?= e($data['excerpt']) ?></textarea>
          <div style="height:16px"></div>
          <label class="f-label">Full Body</label>
          <textarea name="body" class="f-textarea" rows="12"
                    placeholder="Write the full article content…"><?= e($data['body']) ?></textarea>
        </div>
      </div>

    </div>

    <div class="form-side">

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-star"></i></div>
          <div><h4>Visibility</h4><p>Featured &amp; trending flags</p></div>
        </div>
        <div class="form-card-body" style="display:flex;flex-direction:column;gap:10px">
          <label class="f-toggle" for="f-feat">
            <input type="checkbox" id="f-feat" name="is_featured" value="1"
                   <?= $data['is_featured'] ? 'checked' : '' ?>>
            <span class="txt"><b>Featured</b><span>Highlight on homepage</span></span>
          </label>
          <label class="f-toggle" for="f-trend">
            <input type="checkbox" id="f-trend" name="is_trending" value="1"
                   <?= $data['is_trending'] ? 'checked' : '' ?>>
            <span class="txt"><b>Trending</b><span>Show in trending section</span></span>
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
          <i class="bi bi-check2-circle"></i> Create Article
        </button>
        <a href="<?= url('admin/modules/articles/index.php') ?>" class="form-btn form-btn-cancel">Cancel</a>
      </div>

    </div>
  </div>
</form>

<script>
(function () {
  var fileInput = document.getElementById('f-file');
  var fileName  = document.getElementById('fileName');
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