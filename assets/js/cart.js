// assets/js/cart.js

function showToast(msg) {
  const wrap = document.getElementById('toastWrap');
  if (!wrap) return;
  const el = document.createElement('div');
  el.className = 'toast';
  el.innerHTML = '<span class="t-ico">✓</span><span>' + msg + '</span>';
  wrap.appendChild(el);
  setTimeout(() => { el.classList.add('out'); setTimeout(() => el.remove(), 380); }, 2600);
}

function csrfToken() {
  return document.body.dataset.csrf || window.CSRF_TOKEN || '';
}

async function postAction(payload) {
  const body = new FormData();
  Object.entries(payload).forEach(([k, v]) => body.append(k, v));
  body.append('_token', csrfToken());

  // Post to current page URL — no separate ajax folder
  const res = await fetch(window.location.pathname, {
    method: 'POST',
    body,
    credentials: 'same-origin'
  });
  return res.json();
}

async function addToCart(productId, qty = 1) {
  try {
    const data = await postAction({ action: 'cart_add', product_id: productId, qty });
    if (!data.ok) { showToast(data.error || 'Something went wrong'); return; }
    document.getElementById('cartCount').textContent = data.qty;
    const sub = document.getElementById('cartSubtotal');
    if (sub) sub.textContent = data.subtotal;
    showToast(data.message);
  } catch (e) {
    showToast('Network error');
  }
}

document.addEventListener('click', (e) => {
  const addBtn = e.target.closest('[data-add-to-cart]');
  if (addBtn) {
    addToCart(addBtn.dataset.productId, 1);
  }
});

/* ── Cart drawer open/close ── */
document.addEventListener('DOMContentLoaded', () => {
  const cartBtn      = document.getElementById('cartBtn');
  const cartClose    = document.getElementById('cartClose');
  const cartDrawer   = document.getElementById('cartDrawer');
  const drawerBack   = document.getElementById('drawerBackdrop');

  const openCart = () => {
    cartDrawer?.classList.add('open');
    drawerBack?.classList.add('open');
    document.body.classList.add('locked');
  };
  const closeCart = () => {
    cartDrawer?.classList.remove('open');
    drawerBack?.classList.remove('open');
    document.body.classList.remove('locked');
  };

  cartBtn?.addEventListener('click', openCart);
  cartClose?.addEventListener('click', closeCart);
  drawerBack?.addEventListener('click', closeCart);
});