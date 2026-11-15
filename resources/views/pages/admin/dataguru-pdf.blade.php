<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Guru - SMP Muhammadiyah 44</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10px; }
        .heading { padding-bottom: 12px; border-bottom: 2px solid #087443; }
        .school { margin: 0 0 4px; color: #64748b; font-size: 9px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; }
        h1 { margin: 0; color: #087443; font-size: 21px; }
        .meta { margin: 12px 0; color: #475569; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { padding: 8px 9px; border: 1px solid #dbe4dd; text-align: left; vertical-align: top; }
        th { background: #eaf4ee; color: #14532d; font-size: 9px; text-transform: uppercase; letter-spacing: .3px; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .name { color: #111827; font-weight: bold; }
        .subjects { line-height: 1.6; }
        .status { color: #14532d; font-weight: bold; }
        .empty { padding: 18px; color: #64748b; text-align: center; }
        .footer { margin-top: 12px; color: #64748b; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <div class="heading">
        <p class="school">SMP Muhammadiyah 44</p>
        <h1>Data Guru</h1>
    </div>
    <div class="meta">
        Filter pencarian: {{ filled($search) ? $search : 'Semua Guru' }}
        &nbsp;|&nbsp; Status: {{ filled($status) ? ucfirst($status) : 'Semua Status' }}
        &nbsp;|&nbsp; Jumlah data: {{ $gurus->count() }} guru
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: 23%">Nama Guru</th>
                <th style="width: 14%">NBM</th>
                <th style="width: 39%">Mata Pelajaran / Kode Mapel</th>
                <th style="width: 13%">Beban Mengajar</th>
                <th style="width: 11%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($gurus as $guru)
                <tr>
                    <td class="name">{{ trim($guru->nama_lengkap) }}</td>
                    <td>{{ $guru->nbm ?: '-' }}</td>
                    <td class="subjects">
                        @forelse ($guru->mapels as $mapel)
                            {{ trim($mapel->nama_mapel) }} ({{ $mapel->kode }}){{ $loop->last ? '' : ', ' }}
                        @empty
                            Belum ada mapel
                        @endforelse
                    </td>
                    <td>{{ $guru->mapels->sum('pivot.jumlah_jam') }} JP/Minggu</td>
                    <td class="status">{{ ucfirst($guru->status) }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="5">Tidak ada data guru yang sesuai dengan filter.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Dicetak pada {{ $generatedAt->locale('id')->translatedFormat('d F Y H:i') }} WIB</div>
</body>
</html>
