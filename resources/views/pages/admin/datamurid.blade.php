@extends('layouts.admin')
@section('title', 'Data Murid')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/datamurid.css') }}?v=2">@endsection
@section('content')
<div class="admin-page-header">
	<div>
		<h1>Data Murid</h1>
		<p>Kelola data murid SMP Muhammadiyah 44 Tangerang Selatan</p>
	</div>
	<div class="admin-header-actions">
		<button class="admin-action admin-import-trigger" type="button" id="openImportModal">Import Excel</button>
		<button class="admin-action" type="button">+ Tambah Murid</button>
	</div>
</div>

@if(session('success'))
	<div class="admin-import-alert success" role="status">{{ session('success') }}</div>
@endif
@if(session('error'))
	<div class="admin-import-alert error" role="alert">{{ session('error') }}</div>
@endif
@if(session('import_warning'))
	<div class="admin-import-alert warning" role="status">
		{{ session('import_warning') }} baris dilewati karena data tidak valid atau NIS sudah terdaftar.
		@if(session('import_failures'))
			<ul>
				@foreach(session('import_failures') as $failure)
					<li>Baris {{ $failure['row'] }}: {{ implode(' ', $failure['errors']) }}</li>
				@endforeach
			</ul>
		@endif
	</div>
@endif
@if($errors->any())
	<div class="admin-import-alert error" role="alert">{{ $errors->first('file') }}</div>
@endif

<form class="admin-toolbar" method="GET" action="{{ route('admin.datamurid') }}">
	<input class="admin-search" type="search" name="search" value="{{ request('search') }}" placeholder="Cari murid berdasarkan nama, NISN, atau kelas..." aria-label="Cari murid">
	<label for="kelasFilter">Filter Kelas:</label>
	<select class="admin-select" id="kelasFilter" name="kelas_id" onchange="this.form.submit()">
		<option value="">Semua Kelas</option>
		@foreach($kelasList as $kelas)
			<option value="{{ $kelas->id }}" @selected((string) request('kelas_id') === (string) $kelas->id)>{{ $kelas->nama_kelas }}</option>
		@endforeach
	</select>
	<button class="admin-filter-submit" type="submit">Cari</button>
	<span class="admin-total">Total {{ $totalSiswa }} Murid</span>
</form>

<section class="panel admin-table-wrap">
	<table class="admin-table">
		<thead>
			<tr><th>Murid &amp; Identitas</th><th>NISN</th><th>Kelas</th><th>Status</th><th>Aksi</th></tr>
		</thead>
		<tbody>
			@forelse($siswas as $siswa)
				@php
					$nama = $siswa->nama_lengkap;
					$inisial = collect(explode(' ', $nama))->filter()->take(2)->map(fn ($bagian) => mb_substr($bagian, 0, 1))->implode('');
				@endphp
				<tr>
					<td><span class="initial">{{ mb_strtoupper($inisial) }}</span><strong style="display:inline">{{ $nama }}</strong><small>{{ $siswa->user->username }}</small></td>
					<td>{{ $siswa->nisn }}</td>
					<td><span class="badge blue">{{ $siswa->kelas->nama_kelas ?? 'Belum ditentukan' }}</span></td>
					<td><span class="badge">{{ ucfirst($siswa->status) }}</span></td>
					<td><span class="link-action">Detail</span></td>
				</tr>
			@empty
				<tr><td class="admin-empty-row" colspan="5">Belum ada data murid yang sesuai.</td></tr>
			@endforelse
		</tbody>
	</table>
	<div class="admin-pagination">
		<span>Menampilkan {{ $siswas->firstItem() ?? 0 }}–{{ $siswas->lastItem() ?? 0 }} dari {{ $siswas->total() }} murid</span>
		<div class="admin-page-links">
			@if($siswas->previousPageUrl())<a href="{{ $siswas->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹</a>@endif
			<span>Halaman {{ $siswas->currentPage() }} dari {{ $siswas->lastPage() }}</span>
			@if($siswas->nextPageUrl())<a href="{{ $siswas->nextPageUrl() }}" aria-label="Halaman berikutnya">›</a>@endif
		</div>
	</div>
</section>

<div class="admin-modal-backdrop" id="importModal" hidden>
	<section class="admin-import-modal" role="dialog" aria-modal="true" aria-labelledby="importModalTitle">
		<div class="admin-modal-heading">
			<div><h2 id="importModalTitle">Import Data Murid</h2><p>Unggah file Excel daftar siswa resmi dari sekolah (.xlsx / .xls / .csv).</p></div>
			<button type="button" class="admin-modal-close" id="closeImportModal" aria-label="Tutup">&times;</button>
		</div>
		<form action="{{ route('admin.siswa.import') }}" method="POST" enctype="multipart/form-data">
			@csrf
			<label class="admin-file-dropzone" id="studentImportDropzone" for="studentImportFile">
				<span class="admin-file-copy">
					<strong>Pilih file daftar siswa</strong>
					<small>Format .xlsx, .xls, atau .csv. Maksimal 2 MB.</small>
				</span>
				<span class="admin-file-pick">Pilih File</span>
				<input id="studentImportFile" class="admin-file-input" type="file" name="file" accept=".xlsx,.xls,.csv" required>
			</label>
			<p class="admin-file-selected" id="studentImportFileName" aria-live="polite">Belum ada file dipilih</p>
			<div class="admin-modal-actions">
				<button class="admin-modal-cancel" type="button" id="cancelImportModal">Batal</button>
				<button class="admin-action admin-import-submit" type="submit">Import Data</button>
			</div>
		</form>
	</section>
</div>

<script>
	(() => {
		const modal = document.getElementById('importModal');
		const openButton = document.getElementById('openImportModal');
		const closeButton = document.getElementById('closeImportModal');
		const cancelButton = document.getElementById('cancelImportModal');
		const fileInput = document.getElementById('studentImportFile');
		const dropzone = document.getElementById('studentImportDropzone');
		const fileName = document.getElementById('studentImportFileName');

		const showSelectedFile = () => {
			fileName.textContent = fileInput.files[0]
				? `${fileInput.files[0].name} (${(fileInput.files[0].size / 1024 / 1024).toFixed(2)} MB)`
				: 'Belum ada file dipilih';
		};

		const closeModal = () => {
			modal.hidden = true;
			openButton.focus();
		};

		openButton.addEventListener('click', () => {
			modal.hidden = false;
			fileInput.focus();
		});
		fileInput.addEventListener('change', showSelectedFile);
		dropzone.addEventListener('dragover', (event) => {
			event.preventDefault();
			dropzone.classList.add('is-dragging');
		});
		dropzone.addEventListener('dragleave', () => dropzone.classList.remove('is-dragging'));
		dropzone.addEventListener('drop', (event) => {
			event.preventDefault();
			dropzone.classList.remove('is-dragging');
			if (event.dataTransfer.files.length) {
				const transfer = new DataTransfer();
				transfer.items.add(event.dataTransfer.files[0]);
				fileInput.files = transfer.files;
				showSelectedFile();
			}
		});
		closeButton.addEventListener('click', closeModal);
		cancelButton.addEventListener('click', closeModal);
		modal.addEventListener('click', (event) => {
			if (event.target === modal) closeModal();
		});
		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape' && !modal.hidden) closeModal();
		});
	})();
</script>
@endsection
