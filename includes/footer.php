</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">

      <div class="footer-col">
        <div class="footer-brand">
          <a href="<?= url('index.php') ?>" aria-label="Divya Aastha — Home">
            <img src="<?= asset('assets/img/logopng.png') ?>"
                 alt="Divya Aastha"
                 class="footer-logo"
                 width="180" height="48">
          </a>
        </div>
        <p>Bringing authentic Hindu pujas, sacred products, temple experiences and spiritual wisdom closer to devotees.</p>
      </div>

      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="<?= url('index.php') ?>">Home</a></li>
          <li><a href="<?= url('pujas.php') ?>">Puja</a></li>
          <li><a href="<?= url('shop.php') ?>">Shop</a></li>
          <li><a href="<?= url('gyan.php') ?>">Gyan</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Services</h4>
        <ul>
          <li><a href="<?= url('pujas.php') ?>">Online Puja Booking</a></li>
          <li><a href="<?= url('temples.php') ?>">Temple Darshan</a></li>
          <li><a href="<?= url('shop.php') ?>">Siddha Store</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Contact</h4>
        <ul>
          <li><a href="mailto:<?= e(setting('contact_email', 'seva@divyaaastha.in')) ?>"><?= e(setting('contact_email', 'seva@divyaaastha.in')) ?></a></li>
          <li><a href="tel:<?= e(setting('contact_phone', '+919000000000')) ?>"><?= e(setting('contact_phone', '+91 90000 00000')) ?></a></li>
        </ul>
      </div>

    </div>

    <div class="footer-bottom">
      <p>© <?= date('Y') ?> <?= e(APP_NAME) ?>. All Rights Reserved.</p>
    </div>
  </div>
</footer>

<div class="toast-wrap" id="toastWrap" aria-live="polite"></div>

<div class="float-stack">
  <a href="https://wa.me/<?= e(setting('social_whatsapp', '919000000000')) ?>" class="fab fab-wa" target="_blank" aria-label="WhatsApp">
    <svg viewBox="0 0 24 24" width="21" height="21"><path d="M17.5 14.4c-.3-.2-1.7-.9-2-1-.3-.1-.5-.2-.7.1s-.8 1-.9 1.2c-.2.2-.3.2-.6.1a8.2 8.2 0 0 1-2.4-1.5 9 9 0 0 1-1.7-2.1c-.2-.3 0-.5.1-.6l.6-.7c.2-.2.2-.3.3-.5s0-.4 0-.5l-.9-2.1c-.2-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.8.4A3.4 3.4 0 0 0 6 9.2a6 6 0 0 0 1.3 3.2 12.4 12.4 0 0 0 5.3 4.7c2.6 1 2.6.7 3.1.6a3.1 3.1 0 0 0 2-1.4 2.5 2.5 0 0 0 .2-1.4c-.1-.2-.3-.3-.4-.5z"/></svg>
  </a>
  <button class="fab fab-top" id="backToTop" aria-label="Back to top">
    <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 19V5M6 11l6-6 6 6"/>
    </svg>
  </button>
</div>

<!-- ═══════════════════════════════════════════════════════
     ALL HEADER INTERACTIONS — INLINE (guaranteed to work)
     ═══════════════════════════════════════════════════════ -->
<script>
(function() {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function() {
    console.log('🚀 Divya Aastha JS init');

    // ═══════════════ ELEMENT REFS ═══════════════
    var cartBtn     = document.getElementById('cartBtn');
    var cartClose   = document.getElementById('cartClose');
    var cartDrawer  = document.getElementById('cartDrawer');
    var drawerBack  = document.getElementById('drawerBackdrop');

    var accountBtn  = document.getElementById('accountBtn');
    var accountMenu = document.getElementById('accountMenu');

    var hamburger   = document.getElementById('hamburgerBtn');
    var mobileNav   = document.getElementById('mobileNav');

    var searchBtn   = document.getElementById('searchBtn');
    var searchOverlay = document.getElementById('searchOverlay');
    var searchClose = document.getElementById('searchClose');

    var header      = document.getElementById('siteHeader');
    var backToTop   = document.getElementById('backToTop');

    console.log('📍 Cart:', !!cartBtn, !!cartDrawer);
    console.log('📍 Account:', !!accountBtn, !!accountMenu);

    // ═══════════════ CART ═══════════════
    function openCart() {
      if (!cartDrawer) { console.warn('❌ cartDrawer not found'); return; }
      cartDrawer.classList.add('open');
      cartDrawer.setAttribute('aria-hidden', 'false');
      if (drawerBack) drawerBack.classList.add('open');
      document.body.classList.add('locked');
      console.log('✅ Cart opened');
    }
    function closeCart() {
      if (!cartDrawer) return;
      cartDrawer.classList.remove('open');
      cartDrawer.setAttribute('aria-hidden', 'true');
      if (drawerBack) drawerBack.classList.remove('open');
      document.body.classList.remove('locked');
    }

    if (cartBtn) {
      cartBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        openCart();
      });
    } else {
      console.warn('❌ #cartBtn not found in DOM');
    }
    if (cartClose) cartClose.addEventListener('click', closeCart);
    if (drawerBack) drawerBack.addEventListener('click', closeCart);

    // ═══════════════ ACCOUNT DROPDOWN ═══════════════
    function toggleAccount(force) {
      if (!accountMenu) return;
      var willOpen = typeof force === 'boolean' ? force : !accountMenu.classList.contains('open');
      accountMenu.classList.toggle('open', willOpen);
      if (accountBtn) accountBtn.setAttribute('aria-expanded', String(willOpen));
      console.log('👤 Account toggled:', willOpen);
    }

    if (accountBtn) {
      accountBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleAccount();
      });
    } else {
      console.warn('❌ #accountBtn not found in DOM');
    }

    document.addEventListener('click', function(e) {
      if (accountMenu && accountBtn
          && !accountMenu.contains(e.target)
          && !accountBtn.contains(e.target)) {
        toggleAccount(false);
      }
    });

    // ═══════════════ MOBILE NAV ═══════════════
    function closeMobile() {
      if (!mobileNav) return;
      mobileNav.classList.remove('open');
      mobileNav.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('locked');
    }
    if (hamburger && mobileNav) {
      hamburger.addEventListener('click', function() {
        mobileNav.classList.add('open');
        mobileNav.setAttribute('aria-hidden', 'false');
        document.body.classList.add('locked');
      });
    }
    document.querySelectorAll('[data-close-mobile]').forEach(function(el) {
      el.addEventListener('click', closeMobile);
    });

    // ═══════════════ SEARCH ═══════════════
    function closeSearch() {
      if (!searchOverlay) return;
      searchOverlay.classList.remove('open');
      searchOverlay.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('locked');
    }
    if (searchBtn && searchOverlay) {
      searchBtn.addEventListener('click', function() {
        searchOverlay.classList.add('open');
        searchOverlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('locked');
        var inp = document.getElementById('searchInput');
        setTimeout(function() { inp && inp.focus(); }, 200);
      });
    }
    if (searchClose) searchClose.addEventListener('click', closeSearch);
    if (searchOverlay) {
      searchOverlay.addEventListener('click', function(e) {
        if (e.target === searchOverlay) closeSearch();
      });
    }

    // ═══════════════ ESC KEY ═══════════════
    document.addEventListener('keydown', function(e) {
      if (e.key !== 'Escape') return;
      closeCart();
      closeSearch();
      closeMobile();
      toggleAccount(false);
    });

    // ═══════════════ SCROLL HEADER ═══════════════
    function onScroll() {
      var y = window.scrollY;
      if (header) header.classList.toggle('scrolled', y > 14);
      if (backToTop) backToTop.classList.toggle('show', y > 520);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    if (backToTop) {
      backToTop.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    }

    // ═══════════════ CART QTY BUTTONS (drawer) ═══════════════
    var cartBody = document.getElementById('cartBody');
    if (cartBody) {
      cartBody.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-act]');
        if (!btn) return;
        var row = btn.closest('.cd-item');
        if (!row) return;
        var itemId = row.getAttribute('data-id');
        var act = btn.getAttribute('data-act');
        var qtyEl = row.querySelector('.qty span');
        var currentQty = qtyEl ? parseInt(qtyEl.textContent) : 1;
        var newQty = currentQty;

        if (act === 'inc') newQty = currentQty + 1;
        if (act === 'dec') newQty = Math.max(1, currentQty - 1);

        if (act === 'remove') {
          if (!confirm('Remove this item from cart?')) return;
          sendCartAction('cart_remove', { item_id: itemId });
          return;
        }
        if (qtyEl) qtyEl.textContent = newQty;
        sendCartAction('cart_update', { item_id: itemId, qty: newQty });
      });
    }

    // ═══════════════ SEND CART ACTION ═══════════════
    function sendCartAction(action, data) {
      var body = new FormData();
      body.append('action', action);
      body.append('_token', document.body.dataset.csrf || '');
      Object.keys(data).forEach(function(k) { body.append(k, data[k]); });

      fetch(window.location.pathname, {
        method: 'POST',
        body: body,
        credentials: 'same-origin'
      })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (!res.ok) { showToast(res.error || 'Error'); return; }
        var cc = document.getElementById('cartCount');
        if (cc && res.qty !== undefined) cc.textContent = res.qty;
        var st = document.getElementById('cartSubtotal');
        if (st && res.subtotal) st.textContent = res.subtotal;
        var tt = document.getElementById('cartTotal');
        if (tt && res.subtotal) tt.textContent = res.subtotal;
        var hc = document.getElementById('cartHeadCount');
        if (hc && res.qty !== undefined) hc.textContent = res.qty + (res.qty === 1 ? ' item' : ' items');
        if (res.message) showToast(res.message);
        if (action === 'cart_remove') location.reload();
      })
      .catch(function() { showToast('Network error'); });
    }

    // ═══════════════ ADD TO CART (data-add-id buttons) ═══════════════
    document.addEventListener('click', function(e) {
      var addBtn = e.target.closest('[data-add-id]');
      if (!addBtn) return;
      e.preventDefault();
      var id = addBtn.getAttribute('data-add-id');
      var name = addBtn.getAttribute('data-add-name') || 'Item';
      sendCartAction('cart_add', { product_id: id, qty: 1 });
      showToast(name + ' added to cart');
    });

    // ═══════════════ TOAST ═══════════════
    window.showToast = function(msg) {
      var wrap = document.getElementById('toastWrap');
      if (!wrap) return;
      var el = document.createElement('div');
      el.className = 'toast';
      el.innerHTML = '<span class="t-ico">✓</span><span>' + msg + '</span>';
      wrap.appendChild(el);
      setTimeout(function() {
        el.classList.add('out');
        setTimeout(function() { el.remove(); }, 380);
      }, 2600);
    };

    console.log('✅ All interactions ready');
  });
})();
</script>

<!-- ============ Language Switcher JS ============ -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  var btn  = document.getElementById('langBtn');
  var menu = document.getElementById('langMenu');
  if (!btn || !menu) return;

  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    var isOpen = menu.classList.toggle('open');
    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });

  document.addEventListener('click', function (e) {
    if (!menu.contains(e.target) && !btn.contains(e.target)) {
      menu.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      menu.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
    }
  });
});
</script>

</body>
</html>