<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

/* ==================================================================
 |  SAFETY NET — image helpers + ASSETS_PATH
 * ================================================================== */
if (!function_exists('admin_upload_image')) {
    $rootFunctions = dirname(__DIR__, 3) . '/includes/functions.php';
    if (is_file($rootFunctions)) {
        require_once $rootFunctions;
    }
}

if (!defined('ASSETS_PATH')) {
    foreach ([
        dirname(__DIR__, 3) . '/assets',
        dirname(__DIR__, 2) . '/assets',
        ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/assets',
    ] as $__p) {
        if ($__p && is_dir($__p)) {
            define('ASSETS_PATH', $__p);
            break;
        }
    }
}

if (!function_exists('img_url')) {
    function img_url(?string $stored): string {
        $stored = trim((string) $stored);
        if ($stored === '') return '';
        if (preg_match('#^https?://#i', $stored)) return $stored;
        $stored = ltrim($stored, '/');
        if (strpos($stored, 'assets/img/') === 0) $stored = substr($stored, strlen('assets/img/'));
        $m = 'assets/img/';
        $pos = strpos($stored, $m);
        if ($pos !== false) $stored = substr($stored, $pos + strlen($m));
        return 'https://divyaaastha.xinkpayroll.co.in/assets/img/' . ltrim($stored, '/');
    }
}

if (!function_exists('admin_delete_image')) {
    function admin_delete_image(?string $pathOrUrl): void {
        if (!$pathOrUrl) return;
        if (preg_match('#^https?://#i', $pathOrUrl)) return;
        if (!defined('ASSETS_PATH')) return;
        $rel = $pathOrUrl;
        if (strpos($rel, '/assets/img/') === 0) $rel = substr($rel, strlen('/assets/'));
        elseif (strpos($rel, 'assets/img/') === 0) $rel = substr($rel, strlen('assets/'));
        elseif (strpos($rel, 'img/') === 0) $rel = substr($rel, 4);
        $rel = ltrim(str_replace(['..', '\\'], ['', '/'], $rel), '/');
        $path = ASSETS_PATH . '/img/' . $rel;
        $real = realpath($path);
        $root = realpath(ASSETS_PATH . '/img');
        if ($real && $root && strpos($real, $root) === 0 && is_file($real)) {
            @unlink($real);
        }
    }
}

if (!function_exists('admin_upload_image')) {
    function admin_upload_image(string $field, string $subfolder, ?string $existing = null): ?string
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
        if (!$mime && function_exists('mime_content_type')) $mime = @mime_content_type($file['tmp_name']);
        if (!$mime) {
            $info = @getimagesize($file['tmp_name']);
            if ($info && !empty($info['mime'])) $mime = $info['mime'];
        }

        $allowed = [
            'image/jpeg'  => 'jpg', 'image/jpg'   => 'jpg', 'image/pjpeg' => 'jpg',
            'image/png'   => 'png', 'image/webp'  => 'webp', 'image/gif'  => 'gif',
        ];

        if (!$mime || !isset($allowed[$mime])) {
            if (function_exists('flash_set')) flash_set('error', 'Only JPG, PNG, WEBP or GIF images are allowed.');
            return $existing;
        }
        if (@getimagesize($file['tmp_name']) === false) {
            if (function_exists('flash_set')) flash_set('error', 'File is not a valid image.');
            return $existing;
        }

        if (!defined('ASSETS_PATH')) {
            if (function_exists('flash_set')) flash_set('error', 'ASSETS_PATH is not configured.');
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
            admin_delete_image($existing);
        }

        return $subfolder . '/' . $name;
    }
}

/* ==================================================================
 |  Load temple
 * ================================================================== */
$id = (int) get('id', 0);
if ($id <= 0) {
    flash_set('error', 'Invalid temple ID.');
    redirect('admin/modules/temples/index.php');
}

$stmt = $pdo->prepare('SELECT * FROM temples WHERE id = ?');
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    flash_set('error', 'Temple not found.');
    redirect('admin/modules/temples/index.php');
}

/* Ensure new fields exist in $data (fallback if column missing) */
foreach (['closed_days','open_time','close_time','closure_note'] as $f) {
    if (!array_key_exists($f, $data)) $data[$f] = '';
}

$adminPageTitle = 'Edit Temple';
$adminPageSub   = 'Update the sacred destination information';
$errors         = [];
$oldImageUrl    = $data['image_url'];

/* Weekday map + initial selection */
$weekdays = [
    'mon' => 'Monday',
    'tue' => 'Tuesday',
    'wed' => 'Wednesday',
    'thu' => 'Thursday',
    'fri' => 'Friday',
    'sat' => 'Saturday',
    'sun' => 'Sunday',
];
$selectedDays = [];
if (!empty($data['closed_days'])) {
    $selectedDays = array_map('trim', explode(',', strtolower($data['closed_days'])));
}

/* ==================================================================
 |  Handle POST
 * ================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));

    foreach (['name','slug','type','city','state','open_time','close_time','closure_note','image_url','short_desc','description'] as $k) {
        $data[$k] = trim((string) post($k, ''));
    }

    /* Closed days from checkboxes */
    $postedDays = post('closed_days', []);
    if (is_array($postedDays)) {
        $selectedDays = array_values(array_intersect(array_keys($weekdays), $postedDays));
    }
    $data['closed_days'] = implode(',', $selectedDays);

    $data['is_featured']      = (int) post('is_featured', 0);
    $data['is_dark_featured'] = (int) post('is_dark_featured', 0);
    $data['sort_order']       = (int) post('sort_order', 0);
    $data['is_active']        = (int) post('is_active', 0);

    /* Image upload */
    $newPath = null;
    if (isset($_FILES['image_file']['error']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        if (!function_exists('admin_upload_image')) {
            $errors['_'] = 'Upload helper not available.';
        } elseif (!defined('ASSETS_PATH')) {
            $errors['_'] = 'Upload path (ASSETS_PATH) is not configured.';
        } else {
            try {
                $newPath = admin_upload_image('image_file', 'temples', $oldImageUrl);
                if ($newPath && $newPath !== $oldImageUrl) {
                    $data['image_url'] = $newPath;
                }
            } catch (Throwable $e) {
                error_log('[temples.edit:image_upload] ' . $e->getMessage());
                $errors['_'] = 'Image upload failed: ' . $e->getMessage();
            }
        }
    }

    if (empty(post('remove_image')) && empty($data['image_url']) && $oldImageUrl && !$newPath) {
        $data['image_url'] = $oldImageUrl;
    }

    if (post('remove_image')) {
        if ($oldImageUrl && strpos($oldImageUrl, 'http') !== 0 && function_exists('admin_delete_image')) {
            admin_delete_image($oldImageUrl);
        }
        $data['image_url'] = '';
    }

    /* Validation */
    if ($data['name'] === '') {
        $errors['name'] = 'Temple name is required.';
    }
    if ($data['slug'] === '') {
        $data['slug'] = slugify($data['name']);
    }

    foreach (['open_time', 'close_time'] as $tk) {
        if ($data[$tk] !== '' && !preg_match('/^\d{2}:\d{2}$/', $data[$tk])) {
            $errors[$tk] = 'Use 24-hour format HH:MM (e.g. 06:30).';
        }
    }
    if ($data['open_time'] !== '' && $data['close_time'] !== ''
        && $data['open_time'] >= $data['close_time']) {
        $errors['close_time'] = 'Close time must be after open time.';
    }

    if (!isset($errors['slug'])) {
        $c = $pdo->prepare('SELECT id FROM temples WHERE slug = ? AND id <> ?');
        $c->execute([$data['slug'], $id]);
        if ($c->fetch()) {
            $errors['slug'] = 'That slug is already in use.';
        }
    }

    /* UPDATE */
    if (!$errors) {
        try {
            $pdo->prepare(
                'UPDATE temples SET
                   name             = ?,
                   slug             = ?,
                   type             = ?,
                   city             = ?,
                   state            = ?,
                   closed_days      = ?,
                   open_time        = ?,
                   close_time       = ?,
                   closure_note     = ?,
                   image_url        = ?,
                   short_desc       = ?,
                   description      = ?,
                   is_featured      = ?,
                   is_dark_featured = ?,
                   sort_order       = ?,
                   is_active        = ?
                 WHERE id = ?'
            )->execute([
                $data['name'],
                $data['slug'],
                $data['type']             ?: null,
                $data['city']             ?: null,
                $data['state']            ?: null,
                $data['closed_days']      ?: null,
                $data['open_time']        ?: null,
                $data['close_time']       ?: null,
                $data['closure_note']     ?: null,
                $data['image_url']        ?: null,
                $data['short_desc']       ?: null,
                $data['description']      ?: null,
                $data['is_featured'],
                $data['is_dark_featured'],
                $data['sort_order'],
                $data['is_active'],
                $id,
            ]);

            if ($oldImageUrl && $oldImageUrl !== $data['image_url']
                && strpos($oldImageUrl, 'http') !== 0
                && function_exists('admin_delete_image')) {
                admin_delete_image($oldImageUrl);
            }

            flash_set('success', 'Temple updated successfully.');
            redirect('admin/modules/temples/index.php');

        } catch (PDOException $e) {
            error_log('[temples.edit] ' . $e->getMessage());
            $errors['_'] = 'Could not save. Please try again.';
        }
    }
}

/* Reference counts */
$refPujas    = (int) $pdo->query('SELECT COUNT(*) FROM pujas WHERE temple_id = ' . (int) $id)->fetchColumn();
$refBookings = (int) $pdo->query('SELECT COUNT(*) FROM puja_bookings WHERE temple_id = ' . (int) $id)->fetchColumn();

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<?php if (!empty($errors['_'])): ?>
  <div class="form-alert">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <?= e($errors['_']) ?>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" novalidate autocomplete="off">
  <?= csrf_field() ?>

  <div class="form-page">
    <div class="form-main">

      <!-- ═══════════ BASIC INFORMATION ═══════════ -->
      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-info-circle"></i></div>
          <div><h4>Basic Information</h4><p>Name, slug and temple type</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="f-label" for="f-name">Temple Name <span class="req">*</span></label>
              <input type="text" id="f-name" name="name"
                     value="<?= e($data['name']) ?>"
                     class="f-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                     placeholder="e.g. Kashi Vishwanath" required autofocus>
              <?php if (isset($errors['name'])): ?>
                <div class="f-err"><i class="bi bi-exclamation-circle"></i> <?= e($errors['name']) ?></div>
              <?php endif; ?>
            </div>
            <div class="col-md-4">
              <label class="f-label" for="f-slug">Slug</label>
              <div class="f-slugwrap">
                <span class="pre">/temple/</span>
                <input type="text" id="f-slug" name="slug"
                       value="<?= e($data['slug']) ?>"
                       class="f-input <?= isset($errors['slug']) ? 'is-invalid' : '' ?>"
                       placeholder="kashi-vishwanath">
              </div>
              <?php if (isset($errors['slug'])): ?>
                <div class="f-err"><i class="bi bi-exclamation-circle"></i> <?= e($errors['slug']) ?></div>
              <?php else: ?>
                <div class="f-hint">Changing this breaks existing links.</div>
              <?php endif; ?>
            </div>
            <div class="col-md-6">
              <label class="f-label" for="f-type">Temple Type</label>
              <input type="text" id="f-type" name="type"
                     value="<?= e($data['type']) ?>"
                     class="f-input" list="templeTypes"
                     placeholder="Jyotirlinga, Shakti Peeth, Char Dham…">
              <datalist id="templeTypes">
                <option value="Jyotirlinga">
                <option value="Shakti Peeth">
                <option value="Char Dham">
                <option value="Panch Kedar">
                <option value="Divya Desam">
                <option value="UNESCO Heritage">
                <option value="Ancient Temple">
              </datalist>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════ LOCATION ═══════════ -->
      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-geo-alt"></i></div>
          <div><h4>Location</h4><p>City and state for filtering and SEO</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="f-label" for="f-city">City</label>
              <input type="text" id="f-city" name="city" value="<?= e($data['city']) ?>"
                     class="f-input" placeholder="e.g. Varanasi">
            </div>
            <div class="col-md-6">
              <label class="f-label" for="f-state">State</label>
              <input type="text" id="f-state" name="state" value="<?= e($data['state']) ?>"
                     class="f-input" placeholder="e.g. Uttar Pradesh">
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════ OPENING HOURS & CLOSURE ═══════════ -->
      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-clock-history"></i></div>
          <div>
            <h4>Opening Hours &amp; Closure</h4>
            <p>When the temple is open — shown on the public card</p>
          </div>
        </div>
        <div class="form-card-body">

          <div class="row g-3" style="margin-bottom:14px">
            <div class="col-md-6">
              <label class="f-label" for="f-open">Opening Time</label>
              <input type="time" id="f-open" name="open_time"
                     value="<?= e($data['open_time']) ?>"
                     class="f-input <?= isset($errors['open_time']) ? 'is-invalid' : '' ?>">
              <?php if (isset($errors['open_time'])): ?>
                <div class="f-err"><i class="bi bi-exclamation-circle"></i> <?= e($errors['open_time']) ?></div>
              <?php else: ?>
                <div class="f-hint">e.g. 05:30 AM</div>
              <?php endif; ?>
            </div>
            <div class="col-md-6">
              <label class="f-label" for="f-close">Closing Time</label>
              <input type="time" id="f-close" name="close_time"
                     value="<?= e($data['close_time']) ?>"
                     class="f-input <?= isset($errors['close_time']) ? 'is-invalid' : '' ?>">
              <?php if (isset($errors['close_time'])): ?>
                <div class="f-err"><i class="bi bi-exclamation-circle"></i> <?= e($errors['close_time']) ?></div>
              <?php else: ?>
                <div class="f-hint">e.g. 09:00 PM</div>
              <?php endif; ?>
            </div>
          </div>

          <label class="f-label">Weekly Closed Days</label>
          <div class="f-hint" style="margin-bottom:8px">
            Tick all days the temple is closed. Leave all empty if it opens daily.
          </div>

          <div class="tpl-weekdays">
            <?php foreach ($weekdays as $key => $label): ?>
              <label class="tpl-day">
                <input type="checkbox" name="closed_days[]" value="<?= e($key) ?>"
                       <?= in_array($key, $selectedDays, true) ? 'checked' : '' ?>>
                <span><?= e($label) ?></span>
              </label>
            <?php endforeach; ?>
          </div>

          <div style="margin-top:16px">
            <label class="f-label" for="f-note">
              Closure Note
              <small>Optional</small>
            </label>
            <input type="text" id="f-note" name="closure_note"
                   value="<?= e($data['closure_note']) ?>"
                   class="f-input" maxlength="200"
                   placeholder="e.g. Closed in winter (Nov–April) · Friday 12:30–15:00 closed">
            <div class="f-hint">Free-text note that appears with the timing info.</div>
          </div>

        </div>
      </div>

      <!-- ═══════════ FEATURED IMAGE ═══════════ -->
      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-image"></i></div>
          <div><h4>Featured Image</h4><p>Upload a new file or edit the URL</p></div>
        </div>
        <div class="form-card-body">

          <label class="f-label">
            Upload New Image
            <small>JPG · PNG · WEBP · GIF — max 3 MB</small>
          </label>
          <div class="f-upload" id="uploadBox">
            <input type="file" id="f-file" name="image_file"
                   accept="image/jpeg,image/png,image/webp,image/gif">
            <label for="f-file" class="f-upload-btn">
              <i class="bi bi-cloud-arrow-up"></i> Choose File
            </label>
            <div class="f-upload-info" id="fileName">
              No file chosen — uploading replaces the current image
            </div>
          </div>

          <div class="f-divider">or edit URL</div>

          <label class="f-label" for="f-img">Image URL</label>
          <input type="url" id="f-img" name="image_url"
                 value="<?= e($data['image_url']) ?>"
                 class="f-input" placeholder="https://images.unsplash.com/...">

          <div class="f-imgprev" id="imgPreview">
            <?php if ($data['image_url']): ?>
              <img src="<?= e(img_url($data['image_url'])) ?>" alt="preview">
            <?php else: ?>
              <div class="ph"><i class="bi bi-image"></i>Preview appears here</div>
            <?php endif; ?>
          </div>

          <?php if ($data['image_url']): ?>
            <label class="f-toggle" for="f-remove" style="margin-top:12px">
              <input type="checkbox" id="f-remove" name="remove_image" value="1">
              <span class="txt">
                <b>Remove current image</b>
                <span>Clears the URL and deletes the file (if hosted locally)</span>
              </span>
            </label>
          <?php endif; ?>

        </div>
      </div>

      <!-- ═══════════ DESCRIPTIONS ═══════════ -->
      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-text-paragraph"></i></div>
          <div><h4>Descriptions</h4><p>Short summary + full temple story</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label" for="f-short">
            Short Description
            <small><span id="shortCount">0</span> / 400</small>
          </label>
          <input type="text" id="f-short" name="short_desc"
                 value="<?= e($data['short_desc']) ?>"
                 class="f-input" maxlength="400"
                 placeholder="One line summary shown on listing cards">

          <div style="height:16px"></div>

          <label class="f-label" for="f-desc">Full Description</label>
          <textarea id="f-desc" name="description" class="f-textarea"
                    placeholder="History, significance, legends, architecture…"><?= e($data['description']) ?></textarea>
        </div>
      </div>

    </div>

    <!-- ═══════════ SIDEBAR ═══════════ -->
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
          <div><h4>Homepage Placement</h4><p>Where this temple is highlighted</p></div>
        </div>
        <div class="form-card-body" style="display:flex;flex-direction:column;gap:10px">
          <label class="f-toggle" for="f-feat">
            <input type="checkbox" id="f-feat" name="is_featured" value="1"
                   <?= $data['is_featured'] ? 'checked' : '' ?>>
            <span class="txt">
              <b>Featured (light)</b>
              <span>Shown in the light carousel on the homepage</span>
            </span>
          </label>
          <label class="f-toggle" for="f-dfeat">
            <input type="checkbox" id="f-dfeat" name="is_dark_featured" value="1"
                   <?= $data['is_dark_featured'] ? 'checked' : '' ?>>
            <span class="txt">
              <b>Featured (dark)</b>
              <span>Shown in the dark section on the homepage</span>
            </span>
          </label>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-eye"></i></div>
          <div><h4>Visibility</h4><p>Whether devotees can see this temple</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-toggle" for="f-act">
            <input type="checkbox" id="f-act" name="is_active" value="1"
                   <?= $data['is_active'] ? 'checked' : '' ?>>
            <span class="txt">
              <b>Active</b>
              <span>Visible to devotees on the site</span>
            </span>
          </label>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="form-btn form-btn-save">
          <i class="bi bi-check2-circle"></i> Update Temple
        </button>
        <a href="<?= url('admin/modules/temples/index.php') ?>" class="form-btn form-btn-cancel">
          Cancel
        </a>
        <div class="meta">
          <?php if ($refPujas > 0 || $refBookings > 0): ?>
            Referenced by <b><?= (int) $refPujas ?></b> puja(s) ·
            <b><?= (int) $refBookings ?></b> booking(s)
          <?php else: ?>
            No references — safe to delete
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</form>

<div class="form-page" style="margin-top:0">
  <div class="form-main"></div>
  <div class="form-side">
    <form method="post"
          action="<?= url('admin/modules/temples/delete.php') ?>"
          style="margin:0"
          data-confirm="<?php
            if ($refPujas > 0 || $refBookings > 0) {
                echo 'Temple has ' . $refPujas . ' puja(s) and ' . $refBookings . ' booking(s). It will be deactivated instead of deleted. Continue?';
            } else {
                echo 'Delete ' . e($data['name']) . ' permanently?';
            }
          ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $data['id'] ?>">
      <button type="submit" class="form-btn"
              style="width:100%;color:#B23A2E;border-color:rgba(178,58,46,.28);background:transparent">
        <i class="bi bi-trash"></i> Delete Temple
      </button>
    </form>
  </div>
</div>

<style>
.tpl-weekdays{
  display:flex;flex-wrap:wrap;gap:8px;
  padding:12px;background:#FBF7F0;border:1px solid #EFE6D9;border-radius:10px;
}
.tpl-day{
  display:inline-flex;align-items:center;gap:7px;
  padding:8px 14px;border-radius:99px;cursor:pointer;
  background:#fff;border:1px solid #E5DDD0;
  font-size:13px;font-weight:600;color:#432B26;
  user-select:none;transition:all .15s;
}
.tpl-day:hover{border-color:#D4AF37;background:#FDF9EE}
.tpl-day input{accent-color:#4B080C;width:15px;height:15px;cursor:pointer}
.tpl-day:has(input:checked){
  background:linear-gradient(135deg,#4B080C,#310508);
  color:#F6E8CF;border-color:#4B080C;
}
</style>

<script>
(function () {
  'use strict';

  var imgInput   = document.getElementById('f-img');
  var fileInput  = document.getElementById('f-file');
  var fileName   = document.getElementById('fileName');
  var imgPreview = document.getElementById('imgPreview');
  var uploadBox  = document.getElementById('uploadBox');
  var removeBox  = document.getElementById('f-remove');

  var placeholder = '<div class="ph"><i class="bi bi-image"></i>Preview appears here</div>';
  var emptyText   = 'No file chosen — uploading replaces the current image';

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

  if (imgInput) {
    imgInput.addEventListener('input', function () {
      if (removeBox && removeBox.checked) removeBox.checked = false;
      showPreview(imgInput.value.trim());
    });
  }

  if (fileInput) {
    fileInput.addEventListener('change', function () {
      var f = fileInput.files && fileInput.files[0];
      if (!f) { fileName.innerHTML = emptyText; return; }
      if (removeBox) removeBox.checked = false;
      var sizeMB = (f.size / 1024 / 1024).toFixed(2);
      fileName.innerHTML = '<span class="name">' +
        f.name.replace(/[<>&"]/g, '') + '</span> · ' + sizeMB + ' MB';
      var reader = new FileReader();
      reader.onload = function (e) { showPreview(e.target.result); };
      reader.readAsDataURL(f);
    });
  }

  if (removeBox) {
    removeBox.addEventListener('change', function () {
      if (removeBox.checked) {
        imgInput.value = '';
        fileInput.value = '';
        fileName.innerHTML = emptyText;
        showPreview('');
      }
    });
  }

  if (uploadBox) {
    ['dragenter','dragover'].forEach(function (ev) {
      uploadBox.addEventListener(ev, function (e) {
        e.preventDefault();
        uploadBox.classList.add('dragover');
      });
    });
    ['dragleave','drop'].forEach(function (ev) {
      uploadBox.addEventListener(ev, function (e) {
        e.preventDefault();
        uploadBox.classList.remove('dragover');
      });
    });
    uploadBox.addEventListener('drop', function (e) {
      if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        fileInput.dispatchEvent(new Event('change'));
      }
    });
  }

  var shortInput = document.getElementById('f-short');
  var shortCount = document.getElementById('shortCount');
  function upd() { if (shortCount && shortInput) shortCount.textContent = shortInput.value.length; }
  if (shortInput) { shortInput.addEventListener('input', upd); upd(); }

  var form  = document.querySelector('form[enctype]');
  var dirty = false;
  if (form) {
    form.addEventListener('input',  function () { dirty = true; });
    form.addEventListener('change', function () { dirty = true; });
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) {
      if (!dirty) return;
      e.preventDefault();
      e.returnValue = '';
    });
  }
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>