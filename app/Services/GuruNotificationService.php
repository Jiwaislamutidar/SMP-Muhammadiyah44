<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\SesiPelajaran;
use App\Support\SiswaScheduleGrouper;
use Illuminate\Support\Carbon;

class GuruNotificationService
{
    public function forGuru(Guru $guru): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();
        $hari = $now->copy()->locale('id')->isoFormat('dddd');
        $notifications = [];

        $jadwalBerikutnya = (new SiswaScheduleGrouper)->blocks(JadwalPelajaran::query()
            ->with(['mapel:id,nama_mapel', 'kelas:id,nama_kelas'])
            ->where('guru_id', $guru->id)
            ->whereRaw('LOWER(hari) = ?', [mb_strtolower($hari)])
            ->orderBy('jam_mulai')
            ->get(), $now)
            ->filter(fn (array $block): bool => $block['start'] > $now->format('H:i:s'))
            ->sortBy('start')
            ->first();

        if ($jadwalBerikutnya) {
            $jadwal = $jadwalBerikutnya['jadwal'];
            $waktuJadwal = Carbon::parse($today.' '.$jadwalBerikutnya['start'], 'Asia/Jakarta');
            $menit = max(0, (int) ceil($now->diffInSeconds($waktuJadwal, false) / 60));
            $notifications[] = $this->notification(
                'upcoming-'.$jadwal->id.'-'.$today,
                'Jadwal mengajar berikutnya',
                $jadwal->mapel->nama_mapel.' • '.$jadwal->kelas->nama_kelas,
                'Jam '.$jadwalBerikutnya['jam'].' • '.$jadwalBerikutnya['durasi'].' • dalam '.$menit.' menit',
                'success'
            );
        }

        $sesiBerlangsung = SesiPelajaran::query()
            ->with([
                'jadwal.mapel:id,nama_mapel',
                'jadwal.kelas' => fn ($query) => $query->withCount([
                    'siswas as jumlah_siswa_aktif' => fn ($siswaQuery) => $siswaQuery->where('status', 'aktif'),
                ]),
            ])
            ->withCount([
                'presensiPelajarans as jumlah_sudah_absen' => fn ($query) => $query->whereIn('status', ['Hadir', 'Izin', 'Sakit']),
            ])
            ->where('guru_id', $guru->id)
            ->whereDate('tanggal', $today)
            ->where('status_sesi', 'Berlangsung')
            ->orderBy('created_at')
            ->limit(5)
            ->get();

        foreach ($sesiBerlangsung as $sesi) {
            $notifications[] = $this->notification(
                'ongoing-'.$sesi->id.'-'.$sesi->jumlah_sudah_absen,
                'Sesi sedang berlangsung',
                $sesi->jadwal->mapel->nama_mapel.' • '.$sesi->jadwal->kelas->nama_kelas,
                $sesi->jumlah_sudah_absen.' dari '.$sesi->jadwal->kelas->jumlah_siswa_aktif.' siswa sudah absen',
                'success'
            );
        }

        $sesiSelesai = SesiPelajaran::query()
            ->with(['jadwal.mapel:id,nama_mapel', 'jadwal.kelas:id,nama_kelas'])
            ->withCount([
                'presensiPelajarans as jumlah_perlu_diperiksa' => fn ($query) => $query->whereIn('status', ['Belum Absen', 'Alfa']),
            ])
            ->where('guru_id', $guru->id)
            ->whereDate('tanggal', $today)
            ->where('status_sesi', 'Selesai')
            ->whereHas('presensiPelajarans', fn ($query) => $query->whereIn('status', ['Belum Absen', 'Alfa']))
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        foreach ($sesiSelesai as $sesi) {
            $notifications[] = $this->notification(
                'finished-'.$sesi->id.'-'.$sesi->jumlah_perlu_diperiksa,
                'Sesi selesai, presensi perlu diperiksa',
                $sesi->jadwal->mapel->nama_mapel.' • '.$sesi->jadwal->kelas->nama_kelas,
                $sesi->jumlah_perlu_diperiksa.' siswa belum absen atau Alfa',
                'danger'
            );
        }

        return array_slice($notifications, 0, 5);
    }

    private function notification(string $signature, string $title, string $subject, string $detail, string $status): array
    {
        return [
            'signature' => hash('sha256', $signature),
            'title' => $title,
            'subject' => $subject,
            'detail' => $detail,
            'status' => $status,
        ];
    }
}
