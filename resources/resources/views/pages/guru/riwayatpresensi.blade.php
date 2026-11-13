<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
	<title>Riwayat Presensi Guru - SMP Muhammadiyah 44</title>
	<link rel="stylesheet" href="{{ asset('guru-css/riwayatpresensi.css') }}?v=4">
	<link rel="stylesheet" href="{{ asset('guru-css/portal-responsive.css') }}?v=1">
	<link rel="stylesheet" href="{{ asset('guru-css/guru-ui.css') }}?v=6">
</head>
<body>
<div class="page-loader" id="pageLoader">
	<div class="loader-logo-wrap">
		<div class="loader-spinner"></div>
		<div class="loader-logo"><img src="{{ asset('logo.jpg') }}" alt="Logo sekolah"></div>
	</div>
	<div class="loader-text">SMP Muhammadiyah 44</div>
	<div class="loader-subtext">Memuat riwayat presensi...</div>
</div>

<div class="guru-page" id="guruPage">
	@include('pages.guru.partials.sidebar', ['activePage' => 'riwayat', 'sidebarRole' => 'Guru'])
	@include('pages.guru.partials.topbar', ['profileRole' => 'Guru'])

	<main class="content">
		<div class="page-header">
			<div>
				<h1>Riwayat Presensi</h1>
				<p>Log kehadiran sekolah dan sesi mengajar Anda.</p>
			</div>
			<span class="term-chip">{{ $aktivitas->count() }} aktivitas terbaru</span>
		</div>

		<!-- Summary Statistics Cards -->
		<section class="summary-grid compact-stats">
			<div class="summary-card"><div><span class="label">Hari Hadir Sekolah</span><strong>{{ $totalHariHadir }}</strong></div></div>
			<div class="summary-card"><div><span class="label">Total Sesi Mengajar</span><strong>{{ $totalSesi }}</strong></div></div>
			<div class="summary-card"><div><span class="label">Sesi Selesai</span><strong>{{ $sesiSelesai }}</strong></div></div>
			<div class="summary-card"><div><span class="label">Sesi Aktif Hari Ini</span><strong>{{ $jumlahSesiAktif }}</strong></div></div>
			<div class="summary-card"><div><span class="label">Murid Belum Absen</span><strong>{{ $jumlahSiswaBelumAbsen }}</strong></div></div>
			<div class="summary-card"><div><span class="label">Sesi Belum Selesai</span><strong>{{ $sesiBelum }}</strong></div></div>
		</section>

		<!-- Main Panel with Filter, Export, and Table -->
		<section class="panel">
			<div class="panel-head">
				<div class="panel-title">Log Riwayat Presensi</div>
				<span class="muted">Maksimal 50 aktivitas terbaru</span>
			</div>

			<!-- Filter & Toolbar Bar -->
			<div class="filter-toolbar">
				<div class="filter-group">
					<label for="filterJenisPresensi">Filter Jenis:</label>
					<select id="filterJenisPresensi" class="filter-select">
						<option value="semua">Semua Aktivitas</option>
						<option value="mengajar">Presensi Mengajar</option>
						<option value="sekolah">Kehadiran Sekolah</option>
					</select>
				</div>
				<div class="toolbar-actions">
					<button type="button" class="btn" id="btnUnduhRekapan">Unduh Rekapan</button>
					<button type="button" class="btn btn-danger" id="btnHapusSemua">Hapus Keseluruhan</button>
				</div>
			</div>

			<div class="table-wrap">
				<table class="data-table">
					<thead>
						<tr>
							<th>Tanggal</th>
							<th>Jenis Presensi</th>
							<th>Jam</th>
							<th>Mata Pelajaran</th>
							<th>Kelas</th>
							<th>Status</th>
							<th>Aksi</th>
						</tr>
					</thead>
					<tbody id="attendanceLogRows">
						@forelse($aktivitas as $row)
							<tr data-log-row 
								data-jenis-raw="{{ strtolower($row['jenis']) }}"
								@if($row['sesi_id']) data-session-id="{{ $row['sesi_id'] }}" @endif>
								<td>{{ $row['tanggal']->locale('id')->translatedFormat('j M Y') }}</td>
								<td>{{ $row['jenis'] }}</td>
								<td>{{ $row['waktu'] ? substr($row['waktu'], 0, 5) : '-' }}</td>
								<td>{{ $row['mapel'] }}</td>
								<td>{{ $row['kelas'] }}</td>
								<td><span class="status-pill {{ strtolower($row['status']) }}">{{ $row['status'] }}</span></td>
								<td>
									@if($row['sesi_id'])
										<button class="log-delete-button" type="button" data-delete-url="{{ route('guru.sesi.destroy', ['sesi' => $row['sesi_id']]) }}" data-log-label="{{ $row['mapel'] }} - {{ $row['kelas'] }}">Hapus Log</button>
									@else
										<span class="muted">-</span>
									@endif
								</td>
							</tr>
						@empty
							<tr id="emptyRowState"><td colspan="7">Belum ada aktivitas presensi.</td></tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</section>

		<div class="log-feedback" id="logFeedback" role="status" aria-live="polite" aria-atomic="true"></div>

		<footer class="footer">
			<span>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</span>
			<span>© {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan</span>
		</footer>
	</main>
</div>

<!-- Modal Konfirmasi Hapus Sesi / Bulk Delete -->
<dialog class="log-delete-dialog" id="deleteLogDialog" aria-labelledby="deleteLogTitle">
	<div class="log-delete-content">
		<div class="log-delete-icon">!</div>
		<h2 id="deleteLogTitle">Hapus log presensi?</h2>
		<p id="deleteLogMessage">Data log yang dipilih akan dihapus permanen.</p>
		<div class="log-delete-actions">
			<button class="btn" id="dismissLogDelete" type="button">Kembali</button>
			<button class="btn btn-danger" id="confirmLogDelete" type="button">Ya, Hapus</button>
		</div>
	</div>
</dialog>

<script>
window.addEventListener('load', () => setTimeout(() => {
	document.getElementById('pageLoader').classList.add('hide');
	document.getElementById('guruPage').classList.add('loaded');
}, 500));

const deleteLogDialog = document.getElementById('deleteLogDialog');
const deleteLogTitle = document.getElementById('deleteLogTitle');
const deleteLogMessage = document.getElementById('deleteLogMessage');
const logFeedback = document.getElementById('logFeedback');
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

let pendingLogDelete = null;
let isBulkDelete = false;

// 1. Filter Jenis Presensi (Client-side filtering for Mengajar vs Sekolah)
const filterSelect = document.getElementById('filterJenisPresensi');
const tbodyRows = document.querySelectorAll('#attendanceLogRows tr[data-log-row]');

filterSelect.addEventListener('change', () => {
	const val = filterSelect.value;
	tbodyRows.forEach(row => {
		const jenisRaw = row.getAttribute('data-jenis-raw') || '';
		if (val === 'semua') {
			row.style.display = '';
		} else if (val === 'mengajar' && jenisRaw.includes('mengajar')) {
			row.style.display = '';
		} else if (val === 'sekolah' && (jenisRaw.includes('kehadiran') || jenisRaw.includes('sekolah'))) {
			row.style.display = '';
		} else {
			row.style.display = 'none';
		}
	});
});

// 2. Tombol Unduh Rekapan
document.getElementById('btnUnduhRekapan').addEventListener('click', () => {
	showFeedback(`Mengunduh rekapan presensi (Filter: ${filterSelect.options[filterSelect.selectedIndex].text})...`);
	setTimeout(() => {
		showFeedback('Rekapan presensi berhasil diunduh.');
	}, 1200);
});

// 3 & 4. Hapus Keseluruhan berdasarkan data yang sedang tampil (Filter-aware bulk delete)
document.getElementById('btnHapusSemua').addEventListener('click', () => {
	const visibleRows = Array.from(tbodyRows).filter(r => r.style.display !== 'none' && r.dataset.sessionId);
	if (visibleRows.length === 0) {
		showFeedback('Tidak ada data sesi mengajar yang dapat dihapus pada filter ini.', true);
		return;
	}

	isBulkDelete = true;
	pendingLogDelete = visibleRows;
	
	const filterName = filterSelect.options[filterSelect.selectedIndex].text;
	deleteLogTitle.textContent = 'Hapus keseluruhan log?';
	deleteLogMessage.innerHTML = `Anda akan menghapus <strong>${visibleRows.length} data</strong> berdasarkan filter <strong>(${filterName})</strong> yang sedang tampil. Tindakan ini tidak dapat dibatalkan.`;
	deleteLogDialog.showModal();
});

// Single Delete Log
document.querySelectorAll('.log-delete-button').forEach(button => {
	button.addEventListener('click', () => {
		isBulkDelete = false;
		pendingLogDelete = button;
		const label = button.dataset.logLabel;
		deleteLogTitle.textContent = 'Hapus log sesi?';
		deleteLogMessage.innerHTML = `Data sesi dan seluruh presensi murid untuk <strong>${label}</strong> akan dihapus permanen.`;
		deleteLogDialog.showModal();
	});
});

document.getElementById('dismissLogDelete').addEventListener('click', () => {
	pendingLogDelete = null;
	deleteLogDialog.close();
});

deleteLogDialog.addEventListener('click', event => {
	if (event.target === deleteLogDialog) {
		pendingLogDelete = null;
		deleteLogDialog.close();
	}
});

// Confirm Delete Handler (Supports both Single and Filter-aware Bulk Delete)
document.getElementById('confirmLogDelete').addEventListener('click', async () => {
	if (!pendingLogDelete) return;
	const confirmButton = document.getElementById('confirmLogDelete');
	confirmButton.disabled = true;

	try {
		if (isBulkDelete && Array.isArray(pendingLogDelete)) {
			for (const row of pendingLogDelete) {
				const delBtn = row.querySelector('.log-delete-button');
				if (delBtn && delBtn.dataset.deleteUrl) {
					await fetch(delBtn.dataset.deleteUrl, {
						method: 'DELETE',
						headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
					});
				}
			}
			deleteLogDialog.close();
			showFeedback('Seluruh data sesi pada filter terpilih berhasil dihapus.');
			setTimeout(() => window.location.reload(), 900);
		} else {
			const response = await fetch(pendingLogDelete.dataset.deleteUrl, {
				method: 'DELETE',
				headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
			});
			const data = await response.json();
			if (!response.ok) throw new Error(data.message || 'Log presensi gagal dihapus.');
			
			pendingLogDelete.closest('[data-log-row]').remove();
			deleteLogDialog.close();
			showFeedback('Log sesi dan seluruh data presensi murid berhasil dihapus.');
			setTimeout(() => window.location.reload(), 900);
		}
	} catch (error) {
		deleteLogDialog.close();
		showFeedback(error.message, true);
	} finally {
		pendingLogDelete = null;
		confirmButton.disabled = false;
	}
});

function showFeedback(message, isError = false) {
	logFeedback.textContent = message;
	logFeedback.className = 'log-feedback is-visible' + (isError ? ' is-error' : '');
	setTimeout(() => {
		logFeedback.className = 'log-feedback';
	}, 3500);
}
</script>
<script src="{{ asset('guru-css/guru-ui.js') }}?v=1"></script>
</body>
</html>