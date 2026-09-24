(() => {
  /* =====================================================
     ADMIN JS – Shell interactions
     Mengikuti pola guru/siswa
     ===================================================== */

  // ── Page Loader ──────────────────────────────────────
  window.addEventListener('load', () => {
    setTimeout(() => {
      const loader = document.getElementById('pageLoader');
      const app    = document.getElementById('adminApp');
      if (loader) loader.classList.add('hide');
      if (app)    app.classList.add('loaded');
    }, 500);
  });

  // ── Sidebar Toggle ────────────────────────────────────
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const burger  = document.getElementById('burgerBtn');

  window.toggleSidebar = () => {
    if (!sidebar || !overlay || !burger) return;
    const isOpen = sidebar.classList.toggle('open');
    overlay.classList.toggle('show', isOpen);
    burger.classList.toggle('active', isOpen);
    document.body.classList.toggle('menu-open', isOpen);
  };

  const closeSidebar = () => {
    if (!sidebar || !overlay || !burger) return;
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
    burger.classList.remove('active');
    document.body.classList.remove('menu-open');
  };

  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSidebar(); });
  window.addEventListener('resize',   ()  => { if (window.innerWidth > 1024) closeSidebar(); });

  // ── Live Date/Time ────────────────────────────────────
  const datetime = document.getElementById('liveDatetime');
  const days   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
  const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  const pad    = (v) => String(v).padStart(2, '0');

  const updateDatetime = () => {
    if (!datetime) return;
    const now = new Date();
    datetime.innerHTML =
      `<span class="date-html">${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}</span>` +
      `<span class="sep"> | </span>` +
      `<span class="time-html">${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())} WIB</span>`;
  };
  updateDatetime();
  window.setInterval(updateDatetime, 1000);

  // ── Toast ─────────────────────────────────────────────
  const toast = document.createElement('div');
  toast.className = 'admin-toast';
  document.body.appendChild(toast);

  const showToast = (msg) => {
    toast.textContent = msg;
    toast.classList.add('show');
    window.clearTimeout(showToast._t);
    showToast._t = window.setTimeout(() => toast.classList.remove('show'), 2600);
  };
  window.showAdminToast = showToast;

  // ── Bell notification ─────────────────────────────────
  const bell = document.querySelector('.bell-btn');
  if (bell) bell.addEventListener('click', () => showToast('Tidak ada notifikasi baru'));

  // ── Prevent # links from jumping ─────────────────────
  document.querySelectorAll('a[href="#"]').forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      showToast('Detail akan tersedia setelah data terhubung ke database');
    });
  });

  // ── Admin table search ────────────────────────────────
  document.querySelectorAll('.admin-search').forEach((search) => {
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

  // ── Admin select filter ───────────────────────────────
  document.querySelectorAll('.admin-select').forEach((select) => {
    select.addEventListener('change', () => {
      const selected = select.value.toLowerCase();
      if (!selected || selected.includes('semua')) return;
      const container = select.closest('.admin-content') || document;
      container.querySelectorAll('.admin-table tbody tr').forEach(
        (row) => row.classList.toggle('is-filtered', !row.textContent.toLowerCase().includes(selected))
      );
      showToast(`Filter diterapkan: ${select.value}`);
    });
  });

  // ── Admin pagination ──────────────────────────────────
  document.querySelectorAll('.admin-pagination').forEach((pagination) => {
    pagination.querySelectorAll('button').forEach((btn) => {
      btn.addEventListener('click', () => {
        pagination.querySelectorAll('button').forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
        showToast(`Halaman ${btn.textContent.trim()} dipilih`);
      });
    });
  });

  // ── Admin action buttons ──────────────────────────────
  document.querySelectorAll('.admin-action').forEach((btn) => {
    btn.addEventListener('click', () => showToast(`${btn.textContent.trim().replace(/^\+\s*/, '')} siap digunakan`));
  });

})();
