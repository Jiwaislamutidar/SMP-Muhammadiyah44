<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
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
                                <span class="metric-name" style="color:#087443;">Hadir</span>
                            </div>
                            <div class="metric-sub">Presensi Terverifikasi</div>
                        </div>
                    </div>

                    <div class="metric izin">
                        <div class="icon izin-icon">!</div>
                        <div class="metric-text">
                            <div class="value-row">
                                <span class="metric-number">{{ $ringkasan['izin'] }}</span>
                                <span class="metric-name" style="color:#b45309;">Izin</span>
                            </div>
                            <div class="metric-sub">Dengan Surat Keterangan</div>
                        </div>
                    </div>

                    <div class="metric alfa">
                        <div class="icon alfa-icon">×</div>
                        <div class="metric-text">
                            <div class="value-row">
                                <span class="metric-number">{{ $ringkasan['alfa'] }}</span>
                                <span class="metric-name" style="color:#dc2626;">Alfa</span>
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
                                    <td>{{ $presensi->sesiPelajaran->tanggal->format('d M Y') }}</td>
                                    <td>{{ $presensi->sesiPelajaran->tanggal->locale('id')->translatedFormat('l') }}</td>
                                    <td>{{ $jadwal->mapel->nama_mapel }}</td>
                                    <td>{{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }} WIB</td>
                                    <td>{{ $jadwal->kelas->nama_kelas }}</td>
                                    <td><span class="badge badge-{{ str_replace(' ', '-', strtolower($presensi->status)) }}">{{ $presensi->status }}</span></td>
                                </tr>
                            @empty<tr><td colspan="6">Belum ada catatan presensi.</td></tr>@endforelse
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