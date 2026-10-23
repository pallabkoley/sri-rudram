<?php
function sb_active(string $slug): string {
    $current = basename($_SERVER['PHP_SELF']);
    $dir     = basename(dirname($_SERVER['PHP_SELF']));
    return ($current === $slug || $dir === $slug) ? ' active' : '';
}
?>
<nav class="sidebar" id="adminSidebar">
  <div class="logo-container">
    <div class="logo-icon">ॐ</div>
    <div class="logo-text">
      <strong>Divya</strong>
      <span>Aastha Admin</span>
    </div>
  </div>

  <div class="nav">
    <a class="nav-link<?= sb_active('dashboard.php') ?>" href="<?= url('admin/dashboard.php') ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section">Content</div>
    <a class="nav-link<?= sb_active('temples') ?>" href="<?= url('admin/modules/temples/index.php') ?>">
      <i class="bi bi-building"></i> Temples
    </a>
    <a class="nav-link<?= sb_active('pujas') ?>" href="<?= url('admin/modules/pujas/index.php') ?>">
      <i class="bi bi-flower1"></i> Pujas
    </a>
    <a class="nav-link<?= sb_active('bhets') ?>" href="<?= url('admin/modules/bhets/index.php') ?>">
      <i class="bi bi-gift"></i> Bhets
    </a>
    <a class="nav-link<?= sb_active('articles') ?>" href="<?= url('admin/modules/articles/index.php') ?>">
      <i class="bi bi-file-earmark-text"></i> Articles
    </a>
    <a class="nav-link<?= sb_active('festivals') ?>" href="<?= url('admin/modules/festivals/index.php') ?>">
      <i class="bi bi-calendar-event"></i> Festivals
    </a>
    <a class="nav-link<?= sb_active('gyan') ?>" href="<?= url('admin/modules/gyan/index.php') ?>">
      <i class="bi bi-book"></i> Gyan Categories
    </a>
    <a class="nav-link<?= sb_active('testimonials') ?>" href="<?= url('admin/modules/testimonials/index.php') ?>">
      <i class="bi bi-chat-quote"></i> Testimonials
    </a>
    <a class="nav-link<?= sb_active('faqs') ?>" href="<?= url('admin/modules/faqs/index.php') ?>">
      <i class="bi bi-question-circle"></i> FAQs
    </a>

    <div class="nav-section">Commerce</div>
    <a class="nav-link<?= sb_active('products') ?>" href="<?= url('admin/modules/products/index.php') ?>">
      <i class="bi bi-bag"></i> Products
    </a>
    <a class="nav-link<?= sb_active('orders') ?>" href="<?= url('admin/modules/orders/index.php') ?>">
      <i class="bi bi-box-seam"></i> Orders
    </a>
    <a class="nav-link<?= sb_active('bookings') ?>" href="<?= url('admin/modules/bookings/index.php') ?>">
      <i class="bi bi-calendar-check"></i> Puja Bookings
    </a>

    <div class="nav-section">People</div>
    <a class="nav-link<?= sb_active('users') ?>" href="<?= url('admin/modules/users/index.php') ?>">
      <i class="bi bi-people"></i> Users
    </a>
    <a class="nav-link<?= sb_active('subscribers') ?>" href="<?= url('admin/modules/subscribers/index.php') ?>">
      <i class="bi bi-envelope"></i> Subscribers
    </a>

    <div class="nav-section">System</div>
    <a class="nav-link<?= sb_active('settings') ?>" href="<?= url('admin/modules/settings/index.php') ?>">
      <i class="bi bi-gear"></i> Settings
    </a>
    <a class="nav-link" href="<?= url('admin/logout.php') ?>">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </div>

  <div class="sb-foot">
    v1.0 · <b>© <?= date('Y') ?> Divya Aastha</b>
  </div>
</nav>