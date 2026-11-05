@extends('layouts.admin')
@section('title', 'Data Guru')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/dataguru.css') }}?v=7">@endsection
@section('content')
<div class="admin-page-header">
	<div>
		<h1>Data Guru</h1>
		<p>Kelola guru &amp; staf pendidik</p>
	</div>
	<div class="admin-header-actions">
		<button class="admin-action admin-action-outline guru-import-trigger" type="button" data-open-modal="importGuruModal" aria-haspopup="dialog">↑ Import Excel</button>
		<button class="admin-action guru-manual-trigger" type="button" data-open-modal="addGuruModal">+ Tambah Guru</button>
	</div>
</div>

@if (session('success'))
	<div class="guru-alert success" role="status">{{ session('success') }}</div>
@endif
@if (session('error'))
	<div class="guru-alert error" role="alert">{{ session('error') }}</div>
@endif
@if ($errors->any())
	<div class="guru-alert error" role="alert">{{ $errors->first() }}</div>
@endif

<form class="admin-toolbar" method="GET" action="{{ route('admin.guru.index') }}">
	<input class="admin-search" type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama, NBM, atau mata pelajaran..." aria-label="Cari guru">
	<label for="guruStatusFilter">Status:</label>
	<select class="admin-select" id="guruStatusFilter" name="status" onchange="this.form.submit()">
		<option value="">Semua Status</option>
		<option value="aktif" @selected (request('status') === 'aktif')>Aktif</option>
		<option value="nonaktif" @selected (request('status') === 'nonaktif')>Nonaktif</option>
	</select>
	<span class="admin-total" id="guruTotal">Total {{ $totalGuru }} Guru</span>
</form>

<section class="panel admin-table-wrap">
	<table class="admin-table guru-table">
		<thead>
			<tr>
				<th>Guru &amp; Identitas</th>
				<th>Mata Pelajaran</th>
				<th>Beban Mengajar</th>
				<th>Status</th>
				<th>Aksi</th>
			</tr>
		</thead>
		<tbody id="guruTableBody">
			@forelse ($gurus as $guru)
				@php
					$inisial = collect(explode(' ', $guru->nama_lengkap))->filter()->take(2)->map(fn ($bagian) => mb_substr($bagian, 0, 1))->implode('');
					$totalJam = $guru->mapels->sum('pivot.jumlah_jam');
				@endphp
				<tr data-guru-row="{{ $guru->id }}">
					<td>
						<div class="guru-identity">
							<span class="initial">{{ mb_strtoupper($inisial) }}</span>
							<div>
								<strong>{{ $guru->nama_lengkap }}</strong>
								<small>{{ $guru->nip ?: 'NBM belum diisi' }}</small>
							</div>
						</div>
					</td>
					<td>
						<div class="guru-subjects">
							@forelse ($guru->mapels as $mapel)
								<span class="guru-subject">{{ $mapel->nama_mapel }}</span>
							@empty
								<span class="guru-muted">Belum ada mapel</span>
							@endforelse
						</div>
					</td>
					<td><span class="guru-hours">{{ $totalJam }} JP/Minggu</span></td>
					<td><span class="guru-status">{{ ucfirst($guru->status) }}</span></td>
					<td>
						<div class="guru-row-actions">
							<button class="guru-action-edit" type="button" data-edit-guru
								data-id="{{ $guru->id }}"
								data-nama="{{ $guru->nama_lengkap }}"
								data-nip="{{ $guru->nip }}"
								data-status="{{ $guru->status }}"
								data-mapel-items="{{ $guru->mapels->map(fn ($mapel) => ['mapel_id' => $mapel->id, 'jumlah_jam' => $mapel->pivot->jumlah_jam])->values()->toJson() }}">Edit</button>
							<button class="guru-action-delete" type="button" data-delete-guru data-id="{{ $guru->id }}" data-delete-url="{{ route('admin.guru.destroy', $guru->id) }}">Hapus</button>
						</div>
					</td>
				</tr>
			@empty
				<tr data-empty-row><td class="admin-empty-row" colspan="5">Belum ada data guru yang sesuai.</td></tr>
			@endforelse
		</tbody>
	</table>
	<div class="admin-pagination">
		<span id="guruShowing">Menampilkan {{ $gurus->count() }} dari {{ $totalGuru }} guru</span>
	</div>
</section>

<template id="guruMapelRowTemplate">
	<div class="guru-mapel-row">
		<div class="guru-mapel-row-fields">
			<label>Pilih Mapel
				<select class="admin-select" data-mapel-select name="mapel_items[__INDEX__][mapel_id]">
					<option value="">Pilih mata pelajaran</option>
					@foreach ($mapels as $mapel)
						<option value="{{ $mapel->id }}">{{ $mapel->nama_mapel }}</option>
					@endforeach
				</select>
			</label>
			<label>Atau Mapel Baru
				<input class="admin-input" data-mapel-new type="text" name="mapel_items[__INDEX__][nama_baru]" maxlength="255" placeholder="Nama mata pelajaran">
			</label>
		</div>
		<div class="guru-mapel-row-hours">
			<label>Jam (JP)
				<input class="admin-input" type="number" name="mapel_items[__INDEX__][jumlah_jam]" min="1" max="65535" step="1" placeholder="Contoh: 6">
			</label>
			<button class="guru-remove-mapel" type="button" data-remove-mapel aria-label="Batalkan baris mapel">Batal</button>
		</div>
	</div>
</template>

<div class="guru-modal-backdrop" id="addGuruModal" hidden>
	<section class="guru-modal" role="dialog" aria-modal="true" aria-labelledby="addGuruTitle">
		<div class="guru-modal-heading">
			<div><h2 id="addGuruTitle">Tambah Guru</h2></div>
			<button class="guru-modal-close" type="button" data-close-modal aria-label="Tutup">&times;</button>
		</div>
		<form action="{{ route('admin.guru.store') }}" method="POST" class="guru-form">
			@csrf
			<input type="hidden" name="form_context" value="add">
			<label>Nama lengkap<input class="admin-input" type="text" name="nama_lengkap" value="{{ old('form_context') === 'add' ? old('nama_lengkap') : '' }}" required></label>
			<label>NBM (username &amp; password awal)<input class="admin-input" type="text" name="nip" value="{{ old('form_context') === 'add' ? old('nip') : '' }}" required></label>
			<label>Status<select class="admin-select" name="status" required><option value="aktif">Aktif</option><option value="nonaktif" @selected (old('form_context') === 'add' && old('status') === 'nonaktif')>Nonaktif</option></select></label>
			<fieldset class="guru-mapel-fieldset"><legend>Mata Pelajaran &amp; Jam</legend>
				<div class="guru-mapel-items" data-mapel-items data-next-index="0"></div>
				<button class="guru-add-mapel" type="button" data-add-mapel>+ Tambah Baris Mapel &amp; Jam</button>
			</fieldset>
			<div class="guru-modal-actions"><button class="guru-button-secondary" type="button" data-close-modal>Batal</button><button class="admin-action" type="submit">Simpan Guru</button></div>
		</form>
	</section>
</div>

<div class="guru-modal-backdrop" id="importGuruModal" hidden>
	<section class="guru-modal" role="dialog" aria-modal="true" aria-labelledby="importGuruTitle">
		<div class="guru-modal-heading">
			<div><h2 id="importGuruTitle">Import Excel</h2><p>Kolom: Kode, Mata Pelajaran, Nama Guru, Jml. Jam</p></div>
			<button class="guru-modal-close" type="button" data-close-modal aria-label="Tutup">&times;</button>
		</div>
		<form action="{{ route('admin.guru.import') }}" method="POST" enctype="multipart/form-data" class="guru-form">
			@csrf
			<input type="hidden" name="form_context" value="import">

			<label class="import-dropzone" for="guruImportFile">
				<span class="dropzone-icon">📁</span>
				<span class="dropzone-text">Pilih atau seret file Excel/CSV ke sini</span>
				<span class="dropzone-hint">Format .xlsx atau .csv (maksimal 10 MB)</span>
				<input id="guruImportFile" type="file" name="file" accept=".xlsx,.csv" required hidden>
			</label>
			<div class="file-preview" id="guruFilePreview" hidden>
				📄 File terpilih: <strong id="guruImportFileName"></strong>
			</div>

			<a class="guru-template-link" href="{{ route('admin.guru.template') }}">📥 Unduh Template CSV</a>

			<div class="guru-modal-actions">
				<button class="guru-button-secondary" type="button" data-close-modal>Batal</button>
				<button class="admin-action" id="guruImportSubmit" type="submit">Import Data</button>
			</div>
		</form>
	</section>
</div>

<div class="guru-modal-backdrop" id="editGuruModal" hidden>
	<section class="guru-modal" role="dialog" aria-modal="true" aria-labelledby="editGuruTitle">
		<div class="guru-modal-heading">
			<div><h2 id="editGuruTitle">Edit Guru</h2></div>
			<button class="guru-modal-close" type="button" data-close-modal aria-label="Tutup">&times;</button>
		</div>
		<form id="editGuruForm" method="POST" class="guru-form" data-update-url="{{ route('admin.guru.update', ':id') }}">
			@csrf
			@method('PUT')
			<input type="hidden" name="form_context" value="edit">
			<input type="hidden" name="edit_guru_id" id="editGuruId">
			<label>Nama lengkap<input class="admin-input" id="editGuruName" type="text" name="nama_lengkap" required></label>
			<label>NBM (username)<input class="admin-input" id="editGuruNip" type="text" name="nip" required></label>
			<label>Status<select class="admin-select" id="editGuruStatus" name="status" required><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select></label>
			<fieldset class="guru-mapel-fieldset"><legend>Mata Pelajaran &amp; Jam Mengajar</legend>
				<div class="guru-mapel-items" data-mapel-items data-next-index="0"></div>
				<button class="guru-add-mapel" type="button" data-add-mapel>+ Tambah Baris Mapel &amp; Jam</button>
			</fieldset>
			<div class="guru-modal-actions"><button class="guru-button-secondary" type="button" data-close-modal>Batal</button><button class="admin-action" type="submit">Simpan Perubahan</button></div>
		</form>
	</section>
</div>

<div class="guru-modal-backdrop" id="deleteGuruModal" hidden>
	<section class="guru-modal guru-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="deleteGuruTitle">
		<div class="guru-modal-heading">
			<div><h2 id="deleteGuruTitle">Hapus Data Guru?</h2><p id="deleteGuruMessage">Data guru dan akun login terkait akan dihapus.</p></div>
			<button class="guru-modal-close" type="button" data-close-modal aria-label="Tutup">&times;</button>
		</div>
		<div class="guru-modal-actions">
			<button class="guru-button-secondary" type="button" data-close-modal>Batal</button>
			<button class="guru-action-delete" id="confirmDeleteGuru" type="button">Hapus</button>
		</div>
	</section>
</div>

<script>
	(() => {
		const modals = [...document.querySelectorAll('.guru-modal-backdrop')];
		const tableBody = document.getElementById('guruTableBody');
		const statusFilter = document.getElementById('guruStatusFilter');
		const alertHost = document.querySelector('.admin-page-header');
		const deleteModal = document.getElementById('deleteGuruModal');
		let pendingDeleteButton = null;
		let alertTimer;
		const closeModal = (modal) => { modal.hidden = true; };
		const openModal = (id) => {
			modals.forEach(closeModal);
			const modal = document.getElementById(id);
			if (modal) modal.hidden = false;
		};
		const showAlert = (message, type = 'success') => {
			window.clearTimeout(alertTimer);
			document.querySelectorAll('.guru-alert').forEach((alert) => alert.remove());
			const alert = document.createElement('div');
			alert.className = `guru-alert ${type}`;
			alert.setAttribute('role', type === 'error' ? 'alert' : 'status');
			alert.textContent = message;
			alertHost.after(alert);
			alertTimer = window.setTimeout(() => alert.remove(), 3000);
		};
		const updateCounts = (total) => {
			const visibleRows = tableBody.querySelectorAll('[data-guru-row]').length;
			document.getElementById('guruTotal').textContent = `Total ${total} Guru`;
			document.getElementById('guruShowing').textContent = `Menampilkan ${visibleRows} dari ${total} guru`;
			const emptyRow = tableBody.querySelector('[data-empty-row]');
			if (visibleRows === 0 && !emptyRow) {
				const row = document.createElement('tr');
				row.dataset.emptyRow = '';
				const cell = document.createElement('td');
				cell.className = 'admin-empty-row';
				cell.colSpan = 5;
				cell.textContent = 'Belum ada data guru yang sesuai.';
				row.append(cell);
				tableBody.append(row);
			} else if (visibleRows > 0) {
				emptyRow?.remove();
			}
		};
		const removeRow = (row, total) => {
			row.remove();
			updateCounts(total);
		};

		document.querySelectorAll('[data-open-modal]').forEach((button) => {
			button.addEventListener('click', () => openModal(button.dataset.openModal));
		});
		document.querySelectorAll('[data-close-modal]').forEach((button) => {
			button.addEventListener('click', () => closeModal(button.closest('.guru-modal-backdrop')));
		});
		modals.forEach((modal) => modal.addEventListener('click', (event) => {
			if (event.target === modal) closeModal(modal);
		}));
		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape') modals.forEach(closeModal);
		});

		const rowTemplate = document.getElementById('guruMapelRowTemplate');
		const addMapelRow = (container, item = {}) => {
			const row = rowTemplate.content.firstElementChild.cloneNode(true);
			const index = container.dataset.nextIndex++;
			row.querySelectorAll('[name]').forEach((input) => {
				input.name = input.name.replaceAll('__INDEX__', index);
			});
			const select = row.querySelector('[data-mapel-select]');
			const newMapel = row.querySelector('[data-mapel-new]');
			select.value = item.mapel_id || '';
			row.querySelector('input[type="number"]').value = item.jumlah_jam || '';
			select.addEventListener('change', () => {
				if (select.value) newMapel.value = '';
			});
			newMapel.addEventListener('input', () => {
				if (newMapel.value.trim()) select.value = '';
			});
			row.querySelector('[data-remove-mapel]').addEventListener('click', () => row.remove());
			container.append(row);
		};
		document.querySelectorAll('[data-add-mapel]').forEach((button) => {
			button.addEventListener('click', () => addMapelRow(button.closest('fieldset').querySelector('[data-mapel-items]')));
		});

		const importFile = document.getElementById('guruImportFile');
		const importFileName = document.getElementById('guruImportFileName');
		const importPreview = document.getElementById('guruFilePreview');
		const importDropzone = document.querySelector('label[for="guruImportFile"]');

		const updateImportFile = (file) => {
			if (file) {
				importFileName.textContent = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
				if (importPreview) importPreview.hidden = false;
			} else {
				if (importPreview) importPreview.hidden = true;
			}
		};

		if (importFile) {
			importFile.addEventListener('change', () => updateImportFile(importFile.files[0]));
		}
		if (importDropzone && importFile) {
			importDropzone.addEventListener('dragover', (e) => { e.preventDefault(); importDropzone.classList.add('is-dragging'); });
			importDropzone.addEventListener('dragleave', () => importDropzone.classList.remove('is-dragging'));
			importDropzone.addEventListener('drop', (e) => {
				e.preventDefault();
				importDropzone.classList.remove('is-dragging');
				if (e.dataTransfer.files.length) {
					importFile.files = e.dataTransfer.files;
					updateImportFile(e.dataTransfer.files[0]);
				}
			});
		}

		const editForm = document.getElementById('editGuruForm');
		document.querySelectorAll('[data-edit-guru]').forEach((button) => {
			button.addEventListener('click', () => {
				document.getElementById('editGuruName').value = button.dataset.nama;
				document.getElementById('editGuruNip').value = button.dataset.nip;
				document.getElementById('editGuruStatus').value = button.dataset.status;
				document.getElementById('editGuruId').value = button.dataset.id;
				editForm.action = editForm.dataset.updateUrl.replace(':id', button.dataset.id);
				const mapelItems = JSON.parse(button.dataset.mapelItems || '[]');
				const mapelContainer = editForm.querySelector('[data-mapel-items]');
				mapelContainer.replaceChildren();
				mapelContainer.dataset.nextIndex = '0';
				mapelItems.forEach((item) => addMapelRow(mapelContainer, item));
				openModal('editGuruModal');
			});
		});

		document.querySelectorAll('.guru-alert').forEach((alert) => {
			window.setTimeout(() => alert.remove(), 3000);
		});

		document.querySelectorAll('[data-delete-guru]').forEach((button) => {
			button.addEventListener('click', () => {
				pendingDeleteButton = button;
				document.getElementById('deleteGuruMessage').textContent =
					`Data guru ${button.closest('tr').querySelector('.guru-identity strong').textContent} dan akun login terkait akan dihapus.`;
				openModal('deleteGuruModal');
			});
		});

		document.getElementById('confirmDeleteGuru').addEventListener('click', async (event) => {
			if (!pendingDeleteButton) return;
			const button = event.currentTarget;
			button.disabled = true;
			try {
				const csrfToken = document.querySelector('#editGuruForm input[name="_token"]').value;
				const response = await fetch(pendingDeleteButton.dataset.deleteUrl, {
					method: 'POST',
					headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
					body: new URLSearchParams({ _method: 'DELETE' }),
				});
				const result = await response.json();
				if (!response.ok) throw new Error(result.message || 'Data guru gagal dihapus.');
				removeRow(pendingDeleteButton.closest('tr'), result.total_guru);
				closeModal(deleteModal);
				pendingDeleteButton = null;
				showAlert(result.message);
			} catch (error) {
				showAlert(error.message || 'Data guru gagal dihapus.', 'error');
			} finally {
				button.disabled = false;
			}
		});

		editForm.addEventListener('submit', async (event) => {
			event.preventDefault();
			const submitButton = editForm.querySelector('button[type="submit"]');
			submitButton.disabled = true;
			try {
				const response = await fetch(editForm.action, {
					method: 'POST',
					headers: { Accept: 'application/json' },
					body: new FormData(editForm),
				});
				const result = await response.json();
				if (!response.ok) {
					const firstError = Object.values(result.errors || {}).flat()[0];
					throw new Error(firstError || result.message || 'Data guru gagal diperbarui.');
				}

				const guru = result.guru;
				const row = tableBody.querySelector(`[data-guru-row="${guru.id}"]`);
				if (statusFilter.value && statusFilter.value !== guru.status) {
					removeRow(row, result.total_guru);
				} else {
					row.querySelector('.guru-identity strong').textContent = guru.nama_lengkap;
					row.querySelector('.guru-identity small').textContent = guru.nip || 'NBM belum diisi';
					row.querySelector('.guru-status').textContent = guru.status.charAt(0).toUpperCase() + guru.status.slice(1);
					row.querySelector('.guru-hours').textContent = `${guru.total_jam} JP/Minggu`;
					const subjectList = row.querySelector('.guru-subjects');
					subjectList.replaceChildren();
					if (guru.mapels.length) {
						guru.mapels.forEach((name) => {
							const subject = document.createElement('span');
							subject.className = 'guru-subject';
							subject.textContent = name;
							subjectList.append(subject);
						});
					} else {
						const empty = document.createElement('span');
						empty.className = 'guru-muted';
						empty.textContent = 'Belum ada mapel';
						subjectList.append(empty);
					}

					const editButton = row.querySelector('[data-edit-guru]');
					editButton.dataset.nama = guru.nama_lengkap;
					editButton.dataset.nip = guru.nip || '';
					editButton.dataset.status = guru.status;
					editButton.dataset.mapelItems = JSON.stringify(guru.mapel_items);
					const initials = guru.nama_lengkap.trim().split(/\s+/).slice(0, 2).map((part) => part.charAt(0)).join('');
					row.querySelector('.guru-identity .initial').textContent = initials.toUpperCase();
				}

				closeModal(document.getElementById('editGuruModal'));
				showAlert(result.message);
			} catch (error) {
				showAlert(error.message || 'Data guru gagal diperbarui.', 'error');
			} finally {
				submitButton.disabled = false;
			}
		});

		@if ($errors->any())
			const errorContext = @json(old('form_context'));
			const oldMapelItems = @json(old('mapel_items', []));
			if (errorContext === 'add' || errorContext === 'edit') {
				const form = document.querySelector(`#${errorContext === 'add' ? 'addGuruModal' : 'editGuruModal'} form`);
				const mapelContainer = form.querySelector('[data-mapel-items]');
				oldMapelItems.forEach((item) => addMapelRow(mapelContainer, item));
				if (errorContext === 'edit') {
					form.action = form.dataset.updateUrl.replace(':id', @json(old('edit_guru_id')));
					document.getElementById('editGuruName').value = @json(old('nama_lengkap'));
					document.getElementById('editGuruNip').value = @json(old('nip'));
					document.getElementById('editGuruStatus').value = @json(old('status'));
				}
			}
			openModal(errorContext === 'import' ? 'importGuruModal' : (errorContext === 'edit' ? 'editGuruModal' : 'addGuruModal'));
		@endif
	})();
</script>
@endsection