// assets/js/search.js
document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('searchOverlay');
  const input   = document.getElementById('searchInput');
  const suggest = document.getElementById('searchSuggest');
  const openBtn = document.getElementById('searchBtn');
  const closeBtn= document.getElementById('searchClose');

  let timer;

  const openSearch = () => {
    overlay?.classList.add('open');
    overlay?.setAttribute('aria-hidden', 'false');
    document.body.classList.add('locked');
    setTimeout(() => input?.focus(), 240);
  };
  const closeSearch = () => {
    overlay?.classList.remove('open');
    overlay?.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('locked');
    if (input) input.value = '';
    if (suggest) suggest.innerHTML = '';
  };

  openBtn?.addEventListener('click', openSearch);
  closeBtn?.addEventListener('click', closeSearch);
  overlay?.addEventListener('click', e => { if (e.target === overlay) closeSearch(); });

  input?.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();
    if (q.length < 2) { suggest.innerHTML = ''; return; }

    timer = setTimeout(async () => {
      const params = new URLSearchParams({ action: 'search', q });
      try {
        const res  = await fetch(window.location.pathname + '?' + params.toString());
        const data = await res.json();
        if (!data.ok || !data.results.length) {
          suggest.innerHTML = '<div class="ss-empty">No results found</div>';
          return;
        }
        suggest.innerHTML = data.results.map(r => `
          <a href="${r.url}" class="ss-item">
            <span class="ss-ico">${r.icon}</span>
            <span><b>${escapeHtml(r.title)}</b><span>${escapeHtml(r.sub)}</span></span>
            <span class="ss-tag">${escapeHtml(r.tag)}</span>
          </a>`).join('');
      } catch {
        suggest.innerHTML = '<div class="ss-empty">Search unavailable</div>';
      }
    }, 280);
  });

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSearch(); });
});