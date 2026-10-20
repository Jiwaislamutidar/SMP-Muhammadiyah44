<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <div class="sidebar-logo"><img src="{{ asset('logo.jpg') }}" alt="Logo SMP Muhammadiyah 44"></div>
            <div>
                <div class="sidebar-title">SMP Muhammadiyah 44</div>
                <div class="sidebar-subtitle">Portal Guru</div>
            </div>
        </div>
        <button class="sidebar-close-btn" id="sidebarCloseBtn" type="button" aria-label="Tutup sidebar">✕</button>
    </div>
    <div class="sidebar-section-label">Menu</div>
    <nav class="sidebar-nav" aria-label="Menu utama">
        <a href="{{ route('guru.dashboard') }}" class="nav-item{{ $activePage === 'dashboard' ? ' active' : '' }}" @if($activePage === 'dashboard') aria-current="page" @endif style="--delay:1">
            <span class="nav-icon-box"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9 21v-6h6v6"/></svg></span>Dashboard
        </a>
        <a href="{{ route('guru.presensi-guru') }}" class="nav-item{{ $activePage === 'presensi-guru' ? ' active' : '' }}" @if($activePage === 'presensi-guru') aria-current="page" @endif style="--delay:2">
            <span class="nav-icon-box"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8M8 11h4"/><path d="m8 16 2 2 5-5"/></svg></span>Presensi Guru
        </a>
        <a href="{{ route('guru.jadwal') }}" class="nav-item{{ $activePage === 'jadwal' ? ' active' : '' }}" @if($activePage === 'jadwal') aria-current="page" @endif style="--delay:3">
            <span class="nav-icon-box"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg></span>Jadwal Mengajar
        </a>
        <a href="{{ route('guru.presensi-murid') }}" class="nav-item{{ $activePage === 'presensi-murid' ? ' active' : '' }}" @if($activePage === 'presensi-murid') aria-current="page" @endif style="--delay:4">
            <span class="nav-icon-box"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>Presensi Murid
        </a>
        <a href="{{ route('guru.riwayat-presensi') }}" class="nav-item{{ $activePage === 'riwayat' ? ' active' : '' }}" @if($activePage === 'riwayat') aria-current="page" @endif style="--delay:5">
            <span class="nav-icon-box"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>Riwayat Presensi
        </a>
        <a href="{{ route('guru.profil') }}" class="nav-item{{ $activePage === 'profil' ? ' active' : '' }}" @if($activePage === 'profil') aria-current="page" @endif style="--delay:6">
            <span class="nav-icon-box"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg></span>Profil
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-footer-user">
            <div class="avatar">{{ $guruInitials ?? mb_strtoupper(mb_substr($guru->nama_lengkap, 0, 1)) }}</div>
            <div>
                <div class="name">{{ $guru->nama_lengkap }}</div>
                <div class="role">{{ $sidebarRole }}</div>
            </div>
        </div>
        <form action="{{ route('guru.logout') }}" method="POST">
            @csrf
            <button type="submit" class="nav-item logout-item">
                <span class="nav-icon-box"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17-5-5 5-5"/><path d="M15 12H5"/></svg></span>Keluar
            </button>
        </form>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
