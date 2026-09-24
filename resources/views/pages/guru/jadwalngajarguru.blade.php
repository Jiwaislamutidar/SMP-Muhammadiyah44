<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Jadwal Mengajar Guru - SMP Muhammadiyah 44</title>
	<link rel="stylesheet" href="{{ asset('guru css/jadwalngajar.css') }}?v=1">
</head>
<body>
	<div class="page-loader" id="pageLoader">
		<div class="loader-logo-wrap"><div class="loader-spinner"></div><div class="loader-logo"><img src="{{ asset('logo.jpg') }}" alt="Logo sekolah"></div></div>
		<div class="loader-text">SMP Muhammadiyah 44</div>
		<div class="loader-subtext">Memuat jadwal mengajar...</div>
	</div>

	<div class="guru-page" id="guruPage">
		<aside class="sidebar" id="sidebar">
			<div class="sidebar-header">
				<div class="sidebar-brand">
					<div class="sidebar-logo"><img src="{{ asset('logo.jpg') }}" alt="Logo SMP Muhammadiyah 44"></div>
					<div><div class="sidebar-title">SMP Muhammadiyah 44</div><div class="sidebar-subtitle">Portal Guru</div></div>
				</div>
				<button class="sidebar-close-btn" onclick="toggleSidebar()" aria-label="Tutup sidebar">✕</button>
			</div>
			<div class="sidebar-section-label">Menu</div>
			<nav class="sidebar-nav">
				<a href="{{ route('guru.dashboard') }}" class="nav-item" style="--delay:1"><span class="nav-icon-box">🏠</span>Dashboard</a>
				<a href="{{ route('guru.presensi-guru') }}" class="nav-item" style="--delay:2"><span class="nav-icon-box">🧾</span>Presensi Guru</a>
				<a href="{{ route('guru.jadwal') }}" class="nav-item active" style="--delay:3"><span class="nav-icon-box">📅</span>Jadwal Mengajar</a>
				<a href="{{ route('guru.presensi-murid') }}" class="nav-item" style="--delay:4"><span class="nav-icon-box">👥</span>Presensi Murid</a>
				<a href="{{ route('guru.riwayat-presensi') }}" class="nav-item" style="--delay:5"><span class="nav-icon-box">🕘</span>Riwayat Presensi</a>
				<a href="{{ route('guru.profil') }}" class="nav-item" style="--delay:6"><span class="nav-icon-box">👤</span>Profil</a>
			</nav>
			<div class="sidebar-footer">
				<div class="sidebar-footer-user"><div class="avatar">AF</div><div><div class="name">Ust. Ahmad Fauzi</div><div class="role">Guru Mata Pelajaran</div></div></div>
				<form action="{{ route('guru.logout') }}" method="POST">
					@csrf
					<button type="submit" class="nav-item logout-item"><span class="nav-icon-box">🚪</span>Keluar</button>
				</form>
			</div>
		</aside>
		<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

		<header class="topbar">
			<div class="topbar-left">
				<button class="burger-btn" id="burgerBtn" onclick="toggleSidebar()" aria-label="Buka sidebar"><span></span><span></span><span></span></button>
				<div class="brand-wrap"><span class="brand-dot"></span><span class="portal-tag">Portal Guru • SMP Muhammadiyah 44</span></div>
			</div>
			<div class="topbar-right">
				<div class="date-pill" id="liveDatetime">Memuat waktu...</div>
				<div class="bell-btn" aria-label="Notifikasi">🔔<span class="badge"></span></div>
				<div class="profile-pill"><div class="avatar">AF</div><div class="profile-meta"><div class="name">Ust. Ahmad Fauzi, S.Pd.</div><div class="role">Guru IPA &amp; Matematika</div></div></div>
			</div>
		</header>

		<main class="content">
			<div class="page-header">
				<div><h1>Jadwal Mengajar</h1><p>Lihat jadwal mengajar Anda hari ini dan jadwal berikutnya.</p></div>
				<div class="term-chip"><span class="dot"></span>Semester Ganjil TA 2026/2027</div>
			</div>

			<section class="filter-bar">
				<div class="filter-group">
					<label for="dayFilter">Hari:</label><select id="dayFilter"><option>Senin (Hari Ini)</option><option>Selasa</option><option>Rabu</option><option>Kamis</option><option>Jumat</option></select>
				</div>
				<div class="filter-group">
					<label for="classFilter">Kelas:</label><select id="classFilter"><option>Semua Kelas (7A, 8A, 9A)</option><option>Kelas 7A</option><option>Kelas 8A</option><option>Kelas 9A</option></select>
				</div>
				<div class="filter-group">
					<label for="subjectFilter">Mata Pelajaran:</label><select id="subjectFilter"><option>Semua Mata Pelajaran</option><option>Matematika</option></select>
				</div>
				<div class="total-badge">Total Jadwal: <strong>3 Sesi Tatap Muka</strong></div>
			</section>

			<section class="schedule-card">
				<div class="table-heading"><div><h2>Daftar Jadwal Mengajar Guru</h2></div><span>Terkunci otomatis oleh Kurikulum Akademik</span></div>
				<div class="table-scroll">
					<table class="schedule-table">
						<thead><tr><th>Hari</th><th>Jam</th><th>Mata Pelajaran &amp; Ruang KBM</th><th>Kelas</th><th>Status</th><th>Aksi</th></tr></thead>
						<tbody>
							  <tr data-day="Senin" data-class="7A" data-subject="Matematika"><td>Senin</td><td class="time">07.00–08.20</td><td><strong>Matematika</strong><small>Ruang KBM: Gedung K.H. Ahmad Dahlan Lt. 1</small></td><td><span class="class-chip">Kelas 7A</span></td><td><span class="status done"><i></i>Selesai</span></td><td><button class="detail-btn" type="button">Lihat Detail</button></td></tr>
							  <tr class="active-row" data-day="Senin" data-class="8A" data-subject="Matematika"><td>Senin</td><td class="time">08.20–09.40</td><td><div class="subject-line"><strong>Matematika</strong><span class="active-tag">SESI AKTIF</span></div><small>Ruang KBM: Gedung K.H. Ahmad Dahlan Lt. 2</small></td><td><span class="class-chip active">Kelas 8A</span></td><td><span class="status ongoing"><i></i>Sedang Berlangsung</span></td><td><button class="detail-btn" type="button">Lihat Detail</button></td></tr>
							  <tr data-day="Senin" data-class="9A" data-subject="Matematika"><td>Senin</td><td class="time">10.00–11.20</td><td><strong>Matematika</strong><small>Ruang KBM: Gedung K.H. Ahmad Dahlan Lt. 2</small></td><td><span class="class-chip">Kelas 9A</span></td><td><span class="status upcoming"><i></i>Belum</span></td><td><button class="detail-btn" type="button">Lihat Detail</button></td></tr>
						</tbody>
					</table>
				</div>
				<div class="table-footer"><span>Menampilkan <strong>3 sesi mengajar</strong> terjadwal untuk hari ini.</span><span class="sync"><i></i>Sinkronisasi otomatis kurikulum akademik aktif</span></div>
			</section>

			<footer class="footer"><span>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</span><span>© {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan. Seluruh hak cipta dilindungi.</span></footer>
		</main>
	</div>
	<script>
		window.addEventListener('load', function () { setTimeout(function () { document.getElementById('pageLoader').classList.add('hide'); document.getElementById('guruPage').classList.add('loaded'); }, 500); });
		function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('show'); document.getElementById('burgerBtn').classList.toggle('active'); }
		function updateLiveDatetime() { const days=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu']; const months=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; const now=new Date(); const pad=value=>String(value).padStart(2,'0'); document.getElementById('liveDatetime').innerHTML='<span class="date-html">'+days[now.getDay()]+', '+now.getDate()+' '+months[now.getMonth()]+' '+now.getFullYear()+'</span><span class="sep"> | </span><span class="time-html">'+pad(now.getHours())+':'+pad(now.getMinutes())+':'+pad(now.getSeconds())+' WIB</span>'; }
		updateLiveDatetime(); setInterval(updateLiveDatetime, 1000);

		const filters = [document.getElementById('dayFilter'), document.getElementById('classFilter'), document.getElementById('subjectFilter')];
		const scheduleRows = Array.from(document.querySelectorAll('.schedule-table tbody tr'));
		function filterSchedule() {
			const day = filters[0].value.split(' ')[0];
			const selectedClass = filters[1].value.match(/[789]A/g);
			const selectedSubject = filters[2].value === 'Semua Mata Pelajaran' ? null : filters[2].value;
			scheduleRows.forEach(function (row) {
				const classMatches = !selectedClass || selectedClass.includes(row.dataset.class);
				const subjectMatches = !selectedSubject || row.dataset.subject === selectedSubject;
				row.hidden = row.dataset.day !== day || !classMatches || !subjectMatches;
			});
		}
		filters.forEach(function (filter) { filter.addEventListener('change', filterSchedule); });
	</script>
</body>
</html>
