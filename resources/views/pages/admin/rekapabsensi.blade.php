@extends('layouts.admin')
@section('title', 'Rekap Absensi')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/rekapabsensi.css') }}?v=13">@endsection
@section('content')

<div class="admin-page-header">
  <div>
    <h1>Rekap Absensi</h1>
    <p>Kelola dan pantau data kehadiran murid dan guru</p>
  </div>
  <div class="admin-header-actions rekap-actions" id="studentHeaderActions">
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
        <button type="button" class="date-mode-btn active" id="btnModeHarian" style="flex: 1; padding: 6px 10px; font-size: 11px; font-weight: 600; border: 1px solid #d1d5db; border-radius: 6px; background: #087443; color: #fff; cursor: pointer;">📅 Harian</button>
        <button type="button" class="date-mode-btn" id="btnModeRentang" style="flex: 1; padding: 6px 10px; font-size: 11px; font-weight: 600; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; color: #4b5563; cursor: pointer;">📆 Rentang Waktu</button>
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

  <div class="dash-tabs" role="tablist" aria-label="Filter guru" style="display: flex; gap: 16px; margin-bottom: 16px; border-bottom: 1px solid #d1d5db; padding-bottom: 8px;">
    <button class="dash-tab active" type="button" role="tab" aria-selected="true" data-guru-tab="hadir" style="background: none; border: none; font-weight: 700; cursor: pointer; color: #087443; font-size: 13px;">
      Hadir <span class="dash-count" id="guruHadirCountTab">0</span>
    </button>
    <button class="dash-tab" type="button" role="tab" aria-selected="false" data-guru-tab="tidak-hadir" style="background: none; border: none; font-weight: 700; cursor: pointer; color: #6b756f; font-size: 13px;">
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
    <div class="dash-unrecorded" id="guruUnrecordedSection" style="margin-top: 16px; padding: 12px; background: #f9fafb; border-radius: 8px;">
      <h3 style="font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 8px;">Belum Presensi (<span id="guruUnrecordedCount">0</span>)</h3>
      <div id="guruUnrecordedList"></div>
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
    <div class="rekap-delete-icon" aria-hidden="true">!</div>
    <h2 id="deleteModalTitle">Apakah kamu yakin ingin menghapus data ini?</h2>
    <p>Data presensi yang sesuai dengan filter aktif akan dihapus permanen.</p>
    <div class="rekap-delete-actions">
      <button class="admin-action admin-action-outline" type="button" id="cancelDelete">Batal</button>
      <button class="admin-action rekap-danger-button admin-delete-button admin-delete-button--solid" type="submit">Ya, Hapus Data</button>
    </div>
  </form>
</dialog>

<dialog class="dash-photo-modal" id="attendancePhotoModal" aria-label="Pratinjau foto bukti presensi" style="padding: 20px; border-radius: 12px; background: #111827; color: #fff;">
  <button class="dash-photo-modal-close" type="button" aria-label="Tutup pratinjau" style="background: #374151; color: #fff; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; float: right; margin-bottom: 10px;">Tutup</button>
  <img id="attendancePhotoFull" alt="Foto bukti presensi guru" style="display: block; max-width: 100%; max-height: 75vh; margin: 0 auto; object-fit: contain;">
</dialog>

<script>
  const modeTabs = document.querySelectorAll('.rekap-mode-tab');
  const modePanels = document.querySelectorAll('.rekap-mode-panel');
  const studentHeaderActions = document.getElementById('studentHeaderActions');

  modeTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      modeTabs.forEach((t) => {
        t.classList.remove('active');
        t.setAttribute('aria-selected', 'false');
      });
      tab.classList.add('active');
      tab.setAttribute('aria-selected', 'true');
      const target = tab.dataset.modeTarget;
      modePanels.forEach((panel) => {
        const match = panel.dataset.modePanel === target;
        panel.hidden = !match;
      });
      if (studentHeaderActions) {
        studentHeaderActions.style.display = target === 'murid' ? 'flex' : 'none';
      }
      if (target === 'guru') {
        loadGuruPeriod();
      }
    });
  });

  // Switch Mode Harian vs Rentang Waktu
  const btnModeHarian = document.getElementById('btnModeHarian');
  const btnModeRentang = document.getElementById('btnModeRentang');
  const containerHarian = document.getElementById('containerHarian');
  const containerRentang = document.getElementById('containerRentang');
  let currentMode = 'harian';

  btnModeHarian.addEventListener('click', () => {
    currentMode = 'harian';
    btnModeHarian.style.background = '#087443';
    btnModeHarian.style.color = '#fff';
    btnModeHarian.style.borderColor = '#087443';
    
    btnModeRentang.style.background = '#fff';
    btnModeRentang.style.color = '#4b5563';
    btnModeRentang.style.borderColor = '#d1d5db';

    containerHarian.style.display = 'grid';
    containerRentang.style.display = 'none';
    loadReport();
  });

  btnModeRentang.addEventListener('click', () => {
    currentMode = 'rentang';
    btnModeRentang.style.background = '#087443';
    btnModeRentang.style.color = '#fff';
    btnModeRentang.style.borderColor = '#087443';
    
    btnModeHarian.style.background = '#fff';
    btnModeHarian.style.color = '#4b5563';
    btnModeHarian.style.borderColor = '#d1d5db';

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
    if (!filters.date) {
      exportLink.disabled = true;
      deleteButton.disabled = true;
      return;
    }

    const exportFilters = {
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
      preview.style.padding = '2px 6px';
      preview.style.fontSize = '10px';
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
      row.style.borderBottom = '1px solid #f0f1f3';

      const person = document.createElement('div');
      person.style.display = 'flex';
      person.style.alignItems = 'center';
      person.style.gap = '10px';
      
      const avatar = makeTextElement('span', '', teacher.nama.slice(0, 1).toUpperCase());
      avatar.style.width = '32px';
      avatar.style.height = '32px';
      avatar.style.background = '#e8f6ef';
      avatar.style.color = '#087443';
      avatar.style.borderRadius = '50%';
      avatar.style.display = 'grid';
      avatar.style.placeItems = 'center';
      avatar.style.fontWeight = '700';

      const copy = document.createElement('div');
      const name = makeTextElement('strong', '', teacher.nama);
      const role = makeTextElement('small', '', teacher.mapel);
      role.style.display = 'block';
      role.style.color = '#6b756f';
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

  guruTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      guruTabs.forEach((item) => {
        item.setAttribute('aria-selected', String(item === tab));
        item.style.color = item === tab ? '#087443' : '#6b756f';
      });
      guruPanels.forEach((panel) => {
        panel.hidden = panel.dataset.guruPanel !== tab.dataset.guruTab;
      });
    });
  });
  guruPeriodFilter.addEventListener('change', loadGuruPeriod);
  guruSessionFilter.addEventListener('change', applyGuruSessionFilter);
  attendancePhotoModal.querySelector('.dash-photo-modal-close').addEventListener('click', () => attendancePhotoModal.close());
</script>
<script src="{{ asset('admin-pdf-export.js') }}?v=1" defer></script>
@endsection