<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = 'Shipping Settings';
$adminPageSub   = 'Configure delivery &amp; charges';

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

<?php foreach (flash_get() as $f): ?>
  <div class="form-alert" style="<?= $f['type'] === 'success' ? 'background:#ECFDF5;color:#065F46;border-color:#A7F3D0' : '' ?>">
    <i class="bi bi-<?= $f['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill"></i>
    <?= e($f['msg']) ?>
  </div>
<?php endforeach; ?>

<form method="post" action="<?= url('admin/modules/settings/save.php') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="return_to" value="shipping">

  <div class="form-page">
    <div class="form-main">

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-truck"></i></div>
          <div><h4>Shipping Charges</h4><p>Standard delivery fees and thresholds</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label class="f-label" for="f-fsm">Free Shipping Minimum (₹)</label>
              <input type="number" step="1" min="0" id="f-fsm" name="free_shipping_min"
                     value="<?= e($S['free_shipping_min'] ?? '999') ?>"
                     class="f-input">
              <div class="f-hint">Free delivery for orders above this amount.</div>
            </div>

            <div class="col-md-6">
              <label class="f-label" for="f-sc">Standard Shipping Charge (₹)</label>
              <input type="number" step="1" min="0" id="f-sc" name="shipping_charge"
                     value="<?= e($S['shipping_charge'] ?? '99') ?>"
                     class="f-input">
              <div class="f-hint">Applied below the free-shipping threshold.</div>
            </div>

            <div class="col-md-6">
              <label class="f-label" for="f-ed">Estimated Delivery (days)</label>
              <input type="text" id="f-ed" name="delivery_estimate"
                     value="<?= e($S['delivery_estimate'] ?? '3-5') ?>"
                     class="f-input" placeholder="3-5">
              <div class="f-hint">Range shown to customers, e.g. "3-5".</div>
            </div>

            <div class="col-md-6">
              <label class="f-label" for="f-pincodes">Serviceable PIN Codes</label>
              <input type="text" id="f-pincodes" name="serviceable_pincodes"
                     value="<?= e($S['serviceable_pincodes'] ?? '') ?>"
                     class="f-input" placeholder="Leave empty for all India">
              <div class="f-hint">Comma-separated list, or empty for pan-India.</div>
            </div>

            <div class="col-md-12">
              <label class="f-label" for="f-snote">Shipping Note (shown at checkout)</label>
              <input type="text" id="f-snote" name="shipping_note"
                     value="<?= e($S['shipping_note'] ?? 'Free Prasad delivery on Pujas above ₹999') ?>"
                     class="f-input">
            </div>

          </div>
        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-box-seam"></i></div>
          <div><h4>Prasad &amp; Packaging</h4><p>Puja prasad delivery configuration</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label class="f-label" for="f-prasada">Prasad Delivery Time (days)</label>
              <input type="text" id="f-prasada" name="prasad_delivery_days"
                     value="<?= e($S['prasad_delivery_days'] ?? '3-5') ?>"
                     class="f-input" placeholder="3-5">
            </div>

            <div class="col-md-6">
              <label class="f-label" for="f-video">Video Proof Delivery (days)</label>
              <input type="text" id="f-video" name="video_delivery_days"
                     value="<?= e($S['video_delivery_days'] ?? '2-3') ?>"
                     class="f-input" placeholder="2-3">
              <div class="f-hint">Days after puja for video to be shared.</div>
            </div>

          </div>
        </div>
      </div>

    </div>

    <div class="form-side">
      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-lightbulb"></i></div>
          <div><h4>Tips</h4><p>Best practices</p></div>
        </div>
        <div class="form-card-body" style="font-size:13px;color:#6B7280;line-height:1.8">
          <b style="color:#351B2F">Free Shipping Min</b> — set this at your average order value to encourage larger baskets.<br><br>
          <b style="color:#351B2F">Delivery Estimate</b> — be realistic. Most Indian pin codes deliver in 3-5 days.<br><br>
          <b style="color:#351B2F">PIN Codes</b> — leave empty during launch. Add restrictions if you face logistics issues.
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