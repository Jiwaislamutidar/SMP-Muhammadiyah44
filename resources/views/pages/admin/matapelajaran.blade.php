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

@php
  $rawMapel = [
    ['MTK', 'Matematika',          'MTK-SMP44', '4 Guru', '4 JP/Minggu', 'Aktif'],
    ['IPA', 'IPA Terpadu',         'IPA-SMP44', '4 Guru', '4 JP/Minggu', 'Aktif'],
    ['BIN', 'Bahasa Indonesia',    'BIN-SMP44', '3 Guru', '4 JP/Minggu', 'Aktif'],
    ['BIG', 'Bahasa Inggris',      'BIG-SMP44', '3 Guru', '3 JP/Minggu', 'Aktif'],
    ['IPS', 'IPS Terpadu',         'IPS-SMP44', '3 Guru', '3 JP/Minggu', 'Aktif'],
    ['PAI', 'Pend. Agama Islam',   'PAI-SMP44', '4 Guru', '3 JP/Minggu', 'Aktif'],
    ['PKN', 'Pendidikan Pancasila','PKN-SMP44', '2 Guru', '2 JP/Minggu', 'Aktif'],
    ['SBD', 'Seni Budaya',         'SBD-SMP44', '2 Guru', '2 JP/Minggu', 'Aktif'],
  ];
  $mapelList = collect($rawMapel)
    ->when(request('status'), fn($c, $s) => $c->filter(fn($m) => strtolower($m[5]) === strtolower($s)))
    ->when(request('search'), fn($c, $q) => $c->filter(fn($m) => str_contains(strtolower($m[0].' '.$m[1].' '.$m[2]), strtolower($q))));
@endphp

<form class="admin-toolbar" method="GET">
  <input class="admin-search" name="search" value="{{ request('search') }}" placeholder="Cari nama atau kode mapel..." aria-label="Cari mata pelajaran">
  <select class="admin-select" name="status" onchange="this.form.submit()">
    <option value="">Semua Status</option>
    <option value="Aktif" @selected(request('status') === 'Aktif')>Aktif</option>
    <option value="Nonaktif" @selected(request('status') === 'Nonaktif')>Nonaktif</option>
  </select>
  <span class="admin-total">Total {{ $mapelList->count() }} Mapel</span>
</form>

<div class="admin-grid">
  @forelse ($mapelList as $mapel)
    <article class="subject-card">
      <div class="subject-top">
        <span class="subject-code">{{ $mapel[0] }}</span>
        <span class="badge">{{ $mapel[5] }}</span>
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
  @empty
    <div style="grid-column: 1 / -1; padding: 24px; text-align: center; color: var(--muted); background: #fff; border: 1px solid var(--line); border-radius: 12px;">
      Belum ada mata pelajaran yang sesuai.
    </div>
  @endforelse
</div>

@endsection
