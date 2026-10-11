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

<form class="admin-toolbar" method="GET">
  <input class="admin-search" placeholder="Cari nama kelas atau wali kelas..." aria-label="Cari kelas">
  <select class="admin-select" name="tingkat">
    <option value="">Semua Tingkat</option>
    <option>VII</option>
    <option>VIII</option>
    <option>IX</option>
  </select>
  <button class="admin-filter-submit" type="submit">Filter</button>
  <span class="admin-total">Total 18 Kelas</span>
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
      @foreach ([
        ['7A', 'Kelas 7A', 'Tingkat VII', 'Ahmad Fauzi, S.Pd.',    '32', 'Ruang 101', 'Gedung A Lt. 1'],
        ['7B', 'Kelas 7B', 'Tingkat VII', 'Siti Rahma, S.Pd.',     '30', 'Ruang 102', 'Gedung A Lt. 1'],
        ['8A', 'Kelas 8A', 'Tingkat VIII','Budi Santoso, M.Pd.',   '31', 'Ruang 201', 'Gedung B Lt. 2'],
        ['8B', 'Kelas 8B', 'Tingkat VIII','Dewi Lestari, S.Pd.',   '29', 'Ruang 202', 'Gedung B Lt. 2'],
        ['9A', 'Kelas 9A', 'Tingkat IX',  'Rizky Maulana, S.Pd.',  '30', 'Ruang 301', 'Gedung C Lt. 3'],
      ] as $kelas)
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
      @endforeach
    </tbody>
  </table>
  <div class="admin-pagination">
    <span>Menampilkan 1–5 dari 18 kelas</span>
    <div>
      <button class="active">1</button>
      <button>2</button>
      <button>3</button>
    </div>
  </div>
</section>

@endsection
