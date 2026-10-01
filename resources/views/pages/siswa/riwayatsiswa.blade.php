<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#087443">
    <link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
    <title>Riwayat Absensi - SMP Muhammadiyah 44</title>
    <link rel="stylesheet" href="{{ asset('siswa css/riwayatsiswa.css') }}?v=3">
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
        <aside class="sidebar" id="sidebar" aria-label="Menu utama">
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
                <button type="button" class="sidebar-close-btn" onclick="toggleSidebar()" aria-label="Tutup menu">✕</button>
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
                        <div class="role">{{ $siswa->kelas ?? '-' }} • Murid</div>
                    </div>
                </div>
                <form action="{{ route('siswa.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="nav-item logout-item">
                        <span class="nav-icon-box">🚪</span> Keluar
                    </button>
                </form>
            </div>
        </aside>

        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="burger-btn" id="burgerBtn" onclick="toggleSidebar()" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>
                <div class="portal-tag">Portal Murid • SMP Muhammadiyah 44</div>
            </div>

            <div class="topbar-right">
                <div class="ta-badge" id="liveDatetime">Memuat waktu...</div>
                <div class="icon-btn" aria-label="Notifikasi">
                    🔔
                    <span class="badge-dot"></span>
                </div>
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
            <div class="section-header">
                <div class="header-copy">
                    <h1>Riwayat Absensi</h1>
                    <p>Lihat riwayat kehadiran kamu.</p>
                </div>
                <div class="header-status">
                    <div class="status-pill">
                        <span class="dot"></span>
                        <span>Status Siswa: {{ $siswa->status }}</span>
                    </div>
                    <div class="class-pill">{{ $siswa->kelas }} • {{ $semesterInfo }}</div>
                </div>
            </div>

            <section class="summary-card">
                <div class="metric-list">
                    <div class="metric hadirt">
                        <div class="icon hadirt-icon">✓</div>
                        <div class="metric-text">
                            <div class="value-row">
                                <span class="metric-number">{{ $ringkasan['hadir'] }}</span>
                                <span class="metric-name c-hadir">Hadir</span>
                            </div>
                            <div class="metric-sub">Presensi Terverifikasi</div>
                        </div>
                    </div>

                    <div class="metric izin">
                        <div class="icon izin-icon">!</div>
                        <div class="metric-text">
                            <div class="value-row">
                                <span class="metric-number">{{ $ringkasan['izin'] }}</span>
                                <span class="metric-name c-izin">Izin</span>
                            </div>
                            <div class="metric-sub">Dengan Surat Keterangan</div>
                        </div>
                    </div>

                    <div class="metric alfa">
                        <div class="icon alfa-icon">×</div>
                        <div class="metric-text">
                            <div class="value-row">
                                <span class="metric-number">{{ $ringkasan['alfa'] }}</span>
                                <span class="metric-name c-alfa">Alfa</span>
                            </div>
                            <div class="metric-sub">Tanpa Keterangan</div>
                        </div>
                    </div>

                    <div class="term-tag">{{ $semesterInfo }}</div>
                </div>
            </section>

            <section class="filter-bar">
                <div class="filter-block">
                    <label class="filter-label">Periode</label>
                    <div class="select-box">
                        <span>{{ now()->locale('id')->translatedFormat('F Y') }}</span>
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
                        <span>({{ $riwayatPresensi->total() }} Catatan Presensi)</span>
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
                            @forelse($riwayatPresensi as $presensi)
                                @php($jadwal = $presensi->sesiPelajaran->jadwal)
                                <tr>
                                    <td class="nowrap" data-label="Tanggal">{{ $presensi->sesiPelajaran->tanggal->format('d M Y') }}</td>
                                    <td data-label="Hari">{{ $presensi->sesiPelajaran->tanggal->locale('id')->translatedFormat('l') }}</td>
                                    <td data-label="Mata Pelajaran">{{ $jadwal->mapel->nama_mapel }}</td>
                                    <td class="nowrap" data-label="Jam">{{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }} WIB</td>
                                    <td data-label="Kelas">{{ $jadwal->kelas->nama_kelas }}</td>
                                    <td data-label="Status"><span class="badge badge-{{ str_replace(' ', '-', strtolower($presensi->status)) }}">{{ $presensi->status }}</span></td>
                                </tr>
                            @empty
                                <tr><td class="empty" colspan="6">Belum ada catatan presensi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <div class="pagination-info">Menampilkan {{ $riwayatPresensi->firstItem() ?? 0 }}-{{ $riwayatPresensi->lastItem() ?? 0 }} dari {{ $riwayatPresensi->total() }} data</div>
                    {{ $riwayatPresensi->links() }}
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
        function showPage() {
            document.getElementById('pageLoader').classList.add('hide');
            document.getElementById('riwayatMurid').classList.add('loaded');
        }
        window.addEventListener('load', function () { setTimeout(showPage, 700); });
        setTimeout(showPage, 4000);

        function toggleSidebar(force) {
            var sidebar = document.getElementById('sidebar');
            var open = typeof force === 'boolean' ? force : !sidebar.classList.contains('open');
            sidebar.classList.toggle('open', open);
            document.getElementById('sidebarOverlay').classList.toggle('show', open);
            var burger = document.getElementById('burgerBtn');
            burger.classList.toggle('active', open);
            burger.setAttribute('aria-expanded', open);
            document.body.classList.toggle('no-scroll', open);
        }

        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') toggleSidebar(false); });
        window.addEventListener('resize', function () { if (window.innerWidth > 1024) toggleSidebar(false); });

        function updateLiveDatetime() {
            var hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            var bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            var n = new Date();
            var p = function (v) { return String(v).padStart(2, '0'); };
            document.getElementById('liveDatetime').innerHTML =
                '<span class="dt-date">' + hari[n.getDay()] + ', ' + n.getDate() + ' ' + bulan[n.getMonth()] + ' ' + n.getFullYear() + '</span>' +
                '<span class="dt-sep"> | </span>' +
                '<span class="dt-time">' + p(n.getHours()) + ':' + p(n.getMinutes()) + ':' + p(n.getSeconds()) + ' WIB</span>';
        }
        updateLiveDatetime();
        setInterval(updateLiveDatetime, 1000);
    </script>
</body>
</html>