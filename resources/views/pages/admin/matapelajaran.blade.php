@extends('layouts.admin')
@section('title', 'Mata Pelajaran')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/matapelajaran.css') }}?v=3">@endsection
@section('content')

<div class="admin-page-header">
  <div>
    <h1>Mata Pelajaran</h1>
    <p>Kelola kurikulum &amp; daftar mata pelajaran</p>
  </div>
  <button class="admin-action">+ Tambah Mapel</button>
</div>

<form class="admin-toolbar" method="GET">
  <input class="admin-search" name="search" placeholder="Cari nama atau kode mapel..." aria-label="Cari mata pelajaran">
  <select class="admin-select" name="status">
    <option value="">Semua Status</option>
    <option>Aktif</option>
    <option>Nonaktif</option>
  </select>
  <button class="admin-filter-submit" type="submit">Filter</button>
  <span class="admin-total">Total 8 Mapel</span>
</form>

<div class="admin-grid">
  @foreach ([
    ['MTK', 'Matematika',          'MTK-SMP44', '4 Guru', '4 JP/Minggu'],
    ['IPA', 'IPA Terpadu',         'IPA-SMP44', '4 Guru', '4 JP/Minggu'],
    ['BIN', 'Bahasa Indonesia',    'BIN-SMP44', '3 Guru', '4 JP/Minggu'],
    ['BIG', 'Bahasa Inggris',      'BIG-SMP44', '3 Guru', '3 JP/Minggu'],
    ['IPS', 'IPS Terpadu',         'IPS-SMP44', '3 Guru', '3 JP/Minggu'],
    ['PAI', 'Pend. Agama Islam',   'PAI-SMP44', '4 Guru', '3 JP/Minggu'],
    ['PKN', 'Pendidikan Pancasila','PKN-SMP44', '2 Guru', '2 JP/Minggu'],
    ['SBD', 'Seni Budaya',         'SBD-SMP44', '2 Guru', '2 JP/Minggu'],
  ] as $mapel)
    <article class="subject-card">
      <div class="subject-top">
        <span class="subject-code">{{ $mapel[0] }}</span>
        <span class="badge">Aktif</span>
      </div>
      <h3>{{ $mapel[1] }}</h3>
      <p class="subject-sub">{{ $mapel[2] }}</p>
      <div class="subject-meta">
        <span>👨‍🏫 {{ $mapel[3] }}</span>
        <span>⏱️ {{ $mapel[4] }}</span>
      </div>
      <div class="subject-footer">
        <span class="badge blue">{{ $mapel[0] }}</span>
        <a class="link-action" href="#">Kelola →</a>
      </div>
    </article>
  @endforeach
</div>

@endsection
