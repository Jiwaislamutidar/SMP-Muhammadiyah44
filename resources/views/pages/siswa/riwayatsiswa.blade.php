<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#087443">
    <link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
    <title>Riwayat Absensi - SMP Muhammadiyah 44</title>
    <link rel="stylesheet" href="{{ asset('siswa css/riwayatsiswa.css') }}?v=6">
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
                <a href="{{ route('siswa.riwayat') }}" class="nav-item active" aria-current="page" style="--delay: 3">
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
                <form action="{{ route('siswa.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="nav-item logout-item">
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
                <button type="button" class="burger-btn" id="burgerBtn" onclick="toggleSidebar()" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>
                <img class="topbar-logo" src="{{ asset('logo.jpg') }}" alt="Logo SMP Muhammadiyah 44">
                <div class="portal-tag">Riwayat Presensi • SMP Muhammadiyah 44</div>
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
                <form method="GET" action="{{ route('siswa.riwayat') }}" style="display:contents;">
                    <div class="filter-block">
                        <label class="filter-label" id="periodeLabel" for="periode">Periode</label>
                        <div class="select-box filter-select" data-filter-select>
                            <select class="filter-native-select" id="periode" name="periode" aria-hidden="true" tabindex="-1">
                                <option value="">Semua Periode</option>
                                @foreach($periodeOptions as $periode => $label)
                                    <option value="{{ $periode }}" @selected($filters['periode'] === $periode)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button class="filter-select-trigger" type="button" aria-labelledby="periodeLabel" aria-haspopup="listbox" aria-expanded="false" aria-controls="periodeOptions">
                                <span class="filter-select-value"></span>
                            </button>
                            <div class="filter-options" id="periodeOptions" role="listbox" hidden></div>
                        </div>
                    </div>

                    <div class="filter-block">
                        <label class="filter-label" id="mapelLabel" for="mapel_id">Mata Pelajaran</label>
                        <div class="select-box filter-select" data-filter-select>
                            <select class="filter-native-select" id="mapel_id" name="mapel_id" aria-hidden="true" tabindex="-1">
                                <option value="">Semua Mata Pelajaran</option>
                                @foreach($mataPelajaran as $mapel)
                                    <option value="{{ $mapel->id }}" @selected($filters['mapel_id'] === $mapel->id)>{{ $mapel->nama_mapel }}</option>
                                @endforeach
                            </select>
                            <button class="filter-select-trigger" type="button" aria-labelledby="mapelLabel" aria-haspopup="listbox" aria-expanded="false" aria-controls="mapelOptions">
                                <span class="filter-select-value"></span>
                            </button>
                            <div class="filter-options" id="mapelOptions" role="listbox" hidden></div>
                        </div>
                    </div>

                    <div class="filter-block">
                        <label class="filter-label" id="statusLabel" for="status">Status Presensi</label>
                        <div class="select-box filter-select" data-filter-select>
                            <select class="filter-native-select" id="status" name="status" aria-hidden="true" tabindex="-1">
                                <option value="">Semua Status</option>
                                @foreach(['Hadir', 'Izin', 'Sakit', 'Alfa'] as $status)
                                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                            <button class="filter-select-trigger" type="button" aria-labelledby="statusLabel" aria-haspopup="listbox" aria-expanded="false" aria-controls="statusOptions">
                                <span class="filter-select-value"></span>
                            </button>
                            <div class="filter-options" id="statusOptions" role="listbox" hidden></div>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button class="btn-apply" type="submit">Terapkan</button>
                        <a class="btn-reset" href="{{ route('siswa.riwayat') }}" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">Reset</a>
                    </div>
                </form>
            </section>

            <section class="table-card">
                <div class="table-header">
                    <div class="table-title-wrap">
                        <h2>Riwayat Kehadiran</h2>
                        <span>({{ $riwayatPresensi->total() }} Catatan Presensi)</span>
                    </div>
                    <div class="table-actions">
                        <form method="GET" action="{{ route('siswa.riwayat.export') }}">
                            <input type="hidden" name="periode" value="{{ $filters['periode'] }}">
                            <input type="hidden" name="mapel_id" value="{{ $filters['mapel_id'] }}">
                            <input type="hidden" name="status" value="{{ $filters['status'] }}">
                            <button class="btn-export" type="submit">Unduh Rekap (PDF)</button>
                        </form>
                        <button class="btn-delete-history" type="button" id="openDeleteHistoryModal">Hapus Riwayat</button>
                    </div>
                </div>

                @if(session('success'))
                    <div class="history-alert history-alert-success" role="status">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="history-alert history-alert-error" role="alert">{{ session('error') }}</div>
                @endif

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
                                @php($sesi = $presensi->sesiPelajaran)
                                @php($jadwal = $sesi?->jadwal)
                                <tr>
                                    <td class="nowrap" data-label="Tanggal">{{ $sesi?->tanggal?->format('d M Y') ?? '-' }}</td>
                                    <td data-label="Hari">{{ $sesi?->tanggal?->locale('id')->translatedFormat('l') ?? '-' }}</td>
                                    <td data-label="Mata Pelajaran">{{ $jadwal?->mapel?->nama_mapel ?? '-' }}</td>
                                    <td class="nowrap" data-label="Jam">{{ $jadwal?->jam_mulai ? substr($jadwal->jam_mulai, 0, 5) : '-' }} - {{ $jadwal?->jam_selesai ? substr($jadwal->jam_selesai, 0, 5) : '-' }} WIB</td>
                                    <td data-label="Kelas">{{ $jadwal?->kelas?->nama_kelas ?? '-' }}</td>
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

        <div class="history-modal-backdrop" id="deleteHistoryModal" hidden>
            <section class="history-modal" role="dialog" aria-modal="true" aria-labelledby="deleteHistoryTitle" aria-describedby="deleteHistoryDescription">
                <div class="history-modal-icon" aria-hidden="true">!</div>
                <h2 id="deleteHistoryTitle">Hapus riwayat presensi?</h2>
                <p id="deleteHistoryDescription">Riwayat yang sesuai dengan filter aktif akan disembunyikan dari daftar dan rekap Anda. Data presensi tetap tersimpan untuk keperluan sekolah.</p>
                <form method="POST" action="{{ route('siswa.riwayat.destroy') }}" class="history-modal-form">
                    @csrf
                    <input type="hidden" name="periode" value="{{ $filters['periode'] }}">
                    <input type="hidden" name="mapel_id" value="{{ $filters['mapel_id'] }}">
                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                    <div class="history-modal-actions">
                        <button type="button" class="history-modal-cancel" id="cancelDeleteHistory">Batal</button>
                        <button type="submit" class="history-modal-confirm">Hapus Riwayat</button>
                    </div>
                </form>
            </section>
        </div>

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
        window.addEventListener('resize', function () { if (window.innerWidth > 1024) toggleSidebar(false); });

        (function () {
            var modal = document.getElementById('deleteHistoryModal');
            var openButton = document.getElementById('openDeleteHistoryModal');
            var cancelButton = document.getElementById('cancelDeleteHistory');

            function closeModal() {
                modal.hidden = true;
                openButton.focus();
            }

            openButton.addEventListener('click', function () {
                modal.hidden = false;
                cancelButton.focus();
            });
            cancelButton.addEventListener('click', closeModal);
            modal.addEventListener('click', function (event) {
                if (event.target === modal) closeModal();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.hidden) closeModal();
            });
        })();

        (function () {
            var selects = document.querySelectorAll('[data-filter-select]');

            function closeDropdown(dropdown, restoreFocus) {
                var trigger = dropdown.querySelector('.filter-select-trigger');
                window.clearTimeout(dropdown._closeTimer);
                dropdown.classList.remove('is-open');
                dropdown.classList.add('is-closing');
                trigger.setAttribute('aria-expanded', 'false');
                dropdown._closeTimer = window.setTimeout(function () {
                    dropdown.querySelector('.filter-options').hidden = true;
                    dropdown.classList.remove('is-closing');
                }, 150);
                if (restoreFocus) trigger.focus();
            }

            function updateDropdown(dropdown) {
                var select = dropdown.querySelector('.filter-native-select');
                var trigger = dropdown.querySelector('.filter-select-trigger');
                trigger.querySelector('.filter-select-value').textContent = select.options[select.selectedIndex].text;
                dropdown.querySelectorAll('[role="option"]').forEach(function (option) {
                    option.setAttribute('aria-selected', option.dataset.value === select.value ? 'true' : 'false');
                });
            }

            function openDropdown(dropdown, focusSelected) {
                var trigger = dropdown.querySelector('.filter-select-trigger');
                document.querySelectorAll('[data-filter-select].is-open').forEach(function (other) {
                    if (other !== dropdown) closeDropdown(other, false);
                });
                window.clearTimeout(dropdown._closeTimer);
                dropdown.classList.remove('is-closing');
                dropdown.classList.add('is-open');
                dropdown.querySelector('.filter-options').hidden = false;
                trigger.setAttribute('aria-expanded', 'true');
                if (focusSelected) {
                    var selected = dropdown.querySelector('[role="option"][aria-selected="true"]');
                    if (selected) selected.focus();
                }
            }

            selects.forEach(function (dropdown) {
                var select = dropdown.querySelector('.filter-native-select');
                var trigger = dropdown.querySelector('.filter-select-trigger');
                var options = dropdown.querySelector('.filter-options');

                Array.from(select.options).forEach(function (nativeOption) {
                    var option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'filter-option';
                    option.setAttribute('role', 'option');
                    option.dataset.value = nativeOption.value;
                    option.textContent = nativeOption.text;
                    option.addEventListener('click', function () {
                        select.value = option.dataset.value;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        updateDropdown(dropdown);
                        closeDropdown(dropdown, true);
                    });
                    options.appendChild(option);
                });

                trigger.addEventListener('click', function () {
                    if (trigger.getAttribute('aria-expanded') === 'true') {
                        closeDropdown(dropdown, false);
                    } else {
                        openDropdown(dropdown, true);
                    }
                });
                trigger.addEventListener('keydown', function (event) {
                    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        event.preventDefault();
                        openDropdown(dropdown, true);
                    }
                });
                options.addEventListener('keydown', function (event) {
                    var items = Array.from(options.querySelectorAll('[role="option"]'));
                    var index = items.indexOf(document.activeElement);
                    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        event.preventDefault();
                        var step = event.key === 'ArrowDown' ? 1 : -1;
                        items[(index + step + items.length) % items.length].focus();
                    } else if (event.key === 'Home' || event.key === 'End') {
                        event.preventDefault();
                        items[event.key === 'Home' ? 0 : items.length - 1].focus();
                    }
                });
                select.addEventListener('change', function () { updateDropdown(dropdown); });
                updateDropdown(dropdown);
            });

            document.addEventListener('click', function (event) {
                document.querySelectorAll('[data-filter-select].is-open').forEach(function (dropdown) {
                    if (!dropdown.contains(event.target)) closeDropdown(dropdown, false);
                });
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    document.querySelectorAll('[data-filter-select].is-open').forEach(function (dropdown) {
                        closeDropdown(dropdown, true);
                    });
                }
            });
        })();

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