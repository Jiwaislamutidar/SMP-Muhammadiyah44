<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
	<title>Jadwal Mengajar Guru - SMP Muhammadiyah 44</title>
	<link rel="stylesheet" href="{{ asset('guru css/jadwalngajar.css') }}?v=2">
	<link rel="stylesheet" href="{{ asset('guru css/portal-responsive.css') }}?v=1">
</head>
<body>
	<div class="page-loader" id="pageLoader">
		<div class="loader-logo-wrap"><div class="loader-spinner"></div><div class="loader-logo"><img src="{{ asset('logo.jpg') }}" alt="Logo sekolah"></div></div>
		<div class="loader-text">SMP Muhammadiyah 44</div>
		<div class="loader-subtext">Memuat jadwal mengajar...</div>
	</div>
	<div class="guru-page" id="guruPage">
		<aside class="sidebar" id="sidebar">
			<div class="sidebar-header"><div class="sidebar-brand"><div class="sidebar-logo"><img src="{{ asset('logo.jpg') }}" alt="Logo sekolah"></div><div><div class="sidebar-title">SMP Muhammadiyah 44</div><div class="sidebar-subtitle">Portal Guru</div></div></div><button class="sidebar-close-btn" onclick="toggleSidebar()" aria-label="Tutup sidebar">✕</button></div>
			<div class="sidebar-section-label">Menu</div>
			<nav class="sidebar-nav">
				<a href="{{ route('guru.dashboard') }}" class="nav-item"><span class="nav-icon-box">🏠</span>Dashboard</a>
				<a href="{{ route('guru.presensi-guru') }}" class="nav-item"><span class="nav-icon-box">🧾</span>Presensi Guru</a>
				<a href="{{ route('guru.jadwal') }}" class="nav-item active"><span class="nav-icon-box">📅</span>Jadwal Mengajar</a>
				<a href="{{ route('guru.presensi-murid') }}" class="nav-item"><span class="nav-icon-box">👥</span>Presensi Murid</a>
				<a href="{{ route('guru.riwayat-presensi') }}" class="nav-item"><span class="nav-icon-box">🕘</span>Riwayat Presensi</a>
				<a href="{{ route('guru.profil') }}" class="nav-item"><span class="nav-icon-box">👤</span>Profil</a>
			</nav>
			<div class="sidebar-footer"><div class="sidebar-footer-user"><div class="avatar">{{ mb_strtoupper(mb_substr($guru->nama_lengkap, 0, 1)) }}</div><div><div class="name">{{ $guru->nama_lengkap }}</div><div class="role">Guru</div></div></div><form action="{{ route('guru.logout') }}" method="POST">@csrf<button type="submit" class="nav-item logout-item"><span class="nav-icon-box">🚪</span>Keluar</button></form></div>
		</aside>
		<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
		<header class="topbar"><div class="topbar-left"><button class="burger-btn" id="burgerBtn" onclick="toggleSidebar()" aria-label="Buka sidebar"><span></span><span></span><span></span></button><div class="brand-wrap"><span class="brand-dot"></span><span class="portal-tag">Portal Guru • SMP Muhammadiyah 44</span></div></div><div class="topbar-right"><div class="date-pill" id="liveDatetime">Memuat waktu...</div><div class="profile-pill"><div class="avatar">{{ mb_strtoupper(mb_substr($guru->nama_lengkap, 0, 1)) }}</div><div class="profile-meta"><div class="name">{{ $guru->nama_lengkap }}</div><div class="role">Guru</div></div></div></div></header>
		<main class="content">
			<div class="page-header"><div><h1>Jadwal Mengajar</h1><p>{{ now()->locale('id')->translatedFormat('l, j F Y') }}</p></div><div class="term-chip"><span class="dot"></span>{{ $jadwals->count() }} jadwal hari ini</div></div>
			<section class="schedule-card">
				<div class="table-heading"><div><h2>Daftar Jadwal Mengajar</h2></div><span>{{ $guru->nama_lengkap }}</span></div>
				<div class="table-scroll"><table class="schedule-table">
					<thead><tr><th>Hari</th><th>Jam</th><th>Mata Pelajaran &amp; Ruang KBM</th><th>Kelas</th><th>Status</th><th>Aksi</th></tr></thead>
					<tbody>
						@forelse($jadwals as $jadwal)
							@php($sesiJadwal = $jadwal->sesiPelajarans->first())
							<tr class="{{ $sesiJadwal?->status_sesi === 'Berlangsung' ? 'active-row' : '' }}">
								<td>{{ $jadwal->hari }}</td><td class="time">{{ substr($jadwal->jam_mulai, 0, 5) }}-{{ substr($jadwal->jam_selesai, 0, 5) }}</td>
								<td><strong>{{ $jadwal->mapel->nama_mapel }}</strong><small>Ruang KBM: {{ $jadwal->ruangan ?? '-' }}</small></td><td><span class="class-chip">{{ $jadwal->kelas->nama_kelas }}</span></td>
								<td><span class="status {{ $sesiJadwal?->status_sesi === 'Selesai' ? 'done' : ($sesiJadwal?->status_sesi === 'Berlangsung' ? 'ongoing' : 'upcoming') }}"><i></i>{{ $sesiJadwal?->status_sesi ?? 'Belum' }}</span></td>
								<td><a class="detail-btn" href="{{ route('guru.presensi-murid') }}">Kelola Presensi</a></td>
							</tr>
						@empty<tr><td colspan="6">Tidak ada jadwal mengajar untuk hari ini.</td></tr>@endforelse
					</tbody>
				</table></div>
				<div class="table-footer"><span>Menampilkan <strong>{{ $jadwals->count() }} sesi</strong> dari jadwal tersimpan.</span><span class="sync"><i></i>Data jadwal hari ini</span></div>
			</section>
			<footer class="footer"><span>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</span><span>© {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan</span></footer>
		</main>
	</div>
	<script>
		window.addEventListener('load', function () { setTimeout(function () { document.getElementById('pageLoader').classList.add('hide'); document.getElementById('guruPage').classList.add('loaded'); }, 500); });
		function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('show'); document.getElementById('burgerBtn').classList.toggle('active'); }
		function updateLiveDatetime() { const days=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu']; const months=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; const now=new Date(); const pad=value=>String(value).padStart(2,'0'); document.getElementById('liveDatetime').textContent=days[now.getDay()]+', '+now.getDate()+' '+months[now.getMonth()]+' '+now.getFullYear()+' | '+pad(now.getHours())+':'+pad(now.getMinutes())+':'+pad(now.getSeconds())+' WIB'; }
		updateLiveDatetime(); setInterval(updateLiveDatetime, 1000);
	</script>
</body>
</html>