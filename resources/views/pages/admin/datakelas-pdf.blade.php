<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Kelas - SMP Muhammadiyah 44</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10px; }
        .heading { padding-bottom: 12px; border-bottom: 2px solid #3B82F6; }
        .school { margin: 0 0 4px; color: #64748b; font-size: 9px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; }
        h1 { margin: 0; color: #1d4ed8; font-size: 21px; }
        .meta { margin: 12px 0; color: #475569; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { padding: 8px 9px; border: 1px solid #dbe4f0; text-align: left; }
        th { background: #eff6ff; color: #1e40af; font-size: 9px; text-transform: uppercase; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .empty { padding: 18px; color: #64748b; text-align: center; }
        .footer { margin-top: 12px; color: #64748b; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <div class="heading">
        <p class="school">SMP Muhammadiyah 44</p>
        <h1>Data Kelas</h1>
    </div>
    <div class="meta">
        Pencarian: {{ filled($search) ? $search : 'Semua Kelas' }}
        &nbsp;|&nbsp; Tingkat: {{ filled($tingkat) ? $tingkat : 'Semua Tingkat' }}
        &nbsp;|&nbsp; Jumlah data: {{ $kelasList->count() }} kelas
    </div>
    <table>
        <thead><tr><th style="width: 25%">Kode Kelas</th><th>Nama / Tingkat Kelas</th><th style="width: 30%">Nomor Ruangan</th></tr></thead>
        <tbody>
            @forelse ($kelasList as $kelas)
                <tr>
                    <td>{{ $kelas->nama_kelas }}</td>
                    <td>{{ $kelas->tingkat ? 'Tingkat '.$kelas->tingkat : $kelas->nama_kelas }}</td>
                    <td>{{ $kelas->ruangan ?: '-' }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="3">Tidak ada data kelas yang sesuai dengan filter.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Dicetak pada {{ $generatedAt->locale('id')->translatedFormat('d F Y H:i') }} WIB</div>
</body>
</html>
