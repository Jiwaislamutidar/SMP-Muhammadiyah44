<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
  <title>Dashboard Murid - SMP Muhammadiyah 44</title>
  <link rel="stylesheet" href="{{ asset('siswa css/dashboardsiswa.css') }}?v=8">
</head>
<body>

  @php

    $hour = now()->hour;
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));

$status = $presensi['status'] ?? 'Belum Absen';
    $statusKey = match($status) {
      'Hadir' => 'hadir',
      'Izin'  => 'izin',
      'Alfa'  => 'alfa',
      default => 'belum',
    };

$rHadir = (int) ($ringkasan['hadir'] ?? 0);
    $rIzin  = (int) ($ringkasan['izin']  ?? 0);
    $rAlfa  = (int) ($ringkasan['alfa']  ?? 0);
    $rTotal = $rHadir + $rIzin + $rAlfa;
    $persen = $rTotal > 0 ? (int) round(($rHadir / $rTotal) * 100) : 0;

    $circumference = 2 * 3.14159265 * 52;
    $dashOffset    = $circumference * (1 - $persen / 100);
    $ringTone      = $persen >= 90 ? 'good' : ($persen >= 75 ? 'warn' : 'danger');
  @endphp

  <div class="page-loader" id="pageLoader">
    <div class="loader-logo-wrap">
      <div class="loader-spinner"></div>
      <div class="loader-logo">
        <img src="{{ asset('logo.jpg') }}" alt="Logo SMP Muhammadiyah 44">
      </div>
    </div>
    <div class="loader-text">SMP Muhammadiyah 44</div>
    <div class="loader-subtext">Memuat Halaman...</div>
  </div>

  <div class="dashboard-murid" id="dashboardMurid">

    <aside class="sidebar" id="sidebar">
      <nav class="sidebar-nav" aria-label="Menu utama">
        <a href="{{ route('siswa.dashboard') }}" class="nav-item active" aria-current="page" style="--delay: 1">
          <span class="nav-icon-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 10.5 12 3l9 7.5"/>
              <path d="M5 9.5V21h14V9.5"/>
              <path d="M9 21v-6h6v6"/>
            </svg>
          </span>
          Dashboard
        </a>
        <a href="{{ route('siswa.scan-qr') }}" class="nav-item" style="--delay: 2">
          <span class="nav-icon-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="7" height="7" rx="1"/>
              <rect x="14" y="3" width="7" height="7" rx="1"/>
              <rect x="3" y="14" width="7" height="7" rx="1"/>
              <path d="M14 14h3v3h-3z"/>
              <path d="M18 18h3v3h-3z"/>
            </svg>
          </span>
          Scan QR
        </a>
        <a href="{{ route('siswa.riwayat') }}" class="nav-item" style="--delay: 3">
          <span class="nav-icon-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="9"/>
              <path d="M12 7v5l3 2"/>
            </svg>
          </span>
          Riwayat Absensi
        </a>
        <a href="{{ route('siswa.profil') }}" class="nav-item" style="--delay: 4">
          <span class="nav-icon-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="8" r="4"/>
              <path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>
            </svg>
          </span>
          Profil
        </a>
      </nav>

      <div class="sidebar-footer">
        <div class="sidebar-footer-user">
          <div class="avatar">{{ $siswa->inisial ?? 'AF' }}</div>
          <div>
            <div class="name">{{ $siswa->nama ?? 'Ahmad Fauzan' }}</div>
            <div class="role">{{ $siswa->kelas ?? '-' }} • Murid</div>
          </div>
        </div>
        <form action="{{ route('siswa.logout') }}" method="POST" style="margin:0;">
          @csrf
          <button type="submit" class="nav-item logout-item"
                  style="width:100%; border:none; background:transparent; text-align:left; cursor:pointer;">
            <span class="nav-icon-box">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                   stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                <path d="m10 17-5-5 5-5"/>
                <path d="M15 12H5"/>
              </svg>
            </span>
            Keluar
          </button>
        </form>
      </div>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <header class="topbar">
      <div class="topbar-left">
        <button class="burger-btn" id="burgerBtn" onclick="toggleSidebar()" aria-label="Buka menu"
                aria-expanded="false" aria-controls="sidebar">
          <span></span><span></span><span></span>
        </button>
        <img class="topbar-logo" src="{{ asset('logo.jpg') }}" alt="Logo SMP Muhammadiyah 44">
        <div class="portal-tag">Dashboard • SMP Muhammadiyah 44</div>
      </div>
      <div class="topbar-right">
        <div class="ta-badge" id="liveDatetime">Memuat waktu...</div>
        <button class="icon-btn" aria-label="Notifikasi" type="button">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/>
            <path d="M10.3 21a2 2 0 0 0 3.4 0"/>
          </svg>
          <span class="badge-dot"></span>
        </button>
        <div class="user-mini">
          <div class="avatar">{{ $siswa->inisial ?? 'AF' }}</div>
          <div>
            <div class="name">{{ $siswa->nama ?? 'Ahmad Fauzan' }}</div>
            <div class="role">{{ $siswa->kelas ?? '-' }} • Murid</div>
          </div>
        </div>
      </div>
    </header>

    <main class="content">

      <div class="page-header">
        <div>
          <h1>{{ $greeting }}, {{ $siswa->nama ?? 'Ahmad Fauzan' }}</h1>
          <p>Pantau kehadiran kamu hari ini.</p>
        </div>
        <div class="role-status">
          <div class="status-pill">
            <span class="dot"></span> Status Siswa: <strong>{{ $siswa->status ?? 'Aktif' }}</strong>
          </div>
          <div class="class-pill">{{ $siswa->kelas ?? '-' }} • {{ $semester ?? '-' }}</div>
        </div>
      </div>

      <div class="top-row">

        <section class="card attendance-card">
          <div class="card-header">
            <div class="card-title">
              <span class="bar"></span>
              <h2>Kehadiran Hari Ini</h2>
            </div>
            <div class="date-chip">
              {{ $tanggalHariIni ?? now()->locale('id')->translatedFormat('l, j F Y') }}
            </div>
          </div>

          <div class="status-hero {{ $statusKey }}">
            <div class="status-hero-content">
              <div class="status-label">Status Presensi</div>
              <div class="status-badge {{ $statusKey }}">
                <span class="dot"></span>
                {{ $status }}
              </div>
              <div class="status-note">
                Terverifikasi {{ $presensi['verifikator'] ?? '-' }}
              </div>
            </div>

            <div class="status-icon" aria-hidden="true">
              @if($statusKey === 'hadir')
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m5 12 5 5L20 7"/>
                </svg>
              @elseif($statusKey === 'izin')
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="5" width="18" height="14" rx="2"/>
                  <path d="m3 7 9 6 9-6"/>
                </svg>
              @elseif($statusKey === 'alfa')
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M6 6 18 18M18 6 6 18"/>
                </svg>
              @else
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="9"/>
                  <path d="M12 7v5l3 2"/>
                </svg>
              @endif
            </div>
          </div>

          <div class="key-details">
            <div class="box">
              <div class="label">Mata Pelajaran</div>
              <div class="value">{{ $presensi['mapel'] ?? '-' }}</div>
              <div class="sub">{{ $presensi['sesi'] ?? '-' }}</div>
            </div>
            <div class="box">
              <div class="label">Kelas</div>
              <div class="value">{{ $siswa->kelas ?? '-' }}</div>
              <div class="sub">{{ $presensi['ruang'] ?? '-' }}</div>
            </div>
            <div class="box">
              <div class="label">Jam Pelajaran</div>
              <div class="value">{{ $presensi['jam'] ?? '-' }}</div>
              <div class="sub">Tercatat: {{ $presensi['waktu_tercatat'] ?? '-' }}</div>
            </div>
          </div>

          <div class="next-session">
            <span>Sesi berikutnya: <strong>{{ $sesiBerikutnya ?? '-' }}</strong></span>
            <span class="live">{{ $sesi['status'] ?? 'Tidak ada sesi aktif' }}</span>
          </div>
        </section>

        <section class="card scan-card">
          <div class="card-header">
            <div class="card-title">
              <span class="bar"></span>
              <h2>Scan QR Absensi</h2>
            </div>
          </div>

          <p class="desc">
            Scan QR yang ditampilkan guru untuk mencatat kehadiran kamu di kelas saat sesi pembelajaran berlangsung.
          </p>

          <div class="scan-preview">
            <div class="qr-frame" aria-hidden="true">
              <span class="qr-corner tl"></span>
              <span class="qr-corner tr"></span>
              <span class="qr-corner bl"></span>
              <span class="qr-corner br"></span>
              <div class="qr-icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                  <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                  <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                  <path d="M14 14h3v3h-3z"/>
                  <path d="M18 18h3v3h-3z"/>
                </svg>
              </div>
            </div>

            <div>
              <div class="title">Kamera Scanner Presensi</div>
              <div class="sub">Arahkan kamera ke layar proyektor atau gawai guru</div>
            </div>
          </div>

          <div class="scan-actions">
            <button class="btn-camera" onclick="window.location.href='{{ route('siswa.scan-qr') }}'">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                   stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M23 7l-7 5 7 5V7z"/>
                <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
              </svg>
              Buka Kamera
            </button>
          </div>
        </section>
      </div>

      <div class="second-row">

        <div class="left-col">

          <section class="card">
            <div class="card-header">
              <div class="card-title">
                <span class="bar"></span>
                <h2>Ringkasan Kehadiran</h2>
              </div>
              <div class="date-chip">{{ $semesterInfo ?? 'Semester Ganjil 2026' }}</div>
            </div>

            <div class="ringkasan-body">
              <div class="ring-wrap">
                <svg class="ring" viewBox="0 0 120 120" aria-hidden="true">
                  <circle class="ring-track" cx="60" cy="60" r="52"/>
                  <circle class="ring-fill {{ $ringTone }}"
                          cx="60" cy="60" r="52"
                          stroke-dasharray="{{ number_format($circumference, 2, '.', '') }}"
                          stroke-dashoffset="{{ number_format($dashOffset, 2, '.', '') }}"/>
                </svg>
                <div class="ring-content">
                  <span class="ring-num">{{ $persen }}<small>%</small></span>
                  <span class="ring-label">Kehadiran</span>
                </div>
              </div>

              <div class="stat-chips">
                <div class="stat-chip hadir">
                  <span class="chip-dot"></span>
                  <div>
                    <div class="chip-num">{{ $rHadir }}</div>
                    <div class="chip-label">Hadir</div>
                  </div>
                </div>
                <div class="stat-chip izin">
                  <span class="chip-dot"></span>
                  <div>
                    <div class="chip-num">{{ $rIzin }}</div>
                    <div class="chip-label">Izin</div>
                  </div>
                </div>
                <div class="stat-chip alfa">
                  <span class="chip-dot"></span>
                  <div>
                    <div class="chip-num">{{ $rAlfa }}</div>
                    <div class="chip-label">Alfa</div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section class="card">
            <div class="card-header">
              <div class="card-title">
                <span class="bar"></span>
                <h2>Absensi Terbaru</h2>
              </div>
              <a href="{{ route('siswa.riwayat') }}" class="date-chip">Lihat Semua →</a>
            </div>

            <div class="recent-list">
              @forelse($absensiTerbaru ?? [] as $absen)
                <div class="item">
                  <div>
                    <div class="mapel">{{ $absen['mapel'] }}</div>
                    <div class="meta">{{ $absen['tanggal'] }} • {{ $absen['waktu'] }}</div>
                  </div>
                  <span class="pill-{{ strtolower($absen['status']) }}">● {{ $absen['status'] }}</span>
                </div>
              @empty
                <div class="empty-state">
                  <div class="empty-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="9"/>
                      <path d="M12 7v5l3 2"/>
                    </svg>
                  </div>
                  <p class="empty-title">Belum ada catatan presensi</p>
                  <p class="empty-sub">Scan QR saat sesi dimulai untuk mulai mencatat kehadiran.</p>
                </div>
              @endforelse
            </div>
          </section>
        </div>

        <section class="card right-col">
          <div class="card-header">
            <div class="card-title">
              <span class="bar"></span>
              <h2>Jadwal Hari Ini</h2>
            </div>
            <div class="date-chip">
              {{ $siswa->kelas ?? '-' }} • {{ count($jadwalHariIni ?? []) }} Sesi Pembelajaran
            </div>
          </div>

          <div class="table-scroll">
            <table class="jadwal">
              <thead>
                <tr>
                  <th>Jam</th>
                  <th>Mapel</th>
                  <th>Guru</th>
                  <th>Kelas</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @forelse($jadwalHariIni ?? [] as $j)
                  <tr class="{{ $j['status'] === 'Berlangsung' ? 'berlangsung' : '' }}">
                    <td data-label="Jam">{{ $j['jam'] }}</td>
                    <td data-label="Mapel">{{ $j['mapel'] }}</td>
                    <td data-label="Guru">{{ $j['guru'] ?? '-' }}</td>
                    <td data-label="Kelas">
                      <span class="kelas-chip">{{ $j['kelas'] ?? $siswa->kelas ?? '-' }}</span>
                    </td>
                    <td data-label="Status">
                      @if($j['status'] === 'Selesai')
                        <span class="status-selesai">Selesai</span>
                      @elseif($j['status'] === 'Berlangsung')
                        <span class="status-berlangsung">Berlangsung</span>
                      @else
                        <span class="status-netral">{{ $j['status'] }}</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" data-label="Info">
                      Tidak ada jadwal pembelajaran untuk kelas Anda hari ini.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="jadwal-footer">
            <span>
              Menampilkan jadwal aktif
              {{ $tanggalHariIni ?? now()->locale('id')->translatedFormat('l, j F Y') }}
            </span>
            <span>Jadwal {{ $siswa->kelas ?? '-' }}</span>
          </div>
        </section>
      </div>

    </main>

    <footer class="footer">
      <div>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</div>
      <div>© {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan. Seluruh hak cipta dilindungi.</div>
    </footer>
  </div>

  <script>
    window.addEventListener('load', function () {
      setTimeout(function () {
        document.getElementById('pageLoader').classList.add('hide');
        document.getElementById('dashboardMurid').classList.add('loaded');
      }, 1000);
    });
  </script>

  <script>
    function toggleSidebar(force) {
      var sidebar = document.getElementById('sidebar');
      var overlay = document.getElementById('sidebarOverlay');
      var burger  = document.getElementById('burgerBtn');
      var open = typeof force === 'boolean' ? force : !sidebar.classList.contains('open');

      sidebar.classList.toggle('open', open);
      overlay.classList.toggle('show', open);
      burger.classList.toggle('active', open);
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
      burger.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
    }
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') toggleSidebar(false);
    });
    document.querySelectorAll('.sidebar .nav-item.active').forEach(function (item) {
      item.addEventListener('click', function (event) {
        event.preventDefault();
        toggleSidebar(false);
      });
    });
  </script>

  <script>
    (function () {
      var hariList  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
      var bulanList = ['Januari','Februari','Maret','April','Mei','Juni',
                       'Juli','Agustus','September','Oktober','November','Desember'];

      function updateLiveDatetime() {
        var now     = new Date();
        var hari    = hariList[now.getDay()];
        var tanggal = now.getDate();
        var bulan   = bulanList[now.getMonth()];
        var tahun   = now.getFullYear();
        var jam     = String(now.getHours()).padStart(2, '0');
        var menit   = String(now.getMinutes()).padStart(2, '0');
        var detik   = String(now.getSeconds()).padStart(2, '0');

        document.getElementById('liveDatetime').innerHTML =
          '<span class="dt-date">' + hari + ', ' + tanggal + ' ' + bulan + ' ' + tahun + '</span>' +
          '<span class="dt-sep"> | </span>' +
          '<span class="dt-time">' + jam + ':' + menit + ':' + detik + ' WIB</span>';
      }

      updateLiveDatetime();
      setInterval(updateLiveDatetime, 1000);
    })();
  </script>
</body>
</html>