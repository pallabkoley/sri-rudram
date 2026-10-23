<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = 'Add Testimonial';
$adminPageSub   = 'Create a new devotee voice';
$errors         = [];

$data = [
    'name'       => '',
    'city'       => '',
    'initials'   => '',
    'rating'     => 5,
    'message'    => '',
    'sort_order' => 0,
    'is_active'  => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(post('_csrf', ''));

    $data['name']       = trim((string) post('name', ''));
    $data['city']       = trim((string) post('city', ''));
    $data['initials']   = strtoupper(trim((string) post('initials', '')));
    $data['message']    = trim((string) post('message', ''));
    $data['rating']     = max(1, min(5, (int) post('rating', 5)));
    $data['sort_order'] = (int) post('sort_order', 0);
    $data['is_active']  = (int) post('is_active', 0);

    /* Auto-generate initials if not given */
    if ($data['initials'] === '' && $data['name'] !== '') {
        $parts = preg_split('/\s+/', $data['name']);
        $i1 = mb_substr($parts[0] ?? '', 0, 1);
        $i2 = mb_substr($parts[1] ?? '', 0, 1);
        $data['initials'] = strtoupper($i1 . $i2);
    }

    /* Validation */
    if ($data['name'] === '')    $errors['name']    = 'Devotee name is required.';
    if ($data['message'] === '') $errors['message'] = 'Message is required.';
    if (mb_strlen($data['message']) > 600) $errors['message'] = 'Message must be under 600 characters.';

    if (!$errors) {
        try {
            $pdo->prepare(
                'INSERT INTO testimonials
                   (name, city, initials, rating, message, sort_order, is_active)
                 VALUES (?,?,?,?,?,?,?)'
            )->execute([
                $data['name'],
                $data['city'] ?: null,
                $data['initials'] ?: null,
                $data['rating'],
                $data['message'],
                $data['sort_order'],
                $data['is_active'],
            ]);

            flash_set('success', 'Testimonial created successfully.');
            redirect('admin/modules/testimonials/index.php');
        } catch (PDOException $e) {
            error_log('[testimonials.create] ' . $e->getMessage());
            $errors['_'] = 'Could not create: ' . $e->getMessage();
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
          <div class="ico"><i class="bi bi-person-circle"></i></div>
          <div><h4>Devotee Information</h4><p>Name, city and initials</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label class="f-label" for="f-name">Full Name <span class="req">*</span></label>
              <input type="text" id="f-name" name="name" value="<?= e($data['name']) ?>"
                     class="f-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                     placeholder="e.g. Priya Sharma" required autofocus>
              <?php if (isset($errors['name'])): ?><div class="f-err"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
              <label class="f-label" for="f-city">City</label>
              <input type="text" id="f-city" name="city" value="<?= e($data['city']) ?>"
                     class="f-input" placeholder="e.g. Mumbai">
            </div>

            <div class="col-md-3">
              <label class="f-label" for="f-init">Initials</label>
              <input type="text" id="f-init" name="initials" value="<?= e($data['initials']) ?>"
                     class="f-input" maxlength="4" placeholder="PS">
              <div class="f-hint">Auto-generated if empty.</div>
            </div>

            <div class="col-md-3">
              <label class="f-label" for="f-rating">Rating (1–5)</label>
              <select id="f-rating" name="rating" class="f-input">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                  <option value="<?= $i ?>" <?= $data['rating'] == $i ? 'selected' : '' ?>>
                    <?= str_repeat('★', $i) ?> (<?= $i ?>)
                  </option>
                <?php endfor; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="f-label" for="f-sort">Sort Order</label>
              <input type="number" id="f-sort" name="sort_order"
                     value="<?= (int) $data['sort_order'] ?>" class="f-input">
              <div class="f-hint">Lower numbers appear first.</div>
            </div>

          </div>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-chat-quote"></i></div>
          <div><h4>Testimonial Message</h4><p>What the devotee said</p></div>
        </div>
        <div class="form-card-body">
          <label class="f-label" for="f-msg">Message <span class="req">*</span></label>
          <textarea id="f-msg" name="message" class="f-textarea <?= isset($errors['message']) ? 'is-invalid' : '' ?>"
                    rows="5" maxlength="600" required
                    placeholder="The puja booking experience was smooth and authentic…"><?= e($data['message']) ?></textarea>
          <?php if (isset($errors['message'])): ?>
            <div class="f-err"><?= e($errors['message']) ?></div>
          <?php else: ?>
            <div class="f-hint"><span id="msgCount">0</span> / 600 characters</div>
          <?php endif; ?>
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
          <i class="bi bi-check2-circle"></i> Create Testimonial
        </button>
        <a href="<?= url('admin/modules/testimonials/index.php') ?>" class="form-btn form-btn-cancel">Cancel</a>
      </div>
    </div>
  </div>
</form>

<script>
(function () {
  var msg = document.getElementById('f-msg');
  var cnt = document.getElementById('msgCount');
  if (msg && cnt) {
    var upd = function () { cnt.textContent = msg.value.length; };
    msg.addEventListener('input', upd); upd();
  }
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>