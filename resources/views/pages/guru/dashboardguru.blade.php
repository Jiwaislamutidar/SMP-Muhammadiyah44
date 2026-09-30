<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
  <title>Dashboard Guru - SMP Muhammadiyah 44</title>
  <link rel="stylesheet" href="{{ asset('guru css/dashboard.css') }}?v=2">
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
    <div class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-brand">
          <div class="sidebar-logo">
            <img src="{{ asset('logo.jpg') }}" alt="Logo SMP Muhammadiyah 44">
          </div>
          <div>
            <div class="sidebar-title">SMP Muhammadiyah 44</div>
            <div class="sidebar-subtitle">Portal Guru</div>
          </div>
        </div>
        <button class="sidebar-close-btn" onclick="toggleSidebar()" aria-label="Tutup menu">✕</button>
      </div>

      <div class="sidebar-section-label">Menu</div>
      <nav class="sidebar-nav">
        <a href="{{ route('guru.dashboard') }}" class="nav-item active" style="--delay: 1">
          <span class="nav-icon-box">🏠</span> Dashboard
        </a>
        <a href="{{ route('guru.presensi-guru') }}" class="nav-item" style="--delay: 2">
          <span class="nav-icon-box">🧾</span> Presensi Guru
        </a>
        <a href="{{ route('guru.jadwal') }}" class="nav-item" style="--delay: 3">
          <span class="nav-icon-box">📅</span> Jadwal Mengajar
        </a>
        <a href="{{ route('guru.presensi-murid') }}" class="nav-item" style="--delay: 4">
          <span class="nav-icon-box">👥</span> Presensi Murid
        </a>
        <a href="{{ route('guru.riwayat-presensi') }}" class="nav-item" style="--delay: 5">
          <span class="nav-icon-box">🕘</span> Riwayat Presensi
        </a>
        <a href="{{ route('guru.profil') }}" class="nav-item" style="--delay: 6">
          <span class="nav-icon-box">👤</span> Profil
        </a>
      </nav>

      <div class="sidebar-footer">
        <div class="sidebar-footer-user">
          <div class="avatar">AF</div>
          <div>
            <div class="name">Ust. Ahmad Fauzi</div>
            <div class="role">Guru Mata Pelajaran</div>
          </div>
        </div>
        <form action="{{ route('guru.logout') }}" method="POST" style="margin:0;">
          @csrf
          <button type="submit" class="nav-item logout-item" style="width:100%; border:none; background:transparent; text-align:left; cursor:pointer;">
            <span class="nav-icon-box">🚪</span> Keluar
          </button>
        </form>
      </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <header class="topbar">
      <div class="topbar-left">
        <button class="burger-btn" id="burgerBtn" onclick="toggleSidebar()" aria-label="Buka menu">
          <span></span>
          <span></span>
          <span></span>
        </button>

        <div class="brand-wrap">
          <div class="brand-dot"></div>
          <div class="portal-tag">Portal Guru • SMP Muhammadiyah 44</div>
        </div>
      </div>

      <div class="topbar-right">
        <div class="date-pill" id="liveDatetime">Memuat waktu...</div>

        <div class="bell-btn" aria-label="Notifikasi">
          🔔
          <span class="badge"></span>
        </div>

        <div class="profile-pill">
          <div class="avatar">AF</div>
          <div class="profile-meta">
            <div class="name">Ust. Ahmad Fauzi, S.Pd.</div>
            <div class="role">Guru Mata Pelajaran</div>
          </div>
        </div>
      </div>
    </header>

    <main class="content">
      <div class="page-header">
        <h1>Selamat Datang, {{ $guru->nama_lengkap }}</h1>
        <p>Berikut informasi jadwal dan presensi Anda hari ini.</p>
      </div>

      <section class="stats-row">
        <div class="stat-card">
          <div class="stat-left">
            <div class="icon-wrap">🏫</div>
            <div class="stat-meta">
              <div class="label">Masuk Sekolah</div>
              <div class="value">{{ $presensiHariIni?->jam_masuk ? substr($presensiHariIni->jam_masuk, 0, 5).' WIB' : '--:--' }}</div>
            </div>
          </div>
          <div class="status-badge"><span class="dot"></span> {{ $presensiHariIni?->jam_masuk ? 'Sudah Absen' : 'Belum Absen' }}</div>
        </div>

        <div class="stat-card">
          <div class="stat-left">
            <div class="icon-wrap alt">🏠</div>
            <div class="stat-meta">
              <div class="label">Pulang Sekolah</div>
              <div class="value">{{ $presensiHariIni?->jam_pulang ? substr($presensiHariIni->jam_pulang, 0, 5).' WIB' : '--:--' }}</div>
            </div>
          </div>
          <div class="status-badge pending"><span class="dot"></span> {{ $presensiHariIni?->jam_pulang ? 'Sudah Absen' : 'Belum Absen' }}</div>
        </div>
      </section>

      <section class="main-grid">
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title"><span class="bar"></span> Jadwal Mengajar Hari Ini</div>
            <div class="mini-label">{{ $jadwals->count() }} Sesi KBM</div>
          </div>

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
              @forelse($jadwals as $jadwal)
                @php($sesiJadwal = $jadwal->sesiPelajarans->first())
                <tr class="{{ $sesiJadwal?->status_sesi === 'Berlangsung' ? 'active-row' : '' }}">
                  <td>{{ substr($jadwal->jam_mulai, 0, 5) }}-{{ substr($jadwal->jam_selesai, 0, 5) }}</td>
                  <td>{{ $jadwal->mapel->nama_mapel }}</td>
                  <td><span class="pill-soft">{{ $jadwal->kelas->nama_kelas }}</span></td>
                  <td style="text-align:right"><span class="pill-live"><span class="dot"></span>{{ $sesiJadwal?->status_sesi ?? 'Belum' }}</span></td>
                </tr>
              @empty<tr><td colspan="4">Tidak ada jadwal mengajar hari ini.</td></tr>@endforelse
            </tbody>
          </table>

          <div class="session-footer">
            <span>Ruang kelas: {{ $sesiAktif?->ruangan ?? '-' }}</span>
            <strong>Sesi aktif otomatis tersinkron</strong>
          </div>
        </div>

        <div class="right-stack">
          <div class="active-session">
            <div class="session-header">
              <div class="session-label">Sesi Mengajar Saat Ini</div>
              <div class="live-status"><span class="dot"></span> {{ $sesiAktif ? 'Sedang Berlangsung' : 'Tidak ada sesi aktif' }}</div>
            </div>

            <div class="session-main">
              <div>
                <h3>{{ $sesiAktif?->mapel?->nama_mapel ?? '-' }}</h3>
                <div class="meta">
                  <span>{{ $sesiAktif?->kelas?->nama_kelas ?? '-' }}</span>
                  <span>•</span>
                  <span>{{ $sesiAktif ? substr($sesiAktif->jam_mulai, 0, 5).'-'.substr($sesiAktif->jam_selesai, 0, 5).' WIB' : '-' }}</span>
                </div>
              </div>
              <div class="session-icon">📚</div>
            </div>

            <a class="primary-btn" href="{{ route('guru.presensi-murid', $sesiBerlangsung ? ['sesi' => $sesiBerlangsung->id] : []) }}">▶ {{ $sesiBerlangsung ? 'Buka Sesi Berlangsung' : ($jadwalSaatIni ? 'Mulai Sesi Jam Ini' : 'Lihat Jadwal Mengajar') }}</a>
          </div>

          <div class="summary-panel">
            <div class="summary-top">
              <h4>Presensi Murid</h4>
              <span class="green-badge">{{ $sesiAktif ? $sesiAktif->mapel->nama_mapel.' • '.$sesiAktif->kelas->nama_kelas : '-' }}</span>
            </div>

            <div class="summary-body">
              @php($activeSession = $sesiAktif?->sesiPelajarans->first())
              @php($presentCount = $activeSession?->presensiPelajarans->where('status', 'Hadir')->count() ?? 0)
              @php($totalCount = $activeSession?->presensiPelajarans->count() ?? 0)
              <div class="attendance-counts"><div class="count"><strong>{{ $presentCount }}</strong><span>dari {{ $totalCount }} murid hadir</span></div><div class="count pending-count"><strong>{{ $belumAbsenCount }}</strong><span>murid belum absen</span></div></div>
              <div class="progress"><span style="width:{{ $totalCount ? round($presentCount / $totalCount * 100) : 0 }}%"></span></div>
            </div>

            <button class="secondary-btn" onclick="window.location.href='{{ route('guru.presensi-murid') }}'">Kelola Presensi Murid</button>
          </div>
        </div>
      </section>

      <footer class="footer">
        <div>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</div>
        <div>© 2026 SMP Muhammadiyah 44 Tangerang Selatan. Seluruh hak cipta dilindungi.</div>
      </footer>
    </main>
  </div>

  <script>
    window.addEventListener('load', function () {
      setTimeout(function () {
        document.getElementById('pageLoader').classList.add('hide');
        document.getElementById('dashboardGuru').classList.add('loaded');
      }, 600);
    });

    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebarOverlay');
      const burger = document.getElementById('burgerBtn');

      if (!sidebar || !overlay || !burger) return;

      sidebar.classList.toggle('open');
      overlay.classList.toggle('show');
      burger.classList.toggle('active');
    }

    function updateLiveDatetime() {
      const now = new Date();
      const hariList = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
      const bulanList = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

      const hari = hariList[now.getDay()];
      const tanggal = now.getDate();
      const bulan = bulanList[now.getMonth()];
      const tahun = now.getFullYear();
      const jam = String(now.getHours()).padStart(2, '0');
      const menit = String(now.getMinutes()).padStart(2, '0');
      const detik = String(now.getSeconds()).padStart(2, '0');

      const el = document.getElementById('liveDatetime');
      if (el) {
        el.innerHTML = `<span class="date-html">${hari}, ${tanggal} ${bulan} ${tahun}</span><span class="sep"> | </span><span class="time-html">${jam}:${menit}:${detik} WIB</span>`;
      }
    }

    updateLiveDatetime();
    setInterval(updateLiveDatetime, 1000);
  </script>
</body>
</html>
