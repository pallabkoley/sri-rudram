<?php
require_once __DIR__ . '/config/config.php';

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'account.php';
    flash_set('info', 'Please login to access your account.');
    redirect('login.php');
}

$userId = current_user_id();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { session_destroy(); redirect('login.php'); }

$orderCount   = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE user_id = ' . (int)$userId)->fetchColumn();
$bookingCount = (int) $pdo->query('SELECT COUNT(*) FROM puja_bookings WHERE user_id = ' . (int)$userId)->fetchColumn();
$totalSpent   = (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id = " . (int)$userId . " AND status <> 'cancelled'")->fetchColumn();
$wishCount    = (int) $pdo->query('SELECT COUNT(*) FROM wishlists WHERE user_id = ' . (int)$userId)->fetchColumn();

$ro = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
$ro->execute([$userId]);
$recentOrders = $ro->fetchAll(PDO::FETCH_ASSOC);

$rb = $pdo->prepare(
    'SELECT b.*, p.name AS puja_name, t.name AS temple_name
     FROM puja_bookings b
     LEFT JOIN pujas p ON p.id = b.puja_id
     LEFT JOIN temples t ON t.id = b.temple_id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC LIMIT 3'
);
$rb->execute([$userId]);
$recentBookings = $rb->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'My Account — Divya Aastha';
require BASE_PATH . '/includes/header.php';
?>

<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <span class="current">My Account</span>
    </nav>
    <span class="eyebrow">Namaste</span>
    <h1>Welcome, <?= e($user['name']) ?></h1>
    <p>Manage your orders, puja bookings and personal details.</p>
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
.ac-flash.info{background:#EFF6FF;color:#1E40AF;border:1px solid #BFDBFE}
.ac-card{background:#fff;border:1px solid #EFE6D9;border-radius:16px;overflow:hidden;
  box-shadow:0 1px 3px rgba(53,27,47,.05);margin-bottom:20px}
.ac-card__head{padding:16px 22px;border-bottom:1px solid #EFE6D9;
  display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.ac-card__head h3{margin:0;font-family:var(--font-serif,Georgia,serif);font-size:16px;color:#351B2F;font-weight:600}
.ac-card__head a{font-size:12.5px;font-weight:600;color:#B76146;text-decoration:none}
.ac-card__head a:hover{color:#9B4C34}
.ac-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
@media(max-width:800px){.ac-stats{grid-template-columns:repeat(2,1fr)}}
@media(max-width:480px){.ac-stats{grid-template-columns:1fr}}
.ac-stat{background:#fff;border:1px solid #EFE6D9;border-radius:14px;padding:18px 20px;
  box-shadow:0 1px 3px rgba(53,27,47,.05);display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.ac-stat__label{font-size:10.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#6B7280;margin-bottom:8px}
.ac-stat__value{font-size:24px;font-weight:700;color:#1F2937;line-height:1;margin-bottom:6px}
.ac-stat__icon{width:40px;height:40px;border-radius:10px;display:grid;place-items:center;
  font-size:18px;color:#fff;flex-shrink:0}
.ac-stat__icon.plum{background:linear-gradient(135deg,#5B3A55,#351B2F)}
.ac-stat__icon.green{background:linear-gradient(135deg,#10B981,#059669)}
.ac-stat__icon.amber{background:linear-gradient(135deg,#F59E0B,#D97706)}
.ac-stat__icon.terra{background:linear-gradient(135deg,#D07B5C,#B76146)}
.ac-list{list-style:none;padding:0;margin:0}
.ac-list li{padding:16px 22px;border-bottom:1px solid #F3EEE6;display:flex;
  justify-content:space-between;align-items:center;gap:14px}
.ac-list li:last-child{border-bottom:0}
.ac-list__title{font-weight:600;color:#351B2F;font-size:14px;line-height:1.35}
.ac-list__meta{font-size:12px;color:#6B7280;margin-top:4px;line-height:1.5}
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
      <a href="<?= url('account.php') ?>" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="<?= url('account-orders.php') ?>"><i class="bi bi-box-seam"></i> My Orders</a>
      <a href="<?= url('account-bookings.php') ?>"><i class="bi bi-calendar-check"></i> Puja Bookings</a>
      <a href="<?= url('account-addresses.php') ?>"><i class="bi bi-geo-alt"></i> Addresses</a>
      <a href="<?= url('account-edit.php') ?>"><i class="bi bi-person"></i> Edit Profile</a>
      <a href="<?= url('wishlist.php') ?>"><i class="bi bi-heart"></i> Wishlist</a>
      <a href="<?= url('logout.php') ?>" class="logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </aside>

    <div class="ac-content">
      <h1 class="ac-h1">Dashboard</h1>
      <p class="ac-sub">A quick overview of your spiritual journey with us.</p>

      <?php foreach (flash_get() as $f): ?>
        <div class="ac-flash <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
      <?php endforeach; ?>

      <div class="ac-stats">
        <div class="ac-stat"><div><div class="ac-stat__label">Orders</div>
          <div class="ac-stat__value"><?= $orderCount ?></div></div>
          <div class="ac-stat__icon plum"><i class="bi bi-box-seam"></i></div></div>
        <div class="ac-stat"><div><div class="ac-stat__label">Bookings</div>
          <div class="ac-stat__value"><?= $bookingCount ?></div></div>
          <div class="ac-stat__icon green"><i class="bi bi-calendar-check"></i></div></div>
        <div class="ac-stat"><div><div class="ac-stat__label">Spent</div>
          <div class="ac-stat__value" style="font-size:20px"><?= inr($totalSpent) ?></div></div>
          <div class="ac-stat__icon amber"><i class="bi bi-currency-rupee"></i></div></div>
        <div class="ac-stat"><div><div class="ac-stat__label">Wishlist</div>
          <div class="ac-stat__value"><?= $wishCount ?></div></div>
          <div class="ac-stat__icon terra"><i class="bi bi-heart"></i></div></div>
      </div>

      <div class="ac-card">
        <div class="ac-card__head">
          <h3>Recent Orders</h3>
          <a href="<?= url('account-orders.php') ?>">View all →</a>
        </div>
        <?php if (empty($recentOrders)): ?>
          <div class="ac-empty"><div class="ico">📦</div>
            <h4>No orders yet</h4>
            <p>Explore our Siddha Store to begin your spiritual shopping.</p>
            <a href="<?= url('shop.php') ?>" class="ac-btn ac-btn-primary">Visit Store</a></div>
        <?php else: ?>
          <ul class="ac-list">
            <?php foreach ($recentOrders as $o): ?>
              <li>
                <div>
                  <div class="ac-list__title">#<?= e($o['order_number']) ?></div>
                  <div class="ac-list__meta">
                    <?= date('d M Y', strtotime($o['created_at'])) ?>
                    · <span class="ac-pill <?= $o['status']==='delivered'?'green':($o['status']==='cancelled'?'red':'amber') ?>"><?= e($o['status']) ?></span>
                  </div>
                </div>
                <div style="text-align:right">
                  <div style="font-weight:700;color:#351B2F"><?= inr($o['total']) ?></div>
                  <a href="<?= url('account-order.php?id='.(int)$o['id']) ?>" class="ac-btn ac-btn-outline" style="margin-top:6px;padding:6px 14px;font-size:11.5px">Details</a>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <div class="ac-card">
        <div class="ac-card__head">
          <h3>Recent Puja Bookings</h3>
          <a href="<?= url('account-bookings.php') ?>">View all →</a>
        </div>
        <?php if (empty($recentBookings)): ?>
          <div class="ac-empty"><div class="ico">🕉️</div>
            <h4>No bookings yet</h4>
            <p>Book a sacred puja at a revered temple.</p>
            <a href="<?= url('pujas.php') ?>" class="ac-btn ac-btn-primary">Browse Pujas</a></div>
        <?php else: ?>
          <ul class="ac-list">
            <?php foreach ($recentBookings as $b): ?>
              <li>
                <div>
                  <div class="ac-list__title"><?= e($b['puja_name'] ?: 'Puja') ?></div>
                  <div class="ac-list__meta">
                    <?php if ($b['temple_name']): ?><?= e($b['temple_name']) ?> · <?php endif; ?>
                    <?= date('d M Y', strtotime($b['booking_date'])) ?>
                    · <span class="ac-pill <?= $b['status']==='completed'?'green':($b['status']==='cancelled'?'red':'amber') ?>"><?= e($b['status']) ?></span>
                  </div>
                </div>
                <div style="text-align:right">
                  <div style="font-weight:700;color:#351B2F"><?= inr($b['amount']) ?></div>
                  <a href="<?= url('account-booking.php?id='.(int)$b['id']) ?>" class="ac-btn ac-btn-outline" style="margin-top:6px;padding:6px 14px;font-size:11.5px">Details</a>
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