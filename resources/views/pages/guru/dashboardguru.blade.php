<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Guru - SMP Muhammadiyah 44</title>
  <link rel="stylesheet" href="{{ asset('guru css/dashboard.css') }}?v=1">
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "Inter", "Segoe UI", sans-serif;
      background: #f7f9f8;
      color: #17211c;
    }

    .dashboard-guru {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background: linear-gradient(180deg, #f7f9f8 0%, #f7f9f8 100%);
    }

    .topbar {
      background: #ffffff;
      border-bottom: 1px solid #e4eae6;
      height: 68px;
      padding: 0 32px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .topbar-left, .topbar-right {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .burger-btn {
      width: 36px;
      height: 36px;
      border: 1px solid #e4eae6;
      background: #fff;
      border-radius: 8px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 4px;
      cursor: pointer;
    }

    .burger-btn span {
      width: 16px;
      height: 2px;
      background: #17211c;
      border-radius: 2px;
      display: block;
    }

    .brand-wrap {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .brand-dot {
      width: 10px;
      height: 10px;
      border-radius: 999px;
      background: #16a66a;
      box-shadow: 0 0 0 3px rgba(22, 166, 106, 0.12);
    }

    .portal-tag {
      padding: 4px 10px;
      background: #e8f6ef;
      border-radius: 999px;
      color: #087443;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .6px;
      text-transform: uppercase;
    }

    .date-pill {
      display: flex;
      align-items: center;
      gap: 8px;
      background: #f7f9f8;
      border: 1px solid #e4eae6;
      border-radius: 12px;
      padding: 6px 14px;
      font-size: 12px;
      color: #17211c;
    }

    .date-pill .sep {
      color: #6b756f;
      font-weight: 600;
    }

    .date-pill .time {
      color: #087443;
      font-weight: 700;
    }

    .bell-btn {
      width: 36px;
      height: 36px;
      border: 1px solid #e4eae6;
      background: #fff;
      border-radius: 8px;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
    }

    .bell-btn .badge {
      position: absolute;
      right: 7px;
      top: 7px;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #ef4444;
      border: 1px solid #fff;
    }

    .profile-pill {
      display: flex;
      align-items: center;
      gap: 10px;
      padding-left: 8px;
      border-left: 1px solid #e4eae6;
    }

    .avatar {
      width: 34px;
      height: 34px;
      border-radius: 8px;
      background: #087443;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 700;
    }

    .profile-meta {
      display: flex;
      flex-direction: column;
      gap: 1px;
    }

    .profile-meta .name {
      font-size: 12px;
      font-weight: 700;
      color: #17211c;
    }

    .profile-meta .role {
      font-size: 11px;
      color: #6b756f;
    }

    .content {
      flex: 1;
      padding: 28px 48px 20px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      gap: 24px;
    }

    .page-header {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .page-header h1 {
      margin: 0;
      font-size: 30px;
      line-height: 1.2;
      letter-spacing: -0.75px;
      font-weight: 700;
      color: #17211c;
    }

    .page-header p {
      margin: 0;
      color: #6b756f;
      font-size: 14px;
    }

    .stats-row {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 24px;
    }

    .stat-card {
      background: #fff;
      border: 1px solid #e4eae6;
      border-radius: 16px;
      padding: 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }

    .stat-left {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .icon-wrap {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #e8f6ef;
      font-size: 22px;
    }

    .icon-wrap.alt {
      background: #f7f9f8;
      border: 1px solid #e4eae6;
    }

    .stat-meta {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .stat-meta .label {
      font-size: 12px;
      color: #6b756f;
    }

    .stat-meta .value {
      font-size: 20px;
      font-weight: 700;
      color: #17211c;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border-radius: 999px;
      padding: 6px 14px;
      font-size: 12px;
      font-weight: 600;
      border: 1px solid rgba(22, 166, 106, 0.2);
      background: #e8f6ef;
      color: #087443;
    }

    .status-badge .dot {
      width: 8px;
      height: 8px;
      background: #16a66a;
      border-radius: 50%;
    }

    .status-badge.pending {
      background: #f3f4f6;
      border-color: #e5e7eb;
      color: #6b756f;
    }

    .status-badge.pending .dot {
      background: #9ca3af;
    }

    .main-grid {
      display: grid;
      grid-template-columns: 7fr 5fr;
      gap: 24px;
      align-items: start;
    }

    .panel {
      background: #fff;
      border: 1px solid #e4eae6;
      border-radius: 16px;
      padding: 24px;
    }

    .panel-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding-bottom: 14px;
      border-bottom: 1px solid #e4eae6;
      margin-bottom: 14px;
    }

    .panel-title {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #17211c;
      font-size: 14px;
      font-weight: 700;
      letter-spacing: .35px;
      text-transform: uppercase;
    }

    .panel-title .bar {
      width: 8px;
      height: 16px;
      border-radius: 999px;
      background: #087443;
      display: inline-block;
    }

    .mini-label {
      font-size: 12px;
      color: #6b756f;
    }

    .schedule-table {
      width: 100%;
      border-collapse: collapse;
      overflow: hidden;
      border-radius: 12px;
    }

    .schedule-table thead th {
      background: #f8faf9;
      text-align: left;
      font-size: 12px;
      letter-spacing: .6px;
      text-transform: uppercase;
      color: #6b756f;
      font-weight: 700;
      padding: 12px 14px;
      border-bottom: 1px solid #e4eae6;
    }

    .schedule-table tbody td {
      padding: 16px 14px;
      border-bottom: 1px solid #e4eae6;
      font-size: 12px;
      color: #17211c;
      vertical-align: middle;
    }

    .schedule-table tbody tr.active-row {
      background: #f0fdf4;
      border-left: 4px solid #16a66a;
    }

    .schedule-table tbody tr.active-row td:first-child {
      padding-left: 12px;
    }

    .pill-soft {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #f3f4f6;
      border-radius: 999px;
      color: #6b756f;
      padding: 2px 10px;
      font-size: 11px;
      font-weight: 600;
    }

    .pill-live {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #e8f6ef;
      border-radius: 999px;
      color: #087443;
      padding: 2px 10px;
      font-size: 11px;
      font-weight: 700;
    }

    .pill-live .dot {
      width: 6px;
      height: 6px;
      margin-right: 6px;
      border-radius: 50%;
      background: #16a66a;
      display: inline-block;
    }

    .session-footer {
      margin-top: 16px;
      padding-top: 14px;
      border-top: 1px solid #e4eae6;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      font-size: 12px;
      color: #6b756f;
    }

    .session-footer strong {
      color: #087443;
    }

    .right-stack {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .active-session {
      background: #ffffff;
      border: 2px solid #16a66a;
      border-radius: 16px;
      box-shadow: 0 1px 2px rgba(0,0,0,0.05);
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .session-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
    }

    .session-label {
      background: #e8f6ef;
      border-radius: 6px;
      padding: 4px 10px;
      color: #087443;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .55px;
      text-transform: uppercase;
    }

    .live-status {
      display: flex;
      align-items: center;
      gap: 6px;
      color: #16a66a;
      font-size: 12px;
      font-weight: 600;
    }

    .live-status .dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #16a66a;
    }

    .session-main {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 14px;
    }

    .session-main h3 {
      margin: 0 0 6px;
      font-size: 20px;
      color: #17211c;
    }

    .session-main .meta {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      font-size: 12px;
      color: #6b756f;
    }

    .session-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: #e8f6ef;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
    }

    .primary-btn {
      background: #087443;
      border: none;
      border-radius: 12px;
      padding: 16px 16px 12px;
      color: #fff;
      font-weight: 700;
      font-size: 12px;
      cursor: pointer;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .summary-panel {
      background: #fff;
      border: 1px solid #e4eae6;
      border-radius: 16px;
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .summary-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .summary-panel h4 {
      margin: 0;
      font-size: 12px;
      letter-spacing: .3px;
      text-transform: uppercase;
      color: #17211c;
    }

    .green-badge {
      background: #e8f6ef;
      color: #087443;
      border-radius: 999px;
      padding: 2px 10px;
      font-size: 11px;
      font-weight: 600;
    }

    .summary-body {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 4px 0;
    }

    .summary-body .count {
      display: flex;
      align-items: baseline;
      gap: 6px;
      color: #087443;
      font-weight: 700;
    }

    .summary-body .count strong {
      font-size: 24px;
      line-height: 1;
    }

    .summary-body .count span {
      font-size: 12px;
      color: #6b756f;
    }

    .progress {
      width: 96px;
      height: 8px;
      background: #f3f4f6;
      border-radius: 999px;
      overflow: hidden;
      position: relative;
    }

    .progress > span {
      position: absolute;
      inset: 0 auto 0 0;
      width: 87.5%;
      background: #16a66a;
      border-radius: 999px;
    }

    .secondary-btn {
      background: #f7f9f8;
      border: 1px solid #e4eae6;
      border-radius: 12px;
      padding: 10px 16px;
      color: #087443;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      width: 100%;
    }

    .footer {
      border-top: 1px solid #e4eae6;
      padding: 20px 0 8px;
      display: flex;
      justify-content: space-between;
      gap: 12px;
      color: #6b756f;
      font-size: 12px;
    }

    @media (max-width: 980px) {
      .main-grid {
        grid-template-columns: 1fr;
      }
      .stats-row {
        grid-template-columns: 1fr;
      }
      .content {
        padding: 20px 24px 16px;
      }
      .topbar {
        padding: 0 20px;
      }
      .footer {
        flex-direction: column;
        align-items: flex-start;
      }
    }

    @media (max-width: 640px) {
      .topbar {
        height: auto;
        padding: 12px 16px;
        flex-wrap: wrap;
        gap: 12px;
      }
      .topbar-left, .topbar-right {
        width: 100%;
        justify-content: space-between;
      }
      .portal-tag {
        display: none;
      }
      .page-header h1 {
        font-size: 24px;
      }
      .content {
        padding: 18px 16px 14px;
      }
      .stat-card, .panel, .active-session, .summary-panel {
        padding: 16px;
      }
      .session-main {
        flex-direction: column;
      }
      .schedule-table {
        display: block;
        overflow-x: auto;
      }
    }
  </style>
</head>
<body>
  <div class="dashboard-guru">
    <header class="topbar">
      <div class="topbar-left">
        <button class="burger-btn" aria-label="Menu">
          <span></span>
          <span></span>
          <span></span>
        </button>

        <div class="brand-wrap">
          <div class="brand-dot"></div>
          <div class="portal-tag">Portal Guru • SMP Muhammadiyah 44</div>
        </div>
      </div>

      <div class="topbar-right">
        <div class="date-pill">
          <span>Senin, 24 Maret 2025</span>
          <span class="sep">|</span>
          <span class="time">08:45 WIB</span>
        </div>

        <div class="bell-btn" aria-label="Notifikasi">
          🔔
          <span class="badge"></span>
        </div>

        <div class="profile-pill">
          <div class="avatar">AF</div>
          <div class="profile-meta">
            <div class="name">Ust. Ahmad Fauzi, S.Pd.</div>
            <div class="role">Guru IPA &amp; Matematika</div>
          </div>
        </div>
      </div>
    </header>

    <main class="content">
      <div class="page-header">
        <h1>Selamat Datang, Ustadz Ahmad</h1>
        <p>Berikut informasi jadwal dan presensi Anda hari ini.</p>
      </div>

      <section class="stats-row">
        <div class="stat-card">
          <div class="stat-left">
            <div class="icon-wrap">🏫</div>
            <div class="stat-meta">
              <div class="label">Masuk Sekolah</div>
              <div class="value">06.45 WIB</div>
            </div>
          </div>
          <div class="status-badge"><span class="dot"></span> Sudah Absen</div>
        </div>

        <div class="stat-card">
          <div class="stat-left">
            <div class="icon-wrap alt">🏠</div>
            <div class="stat-meta">
              <div class="label">Pulang Sekolah</div>
              <div class="value">— : —</div>
            </div>
          </div>
          <div class="status-badge pending"><span class="dot"></span> Belum Absen</div>
        </div>
      </section>

      <section class="main-grid">
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title"><span class="bar"></span> Jadwal Mengajar Hari Ini</div>
            <div class="mini-label">3 Sesi KBM</div>
          </div>

          <table class="schedule-table">
            <thead>
              <tr>
                <th style="width: 20%;">Jam</th>
                <th style="width: 30%;">Mapel</th>
                <th style="width: 18%;">Kelas</th>
                <th style="width: 32%; text-align: right;">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>07.00–08.20</td>
                <td>Matematika</td>
                <td><span class="pill-soft">7A</span></td>
                <td style="text-align: right;"><span class="pill-live"><span class="dot"></span> Selesai</span></td>
              </tr>
              <tr class="active-row">
                <td>08.20–09.40</td>
                <td>Matematika</td>
                <td><span class="pill-soft">8A</span></td>
                <td style="text-align: right;"><span class="pill-live"><span class="dot"></span> Sedang Berlangsung</span></td>
              </tr>
              <tr>
                <td>10.00–11.20</td>
                <td>Matematika</td>
                <td><span class="pill-soft">9A</span></td>
                <td style="text-align: right;"><span class="pill-soft">Belum</span></td>
              </tr>
            </tbody>
          </table>

          <div class="session-footer">
            <span>Ruang kelas: Gedung K.H. Ahmad Dahlan Lt. 2</span>
            <strong>Sesi aktif otomatis tersinkron</strong>
          </div>
        </div>

        <div class="right-stack">
          <div class="active-session">
            <div class="session-header">
              <div class="session-label">Sesi Mengajar Saat Ini</div>
              <div class="live-status"><span class="dot"></span> Sedang Berlangsung</div>
            </div>

            <div class="session-main">
              <div>
                <h3>Matematika</h3>
                <div class="meta">
                  <span>Kelas 8A</span>
                  <span>•</span>
                  <span>08.20–09.40 WIB</span>
                </div>
              </div>
              <div class="session-icon">📚</div>
            </div>

            <button class="primary-btn">▶ Mulai Presensi Mengajar</button>
          </div>

          <div class="summary-panel">
            <div class="summary-top">
              <h4>Presensi Murid</h4>
              <span class="green-badge">Matematika • 8A</span>
            </div>

            <div class="summary-body">
              <div class="count"><strong>28</strong><span>dari 32 murid hadir</span></div>
              <div class="progress"><span></span></div>
            </div>

            <button class="secondary-btn">Kelola Presensi Murid</button>
          </div>
        </div>
      </section>

      <footer class="footer">
        <div>Sistem Presensi &amp; Manajemen Akademik SMP Muhammadiyah 44 Tangerang Selatan</div>
        <div>© 2026 SMP Muhammadiyah 44 Tangerang Selatan. Seluruh hak cipta dilindungi.</div>
      </footer>
    </main>
  </div>
</body>
</html>
