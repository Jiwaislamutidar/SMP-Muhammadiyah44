
text/x-generic datakelas.blade.php ( HTML document, ASCII text, with CRLF line terminators )
@extends('layouts.admin')

@section('title', 'Data Kelas')

@section('styles')
<link rel="stylesheet" href="{{ asset('admin-css-baru/admin-page.css') }}?v=10">
<style>
    .kelas-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        margin: 20px 0;
    }

    .kelas-toolbar input,
    .kelas-toolbar select,
    .kelas-form input,
    .kelas-form select {
        min-height: 42px;
        padding: 9px 12px;
        border: 1px solid #d8dee5;
        border-radius: 8px;
        background: #fff;
        color: #263445;
        font: inherit;
    }

    .kelas-toolbar input {
        flex: 1;
        min-width: 220px;
    }

    .kelas-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        padding: 9px 15px;
        border: 0;
        border-radius: 8px;
        background: #2563eb;
        color: white;
        font: inherit;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
    }

    .kelas-button.secondary {
        background: #64748b;
    }

    .kelas-button.danger {
        background: #dc2626;
    }

    .kelas-button.light {
        background: #e8eef7;
        color: #263445;
    }

    .kelas-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .kelas-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 750px;
    }

    .kelas-table th,
    .kelas-table td {
        padding: 13px 14px;
        border-bottom: 1px solid #e8edf2;
        text-align: left;
        font-size: 13px;
    }

    .kelas-table th {
        background: #f7f9fc;
        color: #475569;
        font-weight: 700;
    }

    .kelas-table td {
        color: #334155;
    }

    .kelas-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }

    .kelas-alert {
        padding: 12px 15px;
        margin: 15px 0;
        border-radius: 8px;
        font-size: 13px;
    }

    .kelas-alert.success {
        background: #ecfdf5;
        color: #047857;
    }

    .kelas-alert.error {
        background: #fef2f2;
        color: #b91c1c;
    }

    .kelas-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 18px;
        background: rgba(15, 23, 42, .55);
    }

    .kelas-modal.active {
        display: flex;
    }

    .kelas-modal-content {
        width: 100%;
        max-width: 480px;
        max-height: 90vh;
        overflow-y: auto;
        padding: 24px;
        border-radius: 14px;
        background: white;
        box-shadow: 0 20px 60px rgba(0,0,0,.2);
    }

    .kelas-modal-content h2 {
        margin: 0 0 18px;
        color: #1e293b;
        font-size: 20px;
    }

    .kelas-form {
        display: grid;
        gap: 14px;
    }

    .kelas-form label {
        display: grid;
        gap: 6px;
        color: #475569;
        font-size: 13px;
        font-weight: 600;
    }

    .kelas-form input,
    .kelas-form select {
        width: 100%;
        box-sizing: border-box;
    }

    .kelas-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 9px;
        margin-top: 8px;
    }

    .kelas-empty {
        padding: 30px !important;
        text-align: center !important;
        color: #64748b !important;
    }

    @media (max-width: 600px) {
        .kelas-toolbar {
            align-items: stretch;
        }

        .kelas-toolbar input,
        .kelas-toolbar select,
        .kelas-toolbar button,
        .kelas-toolbar a {
            width: 100%;
            box-sizing: border-box;
        }

        .kelas-modal-content {
            padding: 18px;
        }
    }
</style>
@endsection

@section('content')

<div class="admin-page-header">
    <div>
        <h1>Data Kelas</h1>
        <p>Kelola data kelas dan informasi wali kelas</p>
    </div>

    <div class="admin-header-actions">
        <a href="{{ route('admin.kelas.export-pdf', request()->only('search', 'tingkat')) }}"
           class="kelas-button">
            Unduh Data PDF
        </a>

        <button type="button" class="kelas-button" id="openAddKelas">
            + Tambah Kelas
        </button>
    </div>
</div>

@if(session('success'))
    <div class="kelas-alert success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="kelas-alert error">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="kelas-alert error">
        {{ $errors->first() }}
    </div>
@endif

<form class="kelas-toolbar" method="GET" action="{{ route('admin.datakelas') }}">
    <input
        type="search"
        name="search"
        value="{{ request('search') }}"
        placeholder="Cari nama kelas, wali kelas, atau ruangan..."
    >

    <select name="tingkat">
        <option value="">Semua Tingkat</option>
        <option value="VII" @selected(request('tingkat') === 'VII')>Kelas VII</option>
        <option value="VIII" @selected(request('tingkat') === 'VIII')>Kelas VIII</option>
        <option value="IX" @selected(request('tingkat') === 'IX')>Kelas IX</option>
    </select>

    <button type="submit" class="kelas-button">Cari</button>

    <a href="{{ route('admin.datakelas') }}" class="kelas-button secondary">
        Reset
    </a>

    <span style="font-size:13px;color:#64748b;">
        Total {{ $totalKelas }} Kelas
    </span>
</form>

<section class="panel kelas-table-wrap">
    <table class="kelas-table">
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama Kelas</th>
                <th>Tingkat</th>
                <th>Wali Kelas</th>
                <th>Ruangan</th>
                <th>Jumlah Murid</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
            @forelse($kelasList as $index => $kelas)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $kelas->nama_kelas }}</strong></td>
                    <td>{{ $kelas->tingkat }}</td>
                    <td>{{ $kelas->wali_kelas }}</td>
                    <td>{{ $kelas->ruangan }}</td>
                    <td>{{ $kelas->jumlah_murid }}</td>
                    <td>
                        <div class="kelas-actions">
                            <button
                                type="button"
                                class="kelas-button secondary edit-kelas"
                                data-id="{{ $kelas->id }}"
                                data-nama="{{ $kelas->nama_kelas }}"
                                data-tingkat="{{ $kelas->tingkat }}"
                                data-wali="{{ $kelas->wali_kelas }}"
                                data-ruangan="{{ $kelas->ruangan }}"
                                data-jumlah="{{ $kelas->jumlah_murid }}"
                            >
                                Edit
                            </button>

                            <form
                                method="POST"
                                action="{{ route('admin.kelas.destroy', $kelas) }}"
                                onsubmit="return confirm('Yakin ingin menghapus kelas {{ $kelas->nama_kelas }}?')"
                            >
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="kelas-button danger">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="kelas-empty">
                        Belum ada data kelas yang sesuai.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<div class="kelas-modal" id="addKelasModal">
    <div class="kelas-modal-content">
        <h2>Tambah Kelas</h2>

        <form action="{{ route('admin.kelas.store') }}" method="POST" class="kelas-form">
            @csrf

            <label>
                Nama Kelas
                <input type="text" name="nama_kelas" value="{{ old('nama_kelas') }}" placeholder="Contoh: 7.1" required>
            </label>

            <label>
                Tingkat
                <select name="tingkat" required>
                    <option value="">Pilih Tingkat</option>
                    <option value="VII" @selected(old('tingkat') === 'VII')>VII</option>
                    <option value="VIII" @selected(old('tingkat') === 'VIII')>VIII</option>
                    <option value="IX" @selected(old('tingkat') === 'IX')>IX</option>
                </select>
            </label>

            <label>
                Wali Kelas
                <input type="text" name="wali_kelas" value="{{ old('wali_kelas') }}" placeholder="Nama wali kelas" required>
            </label>

            <label>
                Ruangan
                <input type="text" name="ruangan" value="{{ old('ruangan') }}" placeholder="Contoh: R.7.1" required>
            </label>

            <label>
                Jumlah Murid
                <input type="number" name="jumlah_murid" value="{{ old('jumlah_murid', 0) }}" min="0" max="1000" required>
            </label>

            <div class="kelas-modal-actions">
                <button type="button" class="kelas-button secondary close-kelas-modal">
                    Batal
                </button>

                <button type="submit" class="kelas-button">
                    Simpan Kelas
                </button>
            </div>
        </form>
    </div>
</div>

<div class="kelas-modal" id="editKelasModal">
    <div class="kelas-modal-content">
        <h2>Edit Data Kelas</h2>

        <form
            method="POST"
            id="editKelasForm"
            class="kelas-form"
            data-update-url="{{ url('/data-kelas') }}"
        >
            @csrf
            @method('PUT')

            <label>
                Nama Kelas
                <input type="text" name="nama_kelas" id="editNamaKelas" required>
            </label>

            <label>
                Tingkat
                <select name="tingkat" id="editTingkatKelas" required>
                    <option value="VII">VII</option>
                    <option value="VIII">VIII</option>
                    <option value="IX">IX</option>
                </select>
            </label>

            <label>
                Wali Kelas
                <input type="text" name="wali_kelas" id="editWaliKelas" required>
            </label>

            <label>
                Ruangan
                <input type="text" name="ruangan" id="editRuanganKelas" required>
            </label>

            <label>
                Jumlah Murid
                <input type="number" name="jumlah_murid" id="editJumlahMurid" min="0" max="1000" required>
            </label>

            <div class="kelas-modal-actions">
                <button type="button" class="kelas-button secondary close-kelas-modal">
                    Batal
                </button>

                <button type="submit" class="kelas-button">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const addModal = document.getElementById('addKelasModal');
    const editModal = document.getElementById('editKelasModal');
    const editForm = document.getElementById('editKelasForm');

    document.getElementById('openAddKelas').addEventListener('click', () => {
        addModal.classList.add('active');
    });

    document.querySelectorAll('.close-kelas-modal').forEach(button => {
        button.addEventListener('click', () => {
            addModal.classList.remove('active');
            editModal.classList.remove('active');
        });
    });

    [addModal, editModal].forEach(modal => {
        modal.addEventListener('click', event => {
            if (event.target === modal) {
                modal.classList.remove('active');
            }
        });
    });

    document.querySelectorAll('.edit-kelas').forEach(button => {
        button.addEventListener('click', () => {
            const id = button.dataset.id;

            document.getElementById('editNamaKelas').value = button.dataset.nama;
            document.getElementById('editTingkatKelas').value = button.dataset.tingkat;
            document.getElementById('editWaliKelas').value = button.dataset.wali;
            document.getElementById('editRuanganKelas').value = button.dataset.ruangan;
            document.getElementById('editJumlahMurid').value = button.dataset.jumlah;

            editForm.action = editForm.dataset.updateUrl + '/' + id;
            editModal.classList.add('active');
        });
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            addModal.classList.remove('active');
            editModal.classList.remove('active');
        }
    });
})();
</script>

@endsection