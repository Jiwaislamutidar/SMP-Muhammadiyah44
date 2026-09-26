<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
    <title>Profil Murid - SMP Muhammadiyah 44</title>
    <link rel="stylesheet" href="{{ asset('siswa css/profilsiswa.css') }}?v=1">
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
        <div class="loader-subtext">Memuat profil...</div>
    </div>

    <div class="profil-murid" id="profilMurid">
        <aside class="sidebar" id="sidebar">
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
                <a href="{{ route('siswa.scan-qr') }}" class="nav-item" style="--delay: 2">
                    <span class="nav-icon-box">📷</span> Scan QR
                </a>
                <a href="{{ route('siswa.riwayat') }}" class="nav-item" style="--delay: 3">
                    <span class="nav-icon-box">🕘</span> Riwayat Absensi
                </a>
                <a href="{{ route('siswa.profil') }}" class="nav-item active" style="--delay: 4">
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
        </aside>

        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

        <header class="topbar">
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
        </header>

        <main class="content">
            <div class="section-header">
                <div class="header-copy">
                    <h1>Profil Murid</h1>
                    <p>Informasi profil dan akun kamu</p>
                </div>
                <div class="status-badge">
                    <span class="dot"></span> Data Terverifikasi Sistem
                </div>
            </div>

            @if(session('success'))
                <div class="profile-alert success" role="status">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="profile-alert error" role="alert">{{ $errors->first() }}</div>
            @endif

            <section class="profile-card">
                <div class="profile-top">
                    <div class="profile-top-left">
                        <div class="profile-avatar-lg">{{ $siswa->inisial ?? 'AF' }}</div>
                        <div class="profile-top-info">
                            <div class="profile-name-row">
                                <h2>{{ $siswa->nama ?? 'Ahmad Fauzan' }}</h2>
                                <span class="active-pill">{{ $siswa->status ?? 'Aktif' }}</span>
                            </div>
                            <div class="profile-meta-row">
                                <span><span class="meta-label">NISN:</span> {{ $siswa->nisn ?? '00654321' }}</span>
                                <span class="meta-dot">•</span>
                                <span><span class="meta-label">Kelas:</span> {{ $siswa->kelas ?? '7A' }}</span>
                                <span class="meta-dot">•</span>
                                <span class="meta-school">{{ $siswa->sekolah ?? 'SMP Muhammadiyah 44 Tangerang Selatan' }}</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-outline" id="openPasswordModal">🔒 Ubah Kata Sandi</button>
                </div>

                <div class="account-info-section">
                    <div class="section-label">Informasi Akun</div>
                    <div class="info-grid">
                        <div class="info-box">
                            <div class="info-label">Username</div>
                            <div class="info-value">👤 {{ $siswa->username ?? 'ahmadfauzan' }}</div>
                        </div>
                        <div class="info-box">
                            <div class="info-label">Role / Peran</div>
                            <div class="info-value">🎓 Murid Aktif</div>
                        </div>
                        <div class="info-box">
                            <div class="info-label">Status Akun</div>
                            <div class="info-value">✅ Terverifikasi Sistem</div>
                        </div>
                        <div class="info-box">
                            <div class="info-label">Tahun Ajaran</div>
                            <div class="info-value">📅 {{ $tahunAjaran ?? '2026/2027 Ganjil' }}</div>
                        </div>
                    </div>
                </div>

                <div class="profile-footer-note">
                    <div class="footer-note-left">
                        ℹ️ <span>Data profil dan akun terdaftar resmi pada pangkalan data SMP Muhammadiyah 44 Tangerang Selatan.</span>
                    </div>
                </div>
            </section>
        </main>

        <div class="password-modal-backdrop" id="passwordModal" @if(!$errors->any()) hidden @endif>
            <section class="password-modal" role="dialog" aria-modal="true" aria-labelledby="passwordModalTitle">
                <div class="password-modal-heading">
                    <div>
                        <h2 id="passwordModalTitle">Ubah Kata Sandi</h2>
                        <p>Masukkan kata sandi saat ini dan kata sandi baru.</p>
                    </div>
                    <button type="button" class="password-modal-close" id="closePasswordModal" aria-label="Tutup">&times;</button>
                </div>
                <form action="{{ route('siswa.profil.update-password') }}" method="POST" class="password-form">
                    @csrf
                    @method('PUT')

                    <div class="password-form-field">
                        <label for="current_password">Kata sandi saat ini</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                        @error('current_password')<span class="password-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="password-form-field">
                        <label for="password">Kata sandi baru</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" minlength="6" required>
                        @error('password')<span class="password-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="password-form-field">
                        <label for="password_confirmation">Konfirmasi kata sandi baru</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="6" required>
                    </div>
                    <div class="password-modal-actions">
                        <button type="button" class="password-modal-cancel" id="cancelPasswordModal">Batal</button>
                        <button type="submit" class="password-modal-submit">Simpan Kata Sandi</button>
                    </div>
                </form>
            </section>
        </div>

        <footer class="footer">
            <div class="footer-left">
                <span>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</span>
            </div>
            <span>© {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan. Seluruh hak cipta dilindungi.</span>
        </footer>
    </div>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                document.getElementById('pageLoader').classList.add('hide');
                document.getElementById('profilMurid').classList.add('loaded');
            }, 1000);
        });

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('show');
            document.getElementById('burgerBtn').classList.toggle('active');
        }

        const passwordModal = document.getElementById('passwordModal');
        const openPasswordModal = document.getElementById('openPasswordModal');
        const closePasswordModal = document.getElementById('closePasswordModal');
        const cancelPasswordModal = document.getElementById('cancelPasswordModal');

        function closePasswordDialog() {
            passwordModal.hidden = true;
            openPasswordModal.focus();
        }

        openPasswordModal.addEventListener('click', function () {
            passwordModal.hidden = false;
            document.getElementById('current_password').focus();
        });
        closePasswordModal.addEventListener('click', closePasswordDialog);
        cancelPasswordModal.addEventListener('click', closePasswordDialog);
        passwordModal.addEventListener('click', function (event) {
            if (event.target === passwordModal) closePasswordDialog();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !passwordModal.hidden) closePasswordDialog();
        });
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
</body>
</html>