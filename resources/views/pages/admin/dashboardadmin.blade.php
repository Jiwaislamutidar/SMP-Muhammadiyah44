@extends('layouts.admin')

@section('title', 'Dashboard')

@section('styles')
<link rel="stylesheet" href="{{ asset('admin css/dashboardadmin.css') }}?v=7">
@endsection

@section('content')
<div class="dash-header">
  <div class="dash-title-group">
    <h1 class="dash-title">Dashboard</h1>
    <p class="dash-subtitle">Kehadiran &amp; aktivitas hari ini</p>
    <p class="dash-school">SMP Muhammadiyah 44 Tangerang Selatan</p>
  </div>
</div>
@if(session('success'))
  <p class="dash-feedback" role="status">{{ session('success') }}</p>
@endif

<section class="dash-kpi-strip" aria-label="Ringkasan presensi murid hari ini">
  <article class="dash-kpi dash-kpi--green">
    <div class="dash-kpi-label">Murid Hadir</div>
    <div class="dash-kpi-num">{{ $hadirCount }}</div>
    <div class="dash-kpi-sub">dari {{ $totalSiswaAktif }} siswa aktif</div>
  </article>
  <article class="dash-kpi dash-kpi--red">
    <div class="dash-kpi-label">Murid Alfa</div>
    <div class="dash-kpi-num">{{ $alfaCount }}</div>
  </article>
  <article class="dash-kpi dash-kpi--amber">
    <div class="dash-kpi-label">Murid Izin/Sakit</div>
    <div class="dash-kpi-num">{{ $izinSakitCount }}</div>
  </article>
</section>

<div class="dash-two-col">
  <section class="dash-card dash-absent-card" aria-labelledby="guruAttendanceTitle">
    <div class="dash-card-inner">
      <div class="dash-card-header">
        <h2 class="dash-card-title" id="guruAttendanceTitle">Presensi Guru Hari Ini</h2>
        <div class="dash-attendance-filters">
          <label class="dash-filter-label" for="guruPeriodFilter">
            <span class="sr-only">Periode presensi guru</span>
            <select class="dash-class-filter" id="guruPeriodFilter" data-url="{{ route('admin.dashboard.guru-period') }}">
              <option value="hari">Hari Ini</option>
              <option value="minggu">7 Hari Terakhir</option>
              <option value="bulan">1 Bulan Terakhir</option>
            </select>
          </label>
          <label class="dash-filter-label" for="guruSessionFilter">
            <span class="sr-only">Sesi presensi guru</span>
            <select class="dash-class-filter" id="guruSessionFilter">
              <option value="semua">Semua Sesi</option>
              <option value="masuk">Absen Masuk</option>
              <option value="pulang">Absen Pulang</option>
            </select>
          </label>
        </div>
      </div>

      <div class="dash-tabs" role="tablist" aria-label="Filter presensi guru">
        <button class="dash-tab" id="guruHadirTab" type="button" role="tab" aria-selected="true" aria-controls="guruHadirPanel" data-guru-tab="hadir">
          Hadir <span class="dash-count">{{ $guruHadir->count() }}</span>
        </button>
        <button class="dash-tab" id="guruTidakHadirTab" type="button" role="tab" aria-selected="false" aria-controls="guruTidakHadirPanel" data-guru-tab="tidak-hadir">
          Tidak Hadir <span class="dash-count">{{ $guruTidakHadir->count() }}</span>
        </button>
      </div>

      <div class="dash-tab-panel" id="guruHadirPanel" role="tabpanel" aria-labelledby="guruHadirTab" data-guru-panel="hadir">
        <div class="dash-absence-list" id="guruHadirList">
          @forelse($guruHadir as $guru)
            <div class="dash-absence-item">
              <div class="dash-absence-left">
                <span class="dash-avatar">{{ mb_strtoupper(mb_substr($guru->nama_lengkap, 0, 1)) }}</span>
                <div class="dash-person-copy">
                  <strong class="dash-name">{{ $guru->nama_lengkap }}</strong>
                  <small class="dash-role">{{ $guru->mapels->pluck('nama_mapel')->join(', ') ?: 'Guru' }}</small>
                </div>
              </div>
              <div class="dash-absence-right">
                @foreach($guru->dashboardAttendanceSessionLogs as $sessionLog)
                  <div class="dash-attendance-entry" data-attendance-session="{{ $sessionLog->session_type }}">
                    <div class="dash-entry-status">
                      <span class="dash-status-badge dash-status-badge--hadir">{{ $sessionLog->session_type === 'masuk' ? 'Masuk' : 'Pulang' }}</span>
                      <small class="dash-role">{{ $sessionLog->tanggal->locale('id')->translatedFormat('j M') }}{{ $sessionLog->jam ? ' · '.substr($sessionLog->jam, 0, 5) : '' }}</small>
                    </div>
                    <div class="dash-entry-actions">
                      @if($sessionLog->foto)
                        <button class="dash-photo-preview" type="button" data-photo-url="{{ asset('storage/'.$sessionLog->foto) }}" aria-label="Lihat foto absen {{ $sessionLog->session_type }} {{ $guru->nama_lengkap }}">Lihat Foto</button>
                      @endif
                      <form method="POST" action="{{ route('admin.dashboard.guru-session-log.destroy', $sessionLog) }}" onsubmit="return confirm('Hapus log sesi presensi ini dari dashboard admin?')">
                        @csrf
                        @method('DELETE')
                        <button class="dash-delete-button admin-delete-button" type="submit" aria-label="Hapus log absen {{ $sessionLog->session_type }} {{ $guru->nama_lengkap }}">Hapus</button>
                      </form>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          @empty
            <p class="dash-empty">Belum ada guru yang tercatat hadir hari ini.</p>
          @endforelse
        </div>
      </div>

      <div class="dash-tab-panel" id="guruTidakHadirPanel" role="tabpanel" aria-labelledby="guruTidakHadirTab" data-guru-panel="tidak-hadir" hidden>
        <div class="dash-absence-list" id="guruTidakHadirList">
          @forelse($guruTidakHadir as $guru)
            <div class="dash-absence-item">
              <div class="dash-absence-left">
                <span class="dash-avatar">{{ mb_strtoupper(mb_substr($guru->nama_lengkap, 0, 1)) }}</span>
                <div class="dash-person-copy">
                  <strong class="dash-name">{{ $guru->nama_lengkap }}</strong>
                  <small class="dash-role">{{ $guru->mapels->pluck('nama_mapel')->join(', ') ?: 'Guru' }}</small>
                </div>
              </div>
              <div class="dash-absence-right">
                @foreach($guru->dashboardPresensiLogs as $record)
                  @php($recordStatus = $record->status)
                  <div class="dash-attendance-entry" data-attendance-session="lainnya">
                    <div class="dash-entry-status">
                      <span class="dash-status-badge dash-status-badge--{{ strtolower($recordStatus) }}">{{ $recordStatus }}</span>
                      <small class="dash-role">{{ $record->tanggal->locale('id')->translatedFormat('j M') }}</small>
                    </div>
                    <div class="dash-entry-actions">
                      @if($record->foto_masuk)
                        <button class="dash-photo-preview" type="button" data-photo-url="{{ asset('storage/'.$record->foto_masuk) }}" aria-label="Lihat foto bukti {{ $guru->nama_lengkap }}">Lihat Foto</button>
                      @elseif($record->bukti_izin)
                        <a class="dash-small-outline" href="{{ asset('storage/'.$record->bukti_izin) }}" target="_blank" rel="noopener">Lihat Bukti</a>
                      @endif
                      <form method="POST" action="{{ route('admin.dashboard.guru-log.destroy', $record) }}" onsubmit="return confirm('Hapus log presensi ini dari dashboard admin?')">
                        @csrf
                        @method('DELETE')
                        <button class="dash-delete-button admin-delete-button" type="submit" aria-label="Hapus log {{ $guru->nama_lengkap }}">Hapus</button>
                      </form>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          @empty
            <p class="dash-empty">Tidak ada guru dengan status tidak hadir hari ini.</p>
          @endforelse
        </div>

        <div class="dash-unrecorded" id="guruUnrecordedSection">
          <h3><span id="guruUnrecordedTitle">Belum presensi</span> <span class="dash-count" id="guruUnrecordedCount">{{ $guruBelumPresensi->count() }}</span></h3>
          @forelse($guruBelumPresensi as $guru)
            <div class="dash-unrecorded-item" data-guru-id="{{ $guru->id }}">
              <span>{{ $guru->nama_lengkap }}</span>
              <span>Belum presensi</span>
            </div>
          @empty
            <p class="dash-empty">Semua guru sudah memiliki catatan presensi.</p>
          @endforelse
        </div>
        <p class="dash-fetch-error" id="guruFetchError" role="alert" hidden></p>
      </div>
      <form class="dash-log-toolbar" id="deleteGuruLogsForm" method="POST" action="{{ route('admin.dashboard.guru-log.destroy-period') }}">
        @csrf
        @method('DELETE')
        <label for="deleteGuruLogsPeriod">Hapus log dashboard:
          <select class="dash-class-filter" id="deleteGuruLogsPeriod" name="periode">
            <option value="hari">Hari ini</option>
            <option value="minggu">7 hari terakhir</option>
            <option value="bulan">1 bulan terakhir</option>
            <option value="semua">Semua riwayat</option>
          </select>
        </label>
        <button class="dash-delete-button admin-delete-button" type="submit">Hapus Periode</button>
      </form>
    </div>
  </section>

  <section class="dash-card dash-schedule-card" aria-labelledby="scheduleTitle">
    <div class="dash-card-inner">
      <div class="dash-card-header dash-schedule-header">
        <h2 class="dash-card-title" id="scheduleTitle">Jadwal Hari Ini</h2>
        <label class="dash-filter-label" for="scheduleClassFilter">
          <span class="sr-only">Filter kelas</span>
          <select id="scheduleClassFilter" class="dash-class-filter">
            <option value="">Semua Kelas</option>
            @foreach($kelasList as $kelas)
              <option value="{{ $kelas->nama_kelas }}">{{ $kelas->nama_kelas }}</option>
            @endforeach
          </select>
        </label>
      </div>

      <div class="dash-table-wrap">
        <table class="dash-table">
          <thead>
            <tr>
              <th>Jam</th>
              <th>Mata Pelajaran</th>
              <th>Guru</th>
              <th>Kelas</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="scheduleRows">
            @forelse($jadwalBloks as $blok)
              @php($jadwal = $blok['jadwal'])
              <tr class="dash-schedule-row" data-kelas="{{ $jadwal->kelas->nama_kelas }}">
                <td class="dash-td-time">{{ $blok['jam'] }}</td>
                <td><strong>{{ $jadwal->mapel->nama_mapel }}</strong></td>
                <td class="dash-td-muted">{{ $jadwal->guru->nama_lengkap }}</td>
                <td><span class="dash-kelas-badge">{{ $jadwal->kelas->nama_kelas }}</span></td>
                <td><span class="dash-status dash-status--{{ $blok['status'] === 'Berlangsung' ? 'live' : ($blok['status'] === 'Selesai' ? 'done' : 'next') }}">{{ $blok['status'] }}</span></td>
              </tr>
            @empty
              <tr><td class="dash-empty-cell" colspan="5">Tidak ada jadwal pelajaran hari ini.</td></tr>
            @endforelse
            @if($jadwalBloks->isNotEmpty())
              <tr id="scheduleFilterEmpty" hidden><td class="dash-empty-cell" colspan="5">Tidak ada jadwal untuk kelas ini.</td></tr>
            @endif
          </tbody>
        </table>
      </div>
    </div>
    <div class="dash-card-footer">
      <span>{{ $jadwalBloks->count() }} blok jadwal hari ini</span>
      <a class="dash-link-text" href="{{ route('admin.jadwalpelajaran') }}">Kelola Jadwal</a>
    </div>
  </section>
</div>

<dialog class="dash-photo-modal" id="attendancePhotoModal" aria-label="Pratinjau foto bukti presensi">
  <button class="dash-photo-modal-close" type="button" aria-label="Tutup pratinjau">Tutup</button>
  <img id="attendancePhotoFull" alt="Foto bukti presensi guru">
</dialog>

<script>
  const guruTabs = document.querySelectorAll('[data-guru-tab]');
  const guruPanels = document.querySelectorAll('[data-guru-panel]');
  const guruPeriodFilter = document.getElementById('guruPeriodFilter');
  const guruSessionFilter = document.getElementById('guruSessionFilter');
  const guruFetchError = document.getElementById('guruFetchError');
  const guruHadirList = document.getElementById('guruHadirList');
  const guruTidakHadirList = document.getElementById('guruTidakHadirList');
  const guruUnrecordedSection = document.getElementById('guruUnrecordedSection');
  const guruUnrecordedTitle = document.getElementById('guruUnrecordedTitle');
  const guruAttendanceTitle = document.getElementById('guruAttendanceTitle');
  const guruUnrecordedCount = document.getElementById('guruUnrecordedCount');
  const guruHadirCount = document.querySelector('#guruHadirTab .dash-count');
  const guruTidakHadirCount = document.querySelector('#guruTidakHadirTab .dash-count');
  const attendancePhotoModal = document.getElementById('attendancePhotoModal');
  const attendancePhotoFull = document.getElementById('attendancePhotoFull');
  const deleteGuruLogsForm = document.getElementById('deleteGuruLogsForm');
  const csrfToken = @json(csrf_token());

  function animateGuruRows(panel) {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const rows = panel.querySelectorAll('.dash-absence-item');
    rows.forEach((row, index) => {
      row.classList.remove('dash-row-enter');
      row.style.setProperty('--row-index', index);
    });
    void panel.offsetWidth;
    rows.forEach((row) => row.classList.add('dash-row-enter'));
    if (rows.length) {
      rows[rows.length - 1].addEventListener('animationend', () => {
        rows.forEach((row) => row.classList.remove('dash-row-enter'));
      }, { once: true });
    }
  }

  function makeTextElement(tag, className, value) {
    const element = document.createElement(tag);
    if (className) element.className = className;
    element.textContent = value;
    return element;
  }

  function makeDeleteAction(logId, sessionLog = false) {
    const form = document.createElement('form');
    form.method = 'POST';
    const routeTemplate = sessionLog
      ? @json(route('admin.dashboard.guru-session-log.destroy', ['sessionLog' => 'LOG_ID']))
      : @json(route('admin.dashboard.guru-log.destroy', ['log' => 'LOG_ID']));
    form.action = routeTemplate.replace('LOG_ID', logId);
    form.addEventListener('submit', (event) => {
      if (!window.confirm('Hapus log presensi ini dari dashboard admin?')) event.preventDefault();
    });
    const token = document.createElement('input');
    token.type = 'hidden';
    token.name = '_token';
    token.value = csrfToken;
    const method = document.createElement('input');
    method.type = 'hidden';
    method.name = '_method';
    method.value = 'DELETE';
    const button = makeTextElement('button', 'dash-delete-button', 'Hapus');
    button.type = 'submit';
    button.setAttribute('aria-label', 'Hapus log presensi');
    form.append(token, method, button);
    return form;
  }

  function makeAttendanceEntry(record, absent, session = null) {
    const entry = makeTextElement('div', 'dash-attendance-entry', '');
    entry.dataset.attendanceSession = absent ? 'lainnya' : (session || 'lainnya');
    const statusText = absent ? (record.status || 'Hadir') : (session === 'masuk' ? 'Masuk' : (session === 'pulang' ? 'Pulang' : 'Hadir'));
    const status = makeTextElement('span', `dash-status-badge dash-status-badge--${(record.status || 'Hadir').toLocaleLowerCase('id')}`, statusText);
    const detail = makeTextElement('small', 'dash-role', new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short' }).format(new Date(`${record.tanggal}T00:00:00`)));
    const time = session === 'pulang' ? record.jam_pulang : record.jam_masuk;
    if (!absent && time) detail.textContent += ` · ${time.slice(0, 5)}`;
    const statusContent = makeTextElement('div', 'dash-entry-status', '');
    statusContent.append(status, detail);
    entry.append(statusContent);

    const actions = makeTextElement('div', 'dash-entry-actions', '');
    const photoUrl = absent
      ? record.foto_bukti
      : (session === 'pulang' ? record.foto_pulang : record.foto_masuk);
    if (photoUrl) {
      const preview = document.createElement('button');
      preview.type = 'button';
      preview.className = 'dash-photo-preview';
      preview.dataset.photoUrl = photoUrl;
      preview.setAttribute('aria-label', `Lihat foto bukti ${statusText.toLocaleLowerCase('id')}`);
      preview.textContent = 'Lihat Foto';
      actions.append(preview);
    }
    if (absent && record.bukti_izin && !record.foto_bukti) {
      const evidence = document.createElement('a');
      evidence.className = 'dash-small-outline';
      evidence.href = record.bukti_izin;
      evidence.target = '_blank';
      evidence.rel = 'noopener';
      evidence.textContent = 'Lihat Bukti';
      actions.append(evidence);
    }
    actions.append(makeDeleteAction(record.id, !absent));
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
      const row = makeTextElement('div', 'dash-absence-item', '');
      row.dataset.guruId = teacher.id;
      const person = makeTextElement('div', 'dash-absence-left', '');
      person.append(makeTextElement('span', 'dash-avatar', teacher.nama.slice(0, 1).toLocaleUpperCase('id')));
      const copy = makeTextElement('div', 'dash-person-copy', '');
      copy.append(makeTextElement('strong', 'dash-name', teacher.nama));
      copy.append(makeTextElement('small', 'dash-role', teacher.mapel));
      person.append(copy);
      row.append(person);

      const details = makeTextElement('div', 'dash-absence-right', '');
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
    document.querySelectorAll('.dash-absence-list .dash-absence-item').forEach((row) => {
      const entries = row.querySelectorAll('[data-attendance-session]');
      const noSelectedSessions = selectedSession !== 'semua'
        && row.closest('[data-guru-panel="hadir"]')
        && entries.length === 0;
      row.hidden = noSelectedSessions || (entries.length > 0 && [...entries].every((entry) => entry.hidden));
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
      guruHadirCount.textContent = data.counts.hadir;
      guruTidakHadirCount.textContent = data.counts.tidak_hadir;
      guruUnrecordedCount.textContent = data.counts.belum_presensi;
      guruUnrecordedTitle.textContent = guruPeriodFilter.value === 'hari'
        ? 'Belum presensi hari ini'
        : 'Belum presensi dalam periode';
      guruAttendanceTitle.textContent = guruPeriodFilter.value === 'hari'
        ? 'Presensi Guru Hari Ini'
        : `Presensi Guru · ${guruPeriodFilter.options[guruPeriodFilter.selectedIndex].text}`;
      guruUnrecordedSection.hidden = data.belum_presensi.length === 0;
      guruUnrecordedSection.querySelectorAll('.dash-unrecorded-item').forEach((row) => row.remove());
      guruUnrecordedSection.querySelectorAll('.dash-empty').forEach((empty) => empty.remove());
      data.belum_presensi.forEach((teacher) => {
        const row = makeTextElement('div', 'dash-unrecorded-item', '');
        row.dataset.guruId = teacher.id;
        row.append(makeTextElement('span', '', teacher.nama));
        row.append(makeTextElement('span', '', 'Belum presensi'));
        guruUnrecordedSection.append(row);
      });
      const selectedPanel = document.querySelector('[data-guru-panel]:not([hidden])');
      if (selectedPanel) animateGuruRows(selectedPanel);
    } catch (error) {
      guruFetchError.textContent = error.message || 'Terjadi kesalahan saat memuat presensi guru.';
      guruFetchError.hidden = false;
    } finally {
      guruPeriodFilter.disabled = false;
    }
  }

  guruTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('[data-guru-tab]').forEach((item) => {
        item.setAttribute('aria-selected', String(item === tab));
      });
      guruPanels.forEach((panel) => {
        const selected = panel.dataset.guruPanel === tab.dataset.guruTab;
        panel.hidden = !selected;
        if (selected) animateGuruRows(panel);
      });
    });
  });
  guruPeriodFilter.addEventListener('change', loadGuruPeriod);
  guruSessionFilter.addEventListener('change', applyGuruSessionFilter);

  document.addEventListener('click', (event) => {
    const preview = event.target.closest('[data-photo-url]');
    if (!preview) return;
    attendancePhotoFull.src = preview.dataset.photoUrl;
    attendancePhotoModal.showModal();
  });
  attendancePhotoModal.querySelector('.dash-photo-modal-close').addEventListener('click', () => attendancePhotoModal.close());
  attendancePhotoModal.addEventListener('click', (event) => {
    if (event.target === attendancePhotoModal) attendancePhotoModal.close();
  });
  attendancePhotoModal.addEventListener('close', () => {
    attendancePhotoFull.removeAttribute('src');
  });
  deleteGuruLogsForm.addEventListener('submit', (event) => {
    const period = deleteGuruLogsForm.elements.periode.selectedOptions[0].textContent;
    if (!window.confirm(`Hapus log presensi dashboard untuk ${period.toLocaleLowerCase('id')}?`)) event.preventDefault();
  });

  const classFilter = document.getElementById('scheduleClassFilter');
  classFilter.addEventListener('change', () => {
    const visibleRows = [...document.querySelectorAll('.dash-schedule-row')].filter((row) => {
      const visible = !classFilter.value || row.dataset.kelas === classFilter.value;
      row.hidden = !visible;
      return visible;
    });
    const emptyRow = document.getElementById('scheduleFilterEmpty');
    if (emptyRow) emptyRow.hidden = visibleRows.length > 0;
  });
</script>
@endsection
