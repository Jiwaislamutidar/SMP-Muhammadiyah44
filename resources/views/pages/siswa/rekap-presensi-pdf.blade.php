<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Presensi Siswa</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        h1 { margin: 0 0 6px; color: #087443; font-size: 20px; }
        .identity { margin-bottom: 18px; line-height: 1.6; }
        .meta { margin-bottom: 12px; color: #475569; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #dbe4dd; text-align: left; }
        th { background: #eaf4ee; color: #14532d; }
        .empty { padding: 18px; text-align: center; color: #64748b; }
        .footer { margin-top: 16px; text-align: right; color: #64748b; font-size: 9px; }
    </style>
</head>
<body>
    <h1>Rekap Presensi Siswa</h1>
    <div class="identity">
        <strong>{{ $siswa?->nama_lengkap ?? auth()->user()->name }}</strong><br>
        NISN: {{ $siswa?->nisn ?? '-' }}<br>
        Kelas: {{ $siswa?->kelas?->nama_kelas ?? '-' }}
    </div>
    <div class="meta">
        Periode: {{ $filters['periode'] ? \Carbon\Carbon::createFromFormat('Y-m', $filters['periode'])->locale('id')->translatedFormat('F Y') : 'Semua Periode' }}
        &nbsp;|&nbsp; Mata Pelajaran: {{ $mapelTerpilih ?? 'Semua Mata Pelajaran' }}
        &nbsp;|&nbsp; Status: {{ $filters['status'] ?? 'Semua Status' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Hari</th>
                <th>Mata Pelajaran</th>
                <th>Jam</th>
                <th>Kelas</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($riwayatPresensi as $presensi)
                @php($sesi = $presensi->sesiPelajaran)
                @php($jadwal = $sesi?->jadwal)
                <tr>
                    <td>{{ $sesi?->tanggal?->format('d M Y') ?? '-' }}</td>
                    <td>{{ $sesi?->tanggal?->locale('id')->translatedFormat('l') ?? '-' }}</td>
                    <td>{{ $jadwal?->mapel?->nama_mapel ?? '-' }}</td>
                    <td>{{ $jadwal?->jam_mulai ? substr($jadwal->jam_mulai, 0, 5) : '-' }} - {{ $jadwal?->jam_selesai ? substr($jadwal->jam_selesai, 0, 5) : '-' }}</td>
                    <td>{{ $jadwal?->kelas?->nama_kelas ?? '-' }}</td>
                    <td>{{ $presensi->status }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="6">Tidak ada catatan presensi untuk filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Dicetak pada {{ $generatedAt->locale('id')->translatedFormat('d F Y H:i') }} WIB</div>
</body>
</html>
