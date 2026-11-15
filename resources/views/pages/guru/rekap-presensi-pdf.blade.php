<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Presensi Sesi</title>
    <style>
        body { color: #17211c; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { margin: 0 0 5px; color: #087443; font-size: 20px; }
        .school { margin: 0 0 18px; color: #6b756f; font-size: 10px; }
        .session-info { width: 100%; margin-bottom: 18px; border-collapse: collapse; }
        .session-info td { width: 50%; padding: 5px 8px 5px 0; }
        .session-info strong { color: #087443; }
        table.attendance { width: 100%; border-collapse: collapse; }
        .attendance th, .attendance td { padding: 7px 8px; border: 1px solid #dfe8e2; text-align: left; }
        .attendance th { background: #e8f6ef; color: #075c36; }
        .generated { margin-top: 14px; color: #6b756f; font-size: 9px; text-align: right; }
    </style>
</head>
<body>
    <h1>Rekap Presensi Murid</h1>
    <p class="school">SMP Muhammadiyah 44</p>

    <table class="session-info">
        <tr>
            <td><strong>Mata Pelajaran:</strong> {{ $sesi->jadwal->mapel->nama_mapel }}</td>
            <td><strong>Kelas:</strong> {{ $sesi->jadwal->kelas->nama_kelas }}</td>
        </tr>
        <tr>
            <td><strong>Tanggal:</strong> {{ $sesi->tanggal->locale('id')->translatedFormat('j F Y') }}</td>
            <td><strong>Jam:</strong> {{ substr($sesi->jadwal->jam_mulai, 0, 5) }}–{{ substr($sesi->jadwal->jam_selesai, 0, 5) }}</td>
        </tr>
    </table>

    <table class="attendance">
        <thead>
            <tr>
                <th style="width: 8%">No.</th>
                <th>Nama Murid</th>
                <th>Kelas</th>
                <th style="width: 22%">Status Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sesi->presensiPelajarans->sortBy(fn ($presensi) => $presensi->siswa->nama_lengkap) as $index => $presensi)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $presensi->siswa->nama_lengkap }}</td>
                    <td>{{ $presensi->siswa->kelas?->nama_kelas ?? $sesi->jadwal->kelas->nama_kelas }}</td>
                    <td>{{ $presensi->status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center">Tidak ada data presensi murid.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="generated">Dicetak pada {{ now()->locale('id')->translatedFormat('j F Y, H:i') }}</p>
</body>
</html>
