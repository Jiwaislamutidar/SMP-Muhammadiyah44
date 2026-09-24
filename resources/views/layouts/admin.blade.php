<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title') - SMP Muhammadiyah 44</title>

  {{-- Global admin shell styles (sidebar + topbar + base) --}}
  <link rel="stylesheet" href="{{ asset('admin css/admin-shell.css') }}?v=1">
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
        <span class="nav-icon-box">🏠</span> Dashboard
      </a>
      <a href="{{ route('admin.dataguru') }}"
         class="nav-item {{ request()->routeIs('admin.dataguru') ? 'active' : '' }}"
         style="--delay:2">
        <span class="nav-icon-box">👨‍🏫</span> Data Guru
      </a>
      <a href="{{ route('admin.datamurid') }}"
         class="nav-item {{ request()->routeIs('admin.datamurid') ? 'active' : '' }}"
         style="--delay:3">
        <span class="nav-icon-box">👥</span> Data Murid
      </a>
      <a href="{{ route('admin.datakelas') }}"
         class="nav-item {{ request()->routeIs('admin.datakelas') ? 'active' : '' }}"
         style="--delay:4">
        <span class="nav-icon-box">🏫</span> Data Kelas
      </a>
      <a href="{{ route('admin.matapelajaran') }}"
         class="nav-item {{ request()->routeIs('admin.matapelajaran') ? 'active' : '' }}"
         style="--delay:5">
        <span class="nav-icon-box">📚</span> Mata Pelajaran
      </a>
      <a href="{{ route('admin.jadwalpelajaran') }}"
         class="nav-item {{ request()->routeIs('admin.jadwalpelajaran') ? 'active' : '' }}"
         style="--delay:6">
        <span class="nav-icon-box">📅</span> Jadwal Pelajaran
      </a>
      <a href="{{ route('admin.rekapabsensi') }}"
         class="nav-item {{ request()->routeIs('admin.rekapabsensi') ? 'active' : '' }}"
         style="--delay:7">
        <span class="nav-icon-box">🗂️</span> Rekap Absensi
      </a>
      <a href="{{ route('admin.profil') }}"
         class="nav-item {{ request()->routeIs('admin.profil') ? 'active' : '' }}"
         style="--delay:8">
        <span class="nav-icon-box">⚙️</span> Pengaturan
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
          <span class="nav-icon-box">🚪</span> Keluar
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
      <div>Sistem Presensi &amp; Akademik Terpadu SMP Muhammadiyah 44 Tangerang Selatan</div>
      <div>&copy; {{ date('Y') }} SMP Muhammadiyah 44 Tangerang Selatan. Seluruh hak cipta dilindungi.</div>
    </footer>
  </main>

</div>{{-- /.admin-app --}}

<script src="{{ asset('admin css/admin.js') }}?v=2"></script>
</body>
</html>
