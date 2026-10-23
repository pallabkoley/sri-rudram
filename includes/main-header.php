<?php
$nav = [
    ['label' => __('nav_home'),         'href' => 'index.php'],
    ['label' => __('nav_puja'),         'href' => 'pujas.php'],
    ['label' => __('nav_bhet'),         'href' => 'bhet.php'],
    ['label' => __('nav_panchang'),     'href' => 'panchang.php'],
    ['label' => __('nav_rashifal'),     'href' => 'rashifal.php'],
    ['label' => __('nav_gyan'),         'href' => 'gyan.php'],
    ['label' => __('nav_siddha_store'), 'href' => 'shop.php'],
];
$current = current_page();
?>
<header class="site-header" id="siteHeader">
  <div class="container header-inner">
    <a href="<?= url('index.php') ?>" class="brand" aria-label="Divya Aastha — Home">
      <img src="<?= asset('assets/img/logopng.png') ?>"
           alt="Divya Aastha"
           class="brand-logo"
           width="180" height="48">
    </a>

    <nav class="main-nav" aria-label="Primary">
      <?php foreach ($nav as $item): ?>
        <a href="<?= url($item['href']) ?>" class="nav-link<?= $current === $item['href'] ? ' active' : '' ?>">
          <?= e($item['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="header-actions">
      <button class="icon-btn" id="searchBtn" aria-label="<?= e(__('search')) ?>">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
      </button>

      <a href="<?= url('wishlist.php') ?>" class="icon-btn" aria-label="<?= e(__('wishlist')) ?>">
        <svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1L12 21l7.7-7.6 1.1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
        <span class="badge" id="wishCount"><?= $wishCount ?></span>
      </a>

      <button class="icon-btn" id="cartBtn" aria-label="<?= e(__('cart')) ?>">
        <svg viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        <span class="badge" id="cartCount"><?= $cartCount ?></span>
      </button>

      <!-- ================= Language Switcher ================= -->
      <div class="lang-switcher">
        <button class="lang-btn" id="langBtn" type="button"
                aria-haspopup="true" aria-expanded="false"
                aria-label="Select language">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none"
               stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <circle cx="12" cy="12" r="10"/>
            <path d="M2 12h20"/>
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
          </svg>
          <span class="lang-current"><?= e(__('lang_' . current_lang())) ?></span>
          <svg viewBox="0 0 24 24" width="12" height="12" fill="none"
               stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M6 9l6 6 6-6"/>
          </svg>
        </button>
        <div class="lang-menu" id="langMenu" role="menu">
          <a href="<?= e(lang_url('bn')) ?>" role="menuitem"
             class="<?= current_lang() === 'bn' ? 'active' : '' ?>">
            <span>বাংলা</span><small>Bengali</small>
          </a>
          <a href="<?= e(lang_url('en')) ?>" role="menuitem"
             class="<?= current_lang() === 'en' ? 'active' : '' ?>">
            <span>English</span>
          </a>
          <a href="<?= e(lang_url('hi')) ?>" role="menuitem"
             class="<?= current_lang() === 'hi' ? 'active' : '' ?>">
            <span>हिन्दी</span><small>Hindi</small>
          </a>
        </div>
      </div>
      <!-- =============== /Language Switcher ================ -->

      <div class="account-wrap">
        <button class="icon-btn" id="accountBtn" aria-label="<?= e(__('account')) ?>" aria-expanded="false">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
        </button>
        <div class="account-menu" id="accountMenu" role="menu">
          <?php if (is_logged_in()): ?>
            <div class="am-head">
              <b><?= e(__('namaste_user', current_user_name())) ?></b>
              <span><?= e(__('welcome_back')) ?></span>
            </div>
            <a href="<?= url('account.php') ?>"><?= e(__('my_account')) ?></a>
            <a href="<?= url('account-orders.php') ?>"><?= e(__('my_orders')) ?></a>
            <a href="<?= url('account-bookings.php') ?>"><?= e(__('my_bookings')) ?></a>
            <a href="<?= url('wishlist.php') ?>"><?= e(__('wishlist')) ?></a>
            <div class="am-sep"></div>
            <a href="<?= url('logout.php') ?>"><?= e(__('logout')) ?></a>
          <?php else: ?>
            <div class="am-head">
              <b><?= e(__('namaste_devotee')) ?></b>
              <span><?= e(__('welcome_to')) ?> <?= e(APP_NAME) ?></span>
            </div>
            <a href="<?= url('login.php') ?>"><?= e(__('login')) ?></a>
            <a href="<?= url('register.php') ?>"><?= e(__('register')) ?></a>
          <?php endif; ?>
        </div>
      </div>

      <a href="<?= url('pujas.php') ?>" class="btn header-cta"><?= e(__('book_puja')) ?></a>

      <button class="icon-btn hamburger" id="hamburgerBtn" aria-label="Menu">
        <svg viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
    </div>
  </div>
</header>