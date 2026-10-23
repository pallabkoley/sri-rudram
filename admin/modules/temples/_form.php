<?php
/**
 * Shared temple form.
 * Expects:
 *   $data      — array of field values
 *   $errors    — validation errors
 *   $mode      — 'create' or 'edit'
 *   $refPujas    — (edit only) count
 *   $refBookings — (edit only) count
 */
$isEdit = ($mode ?? 'create') === 'edit';
?>

<div class="form-page">

  <div class="form-main">

    <!-- ============ BASIC ============ -->
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
            <label class="f-label" for="f-slug">
              Slug <?php if (!$isEdit): ?><small>auto</small><?php endif; ?>
            </label>
            <div class="f-slugwrap">
              <span class="pre">/temple/</span>
              <input type="text" id="f-slug" name="slug"
                     value="<?= e($data['slug']) ?>"
                     class="f-input <?= isset($errors['slug']) ? 'is-invalid' : '' ?>"
                     placeholder="kashi-vishwanath">
            </div>
            <?php if (isset($errors['slug'])): ?>
              <div class="f-err"><i class="bi bi-exclamation-circle"></i> <?= e($errors['slug']) ?></div>
            <?php elseif ($isEdit): ?>
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
            <div class="f-hint">Pick from suggestions or type your own.</div>
          </div>

        </div>
      </div>
    </div>

    <!-- ============ LOCATION ============ -->
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

    <!-- ============ MEDIA ============ -->
    <div class="form-card">
      <div class="form-card-head">
        <div class="ico"><i class="bi bi-image"></i></div>
        <div>
          <h4>Featured Image</h4>
          <p><?= $isEdit ? 'Upload a new file or edit the URL' : 'Upload a file or paste an external URL' ?></p>
        </div>
      </div>
      <div class="form-card-body">

        <label class="f-label">
          <?= $isEdit ? 'Upload New Image' : 'Upload Image' ?>
          <small>JPG · PNG · WEBP · GIF — max 3 MB</small>
        </label>
        <div class="f-upload" id="uploadBox">
          <input type="file" id="f-file" name="image_file"
                 accept="image/jpeg,image/png,image/webp,image/gif">
          <label for="f-file" class="f-upload-btn">
            <i class="bi bi-cloud-arrow-up"></i> Choose File
          </label>
          <div class="f-upload-info" id="fileName">
            <?= $isEdit
                ? 'No file chosen — uploading replaces the current image'
                : 'No file chosen — JPG, PNG, WEBP or GIF up to 3 MB' ?>
          </div>
        </div>

        <div class="f-divider">or <?= $isEdit ? 'edit' : 'paste a' ?> URL</div>

        <label class="f-label" for="f-img">Image URL</label>
        <input type="url" id="f-img" name="image_url"
               value="<?= e($data['image_url']) ?>"
               class="f-input" placeholder="https://images.unsplash.com/...">
        <div class="f-hint">
          <?= $isEdit
              ? 'Paste a URL or upload a file above — the file takes priority.'
              : 'If both are provided, the uploaded file wins.' ?>
        </div>

        <div class="f-imgprev" id="imgPreview">
          <?php if ($data['image_url']): ?>
            <img src="<?= e(img_url($data['image_url'])) ?>" alt="preview">
          <?php else: ?>
            <div class="ph"><i class="bi bi-image"></i>Preview appears here</div>
          <?php endif; ?>
        </div>

        <?php if ($isEdit && $data['image_url']): ?>
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

    <!-- ============ DESCRIPTIONS ============ -->
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

  <!-- ============ SIDEBAR ============ -->
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
        <i class="bi bi-check2-circle"></i> <?= $isEdit ? 'Save Changes' : 'Save Temple' ?>
      </button>
      <a href="<?= url('admin/modules/temples/index.php') ?>" class="form-btn form-btn-cancel">
        Cancel
      </a>

      <?php if ($isEdit): ?>
        <form method="post"
              action="<?= url('admin/modules/temples/delete.php') ?>"
              style="margin:0"
              data-confirm="<?php
                if (($refPujas ?? 0) > 0 || ($refBookings ?? 0) > 0) {
                    echo 'Temple has ' . ($refPujas ?? 0) . ' puja(s) and ' . ($refBookings ?? 0) . ' booking(s). It will be deactivated instead of deleted. Continue?';
                } else {
                    echo 'Delete ' . e($data['name']) . ' permanently?';
                }
              ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $data['id'] ?>">
          <button type="submit" class="form-btn"
                  style="color:#B23A2E;border-color:rgba(178,58,46,.28);background:transparent">
            <i class="bi bi-trash"></i> Delete Temple
          </button>
        </form>
      <?php endif; ?>

      <div class="meta">
        <?php if ($isEdit): ?>
          <?php if (($refPujas ?? 0) > 0 || ($refBookings ?? 0) > 0): ?>
            Referenced by <b><?= (int) $refPujas ?></b> puja(s) ·
            <b><?= (int) $refBookings ?></b> booking(s)
          <?php else: ?>
            No references — safe to delete
          <?php endif; ?>
        <?php else: ?>
          Fields marked <b style="color:#B76146">*</b> are required
        <?php endif; ?>
      </div>
    </div>

  </div>

</div>

<script>
(function () {
  'use strict';
  var isEdit = <?= $isEdit ? 'true' : 'false' ?>;

  /* ---- Slug auto-gen (create only) ---- */
  if (!isEdit) {
    var nameInput = document.getElementById('f-name');
    var slugInput = document.getElementById('f-slug');
    var slugLocked = slugInput.value.trim() !== '';

    slugInput.addEventListener('input', function () {
      slugLocked = slugInput.value.trim() !== '';
    });
    nameInput.addEventListener('input', function () {
      if (slugLocked) return;
      slugInput.value = (nameInput.value || '').toLowerCase().trim()
        .replace(/[^\p{L}\p{N}]+/gu, '-')
        .replace(/^-+|-+$/g, '');
    });
  }

  /* ---- Image preview ---- */
  var imgInput   = document.getElementById('f-img');
  var fileInput  = document.getElementById('f-file');
  var fileName   = document.getElementById('fileName');
  var imgPreview = document.getElementById('imgPreview');
  var uploadBox  = document.getElementById('uploadBox');
  var removeBox  = document.getElementById('f-remove');

  var placeholder   = '<div class="ph"><i class="bi bi-image"></i>Preview appears here</div>';
  var emptyFileText = isEdit
    ? 'No file chosen — uploading replaces the current image'
    : 'No file chosen — JPG, PNG, WEBP or GIF up to 3 MB';

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

  imgInput.addEventListener('input', function () {
    if (removeBox && removeBox.checked) removeBox.checked = false;
    showPreview(imgInput.value.trim());
  });

  fileInput.addEventListener('change', function () {
    var f = fileInput.files && fileInput.files[0];
    if (!f) { fileName.innerHTML = emptyFileText; return; }
    if (removeBox) removeBox.checked = false;
    var sizeMB = (f.size / 1024 / 1024).toFixed(2);
    fileName.innerHTML = '<span class="name">' +
      f.name.replace(/[<>&"]/g, '') + '</span> · ' + sizeMB + ' MB';
    var reader = new FileReader();
    reader.onload = function (e) { showPreview(e.target.result); };
    reader.readAsDataURL(f);
  });

  if (removeBox) {
    removeBox.addEventListener('change', function () {
      if (removeBox.checked) {
        imgInput.value = '';
        fileInput.value = '';
        fileName.innerHTML = emptyFileText;
        showPreview('');
      }
    });
  }

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

  /* ---- Char counter ---- */
  var shortInput = document.getElementById('f-short');
  var shortCount = document.getElementById('shortCount');
  function upd() { shortCount.textContent = shortInput.value.length; }
  shortInput.addEventListener('input', upd);
  upd();

  /* ---- Unsaved guard ---- */
  var form  = document.querySelector('form');
  var dirty = false;
  form.addEventListener('input',  function () { dirty = true; });
  form.addEventListener('change', function () { dirty = true; });
  form.addEventListener('submit', function () { dirty = false; });
  window.addEventListener('beforeunload', function (e) {
    if (!dirty) return;
    e.preventDefault();
    e.returnValue = '';
  });
})();
</script>