@extends('layouts.admin')
@section('title', 'Rekap Absensi')
@section('styles')
<link rel="stylesheet" href="{{ asset('admin css/rekapabsensi.css') }}?v=14">
<style>
  .rekap-mode-tab, .dash-tab, .admin-action, .date-mode-btn {
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .rekap-mode-tab:hover, .dash-tab:hover, .admin-action:hover:not(:disabled), .date-mode-btn:hover {
    transform: translateY(-1px);
  }
  .rekap-mode-tab:active, .admin-action:active {
    transform: translateY(0);
  }
  .admin-table th { background: #f8fafc; color: #334155; font-weight: 700; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; }
  .admin-table td { vertical-align: middle; }
  .photo-thumbnail-btn {
    padding: 4px 10px;
    background: #f1f5f9;
    color: #0f172a;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .photo-thumbnail-btn:hover {
    background: #334155;
    color: #fff;
    border-color: #334155;
  }
  .sub-guru-nav {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 10px;
  }
  .sub-guru-tab {
    padding: 6px 14px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #475569;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
  }
  .sub-guru-tab.active {
    background: #e8edf5;
    color: #1e293b;
    border-color: #cbd5e1;
  }

  .rekap-mode-switch {
    --tab-count: 2;
    --active-tab: 0;
    position: relative;
    display: grid;
    grid-template-columns: repeat(var(--tab-count), minmax(0, 1fr));
    gap: 4px;
    width: min(100%, 520px);
    margin: 0 0 18px;
    padding: 4px;
    border: 1px solid #dbe3ee;
    border-radius: 12px;
    background: #f1f5f9;
    box-shadow: none;
  }
  .rekap-mode-switch::before {
    content: '';
    position: absolute;
    z-index: 0;
    inset: 4px auto 4px 4px;
    width: calc((100% - 8px - (var(--tab-count) - 1) * 4px) / var(--tab-count));
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(30, 41, 59, .08);
    transform: translateX(calc(var(--active-tab) * (100% + 4px)));
    transition: transform .28s cubic-bezier(.4, 0, .2, 1);
  }
  .rekap-mode-tab {
    position: relative;
    z-index: 1;
    min-height: 38px;
    padding: 8px 14px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: #64748b;
    font-size: 13px;
    font-weight: 650;
    cursor: pointer;
  }
  .rekap-mode-tab.active { color: #1e293b; }
  .rekap-mode-tab:hover { color: #334155; }
  .rekap-mode-tab:focus-visible, .sub-guru-tab:focus-visible, .dash-tab:focus-visible, .date-mode-btn:focus-visible { outline: 2px solid #94a3b8; outline-offset: 2px; }
  .rekap-date-mode-switch > div { display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px !important; }
  .date-mode-btn { border-color: #dbe3ee !important; background: #fff !important; color: #475569 !important; box-shadow: none !important; }
  .date-mode-btn.active { border-color: #64748b !important; background: #334155 !important; color: #fff !important; }
  .sub-guru-nav { gap: 6px; margin-bottom: 14px; padding-bottom: 8px; }
  .sub-guru-tab { border-radius: 8px; border-color: #dbe3ee; background: #f8fafc; color: #475569; }
  .sub-guru-tab:hover { border-color: #94a3b8; background: #f1f5f9; }
  .dash-tabs { gap: 6px !important; margin-bottom: 14px !important; padding-bottom: 6px !important; }
  .dash-tab { position: relative; padding: 8px 10px; color: #64748b !important; font-size: 13px; }
  .dash-tab.active { color: #334155 !important; }
  .dash-tab.active::after { content: ''; position: absolute; right: 10px; bottom: -7px; left: 10px; height: 2px; border-radius: 2px; background: #64748b; }
  .rekap-filter-label, .admin-table th, .rekap-mode-tab, .sub-guru-tab, .dash-tab, button { user-select: none; }
  .admin-table td { line-height: 1.45; }
  .panel-title { color: #1e293b; line-height: 1.35; }
  .panel-head p { line-height: 1.5; }
  .rekap-table th, .rekap-table td { padding: 11px 12px; }

  /* Attendance report UI refinements */
  .admin-page-header { margin-bottom: 2px; }
  .admin-page-header h1 { font-size: 21px; line-height: 1.25; }
  .admin-page-header p { font-size: 12px; line-height: 1.45; }
  .rekap-mode-switch { margin-bottom: 16px; }
  .rekap-mode-tab { min-height: 36px; padding: 7px 13px; font-size: 12px; }
  .rekap-mode-tab.active { color: #fff; background: #087443; }
  .rekap-mode-tab.active:hover { color: #fff; background: #075c36; }
  .admin-action:not(.admin-action-outline), .admin-action-primary { color: #fff; }
  .rekap-mode-tab:focus-visible { outline-color: #16a66a; }

  .rekap-mode-panel { display: grid; gap: 16px; min-width: 0; }
  .rekap-mode-panel[hidden] { display: none !important; }
  .summary-strip { gap: 10px; }
  .summary-item { padding: 13px 15px; border-radius: 10px; }
  .summary-item span { font-size: 10px; }
  .summary-item strong { margin-top: 4px; font-size: 22px; line-height: 1.1; }
  .rekap-toolbar { gap: 10px; padding: 11px; }
  .rekap-filter-label { gap: 4px; font-size: 10px; }
  .rekap-toolbar .admin-input,
  .rekap-toolbar .admin-select { padding: 8px 10px; font-size: 12px; }
  .rekap-total-wrapper { align-self: stretch; }
  .admin-total#rekapTotal { padding: 8px 10px; font-size: 11px; }
  .admin-table-wrap, .sub-guru-content > .panel { min-width: 0; }
  .rekap-table { min-width: 760px; }
  .rekap-table th { padding: 10px 11px; font-size: 10px; }
  .rekap-table td { padding: 10px 11px; font-size: 12px; }
  .rekap-table td:nth-child(2), .rekap-table td:nth-child(4),
  .rekap-table td:nth-child(7), .rekap-table td:nth-child(8) { white-space: nowrap; }
  .rekap-identity { gap: 7px; }
  .rekap-identity strong { font-size: 12px; }
  .rekap-class-badge { display: inline-flex; align-items: center; padding: 3px 7px; font-size: 10px; white-space: nowrap; }
  .rekap-status { display: inline-flex; align-items: center; padding: 3px 7px; border-radius: 999px; background: #f8fafc; font-size: 10px; white-space: nowrap; }

  .sub-guru-nav { gap: 7px; margin: 0; padding: 0 0 9px; }
  .sub-guru-tab { min-height: 34px; padding: 7px 12px; border-radius: 8px; font-size: 11px; transition: color .2s ease, background .2s ease, border-color .2s ease, box-shadow .2s ease, transform .2s ease; }
  .sub-guru-tab:hover { color: #075c36; border-color: #86efac; background: #f0fdf4; box-shadow: 0 3px 8px rgba(8,116,67,.1); transform: translateY(-1px); }
  .sub-guru-tab.active, .sub-guru-tab.active:hover { color: #fff; border-color: #087443; background: #087443; box-shadow: 0 3px 8px rgba(8,116,67,.18); transform: none; }
  .sub-guru-tab:focus-visible { outline: 2px solid #16a66a; outline-offset: 2px; }
  .sub-guru-content > .panel { padding: 16px; border-radius: 12px; }
  .sub-guru-content > .panel > .panel-head { margin-bottom: 12px !important; }
  .panel-title { font-size: 14px; }
  .panel-head p { font-size: 11px !important; line-height: 1.45; }
  .sub-guru-content .admin-toolbar { gap: 8px; }
  .sub-guru-content .dash-tabs { margin: 0 !important; }
  .dash-tab { padding: 7px 9px; font-size: 12px; }
  .dash-count { font-size: 10px; }
  .dash-tab-panel { border-radius: 12px; }
  .sub-guru-content .table-wrap { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 9px; }
  .sub-guru-content .table-wrap .admin-table { min-width: 760px; font-size: 11.5px; }
  .sub-guru-content .table-wrap .admin-table th { padding: 9px 10px; font-size: 9px; }
  .sub-guru-content .table-wrap .admin-table td { padding: 10px; line-height: 1.35; }
  .sub-guru-content .table-wrap .admin-table td small { margin-top: 3px !important; font-size: 10px !important; }
  .sub-guru-content .table-wrap .admin-table td strong { font-size: 11px !important; line-height: 1.35; }
  .photo-thumbnail-btn { min-height: 28px; padding: 5px 9px; border-radius: 6px; font-size: 10px; white-space: nowrap; }
  .photo-thumbnail-btn:focus-visible { outline: 2px solid #16a66a; outline-offset: 2px; }
  .dash-unrecorded { margin-top: 12px !important; padding: 11px !important; }

  @media (max-width: 600px) {
    .rekap-mode-switch { width: 100%; }
    .rekap-mode-tab { padding-inline: 8px; font-size: 11px; }
    .sub-guru-nav { overflow-x: auto; }
    .sub-guru-tab { flex: 0 0 auto; }
    .sub-guru-content > .panel { padding: 12px; }
  }
</style>
@endsection
@section('content')

<div class="admin-page-header">
  <div>
    <h1>Rekap Absensi</h1>
    <p>Kelola dan pantau data kehadiran murid dan guru secara terpusat</p>
  </div>
  <div class="admin-header-actions rekap-actions" id="headerActions">
    <button class="admin-action admin-action-outline admin-delete-button" id="deleteAttendance" type="button">Hapus Data</button>
    <button class="admin-action admin-pdf-export bg-blue-600 hover:bg-blue-700" id="exportAttendance" type="button" data-pdf-export data-export-url="{{ route('admin.rekapabsensi.export') }}" data-export-filename="rekap-absensi.pdf" aria-live="polite">
      <svg class="admin-pdf-export-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 2.5v9m0 0 3.5-3.5M10 11.5 6.5 8M3.5 12.5v3A1.5 1.5 0 0 0 5 17h10a1.5 1.5 0 0 0 1.5-1.5v-3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <span data-export-label>Unduh Rekap PDF</span><span class="admin-pdf-progress" aria-hidden="true"></span>
    </button>
  </div>
</div>

<div class="rekap-mode-switch" role="tablist" aria-label="Pilih mode rekap">
  <button class="rekap-mode-tab active" type="button" role="tab" aria-selected="true" data-mode-target="murid">Rekap Absensi Murid</button>
  <button class="rekap-mode-tab" type="button" role="tab" aria-selected="false" data-mode-target="guru">Rekap Absensi Guru</button>
</div>

<div class="rekap-mode-panel" id="panelMurid" data-mode-panel="murid">
  <div class="summary-strip">
    <div class="summary-item summary-item--present">
      <span>Murid Hadir</span>
      <strong class="summary-count" id="summaryHadir">{{ $report['summary']['hadir'] }}</strong>
    </div>
    <div class="summary-item summary-item--excused">
      <span>Izin / Sakit</span>
      <strong class="summary-count" id="summaryIzinSakit">{{ $report['summary']['izin_sakit'] }}</strong>
    </div>
    <div class="summary-item summary-item--absent">
      <span>Alfa (Tanpa Keterangan)</span>
      <strong class="summary-count" id="summaryAlfa">{{ $report['summary']['alfa'] }}</strong>
    </div>
  </div>

  @if(session('success'))
    <p class="rekap-success" role="status">{{ session('success') }}</p>
  @endif

  <div class="admin-toolbar rekap-toolbar">
    <div class="rekap-date-mode-switch" style="grid-column: span 2; display: flex; flex-direction: column; gap: 6px;">
      <span style="font-size: 11px; font-weight: 600; color: #374151;">Pilih Mode Tampilan Data</span>
      <div style="display: flex; gap: 6px;">
        <button type="button" class="date-mode-btn active" id="btnModeHarian">📅 Harian</button>
        <button type="button" class="date-mode-btn" id="btnModeRentang">📆 Rentang Waktu</button>
      </div>
    </div>

    <label class="rekap-filter-label" id="containerHarian" for="rekapDate" style="grid-column: span 2;">
      Tanggal Absensi
      <input class="admin-input" id="rekapDate" type="date" value="{{ $selectedDate }}" required>
    </label>

    <fieldset class="rekap-export-range" id="containerRentang" style="grid-column: span 2; display: none;">
      <legend>Rentang Waktu & PDF Dari & Sampai</legend>
      <div class="rekap-range-inputs">
        <label class="rekap-filter-label" for="rekapExportDateFrom">
          Dari
          <input class="admin-input" id="rekapExportDateFrom" type="date" value="{{ $selectedDate }}">
        </label>
        <label class="rekap-filter-label" for="rekapExportDateTo">
          Sampai
          <input class="admin-input" id="rekapExportDateTo" type="date" value="{{ $selectedDate }}">
        </label>
      </div>
    </fieldset>

    <form class="rekap-search-form" method="GET" action="{{ route('admin.rekapabsensi') }}" id="rekapSearchForm">
      <label class="rekap-filter-label" for="rekapSearch">
        Cari Murid
        <input class="admin-search admin-input" id="rekapSearch" name="search" type="search" value="{{ $selectedSearch }}" placeholder="Nama / NISN..." aria-label="Cari murid">
      </label>
    </form>
    <label class="rekap-filter-label" for="rekapClass">
      Kelas
      <select class="admin-select" id="rekapClass">
        <option value="">Semua Kelas</option>
        @foreach($kelasList as $kelas)
          <option value="{{ $kelas->id }}" @selected((string) $selectedClass === (string) $kelas->id)>{{ $kelas->nama_kelas }}</option>
        @endforeach
      </select>
    </label>
    <label class="rekap-filter-label" for="rekapStatus">
      Status
      <select class="admin-select" id="rekapStatus">
        <option value="">Semua Status</option>
        @foreach(['Hadir', 'Izin', 'Sakit', 'Alfa'] as $status)
          <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $status }}</option>
        @endforeach
      </select>
    </label>
    <div class="rekap-total-wrapper">
      <span class="admin-total" id="rekapTotal">Total {{ $report['total'] }} Data</span>
    </div>
  </div>

  <p class="rekap-error" id="rekapError" role="alert" hidden></p>

  <section class="panel admin-table-wrap">
    <table class="admin-table rekap-table">
      <thead>
        <tr>
          <th>Murid</th>
          <th>NISN</th>
          <th>Kelas</th>
          <th>Tanggal</th>
          <th>Status</th>
          <th>Mata Pelajaran</th>
          <th>Jam Pelajaran</th>
          <th>Waktu Presensi</th>
        </tr>
      </thead>
      <tbody id="rekapRows">
        @forelse($report['rows'] as $row)
          <tr>
            <td><div class="rekap-identity"><strong>{{ $row['nama'] }}</strong></div></td>
            <td>{{ $row['nisn'] }}</td>
            <td><span class="rekap-class-badge">{{ $row['kelas'] }}</span></td>
            <td class="rekap-date-cell">{{ $row['tanggal'] }}</td>
            <td><span class="rekap-status rekap-status--{{ strtolower($row['status']) }}">{{ $row['status'] }}</span></td>
            <td>{{ $row['mapel'] }}</td>
            <td>{{ $row['jam_pelajaran'] }}</td>
            <td>{{ $row['waktu'] === '-' ? '-' : substr($row['waktu'], 0, 5) }}</td>
          </tr>
        @empty
          <tr><td class="admin-empty-row" colspan="8">Belum ada data presensi yang sesuai.</td></tr>
        @endforelse
      </tbody>
    </table>
  </section>
</div>

<div class="rekap-mode-panel" id="panelGuru" data-mode-panel="guru" hidden>
  <div class="sub-guru-nav">
    <button class="sub-guru-tab active" type="button" data-sub-guru="harian">Kehadiran Sekolah Harian</button>
    <button class="sub-guru-tab" type="button" data-sub-guru="mapel">Presensi Mengajar Mata Pelajaran</button>
  </div>

  <div class="sub-guru-content" id="subGuruHarian">
    <div class="panel" style="margin-bottom: 16px;">
      <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div class="admin-toolbar" style="margin: 0; border: 0; padding: 0; box-shadow: none;">
          <label class="rekap-filter-label" for="guruPeriodFilter">
            Periode Rekap Guru
            <select class="admin-select" id="guruPeriodFilter" data-url="{{ route('admin.dashboard.guru-period') }}">
              <option value="hari">Hari Ini</option>
              <option value="minggu">7 Hari Terakhir</option>
              <option value="bulan">1 Bulan Terakhir</option>
            </select>
          </label>
        </div>
        <div class="admin-toolbar" style="margin: 0; border: 0; padding: 0; box-shadow: none;">
          <label class="rekap-filter-label" for="guruSessionFilter">
            Sesi Absen
            <select class="admin-select" id="guruSessionFilter">
              <option value="semua">Semua Sesi</option>
              <option value="masuk">Absen Masuk</option>
              <option value="pulang">Absen Pulang</option>
            </select>
          </label>
        </div>
      </div>
    </div>

    <div class="dash-tabs" role="tablist" aria-label="Filter guru">
      <button class="dash-tab active" type="button" role="tab" aria-selected="true" data-guru-tab="hadir">
        Hadir <span class="dash-count" id="guruHadirCountTab">0</span>
      </button>
      <button class="dash-tab" type="button" role="tab" aria-selected="false" data-guru-tab="tidak-hadir">
        Tidak Hadir <span class="dash-count" id="guruTidakHadirCountTab">0</span>
      </button>
    </div>

    <div class="panel dash-tab-panel" id="guruHadirPanel" data-guru-panel="hadir">
      <div class="dash-absence-list" id="guruHadirList">
        <p class="dash-empty">Memuat data presensi guru...</p>
      </div>
    </div>

    <div class="panel dash-tab-panel" id="guruTidakHadirPanel" data-guru-panel="tidak-hadir" hidden>
      <div class="dash-absence-list" id="guruTidakHadirList">
        <p class="dash-empty">Memuat data guru tidak hadir...</p>
      </div>
      <div class="dash-unrecorded" id="guruUnrecordedSection" style="margin-top: 16px; padding: 12px; background: #f8fafc; border-radius: 8px;">
        <h3 style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 8px;">Belum Presensi (<span id="guruUnrecordedCount">0</span>)</h3>
        <div id="guruUnrecordedList"></div>
      </div>
    </div>
  </div>

  <div class="sub-guru-content" id="subGuruMapel" hidden>
    <div class="panel">
      <div class="panel-head" style="margin-bottom: 16px;">
        <div>
          <div class="panel-title">Rekap Mengajar Mata Pelajaran Guru</div>
          <p style="color: #64748b; font-size: 12px; margin-top: 4px;">Daftar aktivitas mengajar, jam mulai/selesai, serta bukti foto presensi guru per mata pelajaran.</p>
        </div>
      </div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Nama Guru</th>
              <th>Mata Pelajaran</th>
              <th>Kelas / Ruangan</th>
              <th>Jam Mengajar</th>
              <th>Foto Mulai</th>
              <th>Foto Selesai</th>
            </tr>
          </thead>
          <tbody id="guruMapelRows">
            <tr><td colspan="7" class="admin-empty-row" style="text-align: center; padding: 24px; color: #64748b;">Memuat rekap mapel guru...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <p class="rekap-error" id="guruFetchError" role="alert" hidden></p>
</div>

<dialog class="rekap-delete-modal" id="deleteModal" aria-labelledby="deleteModalTitle">
  <form method="POST" action="{{ route('admin.rekapabsensi.destroy') }}" id="deleteAttendanceForm">
    @csrf
    @method('DELETE')
    <input type="hidden" name="date" value="{{ $selectedDate }}">
    <input type="hidden" name="date_to" value="{{ $selectedDate }}">
    <input type="hidden" name="kelas">
    <input type="hidden" name="status">
    <input type="hidden" name="search">
    <input type="hidden" name="type" value="murid">
    <input type="hidden" name="periode">
    <div class="rekap-delete-icon" aria-hidden="true">!</div>
    <h2 id="deleteModalTitle">Apakah kamu yakin ingin menghapus data ini?</h2>
    <p>Data presensi yang sesuai dengan filter aktif akan dihapus permanen.</p>
    <div class="rekap-delete-actions">
      <button class="admin-action admin-action-outline" type="button" id="cancelDelete">Batal</button>
      <button class="admin-action rekap-danger-button admin-delete-button admin-delete-button--solid" type="submit">Ya, Hapus Data</button>
    </div>
  </form>
</dialog>

<dialog class="dash-photo-modal" id="attendancePhotoModal" aria-label="Pratinjau foto bukti presensi" style="padding: 20px; border-radius: 12px; background: #0f172a; color: #fff; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <h3 style="font-size: 14px; font-weight: 600; margin: 0;">Bukti Foto Presensi</h3>
    <button class="dash-photo-modal-close" type="button" aria-label="Tutup pratinjau" style="background: #334155; color: #fff; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px;">Tutup</button>
  </div>
  <img id="attendancePhotoFull" alt="Foto bukti presensi" style="display: block; max-width: 100%; max-height: 75vh; margin: 0 auto; object-fit: contain; border-radius: 8px;">
</dialog>

<script>
  const modeTabs = document.querySelectorAll('.rekap-mode-tab');
  const modePanels = document.querySelectorAll('.rekap-mode-panel');
  let activeAttendanceType = 'murid';

  modeTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      modeTabs.forEach((t) => {
        t.classList.remove('active');
        t.setAttribute('aria-selected', 'false');
      });
      tab.classList.add('active');
      tab.setAttribute('aria-selected', 'true');
      document.querySelector('.rekap-mode-switch')?.style.setProperty('--active-tab', String([...modeTabs].indexOf(tab)));
      const target = tab.dataset.modeTarget;
      modePanels.forEach((panel) => {
        const match = panel.dataset.modePanel === target;
        panel.hidden = !match;
      });
      activeAttendanceType = target;
      updateActionUrls(activeFilters());
      if (target === 'guru') {
        loadGuruPeriod();
      }
    });
  });

  const subGuruTabs = document.querySelectorAll('.sub-guru-tab');
  const subGuruContents = document.querySelectorAll('.sub-guru-content');
  subGuruTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      subGuruTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const target = tab.dataset.subGuru;
      document.getElementById('subGuruHarian').hidden = target !== 'harian';
      document.getElementById('subGuruMapel').hidden = target !== 'mapel';
      if (target === 'mapel') {
        loadGuruMapelReport();
      }
    });
  });

  const btnModeHarian = document.getElementById('btnModeHarian');
  const btnModeRentang = document.getElementById('btnModeRentang');
  const containerHarian = document.getElementById('containerHarian');
  const containerRentang = document.getElementById('containerRentang');
  let currentMode = 'harian';

  btnModeHarian.addEventListener('click', () => {
    currentMode = 'harian';
    btnModeHarian.classList.add('active');
    btnModeRentang.classList.remove('active');

    containerHarian.style.display = 'grid';
    containerRentang.style.display = 'none';
    loadReport();
  });

  btnModeRentang.addEventListener('click', () => {
    currentMode = 'rentang';
    btnModeRentang.classList.add('active');
    btnModeHarian.classList.remove('active');

    containerHarian.style.display = 'none';
    containerRentang.style.display = 'grid';
    loadReport();
  });

  const reportUrl = @json(route('admin.rekapabsensi.data'));
  const dateFilter = document.getElementById('rekapDate');
  const exportDateFrom = document.getElementById('rekapExportDateFrom');
  const exportDateTo = document.getElementById('rekapExportDateTo');
  const classFilter = document.getElementById('rekapClass');
  const statusFilter = document.getElementById('rekapStatus');
  const searchFilter = document.getElementById('rekapSearch');
  const reportRows = document.getElementById('rekapRows');
  const reportError = document.getElementById('rekapError');
  const exportLink = document.getElementById('exportAttendance');
  const deleteButton = document.getElementById('deleteAttendance');
  const deleteModal = document.getElementById('deleteModal');
  const deleteForm = document.getElementById('deleteAttendanceForm');
  let searchTimer;
  let reportRequestController;
  let deleteConfirmationPending = false;

  function activeFilters() {
    if (currentMode === 'rentang') {
      return {
        date: exportDateFrom.value,
        date_to: exportDateTo.value,
        kelas: classFilter.value,
        status: statusFilter.value,
        search: searchFilter.value.trim(),
      };
    } else {
      return {
        date: dateFilter.value,
        date_to: dateFilter.value,
        kelas: classFilter.value,
        status: statusFilter.value,
        search: searchFilter.value.trim(),
      };
    }
  }

  function updateActionUrls(filters) {
    if (activeAttendanceType === 'guru') {
      const teacherFilters = {
        type: 'guru',
        periode: guruPeriodFilter.value,
      };
      exportLink.dataset.exportParams = new URLSearchParams(teacherFilters).toString();
      exportLink.disabled = false;
      deleteButton.disabled = false;
      deleteForm.elements['type'].value = teacherFilters.type;
      deleteForm.elements['periode'].value = teacherFilters.periode;
      return;
    }

    if (!filters.date) {
      exportLink.disabled = true;
      deleteButton.disabled = true;
      return;
    }

    const exportFilters = {
      type: 'murid',
      date_from: exportDateFrom.value,
      date_to: exportDateTo.value,
      kelas: filters.kelas,
      status: filters.status,
      search: filters.search,
    };
    const validExportRange = exportFilters.date_from
      && exportFilters.date_to
      && exportFilters.date_to >= exportFilters.date_from;
    exportLink.dataset.exportParams = new URLSearchParams(exportFilters).toString();
    exportLink.disabled = !validExportRange;
    deleteButton.disabled = false;
    
    deleteForm.elements['date'].value = filters.date;
    deleteForm.elements['date_to'].value = filters.date_to;
    deleteForm.elements['kelas'].value = filters.kelas;
    deleteForm.elements['status'].value = filters.status;
    deleteForm.elements['search'].value = filters.search;
    deleteForm.elements['type'].value = 'murid';
    deleteForm.elements['periode'].value = '';
  }

  function addCell(row, value, className = '') {
    const cell = document.createElement('td');
    if (className) cell.className = className;
    cell.textContent = value;
    row.append(cell);
    return cell;
  }

  function renderRows(rows) {
    reportRows.replaceChildren();
    if (!rows.length) {
      const row = document.createElement('tr');
      const empty = addCell(row, 'Belum ada data presensi yang sesuai.', 'admin-empty-row');
      empty.colSpan = 8;
      reportRows.append(row);
      return;
    }

    rows.forEach((item) => {
      const row = document.createElement('tr');
      const identityCell = document.createElement('td');
      const identity = document.createElement('div');
      identity.className = 'rekap-identity';
      const name = document.createElement('strong');
      name.textContent = item.nama;
      identity.append(name);
      identityCell.append(identity);
      row.append(identityCell);
      addCell(row, item.nisn);
      const classCell = document.createElement('td');
      const classBadge = document.createElement('span');
      classBadge.className = 'rekap-class-badge';
      classBadge.textContent = item.kelas;
      classCell.append(classBadge);
      row.append(classCell);
      addCell(row, item.tanggal, 'rekap-date-cell');
      const statusCell = document.createElement('td');
      const badge = document.createElement('span');
      badge.className = `rekap-status rekap-status--${item.status.toLowerCase()}`;
      badge.textContent = item.status;
      statusCell.append(badge);
      row.append(statusCell);
      const mapelCell = document.createElement('td');
      mapelCell.append(makeCellText('span', item.mapel));
      row.append(mapelCell);
      addCell(row, item.jam_pelajaran);
      addCell(row, item.waktu === '-' ? '-' : item.waktu.slice(0, 5));
      reportRows.append(row);
    });
  }

  function makeCellText(tag, value) {
    const element = document.createElement(tag);
    element.textContent = value;
    return element;
  }

  async function loadReport() {
    const filters = activeFilters();
    updateActionUrls(filters);
    reportRequestController?.abort();
    if (!filters.date) {
      reportError.textContent = 'Pilih tanggal untuk memuat data rekap.';
      reportError.hidden = false;
      return;
    }
    reportError.hidden = true;
    reportRequestController = new AbortController();
    try {
      const response = await fetch(`${reportUrl}?${new URLSearchParams(filters)}`, {
        headers: { Accept: 'application/json' },
        signal: reportRequestController.signal,
      });
      if (!response.ok) throw new Error('Data rekap tidak dapat dimuat.');
      const report = await response.json();
      document.getElementById('summaryHadir').textContent = report.summary.hadir;
      document.getElementById('summaryIzinSakit').textContent = report.summary.izin_sakit;
      document.getElementById('summaryAlfa').textContent = report.summary.alfa;
      document.getElementById('rekapTotal').textContent = `Total ${report.total} Data`;
      renderRows(report.rows);
    } catch (error) {
      if (error.name !== 'AbortError') {
        reportError.textContent = error.message || 'Terjadi kesalahan saat memuat data rekap.';
        reportError.hidden = false;
      }
    }
  }

  updateActionUrls(activeFilters());
  [exportDateFrom, exportDateTo].forEach((filter) => filter.addEventListener('change', loadReport));
  [dateFilter, classFilter, statusFilter].forEach((filter) => filter.addEventListener('change', loadReport));
  
  function submitSearch() {
    window.clearTimeout(searchTimer);
    loadReport();
  }

  document.getElementById('rekapSearchForm').addEventListener('submit', (event) => {
    event.preventDefault();
    submitSearch();
  });
  searchFilter.addEventListener('input', () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(submitSearch, 250);
  });
  document.getElementById('deleteAttendance').addEventListener('click', () => {
    deleteConfirmationPending = true;
    deleteButton.classList.add('is-awaiting-confirmation');
    deleteModal.showModal();
  });
  document.getElementById('cancelDelete').addEventListener('click', () => deleteModal.close());
  deleteModal.addEventListener('close', () => {
    if (deleteConfirmationPending) deleteButton.classList.remove('is-awaiting-confirmation');
    deleteConfirmationPending = false;
  });

  const guruTabs = document.querySelectorAll('[data-guru-tab]');
  const guruPanels = document.querySelectorAll('[data-guru-panel]');
  const guruPeriodFilter = document.getElementById('guruPeriodFilter');
  const guruSessionFilter = document.getElementById('guruSessionFilter');
  const guruFetchError = document.getElementById('guruFetchError');
  const guruHadirList = document.getElementById('guruHadirList');
  const guruTidakHadirList = document.getElementById('guruTidakHadirList');
  const guruUnrecordedList = document.getElementById('guruUnrecordedList');
  const guruUnrecordedCount = document.getElementById('guruUnrecordedCount');
  const guruHadirCountTab = document.getElementById('guruHadirCountTab');
  const guruTidakHadirCountTab = document.getElementById('guruTidakHadirCountTab');
  const attendancePhotoModal = document.getElementById('attendancePhotoModal');
  const attendancePhotoFull = document.getElementById('attendancePhotoFull');

  function makeTextElement(tag, className, value) {
    const element = document.createElement(tag);
    if (className) element.className = className;
    element.textContent = value;
    return element;
  }

  function makeAttendanceEntry(record, absent, session = null) {
    const entry = makeTextElement('div', '', '');
    entry.style.display = 'flex';
    entry.style.alignItems = 'center';
    entry.style.justifyContent = 'flex-end';
    entry.style.gap = '8px';
    entry.style.marginTop = '4px';
    entry.dataset.attendanceSession = absent ? 'lainnya' : (session || 'lainnya');
    
    const statusText = absent ? (record.status || 'Hadir') : (session === 'masuk' ? 'Masuk' : (session === 'pulang' ? 'Pulang' : 'Hadir'));
    const status = makeTextElement('span', '', statusText);
    status.style.fontWeight = '700';
    status.style.fontSize = '11px';

    const detail = makeTextElement('small', '', new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short' }).format(new Date(`${record.tanggal}T00:00:00`)));
    const time = session === 'pulang' ? record.jam_pulang : record.jam_masuk;
    if (!absent && time) detail.textContent += ` · ${time.slice(0, 5)}`;
    
    const statusContent = makeTextElement('div', '', '');
    statusContent.style.display = 'flex';
    statusContent.style.flexDirection = 'column';
    statusContent.style.alignItems = 'flex-end';
    statusContent.append(status, detail);
    entry.append(statusContent);

    const actions = makeTextElement('div', '', '');
    actions.style.display = 'inline-flex';
    actions.style.gap = '6px';
    const photoUrl = absent ? record.foto_bukti : (session === 'pulang' ? record.foto_pulang : record.foto_masuk);
    if (photoUrl) {
      const preview = document.createElement('button');
      preview.type = 'button';
      preview.className = 'photo-thumbnail-btn';
      preview.textContent = 'Lihat Foto';
      preview.addEventListener('click', () => {
        attendancePhotoFull.src = photoUrl;
        attendancePhotoModal.showModal();
      });
      actions.append(preview);
    }
    entry.append(actions);
    return entry;
  }

  function renderGuruList(container, teachers, absent) {
    container.replaceChildren();
    if (!teachers.length) {
      container.append(makeTextElement('p', 'dash-empty', absent
        ? 'Tidak ada guru dengan status tidak hadir pada periode ini.'
        : 'Belum ada guru yang tercatat hadir pada periode ini.'));
      return;
    }

    teachers.forEach((teacher) => {
      const row = document.createElement('div');
      row.style.display = 'flex';
      row.style.alignItems = 'center';
      row.style.justifyContent = 'space-between';
      row.style.padding = '10px 0';
      row.style.borderBottom = '1px solid #f1f5f9';

      const person = document.createElement('div');
      person.style.display = 'flex';
      person.style.alignItems = 'center';
      person.style.gap = '10px';
      
      const avatar = makeTextElement('span', '', teacher.nama.slice(0, 1).toUpperCase());
      avatar.style.width = '32px';
      avatar.style.height = '32px';
      avatar.style.background = '#e8edf5';
      avatar.style.color = '#334155';
      avatar.style.borderRadius = '50%';
      avatar.style.display = 'grid';
      avatar.style.placeItems = 'center';
      avatar.style.fontWeight = '700';

      const copy = document.createElement('div');
      const name = makeTextElement('strong', '', teacher.nama);
      const role = makeTextElement('small', '', teacher.mapel);
      role.style.display = 'block';
      role.style.color = '#64748b';
      copy.append(name, role);
      person.append(avatar, copy);
      row.append(person);

      const details = document.createElement('div');
      details.style.display = 'flex';
      details.style.flexDirection = 'column';
      details.style.alignItems = 'flex-end';
      teacher.tanggal.forEach((record) => {
        if (absent) {
          details.append(makeAttendanceEntry(record, true));
          return;
        }
        const hasMasuk = record.jam_masuk || record.foto_masuk;
        const hasPulang = record.jam_pulang || record.foto_pulang;
        if (hasMasuk || (!hasMasuk && !hasPulang)) {
          details.append(makeAttendanceEntry(record, false, hasMasuk ? 'masuk' : null));
        }
        if (hasPulang) details.append(makeAttendanceEntry(record, false, 'pulang'));
      });
      row.append(details);
      container.append(row);
    });
    applyGuruSessionFilter();
  }

  function applyGuruSessionFilter() {
    const selectedSession = guruSessionFilter.value;
    document.querySelectorAll('[data-attendance-session]').forEach((entry) => {
      entry.hidden = selectedSession !== 'semua'
        && entry.dataset.attendanceSession !== 'lainnya'
        && entry.dataset.attendanceSession !== selectedSession;
    });
  }

  async function loadGuruPeriod() {
    guruFetchError.hidden = true;
    guruPeriodFilter.disabled = true;
    try {
      const response = await fetch(`${guruPeriodFilter.dataset.url}?${new URLSearchParams({ periode: guruPeriodFilter.value })}`, {
        headers: { Accept: 'application/json' },
      });
      if (!response.ok) throw new Error('Data presensi guru tidak dapat dimuat.');
      const data = await response.json();
      renderGuruList(guruHadirList, data.hadir, false);
      renderGuruList(guruTidakHadirList, data.tidak_hadir, true);
      guruHadirCountTab.textContent = data.counts.hadir;
      guruTidakHadirCountTab.textContent = data.counts.tidak_hadir;
      guruUnrecordedCount.textContent = data.counts.belum_presensi;
      
      guruUnrecordedList.replaceChildren();
      if (!data.belum_presensi.length) {
        guruUnrecordedList.append(makeTextElement('p', 'dash-empty', 'Semua guru sudah memiliki catatan presensi.'));
      } else {
        data.belum_presensi.forEach((teacher) => {
          const item = document.createElement('div');
          item.style.display = 'flex';
          item.style.justifyContent = 'space-between';
          item.style.padding = '4px 0';
          item.style.fontSize = '11px';
          item.append(makeTextElement('span', '', teacher.nama), makeTextElement('span', '', 'Belum presensi'));
          guruUnrecordedList.append(item);
        });
      }
    } catch (error) {
      guruFetchError.textContent = error.message || 'Terjadi kesalahan saat memuat presensi guru.';
      guruFetchError.hidden = false;
    } finally {
      guruPeriodFilter.disabled = false;
    }
  }

  async function loadGuruMapelReport() {
    const tbody = document.getElementById('guruMapelRows');
    tbody.innerHTML = '<tr><td colspan="7" class="admin-empty-row" style="text-align: center; padding: 24px; color: #64748b;">Memuat rekap mapel guru...</td></tr>';
    try {
      const response = await fetch(`${guruPeriodFilter.dataset.url}?${new URLSearchParams({ periode: guruPeriodFilter.value, type: 'mapel' })}`, {
        headers: { Accept: 'application/json' },
      });
      if (!response.ok) throw new Error('Gagal memuat rekap mapel guru.');
      const data = await response.json();
      const logs = data.mapel_logs || [];
      
      tbody.replaceChildren();
      if (!logs.length) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 7;
        td.className = 'admin-empty-row';
        td.style.textAlign = 'center';
        td.style.padding = '24px';
        td.style.color = '#64748b';
        td.textContent = 'Belum ada data presensi mengajar mata pelajaran.';
        tr.append(td);
        tbody.append(tr);
        return;
      }

      logs.forEach(log => {
        const tr = document.createElement('tr');
        
        const tdTgl = document.createElement('td');
        tdTgl.textContent = log.tanggal;
        tr.append(tdTgl);

        const tdNama = document.createElement('td');
        tdNama.innerHTML = `<strong>${log.nama_guru}</strong>`;
        tr.append(tdNama);

        const tdMapel = document.createElement('td');
        tdMapel.textContent = log.nama_mapel;
        tr.append(tdMapel);

        const tdKelas = document.createElement('td');
        tdKelas.innerHTML = `<span class="rekap-class-badge">${log.nama_kelas}</span> <small style="display:block; color:#64748b; margin-top:2px;">R: ${log.ruangan || '-'}</small>`;
        tr.append(tdKelas);

        const tdJam = document.createElement('td');
        tdJam.innerHTML = `<small style="display:block;">Jadwal: ${log.jam_mulai?.slice(0,5)} - ${log.jam_selesai?.slice(0,5)}</small><strong style="font-size:11px; color:#475569;">Mulai: ${log.jam_mulai_mengajar ? log.jam_mulai_mengajar.slice(0,5) : '-'}</strong><br><strong style="font-size:11px; color:#2563eb;">Selesai: ${log.jam_selesai_mengajar ? log.jam_selesai_mengajar.slice(0,5) : '-'}</strong>`;
        tr.append(tdJam);

        const tdFotoMulai = document.createElement('td');
        if (log.foto_mulai) {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'photo-thumbnail-btn';
          btn.textContent = 'Lihat Foto';
          btn.addEventListener('click', () => {
            attendancePhotoFull.src = `/storage/${log.foto_mulai}`;
            attendancePhotoModal.showModal();
          });
          tdFotoMulai.append(btn);
        } else {
          tdFotoMulai.textContent = '-';
        }
        tr.append(tdFotoMulai);

        const tdFotoSelesai = document.createElement('td');
        if (log.foto_selesai) {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'photo-thumbnail-btn';
          btn.textContent = 'Lihat Foto';
          btn.addEventListener('click', () => {
            attendancePhotoFull.src = `/storage/${log.foto_selesai}`;
            attendancePhotoModal.showModal();
          });
          tdFotoSelesai.append(btn);
        } else {
          tdFotoSelesai.textContent = '-';
        }
        tr.append(tdFotoSelesai);

        tbody.append(tr);
      });
    } catch (err) {
      tbody.innerHTML = `<tr><td colspan="7" class="admin-empty-row" style="text-align: center; color: #dc2626; padding: 24px;">${err.message}</td></tr>`;
    }
  }

  guruTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      guruTabs.forEach((item) => {
        item.setAttribute('aria-selected', String(item === tab));
        item.classList.toggle('active', item === tab);
      });
      guruPanels.forEach((panel) => {
        panel.hidden = panel.dataset.guruPanel !== tab.dataset.guruTab;
      });
    });
  });
  guruPeriodFilter.addEventListener('change', () => {
    updateActionUrls(activeFilters());
    loadGuruPeriod();
    if (!document.getElementById('subGuruMapel').hidden) {
      loadGuruMapelReport();
    }
  });
  guruSessionFilter.addEventListener('change', applyGuruSessionFilter);
  attendancePhotoModal.querySelector('.dash-photo-modal-close').addEventListener('click', () => attendancePhotoModal.close());
  attendancePhotoModal.addEventListener('click', (e) => {
    if (e.target === attendancePhotoModal) attendancePhotoModal.close();
  });
</script>
<script src="{{ asset('admin-pdf-export.js') }}?v=1" defer></script>
@endsection
