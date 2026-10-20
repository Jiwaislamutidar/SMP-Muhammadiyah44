<div class="notification-overlay" id="notificationOverlay" aria-hidden="true"></div>
<header class="topbar">
    <div class="topbar-left">
        <button class="burger-btn" id="burgerBtn" type="button" aria-label="Buka sidebar" aria-expanded="false" aria-controls="sidebar">
            <span></span><span></span><span></span>
        </button>
        <img class="topbar-logo" src="{{ asset('logo.jpg') }}" alt="Logo SMP Muhammadiyah 44">
        <div class="brand-wrap"><span class="brand-dot"></span><span class="portal-tag">Portal Guru • SMP Muhammadiyah 44</span></div>
    </div>
    <div class="topbar-right">
        <div class="date-pill" id="liveDatetime">Memuat waktu...</div>
        <div class="notification-menu">
            <button class="bell-btn notification-toggle" id="notificationToggle" type="button" aria-label="Notifikasi" title="Notifikasi" aria-expanded="false" aria-haspopup="dialog" aria-controls="notificationPanel" data-seen-key="guru-notifikasi-{{ auth()->id() }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10.3 21a2 2 0 0 0 3.4 0"/></svg>
                <span class="badge-dot" hidden></span>
            </button>
            <div class="notification-panel" id="notificationPanel" role="dialog" aria-label="Notifikasi" hidden>
                <div class="notification-panel-header">Notifikasi</div>
                <div class="notification-list">
                    @forelse($notifikasiGuru ?? [] as $notifikasi)
                        <div class="notification-item" data-notification-signature="{{ $notifikasi['signature'] }}">
                            <span class="notification-status-dot is-{{ $notifikasi['status'] }}"></span>
                            <div class="notification-copy">
                                <div>{{ $notifikasi['title'] }}</div>
                                <div class="notification-subject">{{ $notifikasi['subject'] }}</div>
                                <div class="notification-datetime">{{ $notifikasi['detail'] }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="notification-empty">Belum ada notifikasi mengajar hari ini.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="profile-pill">
            <div class="avatar">{{ $guruInitials ?? mb_strtoupper(mb_substr($guru->nama_lengkap, 0, 1)) }}</div>
            <div class="profile-meta"><div class="name">{{ $guru->nama_lengkap }}</div><div class="role">{{ $profileRole }}</div></div>
        </div>
    </div>
</header>
