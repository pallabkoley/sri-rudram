<?php
require_once __DIR__ . '/config/config.php';

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'account-orders.php';
    flash_set('info', 'Please login to view your orders.');
    redirect('login.php');
}

$userId = current_user_id();

// Fetch all orders for this user
$stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'My Orders — Divya Aastha';
require BASE_PATH . '/includes/header.php';
?>

<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <a href="<?= url('account.php') ?>">My Account</a>
      <span class="sep">›</span>
      <span class="current">My Orders</span>
    </nav>
    <span class="eyebrow">Commerce</span>
    <h1>My Orders</h1>
    <p>Track and review all your Siddha Store orders.</p>
  </div>
</section>

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
.ac-card{background:#fff;border:1px solid #EFE6D9;border-radius:16px;overflow:hidden;
  box-shadow:0 1px 3px rgba(53,27,47,.05);margin-bottom:20px}
.ac-list{list-style:none;padding:0;margin:0}
.ac-list li{padding:18px 22px;border-bottom:1px solid #F3EEE6;display:flex;
  justify-content:space-between;align-items:center;gap:14px}
.ac-list li:last-child{border-bottom:0}
.ac-list__title{font-weight:600;color:#351B2F;font-size:14.5px;line-height:1.35;margin-bottom:4px}
.ac-list__meta{font-size:12.5px;color:#6B7280;line-height:1.5}
.ac-pill{display:inline-flex;padding:4px 11px;font-size:10.5px;font-weight:700;
  letter-spacing:.4px;text-transform:uppercase;border-radius:99px}
.ac-pill.green{background:#ECFDF5;color:#059669}
.ac-pill.amber{background:#FFFBEB;color:#D97706}
.ac-pill.red{background:#FEF2F2;color:#DC2626}
.ac-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;
  padding:9px 18px;font-size:12.5px;font-weight:600;border-radius:9px;
  text-decoration:none;border:1px solid transparent;cursor:pointer;
  font-family:inherit;transition:all .18s;white-space:nowrap}
.ac-btn-primary{background:#B76146;color:#fff;border-color:#B76146}
.ac-btn-primary:hover{background:#9B4C34;color:#fff}
.ac-btn-outline{background:#fff;color:#351B2F;border-color:#E5E7EB}
.ac-btn-outline:hover{border-color:#B76146;color:#B76146}
.ac-empty{padding:60px 24px;text-align:center;color:#6B7280}
.ac-empty .ico{font-size:52px;color:#D4AF37;margin-bottom:14px;line-height:1}
.ac-empty h4{font-family:var(--font-serif,Georgia,serif);font-size:20px;color:#351B2F;margin:0 0 8px}
.ac-empty p{font-size:13.5px;margin:0 0 22px;line-height:1.6}
</style>

<div class="ac-shell">
  <div class="ac-layout">
    <aside class="ac-nav">
      <a href="<?= url('account.php') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="<?= url('account-orders.php') ?>" class="active"><i class="bi bi-box-seam"></i> My Orders</a>
      <a href="<?= url('account-bookings.php') ?>"><i class="bi bi-calendar-check"></i> Puja Bookings</a>
      <a href="<?= url('account-addresses.php') ?>"><i class="bi bi-geo-alt"></i> Addresses</a>
      <a href="<?= url('account-edit.php') ?>"><i class="bi bi-person"></i> Edit Profile</a>
      <a href="<?= url('wishlist.php') ?>"><i class="bi bi-heart"></i> Wishlist</a>
      <a href="<?= url('logout.php') ?>" class="logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </aside>

    <div class="ac-content">
      <h1 class="ac-h1">My Orders</h1>
      <p class="ac-sub">Showing <b><?= count($orders) ?></b> order<?= count($orders) === 1 ? '' : 's' ?>.</p>

      <?php foreach (flash_get() as $f): ?>
        <div class="ac-flash <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
      <?php endforeach; ?>

      <div class="ac-card">
        <?php if (empty($orders)): ?>
          <div class="ac-empty"><div class="ico">📦</div>
            <h4>No orders yet</h4>
            <p>Start your sacred shopping journey.</p>
            <a href="<?= url('shop.php') ?>" class="ac-btn ac-btn-primary">Visit Store</a></div>
        <?php else: ?>
          <ul class="ac-list">
            <?php foreach ($orders as $o): ?>
              <li>
                <div>
                  <div class="ac-list__title">#<?= e($o['order_number']) ?></div>
                  <div class="ac-list__meta">
                    Placed <?= date('d M Y', strtotime($o['created_at'])) ?>
                    · <span class="ac-pill <?= $o['status'] === 'delivered' ? 'green' : ($o['status'] === 'cancelled' ? 'red' : 'amber') ?>"><?= e($o['status']) ?></span>
                  </div>
                </div>
                <div style="text-align:right">
                  <div style="font-weight:700;color:#351B2F;font-size:16px"><?= inr($o['total']) ?></div>
                  <a href="<?= url('account-order.php?id=' . (int) $o['id']) ?>" class="ac-btn ac-btn-outline" style="margin-top:8px">View Details</a>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require BASE_PATH . '/includes/footer.php'; ?>