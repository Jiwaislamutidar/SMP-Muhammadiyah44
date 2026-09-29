<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
	<title>Riwayat Presensi Guru - SMP Muhammadiyah 44</title>
	<link rel="stylesheet" href="{{ asset('guru css/riwayatpresensi.css') }}?v=1">
</head>
<body>
<div class="page-loader" id="pageLoader"><div class="loader-logo-wrap"><div class="loader-spinner"></div><div class="loader-logo"><img src="{{ asset('logo.jpg') }}" alt="Logo sekolah"></div></div><div class="loader-text">SMP Muhammadiyah 44</div><div class="loader-subtext">Memuat riwayat presensi...</div></div>
<div class="guru-page" id="guruPage">
	<aside class="sidebar" id="sidebar"><div class="sidebar-header"><div class="sidebar-brand"><div class="sidebar-logo"><img src="{{ asset('logo.jpg') }}" alt="Logo sekolah"></div><div><div class="sidebar-title">SMP Muhammadiyah 44</div><div class="sidebar-subtitle">Portal Guru</div></div></div><button class="sidebar-close-btn" onclick="toggleSidebar()">✕</button></div><div class="sidebar-section-label">Menu</div><nav class="sidebar-nav"><a class="nav-item" href="{{ route('guru.dashboard') }}">Dashboard</a><a class="nav-item" href="{{ route('guru.presensi-guru') }}">Presensi Guru</a><a class="nav-item" href="{{ route('guru.jadwal') }}">Jadwal Mengajar</a><a class="nav-item" href="{{ route('guru.presensi-murid') }}">Presensi Murid</a><a class="nav-item active" href="{{ route('guru.riwayat-presensi') }}">Riwayat Presensi</a><a class="nav-item" href="{{ route('guru.profil') }}">Profil</a></nav><div class="sidebar-footer"><div class="sidebar-footer-user"><div class="avatar">{{ mb_strtoupper(mb_substr($guru->nama_lengkap, 0, 1)) }}</div><div><div class="name">{{ $guru->nama_lengkap }}</div><div class="role">Guru</div></div></div><form action="{{ route('guru.logout') }}" method="POST">@csrf<button class="nav-item logout-item" type="submit">Keluar</button></form></div></aside>
	<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
	<header class="topbar"><div class="topbar-left"><button class="burger-btn" id="burgerBtn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="brand-wrap"><span class="brand-dot"></span><span class="portal-tag">Portal Guru • SMP Muhammadiyah 44</span></div></div><div class="topbar-right"><div class="date-pill" id="liveDatetime">Memuat waktu...</div><div class="profile-pill"><div class="avatar">{{ mb_strtoupper(mb_substr($guru->nama_lengkap, 0, 1)) }}</div><div class="profile-meta"><div class="name">{{ $guru->nama_lengkap }}</div><div class="role">Guru</div></div></div></div></header>
	<main class="content">
		<div class="page-header"><div><h1>Riwayat Presensi</h1><p>Log kehadiran sekolah dan sesi mengajar Anda.</p></div><span class="term-chip">{{ $aktivitas->count() }} aktivitas terbaru</span></div>
		<section class="summary-grid"><div class="summary-card"><div><span class="label">Hari Hadir Sekolah</span><strong>{{ $totalHariHadir }}</strong><small>Data presensi harian</small></div></div><div class="summary-card"><div><span class="label">Total Sesi Mengajar</span><strong>{{ $totalSesi }}</strong><small>Jadwal dengan sesi</small></div></div><div class="summary-card"><div><span class="label">Sesi Selesai</span><strong>{{ $sesiSelesai }}</strong><small>KBM ditutup</small></div></div><div class="summary-card"><div><span class="label">Sesi Belum Selesai</span><strong>{{ $sesiBelum }}</strong><small>Perlu ditindaklanjuti</small></div></div></section>
		<section class="panel"><div class="panel-head"><div class="panel-title">Log Riwayat Presensi</div><span class="muted">Maksimal 50 aktivitas terbaru</span></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Tanggal</th><th>Jenis Presensi</th><th>Jam</th><th>Mata Pelajaran</th><th>Kelas</th><th>Status</th></tr></thead><tbody>
			@forelse($aktivitas as $row)
				<tr><td>{{ $row['tanggal']->locale('id')->translatedFormat('j M Y') }}</td><td>{{ $row['jenis'] }}</td><td>{{ $row['waktu'] ? substr($row['waktu'], 0, 5) : '-' }}</td><td>{{ $row['mapel'] }}</td><td>{{ $row['kelas'] }}</td><td><span class="status-pill {{ strtolower($row['status']) }}">{{ $row['status'] }}</span></td></tr>
			@empty<tr><td colspan="6">Belum ada aktivitas presensi.</td></tr>@endforelse
		</tbody></table></div></section>
		<footer class="footer"><span>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</span><span>© {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan</span></footer>
	</main>
</div>
<script>
window.addEventListener('load',()=>setTimeout(()=>{pageLoader.classList.add('hide');guruPage.classList.add('loaded')},500));
function toggleSidebar(){sidebar.classList.toggle('open');sidebarOverlay.classList.toggle('show');burgerBtn.classList.toggle('active')}
function updateLiveDatetime(){const n=new Date(),days=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'],months=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],pad=value=>String(value).padStart(2,'0');liveDatetime.textContent=days[n.getDay()]+', '+n.getDate()+' '+months[n.getMonth()]+' '+n.getFullYear()+' | '+pad(n.getHours())+':'+pad(n.getMinutes())+':'+pad(n.getSeconds())+' WIB'}
updateLiveDatetime();setInterval(updateLiveDatetime,1000);
</script>
</body>
</html>