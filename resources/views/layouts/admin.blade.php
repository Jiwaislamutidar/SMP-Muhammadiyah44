<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="{{ asset('logo.jpg') }}" type="image/jpeg">
  <title>@yield('title') - SMP Muhammadiyah 44</title>

  {{-- Global admin shell styles (sidebar + topbar + base) --}}
  <link rel="stylesheet" href="{{ asset('admin css/admin-shell.css') }}?v=5">
  {{-- Shared page-level utilities --}}
  <link rel="stylesheet" href="{{ asset('admin css/admin-page.css') }}?v=5">
  {{-- Page-specific styles --}}
  @yield('styles')
</head>
<body>

{{-- ===== PAGE LOADER ===== --}}
<div class="page-loader" id="pageLoader">
  <div class="loader-logo-wrap">
    <div class="loader-spinner"></div>
    <div class="loader-logo">
      <img src="{{ asset('logo.jpg') }}" alt="Logo SMP Muhammadiyah 44">
    </div>
  </div>
  <div class="loader-text">SMP Muhammadiyah 44</div>
  <div class="loader-subtext">Admin Portal</div>
</div>

{{-- ===== MAIN APP WRAPPER ===== --}}
<div class="admin-app" id="adminApp">

  {{-- ===== SIDEBAR ===== --}}
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <a class="sidebar-brand" href="{{ route('admin.dashboard') }}">
        <div class="sidebar-logo">
          <img src="{{ asset('logo.jpg') }}" alt="Logo SMP Muhammadiyah 44">
        </div>
        <div>
          <div class="sidebar-title">SMP Muhammadiyah 44</div>
          <div class="sidebar-subtitle">Portal Admin</div>
        </div>
      </a>
      <button class="sidebar-close-btn" onclick="toggleSidebar()" aria-label="Tutup menu">✕</button>
    </div>

    <div class="sidebar-section-label">Menu Utama</div>
    <nav class="sidebar-nav">
      <a href="{{ route('admin.dashboard') }}"
         class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
         style="--delay:1">
        <span class="nav-icon-box">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            <polyline points="9 22 9 12 15 12 15 22"/>
          </svg>
        </span> Dashboard
      </a>
      <a href="{{ route('admin.dataguru') }}"
         class="nav-item {{ request()->routeIs('admin.dataguru') ? 'active' : '' }}"
         style="--delay:2">
        <span class="nav-icon-box">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/>
            <path d="M22 10v6"/>
            <path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/>
          </svg>
        </span> Data Guru
      </a>
      <a href="{{ route('admin.datamurid') }}"
         class="nav-item {{ request()->routeIs('admin.datamurid') ? 'active' : '' }}"
         style="--delay:3">
        <span class="nav-icon-box">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </span> Data Murid
      </a>
      <a href="{{ route('admin.datakelas') }}"
         class="nav-item {{ request()->routeIs('admin.datakelas') ? 'active' : '' }}"
         style="--delay:4">
        <span class="nav-icon-box">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m4 6 8-4 8 4"/>
            <path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"/>
            <path d="M14 22v-4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v4"/>
            <path d="M18 5v17"/>
            <path d="M6 5v17"/>
            <circle cx="12" cy="9" r="2"/>
          </svg>
        </span> Data Kelas
      </a>
      <a href="{{ route('admin.matapelajaran') }}"
         class="nav-item {{ request()->routeIs('admin.matapelajaran') ? 'active' : '' }}"
         style="--delay:5">
        <span class="nav-icon-box">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
          </svg>
        </span> Mata Pelajaran
      </a>
      <a href="{{ route('admin.jadwalpelajaran') }}"
         class="nav-item {{ request()->routeIs('admin.jadwalpelajaran') ? 'active' : '' }}"
         style="--delay:6">
        <span class="nav-icon-box">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
            <line x1="16" x2="16" y1="2" y2="6"/>
            <line x1="8" x2="8" y1="2" y2="6"/>
            <line x1="3" x2="21" y1="10" y2="10"/>
          </svg>
        </span> Jadwal Pelajaran
      </a>
      <a href="{{ route('admin.rekapabsensi') }}"
         class="nav-item {{ request()->routeIs('admin.rekapabsensi') ? 'active' : '' }}"
         style="--delay:7">
        <span class="nav-icon-box">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/>
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
            <path d="m9 14 2 2 4-4"/>
          </svg>
        </span> Rekap Absensi
      </a>
      <a href="{{ route('admin.profil') }}"
         class="nav-item {{ request()->routeIs('admin.profil') ? 'active' : '' }}"
         style="--delay:8">
        <span class="nav-icon-box">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </span> Pengaturan
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-footer-user">
        <div class="avatar">AD</div>
        <div>
          <div class="name">Administrator</div>
          <div class="role">admin@smpm44.sch.id</div>
        </div>
      </div>
      <form action="{{ route('admin.logout') }}" method="POST" style="margin:0;">
        @csrf
        <button type="submit" class="nav-item logout-item">
          <span class="nav-icon-box">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/>
              <line x1="21" x2="9" y1="12" y2="12"/>
            </svg>
          </span> Keluar
        </button>
      </form>
    </div>
  </aside>

  {{-- Overlay --}}
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

  {{-- ===== TOPBAR ===== --}}
  <header class="topbar">
    <div class="topbar-left">
      <button class="burger-btn" id="burgerBtn" onclick="toggleSidebar()" aria-label="Buka menu">
        <span></span><span></span><span></span>
      </button>
      <div class="brand-wrap">
        <div class="brand-dot"></div>
        <div class="portal-tag">Admin Portal &bull; SMP Muhammadiyah 44</div>
      </div>
    </div>
    <div class="topbar-right">
      <div class="date-pill" id="liveDatetime">Memuat waktu...</div>
      <button class="bell-btn" aria-label="Notifikasi">
        🔔
        <span class="badge"></span>
      </button>
      <div class="profile-pill">
        <div class="avatar">AD</div>
        <div class="profile-meta">
          <div class="name">Administrator</div>
          <div class="role">admin@smpm44.sch.id</div>
        </div>
      </div>
    </div>
  </header>

  {{-- ===== MAIN CONTENT ===== --}}
  <main class="content admin-content">
    @yield('content')

    <footer class="footer">
      <div>SMP Muhammadiyah 44 &mdash; Portal Admin</div>
      <div>&copy; {{ date('Y') }} Sistem Presensi &amp; Akademik</div>
    </footer>
  </main>

</div>{{-- /.admin-app --}}

<script src="{{ asset('admin css/admin.js') }}?v={{ filemtime(public_path('admin css/admin.js')) }}"></script>
</body>
</html>
