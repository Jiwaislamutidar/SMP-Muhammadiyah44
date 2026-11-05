@extends('layouts.admin')

@section('title', 'Dashboard')

@section('styles')
<link rel="stylesheet" href="{{ asset('admin css/dashboardadmin.css') }}?v=6">
@endsection

@section('content')
<div class="dash-header">
  <div class="dash-title-group">
    <h1 class="dash-title">Dashboard</h1>
    <p class="dash-subtitle">Kehadiran &amp; aktivitas hari ini</p>
    <p class="dash-school">SMP Muhammadiyah 44 Tangerang Selatan</p>
  </div>
</div>

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
        <label class="dash-filter-label" for="guruPeriodFilter">
          <span class="sr-only">Periode presensi guru</span>
          <select class="dash-class-filter" id="guruPeriodFilter" data-url="{{ route('admin.dashboard.guru-period') }}">
            <option value="hari">Hari Ini</option>
            <option value="minggu">7 Hari Terakhir</option>
            <option value="bulan">1 Bulan Terakhir</option>
          </select>
        </label>
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
            @php($presensiGuru = $guru->presensiHarian->first())
            <div class="dash-absence-item">
              <div class="dash-absence-left">
                <span class="dash-avatar">{{ mb_strtoupper(mb_substr($guru->nama_lengkap, 0, 1)) }}</span>
                <div class="dash-person-copy">
                  <strong class="dash-name">{{ $guru->nama_lengkap }}</strong>
                  <small class="dash-role">{{ $guru->mapels->pluck('nama_mapel')->join(', ') ?: 'Guru' }}</small>
                </div>
              </div>
              <div class="dash-absence-right">
                <span class="dash-status-badge dash-status-badge--hadir">Hadir</span>
                @if($presensiGuru?->jam_masuk)<small class="dash-role">{{ substr($presensiGuru->jam_masuk, 0, 5) }}</small>@endif
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
            @php($presensiGuru = $guru->presensiHarian->first())
            <div class="dash-absence-item">
              <div class="dash-absence-left">
                <span class="dash-avatar">{{ mb_strtoupper(mb_substr($guru->nama_lengkap, 0, 1)) }}</span>
                <div class="dash-person-copy">
                  <strong class="dash-name">{{ $guru->nama_lengkap }}</strong>
                  <small class="dash-role">{{ $guru->mapels->pluck('nama_mapel')->join(', ') ?: 'Guru' }}</small>
                </div>
              </div>
              <div class="dash-absence-right">
                <span class="dash-status-badge dash-status-badge--{{ strtolower($presensiGuru->status) }}">{{ $presensiGuru->status }}</span>
                @if(in_array($presensiGuru->status, ['Izin', 'Sakit'], true) && $presensiGuru->bukti_izin)
                  <a class="dash-small-outline" href="{{ asset('storage/'.$presensiGuru->bukti_izin) }}" target="_blank" rel="noopener">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                    Lihat Bukti
                  </a>
                @endif
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

<script>
  const guruTabs = document.querySelectorAll('[data-guru-tab]');
  const guruPanels = document.querySelectorAll('[data-guru-panel]');
  const guruPeriodFilter = document.getElementById('guruPeriodFilter');
  const guruFetchError = document.getElementById('guruFetchError');
  const guruHadirList = document.getElementById('guruHadirList');
  const guruTidakHadirList = document.getElementById('guruTidakHadirList');
  const guruUnrecordedSection = document.getElementById('guruUnrecordedSection');
  const guruUnrecordedTitle = document.getElementById('guruUnrecordedTitle');
  const guruAttendanceTitle = document.getElementById('guruAttendanceTitle');
  const guruUnrecordedCount = document.getElementById('guruUnrecordedCount');
  const guruHadirCount = document.querySelector('#guruHadirTab .dash-count');
  const guruTidakHadirCount = document.querySelector('#guruTidakHadirTab .dash-count');

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
      if (absent) {
        teacher.tanggal.forEach((record) => {
          const label = `${record.status} · ${new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(`${record.tanggal}T00:00:00`))}`;
          details.append(makeTextElement('span', `dash-status-badge dash-status-badge--${record.status.toLocaleLowerCase('id')}`, label));
        });
      } else {
        teacher.tanggal.forEach((record) => {
          const detail = record.jam_masuk
            ? `${new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(`${record.tanggal}T00:00:00`))} · ${record.jam_masuk.slice(0, 5)}`
            : new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(`${record.tanggal}T00:00:00`));
          details.append(makeTextElement('small', 'dash-role', detail));
        });
      }
      row.append(details);
      container.append(row);
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
