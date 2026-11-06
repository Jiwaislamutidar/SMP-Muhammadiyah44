@extends('layouts.admin')
@section('title', 'Data Guru')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/dataguru.css') }}?v=8">@endsection
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
					$namaGuru = trim($guru->nama_lengkap);
					$inisial = collect(explode(' ', $namaGuru))->filter()->take(2)->map(fn ($bagian) => mb_substr($bagian, 0, 1))->implode('');
					$totalJam = $guru->mapels->sum('pivot.jumlah_jam');
				@endphp
				<tr data-guru-row="{{ $guru->id }}">
					<td>
						<div class="guru-identity">
							<span class="initial">{{ mb_strtoupper($inisial) }}</span>
							<div>
								<strong>{{ $namaGuru }}</strong>
								<small>{{ $guru->nbm ?: 'NBM belum diisi' }}</small>
							</div>
						</div>
					</td>
					<td>
						<div class="guru-subjects">
							@if ($guru->mapels->isNotEmpty())
								@foreach ($guru->mapels as $mapel)
									<span class="guru-subject">
										<span>{{ trim($mapel->nama_mapel) }}</span>
										<span class="guru-subject-code">({{ $mapel->kode }})</span>
									</span>
								@endforeach
							@else
								<span class="guru-muted">Belum ada mapel</span>
							@endif
						</div>
					</td>
					<td><span class="guru-hours">{{ $totalJam }} JP/Minggu</span></td>
					<td><span class="guru-status">{{ ucfirst($guru->status) }}</span></td>
					<td>
						<div class="guru-row-actions">
							<button class="guru-action-edit" type="button" data-edit-guru
								data-id="{{ $guru->id }}"
								data-nama="{{ $namaGuru }}"
								data-nbm="{{ $guru->nbm }}"
								data-status="{{ $guru->status }}"
								data-mapel-items="{{ $guru->mapels->map(fn ($mapel) => ['mapel_id' => $mapel->id, 'nama_mapel' => $mapel->nama_mapel, 'kode_mapel' => $mapel->kode, 'jml_jam' => $mapel->pivot->jumlah_jam])->values()->toJson() }}">Edit</button>
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
			<label>Nama Mapel
				<input class="admin-input" data-mapel-name type="text" name="mapel_items[__INDEX__][nama_mapel]" maxlength="255" placeholder="Nama mata pelajaran">
			</label>
			<label>Kode Mapel
				<input class="admin-input" data-mapel-code type="text" name="mapel_items[__INDEX__][kode_mapel]" maxlength="100" placeholder="Contoh: 2'1">
			</label>
		</div>
		<div class="guru-mapel-row-hours">
			<label>Jam Mengajar (JP)
				<input class="admin-input" data-mapel-hours type="number" name="mapel_items[__INDEX__][jml_jam]" min="1" max="65535" step="1" placeholder="Contoh: 6">
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
		<form action="{{ route('admin.guru.store') }}" method="POST" class="guru-form" id="addGuruForm">
			@csrf
			<input type="hidden" name="form_context" value="add">
			<label>Nama lengkap<input class="admin-input" type="text" name="nama_lengkap" value="{{ old('form_context') === 'add' ? old('nama_lengkap') : '' }}" required></label>
			<label>NBM (username &amp; password awal, opsional)<input class="admin-input" type="text" name="nbm" value="{{ old('form_context') === 'add' ? old('nbm') : '' }}"><small>Password awal otomatis menggunakan NBM yang diisi.</small></label>
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
			<div><h2 id="importGuruTitle">Import Excel</h2><p>Kolom: NBM, Nama Guru, Kode Mapel, Mata Pelajaran, Jml. Jam (NBM, mapel, dan jam boleh kosong)</p></div>
			<button class="guru-modal-close" type="button" data-close-modal aria-label="Tutup">&times;</button>
		</div>
		<form action="{{ route('admin.guru.import') }}" method="POST" enctype="multipart/form-data" class="guru-form" id="importGuruForm">
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

			<a class="guru-template-link" href="{{ route('admin.guru.template') }}">📥 Unduh Template Excel (.xlsx)</a>

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
			<label>NBM (username, opsional)<input class="admin-input" id="editGuruNbm" type="text" name="nbm"></label>
			<label>Password Baru
				<input class="admin-input" id="editGuruPassword" type="password" name="password" minlength="6" autocomplete="new-password">
				<small>Biarkan kosong jika tidak ingin mengubah password guru saat ini.</small>
			</label>
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
		const openModal = (id) => {
			modals.forEach(closeModal);
			const modal = document.getElementById(id);
			if (modal) {
				modal.classList.remove('is-closing');
				modal.hidden = false;
			}
		};
		const closeModal = (modal) => {
			if (!modal || modal.hidden || modal.classList.contains('is-closing')) return;
			modal.classList.add('is-closing');
			if (modal === deleteModal) {
				pendingDeleteButton?.classList.remove('is-awaiting-confirmation');
				pendingDeleteButton = null;
			}
			window.setTimeout(() => {
				if (modal.classList.contains('is-closing')) {
					modal.hidden = true;
					modal.classList.remove('is-closing');
				}
			}, 180);
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
		const createGuruRow = (guru) => {
			const row = document.createElement('tr');
			row.dataset.guruRow = guru.id;
			const identityCell = document.createElement('td');
			const identity = document.createElement('div');
			identity.className = 'guru-identity';
			const initial = document.createElement('span');
			initial.className = 'initial';
			initial.textContent = guru.nama_lengkap.trim().split(/\s+/).slice(0, 2).map((part) => part.charAt(0)).join('').toUpperCase();
			const details = document.createElement('div');
			const name = document.createElement('strong');
			name.textContent = guru.nama_lengkap;
			const nbm = document.createElement('small');
			nbm.textContent = guru.nbm || 'NBM belum diisi';
			details.append(name, nbm);
			identity.append(initial, details);
			identityCell.append(identity);

			const subjectCell = document.createElement('td');
			const subjects = document.createElement('div');
			subjects.className = 'guru-subjects';
			const subject = document.createElement('span');
			subject.className = guru.mapels.length ? 'guru-subject' : 'guru-muted';
			subject.textContent = guru.mapels.length ? guru.mapels.join(', ') : 'Belum ada mapel';
			subjects.append(subject);
			subjectCell.append(subjects);

			const hoursCell = document.createElement('td');
			const hours = document.createElement('span');
			hours.className = 'guru-hours';
			hours.textContent = `${guru.total_jam} JP/Minggu`;
			hoursCell.append(hours);

			const statusCell = document.createElement('td');
			const status = document.createElement('span');
			status.className = 'guru-status';
			status.textContent = guru.status.charAt(0).toUpperCase() + guru.status.slice(1);
			statusCell.append(status);

			const actionsCell = document.createElement('td');
			const actions = document.createElement('div');
			actions.className = 'guru-row-actions';
			const editButton = document.createElement('button');
			editButton.className = 'guru-action-edit';
			editButton.type = 'button';
			editButton.dataset.editGuru = '';
			editButton.dataset.id = guru.id;
			editButton.dataset.nama = guru.nama_lengkap;
			editButton.dataset.nbm = guru.nbm || '';
			editButton.dataset.status = guru.status;
			editButton.dataset.mapelItems = JSON.stringify(guru.mapel_items);
			editButton.textContent = 'Edit';
			const deleteButton = document.createElement('button');
			deleteButton.className = 'guru-action-delete';
			deleteButton.type = 'button';
			deleteButton.dataset.deleteGuru = '';
			deleteButton.dataset.id = guru.id;
			deleteButton.dataset.deleteUrl = @json(route('admin.guru.destroy', ['id' => '__ID__'])).replace('__ID__', guru.id);
			deleteButton.textContent = 'Hapus';
			actions.append(editButton, deleteButton);
			actionsCell.append(actions);
			row.append(identityCell, subjectCell, hoursCell, statusCell, actionsCell);
			return row;
		};
		const refreshGuruTable = async () => {
			const response = await fetch(window.location.href, { headers: { Accept: 'application/json' } });
			const result = await response.json();
			if (!response.ok) throw new Error(result.message || 'Tabel guru gagal diperbarui.');
			tableBody.replaceChildren(...result.gurus.map(createGuruRow));
			updateCounts(result.total_guru);
		};

		['addGuruForm', 'importGuruForm'].forEach((formId) => {
			const form = document.getElementById(formId);
			form.addEventListener('submit', async (event) => {
				event.preventDefault();
				const submitButton = form.querySelector('button[type="submit"]');
				const originalLabel = submitButton.textContent;
				submitButton.disabled = true;
				submitButton.textContent = formId === 'importGuruForm' ? 'Mengimpor...' : 'Menyimpan...';
				try {
					const response = await fetch(form.action, {
						method: 'POST',
						headers: { Accept: 'application/json' },
						body: new FormData(form),
					});
					const result = await response.json();
					if (!response.ok) {
						const firstError = Object.values(result.errors || {}).flat()[0];
						throw new Error(firstError || result.message || 'Data guru gagal disimpan.');
					}

					closeModal(form.closest('.guru-modal-backdrop'));
					form.reset();
					updateImportFile(null);
					showAlert(result.message);
					try {
						await refreshGuruTable();
					} catch (error) {
						showAlert(`${result.message} Namun tabel belum dapat diperbarui.`, 'error');
					}
				} catch (error) {
					showAlert(error.message || 'Data guru gagal disimpan.', 'error');
				} finally {
					submitButton.disabled = false;
					submitButton.textContent = originalLabel;
				}
			});
		});

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
			row.querySelector('[data-mapel-name]').value = item.nama_mapel ?? item.nama_baru ?? '';
			row.querySelector('[data-mapel-code]').value = item.kode_mapel ?? item.kode_baru ?? '';
			row.querySelector('[data-mapel-hours]').value = item.jml_jam ?? item.jumlah_jam ?? '';
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
		tableBody.addEventListener('click', (event) => {
			const button = event.target.closest('[data-edit-guru]');
			if (button) {
				document.getElementById('editGuruName').value = button.dataset.nama;
				document.getElementById('editGuruNbm').value = button.dataset.nbm;
				document.getElementById('editGuruPassword').value = '';
				document.getElementById('editGuruStatus').value = button.dataset.status;
				document.getElementById('editGuruId').value = button.dataset.id;
				editForm.action = editForm.dataset.updateUrl.replace(':id', button.dataset.id);
				const mapelItems = JSON.parse(button.dataset.mapelItems || '[]');
				const mapelContainer = editForm.querySelector('[data-mapel-items]');
				mapelContainer.replaceChildren();
				mapelContainer.dataset.nextIndex = '0';
				mapelItems.forEach((item) => addMapelRow(mapelContainer, item));
				openModal('editGuruModal');
				return;
			}

			const deleteButton = event.target.closest('[data-delete-guru]');
			if (deleteButton) {
				pendingDeleteButton = deleteButton;
				deleteButton.classList.add('is-awaiting-confirmation');
				document.getElementById('deleteGuruMessage').textContent =
					`Data guru ${deleteButton.closest('tr').querySelector('.guru-identity strong').textContent} dan akun login terkait akan dihapus.`;
				openModal('deleteGuruModal');
			}
		});

		document.querySelectorAll('.guru-alert').forEach((alert) => {
			window.setTimeout(() => alert.remove(), 3000);
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
				const row = pendingDeleteButton.closest('tr');
				pendingDeleteButton.classList.remove('is-awaiting-confirmation');
				removeRow(row, result.total_guru);
				closeModal(deleteModal);
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
					row.querySelector('.guru-identity small').textContent = guru.nbm || 'NBM belum diisi';
					row.querySelector('.guru-status').textContent = guru.status.charAt(0).toUpperCase() + guru.status.slice(1);
					row.querySelector('.guru-hours').textContent = `${guru.total_jam} JP/Minggu`;
					const subjectList = row.querySelector('.guru-subjects');
					subjectList.replaceChildren();
					if (guru.mapels.length) {
						const subject = document.createElement('span');
						subject.className = 'guru-subject';
						subject.textContent = guru.mapels.join(', ');
						subjectList.append(subject);
					} else {
						const empty = document.createElement('span');
						empty.className = 'guru-muted';
						empty.textContent = 'Belum ada mapel';
						subjectList.append(empty);
					}

					const editButton = row.querySelector('[data-edit-guru]');
					editButton.dataset.nama = guru.nama_lengkap;
					editButton.dataset.nbm = guru.nbm || '';
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
					document.getElementById('editGuruNbm').value = @json(old('nbm'));
					document.getElementById('editGuruStatus').value = @json(old('status'));
				}
			}
			openModal(errorContext === 'import' ? 'importGuruModal' : (errorContext === 'edit' ? 'editGuruModal' : 'addGuruModal'));
		@endif
	})();
</script>
@endsection