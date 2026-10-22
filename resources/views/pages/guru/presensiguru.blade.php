<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
	<title>Presensi Guru - SMP Muhammadiyah 44</title>
	<link rel="stylesheet" href="{{ asset('guru css/presensiguru.css') }}?v=5">
	<link rel="stylesheet" href="{{ asset('guru css/portal-responsive.css') }}?v=1">
	<link rel="stylesheet" href="{{ asset('guru css/guru-ui.css') }}?v=3">
	<style>
		.attendance-card h3 { font-size: 17px; }
		.attendance-card small, .history-description { font-size: 12px; line-height: 1.5; }
		.status-pill { font-size: 11px; padding: 5px 9px; }
		.status-pill.belum, .status-pill.neutral { color: #4b5563; background: #f3f4f6; border-color: #d1d5db; }
		.history-toolbar, .history-actions { display: flex; align-items: end; flex-wrap: wrap; gap: 12px; }
		.history-toolbar { margin: 16px 0; }
		.history-toolbar label { display: grid; gap: 6px; color: #374151; font-size: 13px; font-weight: 600; }
		.history-toolbar select { min-width: 220px; min-height: 46px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #17211c; font: inherit; }
		.history-actions { align-items: center; margin: 12px 0 16px; }
		.history-actions button { min-height: 42px; padding: 9px 14px; font-size: 13px; }
		.history-table { min-width: 620px; }
		.history-table th, .history-table td { font-size: 13px; }
		.history-description { margin: 6px 0 0; color: #59636e; }
		.attendance-switcher { display: none; }
		@media (max-width: 767px) {
			.attendance-switcher {
				display: grid;
				grid-template-columns: repeat(2, minmax(0, 1fr));
				gap: 4px;
				margin-bottom: 14px;
				padding: 4px;
				border: 1px solid #e5e7eb;
				border-radius: 12px;
				background: #f3f4f6;
			}
			.attendance-switcher button {
				min-width: 0;
				min-height: 42px;
				padding: 8px 10px;
				border: 0;
				border-radius: 9px;
				background: transparent;
				color: #59636e;
				font: inherit;
				font-size: 13px;
				font-weight: 600;
				cursor: pointer;
			}
			.attendance-switcher button[aria-selected="true"] {
				background: #fff;
				color: #17211c;
				box-shadow: 0 1px 3px rgba(22, 43, 30, .12);
			}
			.attendance-cards { grid-template-columns: minmax(0, 1fr); }
			.attendance-card.mobile-tab-hidden { display: none; }
		}
		@media (min-width: 768px) {
			.attendance-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
		}
		@media (max-width: 560px) {
			.history-toolbar { align-items: stretch; flex-direction: column; }
			.history-toolbar select, .history-toolbar button, .history-actions button { width: 100%; }
			.history-actions { align-items: stretch; flex-direction: column; }
		}
	</style>
</head>
<body>
	<div class="page-loader" id="pageLoader">
		<div class="loader-logo-wrap"><div class="loader-spinner"></div><div class="loader-logo"><img src="{{ asset('logo.jpg') }}" alt="Logo sekolah"></div></div>
		<div class="loader-text">SMP Muhammadiyah 44</div>
		<div class="loader-subtext">Memuat presensi guru...</div>
	</div>
	<div class="guru-page attendance-page" id="guruPage">
		@include('pages.guru.partials.sidebar', ['activePage' => 'presensi-guru', 'sidebarRole' => 'Guru'])
		@include('pages.guru.partials.topbar', ['profileRole' => 'Guru'])
		<main class="content">
			<div class="page-header">
				<div>
					<h1>Presensi Guru</h1>
					<p>Kehadiran sekolah untuk {{ now()->locale('id')->translatedFormat('l, j F Y') }}.</p>
				</div>
			</div>

			@if(session('success'))<div class="panel" role="status">{{ session('success') }}</div>@endif
			@if($errors->any())<div class="panel" role="alert">{{ $errors->first() }}</div>@endif

			<section class="panel">
				<div class="panel-head"><div class="panel-title">Kehadiran Sekolah</div></div>
				<div class="attendance-switcher" role="tablist" aria-label="Pilih jenis presensi">
					<button type="button" role="tab" id="attendanceTabMasuk" aria-controls="attendancePanelMasuk" aria-selected="true" data-attendance-tab="masuk">Absen Datang</button>
					<button type="button" role="tab" id="attendanceTabPulang" aria-controls="attendancePanelPulang" aria-selected="false" data-attendance-tab="pulang">Absen Kepulangan</button>
				</div>
				<div class="cards-two attendance-cards">
					<div class="attendance-card" id="attendancePanelMasuk" role="tabpanel" aria-labelledby="attendanceTabMasuk" data-attendance-panel="masuk">
						<div class="card-top">
							<div class="attendance-card-heading">
								<span class="muted">ABSEN DATANG</span>
								<span class="status-pill {{ $presensi?->jam_masuk ? 'hadir' : 'belum' }}">{{ $presensi?->jam_masuk ? 'Sudah Absen' : 'Belum Absen' }}</span>
							</div>
							<div class="attendance-card-copy">
								<h3>Absen Masuk Sekolah</h3>
								<small>{{ $presensi?->jam_masuk ? 'Presensi tersimpan hari ini.' : 'Ambil foto sebagai bukti kehadiran.' }}</small>
							</div>
						</div>
						<div class="big-time">{{ $presensi?->jam_masuk ? substr($presensi->jam_masuk, 0, 5) : 'Belum tercatat' }} @if($presensi?->jam_masuk)<small>WIB</small>@endif</div>
						@if($presensi?->foto_masuk)<p><a href="{{ asset('storage/'.$presensi->foto_masuk) }}" target="_blank" rel="noopener">Lihat foto bukti</a></p>@endif
						@if($presensi?->jam_masuk)
							<button class="attendance-action attendance-done" type="button" disabled>Sudah Absen ({{ substr($presensi->jam_masuk, 0, 5) }} WIB)</button>
						@else
							<button class="attendance-action" type="button" data-title="Absen Masuk Sekolah" data-url="{{ route('guru.presensi.masuk') }}">Absen Masuk Sekolah</button>
						@endif
					</div>
					<div class="attendance-card mobile-tab-hidden" id="attendancePanelPulang" role="tabpanel" aria-labelledby="attendanceTabPulang" data-attendance-panel="pulang">
						<div class="card-top">
							<div class="attendance-card-heading">
								<span class="muted">ABSEN KEPULANGAN</span>
								<span class="status-pill {{ $presensi?->jam_pulang ? 'hadir' : 'belum' }}">{{ $presensi?->jam_pulang ? 'Sudah Absen' : 'Belum Absen' }}</span>
							</div>
							<div class="attendance-card-copy">
								<h3>Absen Pulang Sekolah</h3>
								<small>{{ $presensi?->jam_pulang ? 'Presensi tersimpan hari ini.' : ($presensi?->jam_masuk ? 'Ambil foto sebagai bukti kepulangan.' : 'Absen masuk lebih dahulu.') }}</small>
							</div>
						</div>
						<div class="big-time">{{ $presensi?->jam_pulang ? substr($presensi->jam_pulang, 0, 5) : 'Belum tercatat' }} @if($presensi?->jam_pulang)<small>WIB</small>@endif</div>
						@if($presensi?->foto_pulang)<p><a href="{{ asset('storage/'.$presensi->foto_pulang) }}" target="_blank" rel="noopener">Lihat foto bukti</a></p>@endif
						@if($presensi?->jam_pulang)
							<button class="attendance-action attendance-done" type="button" disabled>Sudah Absen ({{ substr($presensi->jam_pulang, 0, 5) }} WIB)</button>
						@else
							<button class="attendance-action" type="button" data-title="Absen Pulang Sekolah" data-url="{{ route('guru.presensi.pulang') }}" {{ $presensi?->jam_masuk ? '' : 'disabled' }}>Absen Pulang Sekolah</button>
						@endif
					</div>
				</div>
			</section>

			<section class="panel history-panel">
				<div class="panel-head">
					<div>
						<div class="panel-title">Riwayat Presensi</div>
						<p class="history-description">Riwayat presensi sekolah Anda, diurutkan dari tanggal terbaru.</p>
					</div>
					<span class="muted">{{ $presensiRiwayat->count() }} catatan</span>
				</div>

				<form class="history-toolbar" method="GET" action="{{ route('guru.presensi-guru') }}">
					<label for="periode">Filter periode
						<select id="periode" name="periode">
							<option value="hari" {{ $periode === 'hari' ? 'selected' : '' }}>1 hari terakhir</option>
							<option value="minggu" {{ $periode === 'minggu' ? 'selected' : '' }}>Seminggu terakhir</option>
							<option value="bulan" {{ $periode === 'bulan' ? 'selected' : '' }}>Sebulan terakhir</option>
						</select>
					</label>
					<button class="btn btn-primary" type="submit">Tampilkan</button>
				</form>

				<div class="history-actions">
					<button class="btn btn-primary" id="exportHistory" type="button">Unduh rekap CSV</button>
					<form id="resetHistoryForm" method="POST" action="{{ route('guru.presensi-riwayat.reset') }}">
						@csrf
						@method('DELETE')
						<button class="btn" type="submit">Reset seluruh riwayat</button>
					</form>
				</div>

				<div class="table-wrap">
					<table class="data-table history-table" id="attendanceHistory">
						<thead><tr><th>Tanggal</th><th>Jam Masuk</th><th>Jam Pulang</th><th>Status</th></tr></thead>
						<tbody>
							@forelse($presensiRiwayat as $riwayat)
								<tr data-history-row>
									<td>{{ $riwayat->tanggal->locale('id')->translatedFormat('l, j F Y') }}</td>
									<td>{{ $riwayat->jam_masuk ? substr($riwayat->jam_masuk, 0, 5) : '-' }}</td>
									<td>{{ $riwayat->jam_pulang ? substr($riwayat->jam_pulang, 0, 5) : '-' }}</td>
									<td><span class="status-pill {{ $riwayat->status === 'Hadir' ? 'hadir' : 'neutral' }}">{{ $riwayat->status }}</span></td>
								</tr>
							@empty
								<tr><td colspan="4">Belum ada riwayat presensi pada periode ini.</td></tr>
							@endforelse
						</tbody>
					</table>
				</div>
			</section>

			<footer class="footer">
				<span>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</span>
				<span>© {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan</span>
			</footer>
		</main>
	</div>

	<dialog class="photo-modal" id="photoModal" aria-labelledby="photoModalTitle">
		<form id="attendanceForm" method="POST" enctype="multipart/form-data">
			@csrf
			<div class="photo-modal-head">
				<div><h2 id="photoModalTitle">Absen</h2><p>Buka kamera untuk mengambil foto bukti kehadiran.</p></div>
				<button class="modal-close" type="button" id="closePhotoModal" aria-label="Tutup">×</button>
			</div>
			<div class="camera-preview">
				<video id="cameraPreview" autoplay playsinline hidden></video>
				<img id="photoPreview" alt="Pratinjau foto" hidden>
				<div id="cameraPlaceholder">Pratinjau foto akan muncul di sini.</div>
			</div>
			<div class="photo-controls">
				<button class="btn" id="startCamera" type="button">Buka Kamera</button>
				<button class="btn" id="takePhoto" type="button" disabled>Ambil Foto</button>
				<input id="photoInput" type="file" name="foto" accept="image/jpeg,image/png,image/webp" hidden>
			</div>
			<p class="photo-error" id="photoError" role="alert"></p>
			<div class="photo-modal-actions">
				<button class="btn" type="button" id="cancelPhoto">Batal</button>
				<button class="btn btn-primary attendance-submit" id="submitAttendance" type="submit" disabled>Kirim Presensi</button>
			</div>
		</form>
	</dialog>

	<script>
		window.addEventListener('load', () => setTimeout(() => {
			document.getElementById('pageLoader').classList.add('hide');
			document.getElementById('guruPage').classList.add('loaded');
		}, 500));

		document.querySelectorAll('[data-attendance-tab]').forEach(tab => tab.addEventListener('click', () => {
			document.querySelectorAll('[data-attendance-tab]').forEach(item => {
				item.setAttribute('aria-selected', String(item === tab));
			});
			document.querySelectorAll('[data-attendance-panel]').forEach(panel => {
				panel.classList.toggle('mobile-tab-hidden', panel.dataset.attendancePanel !== tab.dataset.attendanceTab);
			});
		}));

		const photoModal = document.getElementById('photoModal');
		const attendanceForm = document.getElementById('attendanceForm');
		const photoInput = document.getElementById('photoInput');
		const photoPreview = document.getElementById('photoPreview');
		const cameraPreview = document.getElementById('cameraPreview');
		const cameraPlaceholder = document.getElementById('cameraPlaceholder');
		const photoError = document.getElementById('photoError');
		const submitAttendance = document.getElementById('submitAttendance');
		let cameraStream = null;
		let previewUrl = null;

		function showPhoto(file) {
			if (!file) return;
			if (previewUrl) URL.revokeObjectURL(previewUrl);
			previewUrl = URL.createObjectURL(file);
			photoPreview.src = previewUrl;
			photoPreview.hidden = false;
			cameraPlaceholder.hidden = true;
			submitAttendance.disabled = false;
			photoError.textContent = '';
		}

		function stopPreviewCamera() {
			cameraStream?.getTracks().forEach(track => track.stop());
			cameraStream = null;
			cameraPreview.srcObject = null;
			cameraPreview.hidden = true;
			cameraPreview.onloadedmetadata = null;
			document.getElementById('takePhoto').disabled = true;
		}

		function cameraErrorMessage(error) {
			if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
				return 'Izin kamera ditolak. Izinkan akses kamera di pengaturan browser, lalu coba lagi.';
			}
			if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
				return 'Kamera tidak ditemukan pada perangkat ini.';
			}
			if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
				return 'Kamera sedang digunakan aplikasi lain. Tutup aplikasi tersebut, lalu coba lagi.';
			}
			return 'Kamera tidak dapat dibuka. Pastikan halaman menggunakan HTTPS dan izin kamera tersedia.';
		}

		document.querySelectorAll('.attendance-action:not(.attendance-done)').forEach(button => button.addEventListener('click', () => {
			attendanceForm.action = button.dataset.url;
			document.getElementById('photoModalTitle').textContent = button.dataset.title;
			photoModal.showModal();
		}));

		document.getElementById('startCamera').addEventListener('click', async () => {
			stopPreviewCamera();
			try {
				if (!navigator.mediaDevices?.getUserMedia) {
					photoError.textContent = 'Kamera tidak tersedia. Buka halaman melalui HTTPS pada browser yang mendukung kamera.';
					return;
				}
				cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
				cameraPreview.srcObject = cameraStream;
				cameraPreview.hidden = false;
				cameraPlaceholder.hidden = true;
				document.getElementById('takePhoto').disabled = true;
				cameraPreview.onloadedmetadata = () => document.getElementById('takePhoto').disabled = false;
				await cameraPreview.play();
				photoError.textContent = '';
			} catch (error) {
				stopPreviewCamera();
				cameraPlaceholder.hidden = false;
				photoError.textContent = cameraErrorMessage(error);
			}
		});

		document.getElementById('takePhoto').addEventListener('click', () => {
			if (!cameraPreview.videoWidth || !cameraPreview.videoHeight) {
				photoError.textContent = 'Kamera belum siap. Tunggu sebentar lalu coba ambil foto kembali.';
				return;
			}
			const canvas = document.createElement('canvas');
			canvas.width = cameraPreview.videoWidth;
			canvas.height = cameraPreview.videoHeight;
			const context = canvas.getContext('2d');
			if (!context) {
				photoError.textContent = 'Foto tidak dapat diproses. Silakan buka kamera kembali.';
				return;
			}
			context.drawImage(cameraPreview, 0, 0);
			canvas.toBlob(blob => {
				if (!blob) {
					photoError.textContent = 'Foto tidak dapat diproses. Silakan coba kembali.';
					return;
				}
				const transfer = new DataTransfer();
				transfer.items.add(new File([blob], 'presensi-guru.jpg', { type: 'image/jpeg' }));
				photoInput.files = transfer.files;
				showPhoto(photoInput.files[0]);
				stopPreviewCamera();
			}, 'image/jpeg', .9);
		});

		function closePhotoModal() {
			stopPreviewCamera();
			photoModal.close();
			attendanceForm.reset();
			photoPreview.hidden = true;
			cameraPlaceholder.hidden = false;
			submitAttendance.disabled = true;
			if (previewUrl) {
				URL.revokeObjectURL(previewUrl);
				previewUrl = null;
			}
		}

		document.getElementById('closePhotoModal').addEventListener('click', closePhotoModal);
		document.getElementById('cancelPhoto').addEventListener('click', closePhotoModal);
		photoModal.addEventListener('click', event => {
			if (event.target === photoModal) closePhotoModal();
		});
		photoModal.addEventListener('close', stopPreviewCamera);
		attendanceForm.addEventListener('submit', event => {
			if (!photoInput.files.length) {
				event.preventDefault();
				photoError.textContent = 'Buka kamera dan ambil foto sebelum mengirim.';
				return;
			}
			submitAttendance.disabled = true;
			submitAttendance.textContent = 'Mengirim...';
		});

		document.getElementById('resetHistoryForm')?.addEventListener('submit', event => {
			if (!window.confirm('Reset seluruh riwayat presensi Anda? Tindakan ini tidak dapat dibatalkan.')) {
				event.preventDefault();
			}
		});

		function csvCell(value) {
			let text = value.trim();
			if (/^[=+\-@\t\r]/.test(text)) text = `'${text}`;
			return `"${text.replace(/"/g, '""')}"`;
		}

		document.getElementById('exportHistory').addEventListener('click', () => {
			const rows = Array.from(document.querySelectorAll('#attendanceHistory tr[data-history-row]'))
				.map(row => Array.from(row.cells, cell => csvCell(cell.innerText)));
			const csv = ['Tanggal,Jam Masuk,Jam Pulang,Status', ...rows.map(row => row.join(','))].join('\r\n');
			const blob = new Blob(['\uFEFF', csv], { type: 'text/csv;charset=utf-8' });
			const url = URL.createObjectURL(blob);
			const link = document.createElement('a');
			link.href = url;
			link.download = `rekap-presensi-guru-${new Date().toISOString().slice(0, 10)}.csv`;
			link.click();
			URL.revokeObjectURL(url);
		});
	</script>
	<script src="{{ asset('guru css/guru-ui.js') }}?v=1"></script>
</body>
</html>
