<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = 'Add Temple';
$adminPageSub   = 'Create a new sacred destination';

$errors = [];
$data = [
    'name'             => '',
    'slug'             => '',
    'type'             => '',
    'city'             => '',
    'state'            => '',
    'closed_days'      => '',
    'open_time'        => '',
    'close_time'       => '',
    'closure_note'     => '',
    'image_url'        => '',
    'short_desc'       => '',
    'description'      => '',
    'is_featured'      => 0,
    'is_dark_featured' => 0,
    'sort_order'       => 0,
    'is_active'        => 1,
];

/* Predefined weekday checkboxes */
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach (['name','slug','type','city','state','open_time','close_time','closure_note','image_url','short_desc','description'] as $k) {
        $data[$k] = trim((string) post($k, ''));
    }

    /* --- Closed days from checkboxes + text field --- */
    $postedDays = post('closed_days', []);
    if (is_array($postedDays)) {
        $selectedDays = array_values(array_intersect(array_keys($weekdays), $postedDays));
    } elseif (is_string($postedDays)) {
        // legacy text field fallback
        $selectedDays = array_map('trim', explode(',', strtolower($postedDays)));
    }
    $data['closed_days'] = implode(',', $selectedDays);

    $data['is_featured']      = (int) post('is_featured', 0);
    $data['is_dark_featured'] = (int) post('is_dark_featured', 0);
    $data['sort_order']       = (int) post('sort_order', 0);
    $data['is_active']        = (int) post('is_active', 0);

    // Handle image upload
    $newPath = admin_upload_image('image_file', 'temples', null);
    if ($newPath) {
        $data['image_url'] = $newPath;
    }

    if (post('remove_image')) {
        $data['image_url'] = '';
    }

    // Validation
    if ($data['name'] === '') {
        $errors['name'] = 'Temple name is required.';
    }
    if ($data['slug'] === '') {
        $data['slug'] = slugify($data['name']);
    }

    // Time format validation
    foreach (['open_time', 'close_time'] as $tk) {
        if ($data[$tk] !== '' && !preg_match('/^\d{2}:\d{2}$/', $data[$tk])) {
            $errors[$tk] = 'Use 24-hour format HH:MM (e.g. 06:30).';
        }
    }
    if ($data['open_time'] !== '' && $data['close_time'] !== ''
        && $data['open_time'] >= $data['close_time']) {
        $errors['close_time'] = 'Close time must be after open time.';
    }

    // Unique slug check
    if (!isset($errors['slug'])) {
        $c = $pdo->prepare('SELECT id FROM temples WHERE slug = ?');
        $c->execute([$data['slug']]);
        if ($c->fetch()) {
            $errors['slug'] = 'That slug is already in use.';
        }
    }

    if (!$errors) {
        try {
            $pdo->prepare(
                'INSERT INTO temples
                 (name, slug, type, city, state,
                  closed_days, open_time, close_time, closure_note,
                  image_url, short_desc, description,
                  is_featured, is_dark_featured, sort_order, is_active)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
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
            ]);
            flash_set('success', 'Temple created successfully.');
            redirect('admin/modules/temples/index.php');
        } catch (PDOException $e) {
            error_log('[temples.create] ' . $e->getMessage());
            $errors['_'] = 'Could not save. Please try again.';
        }
    }
}

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
              <label class="f-label" for="f-name">
                Temple Name <span class="req">*</span>
              </label>
              <input type="text" id="f-name" name="name"
                     value="<?= e($data['name']) ?>"
                     class="f-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                     placeholder="e.g. Kashi Vishwanath"
                     required autofocus>
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
                <div class="f-hint">Leave blank to auto-generate from name.</div>
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
              <div class="f-hint">Pick from suggestions or type your own.</div>
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
              <input type="text" id="f-city" name="city"
                     value="<?= e($data['city']) ?>"
                     class="f-input" placeholder="e.g. Varanasi">
            </div>
            <div class="col-md-6">
              <label class="f-label" for="f-state">State</label>
              <input type="text" id="f-state" name="state"
                     value="<?= e($data['state']) ?>"
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

          <!-- Opening / closing time -->
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

          <!-- Closed days (weekday checkboxes) -->
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

          <!-- Free-text closure note -->
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
              No file chosen
            </div>
          </div>

          <div class="f-divider">or edit URL</div>

          <label class="f-label" for="f-img">Image URL</label>
          <input type="url" id="f-img" name="image_url"
                 value="<?= e($data['image_url']) ?>"
                 class="f-input" placeholder="https://images.unsplash.com/...">
          <div class="f-hint">Paste a URL or upload a file above — the file takes priority.</div>

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
          <i class="bi bi-check2-circle"></i> Create Temple
        </button>
        <a href="<?= url('admin/modules/temples/index.php') ?>" class="form-btn form-btn-cancel">
          Cancel
        </a>
      </div>

    </div>
  </div>
</form>

<!-- ═══════════ Inline styles for weekday picker ═══════════ -->
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
  var emptyText   = 'No file chosen';

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

  var form  = document.querySelector('form');
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