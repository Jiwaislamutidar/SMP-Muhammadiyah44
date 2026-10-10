<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
  <title>Scan QR Presensi - SMP Muhammadiyah 44</title>
  <link rel="stylesheet" href="{{ asset('siswa css/scanqrsiswa.css') }}?v=4">
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
    <div class="loader-subtext">Membuka kamera scanner...</div>
  </div>

  <div class="dashboard-murid" id="dashboardMurid">

    <div class="sidebar" id="sidebar">
      <nav class="sidebar-nav" aria-label="Menu utama">
        <a href="{{ route('siswa.dashboard') }}" class="nav-item" style="--delay: 1">
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
        <a href="{{ route('siswa.scan-qr') }}" class="nav-item active" style="--delay: 2">
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
            <div class="name">{{ $siswa->nama ?? auth()->user()->name }}</div>
            <div class="role">{{ $siswa->kelas ?? '-' }} • Murid</div>
          </div>
        </div>
        <form action="{{ route('siswa.logout') }}" method="POST" style="margin:0;">
          @csrf
          <button type="submit" class="nav-item logout-item" style="width:100%; border:none; background:transparent; text-align:left; cursor:pointer;">
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
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="topbar">
      <div class="topbar-left">
        <button class="burger-btn" id="burgerBtn" onclick="toggleSidebar()" aria-label="Buka menu" aria-expanded="false" aria-controls="sidebar">
          <span></span><span></span><span></span>
        </button>
        <img class="topbar-logo" src="{{ asset('logo.jpg') }}" alt="Logo SMP Muhammadiyah 44">
        <div class="portal-tag">Scan QR • SMP Muhammadiyah 44</div>
      </div>
      <div class="topbar-right">
        <div class="ta-badge" id="liveDatetime">Memuat waktu...</div>
        <details style="position:relative;">
          <summary class="icon-btn" aria-label="Notifikasi" title="Notifikasi" style="list-style:none;cursor:pointer;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 8a6 6 0 0 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/>
              <path d="M10.3 21a2 2 0 0 0 3.4 0"/>
            </svg>
            @if(count($notifikasiPresensi ?? []))
              <span class="badge-dot"></span>
            @endif
          </summary>
          <div style="position:absolute;z-index:20;right:0;top:calc(100% + 10px);width:290px;max-width:80vw;padding:14px;background:#fff;border:1px solid #e4eae6;border-radius:12px;box-shadow:0 12px 30px rgba(0,0,0,.14);">
            <strong style="display:block;margin-bottom:10px;">Presensi Terbaru</strong>
            @forelse($notifikasiPresensi ?? [] as $notifikasi)
              <div style="padding:9px 0;border-top:1px solid #edf1ee;font-size:12px;line-height:1.5;">
                Presensi {{ $notifikasi['status'] }} untuk mata pelajaran {{ $notifikasi['mapel'] }} pada {{ $notifikasi['waktu'] }} ({{ $notifikasi['tanggal'] }})
              </div>
            @empty
              <div style="padding-top:8px;font-size:12px;color:#64748b;">Belum ada riwayat presensi.</div>
            @endforelse
          </div>
        </details>
        <div class="user-mini">
          <div class="avatar">{{ $siswa->inisial ?? 'AF' }}</div>
          <div>
            <div class="name">{{ $siswa->nama ?? auth()->user()->name }}</div>
            <div class="role">{{ $siswa->kelas ?? '-' }} • Murid</div>
          </div>
        </div>
      </div>
    </div>

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
          <div class="class-pill">{{ $siswa->kelas ?? '-' }} • {{ $semester ?? '-' }}</div>
        </div>
      </div>

      <div class="scanner-container">
        <div class="scanner-card">
          <div class="scanner-header">
            <div class="status-indicator live" id="statusDot" style="background-color: #94a3b8; animation: none;"></div>
            <span id="statusText">Kamera Nonaktif</span>
          </div>
          
          <div class="camera-frame">
            <div id="reader" style="width: 100%; height: 100%;"></div>

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
            <button class="btn-primary" id="startCameraButton" onclick="startCamera()">Buka Kamera</button>
            <button class="btn-secondary" onclick="stopCamera()">Hentikan Scan</button>
          </div>
          <div class="scan-feedback" id="scanFeedback" role="status" aria-live="assertive" hidden></div>
        </div>

        <div class="right-column">

          <div class="session-card">
            <div class="session-card-header">
              <span class="session-title"><span class="dot-title"></span> Sesi Saat Ini</span>
              <span class="session-status-badge">● {{ $sesi['status'] ?? 'Tidak ada sesi aktif' }}</span>
            </div>
            <div class="session-rows">
              <div class="session-row">
                <span class="row-label">Mata Pelajaran</span>
                <span class="row-value">{{ $sesi['mapel'] ?? '-' }}</span>
              </div>
              <div class="session-row">
                <span class="row-label">Kelas / Ruang</span>
                <span class="row-value">{{ $sesi['kelas_ruang'] ?? '-' }}</span>
              </div>
              <div class="session-row">
                <span class="row-label">Jam Pelajaran</span>
                <span class="row-value">{{ $sesi['jam'] ?? '-' }}</span>
              </div>
              <div class="session-row">
                <span class="row-label">Guru Pengampu</span>
                <span class="row-value">{{ $sesi['guru'] ?? '-' }}</span>
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

      <dialog class="scan-success-modal" id="scanSuccessModal" aria-labelledby="scanSuccessTitle" aria-describedby="scanSuccessMessage">
        <div class="success-icon" aria-hidden="true">✓</div>
        <h2 id="scanSuccessTitle">Presensi Berhasil Ditambahkan!</h2>
        <p id="scanSuccessMessage">Presensi Anda berhasil dicatat.</p>
        <button class="success-modal-button" type="button" onclick="closeSuccessModal()" autofocus>Tutup</button>
      </dialog>

    </div>
  </div>

  <script src="https://unpkg.com/html5-qrcode"></script>

  <script>
    window.addEventListener('load', function () {
      setTimeout(function () {
        document.getElementById('pageLoader').classList.add('hide');
        document.getElementById('dashboardMurid').classList.add('loaded');
      }, 1000);
    });

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
  </script>

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

  <script>
    let html5QrCode = null;
    let isCameraRunning = false;
    let scanPending = false;
    let lastScannedText = '';

    function startCamera() {
      if (isCameraRunning || scanPending) return;
      lastScannedText = '';
      const feedback = document.getElementById('scanFeedback');
      feedback.hidden = true;
      feedback.textContent = '';

      html5QrCode = new Html5Qrcode("reader");

      const config = {
        fps: 10,
        qrbox: { width: 220, height: 220 }
      };

      html5QrCode.start(
        { facingMode: "environment" },
        config,
        onScanSuccess,
        onScanError
      ).then(() => {
        isCameraRunning = true;
        const startButton = document.getElementById('startCameraButton');
        startButton.disabled = true;
        startButton.textContent = 'Kamera Aktif';

        document.getElementById('cameraPlaceholder').style.display = 'none';
        const dot = document.getElementById('statusDot');
        dot.style.backgroundColor = '#ef4444';
        dot.style.animation = 'pulseRed 1.5s infinite';
        document.getElementById('statusText').innerText = 'Kamera Aktif';
      }).catch(err => {
        alert("Gagal mengakses kamera. Pastikan browser diizinkan mengakses kamera: " + err);
      });
    }

    async function stopCamera() {
      if (!html5QrCode) return;

      const scanner = html5QrCode;
      try {
        if (isCameraRunning) await scanner.stop();
      } catch (error) {
        console.error('Gagal menghentikan kamera melalui HTML5-QRCode.', error);
      } finally {
        const video = document.querySelector('#reader video');
        video?.srcObject?.getTracks().forEach(track => track.stop());

        try {
          scanner.clear();
        } catch (error) {
          console.error('Gagal membersihkan elemen scanner.', error);
        }

        isCameraRunning = false;
        html5QrCode = null;
        document.getElementById('cameraPlaceholder').style.display = 'flex';
        const dot = document.getElementById('statusDot');
        dot.style.backgroundColor = '#94a3b8';
        dot.style.animation = 'none';
        document.getElementById('statusText').innerText = 'Kamera Nonaktif';

        const startButton = document.getElementById('startCameraButton');
        startButton.disabled = scanPending;
        startButton.textContent = scanPending ? 'Memproses...' : 'Buka Kamera';
      }
    }

    async function onScanSuccess(decodedText) {
      if (scanPending || decodedText === lastScannedText) return;
      scanPending = true;
      lastScannedText = decodedText;
      const startButton = document.getElementById('startCameraButton');
      startButton.disabled = true;
      startButton.textContent = 'Memproses...';
      try {
        await stopCamera();
        const response = await fetch(@json(route('siswa.scan')), {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ token: decodedText })
        });
        const result = await response.json();
        if (!response.ok) {
          const message = response.status === 422
            ? 'QR Code sudah kadaluarsa. Minta Guru untuk klik Perbarui QR.'
            : (result.message || 'QR Code tidak dapat digunakan. Pastikan QR sesuai dengan kelas Anda.');
          showScanFeedback('error', message);
          return;
        }
        showSuccessModal(result.message || 'Presensi berhasil dicatat.');
        playSuccessFeedback();
      } catch (error) {
        lastScannedText = '';
        showScanFeedback('error', 'Koneksi bermasalah. Periksa internet lalu coba scan kembali.');
      } finally {
        scanPending = false;
        startButton.disabled = isCameraRunning;
        startButton.textContent = isCameraRunning ? 'Kamera Aktif' : 'Buka Kamera';
      }
    }

    function showScanFeedback(type, message) {
      const feedback = document.getElementById('scanFeedback');
      feedback.className = `scan-feedback ${type === 'success' ? 'is-success' : 'is-error'}`;
      feedback.textContent = message;
      feedback.hidden = false;
    }

    function showSuccessModal(message) {
      document.getElementById('scanSuccessMessage').textContent = message;
      document.getElementById('scanSuccessModal').showModal();
    }

    function closeSuccessModal() {
      document.getElementById('scanSuccessModal').close();
    }

    function playSuccessFeedback() {
      try {
        if (navigator.vibrate) navigator.vibrate([120, 60, 120]);
      } catch (error) {
        console.debug('Getaran feedback tidak tersedia.', error);
      }
      try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) return;
        const context = new AudioContextClass();
        const oscillator = context.createOscillator();
        const gain = context.createGain();
        oscillator.frequency.value = 880;
        oscillator.type = 'sine';
        gain.gain.value = 0.12;
        oscillator.connect(gain);
        gain.connect(context.destination);
        oscillator.start();
        oscillator.stop(context.currentTime + 0.14);
        oscillator.onended = () => context.close();
      } catch (error) {
        console.debug('Audio feedback tidak tersedia.', error);
      }
    }

    function onScanError(errorMessage) {

    }
  </script>
</body>
</html>