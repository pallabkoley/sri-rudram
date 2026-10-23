<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$adminPageTitle = 'Settings';
$adminPageSub   = 'Manage site configuration';

/* ==================================================================
 |  Load all settings as key => value
 * ================================================================== */
if (!function_exists('st_settings')) {
    function st_settings(PDO $pdo): array {
        static $cache = null;
        if ($cache !== null) return $cache;
        $cache = [];
        try {
            $rows = $pdo->query('SELECT key_name, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
            if ($rows) $cache = $rows;
        } catch (Throwable $e) {
            error_log('[settings] ' . $e->getMessage());
        }
        return $cache;
    }
}

$S = st_settings($pdo);

/* Count of settings */
$totalSettings = count($S);

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<style>
.st-wrap{margin:-8px 0 0}
.st-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:24px}
@media (max-width:1100px){.st-stats{grid-template-columns:repeat(2,1fr)}}
@media (max-width:600px){.st-stats{grid-template-columns:1fr}}
.st-stat{background:#fff;border-radius:14px;padding:22px;border:1px solid #E5E7EB;
  box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.st-stat-label{font-size:11px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;
  color:#6B7280;margin-bottom:10px}
.st-stat-value{font-size:26px;font-weight:700;color:#1F2937;line-height:1;margin-bottom:8px}
.st-stat-foot{font-size:12px;color:#6B7280}
.st-stat-icon{width:48px;height:48px;border-radius:10px;display:grid;place-items:center;
  font-size:22px;color:#fff;flex-shrink:0}
.st-stat-icon.plum{background:linear-gradient(135deg,#5B3A55,#351B2F)}
.st-stat-icon.terra{background:linear-gradient(135deg,#D07B5C,#B76146)}
.st-stat-icon.amber{background:linear-gradient(135deg,#F59E0B,#D97706)}
.st-stat-icon.green{background:linear-gradient(135deg,#10B981,#059669)}

/* Nav tiles */
.st-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
@media (max-width:1000px){.st-grid{grid-template-columns:repeat(2,1fr)}}
@media (max-width:500px){.st-grid{grid-template-columns:1fr}}
.st-tile{background:#fff;border:1px solid #E5E7EB;border-radius:16px;padding:26px 22px;
  text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:12px;
  box-shadow:0 1px 3px rgba(0,0,0,.06);transition:transform .22s,box-shadow .22s,border-color .22s}
.st-tile:hover{transform:translateY(-4px);box-shadow:0 12px 28px rgba(53,27,47,.10);
  border-color:rgba(183,97,70,.35)}
.st-tile__icon{width:52px;height:52px;border-radius:12px;display:grid;place-items:center;
  font-size:24px;color:#fff}
.st-tile__icon.plum{background:linear-gradient(135deg,#5B3A55,#351B2F)}
.st-tile__icon.terra{background:linear-gradient(135deg,#D07B5C,#B76146)}
.st-tile__icon.amber{background:linear-gradient(135deg,#F59E0B,#D97706)}
.st-tile__icon.green{background:linear-gradient(135deg,#10B981,#059669)}
.st-tile h3{margin:0;font-family:var(--font-serif,Georgia,serif);
  font-size:17px;color:#351B2F;font-weight:600}
.st-tile p{margin:0;font-size:13px;color:#6B7280;line-height:1.55}
.st-tile__cta{margin-top:auto;font-size:12.5px;font-weight:700;color:#B76146;
  display:inline-flex;align-items:center;gap:5px}
</style>

<div class="st-wrap">

  <div class="st-stats">
    <div class="st-stat"><div><div class="st-stat-label">Total Settings</div>
      <div class="st-stat-value"><?= number_format($totalSettings) ?></div>
      <div class="st-stat-foot">Configurable keys</div></div>
      <div class="st-stat-icon plum"><i class="bi bi-gear"></i></div></div>
    <div class="st-stat"><div><div class="st-stat-label">Site Rating</div>
      <div class="st-stat-value"><?= e($S['rating'] ?? '—') ?></div>
      <div class="st-stat-foot">Public rating display</div></div>
      <div class="st-stat-icon amber"><i class="bi bi-star-fill"></i></div></div>
    <div class="st-stat"><div><div class="st-stat-label">Free Shipping From</div>
      <div class="st-stat-value">₹<?= e($S['free_shipping_min'] ?? '0') ?></div>
      <div class="st-stat-foot">Order amount threshold</div></div>
      <div class="st-stat-icon terra"><i class="bi bi-truck"></i></div></div>
    <div class="st-stat"><div><div class="st-stat-label">Contact Email</div>
      <div class="st-stat-value" style="font-size:15px;word-break:break-all"><?= e($S['contact_email'] ?? '—') ?></div>
      <div class="st-stat-foot">Public contact</div></div>
      <div class="st-stat-icon green"><i class="bi bi-envelope"></i></div></div>
  </div>

  <div class="st-grid">

    <a href="<?= url('admin/modules/settings/index.php') ?>#general" class="st-tile">
      <div class="st-tile__icon plum"><i class="bi bi-sliders"></i></div>
      <h3>General</h3>
      <p>Site name, announcement bar, rating and devotee count.</p>
      <span class="st-tile__cta">Manage →</span>
    </a>

    <a href="<?= url('admin/modules/settings/email.php') ?>" class="st-tile">
      <div class="st-tile__icon green"><i class="bi bi-envelope"></i></div>
      <h3>Email</h3>
      <p>Contact email, sender and reply-to addresses.</p>
      <span class="st-tile__cta">Configure →</span>
    </a>

    <a href="<?= url('admin/modules/settings/payment.php') ?>" class="st-tile">
      <div class="st-tile__icon terra"><i class="bi bi-credit-card"></i></div>
      <h3>Payment</h3>
      <p>Payment methods, currency and Cash-on-Delivery.</p>
      <span class="st-tile__cta">Configure →</span>
    </a>

    <a href="<?= url('admin/modules/settings/shipping.php') ?>" class="st-tile">
      <div class="st-tile__icon amber"><i class="bi bi-truck"></i></div>
      <h3>Shipping</h3>
      <p>Free shipping threshold, delivery estimate and charges.</p>
      <span class="st-tile__cta">Configure →</span>
    </a>

  </div>
</div>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>