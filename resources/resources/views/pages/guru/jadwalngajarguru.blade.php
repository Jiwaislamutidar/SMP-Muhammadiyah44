<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
	<title>Jadwal Mengajar Guru - SMP Muhammadiyah 44</title>
	<link rel="stylesheet" href="{{ asset('guru-css/jadwalngajar.css') }}?v=5">
	<link rel="stylesheet" href="{{ asset('guru-css/portal-responsive.css') }}?v=1">
	<link rel="stylesheet" href="{{ asset('guru-css/guru-ui.css') }}?v=6">
</head>
<body>
	<div class="page-loader" id="pageLoader">
		<div class="loader-logo-wrap"><div class="loader-spinner"></div><div class="loader-logo"><img src="{{ asset('logo.jpg') }}" alt="Logo sekolah"></div></div>
		<div class="loader-text">SMP Muhammadiyah 44</div>
		<div class="loader-subtext">Memuat jadwal mengajar...</div>
	</div>
	<div class="guru-page schedule-page" id="guruPage">
		@include('pages.guru.partials.sidebar', ['activePage' => 'jadwal', 'sidebarRole' => 'Guru'])
		@include('pages.guru.partials.topbar', ['profileRole' => 'Guru'])
		<main class="content">
			<div class="page-header"><div><h1>Jadwal Mengajar</h1><p>Jadwal mingguan Anda</p></div><div class="term-chip"><span class="dot"></span>{{ $jadwals->count() }} jadwal minggu ini</div></div>
			<section class="schedule-card">
				<div class="table-heading"><div><h2>Daftar Jadwal Mengajar</h2></div><span>{{ $guru->nama_lengkap }}</span></div>
				<div class="table-scroll"><table class="schedule-table">
					<thead><tr><th>Hari</th><th>Jam</th><th>Mata Pelajaran &amp; Ruang KBM</th><th>Kelas</th><th>Aksi</th></tr></thead>
					<tbody>
						@forelse($jadwals as $jadwal)
							<tr>
								<td>{{ $jadwal->hari }}</td><td class="time">{{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}</td>
								<td><strong>{{ $jadwal->mapel->nama_mapel }}</strong><small>Ruang KBM: {{ $jadwal->kelas?->ruangan ?? '-' }}</small></td><td><span class="class-chip">{{ $jadwal->kelas->nama_kelas }}</span></td>
								<td><a class="detail-btn" href="{{ route('guru.presensi-murid', ['jadwal' => $jadwal->id]) }}">Kelola Presensi</a></td>
							</tr>
						@empty<tr><td colspan="5">Tidak ada jadwal mengajar yang tersimpan.</td></tr>@endforelse
					</tbody>
				</table></div>
				<div class="table-footer"><span>Menampilkan <strong>{{ $jadwals->count() }} jadwal</strong> dari jadwal tersimpan.</span><span class="sync"><i></i>Data jadwal mingguan</span></div>
			</section>
			<footer class="footer"><span>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</span><span>© {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan</span></footer>
		</main>
	</div>
	<script src="{{ asset('guru-css/guru-ui.js') }}?v=1"></script>
	<script>
		window.addEventListener('load', function () { setTimeout(function () { document.getElementById('pageLoader').classList.add('hide'); document.getElementById('guruPage').classList.add('loaded'); }, 500); });
	</script>
</body>
</html>