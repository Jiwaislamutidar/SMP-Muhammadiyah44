<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
  <title>Dashboard Guru - SMP Muhammadiyah 44</title>
  <link rel="stylesheet" href="{{ asset('guru css/dashboard.css') }}?v=3">
  <link rel="stylesheet" href="{{ asset('guru css/portal-responsive.css') }}?v=1">
  <link rel="stylesheet" href="{{ asset('guru css/guru-ui.css') }}?v=2">
  <style>
    .stat-meta .label { font-size: 14px; }
    .stat-meta .value { font-size: 22px; }
    .status-badge { font-size: 14px; padding: 8px 12px; }
    .session-label, .live-status { font-size: 15px; }
    .session-main h3 { font-size: 24px; }
    .session-main .meta { font-size: 15px; }
    .active-session .primary-btn { min-height: 48px; font-size: 16px; }
    .live-status.inactive { color: #59636e; }
    .live-status.inactive .dot { background: #9ca3af; }
    @media (max-width: 760px) {
      .stat-meta .value { font-size: 20px; }
      .session-main { gap: 12px; }
    }
  </style>
</head>
<body>
  <div class="page-loader" id="pageLoader">
    <div class="loader-logo-wrap">
      <div class="loader-spinner"></div>
      <div class="loader-logo">
        <img src="{{ asset('logo.jpg') }}" alt="Logo SMP Muhammadiyah 44">
      </div>
    </div>
    <div class="loader-text">SMP Muhammadiyah 44</div>
    <div class="loader-subtext">Memuat dashboard guru...</div>
  </div>

  <div class="dashboard-guru" id="dashboardGuru">
    @include('pages.guru.partials.sidebar', ['activePage' => 'dashboard', 'sidebarRole' => 'Guru Mata Pelajaran'])
    @include('pages.guru.partials.topbar', ['profileRole' => 'Guru Mata Pelajaran'])

    <main class="content">
      <div class="page-header">
        <h1>Selamat Datang, {{ $guru->nama_lengkap }}</h1>
        <p>Berikut informasi jadwal dan presensi Anda hari ini.</p>
      </div>

      <section class="stats-row">
        <div class="stat-card">
          <div class="stat-left">
            <div class="stat-meta">
              <div class="label">Masuk Sekolah</div>
              <div class="value">{{ $presensiHariIni?->jam_masuk ? substr($presensiHariIni->jam_masuk, 0, 5).' WIB' : 'Belum tercatat' }}</div>
            </div>
          </div>
          <div class="status-badge {{ $presensiHariIni?->jam_masuk ? '' : 'pending' }}"><span class="dot"></span> {{ $presensiHariIni?->jam_masuk ? 'Sudah Absen' : 'Belum Absen' }}</div>
        </div>

        <div class="stat-card">
          <div class="stat-left">
            <div class="stat-meta">
              <div class="label">Pulang Sekolah</div>
              <div class="value">{{ $presensiHariIni?->jam_pulang ? substr($presensiHariIni->jam_pulang, 0, 5).' WIB' : 'Belum tercatat' }}</div>
            </div>
          </div>
          <div class="status-badge {{ $presensiHariIni?->jam_pulang ? '' : 'pending' }}"><span class="dot"></span> {{ $presensiHariIni?->jam_pulang ? 'Sudah Absen' : 'Belum Absen' }}</div>
        </div>
      </section>

      <section class="main-grid">
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title"><span class="bar"></span> Jadwal Mengajar Hari Ini</div>
            <div class="mini-label">{{ $jadwalBloks->count() }} Sesi KBM</div>
          </div>

          <div class="table-wrap">
          <table class="schedule-table">
            <thead>
              <tr>
                <th style="width: 20%;">Jam</th>
                <th style="width: 30%;">Mapel</th>
                <th style="width: 18%;">Kelas</th>
                <th style="width: 32%; text-align: right;">Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($jadwalBloks as $blok)
                @php($jadwal = $blok['jadwal'])
                <tr class="{{ $blok['status'] === 'Berlangsung' ? 'active-row' : '' }}">
                  <td>{{ $blok['jam'] }}@if(count($blok['segments']) > 1)<small class="schedule-block-duration">{{ $blok['durasi'] }}</small>@endif</td>
                  <td>{{ $jadwal->mapel->nama_mapel }}</td>
                  <td><span class="pill-soft">{{ $jadwal->kelas->nama_kelas }}</span></td>
                  <td style="text-align:right">
                    @if($blok['status'] === 'Berlangsung')
                      <span class="pill-live"><span class="dot"></span>{{ $blok['status'] }}</span>
                    @else
                      <span class="pill-soft">{{ $blok['status'] }}</span>
                    @endif
                  </td>
                </tr>
              @empty<tr><td colspan="4">Tidak ada jadwal mengajar hari ini.</td></tr>@endforelse
            </tbody>
          </table>
          </div>

          <div class="session-footer">
            <span>Ruang kelas: {{ $jadwalSaatIni?->ruangan ?? '-' }}</span>
            <strong>{{ $sesiBerlangsung ? 'Sesi aktif otomatis tersinkron' : 'Jadwal hari ini' }}</strong>
          </div>
        </div>

        <div class="right-stack">
          <div class="active-session">
            <div class="session-header">
              <div class="session-label">Sesi Mengajar Saat Ini</div>
              <div class="live-status {{ $jadwalSaatIni ? '' : 'inactive' }}"><span class="dot"></span> {{ $jadwalSaatIni ? 'Sedang Berlangsung' : 'Tidak ada sesi aktif' }}</div>
            </div>

            <div class="session-main">
              <div>
                <h3>{{ $jadwalSaatIni?->mapel?->nama_mapel ?? 'Tidak ada sesi aktif' }}</h3>
                <div class="meta">
                  @if($jadwalSaatIni)
                    <span>Kelas: {{ $jadwalSaatIni->kelas->nama_kelas }}</span>
                    <span>Jam: {{ substr($jadwalSaatIni->jam_mulai, 0, 5) }}–{{ substr($jadwalSaatIni->jam_selesai, 0, 5) }} WIB</span>
                  @else
                    <span>Tidak ada jadwal yang berlangsung saat ini.</span>
                  @endif
                </div>
              </div>
            </div>

            <a class="primary-btn" href="{{ route('guru.presensi-murid', $sesiBerlangsung ? ['sesi' => $sesiBerlangsung->id] : []) }}">{{ $sesiBerlangsung ? 'Buka Sesi Berlangsung' : ($jadwalSaatIni ? 'Mulai Sesi Jam Ini' : 'Lihat Jadwal Mengajar') }}</a>
          </div>
        </div>
      </section>

      <footer class="footer">
        <div>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</div>
        <div>© {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan. Seluruh hak cipta dilindungi.</div>
      </footer>
    </main>
  </div>

  <script src="{{ asset('guru css/guru-ui.js') }}?v=1"></script>
  <script>
    window.addEventListener('load', function () {
      setTimeout(function () {
        document.getElementById('pageLoader').classList.add('hide');
        document.getElementById('dashboardGuru').classList.add('loaded');
      }, 600);
    });
  </script>
</body>
</html>
