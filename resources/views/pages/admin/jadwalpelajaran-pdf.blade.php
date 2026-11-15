<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Pelajaran - SMP Muhammadiyah 44</title>
    <style>
        @page { margin: 24px 26px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 9px; }
        .heading { padding-bottom: 10px; border-bottom: 2px solid #3B82F6; }
        .school { margin: 0 0 4px; color: #64748b; font-size: 8px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; }
        h1 { margin: 0; color: #1d4ed8; font-size: 20px; }
        .meta { margin: 10px 0; color: #475569; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { padding: 7px; border: 1px solid #dbe4f0; text-align: left; vertical-align: top; }
        th { background: #eff6ff; color: #1e40af; font-size: 8px; text-transform: uppercase; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .empty { padding: 16px; color: #64748b; text-align: center; }
        .footer { margin-top: 10px; color: #64748b; font-size: 7px; text-align: right; }
    </style>
</head>
<body>
    <div class="heading">
        <p class="school">SMP Muhammadiyah 44</p>
        <h1>Jadwal Pelajaran</h1>
    </div>
    <div class="meta">
        Pencarian: {{ filled($search) ? $search : 'Semua Jadwal' }}
        &nbsp;|&nbsp; Hari: {{ filled($hari) ? $hari : 'Semua Hari' }}
        &nbsp;|&nbsp; Jumlah data: {{ $jadwals->count() }} jadwal
    </div>
    <table>
        <thead><tr><th>Hari</th><th>Waktu</th><th>Mata Pelajaran</th><th>Guru</th><th>Kelas</th></tr></thead>
        <tbody>
            @forelse ($jadwals as $jadwal)
                <tr>
                    <td>{{ $jadwal->hari }}</td>
                    <td>{{ substr($jadwal->jam_mulai, 0, 5) }}–{{ substr($jadwal->jam_selesai, 0, 5) }}</td>
                    <td>{{ $jadwal->mapel?->nama_mapel ?? '-' }}</td>
                    <td>{{ $jadwal->guru?->nama_lengkap ?? '-' }}</td>
                    <td>{{ $jadwal->kelas?->nama_kelas ?? '-' }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="5">Tidak ada jadwal yang sesuai dengan filter.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Dicetak pada {{ $generatedAt->locale('id')->translatedFormat('d F Y H:i') }} WIB</div>
</body>
</html>
