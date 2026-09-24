@extends('layouts.admin')
@section('title', 'Pengaturan')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/profil.css') }}?v=1">@endsection
@section('content')
<div class="admin-page-header"><div><h1>Pengaturan</h1><p>Kelola pengaturan sistem presensi</p></div></div><div class="settings-grid"><nav class="settings-menu"><a class="active" href="#">Profil Sistem</a><a href="#">Pengaturan Presensi</a><a href="#">Akun Admin</a></nav><section class="settings-card"><h2>Profil Sistem</h2><p>Informasi dasar institusi sekolah yang digunakan pada portal dan laporan presensi</p><div class="form-stack"><div class="form-field"><label>Nama Sekolah</label><input value="SMP Muhammadiyah 44" readonly></div><div class="form-field"><label>Kota</label><input value="Tangerang Selatan" readonly></div><div class="form-field"><label>Tahun Ajaran</label><select class="admin-select" style="width:100%"><option>2026/2027</option><option>2025/2026</option></select></div><div class="form-actions"><button class="admin-action">Simpan Perubahan</button></div></div></section></div>
@endsection
