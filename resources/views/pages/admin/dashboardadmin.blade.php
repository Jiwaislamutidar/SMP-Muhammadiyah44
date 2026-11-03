@extends('layouts.admin')

@section('title', 'Dashboard')

@section('styles')
<link rel="stylesheet" href="{{ asset('admin css/dashboardadmin.css') }}?v=4">
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
    <button class="dash-small-outline" type="button" data-open-modal="alfaModal">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 10a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/><path d="m13 13 6 6"/><path d="M8 3h8"/><path d="M12 3v3"/></svg>
      Lihat Daftar
    </button>
  </article>
  <article class="dash-kpi dash-kpi--amber">
    <div class="dash-kpi-label">Murid Izin/Sakit</div>
    <div class="dash-kpi-num">{{ $izinSakitCount }}</div>
    <button class="dash-small-outline" type="button" data-open-modal="izinSakitModal">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 10a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/><path d="m13 13 6 6"/><path d="M8 3h8"/><path d="M12 3v3"/></svg>
      Lihat Daftar
    </button>
  </article>
</section>

<div class="dash-two-col">
  <section class="dash-card dash-absent-card" aria-labelledby="guruAttendanceTitle">
    <div class="dash-card-inner">
      <div class="dash-card-header">
        <h2 class="dash-card-title" id="guruAttendanceTitle">Presensi Guru Hari Ini</h2>
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
        <div class="dash-absence-list">
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
        <div class="dash-absence-list">
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

        <div class="dash-unrecorded">
          <h3>Belum presensi <span class="dash-count">{{ $guruBelumPresensi->count() }}</span></h3>
          @forelse($guruBelumPresensi as $guru)
            <div class="dash-unrecorded-item">
              <span>{{ $guru->nama_lengkap }}</span>
              <span>Belum presensi</span>
            </div>
          @empty
            <p class="dash-empty">Semua guru sudah memiliki catatan presensi.</p>
          @endforelse
        </div>
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

<dialog class="dash-modal" id="alfaModal" aria-labelledby="alfaModalTitle">
  <div class="dash-modal-header">
    <h2 id="alfaModalTitle">Daftar Murid Alfa</h2>
    <button class="dash-modal-close" type="button" data-close-modal aria-label="Tutup">×</button>
  </div>
  <div class="dash-modal-body">
    <table class="dash-detail-table">
      <thead><tr><th>Nama Murid</th><th>Kelas</th><th>Mata Pelajaran</th><th>Jam</th></tr></thead>
      <tbody>
        @forelse($alfaStudents as $attendance)
          <tr>
            <td>{{ $attendance->siswa?->nama_lengkap ?? '-' }}</td>
            <td>{{ $attendance->siswa?->kelas?->nama_kelas ?? '-' }}</td>
            <td>{{ $attendance->sesiPelajaran?->jadwal?->mapel?->nama_mapel ?? '-' }}</td>
            <td>@if($attendance->sesiPelajaran?->jadwal){{ substr($attendance->sesiPelajaran->jadwal->jam_mulai, 0, 5) }}–{{ substr($attendance->sesiPelajaran->jadwal->jam_selesai, 0, 5) }}@else-@endif</td>
          </tr>
        @empty
          <tr><td colspan="4" class="dash-empty-cell">Tidak ada murid Alfa hari ini.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</dialog>

<dialog class="dash-modal" id="izinSakitModal" aria-labelledby="izinSakitModalTitle">
  <div class="dash-modal-header">
    <h2 id="izinSakitModalTitle">Daftar Murid Izin/Sakit</h2>
    <button class="dash-modal-close" type="button" data-close-modal aria-label="Tutup">×</button>
  </div>
  <div class="dash-modal-body">
    <table class="dash-detail-table">
      <thead><tr><th>Nama Murid</th><th>Kelas</th><th>Mata Pelajaran</th><th>Jam</th><th>Keterangan</th></tr></thead>
      <tbody>
        @forelse($izinSakitStudents as $attendance)
          <tr>
            <td>{{ $attendance->siswa?->nama_lengkap ?? '-' }}</td>
            <td>{{ $attendance->siswa?->kelas?->nama_kelas ?? '-' }}</td>
            <td>{{ $attendance->sesiPelajaran?->jadwal?->mapel?->nama_mapel ?? '-' }}</td>
            <td>@if($attendance->sesiPelajaran?->jadwal){{ substr($attendance->sesiPelajaran->jadwal->jam_mulai, 0, 5) }}–{{ substr($attendance->sesiPelajaran->jadwal->jam_selesai, 0, 5) }}@else-@endif</td>
            <td>{{ $attendance->keterangan ?: '-' }}</td>
          </tr>
        @empty
          <tr><td colspan="5" class="dash-empty-cell">Tidak ada murid Izin/Sakit hari ini.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</dialog>

<script>
  document.querySelectorAll('[data-guru-tab]').forEach((tab) => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('[data-guru-tab]').forEach((item) => {
        item.setAttribute('aria-selected', String(item === tab));
      });
      document.querySelectorAll('[data-guru-panel]').forEach((panel) => {
        panel.hidden = panel.dataset.guruPanel !== tab.dataset.guruTab;
      });
    });
  });

  document.querySelectorAll('[data-open-modal]').forEach((button) => {
    button.addEventListener('click', () => document.getElementById(button.dataset.openModal).showModal());
  });
  document.querySelectorAll('[data-close-modal]').forEach((button) => {
    button.addEventListener('click', () => button.closest('dialog').close());
  });
  document.querySelectorAll('.dash-modal').forEach((modal) => {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) modal.close();
    });
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
