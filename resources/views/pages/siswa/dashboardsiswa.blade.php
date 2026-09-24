<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Murid - SMP Muhammadiyah 44</title>
  <link rel="stylesheet" href="{{ asset('siswa css/dashboardsiswa.css') }}?v=6">
</head>
<body>

  {{-- Preloader, muncul sesaat pas halaman pertama dibuka --}}
  <div class="page-loader" id="pageLoader">
    <div class="loader-logo-wrap">
      <div class="loader-spinner"></div>
      <div class="loader-logo">
        <img src="{{ asset('logo.jpg') }}" alt="Logo SMP Muhammadiyah 44">
      </div>
    </div>
    <div class="loader-text">SMP Muhammadiyah 44</div>
    <div class="loader-subtext">Memuat data presensi...</div>
  </div>

  <div class="dashboard-murid" id="dashboardMurid">

    {{-- Sidebar --}}
    <div class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-brand">
          <div class="sidebar-logo">
            <img src="{{ asset('logo.jpg') }}" alt="Logo SMP Muhammadiyah 44">
          </div>
          <div>
            <div class="sidebar-title">SMP Muhammadiyah 44</div>
            <div class="sidebar-subtitle">Portal Presensi Murid</div>
          </div>
        </div>
        <button class="sidebar-close-btn" onclick="toggleSidebar()" aria-label="Tutup menu">✕</button>
      </div>

      <div class="sidebar-section-label">Menu</div>
      <nav class="sidebar-nav">
        <a href="{{ route('siswa.dashboard') }}" class="nav-item active" style="--delay: 1">
          <span class="nav-icon-box">🏠</span> Dashboard
        </a>
        <a href="{{ route('siswa.scan-qr') }}" class="nav-item" style="--delay: 2">
          <span class="nav-icon-box">📷</span> Scan QR
        </a>
        <a href="{{ route('siswa.riwayat') }}" class="nav-item" style="--delay: 3">
          <span class="nav-icon-box">🕘</span> Riwayat Absensi
        </a>
        <a href="{{ route('siswa.profil') }}" class="nav-item" style="--delay: 4">
          <span class="nav-icon-box">👤</span> Profil
        </a>
      </nav>

      <div class="sidebar-footer">
        <div class="sidebar-footer-user">
          <div class="avatar">{{ $siswa->inisial ?? 'AF' }}</div>
          <div>
            <div class="name">{{ $siswa->nama ?? 'Ahmad Fauzan' }}</div>
            <div class="role">{{ $siswa->kelas ?? '7A' }} • Murid</div>
          </div>
        </div>
        <form action="{{ route('siswa.logout') }}" method="POST" style="margin:0;">
          @csrf
          <button type="submit" class="nav-item logout-item" style="width:100%; border:none; background:transparent; text-align:left; cursor:pointer;">
            <span class="nav-icon-box">🚪</span> Keluar
          </button>
        </form>
      </div>
    </div>

    {{-- Overlay, buat nutup sidebar kalau diklik di luar --}}
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    {{-- Topbar --}}
    <div class="topbar">
      <div class="topbar-left">
        <button class="burger-btn" id="burgerBtn" onclick="toggleSidebar()" aria-label="Buka menu">
          <span></span><span></span><span></span>
        </button>
        <div class="portal-tag">Portal Murid • SMP Muhammadiyah 44</div>
      </div>
      <div class="topbar-right">
        <div class="ta-badge" id="liveDatetime">Memuat waktu...</div>
        <div class="icon-btn">
          🔔
          <span class="badge-dot"></span>
        </div>
        <div class="user-mini">
          <div class="avatar">{{ $siswa->inisial ?? 'AF' }}</div>
          <div>
            <div class="name">{{ $siswa->nama ?? 'Ahmad Fauzan' }}</div>
            <div class="role">{{ $siswa->kelas ?? '7A' }} • Murid</div>
          </div>
        </div>
      </div>
    </div>

    <div class="content">

      {{-- Header sapaan --}}
      <div class="page-header">
        <div>
          <h1>Selamat Datang, {{ $siswa->nama ?? 'Ahmad Fauzan' }}</h1>
          <p>Pantau kehadiran kamu hari ini.</p>
        </div>
        <div class="role-status">
          <div class="status-pill">
            <span class="dot"></span> Status Siswa: <strong>{{ $siswa->status ?? 'Aktif' }}</strong>
          </div>
          <div class="class-pill">{{ $siswa->kelas ?? 'Kelas 7A' }} • {{ $semester ?? 'Semester Ganjil' }}</div>
        </div>
      </div>

      {{-- Baris atas: kehadiran hari ini & scan QR --}}
      <div class="top-row">

        <div class="card attendance-card">
          <div class="card-header">
            <div class="card-title"><span class="bar"></span><h2>Kehadiran Hari Ini</h2></div>
            <div class="date-chip">{{ $tanggalHariIni ?? 'Senin, 8 September 2026' }}</div>
          </div>

          <div class="status-box">
            <div>
              <div class="status-label">Status Presensi</div>
              <div class="status-badge"><span class="dot"></span> {{ $presensi['status'] ?? 'Hadir' }}</div>
              <div class="status-note">Terverifikasi {{ $presensi['verifikator'] ?? 'Ustadz Pengampu' }}</div>
            </div>
            <div class="status-icon"></div>
          </div>

          <div class="key-details">
            <div class="box">
              <div class="label">Mata Pelajaran</div>
              <div class="value">{{ $presensi['mapel'] ?? 'Matematika' }}</div>
              <div class="sub">{{ $presensi['sesi'] ?? 'Sesi 1 Selesai' }}</div>
            </div>
            <div class="box">
              <div class="label">Kelas</div>
              <div class="value">{{ $siswa->kelas ?? '7A' }}</div>
              <div class="sub">{{ $presensi['ruang'] ?? 'Ruang Kelas 03' }}</div>
            </div>
            <div class="box">
              <div class="label">Jam Pelajaran</div>
              <div class="value">{{ $presensi['jam'] ?? '07.00 – 08.20' }}</div>
              <div class="sub">Tercatat: {{ $presensi['waktu_tercatat'] ?? '07.02 WIB' }}</div>
            </div>
          </div>

          <div class="next-session">
            <span>Sesi pembelajaran berikutnya: <strong>{{ $sesiBerikutnya ?? 'IPA Terpadu (08.20 WIB)' }}</strong></span>
            <span class="live">Sesi Berlangsung</span>
          </div>
        </div>

        <div class="card scan-card">
          <div class="card-header">
            <div class="card-title"><span class="bar"></span><h2>Scan QR Absensi</h2></div>
          </div>
          <p class="desc">Scan QR yang ditampilkan guru untuk mencatat kehadiran kamu di kelas saat sesi pembelajaran berlangsung.</p>
          <div class="scan-preview">

            {{-- Kotak Ikon yang ditambahkan SVG QR Code --}}
            <div class="icon" style="display: flex; align-items: center; justify-content: center;">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <rect x="7" y="7" width="3" height="3"></rect>
                <rect x="14" y="7" width="3" height="3"></rect>
                <rect x="7" y="14" width="3" height="3"></rect>
                <rect x="14" y="14" width="3" height="3"></rect>
              </svg>
            </div>

            <div>
              <div class="title">Kamera Scanner Presensi</div>
              <div class="sub">Arahkan kamera ke layar proyektor atau gawai guru</div>
            </div>
          </div>

          {{-- Tombol dengan tambahan fungsi onclick pindah rute --}}
          <div class="scan-actions">
            <button class="btn-camera" onclick="window.location.href='{{ route('siswa.scan-qr') }}'">Buka Kamera</button>
          </div>
        </div>
      </div>

      {{-- Baris kedua: ringkasan, absensi terbaru, jadwal --}}
      <div class="second-row">

        <div class="left-col">
          <div class="card">
            <div class="card-header">
              <div class="card-title"><span class="bar"></span><h2>Ringkasan Kehadiran</h2></div>
              <div class="date-chip">{{ $semesterInfo ?? 'Semester Ganjil 2026' }}</div>
            </div>
            <div class="stat-cards">
              <div class="stat hadir">
                <div class="label">Hadir</div>
                <div class="num">{{ $ringkasan['hadir'] ?? 18 }}</div>
                <div class="sub">Presensi Sah</div>
              </div>
              <div class="stat izin">
                <div class="label">Izin</div>
                <div class="num">{{ $ringkasan['izin'] ?? 2 }}</div>
                <div class="sub">Surat Masuk</div>
              </div>
              <div class="stat alfa">
                <div class="label">Alfa</div>
                <div class="num">{{ $ringkasan['alfa'] ?? 1 }}</div>
                <div class="sub">Tanpa Berita</div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <div class="card-title"><span class="bar"></span><h2>Absensi Terbaru</h2></div>
              <a href="{{ route('siswa.riwayat') }}" class="date-chip" style="text-decoration:none;">Lihat Semua Riwayat →</a>
            </div>
            <div class="recent-list">
              @forelse($absensiTerbaru ?? [] as $absen)
                <div class="item">
                  <div>
                    <div class="mapel">{{ $absen['mapel'] }}</div>
                    <div class="meta">{{ $absen['tanggal'] }} • {{$absen['waktu'] }}</div>
                  </div>
                  <span class="pill-{{ strtolower($absen['status']) }}">● {{ $absen['status'] }}</span>
                </div>
              @empty
                {{-- data contoh statis sesuai desain --}}
                <div class="item">
                  <div><div class="mapel">Matematika</div><div class="meta">08 Sep 2026 • 07.02 WIB</div></div>
                  <span class="pill-hadir">● Hadir</span>
                </div>
                <div class="item">
                  <div><div class="mapel">IPA Terpadu</div><div class="meta">07 Sep 2026 • 08.25 WIB</div></div>
                  <span class="pill-hadir">● Hadir</span>
                </div>
                <div class="item">
                  <div><div class="mapel">Bahasa Indonesia</div><div class="meta">06 Sep 2026 • Surat Sakit</div></div>
                  <span class="pill-izin">● Izin</span>
                </div>
              @endforelse
            </div>
          </div>
        </div>

        <div class="card right-col">
          <div class="card-header">
            <div class="card-title"><span class="bar"></span><h2>Jadwal Hari Ini</h2></div>
            <div class="date-chip">{{ $siswa->kelas ?? '7A' }} • {{ count($jadwalHariIni ?? []) ?: 4 }} Sesi Pembelajaran</div>
          </div>

          {{-- Dibungkus supaya tabel bisa discroll ke samping di layar kecil, bukan gepeng --}}
          <div class="table-scroll">
            <table class="jadwal">
              <thead>
                <tr><th>Jam</th><th>Mapel</th><th>Guru</th><th>Kelas</th><th>Status</th></tr>
              </thead>
              <tbody>
                @forelse($jadwalHariIni ?? [] as $j)
                  <tr class="{{ $j['status'] === 'Berlangsung' ? 'berlangsung' : '' }}">
                    <td>{{ $j['jam'] }}</td>
                    <td>{{ $j['mapel'] }}</td>
                    <td>{{ $j['guru'] ?? '-' }}</td>
                    <td><span class="kelas-chip">{{ $j['kelas'] ?? $siswa->kelas ?? '7A' }}</span></td>
                    <td>
                      @if($j['status'] === 'Selesai')
                        <span class="status-selesai">● Selesai</span>
                      @elseif($j['status'] === 'Berlangsung')
                        <span class="status-berlangsung">Berlangsung</span>
                      @else
                        <span class="status-netral">{{ $j['status'] }}</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  {{-- data contoh statis sesuai desain --}}
                  <tr>
                    <td>07.00–08.20</td><td>Matematika</td><td>Ustadz Ahmad</td>
                    <td><span class="kelas-chip">7A</span></td><td><span class="status-selesai">● Selesai</span></td>
                  </tr>
                  <tr class="berlangsung">
                    <td>08.20–09.40</td><td>IPA</td><td>Ustadzah Siti</td>
                    <td><span class="kelas-chip">7A</span></td><td><span class="status-berlangsung">Berlangsung</span></td>
                  </tr>
                  <tr>
                    <td>09.40–10.00</td><td>Istirahat & Sholat Dhuha</td><td>-</td>
                    <td></td><td><span class="status-netral">Istirahat</span></td>
                  </tr>
                  <tr>
                    <td>10.00–11.20</td><td>Bahasa Indonesia</td><td>Ustadz Budi</td>
                    <td><span class="kelas-chip">7A</span></td><td><span class="status-netral">Mendatang</span></td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="jadwal-footer">
            <span>Menampilkan jadwal aktif {{ $tanggalHariIni ?? 'Senin, 8 September 2026' }}</span>
            <span>Jadwal {{ $siswa->kelas ?? 'Kelas 7A' }}</span>
          </div>
        </div>
      </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
      <div>Sistem Presensi & Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</div>
      <div>© {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan. Seluruh hak cipta dilindungi.</div>
    </div>
  </div>

  {{-- Sembunyikan preloader & tampilkan konten setelah halaman siap --}}
  <script>
    window.addEventListener('load', function () {
      setTimeout(function () {
        document.getElementById('pageLoader').classList.add('hide');
        document.getElementById('dashboardMurid').classList.add('loaded');
      }, 1000);
    });
  </script>

  {{-- Toggle buka/tutup sidebar --}}
  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('open');
      document.getElementById('sidebarOverlay').classList.toggle('show');
      document.getElementById('burgerBtn').classList.toggle('active');
    }
  </script>

  {{-- Jam & tanggal hidup, update tiap detik. Dipecah jadi 2 <span> (tanggal & jam)
       supaya di layar kecil tanggalnya bisa disembunyikan lewat CSS, sisain jamnya aja --}}
  <script>
    function updateLiveDatetime() {
      const hariList = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
      const bulanList = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
      const now = new Date();
      const hari = hariList[now.getDay()];
      const tanggal = now.getDate();
      const bulan = bulanList[now.getMonth()];
      const tahun = now.getFullYear();
      const jam = String(now.getHours()).padStart(2, '0');
      const menit = String(now.getMinutes()).padStart(2, '0');
      const detik = String(now.getSeconds()).padStart(2, '0');
      document.getElementById('liveDatetime').innerHTML =
        `<span class="dt-date">${hari}, ${tanggal} ${bulan} ${tahun}</span>` +
        `<span class="dt-sep"> | </span>` +
        `<span class="dt-time">${jam}:${menit}:${detik} WIB</span>`;
    }
    updateLiveDatetime();
    setInterval(updateLiveDatetime, 1000);
  </script>
</body>
</html>