<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Absensi - SMP Muhammadiyah 44</title>
    <link rel="stylesheet" href="{{ asset('siswa css/riwayatsiswa.css') }}?v=2">
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
        <div class="loader-subtext">Memuat riwayat absensi...</div>
    </div>

    <div class="riwayat-murid" id="riwayatMurid">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <div class="sidebar-logo">44</div>
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
                <a href="{{ route('siswa.riwayat') }}" class="nav-item active" style="--delay: 3">
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
                    <h1>Riwayat Absensi</h1>
                    <p>Lihat riwayat kehadiran kamu.</p>
                </div>
                <div class="header-status">
                    <div class="status-pill">
                        <span class="dot"></span>
                        <span>Status Siswa: Aktif</span>
                    </div>
                    <div class="class-pill">Kelas 7A • Semester Ganjil</div>
                </div>
            </div>

            <section class="summary-card">
                <div class="metric-list">
                    <div class="metric hadirt">
                        <div class="icon hadirt-icon">✓</div>
                        <div class="metric-text">
                            <div class="value-row">
                                <span class="metric-number">18</span>
                                <span class="metric-name" style="color:#087443;">Hadir</span>
                            </div>
                            <div class="metric-sub">Presensi Terverifikasi</div>
                        </div>
                    </div>

                    <div class="metric izin">
                        <div class="icon izin-icon">!</div>
                        <div class="metric-text">
                            <div class="value-row">
                                <span class="metric-number">2</span>
                                <span class="metric-name" style="color:#b45309;">Izin</span>
                            </div>
                            <div class="metric-sub">Dengan Surat Keterangan</div>
                        </div>
                    </div>

                    <div class="metric alfa">
                        <div class="icon alfa-icon">×</div>
                        <div class="metric-text">
                            <div class="value-row">
                                <span class="metric-number">1</span>
                                <span class="metric-name" style="color:#dc2626;">Alfa</span>
                            </div>
                            <div class="metric-sub">Tanpa Keterangan</div>
                        </div>
                    </div>

                    <div class="term-tag">Semester Ganjil 2026</div>
                </div>
            </section>

            <section class="filter-bar">
                <div class="filter-block">
                    <label class="filter-label">Periode</label>
                    <div class="select-box">
                        <span>September 2026</span>
                        <span class="chev">▾</span>
                    </div>
                </div>

                <div class="filter-block">
                    <label class="filter-label">Mata Pelajaran</label>
                    <div class="select-box">
                        <span>Semua Mata Pelajaran</span>
                        <span class="chev">▾</span>
                    </div>
                </div>

                <div class="filter-block">
                    <label class="filter-label">Status Presensi</label>
                    <div class="select-box">
                        <span>Semua Status</span>
                        <span class="chev">▾</span>
                    </div>
                </div>

                <div class="filter-actions">
                    <button class="btn-apply" type="button">Terapkan</button>
                    <button class="btn-reset" type="button">Reset</button>
                </div>
            </section>

            <section class="table-card">
                <div class="table-header">
                    <div class="table-title-wrap">
                        <h2>Riwayat Kehadiran</h2>
                        <span>(21 Catatan Presensi)</span>
                    </div>
                    <button class="btn-export" type="button">Unduh Rekap (PDF)</button>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Hari</th>
                                <th>Mata Pelajaran</th>
                                <th>Jam</th>
                                <th>Kelas</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>08 Sep 2026</td>
                                <td>Senin</td>
                                <td>Matematika</td>
                                <td>08.20 – 09.40 WIB</td>
                                <td>7A</td>
                                <td><span class="badge badge-hadir">Hadir</span></td>
                            </tr>
                            <tr>
                                <td>07 Sep 2026</td>
                                <td>Minggu</td>
                                <td>IPA Terpadu</td>
                                <td>08.20 – 09.40 WIB</td>
                                <td>7A</td>
                                <td><span class="badge badge-hadir">Hadir</span></td>
                            </tr>
                            <tr>
                                <td>06 Sep 2026</td>
                                <td>Sabtu</td>
                                <td>Bahasa Indonesia</td>
                                <td>10.00 – 11.20 WIB</td>
                                <td>7A</td>
                                <td><span class="badge badge-izin">Izin</span></td>
                            </tr>
                            <tr>
                                <td>05 Sep 2026</td>
                                <td>Jumat</td>
                                <td>Matematika</td>
                                <td>07.00 – 08.20 WIB</td>
                                <td>7A</td>
                                <td><span class="badge badge-hadir">Hadir</span></td>
                            </tr>
                            <tr>
                                <td>04 Sep 2026</td>
                                <td>Kamis</td>
                                <td>IPA Terpadu</td>
                                <td>08.20 – 09.40 WIB</td>
                                <td>7A</td>
                                <td><span class="badge badge-alfa">Alfa</span></td>
                            </tr>
                            <tr>
                                <td>03 Sep 2026</td>
                                <td>Rabu</td>
                                <td>Bahasa Inggris</td>
                                <td>08.20 – 09.40 WIB</td>
                                <td>7A</td>
                                <td><span class="badge badge-hadir">Hadir</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <div class="pagination-info">Menampilkan 1–6 dari 21 data</div>
                    <div class="pagination">
                        <button type="button" class="page-btn nav-btn" aria-label="Halaman sebelumnya">‹</button>
                        <button type="button" class="page-btn active">1</button>
                        <button type="button" class="page-btn">2</button>
                        <button type="button" class="page-btn">3</button>
                        <button type="button" class="page-btn nav-btn" aria-label="Halaman berikutnya">›</button>
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div class="footer-left">
                <span class="footer-dot"></span>
                <span>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</span>
            </div>
            <span>© 2026 SMP Muhammadiyah 44 Tangerang Selatan. Seluruh hak cipta dilindungi.</span>
        </footer>
    </div>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                document.getElementById('pageLoader').classList.add('hide');
                document.getElementById('riwayatMurid').classList.add('loaded');
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
            document.getElementById('liveDatetime').textContent =
                `${hari}, ${tanggal} ${bulan} ${tahun} | ${jam}:${menit}:${detik} WIB`;
        }
        updateLiveDatetime();
        setInterval(updateLiveDatetime, 1000);
    </script>
</body>
</html>