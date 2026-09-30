@extends('layouts.admin')
@section('title', 'Jadwal Pelajaran')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/jadwalpelajaran.css') }}?v=2">@endsection
@section('content')

<div class="admin-page-header">
	<div><h1>Jadwal Pelajaran</h1><p>Kelola jadwal mengajar dari data akademik.</p></div>
	<div class="admin-header-actions">
		<a class="admin-action admin-action-outline" href="{{ route('admin.jadwalpelajaran.template') }}">📥 Unduh Template</a>
		<button type="button" class="admin-action admin-action-primary" id="openImportModalBtn">📤 Import Jadwal</button>
	</div>
</div>

{{-- Flash message: sukses / gagal import --}}
@if(session('success'))
	<div class="alert alert-success" role="status">✅ {{ session('success') }}</div>
@endif
@if(session('error'))
	<div class="alert alert-danger" role="alert">❌ {{ session('error') }}</div>
@endif
@if($errors->any())
	<div class="alert alert-danger" role="alert">❌ {{ $errors->first() }}</div>
@endif

{{-- ===== Modal Import Jadwal ===== --}}
<div class="modal-overlay" id="importModalOverlay">
	<div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="importModalTitle">
		<div class="modal-header">
			<h2 id="importModalTitle">📤 Import Jadwal Pelajaran</h2>
			<button type="button" class="modal-close-btn" id="closeImportModalBtn" aria-label="Tutup">✕</button>
		</div>

		<form action="{{ route('admin.jadwalpelajaran.import') }}" method="POST" enctype="multipart/form-data" id="importJadwalForm">
			@csrf
			<div class="modal-body">
				<label for="jadwalFile" class="import-dropzone" id="importDropzone">
					<span class="dropzone-icon">📁</span>
					<span class="dropzone-text">Klik untuk pilih file, atau tarik file ke sini</span>
					<span class="dropzone-hint">Format didukung: .xlsx, .xls, .csv</span>
					<input id="jadwalFile" type="file" name="file" accept=".xlsx,.xls,.csv" required hidden>
				</label>

				{{-- Indikator file yang sudah dipilih --}}
				<div class="file-preview" id="filePreview" hidden>
					📄 File siap diimpor: <strong id="filePreviewName"></strong>
				</div>

				<p class="import-note">Kolom wajib CSV/XLSX: <code>Hari, Jam Mulai, Jam Selesai, Kode Mapel, Nama Guru, Nama Kelas, Ruangan</code>. Format jam: "07.35" (bukan digabung). Ruangan boleh kosong. Kode mapel, nama guru, dan nama kelas harus sudah terdaftar di master data.</p>

				<a class="template-shortcut-link" href="{{ route('admin.jadwalpelajaran.template') }}">📥 Unduh Template CSV/XLSX</a>
			</div>

			<div class="modal-footer">
				<button type="button" class="admin-action admin-action-outline" id="cancelImportBtn">Batal</button>
				<button type="submit" class="admin-action admin-action-primary" id="submitImportBtn">🚀 Impor Jadwal Sekarang</button>
			</div>
		</form>
	</div>
</div>

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

{{-- Script ditaruh di sini (bukan @section('scripts')) supaya tetap jalan
     walau layouts.admin tidak punya @yield('scripts') --}}
<script>
(function () {
	const overlay = document.getElementById('importModalOverlay');
	const openBtn = document.getElementById('openImportModalBtn');
	const closeBtn = document.getElementById('closeImportModalBtn');
	const cancelBtn = document.getElementById('cancelImportBtn');
	const fileInput = document.getElementById('jadwalFile');
	const filePreview = document.getElementById('filePreview');
	const filePreviewName = document.getElementById('filePreviewName');
	const form = document.getElementById('importJadwalForm');
	const submitBtn = document.getElementById('submitImportBtn');

	if (!overlay || !openBtn || !form) return;

	function openModal() { overlay.classList.add('show'); }
	function closeModal() { overlay.classList.remove('show'); }

	openBtn.addEventListener('click', openModal);
	closeBtn.addEventListener('click', closeModal);
	cancelBtn.addEventListener('click', closeModal);
	overlay.addEventListener('click', function (e) {
		if (e.target === overlay) closeModal();
	});

	fileInput.addEventListener('change', function () {
		if (fileInput.files.length > 0) {
			filePreviewName.textContent = fileInput.files[0].name;
			filePreview.hidden = false;
		} else {
			filePreview.hidden = true;
		}
	});

	form.addEventListener('submit', function () {
		submitBtn.disabled = true;
		submitBtn.innerHTML = '⏳ Sedang mengimpor data...';
	});
})();
</script>

@endsection