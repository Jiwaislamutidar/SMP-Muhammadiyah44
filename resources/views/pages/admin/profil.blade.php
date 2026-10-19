@extends('layouts.admin')
@section('title', 'Pengaturan')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/profil.css') }}?v=3">@endsection
@section('content')

<div class="admin-page-header">
  <div>
    <h1>Pengaturan</h1>
    <p>Konfigurasi sistem presensi &amp; institusi</p>
  </div>
</div>

<div class="settings-grid">
  <nav class="settings-menu">
    <button class="active" type="button" data-tab="tab-institusi">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/><path d="M14 22v-4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v4"/><path d="M18 5v17"/><path d="M6 5v17"/><circle cx="12" cy="9" r="2"/></svg>
      Profil Sekolah
    </button>
    <button type="button" data-tab="tab-presensi">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Jam Presensi
    </button>
    <button type="button" data-tab="tab-akun">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      Akun Admin
    </button>
  </nav>

  <section class="settings-card">
    {{-- Panel 1: Profil Sekolah --}}
    <div class="settings-panel active" id="tab-institusi">
      <h2>Profil Sekolah</h2>
      <p>Data institusi yang dicantumkan pada portal dan laporan.</p>
      <form class="form-stack" onsubmit="event.preventDefault(); showAdminToast('Perubahan profil berhasil disimpan!');">
        <div class="form-field">
          <label>Nama Sekolah</label>
          <input value="SMP Muhammadiyah 44" required>
        </div>
        <div class="form-field">
          <label>NPSN</label>
          <input value="20603412" required>
        </div>
        <div class="form-field">
          <label>Kota / Wilayah</label>
          <input value="Tangerang Selatan, Banten" required>
        </div>
        <div class="form-field">
          <label>Tahun Ajaran Aktif</label>
          <select class="admin-select">
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

    {{-- Panel 2: Jam Presensi --}}
    <div class="settings-panel" id="tab-presensi">
      <h2>Konfigurasi Jam Presensi</h2>
      <p>Batas waktu kehadiran murid &amp; guru harian.</p>
      <form class="form-stack" onsubmit="event.preventDefault(); showAdminToast('Jadwal presensi berhasil diperbarui!');">
        <div class="form-field">
          <label>Jam Masuk Standar</label>
          <input type="time" value="07:00" required>
        </div>
        <div class="form-field">
          <label>Batas Toleransi Keterlambatan</label>
          <input type="time" value="07:15" required>
        </div>
        <div class="form-field">
          <label>Jam Kepulangan</label>
          <input type="time" value="14:30" required>
        </div>
        <div class="form-actions">
          <button class="admin-action" type="submit">Simpan Jadwal</button>
        </div>
      </form>
    </div>

    {{-- Panel 3: Akun Admin --}}
    <div class="settings-panel" id="tab-akun">
      <h2>Keamanan Akun Admin</h2>
      <p>Kelola kredensial login akun administrator.</p>
      <form class="form-stack" onsubmit="event.preventDefault(); showAdminToast('Kata sandi berhasil diubah!');">
        <div class="form-field">
          <label>Email Administrator</label>
          <input type="email" value="admin@smpm44.sch.id" required>
        </div>
        <div class="form-field">
          <label>Kata Sandi Baru</label>
          <input type="password" placeholder="Minimal 8 karakter">
        </div>
        <div class="form-field">
          <label>Konfirmasi Kata Sandi</label>
          <input type="password" placeholder="Ulangi kata sandi baru">
        </div>
        <div class="form-actions">
          <button class="admin-action" type="submit">Perbarui Akun</button>
        </div>
      </form>
    </div>
  </section>
</div>

<script>
(() => {
  const tabs = document.querySelectorAll('.settings-menu button');
  const panels = document.querySelectorAll('.settings-panel');

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      tabs.forEach((t) => t.classList.remove('active'));
      panels.forEach((p) => p.classList.remove('active'));

      tab.classList.add('active');
      const target = document.getElementById(tab.dataset.tab);
      if (target) target.classList.add('active');
    });
  });
})();
</script>

@endsection
