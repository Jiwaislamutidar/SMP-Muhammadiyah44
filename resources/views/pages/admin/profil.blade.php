@extends('layouts.admin')
@section('title', 'Pengaturan')
@section('styles')<link rel="stylesheet" href="{{ asset('admin-css-baru/profil.css') }}?v=7">@endsection
@section('content')

<div class="admin-page-header">
  <div>
    <h1>Pengaturan</h1>
    <p>Konfigurasi sistem presensi &amp; institusi</p>
  </div>
</div>

<!-- Notif Toast Melayang di Atas Card (Durasi 2 Detik) -->
<div class="admin-top-toast" id="adminTopToast">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
  <span id="toastMessage">Perubahan berhasil disimpan!</span>
</div>

<div class="settings-grid">
  <nav class="settings-menu" id="settingsMenu">
    <div class="settings-indicator" id="settingsIndicator"></div>

    <button class="active" type="button" data-tab="tab-institusi">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/><path d="M14 22v-4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v4"/><path d="M18 5v17"/><path d="M6 5v17"/><circle cx="12" cy="9" r="2"/></svg>
      <span>Profil Sekolah</span>
    </button>
    <button type="button" data-tab="tab-presensi">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      <span>Jam Presensi</span>
    </button>
    <button type="button" data-tab="tab-akun">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      <span>Akun Admin</span>
    </button>
  </nav>

  <section class="settings-card">
    {{-- Panel 1: Profil Sekolah --}}
    <div class="settings-panel active" id="tab-institusi">
      <h2>Profil Sekolah</h2>
      <p>Data institusi yang dicantumkan pada portal dan laporan.</p>
      <form class="form-stack" id="formInstitusi" onsubmit="handleGeneralSubmit(event, 'Profil sekolah berhasil diperbarui!')">
        <div class="form-field">
          <label>Nama Sekolah</label>
          <input name="nama_sekolah" value="SMP Muhammadiyah 44" required>
        </div>
        <div class="form-field">
          <label>NPSN</label>
          <input name="npsn" value="20603412" required>
        </div>
        <div class="form-field">
          <label>Kota / Wilayah</label>
          <input name="wilayah" value="Tangerang Selatan, Banten" required>
        </div>
        <div class="form-field">
          <label>Tahun Ajaran Aktif</label>
          <select name="tahun_ajaran" class="admin-select">
            <option selected>2026/2027 - Semester Genap</option>
            <option>2026/2027 - Semester Ganjil</option>
            <option>2025/2026 - Semester Genap</option>
          </select>
        </div>
        <div class="form-actions">
          <button class="admin-action" type="submit">Simpan Profil</button>
        </div>
      </form>
    </div>

    {{-- Panel 2: Jam Presensi (Terhubung ke Database $setting) --}}
    <div class="settings-panel" id="tab-presensi">
      <h2>Konfigurasi Jam Presensi</h2>
      <p>Batas waktu kehadiran murid &amp; guru harian.</p>
      <form class="form-stack" id="formPresensi" action="{{ route('admin.pengaturan.presensi') }}" method="POST">
        @csrf
        <div class="form-field">
          <label>Jam Masuk Standar</label>
          <input type="time" name="jam_masuk" value="{{ old('jam_masuk', $setting->jam_masuk ?? '07:00') }}" required>
        </div>
        <div class="form-field">
          <label>Batas Toleransi Keterlambatan</label>
          <input type="time" name="jam_toleransi" value="{{ old('jam_toleransi', $setting->jam_toleransi ?? '07:15') }}" required>
        </div>

        <div class="form-field-group">
          <label class="group-label">Jadwal Jam Kepulangan (Senin – Jumat)</label>
          <div class="pulang-days-grid">
            <div class="form-field">
              <label>Senin</label>
              <input type="time" name="jam_pulang_senin" value="{{ old('jam_pulang_senin', $setting->jam_pulang_senin ?? '14:30') }}" required>
            </div>
            <div class="form-field">
              <label>Selasa</label>
              <input type="time" name="jam_pulang_selasa" value="{{ old('jam_pulang_selasa', $setting->jam_pulang_selasa ?? '13:00') }}" required>
            </div>
            <div class="form-field">
              <label>Rabu</label>
              <input type="time" name="jam_pulang_rabu" value="{{ old('jam_pulang_rabu', $setting->jam_pulang_rabu ?? '14:30') }}" required>
            </div>
            <div class="form-field">
              <label>Kamis</label>
              <input type="time" name="jam_pulang_kamis" value="{{ old('jam_pulang_kamis', $setting->jam_pulang_kamis ?? '13:00') }}" required>
            </div>
            <div class="form-field">
              <label>Jumat</label>
              <input type="time" name="jam_pulang_jumat" value="{{ old('jam_pulang_jumat', $setting->jam_pulang_jumat ?? '12:30') }}" required>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button class="admin-action" type="submit">Simpan Jadwal</button>
        </div>
      </form>
    </div>

    {{-- Panel 3: Akun Admin (Username & Password) --}}
    <div class="settings-panel" id="tab-akun">
      <h2>Keamanan Akun Admin</h2>
      <p>Kelola kredensial login akun administrator.</p>
      <form class="form-stack" id="formAkun" action="{{ route('admin.pengaturan.akun') }}" method="POST">
        @csrf
        <div class="form-field">
          <label>Username Administrator</label>
          <input type="text" name="username" value="{{ old('username', Auth::user()->username ?? 'admin') }}" required placeholder="Masukkan username login">
        </div>

        <div class="form-field">
          <label>Kata Sandi Saat Ini</label>
          <div class="password-input-wrapper">
            <input type="password" name="current_password" placeholder="Masukkan kata sandi lama" required>
            <button type="button" class="toggle-password-btn" aria-label="Lihat kata sandi">
              <svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <div class="form-field">
          <label>Kata Sandi Baru</label>
          <div class="password-input-wrapper">
            <input type="password" name="new_password" placeholder="Minimal 8 karakter">
            <button type="button" class="toggle-password-btn" aria-label="Lihat kata sandi">
              <svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <div class="form-field">
          <label>Konfirmasi Kata Sandi</label>
          <div class="password-input-wrapper">
            <input type="password" name="new_password_confirmation" placeholder="Ulangi kata sandi baru">
            <button type="button" class="toggle-password-btn" aria-label="Lihat kata sandi">
              <svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <div class="form-actions">
          <button class="admin-action" type="submit">Perbarui Akun</button>
        </div>
      </form>
    </div>
  </section>
</div>

<script>
  // Fungsi Toast Melayang di Atas Card (Durasi 2 Detik)
  function showTopToast(message) {
    const toast = document.getElementById('adminTopToast');
    const msgSpan = document.getElementById('toastMessage');
    msgSpan.textContent = message;
    
    toast.classList.add('show');
    setTimeout(() => {
      toast.classList.remove('show');
    }, 2000);
  }

  // Handle Form Umum (Profil Sekolah)
  function handleGeneralSubmit(event, successMessage) {
    event.preventDefault();
    showTopToast(successMessage);
  }

  // Handle Form AJAX (Presensi & Akun) ke Backend
  document.querySelectorAll('#formPresensi, #formAkun').forEach(form => {
    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      const actionUrl = this.getAttribute('action');

      try {
        const response = await fetch(actionUrl, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
          },
          body: formData
        });

        const result = await response.json();
        if (response.ok && result.success) {
          showTopToast(result.message);
          if (form.id === 'formAkun') {
            form.reset();
          }
        } else {
          showTopToast(result.message || 'Terjadi kesalahan saat menyimpan.');
        }
      } catch (err) {
        showTopToast('Gagal terhubung ke server.');
      }
    });
  });

  // Script Sliding Indicator & Toggle Password
  (() => {
    const menu = document.getElementById('settingsMenu');
    const indicator = document.getElementById('settingsIndicator');
    const tabs = menu.querySelectorAll('button');
    const panels = document.querySelectorAll('.settings-panel');

    function moveIndicator(activeTab) {
      if (!activeTab || !indicator) return;
      const isMobile = window.innerWidth <= 860;
      
      if (isMobile) {
        indicator.style.top = `${activeTab.offsetTop}px`;
        indicator.style.left = `${activeTab.offsetLeft}px`;
        indicator.style.width = `${activeTab.offsetWidth}px`;
        indicator.style.height = `${activeTab.offsetHeight}px`;
      } else {
        indicator.style.top = `${activeTab.offsetTop}px`;
        indicator.style.left = '12px';
        indicator.style.width = `calc(100% - 24px)`;
        indicator.style.height = `${activeTab.offsetHeight}px`;
      }
    }

    const activeTab = menu.querySelector('button.active');
    if (activeTab) setTimeout(() => moveIndicator(activeTab), 50);

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        tabs.forEach((t) => t.classList.remove('active'));
        panels.forEach((p) => p.classList.remove('active'));

        tab.classList.add('active');
        moveIndicator(tab);

        const target = document.getElementById(tab.dataset.tab);
        if (target) target.classList.add('active');
      });
    });

    window.addEventListener('resize', () => {
      const currentActive = menu.querySelector('button.active');
      if (currentActive) moveIndicator(currentActive);
    });

    // Toggle Password Visibility
    document.querySelectorAll('.toggle-password-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        const input = btn.previousElementSibling;
        const isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');

        if (isPassword) {
          btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>`;
        } else {
          btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>`;
        }
      });
    });
  })();
</script>

@endsection