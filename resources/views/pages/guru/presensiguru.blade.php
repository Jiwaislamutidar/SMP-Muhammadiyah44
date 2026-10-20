<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg"><title>Presensi Guru - SMP Muhammadiyah 44</title><link rel="stylesheet" href="{{ asset('guru css/presensiguru.css') }}?v=4"><link rel="stylesheet" href="{{ asset('guru css/portal-responsive.css') }}?v=1"><link rel="stylesheet" href="{{ asset('guru css/guru-ui.css') }}?v=1"></head>
<body>
<div class="page-loader" id="pageLoader"><div class="loader-logo-wrap"><div class="loader-spinner"></div><div class="loader-logo"><img src="{{ asset('logo.jpg') }}" alt="Logo sekolah"></div></div><div class="loader-text">SMP Muhammadiyah 44</div><div class="loader-subtext">Memuat presensi guru...</div></div>
@include('pages.guru.partials.sidebar', ['activePage' => 'presensi-guru', 'sidebarRole' => 'Guru'])
@include('pages.guru.partials.topbar', ['profileRole' => 'Guru'])
<main class="content">
	<div class="page-header"><div><h1>Presensi Guru</h1><p>Kehadiran sekolah untuk {{ now()->locale('id')->translatedFormat('l, j F Y') }}.</p></div></div>
	@if(session('success'))<div class="panel" role="status">{{ session('success') }}</div>@endif
	@if($errors->any())<div class="panel" role="alert">{{ $errors->first() }}</div>@endif
	<section class="panel"><div class="panel-head"><div class="panel-title">Kehadiran Sekolah</div></div>
		<div class="cards-two attendance-cards">
			<div class="attendance-card"><div class="card-top"><div><span class="muted">ABSEN DATANG</span><h3>Absen Masuk Sekolah</h3><small>{{ $presensi?->jam_masuk ? 'Presensi tersimpan hari ini.' : 'Ambil foto sebagai bukti kehadiran.' }}</small></div><span class="status-pill {{ $presensi?->jam_masuk ? 'hadir' : 'izin' }}">{{ $presensi?->jam_masuk ? 'Tercatat' : 'Belum Absen' }}</span></div>
				<div class="big-time">{{ $presensi?->jam_masuk ? substr($presensi->jam_masuk, 0, 5) : '--:--' }} <small>WIB</small></div>
				@if($presensi?->foto_masuk)<p><a href="{{ asset('storage/'.$presensi->foto_masuk) }}" target="_blank" rel="noopener">Lihat foto bukti</a></p>@endif
				@if($presensi?->jam_masuk)<button class="attendance-action attendance-done" type="button" disabled>✓ Sudah Absen ({{ substr($presensi->jam_masuk, 0, 5) }} WIB)</button>@else<button class="attendance-action" type="button" data-title="Absen Masuk Sekolah" data-url="{{ route('guru.presensi.masuk') }}">📸 Absen Masuk Sekolah</button>@endif
			</div>
			<div class="attendance-card"><div class="card-top"><div><span class="muted">ABSEN KEPULANGAN</span><h3>Absen Pulang Sekolah</h3><small>{{ $presensi?->jam_pulang ? 'Presensi tersimpan hari ini.' : ($presensi?->jam_masuk ? 'Ambil foto sebagai bukti kepulangan.' : 'Absen masuk lebih dahulu.') }}</small></div><span class="status-pill {{ $presensi?->jam_pulang ? 'hadir' : 'izin' }}">{{ $presensi?->jam_pulang ? 'Tercatat' : 'Belum Absen' }}</span></div>
				<div class="big-time">{{ $presensi?->jam_pulang ? substr($presensi->jam_pulang, 0, 5) : '--:--' }} <small>WIB</small></div>
				@if($presensi?->foto_pulang)<p><a href="{{ asset('storage/'.$presensi->foto_pulang) }}" target="_blank" rel="noopener">Lihat foto bukti</a></p>@endif
				@if($presensi?->jam_pulang)<button class="attendance-action attendance-done" type="button" disabled>✓ Sudah Absen ({{ substr($presensi->jam_pulang, 0, 5) }} WIB)</button>@else<button class="attendance-action" type="button" data-title="Absen Pulang Sekolah" data-url="{{ route('guru.presensi.pulang') }}" {{ $presensi?->jam_masuk ? '' : 'disabled' }}>📸 Absen Pulang Sekolah</button>@endif
			</div>
		</div>
	</section>
	<section class="panel" style="margin-top:20px"><div class="panel-head"><div class="panel-title">Jadwal Mengajar Hari Ini</div><span class="muted">{{ $jadwals->count() }} jadwal</span></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Jam KBM</th><th>Mata Pelajaran</th><th>Kelas</th><th>Status Sesi</th><th>Aksi</th></tr></thead><tbody>
		@forelse($jadwals as $jadwal)@php($jadwalSesi = $jadwal->sesiPelajarans->first())<tr><td>{{ substr($jadwal->jam_mulai, 0, 5) }}-{{ substr($jadwal->jam_selesai, 0, 5) }}</td><td>{{ $jadwal->mapel->nama_mapel }}</td><td>{{ $jadwal->kelas->nama_kelas }}</td><td>{{ $jadwalSesi?->status_sesi ?? 'Belum' }}</td><td><a class="btn {{ $jadwalSesi?->status_sesi === 'Berlangsung' ? 'btn-primary' : '' }}" href="{{ route('guru.presensi-murid') }}">Kelola Presensi</a></td></tr>@empty<tr><td colspan="5">Tidak ada jadwal mengajar untuk hari ini.</td></tr>@endforelse
	</tbody></table></div></section>
	<footer class="footer"><span>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</span><span>© {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan</span></footer>
</main></div>
<dialog class="photo-modal" id="photoModal" aria-labelledby="photoModalTitle">
	<form id="attendanceForm" method="POST" enctype="multipart/form-data">
		@csrf
		<div class="photo-modal-head"><div><h2 id="photoModalTitle">Absen</h2><p>Ambil foto atau pilih gambar dari perangkat.</p></div><button class="modal-close" type="button" id="closePhotoModal" aria-label="Tutup">×</button></div>
		<div class="camera-preview"><video id="cameraPreview" autoplay playsinline hidden></video><img id="photoPreview" alt="Pratinjau foto" hidden><div id="cameraPlaceholder">Pratinjau foto akan muncul di sini.</div></div>
		<div class="photo-controls"><button class="btn" id="startCamera" type="button">Buka Kamera</button><button class="btn" id="takePhoto" type="button" disabled>Ambil Foto</button><label class="btn upload-label" for="photoInput">Pilih Foto</label><input id="photoInput" type="file" name="foto" accept="image/jpeg,image/png,image/webp" capture="environment" required></div>
		<p class="photo-error" id="photoError" role="alert"></p>
		<div class="photo-modal-actions"><button class="btn" type="button" id="cancelPhoto">Batal</button><button class="btn btn-primary attendance-submit" id="submitAttendance" type="submit" disabled>Kirim Presensi</button></div>
	</form>
</dialog>
<script>
window.addEventListener('load',()=>setTimeout(()=>{pageLoader.classList.add('hide');guruPage.classList.add('loaded')},500));
const photoModal=document.getElementById('photoModal'),attendanceForm=document.getElementById('attendanceForm'),photoInput=document.getElementById('photoInput'),photoPreview=document.getElementById('photoPreview'),cameraPreview=document.getElementById('cameraPreview'),cameraPlaceholder=document.getElementById('cameraPlaceholder'),photoError=document.getElementById('photoError'),submitAttendance=document.getElementById('submitAttendance');
let cameraStream=null,previewUrl=null;
function showPhoto(file){if(!file)return;if(previewUrl)URL.revokeObjectURL(previewUrl);previewUrl=URL.createObjectURL(file);photoPreview.src=previewUrl;photoPreview.hidden=false;cameraPlaceholder.hidden=true;submitAttendance.disabled=false;photoError.textContent=''}
function stopPreviewCamera(){cameraStream?.getTracks().forEach(track=>track.stop());cameraStream=null;cameraPreview.srcObject=null;cameraPreview.hidden=true;document.getElementById('takePhoto').disabled=true}
document.querySelectorAll('.attendance-action:not(.attendance-done)').forEach(button=>button.addEventListener('click',()=>{attendanceForm.action=button.dataset.url;document.getElementById('photoModalTitle').textContent=button.dataset.title;photoModal.showModal()}));
document.getElementById('startCamera').addEventListener('click',async()=>{try{if(!navigator.mediaDevices?.getUserMedia)throw new Error('Kamera tidak tersedia. Pilih foto dari perangkat.');cameraStream=await navigator.mediaDevices.getUserMedia({video:{facingMode:'user'},audio:false});cameraPreview.srcObject=cameraStream;cameraPreview.hidden=false;cameraPlaceholder.hidden=true;document.getElementById('takePhoto').disabled=true;cameraPreview.onloadedmetadata=()=>document.getElementById('takePhoto').disabled=false;await cameraPreview.play();photoError.textContent=''}catch(error){photoError.textContent=error.message||'Kamera tidak dapat dibuka. Pilih foto dari perangkat.'}});
document.getElementById('takePhoto').addEventListener('click',()=>{const canvas=document.createElement('canvas');canvas.width=cameraPreview.videoWidth;canvas.height=cameraPreview.videoHeight;canvas.getContext('2d').drawImage(cameraPreview,0,0);canvas.toBlob(blob=>{if(!blob)return;const transfer=new DataTransfer();transfer.items.add(new File([blob],'presensi-guru.jpg',{type:'image/jpeg'}));photoInput.files=transfer.files;showPhoto(photoInput.files[0]);stopPreviewCamera()},'image/jpeg',.9)});
photoInput.addEventListener('change',()=>{if(photoInput.files[0]){stopPreviewCamera();showPhoto(photoInput.files[0])}});
function closePhotoModal(){stopPreviewCamera();photoModal.close();attendanceForm.reset();photoPreview.hidden=true;cameraPlaceholder.hidden=false;submitAttendance.disabled=true;if(previewUrl){URL.revokeObjectURL(previewUrl);previewUrl=null}}
document.getElementById('closePhotoModal').addEventListener('click',closePhotoModal);document.getElementById('cancelPhoto').addEventListener('click',closePhotoModal);photoModal.addEventListener('click',event=>{if(event.target===photoModal)closePhotoModal()});photoModal.addEventListener('close',stopPreviewCamera);
attendanceForm.addEventListener('submit',event=>{if(!photoInput.files.length){event.preventDefault();photoError.textContent='Pilih atau ambil foto sebelum mengirim.';return}submitAttendance.disabled=true;submitAttendance.textContent='Mengirim...'});
</script>
<script src="{{ asset('guru css/guru-ui.js') }}?v=1"></script>
</body></html>
