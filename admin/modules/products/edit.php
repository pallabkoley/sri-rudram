<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* Inline helpers (same as create.php) */
if (!defined('ASSETS_PATH')) {
    foreach ([dirname(__DIR__, 3) . '/assets', dirname(__DIR__, 2) . '/assets'] as $p) {
        if (is_dir($p)) { define('ASSETS_PATH', $p); break; }
    }
}
if (!function_exists('prod_delete_image')) {
    function prod_delete_image(?string $p): void {
        if (!$p) return;
        if (preg_match('#^https?://#i', $p)) return;
        $rel = $p;
        if (strpos($rel, '/assets/img/') === 0) $rel = substr($rel, strlen('/assets/'));
        elseif (strpos($rel, 'assets/img/') === 0) $rel = substr($rel, strlen('assets/'));
        elseif (strpos($rel, 'img/') === 0) $rel = substr($rel, 4);
        $rel = ltrim(str_replace(['..', '\\'], ['', '/'], $rel), '/');
        if (!defined('ASSETS_PATH')) return;
        $path = ASSETS_PATH . '/img/' . $rel;
        $real = realpath($path); $root = realpath(ASSETS_PATH . '/img');
        if ($real && $root && strpos($real, $root) === 0 && is_file($real)) @unlink($real);
    }
}
if (!function_exists('prod_upload_image')) {
    function prod_upload_image(string $field, string $sub = 'products', ?string $existing = null): ?string {
        if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? 4) === 4) return $existing;
        $f = $_FILES[$field];
        if ($f['error'] !== UPLOAD_ERR_OK) { flash_set('error', 'Upload failed.'); return $existing; }
        if ($f['size'] > 3*1024*1024) { flash_set('error', 'Max 3 MB.'); return $existing; }
        $mime = null;
        if (function_exists('finfo_open')) { $fi=@finfo_open(FILEINFO_MIME_TYPE); if($fi){$mime=@finfo_file($fi,$f['tmp_name']);@finfo_close($fi);} }
        if (!$mime && function_exists('mime_content_type')) $mime = @mime_content_type($f['tmp_name']);
        if (!$mime) { $i=@getimagesize($f['tmp_name']); if($i&&!empty($i['mime'])) $mime=$i['mime']; }
        $ok = ['image/jpeg'=>'jpg','image/jpg'=>'jpg','image/pjpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
        if (!$mime || !isset($ok[$mime])) { flash_set('error', 'Only JPG/PNG/WEBP/GIF.'); return $existing; }
        if (@getimagesize($f['tmp_name']) === false) { flash_set('error', 'Invalid image.'); return $existing; }
        if (!defined('ASSETS_PATH')) return $existing;
        $sub = trim($sub, '/');
        $dir = ASSETS_PATH . '/img' . ($sub ? '/' . $sub : '');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) { flash_set('error','Cannot create folder.'); return $existing; }
        if (!is_writable($dir)) { flash_set('error','Not writable.'); return $existing; }
        $ext = $ok[$mime];
        $name = date('Y-m-d') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (!@move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) { flash_set('error','Could not save.'); return $existing; }
        @chmod($dir . '/' . $name, 0644);
        if ($existing && $existing !== $sub . '/' . $name) prod_delete_image($existing);
        return $sub . '/' . $name;
    }
}
if (!function_exists('prod_img_url')) {
    function prod_img_url(?string $stored): string {
        $stored = trim((string) $stored);
        if ($stored === '') return '';
        if (preg_match('#^https?://#i', $stored)) return $stored;
        $stored = ltrim($stored, '/');
        if (strpos($stored, 'assets/img/') === 0) $stored = substr($stored, strlen('assets/img/'));
        $m = 'assets/img/'; $pos = strpos($stored, $m);
        if ($pos !== false) $stored = substr($stored, $pos + strlen($m));
        return 'https://divyaaastha.xinkpayroll.co.in/assets/img/' . ltrim($stored, '/');
    }
}

$id = (int) get('id', 0);
if ($id <= 0) { flash_set('error', 'Invalid ID.'); redirect('admin/modules/products/index.php'); }

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) { flash_set('error', 'Product not found.'); redirect('admin/modules/products/index.php'); }

$adminPageTitle = 'Edit Product';
$adminPageSub   = 'Update product details';
$errors         = [];
$oldImageUrl    = $product['image_url'];

$data = [
    'name'         => $product['name'],
    'slug'         => $product['slug'],
    'category_id'  => (int) ($product['category_id'] ?? 0),
    'short_desc'   => (string) ($product['short_desc'] ?? ''),
    'description'  => (string) ($product['description'] ?? ''),
    'price'        => (float) $product['price'],
    'mrp'          => (float) ($product['mrp'] ?? 0),
    'badge'        => (string) ($product['badge'] ?? ''),
    'badge_style'  => (string) ($product['badge_style'] ?? 'plum'),
    'image_url'    => (string) ($product['image_url'] ?? ''),
    'stock'        => (int) $product['stock'],
    'sku'          => (string) ($product['sku'] ?? ''),
    'is_featured'  => (int) $product['is_featured'],
    'sort_order'   => (int) $product['sort_order'],
    'is_active'    => (int) $product['is_active'],
];

$categories = $pdo->query('SELECT id, name FROM product_categories ORDER BY sort_order ASC, name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));

    foreach (['name','slug','short_desc','description','badge','badge_style','sku'] as $k) {
        $data[$k] = trim((string) post($k, ''));
    }
    $data['category_id'] = (int) post('category_id', 0);
    $data['price']       = (float) post('price', 0);
    $data['mrp']         = (float) post('mrp', 0);
    $data['stock']       = (int) post('stock', 0);
    $data['is_featured'] = (int) post('is_featured', 0);
    $data['sort_order']  = (int) post('sort_order', 0);
    $data['is_active']   = (int) post('is_active', 0);

    /* Image */
    if (!empty($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? 4) !== 4) {
        try {
            $newPath = prod_upload_image('image_file', 'products', $oldImageUrl);
            if ($newPath && $newPath !== $oldImageUrl) $data['image_url'] = $newPath;
        } catch (Throwable $e) {
            $errors['_'] = 'Upload failed: ' . $e->getMessage();
        }
    }
    if (empty(post('remove_image')) && empty($data['image_url']) && $oldImageUrl) {
        $data['image_url'] = $oldImageUrl;
    }
    if (post('remove_image')) {
        if ($oldImageUrl) prod_delete_image($oldImageUrl);
        $data['image_url'] = '';
    }

    /* Validation */
    if ($data['name'] === '') $errors['name']  = 'Product name is required.';
    if ($data['price'] <= 0)  $errors['price'] = 'Price must be greater than 0.';
    if ($data['slug'] === '') $data['slug'] = function_exists('slugify') ? slugify($data['name']) : trim(strtolower(preg_replace('/[^a-z0-9]+/i','-',$data['name'])),'-');

    if (!isset($errors['slug'])) {
        $c = $pdo->prepare('SELECT id FROM products WHERE slug = ? AND id <> ?');
        $c->execute([$data['slug'], $id]);
        if ($c->fetch()) $errors['slug'] = 'Slug already in use.';
    }
    if ($data['sku'] !== '') {
        $c = $pdo->prepare('SELECT id FROM products WHERE sku = ? AND id <> ?');
        $c->execute([$data['sku'], $id]);
        if ($c->fetch()) $errors['sku'] = 'SKU already in use.';
    }

    if (!$errors) {
        try {
            $pdo->prepare(
                'UPDATE products SET
                   category_id = ?, name = ?, slug = ?, short_desc = ?, description = ?,
                   price = ?, mrp = ?, badge = ?, badge_style = ?, image_url = ?,
                   stock = ?, sku = ?, is_featured = ?, sort_order = ?, is_active = ?
                 WHERE id = ?'
            )->execute([
                $data['category_id'] ?: null,
                $data['name'],
                $data['slug'],
                $data['short_desc'] ?: null,
                $data['description'] ?: null,
                $data['price'],
                $data['mrp'] ?: null,
                $data['badge'] ?: null,
                $data['badge_style'] ?: 'plum',
                $data['image_url'] ?: null,
                $data['stock'],
                $data['sku'] ?: null,
                $data['is_featured'],
                $data['sort_order'],
                $data['is_active'],
                $id,
            ]);

            if ($oldImageUrl && $oldImageUrl !== $data['image_url'] && strpos($oldImageUrl, 'http') !== 0) {
                prod_delete_image($oldImageUrl);
            }

            flash_set('success', 'Product updated successfully.');
            redirect('admin/modules/products/index.php');
        } catch (PDOException $e) {
            error_log('[products.edit] ' . $e->getMessage());
            $errors['_'] = 'Could not save: ' . $e->getMessage();
        }
    }
}

require dirname(__DIR__, 2) . '/includes/header.php';
$previewUrl = prod_img_url($data['image_url']);
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
          <div><h4>Basic Information</h4><p>Name, slug and category</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="f-label">Product Name <span class="req">*</span></label>
              <input type="text" name="name" value="<?= e($data['name']) ?>"
                     class="f-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>" required autofocus>
              <?php if (isset($errors['name'])): ?><div class="f-err"><?= e($errors['name']) ?></div><?php endif; ?>
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
            <div class="col-md-6">
              <label class="f-label">SKU</label>
              <input type="text" name="sku" value="<?= e($data['sku']) ?>"
                     class="f-input <?= isset($errors['sku']) ? 'is-invalid' : '' ?>">
              <?php if (isset($errors['sku'])): ?><div class="f-err"><?= e($errors['sku']) ?></div><?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-currency-rupee"></i></div>
          <div><h4>Pricing &amp; Stock</h4><p>Price, MRP and inventory</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="f-label">Price (₹) <span class="req">*</span></label>
              <input type="number" step="0.01" min="0" name="price" value="<?= e($data['price']) ?>"
                     class="f-input <?= isset($errors['price']) ? 'is-invalid' : '' ?>">
              <?php if (isset($errors['price'])): ?><div class="f-err"><?= e($errors['price']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-4">
              <label class="f-label">MRP (₹)</label>
              <input type="number" step="0.01" min="0" name="mrp" value="<?= e($data['mrp']) ?>" class="f-input">
            </div>
            <div class="col-md-4">
              <label class="f-label">Stock</label>
              <input type="number" min="0" name="stock" value="<?= (int) $data['stock'] ?>" class="f-input">
            </div>
          </div>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-image"></i></div>
          <div><h4>Product Image</h4><p>Replace or remove</p></div>
        </div>
        <div class="form-card-body">
          <div class="f-upload" id="uploadBox">
            <input type="file" id="f-file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif">
            <label for="f-file" class="f-upload-btn">
              <i class="bi bi-cloud-arrow-up"></i> Choose File
            </label>
            <div class="f-upload-info" id="fileName">No file chosen — uploading replaces current image</div>
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
          <div><h4>Descriptions</h4><p>Short summary + full details</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label">Short Description</label>
          <input type="text" name="short_desc" value="<?= e($data['short_desc']) ?>" class="f-input" maxlength="400">
          <div style="height:16px"></div>
          <label class="f-label">Full Description</label>
          <textarea name="description" class="f-textarea" rows="5"><?= e($data['description']) ?></textarea>
        </div>
      </div>

    </div>

    <div class="form-side">
      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-tag"></i></div>
          <div><h4>Badge</h4><p>Optional label</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label">Badge Text</label>
          <input type="text" name="badge" value="<?= e($data['badge']) ?>" class="f-input">
          <div style="height:12px"></div>
          <label class="f-label">Badge Style</label>
          <select name="badge_style" class="f-input">
            <?php foreach (['plum','terra','sage','gold','amber','blue'] as $s): ?>
              <option value="<?= $s ?>" <?= $data['badge_style'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-sliders"></i></div>
          <div><h4>Display</h4><p>Ordering</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label">Sort Order</label>
          <input type="number" name="sort_order" value="<?= (int) $data['sort_order'] ?>" class="f-input">
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-star"></i></div>
          <div><h4>Placement</h4><p>Featured &amp; visibility</p></div>
        </div>
        <div class="form-card-body" style="display:flex;flex-direction:column;gap:10px">
          <label class="f-toggle" for="f-feat">
            <input type="checkbox" id="f-feat" name="is_featured" value="1" <?= $data['is_featured'] ? 'checked' : '' ?>>
            <span class="txt"><b>Featured</b><span>Highlight on homepage</span></span>
          </label>
          <label class="f-toggle" for="f-act">
            <input type="checkbox" id="f-act" name="is_active" value="1" <?= $data['is_active'] ? 'checked' : '' ?>>
            <span class="txt"><b>Active</b><span>Visible on the site</span></span>
          </label>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="form-btn form-btn-save">
          <i class="bi bi-check2-circle"></i> Save Changes
        </button>
        <a href="<?= url('admin/modules/products/index.php') ?>" class="form-btn form-btn-cancel">Cancel</a>
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
  var emptyText = 'No file chosen — uploading replaces current image';
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
    if (!f) { fileName.innerHTML = emptyText; return; }
    if (removeBox) removeBox.checked = false;
    fileName.innerHTML = f.name.replace(/[<>&"]/g,'');
    var r = new FileReader();
    r.onload = function (e) { show(e.target.result); };
    r.readAsDataURL(f);
  });
  if (removeBox) removeBox.addEventListener('change', function () {
    if (removeBox.checked) {
      fileInput.value = '';
      fileName.innerHTML = emptyText;
      show('');
    }
  });
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>