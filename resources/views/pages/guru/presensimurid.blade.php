<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
  <title>Presensi Murid - SMP Muhammadiyah 44</title>
  <link rel="stylesheet" href="{{ asset('guru css/presensimurid.css') }}?v=1">
</head>
<body>
<div class="page-loader" id="pageLoader"><div class="loader-logo-wrap"><div class="loader-spinner"></div><div class="loader-logo"><img src="{{ asset('logo.jpg') }}" alt="Logo sekolah"></div></div><div class="loader-text">SMP Muhammadiyah 44</div><div class="loader-subtext">Memuat presensi murid...</div></div>
<div class="guru-page" id="guruPage">
  <aside class="sidebar" id="sidebar"><div class="sidebar-header"><div class="sidebar-brand"><div class="sidebar-logo"><img src="{{ asset('logo.jpg') }}" alt="Logo sekolah"></div><div><div class="sidebar-title">SMP Muhammadiyah 44</div><div class="sidebar-subtitle">Portal Guru</div></div></div><button class="sidebar-close-btn" onclick="toggleSidebar()">✕</button></div><div class="sidebar-section-label">Menu</div><nav class="sidebar-nav">
    <a class="nav-item" style="--delay:1" href="{{ route('guru.dashboard') }}"><span class="nav-icon-box">🏠</span>Dashboard</a><a class="nav-item" style="--delay:2" href="{{ route('guru.presensi-guru') }}"><span class="nav-icon-box">🧾</span>Presensi Guru</a><a class="nav-item" style="--delay:3" href="{{ route('guru.jadwal') }}"><span class="nav-icon-box">📅</span>Jadwal Mengajar</a><a class="nav-item active" style="--delay:4" href="{{ route('guru.presensi-murid') }}"><span class="nav-icon-box">👥</span>Presensi Murid</a><a class="nav-item" style="--delay:5" href="{{ route('guru.riwayat-presensi') }}"><span class="nav-icon-box">🕘</span>Riwayat Presensi</a><a class="nav-item" style="--delay:6" href="{{ route('guru.profil') }}"><span class="nav-icon-box">👤</span>Profil</a>
  </nav><div class="sidebar-footer"><div class="sidebar-footer-user"><div class="avatar">AF</div><div><div class="name">Ust. Ahmad Fauzi</div><div class="role">Guru Mata Pelajaran</div></div></div><form action="{{ route('guru.logout') }}" method="POST">@csrf<button class="nav-item logout-item" type="submit"><span class="nav-icon-box">🚪</span>Keluar</button></form></div></aside><div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
  <header class="topbar"><div class="topbar-left"><button class="burger-btn" id="burgerBtn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="brand-wrap"><span class="brand-dot"></span><span class="portal-tag">Portal Guru • SMP Muhammadiyah 44</span></div></div><div class="topbar-right"><div class="date-pill" id="liveDatetime">Memuat waktu...</div><div class="bell-btn">🔔<span class="badge"></span></div><div class="profile-pill"><div class="avatar">AF</div><div class="profile-meta"><div class="name">Ust. Ahmad Fauzi, S.Pd.</div><div class="role">Guru IPA &amp; Matematika</div></div></div></div></header>
  <main class="content">
    <div class="page-header"><div><h1>Presensi Murid</h1><p>Kelola sesi mengajar dan kehadiran kelas hari ini.</p></div><span class="term-chip">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</span></div>
    @if(session('success'))<div class="panel" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="panel" role="alert">{{ $errors->first() }}</div>@endif
    <section class="panel" style="margin-bottom:20px">
      <div class="panel-head"><div class="panel-title">Jadwal Mengajar Hari Ini</div><span class="muted">{{ $jadwals->count() }} jadwal dari database</span></div>
      <div class="table-wrap"><table class="data-table"><thead><tr><th>Jam</th><th>Mata Pelajaran</th><th>Kelas</th><th>Status Sesi</th><th>Aksi</th></tr></thead><tbody>
        @forelse($jadwals as $jadwal)
          @php($jadwalSesi = $jadwal->sesiPelajarans->first())
          <tr><td>{{ substr($jadwal->jam_mulai, 0, 5) }}-{{ substr($jadwal->jam_selesai, 0, 5) }}</td><td>{{ $jadwal->mapel->nama_mapel }}</td><td>{{ $jadwal->kelas->nama_kelas }}</td><td>{{ $jadwalSesi?->status_sesi ?? 'Belum' }}</td><td>
            @if($jadwalSesi?->status_sesi === 'Berlangsung')<a class="btn btn-primary" href="{{ route('guru.presensi-murid', ['sesi' => $jadwalSesi->id]) }}">Kelola Sesi</a>
            @elseif($jadwalSesi?->status_sesi !== 'Selesai')<button class="btn btn-primary open-session" data-url="{{ route('guru.sesi.open', ['jadwal' => $jadwal->id]) }}">Buka Sesi</button>
            @else<span class="muted">Selesai</span>@endif
          </td></tr>
        @empty<tr><td colspan="5">Tidak ada jadwal mengajar untuk hari ini.</td></tr>@endforelse
      </tbody></table></div>
    </section>
    <section class="qr-layout" id="sessionPanel" data-session-id="{{ $sesi?->id }}" data-status-url="{{ $sesi ? route('guru.sesi.status', ['sesi' => $sesi->id]) : '' }}" data-qr-template="{{ route('guru.sesi.qr', ['sesi' => '__ID__']) }}" data-close-template="{{ route('guru.sesi.close', ['sesi' => '__ID__']) }}" data-manual-template="{{ route('guru.presensi-murid.update', ['presensi' => '__ID__']) }}">
      <div class="panel"><div class="panel-head"><div class="panel-title">QR Code Sesi</div><span class="status-pill hadir" id="sessionStatus">{{ $sesi?->status_sesi ?? 'Belum ada sesi' }}</span></div>
        <div class="qr-box" id="qrBox">
          @if($sesi?->status_sesi === 'Berlangsung' && $sesi->qr_token && $sesi->qr_expires_at?->isFuture())
            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(220)->margin(1)->generate($sesi->qr_token) !!}
          @else<p>Pilih jadwal lalu buka sesi untuk membuat QR Code.</p>@endif
          <strong id="sessionTitle">{{ $sesi ? $sesi->jadwal->mapel->nama_mapel.' • '.$sesi->jadwal->kelas->nama_kelas : 'Belum ada sesi aktif' }}</strong>
          <small id="qrExpiry">{{ $sesi?->qr_expires_at?->format('H:i:s') ?? '-' }}</small>
        </div>
        <div class="panel-head" style="border:0;padding-bottom:12px"><span class="muted" id="attendanceCounts">-</span><div><button class="btn btn-primary" id="refreshQr" type="button" {{ $sesi?->status_sesi === 'Berlangsung' ? '' : 'disabled' }}>Perbarui QR</button> <button class="btn" id="closeSession" type="button" {{ $sesi?->status_sesi === 'Berlangsung' ? '' : 'disabled' }}>Tutup Sesi</button></div></div>
      </div>
      <div class="panel"><div class="panel-head"><div class="panel-title">Daftar Presensi Murid</div><span class="muted">Status tersinkron otomatis</span></div><div class="student-grid" id="studentList"><p style="padding:20px">Belum ada data sesi.</p></div></div>
    </section>
    <div id="feedback" role="status" aria-live="polite"></div>
    <footer class="footer"><span>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</span><span>© {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan</span></footer>
  </main>
    <footer class="footer"><span>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</span><span>© {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan</span></footer>
  </main>
</div>
<script>
window.addEventListener('load',()=>setTimeout(()=>{pageLoader.classList.add('hide');guruPage.classList.add('loaded')},500));
function toggleSidebar(){sidebar.classList.toggle('open');sidebarOverlay.classList.toggle('show');burgerBtn.classList.toggle('active')}
function updateLiveDatetime(){const n=new Date(),d=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'],m=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],p=x=>String(x).padStart(2,'0');liveDatetime.textContent=`${d[n.getDay()]}, ${n.getDate()} ${m[n.getMonth()]} ${n.getFullYear()} | ${p(n.getHours())}:${p(n.getMinutes())}:${p(n.getSeconds())} WIB`}
updateLiveDatetime();setInterval(updateLiveDatetime,1000);
const panel=document.getElementById('sessionPanel'),feedback=document.getElementById('feedback'),csrf=document.querySelector('meta[name="csrf-token"]')?.content;
async function requestJson(url,method='GET',body=null){const response=await fetch(url,{method,headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:body?JSON.stringify(body):undefined});const data=await response.json();if(!response.ok)throw new Error(data.message||Object.values(data.errors||{}).flat()[0]||'Permintaan gagal.');return data}
function showError(error){feedback.textContent=error.message}
function renderStudents(rows){const list=document.getElementById('studentList');list.replaceChildren();const counts={Hadir:0,Izin:0,Sakit:0,Alfa:0,'Belum Absen':0};rows.forEach(row=>{counts[row.status]=(counts[row.status]||0)+1;const line=document.createElement('div');line.className='student-row';line.innerHTML='<div><span class="student-name"></span><span class="student-id"></span></div><div class="radio-group"></div>';line.querySelector('.student-name').textContent=row.nama;line.querySelector('.student-id').textContent=row.nisn;const radios=line.querySelector('.radio-group');['Hadir','Izin','Sakit','Alfa'].forEach(status=>{const label=document.createElement('label'),input=document.createElement('input');input.type='radio';input.name=`status-${row.id}`;input.value=status;input.checked=row.status===status;input.disabled=document.getElementById('sessionStatus').textContent!=='Berlangsung';input.addEventListener('change',async()=>{try{await requestJson(panel.dataset.manualTemplate.replace('__ID__',row.id),'PUT',{status});await refreshStatus()}catch(error){showError(error)}});label.append(input,document.createTextNode(` ${status}`));radios.append(label)});list.append(line)});document.getElementById('attendanceCounts').textContent=`Hadir ${counts.Hadir||0} • Izin ${counts.Izin||0} • Sakit ${counts.Sakit||0} • Alfa ${counts.Alfa||0} • Belum ${counts['Belum Absen']||0}`}
function updateState(data){document.getElementById('sessionStatus').textContent=data.status_sesi;document.getElementById('refreshQr').disabled=data.status_sesi!=='Berlangsung';document.getElementById('closeSession').disabled=data.status_sesi!=='Berlangsung';document.getElementById('qrExpiry').textContent=data.qr_expires_at?new Date(data.qr_expires_at).toLocaleTimeString('id-ID'):'QR tidak aktif';renderStudents(data.presensi||[])}
async function refreshStatus(){if(!panel.dataset.statusUrl)return;try{updateState(await requestJson(panel.dataset.statusUrl))}catch(error){showError(error)}}
document.querySelectorAll('.open-session').forEach(button=>button.addEventListener('click',async()=>{try{const data=await requestJson(button.dataset.url,'POST');panel.dataset.sessionId=data.id;panel.dataset.statusUrl=button.dataset.url.replace(/\/buka$/,'/status').replace(/\/sesi\/([^/]+)\/buka$/,'/sesi/'+data.id+'/status');document.getElementById('sessionTitle').textContent=`${data.jadwal.mapel} • ${data.jadwal.kelas}`;document.getElementById('qrBox').querySelector('svg')?.remove();document.getElementById('qrBox').insertAdjacentHTML('afterbegin',data.qr_svg);updateState(data);feedback.textContent='Sesi berhasil dibuka.'}catch(error){showError(error)}}));
document.getElementById('refreshQr').addEventListener('click',async()=>{try{const data=await requestJson(panel.dataset.qrTemplate.replace('__ID__',panel.dataset.sessionId),'POST');document.getElementById('qrBox').querySelector('svg')?.remove();document.getElementById('qrBox').insertAdjacentHTML('afterbegin',data.qr_svg);updateState(data);feedback.textContent='QR Code diperbarui.'}catch(error){showError(error)}});
document.getElementById('closeSession').addEventListener('click',async()=>{if(!confirm('Tutup sesi dan tandai murid yang belum absen sebagai Alfa?'))return;try{const data=await requestJson(panel.dataset.closeTemplate.replace('__ID__',panel.dataset.sessionId),'POST');updateState(data);document.getElementById('qrBox').querySelector('svg')?.remove();feedback.textContent='Sesi ditutup. Murid yang belum absen ditandai Alfa.'}catch(error){showError(error)}});
refreshStatus();setInterval(refreshStatus,5000);
</script>
</body></html>
