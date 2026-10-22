<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg"><title>Profil Guru - SMP Muhammadiyah 44</title><link rel="stylesheet" href="{{ asset('guru css/profil.css') }}?v=2"><link rel="stylesheet" href="{{ asset('guru css/portal-responsive.css') }}?v=1"><link rel="stylesheet" href="{{ asset('guru css/guru-ui.css') }}?v=3"></head>
<body>
<div class="guru-page" id="guruPage">
@include('pages.guru.partials.sidebar', ['activePage' => 'profil', 'sidebarRole' => 'Guru'])
@include('pages.guru.partials.topbar', ['profileRole' => 'Guru'])
<main class="content">
	<div class="page-header">
		<div>
			<h1>Profil Guru</h1>
			<p>Lihat dan kelola informasi profil akun Anda.</p>
		</div>
	</div>

	<section class="profile-layout">
		<div class="panel profile-card">
			<div class="avatar">{{ mb_strtoupper(mb_substr($guru->nama_lengkap, 0, 1)) }}</div>
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
<script src="{{ asset('guru css/guru-ui.js') }}?v=1"></script>
<script>window.addEventListener('load',()=>guruPage.classList.add('loaded'))</script>
</body></html>
