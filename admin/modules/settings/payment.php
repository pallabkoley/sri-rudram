<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = 'Payment Settings';
$adminPageSub   = 'Configure payment options';

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

/* Toggles that default to 1 unless explicitly "0" */
function st_on(array $S, string $key, bool $default = true): bool {
    if (!isset($S[$key])) return $default;
    return $S[$key] === '1';
}

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
  <input type="hidden" name="return_to" value="payment">

  <div class="form-page">
    <div class="form-main">

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-credit-card"></i></div>
          <div><h4>Payment Methods</h4><p>Enable or disable payment options</p></div>
        </div>
        <div class="form-card-body" style="display:flex;flex-direction:column;gap:10px">

          <label class="f-toggle" for="f-upi">
            <input type="checkbox" id="f-upi" name="pay_upi" value="1"
                   <?= st_on($S, 'pay_upi') ? 'checked' : '' ?>>
            <span class="txt"><b>UPI</b><span>Google Pay, PhonePe, Paytm, BHIM</span></span>
          </label>

          <label class="f-toggle" for="f-cards">
            <input type="checkbox" id="f-cards" name="pay_cards" value="1"
                   <?= st_on($S, 'pay_cards') ? 'checked' : '' ?>>
            <span class="txt"><b>Credit / Debit Cards</b><span>Visa, Mastercard, RuPay, Amex</span></span>
          </label>

          <label class="f-toggle" for="f-net">
            <input type="checkbox" id="f-net" name="pay_netbanking" value="1"
                   <?= st_on($S, 'pay_netbanking') ? 'checked' : '' ?>>
            <span class="txt"><b>Net Banking</b><span>All major Indian banks</span></span>
          </label>

          <label class="f-toggle" for="f-wallets">
            <input type="checkbox" id="f-wallets" name="pay_wallets" value="1"
                   <?= st_on($S, 'pay_wallets') ? 'checked' : '' ?>>
            <span class="txt"><b>Wallets</b><span>Paytm, Amazon Pay, Mobikwik</span></span>
          </label>

          <label class="f-toggle" for="f-cod">
            <input type="checkbox" id="f-cod" name="pay_cod" value="1"
                   <?= st_on($S, 'pay_cod') ? 'checked' : '' ?>>
            <span class="txt"><b>Cash on Delivery (COD)</b><span>Pay when the product is delivered</span></span>
          </label>

        </div>
      </div>

      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-currency-rupee"></i></div>
          <div><h4>Currency &amp; Tax</h4><p>Base currency and tax settings</p></div>
        </div>
        <div class="form-card-body">
          <div class="row g-3">

            <div class="col-md-4">
              <label class="f-label" for="f-cur">Currency</label>
              <input type="text" id="f-cur" name="currency"
                     value="<?= e($S['currency'] ?? 'INR') ?>"
                     class="f-input" placeholder="INR">
            </div>

            <div class="col-md-4">
              <label class="f-label" for="f-sym">Currency Symbol</label>
              <input type="text" id="f-sym" name="currency_symbol"
                     value="<?= e($S['currency_symbol'] ?? '₹') ?>"
                     class="f-input" placeholder="₹">
            </div>

            <div class="col-md-4">
              <label class="f-label" for="f-gst">GST % (optional)</label>
              <input type="number" step="0.01" min="0" id="f-gst" name="gst_percent"
                     value="<?= e($S['gst_percent'] ?? '0') ?>"
                     class="f-input" placeholder="0">
              <div class="f-hint">Applied on invoice if &gt; 0.</div>
            </div>

            <div class="col-md-6">
              <label class="f-label" for="f-codmax">COD Max Order Value (₹)</label>
              <input type="number" step="1" min="0" id="f-codmax" name="cod_max_value"
                     value="<?= e($S['cod_max_value'] ?? '5000') ?>"
                     class="f-input">
              <div class="f-hint">Orders above this amount cannot be COD.</div>
            </div>

          </div>
        </div>
      </div>

    </div>

    <div class="form-side">
      <div class="form-card">
        <div class="form-card-head">
          <div class="ico"><i class="bi bi-shield-check"></i></div>
          <div><h4>Gateway Keys</h4><p>Leave empty to disable gateway</p></div>
        </div>
        <div class="form-card-body">

          <label class="f-label" for="f-rzp-id">Razorpay Key ID</label>
          <input type="text" id="f-rzp-id" name="razorpay_key_id"
                 value="<?= e($S['razorpay_key_id'] ?? '') ?>"
                 class="f-input" placeholder="rzp_live_…">
          <div style="height:12px"></div>

          <label class="f-label" for="f-rzp-sec">Razorpay Key Secret</label>
          <input type="password" id="f-rzp-sec" name="razorpay_key_secret"
                 value="<?= e($S['razorpay_key_secret'] ?? '') ?>"
                 class="f-input" placeholder="••••••••">

          <div class="f-hint" style="margin-top:10px">
            <i class="bi bi-shield-lock"></i> Keys are stored in your database. Only enter production keys when ready.
          </div>
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