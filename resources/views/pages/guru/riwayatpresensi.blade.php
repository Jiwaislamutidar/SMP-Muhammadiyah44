<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
	<title>Riwayat Presensi Guru - SMP Muhammadiyah 44</title>
	<link rel="stylesheet" href="{{ asset('guru css/riwayatpresensi.css') }}?v=2">
	<link rel="stylesheet" href="{{ asset('guru css/portal-responsive.css') }}?v=1">
	<link rel="stylesheet" href="{{ asset('guru css/guru-ui.css') }}?v=3">
</head>
<body>
<div class="page-loader" id="pageLoader"><div class="loader-logo-wrap"><div class="loader-spinner"></div><div class="loader-logo"><img src="{{ asset('logo.jpg') }}" alt="Logo sekolah"></div></div><div class="loader-text">SMP Muhammadiyah 44</div><div class="loader-subtext">Memuat riwayat presensi...</div></div>
<div class="guru-page" id="guruPage">
	@include('pages.guru.partials.sidebar', ['activePage' => 'riwayat', 'sidebarRole' => 'Guru'])
	@include('pages.guru.partials.topbar', ['profileRole' => 'Guru'])
	<main class="content">
		<div class="page-header"><div><h1>Riwayat Presensi</h1><p>Log kehadiran sekolah dan sesi mengajar Anda.</p></div><span class="term-chip">{{ $aktivitas->count() }} aktivitas terbaru</span></div>
		<section class="summary-grid"><div class="summary-card"><div><span class="label">Hari Hadir Sekolah</span><strong>{{ $totalHariHadir }}</strong><small>Data presensi harian</small></div></div><div class="summary-card"><div><span class="label">Total Sesi Mengajar</span><strong>{{ $totalSesi }}</strong><small>Jadwal dengan sesi</small></div></div><div class="summary-card"><div><span class="label">Sesi Selesai</span><strong>{{ $sesiSelesai }}</strong><small>KBM ditutup</small></div></div><div class="summary-card"><div><span class="label">Sesi Aktif Hari Ini</span><strong>{{ $jumlahSesiAktif }}</strong><small>Sesi yang sedang berjalan</small></div></div><div class="summary-card"><div><span class="label">Murid Belum Absen</span><strong>{{ $jumlahSiswaBelumAbsen }}</strong><small>Di semua sesi aktif hari ini</small></div></div><div class="summary-card"><div><span class="label">Sesi Belum Selesai</span><strong>{{ $sesiBelum }}</strong><small>Perlu ditindaklanjuti</small></div></div></section>
		<section class="panel"><div class="panel-head"><div class="panel-title">Log Riwayat Presensi</div><span class="muted">Maksimal 50 aktivitas terbaru</span></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Tanggal</th><th>Jenis Presensi</th><th>Jam</th><th>Mata Pelajaran</th><th>Kelas</th><th>Status</th><th>Aksi</th></tr></thead><tbody id="attendanceLogRows">
			@forelse($aktivitas as $row)
				<tr data-log-row @if($row['sesi_id']) data-session-id="{{ $row['sesi_id'] }}" @endif><td>{{ $row['tanggal']->locale('id')->translatedFormat('j M Y') }}</td><td>{{ $row['jenis'] }}</td><td>{{ $row['waktu'] ? substr($row['waktu'], 0, 5) : '-' }}</td><td>{{ $row['mapel'] }}</td><td>{{ $row['kelas'] }}</td><td><span class="status-pill {{ strtolower($row['status']) }}">{{ $row['status'] }}</span></td><td>@if($row['sesi_id'])<button class="log-delete-button" type="button" data-delete-url="{{ route('guru.sesi.destroy', ['sesi' => $row['sesi_id']]) }}" data-log-label="{{ $row['mapel'] }} - {{ $row['kelas'] }}">Hapus Log</button>@else<span class="muted">-</span>@endif</td></tr>
			@empty<tr><td colspan="7">Belum ada aktivitas presensi.</td></tr>@endforelse
		</tbody></table></div></section>
		<div class="log-feedback" id="logFeedback" role="status" aria-live="polite" aria-atomic="true"></div>
		<footer class="footer"><span>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</span><span>© {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan</span></footer>
	</main>
</div>
<dialog class="log-delete-dialog" id="deleteLogDialog" aria-labelledby="deleteLogTitle"><div class="log-delete-content"><span class="log-delete-icon">!</span><h2 id="deleteLogTitle">Hapus log sesi?</h2><p>Data sesi dan seluruh presensi murid untuk <strong id="deleteLogLabel"></strong> akan dihapus permanen.</p><div class="log-delete-actions"><button class="log-cancel-button" id="dismissLogDelete" type="button">Kembali</button><button class="log-confirm-button" id="confirmLogDelete" type="button">Ya, Hapus Log</button></div></div></dialog>
<script>
window.addEventListener('load',()=>setTimeout(()=>{pageLoader.classList.add('hide');guruPage.classList.add('loaded')},500));
const deleteLogDialog=document.getElementById('deleteLogDialog'),deleteLogLabel=document.getElementById('deleteLogLabel'),logFeedback=document.getElementById('logFeedback'),csrfToken=document.querySelector('meta[name="csrf-token"]').content;let pendingLogDelete=null;
document.querySelectorAll('.log-delete-button').forEach(button=>button.addEventListener('click',()=>{pendingLogDelete=button;deleteLogLabel.textContent=button.dataset.logLabel;deleteLogDialog.showModal()}));
document.getElementById('dismissLogDelete').addEventListener('click',()=>{pendingLogDelete=null;deleteLogDialog.close()});
deleteLogDialog.addEventListener('click',event=>{if(event.target===deleteLogDialog){pendingLogDelete=null;deleteLogDialog.close()}});
document.getElementById('confirmLogDelete').addEventListener('click',async()=>{if(!pendingLogDelete)return;const confirmButton=document.getElementById('confirmLogDelete');confirmButton.disabled=true;try{const response=await fetch(pendingLogDelete.dataset.deleteUrl,{method:'DELETE',headers:{'Accept':'application/json','X-CSRF-TOKEN':csrfToken}});const data=await response.json();if(!response.ok)throw new Error(data.message||'Log presensi gagal dihapus.');pendingLogDelete.closest('[data-log-row]').remove();deleteLogDialog.close();logFeedback.textContent='Log sesi dan seluruh data presensi murid berhasil dihapus.';logFeedback.classList.add('is-visible');setTimeout(()=>window.location.reload(),900)}catch(error){deleteLogDialog.close();logFeedback.textContent=error.message;logFeedback.classList.add('is-error','is-visible')}finally{pendingLogDelete=null;confirmButton.disabled=false}});
</script>
<script src="{{ asset('guru css/guru-ui.js') }}?v=1"></script>
</body>
</html>