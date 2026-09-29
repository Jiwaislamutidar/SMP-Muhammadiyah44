<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg"><title>Profil Guru - SMP Muhammadiyah 44</title><link rel="stylesheet" href="{{ asset('guru css/profil.css') }}?v=1"></head>
<body>
<div class="guru-page" id="guruPage"><aside class="sidebar" id="sidebar"><div class="sidebar-header"><div class="sidebar-brand"><div class="sidebar-logo"><img src="{{ asset('logo.jpg') }}" alt="Logo sekolah"></div><div><div class="sidebar-title">SMP Muhammadiyah 44</div><div class="sidebar-subtitle">Portal Guru</div></div></div><button class="sidebar-close-btn" onclick="toggleSidebar()">✕</button></div><div class="sidebar-section-label">Menu</div><nav class="sidebar-nav"><a class="nav-item" style="--delay:1" href="{{ route('guru.dashboard') }}"><span class="nav-icon-box">🏠</span>Dashboard</a><a class="nav-item" style="--delay:2" href="{{ route('guru.presensi-guru') }}"><span class="nav-icon-box">🧾</span>Presensi Guru</a><a class="nav-item" style="--delay:3" href="{{ route('guru.jadwal') }}"><span class="nav-icon-box">📅</span>Jadwal Mengajar</a><a class="nav-item" style="--delay:4" href="{{ route('guru.presensi-murid') }}"><span class="nav-icon-box">👥</span>Presensi Murid</a><a class="nav-item" style="--delay:5" href="{{ route('guru.riwayat-presensi') }}"><span class="nav-icon-box">🕘</span>Riwayat Presensi</a><a class="nav-item active" style="--delay:6" href="{{ route('guru.profil') }}"><span class="nav-icon-box">👤</span>Profil</a></nav><div class="sidebar-footer"><div class="sidebar-footer-user"><div class="avatar">AF</div><div><div class="name">Ust. Ahmad Fauzi</div><div class="role">Guru Mata Pelajaran</div></div></div><form action="{{ route('guru.logout') }}" method="POST">@csrf<button class="nav-item logout-item" type="submit"><span class="nav-icon-box">🚪</span>Keluar</button></form></div></aside><div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<header class="topbar"><div class="topbar-left"><button class="burger-btn" id="burgerBtn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="brand-wrap"><span class="brand-dot"></span><span class="portal-tag">Portal Guru • SMP Muhammadiyah 44</span></div></div><div class="topbar-right"><div class="date-pill" id="liveDatetime">Memuat waktu...</div><div class="bell-btn">🔔<span class="badge"></span></div><div class="profile-pill"><div class="avatar">AF</div><div class="profile-meta"><div class="name">Ust. Ahmad Fauzi, S.Pd.</div><div class="role">Guru IPA &amp; Matematika</div></div></div></div></header>
<main class="content">
	<div class="page-header">
		<div>
			<h1>Profil Guru</h1>
			<p>Lihat dan kelola informasi profil akun Anda.</p>
		</div>
	</div>

	<section class="profile-layout">
		<div class="panel profile-card">
			<div class="avatar">AF</div>
			<div class="profile-identity">
				<div class="profile-name-row">
					<h2>{{ $guru->nama_lengkap }}</h2>
					<span class="role-chip">Guru</span>
					<span class="status-pill hadir"><i></i>Aktif</span>
				</div>
				<p><strong>NIP:</strong> {{ $guru->nip ?? '-' }} <span class="identity-separator">•</span> SMP Muhammadiyah 44 Tangerang Selatan</p>
			</div>
			<button class="btn btn-primary profile-edit" type="button">Edit Profil</button>
		</div>

		<div class="profile-main-column">
			<section class="panel profile-info">
				<div class="panel-head">
					<h2>Informasi Guru</h2>
					<span>Data Induk Kepegawaian</span>
				</div>
				<dl class="details-grid">
					<div><dt>Nama Lengkap</dt><dd>{{ $guru->nama_lengkap }}</dd></div>
					<div><dt>NIP (Nomor Induk Pegawai)</dt><dd class="mono">{{ $guru->nip ?? '-' }}</dd></div>
					<div><dt>Status Kepegawaian</dt><dd><span class="status-pill hadir"><i></i>{{ ucfirst($guru->status) }}</span></dd></div>
					<div><dt>Username Akun</dt><dd class="mono">{{ $user->username }}</dd></div>
					<div class="detail-wide"><dt>Email Akun</dt><dd>{{ $user->email ?? '-' }}</dd></div>
				</dl>
			</section>

			<section class="panel subject-panel">
				<div class="panel-head">
					<h2>Mata Pelajaran dan Kelas Diampu</h2>
					<span class="read-only-chip">Read-only Akademik</span>
				</div>
				<div class="subject-details">
					<div><span class="detail-label">Mata Pelajaran</span><div class="class-tags">@forelse($mapelDiampu as $namaMapel)<span>{{ $namaMapel }}</span>@empty<span>Belum ada mata pelajaran</span>@endforelse</div></div>
					<div><span class="detail-label">Kelas yang Diampu</span><div class="class-tags">@forelse($kelasDiampu as $namaKelas)<span>{{ $namaKelas }}</span>@empty<span>Belum ada kelas</span>@endforelse</div></div>
				</div>
				<p class="sync-note">Penetapan rombongan belajar disinkronisasikan langsung oleh Bagian Kurikulum Akademik SMP Muhammadiyah 44.</p>
			</section>
		</div>

		<aside class="panel account-panel">
			<div class="panel-head">
				<h2>Akun &amp; Autentikasi</h2>
				<span class="verified-chip">Terverifikasi</span>
			</div>
			<div class="account-field">
				<span class="detail-label">Username Akun</span>
				<div class="account-value mono">{{ $user->username }}</div>
			</div>
			<div class="account-row"><span>Status Akun Portal</span><span class="status-pill hadir"><i></i>Aktif</span></div>
			<div class="account-row"><span>Terakhir Masuk Sistem</span><strong>8 Sept 2026, 06.35 WIB</strong></div>
			<button class="btn account-password" type="button">Ubah Password Akun</button>
			<div class="device-info"><span class="device-icon">&#9673;</span><div><strong>Browser Saat Ini: Chrome on Windows</strong><small>IP Address: 182.253.114.20 (Aktif)</small></div></div>
		</aside>
	</section>

	<footer class="footer"><span>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</span><span>© {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan</span></footer>
</main></div>
<script>window.addEventListener('load',()=>guruPage.classList.add('loaded'));function toggleSidebar(){sidebar.classList.toggle('open');sidebarOverlay.classList.toggle('show');burgerBtn.classList.toggle('active')}function updateLiveDatetime(){let n=new Date(),d=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'],m=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],p=x=>String(x).padStart(2,'0');liveDatetime.innerHTML=`<span class="date-html">${d[n.getDay()]}, ${n.getDate()} ${m[n.getMonth()]} ${n.getFullYear()}</span><span class="sep"> | </span><span class="time-html">${p(n.getHours())}:${p(n.getMinutes())}:${p(n.getSeconds())} WIB</span>`}updateLiveDatetime();setInterval(updateLiveDatetime,1000)</script>
</body></html>
