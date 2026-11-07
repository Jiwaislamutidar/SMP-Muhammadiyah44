@extends('layouts.admin')
@section('title', 'Jadwal Pelajaran')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/jadwalpelajaran.css') }}?v=6">@endsection
@section('content')

<div class="admin-page-header">
	<div>
		<h1>Jadwal Pelajaran</h1>
		<p>Kelola jadwal mengajar</p>
	</div>
	<div class="admin-header-actions">
		<button class="admin-action admin-pdf-export bg-blue-600 hover:bg-blue-700" type="button" data-pdf-export data-export-url="{{ route('admin.jadwalpelajaran.export-pdf') }}" data-export-filename="jadwal-pelajaran.pdf" aria-live="polite">
			<svg class="admin-pdf-export-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 2.5v9m0 0 3.5-3.5M10 11.5 6.5 8M3.5 12.5v3A1.5 1.5 0 0 0 5 17h10a1.5 1.5 0 0 0 1.5-1.5v-3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<span data-export-label>Unduh Jadwal PDF</span><span class="admin-pdf-progress" aria-hidden="true"></span>
		</button>
		<button type="button" class="admin-action schedule-reset-button" id="openResetSchedulesBtn">Hapus Semua Jadwal</button>
		<a class="admin-action admin-action-outline admin-glass-button" href="{{ route('admin.jadwalpelajaran.template') }}">↓ Unduh Template</a>
		<button type="button" class="admin-action admin-glass-button" id="openImportModalBtn">↑ Import Jadwal</button>
	</div>
</div>

{{-- Flash message --}}
@if(session('success'))
	<div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if(session('error'))
	<div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
@if($errors->any())
	<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
@endif

{{-- ===== Modal Import Jadwal ===== --}}
<div class="modal-overlay" id="importModalOverlay">
	<div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="importModalTitle">
		<div class="modal-header">
			<h2 id="importModalTitle">Import Jadwal Pelajaran</h2>
			<button type="button" class="modal-close-btn" id="closeImportModalBtn" aria-label="Tutup">✕</button>
		</div>

		<form action="{{ route('admin.jadwalpelajaran.import') }}" method="POST" enctype="multipart/form-data" id="importJadwalForm">
			@csrf
			<div class="modal-body">
				<label for="jadwalFile" class="import-dropzone" id="importDropzone">
					<span class="dropzone-icon">📁</span>
					<span class="dropzone-text">Pilih atau seret file ke sini</span>
					<span class="dropzone-hint">Format didukung: .xlsx, .xls, .csv</span>
					<input id="jadwalFile" type="file" name="file" accept=".xlsx,.xls,.csv" required hidden>
				</label>

				<div class="file-preview" id="filePreview" hidden>
					📄 File terpilih: <strong id="filePreviewName"></strong>
				</div>

				<p class="import-note">Kolom template: <code>hari, jam_ke, waktu, kode_kelas, kode_mapel</code>. Guru dan mata pelajaran dicocokkan otomatis dari kode mapel. Jika waktu kosong, sistem memakai slot 40 menit mulai pukul 07.00 sesuai jam_ke.</p>

				<a class="template-shortcut-link admin-glass-button" href="{{ route('admin.jadwalpelajaran.template') }}">📥 Unduh Template File</a>
			</div>

			<div class="modal-footer">
				<button type="button" class="admin-action admin-action-outline" id="cancelImportBtn">Batal</button>
				<button type="submit" class="admin-action admin-action-primary admin-glass-button" id="submitImportBtn">Import Data</button>
			</div>
		</form>
	</div>
</div>

<form class="admin-toolbar" method="GET" action="{{ route('admin.jadwalpelajaran') }}">
	<input class="admin-search" name="search" value="{{ request('search') }}" placeholder="Cari guru, mapel, atau kelas..." aria-label="Cari jadwal">
	<select class="admin-select" name="hari">
		<option value="">Semua Hari</option>
		@foreach($hariList as $hari)
			<option value="{{ $hari }}" @selected(request('hari') === $hari)>{{ $hari }}</option>
		@endforeach
	</select>
	<select class="admin-select" name="kelas_id">
		<option value="">Semua Kelas</option>
		@foreach($kelasList as $kelas)
			<option value="{{ $kelas->id }}" @selected((string) request('kelas_id') === (string) $kelas->id)>{{ $kelas->nama_kelas }}</option>
		@endforeach
	</select>
	<select class="admin-select" name="mapel_id">
		<option value="">Semua Mapel</option>
		@foreach($mapelList as $mapel)
			<option value="{{ $mapel->id }}" @selected((string) request('mapel_id') === (string) $mapel->id)>{{ $mapel->nama_mapel }}</option>
		@endforeach
	</select>
	<button class="admin-action processing-filter-button" type="submit">Filter</button>
	<span class="schedule-total">Total {{ $totalJadwal }} Jadwal</span>
</form>

<section class="panel admin-table-wrap schedule-card">
	<table class="admin-table">
		<thead>
			<tr>
				<th>Hari</th>
				<th>Jam</th>
				<th>Mata Pelajaran</th>
				<th>Guru Pengampu</th>
				<th>Kelas</th>
				<th>Ruangan</th>
				<th>Aksi</th>
			</tr>
		</thead>
		<tbody>
			@forelse($jadwals as $jadwal)
				<tr>
					<td><span class="schedule-day">{{ $jadwal->hari }}</span></td>
					<td><span class="schedule-time">{{ substr($jadwal->jam_mulai, 0, 5) }} – {{ substr($jadwal->jam_selesai, 0, 5) }}</span></td>
					<td><span class="schedule-subject">{{ $jadwal->mapel->nama_mapel }}</span></td>
					<td><span class="schedule-teacher">{{ $jadwal->guru->nama_lengkap }}</span></td>
					<td><span class="schedule-class">{{ $jadwal->kelas->nama_kelas }}</span></td>
					<td><span class="schedule-room">{{ $jadwal->kelas?->ruangan ?: '-' }}</span></td>
					<td>
						<button class="schedule-delete-button admin-delete-button" type="button" data-delete-schedule
							data-delete-url="{{ route('admin.jadwalpelajaran.destroy', $jadwal) }}"
							aria-label="Hapus jadwal {{ $jadwal->hari }} {{ substr($jadwal->jam_mulai, 0, 5) }} kelas {{ $jadwal->kelas->nama_kelas }}">Hapus</button>
					</td>
				</tr>
			@empty
				<tr>
					<td class="admin-empty-row" colspan="7">Belum ada jadwal tersimpan. Gunakan tombol import di atas.</td>
				</tr>
			@endforelse
		</tbody>
	</table>
	<div class="admin-pagination">
		<span>Menampilkan {{ $jadwals->firstItem() ?? 0 }}–{{ $jadwals->lastItem() ?? 0 }} dari {{ $jadwals->total() }} jadwal</span>
		<div class="admin-page-links">
			@if ($jadwals->onFirstPage())
				<span class="disabled" aria-disabled="true">‹</span>
			@else
				<a href="{{ $jadwals->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹</a>
			@endif
			<span>Halaman {{ $jadwals->currentPage() }} dari {{ $jadwals->lastPage() }}</span>
			@if ($jadwals->hasMorePages())
				<a href="{{ $jadwals->nextPageUrl() }}" aria-label="Halaman berikutnya">›</a>
			@else
				<span class="disabled" aria-disabled="true">›</span>
			@endif
		</div>
	</div>
</section>

<dialog class="schedule-delete-modal" id="deleteScheduleModal" aria-labelledby="deleteScheduleTitle">
	<form method="POST" id="deleteScheduleForm">
		@csrf
		@method('DELETE')
		<h2 id="deleteScheduleTitle">Apakah kamu yakin ingin menghapus jadwal ini?</h2>
		<p>Sesi pelajaran dan catatan presensi yang terkait juga akan dihapus.</p>
		<div class="schedule-delete-actions">
			<button class="admin-action-outline" type="button" id="cancelDeleteSchedule">Batal</button>
			<button class="admin-delete-button admin-delete-button--solid" type="submit">Ya, Hapus</button>
		</div>
	</form>
</dialog>

<dialog class="schedule-delete-modal" id="resetSchedulesModal" aria-labelledby="resetSchedulesTitle">
	<form method="POST" action="{{ route('admin.jadwalpelajaran.destroy-all') }}" id="resetSchedulesForm">
		@csrf
		@method('DELETE')
		<h2 id="resetSchedulesTitle">Hapus semua jadwal pelajaran?</h2>
		<p>Semua jadwal pada database aktif beserta sesi pelajaran dan catatan presensi terkait akan dihapus. Data guru, mata pelajaran, dan kelas tidak akan terpengaruh. Tindakan ini tidak dapat dibatalkan.</p>
		<div class="schedule-delete-actions">
			<button class="admin-action-outline" type="button" id="cancelResetSchedules">Batal</button>
			<button class="admin-delete-button admin-delete-button--solid" type="submit" id="confirmResetSchedules">Ya, Hapus Semua</button>
		</div>
	</form>
</dialog>

<script>
(function () {
	const overlay = document.getElementById('importModalOverlay');
	const openBtn = document.getElementById('openImportModalBtn');
	const closeBtn = document.getElementById('closeImportModalBtn');
	const cancelBtn = document.getElementById('cancelImportBtn');
	const fileInput = document.getElementById('jadwalFile');
	const dropzone = document.getElementById('importDropzone');
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
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && overlay.classList.contains('show')) closeModal();
	});

	function updateFile(file) {
		if (file) {
			filePreviewName.textContent = file.name;
			filePreview.hidden = false;
		} else {
			filePreview.hidden = true;
		}
	}

	fileInput.addEventListener('change', function () {
		updateFile(fileInput.files[0]);
	});

	if (dropzone) {
		dropzone.addEventListener('dragover', function (e) { e.preventDefault(); dropzone.classList.add('is-dragging'); });
		dropzone.addEventListener('dragleave', function () { dropzone.classList.remove('is-dragging'); });
		dropzone.addEventListener('drop', function (e) {
			e.preventDefault();
			dropzone.classList.remove('is-dragging');
			if (e.dataTransfer.files.length) {
				fileInput.files = e.dataTransfer.files;
				updateFile(e.dataTransfer.files[0]);
			}
		});
	}

	form.addEventListener('submit', function () {
		submitBtn.disabled = true;
		submitBtn.textContent = 'Mengimpor...';
	});

	const deleteScheduleModal = document.getElementById('deleteScheduleModal');
	const deleteScheduleForm = document.getElementById('deleteScheduleForm');
	let pendingDeleteScheduleButton = null;
	document.querySelectorAll('[data-delete-schedule]').forEach((button) => {
		button.addEventListener('click', function () {
			pendingDeleteScheduleButton = button;
			button.classList.add('is-awaiting-confirmation');
			deleteScheduleForm.action = button.dataset.deleteUrl;
			deleteScheduleModal.showModal();
		});
	});
	document.getElementById('cancelDeleteSchedule').addEventListener('click', function () {
		deleteScheduleModal.close();
	});
	deleteScheduleModal.addEventListener('close', function () {
		pendingDeleteScheduleButton?.classList.remove('is-awaiting-confirmation');
		pendingDeleteScheduleButton = null;
	});

	const resetSchedulesModal = document.getElementById('resetSchedulesModal');
	const resetSchedulesForm = document.getElementById('resetSchedulesForm');
	document.getElementById('openResetSchedulesBtn').addEventListener('click', function () {
		resetSchedulesModal.showModal();
	});
	document.getElementById('cancelResetSchedules').addEventListener('click', function () {
		resetSchedulesModal.close();
	});
	resetSchedulesForm.addEventListener('submit', function () {
		const confirmButton = document.getElementById('confirmResetSchedules');
		confirmButton.disabled = true;
		confirmButton.textContent = 'Menghapus...';
	});
})();
</script>
<script src="{{ asset('admin-pdf-export.js') }}?v=1" defer></script>
@endsection