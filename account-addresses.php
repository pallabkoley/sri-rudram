<?php
require_once __DIR__ . '/config/config.php';

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'account-addresses.php';
    flash_set('info', 'Please login to view your addresses.');
    redirect('login.php');
}

$userId = current_user_id();

/* ==================================================================
 |  Fetch distinct shipping addresses from order history
 * ================================================================== */
try {
    $stmt = $pdo->prepare(
        'SELECT ship_name, ship_phone, ship_line1, ship_line2,
                ship_city, ship_state, ship_pincode,
                MAX(created_at) AS last_used
         FROM orders
         WHERE user_id = ? AND ship_line1 IS NOT NULL AND ship_line1 <> ""
         GROUP BY ship_name, ship_phone, ship_line1, ship_line2,
                  ship_city, ship_state, ship_pincode
         ORDER BY last_used DESC'
    );
    $stmt->execute([$userId]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[account-addresses] ' . $e->getMessage());
    $addresses = [];
}

$pageTitle = 'My Addresses — Divya Aastha';
require BASE_PATH . '/includes/header.php';
?>

<!-- ═══════════════ HERO ═══════════════ -->
<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <a href="<?= url('account.php') ?>">My Account</a>
      <span class="sep">›</span>
      <span class="current">Addresses</span>
    </nav>
    <span class="eyebrow">Delivery</span>
    <h1>My Addresses</h1>
    <p>Your recently used shipping addresses.</p>
  </div>
</section>

<!-- ═══════════════ ACCOUNT SHELL ═══════════════ -->
<style>
.ac-shell{max-width:1200px;margin:0 auto;padding:36px 22px 80px;background:#FAF6EF}
.ac-layout{display:grid;grid-template-columns:240px 1fr;gap:26px}
@media(max-width:900px){.ac-layout{grid-template-columns:1fr;gap:18px}}
.ac-nav{background:#fff;border:1px solid #EFE6D9;border-radius:16px;padding:14px;height:fit-content;
  position:sticky;top:90px;box-shadow:0 1px 3px rgba(53,27,47,.05)}
@media(max-width:900px){.ac-nav{position:static;display:flex;overflow-x:auto;gap:8px;padding:10px}}
.ac-nav a{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;
  font-size:13.5px;font-weight:600;color:#4B5563;text-decoration:none;transition:all .15s;margin-bottom:4px}
@media(max-width:900px){.ac-nav a{flex-shrink:0;margin-bottom:0;white-space:nowrap}}
.ac-nav a:hover{background:#FAF6EF;color:#B76146}
.ac-nav a.active{background:#351B2F;color:#fff}
.ac-nav a i{font-size:16px;width:18px;text-align:center}
.ac-nav a.logout{color:#DC2626;margin-top:8px;border-top:1px solid #F3EEE6;padding-top:16px}
.ac-nav a.logout:hover{background:#FEF2F2;color:#DC2626}

.ac-content{min-width:0}
.ac-h1{font-family:var(--font-serif,Georgia,serif);font-size:26px;color:#351B2F;margin:0 0 6px;font-weight:600}
.ac-sub{color:#6B7280;font-size:13.5px;margin:0 0 24px;line-height:1.6}

.ac-flash{padding:14px 18px;border-radius:12px;font-size:13.5px;margin-bottom:18px;line-height:1.55}
.ac-flash.success{background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0}
.ac-flash.error{background:#FEF2F2;color:#991B1B;border:1px solid #FECACA}
.ac-flash.info{background:#EFF6FF;color:#1E40AF;border:1px solid #BFDBFE}

.ac-card{background:#fff;border:1px solid #EFE6D9;border-radius:16px;overflow:hidden;
  box-shadow:0 1px 3px rgba(53,27,47,.05);margin-bottom:20px}
.ac-card__head{padding:16px 22px;border-bottom:1px solid #EFE6D9;
  display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.ac-card__head h3{margin:0;font-family:var(--font-serif,Georgia,serif);font-size:16px;color:#351B2F;font-weight:600}
.ac-card__body{padding:22px}

.ac-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;
  padding:11px 22px;font-size:13px;font-weight:600;border-radius:10px;
  text-decoration:none;border:1px solid transparent;cursor:pointer;
  font-family:inherit;transition:all .18s;white-space:nowrap}
.ac-btn-primary{background:#B76146;color:#fff;border-color:#B76146}
.ac-btn-primary:hover{background:#9B4C34;color:#fff}

.ac-empty{padding:60px 24px;text-align:center;color:#6B7280}
.ac-empty .ico{font-size:52px;color:#D4AF37;margin-bottom:14px;line-height:1}
.ac-empty h4{font-family:var(--font-serif,Georgia,serif);font-size:20px;color:#351B2F;margin:0 0 8px}
.ac-empty p{font-size:13.5px;margin:0 0 22px;line-height:1.6}

.ac-row{display:grid;grid-template-columns:1fr 1fr;gap:20px}
@media(max-width:700px){.ac-row{grid-template-columns:1fr}}
</style>

<div class="ac-shell">
  <div class="ac-layout">

    <!-- ── SIDEBAR (inline, no external require) ─────────── -->
    <aside class="ac-nav">
      <a href="<?= url('account.php') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="<?= url('account-orders.php') ?>"><i class="bi bi-box-seam"></i> My Orders</a>
      <a href="<?= url('account-bookings.php') ?>"><i class="bi bi-calendar-check"></i> Puja Bookings</a>
      <a href="<?= url('account-addresses.php') ?>" class="active"><i class="bi bi-geo-alt"></i> Addresses</a>
      <a href="<?= url('account-edit.php') ?>"><i class="bi bi-person"></i> Edit Profile</a>
      <a href="<?= url('wishlist.php') ?>"><i class="bi bi-heart"></i> Wishlist</a>
      <a href="<?= url('logout.php') ?>" class="logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </aside>

    <!-- ── CONTENT ────────────────────────────────────────── -->
    <div class="ac-content">
      <h1 class="ac-h1">My Addresses</h1>
      <p class="ac-sub">Addresses from your order history. When placing a new order, you can reuse any of these.</p>

      <?php foreach (flash_get() as $f): ?>
        <div class="ac-flash <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
      <?php endforeach; ?>

      <?php if (empty($addresses)): ?>

        <div class="ac-card">
          <div class="ac-empty">
            <div class="ico">📍</div>
            <h4>No addresses yet</h4>
            <p>Once you place your first order, your delivery address will appear here.</p>
            <a href="<?= url('shop.php') ?>" class="ac-btn ac-btn-primary">Start Shopping</a>
          </div>
        </div>

      <?php else: ?>

        <div class="ac-row">
          <?php foreach ($addresses as $idx => $a): ?>
            <div class="ac-card" style="margin-bottom:0">
              <div class="ac-card__head">
                <h3>
                  <?= $idx === 0 ? '📍 Most Recent' : '📍 Address #' . ($idx + 1) ?>
                </h3>
                <span style="font-size:11.5px;color:#6B7280">
                  Last used <?= date('d M Y', strtotime($a['last_used'])) ?>
                </span>
              </div>
              <div class="ac-card__body" style="font-size:13.5px;color:#4B5563;line-height:1.8">
                <b style="color:#351B2F;font-size:14.5px"><?= e($a['ship_name']) ?></b><br>
                <?php if (!empty($a['ship_phone'])): ?><?= e($a['ship_phone']) ?><br><?php endif; ?>
                <?= e($a['ship_line1']) ?><br>
                <?php if (!empty($a['ship_line2'])): ?><?= e($a['ship_line2']) ?><br><?php endif; ?>
                <?= e($a['ship_city']) ?>, <?= e($a['ship_state']) ?> — <b><?= e($a['ship_pincode']) ?></b>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="margin-top:20px;text-align:center">
          <a href="<?= url('shop.php') ?>" class="ac-btn ac-btn-primary">Shop &amp; Add New Address</a>
        </div>

      <?php endif; ?>
    </div>

  </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>