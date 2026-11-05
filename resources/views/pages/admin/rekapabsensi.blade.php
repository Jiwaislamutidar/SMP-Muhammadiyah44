@extends('layouts.admin')
@section('title', 'Rekap Absensi')
@section('styles')<link rel="stylesheet" href="{{ asset('admin css/rekapabsensi.css') }}?v=7">@endsection
@section('content')

<div class="admin-page-header">
  <div>
    <h1>Rekap Absensi Murid</h1>
    <p>Data presensi murid berdasarkan tanggal yang dipilih</p>
  </div>
  <div class="admin-header-actions rekap-actions">
    <button class="admin-action admin-action-outline" id="deleteAttendance" type="button">Hapus Data</button>
    <a class="admin-action" id="exportAttendance" href="{{ route('admin.rekapabsensi.export', ['date' => $selectedDate]) }}">↓ Export Rekap</a>
  </div>
</div>

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
  <label class="rekap-filter-label" for="rekapDate">
    Tanggal
    <input class="admin-input" id="rekapDate" type="date" value="{{ $selectedDate }}" required>
  </label>
  <form class="rekap-search-form" method="GET" action="{{ route('admin.rekapabsensi') }}" id="rekapSearchForm">
    <input class="admin-search" id="rekapSearch" name="search" type="search" placeholder="Cari nama atau NISN murid..." aria-label="Cari murid">
  </form>
  <label class="rekap-filter-label" for="rekapClass">
    Kelas
    <select class="admin-select" id="rekapClass">
      <option value="">Semua Kelas</option>
      @foreach($kelasList as $kelas)
        <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
      @endforeach
    </select>
  </label>
  <label class="rekap-filter-label" for="rekapStatus">
    Status
    <select class="admin-select" id="rekapStatus">
      <option value="">Semua Status</option>
      @foreach(['Hadir', 'Izin', 'Sakit', 'Alfa'] as $status)
        <option value="{{ $status }}">{{ $status }}</option>
      @endforeach
    </select>
  </label>
  <span class="admin-total" id="rekapTotal">Total {{ $report['total'] }} Data</span>
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

<dialog class="rekap-delete-modal" id="deleteModal" aria-labelledby="deleteModalTitle">
  <form method="POST" action="{{ route('admin.rekapabsensi.destroy') }}" id="deleteAttendanceForm">
    @csrf
    @method('DELETE')
    <input type="hidden" name="date" value="{{ $selectedDate }}">
    <input type="hidden" name="kelas">
    <input type="hidden" name="status">
    <input type="hidden" name="search">
    <div class="rekap-delete-icon" aria-hidden="true">!</div>
    <h2 id="deleteModalTitle">Apakah kamu yakin ingin menghapus data ini?</h2>
    <p>Data presensi yang sesuai dengan filter aktif akan dihapus permanen.</p>
    <div class="rekap-delete-actions">
      <button class="admin-action admin-action-outline" type="button" id="cancelDelete">Batal</button>
      <button class="admin-action rekap-danger-button" type="submit">Ya, Hapus Data</button>
    </div>
  </form>
</dialog>

<script>
  const reportUrl = @json(route('admin.rekapabsensi.data'));
  const dateFilter = document.getElementById('rekapDate');
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

  function activeFilters() {
    return {
      date: dateFilter.value,
      kelas: classFilter.value,
      status: statusFilter.value,
      search: searchFilter.value.trim(),
    };
  }

  function updateActionUrls(filters) {
    if (!filters.date) {
      exportLink.removeAttribute('href');
      exportLink.setAttribute('aria-disabled', 'true');
      deleteButton.disabled = true;
      return;
    }

    const query = new URLSearchParams(filters);
    exportLink.href = `{{ route('admin.rekapabsensi.export') }}?${query}`;
    exportLink.removeAttribute('aria-disabled');
    deleteButton.disabled = false;
    Object.entries(filters).forEach(([name, value]) => {
      deleteForm.elements[name].value = value;
    });
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

  [dateFilter, classFilter, statusFilter].forEach((filter) => filter.addEventListener('change', loadReport));
  document.getElementById('rekapSearchForm').addEventListener('submit', (event) => {
    event.preventDefault();
    window.clearTimeout(searchTimer);
    loadReport();
  });
  searchFilter.addEventListener('input', () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(loadReport, 250);
  });
  document.getElementById('deleteAttendance').addEventListener('click', () => deleteModal.showModal());
  document.getElementById('cancelDelete').addEventListener('click', () => deleteModal.close());
</script>
@endsection
