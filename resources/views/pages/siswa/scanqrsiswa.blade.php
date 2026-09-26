<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
  <title>Scan QR Presensi - SMP Muhammadiyah 44</title>
  <link rel="stylesheet" href="{{ asset('siswa css/scanqrsiswa.css') }}?v=3">
</head>
<body>

  <!-- Preloader -->
  <div class="page-loader" id="pageLoader">
    <div class="loader-logo-wrap">
      <div class="loader-spinner"></div>
      <div class="loader-logo">
        <img src="{{ asset('logo.jpg') }}" alt="Logo SMP Muhammadiyah 44">
      </div>
    </div>
    <div class="loader-text">SMP Muhammadiyah 44</div>
    <div class="loader-subtext">Membuka kamera scanner...</div>
  </div>

  <div class="dashboard-murid" id="dashboardMurid">

    <!-- Sidebar -->
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
        <a href="{{ route('siswa.dashboard') }}" class="nav-item" style="--delay: 1">
          <span class="nav-icon-box">🏠</span> Dashboard
        </a>
        <a href="{{ route('siswa.scan-qr') }}" class="nav-item active" style="--delay: 2">
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
          <div class="avatar">AF</div>
          <div>
            <div class="name">Ahmad Fauzan</div>
            <div class="role">7A • Murid</div>
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

    <!-- Overlay Sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Topbar -->
    <div class="topbar">
      <div class="topbar-left">
        <button class="burger-btn" id="burgerBtn" onclick="toggleSidebar()">
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
          <div class="avatar">AF</div>
          <div>
            <div class="name">Ahmad Fauzan</div>
            <div class="role">7A • Murid</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Content Utama Scan QR -->
    <div class="content">
      <div class="page-header">
        <div>
          <h1>Scan QR Absensi</h1>
          <p>Scan QR yang ditampilkan guru untuk mencatat kehadiran.</p>
        </div>
        <div class="role-status">
          <div class="status-pill">
            <span class="dot"></span> Status Siswa: <strong>{{ $siswa->status ?? 'Aktif' }}</strong>
          </div>
          <div class="class-pill">{{ $siswa->kelas ?? 'Kelas 7A' }} • {{ $semester ?? 'Semester Ganjil' }}</div>
        </div>
      </div>

      <div class="scanner-container">
        <div class="scanner-card">
          <div class="scanner-header">
            <div class="status-indicator live" id="statusDot" style="background-color: #94a3b8; animation: none;"></div>
            <span id="statusText">Kamera Nonaktif</span>
          </div>
          
          <div class="camera-frame">
            <!-- Wadah stream video kamera -->
            <div id="reader" style="width: 100%; height: 100%;"></div>

            <!-- Tampilan awal sebelum kamera dinyalakan -->
            <div class="camera-placeholder" id="cameraPlaceholder">
              <div class="scan-line"></div>
              <div class="corner top-left"></div>
              <div class="corner top-right"></div>
              <div class="corner bottom-left"></div>
              <div class="corner bottom-right"></div>
              <p>Klik "Buka Kamera" untuk memulai</p>
            </div>
          </div>

          <div class="scanner-actions">
            <button class="btn-primary" onclick="startCamera()">Buka Kamera</button>
            <button class="btn-secondary" onclick="stopCamera()">Hentikan Scan</button>
          </div>
        </div>

        {{-- Kolom kanan: sesi saat ini, cara absensi, info --}}
        <div class="right-column">

          <div class="session-card">
            <div class="session-card-header">
              <span class="session-title"><span class="dot-title"></span> Sesi Saat Ini</span>
              <span class="session-status-badge">● {{ $sesi['status'] ?? 'Berlangsung' }}</span>
            </div>
            <div class="session-rows">
              <div class="session-row">
                <span class="row-label">Mata Pelajaran</span>
                <span class="row-value">{{ $sesi['mapel'] ?? 'Matematika' }}</span>
              </div>
              <div class="session-row">
                <span class="row-label">Kelas / Ruang</span>
                <span class="row-value">{{ $sesi['kelas_ruang'] ?? '7A (Ruang Kelas 03)' }}</span>
              </div>
              <div class="session-row">
                <span class="row-label">Jam Pelajaran</span>
                <span class="row-value">{{ $sesi['jam'] ?? '08.20 – 09.40 WIB' }}</span>
              </div>
              <div class="session-row">
                <span class="row-label">Guru Pengampu</span>
                <span class="row-value">{{ $sesi['guru'] ?? 'Ustadz Ahmad Fauzi, M.Pd.' }}</span>
              </div>
            </div>
          </div>

          <div class="steps-card">
            <div class="steps-card-header">
              <span class="steps-title"><span class="dot-title"></span> Cara Absensi</span>
              <p>Langkah mudah melakukan presensi kelas:</p>
            </div>
            <div class="steps-list">
              <div class="step-item">
                <div class="step-num">01</div>
                <div>
                  <div class="step-title">Guru menampilkan QR Code</div>
                  <div class="step-desc">Ditampilkan di proyektor kelas atau gawai guru.</div>
                </div>
              </div>
              <div class="step-item">
                <div class="step-num">02</div>
                <div>
                  <div class="step-title">Scan QR menggunakan perangkat</div>
                  <div class="step-desc">Arahkan kamera atau unggah tangkapan layar kode QR.</div>
                </div>
              </div>
              <div class="step-item">
                <div class="step-num">03</div>
                <div>
                  <div class="step-title">Absensi tercatat otomatis</div>
                  <div class="step-desc">Sistem memverifikasi kehadiran Anda secara real-time.</div>
                </div>
              </div>
            </div>
          </div>

          <div class="info-note-card">
            <span>ℹ️</span>
            <p><strong>Informasi:</strong> Presensi hanya dapat dicatat saat sesi berlangsung dan QR guru aktif. Anda tidak perlu memilih status manual.</p>
          </div>

        </div>
      </div>

    </div>
  </div>

  <!-- Library HTML5-QRCode via CDN -->
  <script src="https://unpkg.com/html5-qrcode"></script>

  <!-- Script Preloader & Sidebar -->
  <script>
    window.addEventListener('load', function () {
      setTimeout(function () {
        document.getElementById('pageLoader').classList.add('hide');
        document.getElementById('dashboardMurid').classList.add('loaded');
      }, 1000);
    });

    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('open');
      document.getElementById('sidebarOverlay').classList.toggle('show');
      document.getElementById('burgerBtn').classList.toggle('active');
    }
  </script>

  {{-- Jam & tanggal hidup, update tiap detik --}}
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

  <!-- Script Pengontrol Kamera -->
  <script>
    let html5QrCode = null;
    let isCameraRunning = false;

    function startCamera() {
      if (isCameraRunning) return;

      html5QrCode = new Html5Qrcode("reader");

      const config = {
        fps: 10,
        qrbox: { width: 220, height: 220 }
      };

      // Membuka kamera (mengutamakan kamera belakang HP / facingMode environment)
      html5QrCode.start(
        { facingMode: "environment" },
        config,
        onScanSuccess,
        onScanError
      ).then(() => {
        isCameraRunning = true;

        // Sembunyikan placeholder dan ubah indikator status jadi merah (live)
        document.getElementById('cameraPlaceholder').style.display = 'none';
        const dot = document.getElementById('statusDot');
        dot.style.backgroundColor = '#ef4444';
        dot.style.animation = 'pulseRed 1.5s infinite';
        document.getElementById('statusText').innerText = 'Kamera Aktif';
      }).catch(err => {
        alert("Gagal mengakses kamera. Pastikan browser diizinkan mengakses kamera: " + err);
      });
    }

    function stopCamera() {
      if (html5QrCode && isCameraRunning) {
        html5QrCode.stop().then(() => {
          isCameraRunning = false;
          
          // Kembalikan ke tampilan placeholder awal
          document.getElementById('cameraPlaceholder').style.display = 'flex';
          const dot = document.getElementById('statusDot');
          dot.style.backgroundColor = '#94a3b8';
          dot.style.animation = 'none';
          document.getElementById('statusText').innerText = 'Kamera Nonaktif';
          
          html5QrCode.clear();
        }).catch(err => {
          console.error("Gagal menghentikan kamera", err);
        });
      }
    }

    function onScanSuccess(decodedText, decodedResult) {
      // Sementara diletakkan alert dulu untuk ngetes kalau QR berhasil terbaca
      alert("QR Code Berhasil Terbaca!\nIsi QR: " + decodedText);
    }

    function onScanError(errorMessage) {
      // Mengabaikan error pemindaian per-frame saat kamera lagi mencari QR
    }
  </script>
</body>
</html>