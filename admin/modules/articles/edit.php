<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* ==================================================================
 |  Inline image helpers
 * ================================================================== */
if (!defined('ASSETS_PATH')) {
    foreach ([
        dirname(__DIR__, 3) . '/assets',
        dirname(__DIR__, 2) . '/assets',
    ] as $__p) {
        if ($__p && is_dir($__p)) { define('ASSETS_PATH', $__p); break; }
    }
}

if (!function_exists('art_img_url')) {
    function art_img_url(?string $s): string {
        $s = trim((string) $s);
        if ($s === '') return '';
        if (preg_match('#^https?://#i', $s)) return $s;
        $s = ltrim($s, '/');
        if (strpos($s, 'assets/img/') === 0) $s = substr($s, strlen('assets/img/'));
        $m = 'assets/img/';
        $pos = strpos($s, $m);
        if ($pos !== false) $s = substr($s, $pos + strlen($m));
        return 'https://divyaaastha.xinkpayroll.co.in/assets/img/' . ltrim($s, '/');
    }
}

if (!function_exists('art_admin_upload')) {
    function art_admin_upload(string $field, string $sub = 'articles', ?string $ex = null): ?string
    {
        if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? 4) === 4) return $ex;
        $f = $_FILES[$field];
        if ($f['error'] !== UPLOAD_ERR_OK) { flash_set('error','Upload failed.'); return $ex; }
        if ($f['size'] > 3*1024*1024) { flash_set('error','Max 3 MB.'); return $ex; }

        $mime = null;
        if (function_exists('finfo_open')) {
            $fi = @finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) { $mime = @finfo_file($fi, $f['tmp_name']); @finfo_close($fi); }
        }
        if (!$mime && function_exists('mime_content_type')) $mime = @mime_content_type($f['tmp_name']);
        if (!$mime) { $i=@getimagesize($f['tmp_name']); if($i&&!empty($i['mime'])) $mime=$i['mime']; }

        $ok = ['image/jpeg'=>'jpg','image/jpg'=>'jpg','image/pjpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
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

$id = (int) get('id', 0);
if ($id <= 0) { flash_set('error','Invalid ID.'); redirect('admin/modules/articles/index.php'); }

$stmt = $pdo->prepare('SELECT * FROM articles WHERE id = ?');
$stmt->execute([$id]);
$article = $stmt->fetch();
if (!$article) { flash_set('error','Article not found.'); redirect('admin/modules/articles/index.php'); }

$adminPageTitle = 'Edit Article';
$adminPageSub   = 'Update article details';
$errors         = [];
$oldImageUrl    = $article['image_url'];

$data = [
    'title'        => $article['title'],
    'slug'         => $article['slug'],
    'category_id'  => (int) ($article['category_id'] ?? 0),
    'excerpt'      => (string) ($article['excerpt'] ?? ''),
    'body'         => (string) ($article['body'] ?? ''),
    'image_url'    => (string) ($article['image_url'] ?? ''),
    'read_minutes' => (int) $article['read_minutes'],
    'is_trending'  => (int) $article['is_trending'],
    'is_featured'  => (int) $article['is_featured'],
    'published_at' => $article['published_at'] ?: date('Y-m-d H:i:s'),
    'is_active'    => (int) $article['is_active'],
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

    $newPath = null;
    if (!empty($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? 4) !== 4) {
        try {
            $newPath = art_admin_upload('image_file', 'articles', $oldImageUrl);
            if ($newPath && $newPath !== $oldImageUrl) {
                $data['image_url'] = $newPath;
            }
        } catch (Throwable $e) {
            $errors['_'] = 'Upload failed: ' . $e->getMessage();
        }
    }

    if (post('remove_image')) { $data['image_url'] = ''; }
    if (empty(post('remove_image')) && empty($data['image_url']) && $oldImageUrl && !$newPath) {
        $data['image_url'] = $oldImageUrl;
    }

    if ($data['title'] === '') $errors['title'] = 'Title is required.';
    if ($data['slug'] === '')  $data['slug'] = slugify($data['title']);

    if (!isset($errors['slug'])) {
        $c = $pdo->prepare('SELECT id FROM articles WHERE slug = ? AND id <> ?');
        $c->execute([$data['slug'], $id]);
        if ($c->fetch()) $errors['slug'] = 'Slug already in use.';
    }

    if (!$errors) {
        try {
            $pdo->prepare(
                'UPDATE articles SET
                   category_id = ?, title = ?, slug = ?, excerpt = ?, body = ?,
                   image_url = ?, read_minutes = ?, is_trending = ?,
                   is_featured = ?, published_at = ?, is_active = ?
                 WHERE id = ?'
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
                $id,
            ]);
            flash_set('success','Article updated.');
            redirect('admin/modules/articles/index.php');
        } catch (PDOException $e) {
            error_log('[articles.edit] ' . $e->getMessage());
            $errors['_'] = 'Could not save: ' . $e->getMessage();
        }
    }
}

require dirname(__DIR__, 2) . '/includes/header.php';
$previewUrl = art_img_url($data['image_url']);
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
                     class="f-input <?= isset($errors['slug']) ? 'is-invalid' : '' ?>">
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
          <div><h4>Featured Image</h4><p>Replace or remove</p></div>
        </div>
        <div class="form-card-body">
          <div class="f-upload" id="uploadBox">
            <input type="file" id="f-file" name="image_file"
                   accept="image/jpeg,image/png,image/webp,image/gif">
            <label for="f-file" class="f-upload-btn">
              <i class="bi bi-cloud-arrow-up"></i> Choose File
            </label>
            <div class="f-upload-info" id="fileName">No file chosen</div>
          </div>
          <div class="f-imgprev" id="imgPreview" style="margin-top:16px">
            <?php if ($previewUrl): ?>
              <img src="<?= e($previewUrl) ?>" alt="preview">
            <?php else: ?>
              <div class="ph"><i class="bi bi-image"></i>Preview appears here</div>
            <?php endif; ?>
          </div>
          <?php if ($previewUrl): ?>
            <label class="f-toggle" for="f-remove" style="margin-top:12px">
              <input type="checkbox" id="f-remove" name="remove_image" value="1">
              <span class="txt"><b>Remove current image</b><span>Deletes the file</span></span>
            </label>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-text-paragraph"></i></div>
          <div><h4>Content</h4><p>Excerpt + full body</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label">Excerpt</label>
          <textarea name="excerpt" class="f-textarea" rows="3" maxlength="500"><?= e($data['excerpt']) ?></textarea>
          <div style="height:16px"></div>
          <label class="f-label">Full Body</label>
          <textarea name="body" class="f-textarea" rows="12"><?= e($data['body']) ?></textarea>
        </div>
      </div>

    </div>

    <div class="form-side">
      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-star"></i></div>
          <div><h4>Visibility</h4><p>Flags</p></div>
        </div>
        <div class="form-card-body" style="display:flex;flex-direction:column;gap:10px">
          <label class="f-toggle" for="f-feat">
            <input type="checkbox" id="f-feat" name="is_featured" value="1"
                   <?= $data['is_featured'] ? 'checked' : '' ?>>
            <span class="txt"><b>Featured</b><span>Homepage highlight</span></span>
          </label>
          <label class="f-toggle" for="f-trend">
            <input type="checkbox" id="f-trend" name="is_trending" value="1"
                   <?= $data['is_trending'] ? 'checked' : '' ?>>
            <span class="txt"><b>Trending</b><span>Trending section</span></span>
          </label>
          <label class="f-toggle" for="f-act">
            <input type="checkbox" id="f-act" name="is_active" value="1"
                   <?= $data['is_active'] ? 'checked' : '' ?>>
            <span class="txt"><b>Active</b><span>Visible on site</span></span>
          </label>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="form-btn form-btn-save">
          <i class="bi bi-check2-circle"></i> Save Changes
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
  var removeBox = document.getElementById('f-remove');
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
    if (removeBox) removeBox.checked = false;
    fileName.innerHTML = f.name.replace(/[<>&"]/g, '');
    var r = new FileReader();
    r.onload = function (e) { show(e.target.result); };
    r.readAsDataURL(f);
  });

  if (removeBox) removeBox.addEventListener('change', function () {
    if (removeBox.checked) { fileInput.value = ''; fileName.innerHTML = 'No file chosen'; show(''); }
  });
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>