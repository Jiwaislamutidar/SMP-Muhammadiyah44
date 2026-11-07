<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Absensi - SMP Muhammadiyah 44</title>
    <style>
        @page { margin: 22px 24px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 8px; }
        .heading { padding-bottom: 9px; border-bottom: 2px solid #3B82F6; }
        .school { margin: 0 0 3px; color: #64748b; font-size: 7px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; }
        h1 { margin: 0; color: #1d4ed8; font-size: 18px; }
        .meta { margin: 9px 0; color: #475569; font-size: 7px; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { padding: 5px; border: 1px solid #dbe4f0; text-align: left; vertical-align: top; }
        th { background: #eff6ff; color: #1e40af; font-size: 7px; text-transform: uppercase; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .empty { padding: 16px; color: #64748b; text-align: center; }
        .footer { margin-top: 9px; color: #64748b; font-size: 7px; text-align: right; }
    </style>
</head>
<body>
    <div class="heading">
        <p class="school">SMP Muhammadiyah 44</p>
        <h1>Rekap Absensi Murid</h1>
    </div>
    <div class="meta">
        Periode: {{ $dateFrom }}{{ $dateTo !== $dateFrom ? ' s.d. '.$dateTo : '' }}
        &nbsp;|&nbsp; Kelas: {{ $kelasName ?: 'Semua Kelas' }}
        &nbsp;|&nbsp; Status: {{ $filters['status'] ?: 'Semua Status' }}
        &nbsp;|&nbsp; Pencarian: {{ $filters['search'] ?: 'Semua Murid' }}
        &nbsp;|&nbsp; Jumlah data: {{ $rows->count() }}
    </div>
    <table>
        <thead><tr><th>Nama Murid</th><th>NISN</th><th>Kelas</th><th>Tanggal</th><th>Status</th><th>Mata Pelajaran</th><th>Jam Pelajaran</th><th>Waktu Presensi</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['nama'] }}</td>
                    <td>{{ $row['nisn'] }}</td>
                    <td>{{ $row['kelas'] }}</td>
                    <td>{{ $row['tanggal'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td>{{ $row['mapel'] }}</td>
                    <td>{{ $row['jam_pelajaran'] }}</td>
                    <td>{{ $row['waktu'] === '-' ? '-' : substr($row['waktu'], 0, 5) }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="8">Tidak ada data presensi yang sesuai dengan filter.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Dicetak pada {{ $generatedAt->locale('id')->translatedFormat('d F Y H:i') }} WIB</div>
</body>
</html>
