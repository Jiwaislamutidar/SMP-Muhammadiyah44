@extends('layouts.admin')
@section('title', 'Data Kelas')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/datakelas.css') }}?v=6">@endsection
@section('content')

<div class="admin-page-header">
  <div>
    <h1>Data Kelas</h1>
    <p>Kelola rombongan belajar &amp; wali kelas</p>
  </div>
  <button class="admin-action" type="button" data-add-class>+ Tambah Kelas</button>
</div>

@if (session('success'))
  <div class="admin-alert success" role="status">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div class="admin-alert error" role="alert">{{ session('error') }}</div>
@endif
@if ($errors->any())
  <div class="admin-alert error" role="alert">{{ $errors->first() }}</div>
@endif

<form class="admin-toolbar" method="GET" action="{{ route('admin.datakelas') }}">
  <input class="admin-search" type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama kelas, wali kelas, atau ruangan..." aria-label="Cari kelas">
  <select class="admin-select" name="tingkat" onchange="this.form.submit()">
    <option value="">Semua Tingkat</option>
    <option value="VII" @selected(request('tingkat') === 'VII')>VII</option>
    <option value="VIII" @selected(request('tingkat') === 'VIII')>VIII</option>
    <option value="IX" @selected(request('tingkat') === 'IX')>IX</option>
  </select>
  <span class="admin-total class-total">Total {{ $totalKelas }} Kelas</span>
</form>

<section class="panel admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Kode Kelas</th>
        <th>Wali Kelas</th>
        <th>Jumlah Murid</th>
        <th>Ruangan</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($kelasList as $kelas)
        <tr>
          <td>
            <strong class="class-code">{{ $kelas->nama_kelas }}</strong>
          </td>
          <td><strong>{{ $kelas->wali_kelas ?: 'Belum diisi' }}</strong></td>
          <td><span class="class-count">{{ $kelas->jumlah_murid }} Murid</span></td>
          <td>{{ $kelas->ruangan ?: 'Belum diisi' }}</td>
          <td>
            <button class="class-edit-button" type="button" data-edit-class
              data-id="{{ $kelas->id }}"
              data-nama="{{ $kelas->nama_kelas }}"
              data-tingkat="{{ $kelas->tingkat }}"
              data-wali="{{ $kelas->wali_kelas }}"
              data-ruangan="{{ $kelas->ruangan }}"
              data-jumlah="{{ $kelas->jumlah_murid }}"
              data-update-url="{{ route('admin.kelas.update', $kelas) }}">Edit</button>
            <button class="class-delete-button" type="button" data-delete-class
              data-code="{{ $kelas->nama_kelas }}"
              data-delete-url="{{ route('admin.kelas.destroy', $kelas) }}">Hapus</button>
          </td>
        </tr>
      @empty
        <tr><td class="admin-empty-row" colspan="5">Belum ada data kelas yang sesuai.</td></tr>
      @endforelse
    </tbody>
  </table>
</section>

<dialog class="class-delete-modal" id="deleteClassModal" aria-labelledby="deleteClassTitle">
  <form method="POST" id="deleteClassForm">
    @csrf
    @method('DELETE')
    <h2 id="deleteClassTitle">Apakah kamu yakin untuk menghapus kelas <span id="deleteClassCode"></span>?</h2>
    <p>Kelas yang masih memiliki murid atau jadwal tidak dapat dihapus.</p>
    <div class="class-delete-actions">
      <button class="admin-action-outline" type="button" id="cancelDeleteClass">Batal</button>
      <button class="class-delete-confirm" type="submit">Ya, Hapus</button>
    </div>
  </form>
</dialog>

<div class="modal-overlay" id="kelasModal" aria-hidden="true">
  <section class="modal-box kelas-modal" role="dialog" aria-modal="true" aria-labelledby="kelasModalTitle">
    <div class="modal-header">
      <h2 id="kelasModalTitle">Tambah Kelas</h2>
      <button class="modal-close-btn" type="button" data-close-class-modal aria-label="Tutup">&times;</button>
    </div>
    <form id="kelasForm" method="POST" action="{{ route('admin.kelas.store') }}" data-store-url="{{ route('admin.kelas.store') }}">
      @csrf
      <input type="hidden" name="form_context" value="add">
      <input type="hidden" name="kelas_id" value="">
      <input type="hidden" id="kelasMethod" name="_method" value="PUT" disabled>
      <div class="modal-body kelas-form-grid">
        <label>Nama Kelas
          <input class="admin-input" type="text" name="nama_kelas" maxlength="100" placeholder="Contoh: 7A" required>
        </label>
        <label>Tingkat
          <select class="admin-select" name="tingkat" required>
            <option value="">Pilih tingkat</option>
            <option value="VII">VII</option>
            <option value="VIII">VIII</option>
            <option value="IX">IX</option>
          </select>
        </label>
        <label>Wali Kelas
          <input class="admin-input" type="text" name="wali_kelas" maxlength="255" placeholder="Nama wali kelas" required>
        </label>
        <label>Ruangan
          <input class="admin-input" type="text" name="ruangan" maxlength="100" placeholder="Contoh: Ruang 101" required>
        </label>
        <label class="kelas-count-field">Jumlah Murid
          <input class="admin-input" type="number" name="jumlah_murid" min="0" max="1000" step="1" placeholder="0" required>
        </label>
      </div>
      <div class="modal-footer">
        <button class="admin-action-outline kelas-cancel-button" type="button" data-close-class-modal>Batal</button>
        <button class="admin-action" id="kelasSubmitButton" type="submit">Simpan Kelas</button>
      </div>
    </form>
  </section>
</div>

@php
  $oldKelasForm = [
    'context' => old('form_context'),
    'id' => old('kelas_id'),
    'nama' => old('nama_kelas'),
    'tingkat' => old('tingkat'),
    'wali' => old('wali_kelas'),
    'ruangan' => old('ruangan'),
    'jumlah' => old('jumlah_murid'),
  ];
@endphp

<script>
(() => {
  const modal = document.getElementById('kelasModal');
  const form = document.getElementById('kelasForm');
  const method = document.getElementById('kelasMethod');
  const title = document.getElementById('kelasModalTitle');
  const submit = document.getElementById('kelasSubmitButton');
  const fields = {
    id: form.elements.kelas_id,
    context: form.elements.form_context,
    nama: form.elements.nama_kelas,
    tingkat: form.elements.tingkat,
    wali: form.elements.wali_kelas,
    ruangan: form.elements.ruangan,
    jumlah: form.elements.jumlah_murid
  };

  const showModal = () => {
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    fields.nama.focus();
  };
  const closeModal = () => {
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };
  const openAddModal = () => {
    form.reset();
    form.action = form.dataset.storeUrl;
    fields.id.value = '';
    fields.context.value = 'add';
    method.disabled = true;
    title.textContent = 'Tambah Kelas';
    submit.textContent = 'Simpan Kelas';
    showModal();
  };
  const openEditModal = (button) => {
    fields.id.value = button.dataset.id;
    fields.context.value = 'edit';
    fields.nama.value = button.dataset.nama;
    fields.tingkat.value = button.dataset.tingkat || '';
    fields.wali.value = button.dataset.wali || '';
    fields.ruangan.value = button.dataset.ruangan || '';
    fields.jumlah.value = button.dataset.jumlah || '0';
    form.action = button.dataset.updateUrl;
    method.disabled = false;
    title.textContent = 'Edit Data Kelas';
    submit.textContent = 'Simpan Perubahan';
    showModal();
  };

  document.querySelector('[data-add-class]').addEventListener('click', openAddModal);
  document.querySelectorAll('[data-edit-class]').forEach((button) => {
    button.addEventListener('click', () => openEditModal(button));
  });
  document.querySelectorAll('[data-close-class-modal]').forEach((button) => {
    button.addEventListener('click', closeModal);
  });
  modal.addEventListener('click', (event) => {
    if (event.target === modal) closeModal();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal.classList.contains('show')) closeModal();
  });

  const deleteClassModal = document.getElementById('deleteClassModal');
  const deleteClassForm = document.getElementById('deleteClassForm');
  let pendingDeleteClassButton = null;
  document.querySelectorAll('[data-delete-class]').forEach((button) => {
    button.addEventListener('click', () => {
      pendingDeleteClassButton = button;
      button.classList.add('is-awaiting-confirmation');
      document.getElementById('deleteClassCode').textContent = button.dataset.code;
      deleteClassForm.action = button.dataset.deleteUrl;
      deleteClassModal.showModal();
    });
  });
  document.getElementById('cancelDeleteClass').addEventListener('click', () => deleteClassModal.close());
  deleteClassModal.addEventListener('close', () => {
    pendingDeleteClassButton?.classList.remove('is-awaiting-confirmation');
    pendingDeleteClassButton = null;
  });

  const oldForm = @json($oldKelasForm);
  if (oldForm.context === 'add') {
    openAddModal();
    fields.nama.value = oldForm.nama || '';
    fields.tingkat.value = oldForm.tingkat || '';
    fields.wali.value = oldForm.wali || '';
    fields.ruangan.value = oldForm.ruangan || '';
    fields.jumlah.value = oldForm.jumlah || '';
  } else if (oldForm.context === 'edit') {
    const button = Array.from(document.querySelectorAll('[data-edit-class]'))
      .find((item) => item.dataset.id === String(oldForm.id));
    if (button) {
      openEditModal(button);
      fields.nama.value = oldForm.nama || '';
      fields.tingkat.value = oldForm.tingkat || '';
      fields.wali.value = oldForm.wali || '';
      fields.ruangan.value = oldForm.ruangan || '';
      fields.jumlah.value = oldForm.jumlah || '';
    }
  }
})();
</script>

@endsection
