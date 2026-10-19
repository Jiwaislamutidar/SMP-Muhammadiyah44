(() => {
  /* ==============================================
     ADMIN JS – Shell interactions
     ============================================== */

  // ── Page Loader ──────────────────────────────
  window.addEventListener('load', () => {
    setTimeout(() => {
      const loader = document.getElementById('pageLoader');
      const app    = document.getElementById('adminApp');
      if (loader) loader.classList.add('hide');
      if (app)    app.classList.add('loaded');
    }, 400);
  });

  // ── Sidebar Toggle ────────────────────────────
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebarOverlay');
  const burger   = document.getElementById('burgerBtn');
  const DESKTOP  = () => window.innerWidth >= 1100;

  window.toggleSidebar = () => {
    if (!sidebar || DESKTOP()) return;
    const isOpen = sidebar.classList.toggle('open');
    overlay && overlay.classList.toggle('show', isOpen);
    burger  && burger.classList.toggle('active', isOpen);
    document.body.classList.toggle('menu-open', isOpen);
  };

  const closeSidebar = () => {
    if (!sidebar || DESKTOP()) return;
    sidebar.classList.remove('open');
    overlay && overlay.classList.remove('show');
    burger  && burger.classList.remove('active');
    document.body.classList.remove('menu-open');
  };

  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSidebar(); });
  window.addEventListener('resize', () => { if (DESKTOP()) closeSidebar(); });

  // ── Live Date/Time ────────────────────────────
  const datetime = document.getElementById('liveDatetime');
  const days   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
  const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
  const pad    = (v) => String(v).padStart(2, '0');

  const updateDatetime = () => {
    if (!datetime) return;
    const now = new Date();
    datetime.innerHTML =
      `<span class="date-html">${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}</span>` +
      `<span class="sep"> | </span>` +
      `<span class="time-html">${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}</span>`;
  };
  updateDatetime();
  window.setInterval(updateDatetime, 1000);

  // ── Toast ─────────────────────────────────────
  const toast = document.createElement('div');
  toast.className = 'admin-toast';
  document.body.appendChild(toast);

  const showToast = (msg, type = '') => {
    toast.textContent = msg;
    toast.className = 'admin-toast' + (type ? ' toast--' + type : '');
    toast.classList.add('show');
    window.clearTimeout(showToast._t);
    showToast._t = window.setTimeout(() => toast.classList.remove('show'), 2800);
  };
  window.showAdminToast = showToast;

  // ── Bell notification ─────────────────────────
  const bell = document.querySelector('.bell-btn');
  if (bell) bell.addEventListener('click', () => showToast('Tidak ada notifikasi baru'));

  // ── Prevent # links from jumping ─────────────
  document.querySelectorAll('a[href="#"]').forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      showToast('Fitur ini akan tersedia setelah terhubung ke database');
    });
  });

  // ── Admin table search ────────────────────────
  // Only run client-side search on inputs WITHOUT a surrounding form that has server action
  document.querySelectorAll('.admin-search').forEach((search) => {
    // If the input is inside a form that does a real GET request, skip client-side filter
    const form = search.closest('form');
    if (form && (form.method || form.action)) return;

    search.addEventListener('input', () => {
      const q = search.value.trim().toLowerCase();
      const container = search.closest('.admin-content') || document;
      container.querySelectorAll('.admin-table tbody tr').forEach(
        (row) => row.classList.toggle('is-filtered', q.length > 0 && !row.textContent.toLowerCase().includes(q))
      );
      container.querySelectorAll('.subject-card').forEach(
        (card) => card.classList.toggle('is-filtered', q.length > 0 && !card.textContent.toLowerCase().includes(q))
      );
    });
  });


  // ── Admin pagination (stub pages) ────────────
  document.querySelectorAll('.admin-pagination').forEach((pagination) => {
    pagination.querySelectorAll('button').forEach((btn) => {
      btn.addEventListener('click', () => {
        pagination.querySelectorAll('button').forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
        showToast(`Halaman ${btn.textContent.trim()}`);
      });
    });
  });

})();
