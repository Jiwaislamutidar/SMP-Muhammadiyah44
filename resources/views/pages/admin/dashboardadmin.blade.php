@extends('layouts.admin')

@section('title', 'Dashboard')

@section('styles')
<link rel="stylesheet" href="{{ asset('admin css/dashboardadmin.css') }}?v=2">
@endsection

@section('content')
{{-- ===== PAGE TITLE & QUICK ACTIONS ===== --}}
<div class="dash-header">
  <div class="dash-title-group">
    <h1 class="dash-title">Dashboard</h1>
    <p class="dash-subtitle">Ringkasan aktivitas dan kehadiran hari ini</p>
  </div>
  <div class="dash-quick-actions">
    <a class="dash-btn-outline" href="{{ route('admin.dataguru') }}">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      + Tambah Guru
    </a>
    <a class="dash-btn-outline" href="{{ route('admin.datamurid') }}">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      + Tambah Murid
    </a>
    <a class="dash-btn-primary" href="{{ route('admin.rekapabsensi') }}">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Lihat Rekap Absensi
    </a>
  </div>
</div>

{{-- ===== SECTION 1 – RINGKASAN KEHADIRAN ===== --}}
<section class="dash-card">
  <div class="dash-card-header">
    <div class="dash-card-title-row">
      <span class="dash-accent-bar"></span>
      <h2 class="dash-card-title">Ringkasan Kehadiran Hari Ini</h2>
    </div>
    <span class="dash-pill-badge">Sinkronisasi otomatis gerbang &amp; sesi kelas</span>
  </div>

  <div class="dash-attendance-groups">
    {{-- Grup Murid --}}
    <div class="dash-group">
      <div class="dash-group-header">
        <div class="dash-group-label">
          <span class="dot dot--green-light"></span>
          <h3 class="dash-group-title">Murid</h3>
        </div>
        <span class="dash-group-total">Total Terdaftar: <strong>842 Siswa</strong></span>
      </div>
      <div class="dash-metrics">
        <div class="dash-metric">
          <span class="dash-metric-label">Hadir</span>
          <strong class="dash-metric-num dash-metric-num--green">795</strong>
          <span class="dash-metric-sub dash-metric-sub--green">94.4% Tepat Waktu</span>
        </div>
        <div class="dash-metric">
          <span class="dash-metric-label">Izin / Sakit</span>
          <strong class="dash-metric-num dash-metric-num--amber">32</strong>
          <span class="dash-metric-sub">Surat Terverifikasi</span>
        </div>
        <div class="dash-metric">
          <span class="dash-metric-label">Alfa</span>
          <strong class="dash-metric-num dash-metric-num--red">15</strong>
          <span class="dash-metric-sub dash-metric-sub--red">Perlu Konfirmasi</span>
        </div>
      </div>
    </div>

    {{-- Grup Guru --}}
    <div class="dash-group">
      <div class="dash-group-header">
        <div class="dash-group-label">
          <span class="dot dot--green"></span>
          <h3 class="dash-group-title">Guru &amp; Tenaga Pengajar</h3>
        </div>
        <span class="dash-group-total">Total Pengajar: <strong>48 Pendidik</strong></span>
      </div>
      <div class="dash-metrics">
        <div class="dash-metric">
          <span class="dash-metric-label">Hadir</span>
          <strong class="dash-metric-num dash-metric-num--green">44</strong>
          <span class="dash-metric-sub dash-metric-sub--green">91.6% Mengajar</span>
        </div>
        <div class="dash-metric">
          <span class="dash-metric-label">Izin / Dinas</span>
          <strong class="dash-metric-num dash-metric-num--amber">2</strong>
          <span class="dash-metric-sub">Disposisi Resmi</span>
        </div>
        <div class="dash-metric">
          <span class="dash-metric-label">Sakit / Alfa</span>
          <strong class="dash-metric-num dash-metric-num--red">2</strong>
          <span class="dash-metric-sub">1 Sakit, 1 Mengganti</span>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ===== SECTION 2 & 3 – 2-COLUMN GRID ===== --}}
<div class="dash-two-col">

  {{-- Guru yang Tidak Hadir (5 cols) --}}
  <section class="dash-card dash-absent-card">
    <div class="dash-card-inner">
      <div class="dash-card-header">
        <div class="dash-card-title-row">
          <span class="dot dot--red"></span>
          <h2 class="dash-card-title">Guru yang Tidak Hadir</h2>
        </div>
        <a class="dash-link-text" href="#">Lihat Semua →</a>
      </div>
      <p class="dash-desc">Pendidik yang berhalangan hadir pada tanggal 24 Maret 2025:</p>

      <div class="dash-absence-list">
        {{-- Item 1 --}}
        <div class="dash-absence-item dash-absence-item--first">
          <div class="dash-absence-left">
            <span class="dash-avatar dash-avatar--green">SA</span>
            <div>
              <strong class="dash-name">Dra. Hj. Siti Aminah, M.Pd.</strong>
              <small class="dash-role">Guru Bahasa Indonesia</small>
            </div>
          </div>
          <div class="dash-absence-right">
            <span class="dash-status-badge dash-status-badge--amber">Izin Dinas</span>
            <span class="dash-status-note">Disposisi No. 420/SMPM</span>
          </div>
        </div>
        {{-- Item 2 --}}
        <div class="dash-absence-item">
          <div class="dash-absence-left">
            <span class="dash-avatar dash-avatar--red">HW</span>
            <div>
              <strong class="dash-name">Drs. Hendra Wijaya</strong>
              <small class="dash-role">Guru IPA Terpadu</small>
            </div>
          </div>
          <div class="dash-absence-right">
            <span class="dash-status-badge dash-status-badge--red">Sakit</span>
            <span class="dash-status-note">Surat Dokter terlampir</span>
          </div>
        </div>
        {{-- Item 3 --}}
        <div class="dash-absence-item">
          <div class="dash-absence-left">
            <span class="dash-avatar dash-avatar--blue">MR</span>
            <div>
              <strong class="dash-name">Muhammad Rizky, S.Pd.</strong>
              <small class="dash-role">Guru PJOK</small>
            </div>
          </div>
          <div class="dash-absence-right">
            <span class="dash-status-badge dash-status-badge--blue">Izin</span>
            <span class="dash-status-note">Pelatihan MGMP Kota</span>
          </div>
        </div>
        {{-- Item 4 --}}
        <div class="dash-absence-item">
          <div class="dash-absence-left">
            <span class="dash-avatar dash-avatar--red">AL</span>
            <div>
              <strong class="dash-name">Nurul Aini, S.Pd.</strong>
              <small class="dash-role">Guru Bahasa Inggris</small>
            </div>
          </div>
          <div class="dash-absence-right">
            <span class="dash-status-badge dash-status-badge--red">Alfa</span>
            <span class="dash-status-note">Belum ada konfirmasi</span>
          </div>
        </div>
      </div>
    </div>

    <div class="dash-card-footer">
      <span>Menampilkan 4 dari 4 pendidik berhalangan</span>
      <span class="dash-link-text">Semua jadwal guru pengganti telah siap</span>
    </div>
  </section>

  {{-- Jadwal Hari Ini (7 cols) --}}
  <section class="dash-card dash-schedule-card">
    <div class="dash-card-inner">
      <div class="dash-card-header">
        <div class="dash-card-title-row">
          <span class="dot dot--green"></span>
          <h2 class="dash-card-title">Jadwal Hari Ini</h2>
        </div>
        <span class="dash-muted-text">Semester Genap TA 2024/2025</span>
      </div>

      <div class="dash-table-wrap">
        <table class="dash-table">
          <thead>
            <tr>
              <th>JAM</th>
              <th>MATA PELAJARAN</th>
              <th>GURU PENGAMPU</th>
              <th>KELAS</th>
              <th class="text-right">STATUS</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="dash-td-time">07.00 – 08.20</td>
              <td><strong>Matematika</strong></td>
              <td class="dash-td-muted">Pak Ahmad Fauzi, S.Pd.</td>
              <td><span class="dash-kelas-badge">7A</span></td>
              <td class="text-right"><span class="dash-status dash-status--done">● Selesai</span></td>
            </tr>
            <tr class="dash-row-active">
              <td class="dash-td-time dash-td-time--green">08.20 – 09.40</td>
              <td><strong class="text-green">IPA Terpadu</strong></td>
              <td class="dash-td-muted">Bu Rahayu Wulandari, S.Pd.</td>
              <td><span class="dash-kelas-badge dash-kelas-badge--green">7A</span></td>
              <td class="text-right"><span class="dash-status dash-status--live">Berlangsung</span></td>
            </tr>
            <tr>
              <td class="dash-td-time">09.40 – 10.20</td>
              <td class="dash-td-muted">Pembiasaan Shalat Dhuha</td>
              <td class="dash-td-muted">Ustadz Hilman Nurdin, S.Ag.</td>
              <td><span class="dash-kelas-badge">Semua 7</span></td>
              <td class="text-right"><span class="dash-status dash-status--next">Berikutnya</span></td>
            </tr>
            <tr>
              <td class="dash-td-time">10.20 – 11.40</td>
              <td><strong>Bahasa Indonesia</strong></td>
              <td class="dash-td-muted">Bu Siti Aminah (Guru Pengganti)</td>
              <td><span class="dash-kelas-badge">7B</span></td>
              <td class="text-right"><span class="dash-status dash-status--next">Terjadwal</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="dash-card-footer">
      <span>Menampilkan 4 sesi utama kelas 7 hari ini</span>
      <a class="dash-link-text" href="#">Kelola Jadwal Lengkap →</a>
    </div>
  </section>

</div>
@endsection
