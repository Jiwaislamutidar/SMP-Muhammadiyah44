@extends('layouts.admin')

@section('title', 'Dashboard')

@section('styles')
<link rel="stylesheet" href="{{ asset('admin css/dashboardadmin.css') }}?v=3">
@endsection

@section('content')
{{-- ===== PAGE TITLE & QUICK ACTIONS ===== --}}
<div class="dash-header">
  <div class="dash-title-group">
    <h1 class="dash-title">Dashboard</h1>
    <p class="dash-subtitle">Kehadiran & aktivitas hari ini</p>
  </div>
  <div class="dash-quick-actions">
    <a class="dash-btn-outline" href="{{ route('admin.dataguru') }}">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      + Guru
    </a>
    <a class="dash-btn-outline" href="{{ route('admin.datamurid') }}">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      + Murid
    </a>
    <a class="dash-btn-primary" href="{{ route('admin.rekapabsensi') }}">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Rekap Absensi
    </a>
  </div>
</div>

{{-- ===== KPI STRIP ===== --}}
<div class="dash-kpi-strip">
  <div class="dash-kpi dash-kpi--green">
    <div class="dash-kpi-label">Murid Hadir</div>
    <div class="dash-kpi-num">795</div>
    <div class="dash-kpi-sub">dari 842 terdaftar</div>
  </div>
  <div class="dash-kpi dash-kpi--red">
    <div class="dash-kpi-label">Murid Alfa</div>
    <div class="dash-kpi-num">15</div>
    <div class="dash-kpi-sub dash-kpi-sub--urgent">Perlu tindak lanjut</div>
  </div>
  <div class="dash-kpi dash-kpi--amber">
    <div class="dash-kpi-label">Murid Izin/Sakit</div>
    <div class="dash-kpi-num">32</div>
    <div class="dash-kpi-sub">Surat terverifikasi</div>
  </div>
  <div class="dash-kpi dash-kpi--teal">
    <div class="dash-kpi-label">Guru Hadir</div>
    <div class="dash-kpi-num">44</div>
    <div class="dash-kpi-sub">dari 48 pengajar</div>
  </div>
  <div class="dash-kpi dash-kpi--red">
    <div class="dash-kpi-label">Guru Tidak Hadir</div>
    <div class="dash-kpi-num">4</div>
    <div class="dash-kpi-sub">2 izin · 1 sakit · 1 alfa</div>
  </div>
</div>

{{-- ===== 2-COLUMN GRID ===== --}}
<div class="dash-two-col">

  {{-- Guru yang Tidak Hadir --}}
  <section class="dash-card dash-absent-card">
    <div class="dash-card-inner">
      <div class="dash-card-header">
        <div class="dash-card-title-row">
          <span class="dot dot--red"></span>
          <h2 class="dash-card-title">Guru Tidak Hadir</h2>
        </div>
        <a class="dash-link-text" href="#">Lihat Semua →</a>
      </div>

      <div class="dash-absence-list">
        {{-- Item 1 --}}
        <div class="dash-absence-item dash-absence-item--first">
          <div class="dash-absence-left">
            <span class="dash-avatar dash-avatar--green">SA</span>
            <div>
              <strong class="dash-name">Dra. Hj. Siti Aminah</strong>
              <small class="dash-role">Bahasa Indonesia</small>
            </div>
          </div>
          <div class="dash-absence-right">
            <span class="dash-status-badge dash-status-badge--amber">Izin Dinas</span>
          </div>
        </div>
        {{-- Item 2 --}}
        <div class="dash-absence-item">
          <div class="dash-absence-left">
            <span class="dash-avatar dash-avatar--red">HW</span>
            <div>
              <strong class="dash-name">Drs. Hendra Wijaya</strong>
              <small class="dash-role">IPA Terpadu</small>
            </div>
          </div>
          <div class="dash-absence-right">
            <span class="dash-status-badge dash-status-badge--red">Sakit</span>
          </div>
        </div>
        {{-- Item 3 --}}
        <div class="dash-absence-item">
          <div class="dash-absence-left">
            <span class="dash-avatar dash-avatar--blue">MR</span>
            <div>
              <strong class="dash-name">Muhammad Rizky, S.Pd.</strong>
              <small class="dash-role">PJOK</small>
            </div>
          </div>
          <div class="dash-absence-right">
            <span class="dash-status-badge dash-status-badge--blue">Izin</span>
          </div>
        </div>
        {{-- Item 4 --}}
        <div class="dash-absence-item">
          <div class="dash-absence-left">
            <span class="dash-avatar dash-avatar--red">AL</span>
            <div>
              <strong class="dash-name">Nurul Aini, S.Pd.</strong>
              <small class="dash-role">Bahasa Inggris</small>
            </div>
          </div>
          <div class="dash-absence-right">
            <span class="dash-status-badge dash-status-badge--red">Alfa</span>
          </div>
        </div>
      </div>
    </div>

    <div class="dash-card-footer">
      <span>4 pendidik berhalangan</span>
      <span class="dash-badge-ok">Pengganti siap</span>
    </div>
  </section>

  {{-- Jadwal Hari Ini --}}
  <section class="dash-card dash-schedule-card">
    <div class="dash-card-inner">
      <div class="dash-card-header">
        <div class="dash-card-title-row">
          <span class="dot dot--green"></span>
          <h2 class="dash-card-title">Jadwal Hari Ini</h2>
        </div>
        <span class="dash-muted-text">Sem. Genap 2024/2025</span>
      </div>

      <div class="dash-table-wrap">
        <table class="dash-table">
          <thead>
            <tr>
              <th>JAM</th>
              <th>MATA PELAJARAN</th>
              <th>GURU</th>
              <th>KELAS</th>
              <th class="text-right">STATUS</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="dash-td-time">07.00 – 08.20</td>
              <td><strong>Matematika</strong></td>
              <td class="dash-td-muted">Pak Ahmad Fauzi</td>
              <td><span class="dash-kelas-badge">7A</span></td>
              <td class="text-right"><span class="dash-status dash-status--done">● Selesai</span></td>
            </tr>
            <tr class="dash-row-active">
              <td class="dash-td-time dash-td-time--green">08.20 – 09.40</td>
              <td><strong class="text-green">IPA Terpadu</strong></td>
              <td class="dash-td-muted">Bu Rahayu Wulandari</td>
              <td><span class="dash-kelas-badge dash-kelas-badge--green">7A</span></td>
              <td class="text-right"><span class="dash-status dash-status--live">Berlangsung</span></td>
            </tr>
            <tr>
              <td class="dash-td-time">09.40 – 10.20</td>
              <td class="dash-td-muted">Shalat Dhuha</td>
              <td class="dash-td-muted">Ust. Hilman Nurdin</td>
              <td><span class="dash-kelas-badge">Semua 7</span></td>
              <td class="text-right"><span class="dash-status dash-status--next">Berikutnya</span></td>
            </tr>
            <tr>
              <td class="dash-td-time">10.20 – 11.40</td>
              <td><strong>Bahasa Indonesia</strong></td>
              <td class="dash-td-muted">Bu Siti Aminah <em>(Pengganti)</em></td>
              <td><span class="dash-kelas-badge">7B</span></td>
              <td class="text-right"><span class="dash-status dash-status--next">Terjadwal</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="dash-card-footer">
      <span>4 sesi kelas 7 hari ini</span>
      <a class="dash-link-text" href="{{ route('admin.jadwalpelajaran') }}">Kelola Jadwal →</a>
    </div>
  </section>

</div>
@endsection
