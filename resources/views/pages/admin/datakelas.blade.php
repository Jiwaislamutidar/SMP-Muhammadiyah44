@extends('layouts.admin')
@section('title', 'Data Kelas')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/datakelas.css') }}?v=3">@endsection
@section('content')

<div class="admin-page-header">
  <div>
    <h1>Data Kelas</h1>
    <p>Kelola rombongan belajar &amp; wali kelas</p>
  </div>
  <button class="admin-action" type="button" onclick="showAdminToast('Fitur Tambah Kelas siap digunakan setelah sinkronisasi')">+ Tambah Kelas</button>
</div>

@php
  $rawKelas = [
    ['7A', 'Kelas 7A', 'Tingkat VII', 'Ahmad Fauzi, S.Pd.',    '32', 'Ruang 101', 'Gedung A Lt. 1'],
    ['7B', 'Kelas 7B', 'Tingkat VII', 'Siti Rahma, S.Pd.',     '30', 'Ruang 102', 'Gedung A Lt. 1'],
    ['8A', 'Kelas 8A', 'Tingkat VIII','Budi Santoso, M.Pd.',   '31', 'Ruang 201', 'Gedung B Lt. 2'],
    ['8B', 'Kelas 8B', 'Tingkat VIII','Dewi Lestari, S.Pd.',   '29', 'Ruang 202', 'Gedung B Lt. 2'],
    ['9A', 'Kelas 9A', 'Tingkat IX',  'Rizky Maulana, S.Pd.',  '30', 'Ruang 301', 'Gedung C Lt. 3'],
  ];
  $kelasList = collect($rawKelas)
    ->when(request('tingkat'), fn($c, $t) => $c->filter(fn($k) => str_contains($k[2], $t)))
    ->when(request('search'), fn($c, $s) => $c->filter(fn($k) => str_contains(strtolower($k[1].' '.$k[3]), strtolower($s))));
@endphp

<form class="admin-toolbar" method="GET">
  <input class="admin-search" name="search" value="{{ request('search') }}" placeholder="Cari nama kelas atau wali kelas..." aria-label="Cari kelas">
  <select class="admin-select" name="tingkat" onchange="this.form.submit()">
    <option value="">Semua Tingkat</option>
    <option value="VII" @selected(request('tingkat') === 'VII')>VII</option>
    <option value="VIII" @selected(request('tingkat') === 'VIII')>VIII</option>
    <option value="IX" @selected(request('tingkat') === 'IX')>IX</option>
  </select>
  <span class="admin-total">Total {{ $kelasList->count() }} Kelas</span>
</form>

<section class="panel admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Kelas &amp; Tingkat</th>
        <th>Wali Kelas</th>
        <th>Kapasitas Murid</th>
        <th>Ruangan</th>
        <th>Status</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($kelasList as $kelas)
        <tr>
          <td>
            <div class="class-identity">
              <span class="class-pill-badge">{{ $kelas[0] }}</span>
              <div>
                <strong>{{ $kelas[1] }}</strong>
                <small>{{ $kelas[2] }}</small>
              </div>
            </div>
          </td>
          <td><strong>{{ $kelas[3] }}</strong></td>
          <td><span class="badge blue">{{ $kelas[4] }} Murid</span></td>
          <td>
            <span class="class-room-badge">{{ $kelas[5] }}</span>
            <small>{{ $kelas[6] }}</small>
          </td>
          <td><span class="badge">Aktif</span></td>
          <td><button class="link-action" type="button" onclick="showAdminToast('Detail {{ $kelas[1] }}')">Detail</button></td>
        </tr>
      @empty
        <tr><td class="admin-empty-row" colspan="6">Belum ada data kelas yang sesuai.</td></tr>
      @endforelse
    </tbody>
  </table>
  <div class="admin-pagination">
    <span>Menampilkan 1–5 dari 18 kelas</span>
    <div class="admin-page-links">
      <span class="disabled" aria-disabled="true">‹</span>
      <span>Halaman 1 dari 4</span>
      <button type="button" onclick="showAdminToast('Halaman 2')" aria-label="Halaman berikutnya">›</button>
    </div>
  </div>
</section>

@endsection
