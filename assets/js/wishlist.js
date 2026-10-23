// assets/js/wishlist.js
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.wish-btn');
  if (!btn) return;

  const productId = btn.dataset.wishId;
  if (!productId) return;

  const body = new FormData();
  body.append('action', 'wishlist_toggle');
  body.append('product_id', productId);
  body.append('_token', document.body.dataset.csrf || '');

  try {
    const res  = await fetch(window.location.pathname, { method: 'POST', body, credentials: 'same-origin' });
    const data = await res.json();

    if (data.login) { window.location.href = window.APP_URL + '/login.php'; return; }
    if (!data.ok)   { showToast(data.error); return; }

    btn.classList.toggle('active', data.active);
    const badge = document.getElementById('wishCount');
    if (badge) badge.textContent = data.count;
    showToast(data.message);
  } catch {
    showToast('Network error');
  }
});