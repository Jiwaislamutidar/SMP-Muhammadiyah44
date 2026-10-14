<?php

namespace App\Support;

use App\Models\JadwalPelajaran;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SiswaScheduleGrouper
{
    public function merge(Collection $jadwals, Carbon $now): Collection
    {
        $groups = $jadwals->groupBy(fn (JadwalPelajaran $jadwal): string => implode('|', [
            $jadwal->kelas_id,
            mb_strtolower($jadwal->hari),
            $jadwal->mapel_id,
            $jadwal->guru_id,
        ]));
        $rows = collect();

        foreach ($groups as $group) {
            $sorted = $group->sortBy('jam_mulai')->values();
            $merged = [];

            foreach ($sorted as $jadwal) {
                $start = substr($jadwal->jam_mulai, 0, 8);
                $end = substr($jadwal->jam_selesai, 0, 8);
                $lastIndex = array_key_last($merged);

                if ($lastIndex !== null && $start <= $merged[$lastIndex]['end']) {
                    if ($end > $merged[$lastIndex]['end']) {
                        $merged[$lastIndex]['end'] = $end;
                    }

                    continue;
                }

                $merged[] = ['jadwal' => $jadwal, 'start' => $start, 'end' => $end];
            }

            foreach ($merged as $interval) {
                $start = $interval['start'];
                $end = $interval['end'];
                $duration = (int) ((Carbon::parse($end)->timestamp - Carbon::parse($start)->timestamp) / 60);
                $nowTime = $now->format('H:i:s');
                $status = $nowTime < $start
                    ? 'Akan Datang'
                    : ($nowTime >= $end ? 'Selesai' : 'Berlangsung');

                $rows->push([
                    'jam' => substr($start, 0, 5).' - '.substr($end, 0, 5),
                    'durasi' => self::formatDuration($duration),
                    'mapel' => $interval['jadwal']->mapel->nama_mapel,
                    'guru' => $interval['jadwal']->guru->nama_lengkap,
                    'kelas' => $interval['jadwal']->kelas->nama_kelas,
                    'status' => $status,
                    'mulai' => $start,
                ]);
            }
        }

        return $rows->sortBy('mulai')->values()->map(function (array $row): array {
            unset($row['mulai']);

            return $row;
        });
    }

    public static function formatDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;
        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.' jam';
        }

        if ($remainingMinutes > 0) {
            $parts[] = $remainingMinutes.' menit';
        }

        return implode(' ', $parts);
    }
}
