@extends('layouts.admin')
@section('title', 'Data Murid')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/datamurid.css') }}?v=8">@endsection
@section('content')
<div class="admin-page-header">
	<div>
		<h1>Data Murid</h1>
		<p>Kelola data siswa terdaftar</p>
	</div>
	<div class="admin-header-actions">
		<button class="admin-action admin-pdf-export bg-blue-600 hover:bg-blue-700" type="button" data-pdf-export data-export-url="{{ route('admin.siswa.export-pdf') }}" data-export-filename="data-murid.pdf" aria-live="polite">
			<svg class="admin-pdf-export-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 2.5v9m0 0 3.5-3.5M10 11.5 6.5 8M3.5 12.5v3A1.5 1.5 0 0 0 5 17h10a1.5 1.5 0 0 0 1.5-1.5v-3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<span data-export-label>Unduh Data Murid</span><span class="admin-pdf-progress" aria-hidden="true"></span>
		</button>
		<button class="admin-action admin-import-trigger admin-glass-button" type="button" id="openImportModal">↑ Import Excel</button>
		<a class="admin-action admin-action-outline admin-template-trigger admin-glass-button" href="{{ route('admin.siswa.template') }}">↓ Unduh Template</a>
		<button class="admin-action admin-manual-trigger" type="button" id="openManualImportModal">+ Tambah Siswa</button>
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
	<div class="admin-import-alert error" role="alert">{{ $errors->first() }}</div>
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
	<span>Total {{ $totalSiswa }} Murid</span>
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
					<td>
						<div class="student-identity">
							<span class="initial">{{ mb_strtoupper($inisial) }}</span>
							<div>
								<strong>{{ $nama }}</strong>
								<small>{{ $siswa->user->username ?? $siswa->nisn }}</small>
							</div>
						</div>
					</td>
					<td><span class="student-nisn">{{ $siswa->nisn }}</span></td>
					<td><span class="student-class">{{ $siswa->kelas->nama_kelas ?? 'Belum ada kelas' }}</span></td>
					<td><span class="student-status">{{ ucfirst($siswa->status) }}</span></td>
					<td>
						<div class="student-row-actions">
							<button class="student-edit-button" type="button" data-edit-student
								data-id="{{ $siswa->id }}"
								data-nama="{{ $nama }}"
								data-nisn="{{ $siswa->nisn }}"
								data-kode-kelas="{{ $siswa->kelas->nama_kelas ?? '' }}">Edit</button>
							<button class="student-delete-button admin-delete-button" type="button" data-delete-student
								data-name="{{ $nama }}"
								data-delete-url="{{ route('admin.siswa.destroy', $siswa) }}">Hapus</button>
						</div>
					</td>
				</tr>
			@empty
				<tr><td class="admin-empty-row" colspan="5">Belum ada data murid yang sesuai.</td></tr>
			@endforelse
		</tbody>
	</table>
	<div class="admin-pagination">
		<span>Menampilkan {{ $siswas->firstItem() ?? 0 }}–{{ $siswas->lastItem() ?? 0 }} dari {{ $siswas->total() }} murid</span>
		<div class="admin-page-links">
			@if ($siswas->onFirstPage())
				<span class="disabled" aria-disabled="true">‹</span>
			@else
				<a href="{{ $siswas->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹</a>
			@endif
			<span>Halaman {{ $siswas->currentPage() }} dari {{ $siswas->lastPage() }}</span>
			@if ($siswas->hasMorePages())
				<a href="{{ $siswas->nextPageUrl() }}" aria-label="Halaman berikutnya">›</a>
			@else
				<span class="disabled" aria-disabled="true">›</span>
			@endif
		</div>
	</div>
</section>

<dialog class="student-delete-modal" id="deleteStudentModal" aria-labelledby="deleteStudentTitle">
	<form method="POST" id="deleteStudentForm">
		@csrf
		@method('DELETE')
		<h2 id="deleteStudentTitle">Apakah kamu yakin untuk menghapus siswa <span id="deleteStudentName"></span>?</h2>
		<p>Data murid, akun login, dan catatan presensinya akan dihapus permanen.</p>
		<div class="student-delete-actions">
			<button type="button" id="cancelDeleteStudent">Batal</button>
			<button class="student-delete-confirm admin-delete-button admin-delete-button--solid" type="submit">Ya, Hapus</button>
		</div>
	</form>
</dialog>

<div class="student-edit-backdrop" id="editStudentModal" data-reopen="{{ old('form_context') === 'edit' ? 'true' : 'false' }}" hidden>
	<section class="student-edit-modal" role="dialog" aria-modal="true" aria-labelledby="editStudentTitle">
		<div class="student-edit-heading">
			<div><h2 id="editStudentTitle">Edit Siswa</h2></div>
			<button class="student-edit-close" type="button" data-close-student-edit aria-label="Tutup">&times;</button>
		</div>
		<form id="editStudentForm" method="POST" class="student-edit-form" data-update-url="{{ route('admin.siswa.update', ':id') }}">
			@csrf
			@method('PUT')
			<input type="hidden" name="form_context" value="edit">
			<input type="hidden" name="edit_siswa_id" id="editStudentId" value="{{ old('edit_siswa_id') }}">
			<input type="hidden" name="reset_password" id="editStudentResetPasswordInput" value="0">

			<label>Nama lengkap
				<input class="admin-input" id="editStudentName" type="text" name="nama_lengkap" value="{{ old('nama_lengkap') }}" maxlength="255" required>
			</label>
			<label>NISN
				<input class="admin-input" id="editStudentNisn" type="text" name="nisn" value="{{ old('nisn') }}" maxlength="255" required>
			</label>
			<label>Kelas
				<select class="admin-select" id="editStudentClass" name="kode_kelas">
					<option value="">Belum ada kelas</option>
					@foreach($kelasList as $kelas)
						<option value="{{ $kelas->nama_kelas }}" @selected(old('kode_kelas') === $kelas->nama_kelas)>{{ $kelas->nama_kelas }}</option>
					@endforeach
				</select>
			</label>

			<div class="student-reset-pass-wrap" style="display: flex; flex-direction: column; gap: 6px; margin-top: 4px;">
				<span style="font-size: 12.5px; font-weight: 600; color: var(--ink, #17211c);">Reset Password Akun</span>
				<button type="button" id="editStudentResetPasswordBtn" style="width: 100%; min-height: 40px; padding: 8px 14px; border: 1px solid #fca5a5; border-radius: 8px; background: #fef2f2; color: #dc2626; font-family: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all .15s ease;">
					🔄 Reset Password ke Default (NISN)
				</button>
				<small style="font-size: 11px; color: #6b756f;">Klik jika siswa lupa password. Password akan dikembalikan sama dengan NISN.</small>
			</div>

			<div class="student-edit-actions" style="margin-top: 14px;">
				<button class="student-edit-cancel" type="button" data-close-student-edit>Batal</button>
				<button class="admin-action" type="submit">Simpan Perubahan</button>
			</div>
		</form>
	</section>
</div>

<div class="admin-modal-backdrop" id="importModal" data-reopen="{{ $errors->has('file') ? 'true' : 'false' }}" hidden>
	<section class="admin-import-modal" role="dialog" aria-modal="true" aria-labelledby="importModalTitle">
		<div class="admin-modal-heading">
			<div><h2 id="importModalTitle">Import Excel</h2><p>Kolom: nisn, nama_lengkap, kode_kelas &mdash; .xlsx / .xls / .csv, maks. 2 MB</p></div>
			<button type="button" class="admin-modal-close" id="closeImportModal" aria-label="Tutup">&times;</button>
		</div>
		<form action="{{ route('admin.siswa.import') }}" method="POST" enctype="multipart/form-data">
			@csrf
			<label class="admin-file-dropzone" id="studentImportDropzone" for="studentImportFile">
				<span class="admin-file-copy">
					<strong>Pilih atau seret file daftar siswa ke sini</strong>
					<small>Format .xlsx, .xls, atau .csv. Maksimal 2 MB.</small>
				</span>
				<span class="admin-file-pick">Pilih File</span>
				<input id="studentImportFile" class="admin-file-input" type="file" name="file" accept=".xlsx,.xls,.csv" required>
			</label>
			<p class="admin-file-selected" id="studentImportFileName" aria-live="polite">Belum ada file dipilih</p>
			<div class="admin-modal-actions">
				<button class="admin-modal-cancel" type="button" id="cancelImportModal">Batal</button>
				<button class="admin-action admin-import-submit admin-glass-button" type="submit">Import Data</button>
			</div>
		</form>
	</section>
</div>

<div class="admin-modal-backdrop" id="manualImportModal" data-reopen="{{ old('students') !== null || $errors->has('students') ? 'true' : 'false' }}" hidden>
	<section class="admin-import-modal admin-manual-modal" role="dialog" aria-modal="true" aria-labelledby="manualImportModalTitle">
		<div class="admin-modal-heading">
			<div>
				<h2 id="manualImportModalTitle">Tambah Siswa</h2>
				<p>Tambah hingga 50 siswa sekaligus.</p>
			</div>
			<button type="button" class="admin-modal-close" id="closeManualImportModal" aria-label="Tutup">&times;</button>
		</div>
		<div class="admin-manual-account-note"><strong>Akun login otomatis.</strong> NIS/NISN digunakan sebagai username dan password awal.</div>
		<form action="{{ route('admin.siswa.manual-import') }}" method="POST">
			@csrf
			<div class="admin-manual-rows" id="manualStudentRows">
				@foreach(old('students', [['nama_lengkap' => '', 'nisn' => '', 'kelas_id' => '']]) as $index => $student)
					<div class="admin-manual-row">
						<div class="admin-manual-row-heading"><strong>Data siswa</strong><button type="button" class="admin-row-remove admin-delete-button" aria-label="Hapus baris siswa">Hapus</button></div>
						<div class="admin-manual-fields">
							<label>Nama lengkap
								<input type="text" name="students[{{ $index }}][nama_lengkap]" value="{{ $student['nama_lengkap'] ?? '' }}" maxlength="255" placeholder="Contoh: Alya Putri" required>
								@error("students.{$index}.nama_lengkap")<small class="admin-field-error">{{ $message }}</small>@enderror
							</label>
							<label>NIS / NISN
								<input type="text" name="students[{{ $index }}][nisn]" value="{{ $student['nisn'] ?? '' }}" maxlength="255" placeholder="Nomor induk siswa" required>
								@error("students.{$index}.nisn")<small class="admin-field-error">{{ $message }}</small>@enderror
							</label>
							<label>Kelas <span>(opsional)</span>
								<select name="students[{{ $index }}][kelas_id]">
									<option value="">Tentukan nanti</option>
									@foreach($kelasList as $kelas)
										<option value="{{ $kelas->id }}" @selected((string) ($student['kelas_id'] ?? '') === (string) $kelas->id)>{{ $kelas->nama_kelas }}</option>
									@endforeach
								</select>
								@error("students.{$index}.kelas_id")<small class="admin-field-error">{{ $message }}</small>@enderror
							</label>
						</div>
					</div>
				@endforeach
			</div>
			<button class="admin-add-student-row" id="addStudentRow" type="button">+ Tambah baris siswa</button>
			<p class="admin-manual-limit" id="manualStudentCount" aria-live="polite">1 dari 50 siswa</p>
			<div class="admin-modal-actions">
				<button class="admin-modal-cancel" type="button" id="cancelManualImportModal">Batal</button>
				<button class="admin-action admin-import-submit" type="submit">Simpan Data Siswa</button>
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
		const manualModal = document.getElementById('manualImportModal');
		const manualOpenButton = document.getElementById('openManualImportModal');
		const manualRows = document.getElementById('manualStudentRows');
		const addStudentButton = document.getElementById('addStudentRow');
		const studentCount = document.getElementById('manualStudentCount');
		const classOptions = @json($kelasList->map(fn ($kelas) => ['id' => $kelas->id, 'nama' => $kelas->nama_kelas])->values());
		const deleteStudentModal = document.getElementById('deleteStudentModal');
		const deleteStudentForm = document.getElementById('deleteStudentForm');
		const editStudentModal = document.getElementById('editStudentModal');
		const editStudentForm = document.getElementById('editStudentForm');
		const resetPasswordBtn = document.getElementById('editStudentResetPasswordBtn');
		const resetPasswordInput = document.getElementById('editStudentResetPasswordInput');
		let pendingDeleteStudentButton = null;

		const resetPasswordState = () => {
			if (!resetPasswordBtn || !resetPasswordInput) return;
			resetPasswordInput.value = '0';
			resetPasswordBtn.style.background = '#fef2f2';
			resetPasswordBtn.style.color = '#dc2626';
			resetPasswordBtn.style.borderColor = '#fca5a5';
			resetPasswordBtn.textContent = '🔄 Reset Password ke Default (NISN)';
		};

		if (resetPasswordBtn && resetPasswordInput) {
			resetPasswordBtn.addEventListener('click', () => {
				const isSet = resetPasswordInput.value === '1';
				if (isSet) {
					resetPasswordState();
				} else {
					resetPasswordInput.value = '1';
					resetPasswordBtn.style.background = '#ecfdf5';
					resetPasswordBtn.style.color = '#065f46';
					resetPasswordBtn.style.borderColor = '#a7f3d0';
					resetPasswordBtn.textContent = '✔️ Password akan di-reset saat disimpan!';
				}
			});
		}

		document.querySelectorAll('.admin-import-alert').forEach((alert) => {
			window.setTimeout(() => alert.remove(), 3000);
		});

		document.querySelectorAll('[data-delete-student]').forEach((button) => {
			button.addEventListener('click', () => {
				pendingDeleteStudentButton = button;
				button.classList.add('is-awaiting-confirmation');
				document.getElementById('deleteStudentName').textContent = button.dataset.name;
				deleteStudentForm.action = button.dataset.deleteUrl;
				deleteStudentModal.showModal();
			});
		});
		document.getElementById('cancelDeleteStudent').addEventListener('click', () => deleteStudentModal.close());
		deleteStudentModal.addEventListener('close', () => {
			pendingDeleteStudentButton?.classList.remove('is-awaiting-confirmation');
			pendingDeleteStudentButton = null;
		});

		const closeEditStudentModal = () => {
			editStudentModal.hidden = true;
			resetPasswordState();
		};
		document.querySelectorAll('[data-edit-student]').forEach((button) => {
			button.addEventListener('click', () => {
				resetPasswordState();
				document.getElementById('editStudentId').value = button.dataset.id;
				document.getElementById('editStudentName').value = button.dataset.nama;
				document.getElementById('editStudentNisn').value = button.dataset.nisn;
				document.getElementById('editStudentClass').value = button.dataset.kodeKelas;
				editStudentForm.action = editStudentForm.dataset.updateUrl.replace(':id', button.dataset.id);
				editStudentModal.hidden = false;
				document.getElementById('editStudentName').focus();
			});
		});
		document.querySelectorAll('[data-close-student-edit]').forEach((button) => {
			button.addEventListener('click', closeEditStudentModal);
		});
		editStudentModal.addEventListener('click', (event) => {
			if (event.target === editStudentModal) closeEditStudentModal();
		});
		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape' && !editStudentModal.hidden) closeEditStudentModal();
		});
		if (editStudentModal.dataset.reopen === 'true') {
			editStudentForm.action = editStudentForm.dataset.updateUrl.replace(':id', document.getElementById('editStudentId').value);
			editStudentModal.hidden = false;
		}

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

		const updateManualRows = () => {
			const rows = [...manualRows.querySelectorAll('.admin-manual-row')];
			rows.forEach((row, index) => {
				row.querySelector('.admin-manual-row-heading strong').textContent = `Siswa ${index + 1}`;
				row.querySelector('.admin-row-remove').disabled = rows.length === 1;
			});
			studentCount.textContent = `${rows.length} dari 50 siswa`;
			addStudentButton.disabled = rows.length >= 50;
		};

		const closeManualModal = () => {
			manualModal.hidden = true;
			manualOpenButton.focus();
		};

		const addManualRow = () => {
			const index = Math.max(-1, ...[...manualRows.querySelectorAll('[name^="students["]')].map((input) => {
				const match = input.name.match(/^students\[(\d+)\]/);
				return match ? Number(match[1]) : -1;
			})) + 1;
			const row = document.createElement('div');
			row.className = 'admin-manual-row';
			const options = classOptions.map((kelas) => {
				const option = new Option(kelas.nama, kelas.id);
				return option.outerHTML;
			}).join('');
			row.innerHTML = `<div class="admin-manual-row-heading"><strong>Siswa</strong><button type="button" class="admin-row-remove admin-delete-button" aria-label="Hapus baris siswa">Hapus</button></div>
				<div class="admin-manual-fields">
					<label>Nama lengkap<input type="text" name="students[${index}][nama_lengkap]" maxlength="255" placeholder="Contoh: Alya Putri" required></label>
					<label>NIS / NISN<input type="text" name="students[${index}][nisn]" maxlength="255" placeholder="Nomor induk siswa" required></label>
					<label>Kelas <span>(opsional)</span><select name="students[${index}][kelas_id]"><option value="">Tentukan nanti</option>${options}</select></label>
				</div>`;
			manualRows.append(row);
			updateManualRows();
			row.querySelector('input').focus();
		};

		manualOpenButton.addEventListener('click', () => {
			manualModal.hidden = false;
			manualRows.querySelector('input').focus();
		});
		addStudentButton.addEventListener('click', addManualRow);
		manualRows.addEventListener('click', (event) => {
			if (event.target.matches('.admin-row-remove') && manualRows.querySelectorAll('.admin-manual-row').length > 1) {
				event.target.closest('.admin-manual-row').remove();
				updateManualRows();
			}
		});
		document.getElementById('closeManualImportModal').addEventListener('click', closeManualModal);
		document.getElementById('cancelManualImportModal').addEventListener('click', closeManualModal);
		manualModal.addEventListener('click', (event) => {
			if (event.target === manualModal) closeManualModal();
		});
		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape' && !manualModal.hidden) closeManualModal();
		});
		updateManualRows();
		if (manualModal.dataset.reopen === 'true') manualModal.hidden = false;
		if (modal.dataset.reopen === 'true') modal.hidden = false;
	})();
</script>
<script src="{{ asset('admin-pdf-export.js') }}?v=1" defer></script>
@endsection