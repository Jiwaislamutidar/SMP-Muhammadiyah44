@extends('layouts.admin')
@section('title', 'Jadwal Pelajaran')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/jadwalpelajaran.css') }}?v=1">@endsection
@section('content')
<div class="admin-page-header"><div><h1>Jadwal Pelajaran</h1><p>Kelola jadwal mengajar dari data akademik.</p></div><a class="admin-action" href="{{ route('admin.jadwalpelajaran.template') }}">Unduh Template</a></div>
@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<section class="panel" style="padding:16px;margin-bottom:16px">
	<form action="{{ route('admin.jadwalpelajaran.import') }}" method="POST" enctype="multipart/form-data" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
		@csrf<label for="jadwalFile">Import XLSX/CSV jadwal</label><input id="jadwalFile" type="file" name="file" accept=".xlsx,.xls,.csv" required><button class="admin-action" type="submit">Import Jadwal</button>
	</form>
	<p>Kolom template: Hari, Jam Mulai, Jam Selesai, Kode Mapel, Nama Guru, Nama Kelas, Ruangan. Guru, kelas, dan mapel harus sudah terdaftar.</p>
</section>
<form class="admin-toolbar" method="GET" action="{{ route('admin.jadwalpelajaran') }}">
	<input class="admin-search" name="search" value="{{ request('search') }}" placeholder="Cari guru, mata pelajaran, atau kelas...">
	<select class="admin-select" name="hari"><option value="">Semua Hari</option>@foreach($hariList as $hari)<option value="{{ $hari }}" @selected(request('hari') === $hari)>{{ $hari }}</option>@endforeach</select>
	<select class="admin-select" name="kelas_id"><option value="">Semua Kelas</option>@foreach($kelasList as $kelas)<option value="{{ $kelas->id }}" @selected((string) request('kelas_id') === (string) $kelas->id)>{{ $kelas->nama_kelas }}</option>@endforeach</select>
	<select class="admin-select" name="mapel_id"><option value="">Semua Mata Pelajaran</option>@foreach($mapelList as $mapel)<option value="{{ $mapel->id }}" @selected((string) request('mapel_id') === (string) $mapel->id)>{{ $mapel->nama_mapel }}</option>@endforeach</select>
	<button class="admin-action" type="submit">Terapkan</button><span class="admin-total">Total {{ $totalJadwal }} Jadwal</span>
</form>
<section class="panel admin-table-wrap schedule-card"><table class="admin-table"><thead><tr><th>Hari</th><th>Jam</th><th>Mata Pelajaran</th><th>Guru Pengampu</th><th>Kelas</th><th>Ruangan</th></tr></thead><tbody>
	@forelse($jadwals as $jadwal)<tr><td><strong>{{ $jadwal->hari }}</strong></td><td><span class="badge blue">{{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}</span></td><td><strong>{{ $jadwal->mapel->nama_mapel }}</strong></td><td>{{ $jadwal->guru->nama_lengkap }}</td><td><span class="badge">{{ $jadwal->kelas->nama_kelas }}</span></td><td>{{ $jadwal->ruangan ?? '-' }}</td></tr>
	@empty<tr><td colspan="6">Belum ada jadwal tersimpan. Impor jadwal dengan template di atas.</td></tr>@endforelse
</tbody></table><div class="admin-pagination">{{ $jadwals->links() }}</div></section>
@endsection