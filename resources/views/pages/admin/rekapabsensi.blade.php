@extends('layouts.admin')
@section('title', 'Rekap Absensi')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/rekapabsensi.css') }}?v=3">@endsection
@section('content')

<div class="admin-page-header">
  <div>
    <h1>Rekap Absensi</h1>
    <p>Pantau kehadiran guru &amp; murid harian</p>
  </div>
  <div class="admin-header-actions">
    <button class="admin-action admin-action-outline" type="button" onclick="showAdminToast('Menampilkan data hari ini')">Hari Ini</button>
    <button class="admin-action" type="button" onclick="showAdminToast('Menyiapkan file ekspor rekap...')">↓ Export Rekap</button>
  </div>
</div>

{{-- KPI summary strip --}}
<div class="summary-strip">
  <div class="summary-item summary-item--green">
    <span>Murid Hadir</span>
    <strong style="color:#087443">795</strong>
  </div>
  <div class="summary-item summary-item--amber">
    <span>Izin / Sakit</span>
    <strong style="color:#d97706">32</strong>
  </div>
  <div class="summary-item summary-item--red">
    <span>Alfa (Tanpa Keterangan)</span>
    <strong style="color:#dc2626">15</strong>
  </div>
</div>

<form class="admin-toolbar" method="GET">
  <input class="admin-search" name="search" placeholder="Cari nama atau NISN murid..." aria-label="Cari murid">
  <select class="admin-select" name="kelas">
    <option value="">Semua Kelas</option>
    <option>7A</option><option>7B</option><option>8A</option><option>8B</option><option>9A</option><option>9B</option>
  </select>
  <select class="admin-select" name="status">
    <option value="">Semua Status</option>
    <option>Hadir</option><option>Izin</option><option>Alfa</option>
  </select>
  <button class="admin-filter-submit" type="submit">Filter</button>
</form>

<section class="panel admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Murid</th>
        <th>NISN</th>
        <th>Kelas</th>
        <th>Status</th>
        <th>Waktu Presensi</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @foreach ([
        ['Ahmad Fauzan',   '00654321', '7A', 'Hadir', '06:52 WIB', ''],
        ['Muhammad Rizky', '00654322', '7A', 'Hadir', '06:55 WIB', ''],
        ['Fahri Ramadhan', '00654323', '7B', 'Izin',  '-',          'amber'],
        ['Aisyah Putri',   '00654324', '7B', 'Hadir', '06:58 WIB', ''],
        ['Nabila Zahra',   '00654325', '8A', 'Alfa',  '-',          'red'],
        ['Raka Pratama',   '00654326', '8A', 'Hadir', '07:01 WIB', ''],
        ['Fikri Maulana',  '00654327', '8B', 'Hadir', '06:49 WIB', ''],
        ['Siti Aulia',     '00654328', '8B', 'Izin',  '-',          'amber'],
      ] as $absen)
        <tr>
          <td>
            <div class="rekap-identity">
              <span class="initial">{{ collect(explode(' ', $absen[0]))->map(fn($w) => substr($w, 0, 1))->join('') }}</span>
              <strong>{{ $absen[0] }}</strong>
            </div>
          </td>
          <td><span style="font-variant-numeric:tabular-nums; font-weight:600; color:#475569;">{{ $absen[1] }}</span></td>
          <td><span class="badge blue">{{ $absen[2] }}</span></td>
          <td><span class="badge {{ $absen[5] }}">{{ $absen[3] }}</span></td>
          <td>
            <span class="rekap-time {{ $absen[4] === '-' ? 'rekap-time--empty' : '' }}">{{ $absen[4] }}</span>
          </td>
          <td><button class="link-action" type="button" onclick="showAdminToast('Detail presensi {{ $absen[0] }}')">Detail</button></td>
        </tr>
      @endforeach
    </tbody>
  </table>
  <div class="admin-pagination">
    <span>Menampilkan 1–8 dari 842 data presensi</span>
    <div class="admin-page-links">
      <span class="disabled" aria-disabled="true">‹</span>
      <span>Halaman 1 dari 106</span>
      <button type="button" onclick="showAdminToast('Halaman 2')" aria-label="Halaman berikutnya">›</button>
    </div>
  </div>
</section>

@endsection
