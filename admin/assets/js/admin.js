(function () {
  'use strict';

  const sidebar  = document.getElementById('adminSidebar');
  const backdrop = document.getElementById('sbBackdrop');
  const toggle   = document.getElementById('menuToggle');

  function toggleSidebar(force) {
    if (!sidebar) return;
    const willOpen = typeof force === 'boolean'
      ? force
      : !sidebar.classList.contains('show');
    sidebar.classList.toggle('show', willOpen);
    backdrop?.classList.toggle('show', willOpen);
  }

  toggle?.addEventListener('click', () => toggleSidebar());
  backdrop?.addEventListener('click', () => toggleSidebar(false));

  // Close sidebar when clicking a link on mobile
  document.querySelectorAll('.sidebar .nav-link').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth < 992) toggleSidebar(false);
    });
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') toggleSidebar(false);
  });

  // Auto-hide flash messages
  document.querySelectorAll('.flash').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'all .5s ease';
      el.style.opacity = '0';
      el.style.transform = 'translateY(-8px)';
      setTimeout(() => el.remove(), 500);
    }, 4000);
  });

  // Confirm delete
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  // Image preview
  document.querySelectorAll('[data-image-input]').forEach(input => {
    input.addEventListener('change', () => {
      const target = document.getElementById(input.getAttribute('data-image-input'));
      if (!target) return;
      const file = input.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = e => {
        target.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
      };
      reader.readAsDataURL(file);
    });
  });
})();