<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg"><title>Profil Guru - SMP Muhammadiyah 44</title><link rel="stylesheet" href="{{ asset('guru-css/profil.css') }}?v=3"><link rel="stylesheet" href="{{ asset('guru-css/portal-responsive.css') }}?v=1"><link rel="stylesheet" href="{{ asset('guru-css/guru-ui.css') }}?v=6"></head>
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

	@if(session('success'))
		<div class="profile-alert success" role="status">{{ session('success') }}</div>
	@endif
	@if($errors->any())
		<div class="profile-alert error" role="alert">{{ $errors->first() }}</div>
	@endif

	<section class="profile-layout">
		<div class="panel profile-card">
			<div class="avatar">{{ mb_strtoupper(mb_substr($guru->nama_lengkap, 0, 1)) }}</div>
			<div class="profile-identity">
				<div class="profile-name-row">
					<h2>{{ $guru->nama_lengkap }}</h2>
					<span class="role-chip">Guru</span>
					<span class="status-pill hadir">Aktif</span>
				</div>
				<p><strong>NBM:</strong> {{ $guru->nbm ?? '-' }} <span class="identity-separator">•</span> SMP Muhammadiyah 44 Tangerang Selatan</p>
			</div>
		</div>

		<div class="profile-main-column">
			<section class="panel profile-info">
				<div class="panel-head">
					<h2>Informasi Guru</h2>
					<span>Data Induk Kepegawaian</span>
				</div>
				<dl class="details-grid">
					<div><dt>Nama Lengkap</dt><dd>{{ $guru->nama_lengkap }}</dd></div>
					<div><dt>NBM (Nomor Baku Muhammadiyah)</dt><dd class="mono">{{ $guru->nbm ?? '-' }}</dd></div>
					<div><dt>Status Kepegawaian</dt><dd><span class="status-pill hadir">{{ ucfirst($guru->status) }}</span></dd></div>
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
			<div class="account-row"><span>Status Akun Portal</span><span class="status-pill hadir">Aktif</span></div>
			<div class="account-row"><span>Terakhir Masuk Sistem</span><strong>8 Sept 2026, 06.35 WIB</strong></div>
			<button class="btn account-password" id="openPasswordModal" type="button">Ubah Password Akun</button>
		</aside>
	</section>

	<div class="password-modal-backdrop" id="passwordModal" @if(!$errors->any()) hidden @endif>
		<section class="password-modal" role="dialog" aria-modal="true" aria-labelledby="passwordModalTitle">
			<div class="password-modal-heading">
				<div>
					<h2 id="passwordModalTitle">Ubah Kata Sandi</h2>
					<p>Masukkan kata sandi saat ini dan kata sandi baru.</p>
				</div>
				<button type="button" class="password-modal-close" id="closePasswordModal" aria-label="Tutup">&times;</button>
			</div>
			<form action="{{ route('guru.profil.update-password') }}" method="POST" class="password-form">
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
					<button type="button" id="cancelPasswordModal">Batal</button>
					<button type="submit" class="password-modal-submit">Simpan Kata Sandi</button>
				</div>
			</form>
		</section>
	</div>

	<footer class="footer"><span>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</span><span>© {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan</span></footer>
</main></div>
<script src="{{ asset('guru-css/guru-ui.js') }}?v=1"></script>
<script>window.addEventListener('load',()=>guruPage.classList.add('loaded'))</script>
<script>
	const passwordModal = document.getElementById('passwordModal');
	const openPasswordModal = document.getElementById('openPasswordModal');
	const currentPassword = document.getElementById('current_password');

	function closePasswordDialog() {
		passwordModal.hidden = true;
		openPasswordModal.focus();
	}

	openPasswordModal.addEventListener('click', function () {
		passwordModal.hidden = false;
		currentPassword.focus();
	});
	document.getElementById('closePasswordModal').addEventListener('click', closePasswordDialog);
	document.getElementById('cancelPasswordModal').addEventListener('click', closePasswordDialog);
	passwordModal.addEventListener('click', function (event) {
		if (event.target === passwordModal) closePasswordDialog();
	});
	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && !passwordModal.hidden) closePasswordDialog();
	});
	if (!passwordModal.hidden) currentPassword.focus();
</script>
</body></html>
