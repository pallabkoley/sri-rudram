<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = 'Email Settings';
$adminPageSub   = 'Configure email addresses';

if (!function_exists('st_settings')) {
    function st_settings(PDO $pdo): array {
        $cache = [];
        try {
            $rows = $pdo->query('SELECT key_name, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
            if ($rows) $cache = $rows;
        } catch (Throwable $e) { error_log('[settings] ' . $e->getMessage()); }
        return $cache;
    }
}
$S = st_settings($pdo);

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<?php if (!empty($_SESSION['_flash'])): ?>
  <?php foreach (flash_get() as $f): ?>
    <div class="form-alert" style="<?= $f['type'] === 'success' ? 'background:#ECFDF5;color:#065F46;border-color:#A7F3D0' : '' ?>">
      <i class="bi bi-<?= $f['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill"></i>
      <?= e($f['msg']) ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<form method="post" action="<?= url('admin/modules/settings/save.php') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="return_to" value="email">

  <div class="form-page">
    <div class="form-main">

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-envelope"></i></div>
          <div><h4>Email Addresses</h4><p>Public contact and outgoing email configuration</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label class="f-label" for="f-ce">Contact Email (public)</label>
              <input type="email" id="f-ce" name="contact_email"
                     value="<?= e($S['contact_email'] ?? '') ?>"
                     class="f-input" placeholder="seva@divyaaastha.in">
              <div class="f-hint">Shown on Contact page and footer.</div>
            </div>

            <div class="col-md-6">
              <label class="f-label" for="f-se">Sender Email (from)</label>
              <input type="email" id="f-se" name="sender_email"
                     value="<?= e($S['sender_email'] ?? '') ?>"
                     class="f-input" placeholder="no-reply@divyaaastha.in">
              <div class="f-hint">Used as the "From" address in outgoing emails.</div>
            </div>

            <div class="col-md-6">
              <label class="f-label" for="f-re">Reply-To Email</label>
              <input type="email" id="f-re" name="reply_to_email"
                     value="<?= e($S['reply_to_email'] ?? '') ?>"
                     class="f-input" placeholder="support@divyaaastha.in">
              <div class="f-hint">Where replies should go.</div>
            </div>

            <div class="col-md-6">
              <label class="f-label" for="f-cp">Contact Phone</label>
              <input type="text" id="f-cp" name="contact_phone"
                     value="<?= e($S['contact_phone'] ?? '') ?>"
                     class="f-input" placeholder="+91 90000 00000">
            </div>

            <div class="col-md-12">
              <label class="f-label" for="f-ca">Contact Address</label>
              <input type="text" id="f-ca" name="contact_address"
                     value="<?= e($S['contact_address'] ?? '') ?>"
                     class="f-input" placeholder="Bharat · Serving worldwide">
            </div>

          </div>
        </div>
      </div>

    </div>

    <div class="form-side">
      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-info-circle"></i></div>
          <div><h4>Notes</h4><p>How these emails are used</p></div>
        </div>
        <div class="form-card-body" style="font-size:13px;color:#6B7280;line-height:1.8">
          <b style="color:#351B2F">Contact Email</b> — shown publicly on the Contact page, footer, and used as the default recipient for contact form submissions.<br><br>
          <b style="color:#351B2F">Sender Email</b> — used as the From: header in transactional emails (order confirmations, booking confirmations).<br><br>
          <b style="color:#351B2F">Reply-To</b> — used as the reply address when customers respond to automated emails.
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="form-btn form-btn-save">
          <i class="bi bi-check2-circle"></i> Save Changes
        </button>
        <a href="<?= url('admin/modules/settings/index.php') ?>" class="form-btn form-btn-cancel">Back</a>
      </div>
    </div>
  </div>
</form>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>