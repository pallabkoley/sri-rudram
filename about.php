<?php
require_once __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require BASE_PATH . '/includes/handlers/cart-handler.php';
}

require_login();
$user = current_user();
$pageTitle = 'My Account — ' . APP_NAME;
require BASE_PATH . '/includes/header.php';
?>

<section class="page-hero">
  <div class="page-hero-mandala"></div>
  <div class="container">
    <nav class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a>
      <span class="sep">›</span>
      <span class="current">Account</span>
    </nav>
    <h1>Namaste, <?= e($user['name']) ?></h1>
    <p>Manage your bookings, orders and account details.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php foreach (flash_get() as $f): ?>
      <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>

    <div class="account-grid">
      <aside class="account-nav">
        <a href="<?= url('account.php') ?>" class="active">👤 Dashboard</a>
        <a href="<?= url('account-orders.php') ?>">📦 My Orders</a>
        <a href="<?= url('account-bookings.php') ?>">🙏 My Bookings</a>
        <a href="<?= url('wishlist.php') ?>">♡ Wishlist</a>
        <a href="<?= url('logout.php') ?>">🚪 Logout</a>
      </aside>

      <div class="account-main">
        <h1>Dashboard</h1>
        <p class="sub">Overview of your account activity</p>

        <?php
        $uid = current_user_id();
        $orderCount = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE user_id = $uid")->fetchColumn();
        $bookingCount = (int) $pdo->query("SELECT COUNT(*) FROM puja_bookings WHERE user_id = $uid")->fetchColumn();
        $wishlistCount = wishlist_count();
        ?>

        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
          <div class="order-card" style="text-align:center">
            <div style="font-size:28px;font-family:'Playfair Display',serif;color:var(--terracotta);font-weight:700"><?= $orderCount ?></div>
            <div style="font-size:12.6px;color:var(--muted);margin-top:4px">Orders</div>
          </div>
          <div class="order-card" style="text-align:center">
            <div style="font-size:28px;font-family:'Playfair Display',serif;color:var(--terracotta);font-weight:700"><?= $bookingCount ?></div>
            <div style="font-size:12.6px;color:var(--muted);margin-top:4px">Puja Bookings</div>
          </div>
          <div class="order-card" style="text-align:center">
            <div style="font-size:28px;font-family:'Playfair Display',serif;color:var(--terracotta);font-weight:700"><?= $wishlistCount ?></div>
            <div style="font-size:12.6px;color:var(--muted);margin-top:4px">Wishlist Items</div>
          </div>
        </div>

        <h3 style="margin-top:32px;margin-bottom:16px;font-size:16px">Account Details</h3>
        <div class="order-card">
          <div style="font-size:13.6px;line-height:2">
            <div><strong style="color:var(--plum)">Name:</strong> <?= e($user['name']) ?></div>
            <div><strong style="color:var(--plum)">Email:</strong> <?= e($user['email']) ?></div>
            <?php if (!empty($user['phone'])): ?>
              <div><strong style="color:var(--plum)">Phone:</strong> <?= e($user['phone']) ?></div>
            <?php endif; ?>
            <?php if (!empty($user['gotra'])): ?>
              <div><strong style="color:var(--plum)">Gotra:</strong> <?= e($user['gotra']) ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require BASE_PATH . '/includes/footer.php'; ?>