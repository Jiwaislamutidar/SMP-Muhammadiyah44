@extends('layouts.admin')
@section('title', 'Data Guru')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/dataguru.css') }}?v=4">@endsection
@section('content')
<div class="admin-page-header">
	<div>
		<h1>Data Guru</h1>
		<p>Kelola data guru dan staf pendidik SMP Muhammadiyah 44</p>
	</div>
	<div class="admin-header-actions">
		<button class="admin-action guru-import-trigger" type="button" data-open-modal="importGuruModal" aria-haspopup="dialog">Import Data Excel</button>
		<button class="admin-action guru-manual-trigger" type="button" data-open-modal="addGuruModal">+ Tambah Guru Manual</button>
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
	<input class="admin-search" type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIP/kode, atau mata pelajaran..." aria-label="Cari guru">
	<label for="guruStatusFilter">Status:</label>
	<select class="admin-select" id="guruStatusFilter" name="status" onchange="this.form.submit()">
		<option value="">Semua Status</option>
		<option value="aktif" @selected (request('status') === 'aktif')>Aktif</option>
		<option value="nonaktif" @selected (request('status') === 'nonaktif')>Nonaktif</option>
	</select>
	<button class="admin-filter-submit" type="submit">Cari</button>
	<span class="admin-total">Total {{ $totalGuru }} Guru</span>
</form>

<section class="panel admin-table-wrap">
	<table class="admin-table guru-table">
		<thead>
			<tr>
				<th>Guru &amp; Identitas</th>
				<th>Mata Pelajaran yang Diampu</th>
				<th>Total Beban Mengajar</th>
				<th>Status Kepegawaian</th>
				<th>Aksi</th>
			</tr>
		</thead>
		<tbody>
			@forelse ($gurus as $guru)
				@php
					$inisial = collect(explode(' ', $guru->nama_lengkap))->filter()->take(2)->map(fn ($bagian) => mb_substr($bagian, 0, 1))->implode('');
					$totalJam = $guru->mapels->sum('pivot.jumlah_jam');
				@endphp
				<tr>
					<td class="guru-identity">
						<span class="initial">{{ mb_strtoupper($inisial) }}</span>
						<strong>{{ $guru->nama_lengkap }}</strong>
						<small>{{ $guru->nip ?: 'NIP/Kode belum diisi' }}</small>
					</td>
					<td>
						<div class="guru-subjects">
							@forelse ($guru->mapels as $index => $mapel)
								<span class="guru-subject subject-{{ $index % 6 }}">{{ $mapel->nama_mapel }}</span>
							@empty
								<span class="guru-muted">Belum ada mata pelajaran</span>
							@endforelse
						</div>
					</td>
					<td><strong class="guru-hours">{{ $totalJam }} JP/Minggu</strong></td>
					<td><span class="guru-status {{ $guru->status === 'aktif' ? 'is-active' : 'is-inactive' }}">{{ ucfirst($guru->status) }}</span></td>
					<td>
						<div class="guru-row-actions">
							<button class="link-action guru-action-edit" type="button" data-edit-guru
								data-id="{{ $guru->id }}"
								data-nama="{{ $guru->nama_lengkap }}"
								data-nip="{{ $guru->nip }}"
								data-status="{{ $guru->status }}"
								data-mapel-items="{{ $guru->mapels->map(fn ($mapel) => ['mapel_id' => $mapel->id, 'jumlah_jam' => $mapel->pivot->jumlah_jam])->values()->toJson() }}">Edit</button>
							<form action="{{ route('admin.guru.destroy', $guru->id) }}" method="POST" onsubmit="return confirm('Ubah status guru ini?')">
								@csrf
								@method('DELETE')
								<button class="link-action guru-action-toggle {{ $guru->status === 'aktif' ? 'is-destructive' : 'is-restore' }}" type="submit">{{ $guru->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
							</form>
						</div>
					</td>
				</tr>
			@empty
				<tr><td class="admin-empty-row" colspan="5">Belum ada data guru yang sesuai.</td></tr>
			@endforelse
		</tbody>
	</table>
	<div class="admin-pagination">
		<span>Menampilkan {{ $gurus->firstItem() ?? 0 }}–{{ $gurus->lastItem() ?? 0 }} dari {{ $gurus->total() }} guru</span>
		<div class="admin-page-links">
			@if ($gurus->previousPageUrl())<a href="{{ $gurus->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹</a>@endif
			<span>Halaman {{ $gurus->currentPage() }} dari {{ $gurus->lastPage() }}</span>
			@if ($gurus->nextPageUrl())<a href="{{ $gurus->nextPageUrl() }}" aria-label="Halaman berikutnya">›</a>@endif
		</div>
	</div>
</section>

<template id="guruMapelRowTemplate">
	<div class="guru-mapel-row">
		<div class="guru-mapel-row-fields">
			<label>Mapel yang tersedia
				<select class="admin-select" data-mapel-select name="mapel_items[__INDEX__][mapel_id]">
					<option value="">Pilih mata pelajaran</option>
					@foreach ($mapels as $mapel)
						<option value="{{ $mapel->id }}">{{ $mapel->nama_mapel }}</option>
					@endforeach
				</select>
			</label>
			<label>Atau ketik mapel baru
				<input class="admin-input" data-mapel-new type="text" name="mapel_items[__INDEX__][nama_baru]" maxlength="255" placeholder="Nama mata pelajaran">
			</label>
		</div>
		<div class="guru-mapel-row-hours">
			<label>Jumlah Jam (JP)
				<input class="admin-input" type="number" name="mapel_items[__INDEX__][jumlah_jam]" min="1" max="65535" step="1" placeholder="Contoh: 6">
			</label>
			<button class="guru-remove-mapel" type="button" data-remove-mapel aria-label="Hapus baris mapel">Hapus</button>
		</div>
	</div>
</template>

<div class="guru-modal-backdrop" id="addGuruModal" hidden>
	<section class="guru-modal" role="dialog" aria-modal="true" aria-labelledby="addGuruTitle">
		<div class="guru-modal-heading">
			<div><h2 id="addGuruTitle">Tambah Guru Manual</h2><p>Masukkan identitas dan mata pelajaran yang diampu.</p></div>
			<button class="guru-modal-close" type="button" data-close-modal aria-label="Tutup">&times;</button>
		</div>
		<form action="{{ route('admin.guru.store') }}" method="POST" class="guru-form">
			@csrf
			<input type="hidden" name="form_context" value="add">
			<label>Nama lengkap<input class="admin-input" type="text" name="nama_lengkap" value="{{ old('form_context') === 'add' ? old('nama_lengkap') : '' }}" required></label>
			<label>NIP/Kode<input class="admin-input" type="text" name="nip" value="{{ old('form_context') === 'add' ? old('nip') : '' }}"></label>
			<label>Status<select class="admin-select" name="status" required><option value="aktif">Aktif</option><option value="nonaktif" @selected (old('form_context') === 'add' && old('status') === 'nonaktif')>Nonaktif</option></select></label>
			<fieldset class="guru-mapel-fieldset"><legend>Mata pelajaran &amp; beban mengajar</legend>
				<p class="guru-mapel-help">Pilih mapel yang tersedia atau ketik mapel baru, lalu isi jumlah jam per minggu.</p>
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
			<div><h2 id="importGuruTitle">Import Data Excel</h2><p>Unggah file .xlsx atau .csv dengan kolom Kode, Mata Pelajaran, Nama Guru, dan Jml. Jam.</p></div>
			<button class="guru-modal-close" type="button" data-close-modal aria-label="Tutup">&times;</button>
		</div>
		<form action="{{ route('admin.guru.import') }}" method="POST" enctype="multipart/form-data" class="guru-form">
			@csrf
			<input type="hidden" name="form_context" value="import">

			<div style="display:grid;gap:8px;">
				<label for="guruImportFile" style="font-size:12px;font-weight:600;">File Excel/CSV</label>
				<input id="guruImportFile" type="file" name="file" accept=".xlsx,.csv" required style="width:100%;padding:10px;border:1px dashed #a9c7b3;border-radius:8px;background:#f7faf8;font-size:12px;">
				<p id="guruImportFileName" aria-live="polite" style="margin:0;font-size:12px;color:#6b756f;">Belum ada file dipilih (.xlsx atau .csv, maksimal 10 MB)</p>
			</div>

			<a class="guru-template-link" href="{{ route('admin.guru.template') }}">Download template CSV</a>

			<div style="display:flex;justify-content:flex-end;gap:9px;flex-wrap:wrap;">
				<button class="guru-button-secondary" type="button" data-close-modal>Batal</button>
				<button id="guruImportSubmit" type="submit" style="min-width:150px;min-height:44px;padding:0 22px;border:0;border-radius:8px;background:#087443;color:#fff;font-size:14px;font-weight:700;cursor:pointer;">Import Data</button>
			</div>
		</form>
	</section>
</div>

<div class="guru-modal-backdrop" id="editGuruModal" hidden>
	<section class="guru-modal" role="dialog" aria-modal="true" aria-labelledby="editGuruTitle">
		<div class="guru-modal-heading">
			<div><h2 id="editGuruTitle">Edit Data Guru</h2><p>Perbarui identitas, status, dan penugasan mata pelajaran.</p></div>
			<button class="guru-modal-close" type="button" data-close-modal aria-label="Tutup">&times;</button>
		</div>
		<form id="editGuruForm" method="POST" class="guru-form" data-update-url="{{ route('admin.guru.update', ':id') }}">
			@csrf
			@method('PUT')
			<input type="hidden" name="form_context" value="edit">
			<input type="hidden" name="edit_guru_id" id="editGuruId">
			<label>Nama lengkap<input class="admin-input" id="editGuruName" type="text" name="nama_lengkap" required></label>
			<label>NIP/Kode<input class="admin-input" id="editGuruNip" type="text" name="nip"></label>
			<label>Status<select class="admin-select" id="editGuruStatus" name="status" required><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select></label>
			<fieldset class="guru-mapel-fieldset"><legend>Mata pelajaran &amp; beban mengajar</legend>
				<p class="guru-mapel-help">Pilih mapel yang tersedia atau ketik mapel baru, lalu isi jumlah jam per minggu.</p>
				<div class="guru-mapel-items" data-mapel-items data-next-index="0"></div>
				<button class="guru-add-mapel" type="button" data-add-mapel>+ Tambah Baris Mapel &amp; Jam</button>
			</fieldset>
			<div class="guru-modal-actions"><button class="guru-button-secondary" type="button" data-close-modal>Batal</button><button class="admin-action" type="submit">Simpan Perubahan</button></div>
		</form>
	</section>
</div>

<script>
	(() => {
		const modals = [...document.querySelectorAll('.guru-modal-backdrop')];
		const closeModal = (modal) => { modal.hidden = true; };
		const openModal = (id) => {
			modals.forEach(closeModal);
			const modal = document.getElementById(id);
			if (modal) modal.hidden = false;
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
		importFile.addEventListener('change', () => {
			const file = importFile.files[0];
			importFileName.textContent = file
				? `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`
				: 'Belum ada file dipilih (.xlsx atau .csv, maksimal 10 MB)';
		});

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