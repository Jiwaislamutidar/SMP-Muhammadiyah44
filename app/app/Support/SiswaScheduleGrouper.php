<?php

namespace App\Support;

use App\Models\JadwalPelajaran;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SiswaScheduleGrouper
{
    private const BREAK_WINDOWS = [
        ['label' => 'Istirahat', 'type' => 'istirahat', 'start' => '09:55:00', 'end' => '10:15:00'],
        ['label' => 'Istirahat', 'type' => 'dzuhur', 'start' => '11:55:00', 'end' => '13:00:00'],
    ];

    private const BREAK_DAYS = ['senin', 'selasa', 'rabu', 'kamis'];

    public function blocks(Collection $jadwals, ?Carbon $now = null): Collection
    {
        $now ??= now();
        $groups = $jadwals->groupBy(fn (JadwalPelajaran $jadwal): string => implode('|', [
            $jadwal->kelas_id,
            mb_strtolower($jadwal->hari),
            $jadwal->mapel_id,
            $jadwal->guru_id,
        ]));
        $blocks = collect();

        foreach ($groups as $group) {
            $day = mb_strtolower($group->first()->hari);
            $breaks = $this->breakWindows($day);
            $clusters = [];

            foreach ($group->sortBy('jam_mulai')->values() as $jadwal) {
                $start = substr($jadwal->jam_mulai, 0, 8);
                $end = substr($jadwal->jam_selesai, 0, 8);
                $lastIndex = array_key_last($clusters);
                $joinsLast = $lastIndex !== null
                    && ($start <= $clusters[$lastIndex]['end']
                        || $this->isBreakGap($clusters[$lastIndex]['end'], $start, $breaks));

                if ($joinsLast) {
                    $clusters[$lastIndex]['end'] = max($clusters[$lastIndex]['end'], $end);
                    $clusters[$lastIndex]['jadwals']->push($jadwal);
                    continue;
                }

                $clusters[] = [
                    'start' => $start,
                    'end' => $end,
                    'jadwals' => collect([$jadwal]),
                ];
            }

            foreach ($clusters as $cluster) {
                $intervals = $cluster['jadwals']->map(fn (JadwalPelajaran $jadwal): array => [
                    'start' => substr($jadwal->jam_mulai, 0, 8),
                    'end' => substr($jadwal->jam_selesai, 0, 8),
                ]);
                $segments = $this->workingSegments($intervals, $breaks);
                $canonical = $cluster['jadwals']
                    ->sort(function (JadwalPelajaran $left, JadwalPelajaran $right): int {
                        $durationDifference = $this->durationMinutes($right->jam_mulai, $right->jam_selesai)
                            <=> $this->durationMinutes($left->jam_mulai, $left->jam_selesai);

                        return $durationDifference !== 0
                            ? $durationDifference
                            : strcmp($left->jam_mulai, $right->jam_mulai);
                    })
                    ->first();
                $duration = $segments->sum(fn (array $segment): int => $this->durationMinutes($segment['start'], $segment['end']));
                $timeSegments = $segments
                    ->map(fn (array $segment): string => substr($segment['start'], 0, 5).' - '.substr($segment['end'], 0, 5))
                    ->all();

                $blocks->push([
                    'jadwal' => $canonical,
                    'jadwals' => $cluster['jadwals'],
                    'segments' => $segments->all(),
                    'jam' => implode(', ', $timeSegments),
                    'start' => $cluster['start'],
                    'end' => $cluster['end'],
                    'durasi_menit' => $duration,
                    'durasi' => self::formatDuration($duration),
                    'status' => $this->status($cluster['start'], $cluster['end'], $now),
                ]);
            }
        }

        return $blocks->sortBy('start')->values();
    }

    public function merge(Collection $jadwals, Carbon $now): Collection
    {
        $rows = collect();

        foreach ($this->blocks($jadwals, $now) as $block) {
            $breaks = $this->breakWindows(mb_strtolower($block['jadwal']->hari));
            $dzuhurParts = $this->splitAtDzuhur($block['segments'], $breaks);

            if (count($dzuhurParts) === 1) {
                $rows->push($this->studentRow($block, $block['segments'], $now));
                continue;
            }

            foreach ($dzuhurParts as $index => $part) {
                if ($index > 0) {
                    $previous = $dzuhurParts[$index - 1];
                    $pause = $this->breakBetween($previous[array_key_last($previous)]['end'], $part[0]['start'], $breaks);
                    if ($pause) {
                        $rows->push([
                            'kind' => 'break',
                            'label' => $pause['label'],
                            'jam' => substr($pause['start'], 0, 5).' - '.substr($pause['end'], 0, 5),
                            'mulai' => $pause['start'],
                        ]);
                    }
                }

                $rows->push($this->studentRow($block, $part, $now, $index === 0));
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

    private function studentRow(array $block, array $segments, Carbon $now, bool $runsThroughBlock = false): array
    {
        $start = $segments[0]['start'];
        $end = $segments[array_key_last($segments)]['end'];
        $duration = collect($segments)->sum(fn (array $segment): int => $this->durationMinutes($segment['start'], $segment['end']));
        $status = $runsThroughBlock
            ? $this->status($block['start'], $block['end'], $now)
            : $this->status($start, $end, $now);

        return [
            'kind' => 'lesson',
            'jam' => substr($start, 0, 5).' - '.substr($end, 0, 5),
            'durasi' => self::formatDuration($duration),
            'mapel' => $block['jadwal']->mapel->nama_mapel,
            'guru' => $block['jadwal']->guru->nama_lengkap,
            'kelas' => $block['jadwal']->kelas->nama_kelas,
            'status' => $status,
            'mulai' => $start,
        ];
    }

    private function workingSegments(Collection $intervals, array $breaks): Collection
    {
        $segments = collect();

        foreach ($intervals as $interval) {
            $pieces = [$interval];

            foreach ($breaks as $break) {
                $pieces = collect($pieces)->flatMap(function (array $piece) use ($break): array {
                    if ($piece['start'] >= $break['end'] || $piece['end'] <= $break['start']) {
                        return [$piece];
                    }

                    $remaining = [];
                    if ($piece['start'] < $break['start']) {
                        $remaining[] = ['start' => $piece['start'], 'end' => $break['start']];
                    }
                    if ($piece['end'] > $break['end']) {
                        $remaining[] = ['start' => $break['end'], 'end' => $piece['end']];
                    }

                    return $remaining;
                })->values()->all();
            }

            $segments = $segments->concat($pieces);
        }

        $merged = collect();
        foreach ($segments->sortBy('start')->values() as $segment) {
            $lastIndex = $merged->count() - 1;
            if ($lastIndex >= 0 && $segment['start'] <= $merged[$lastIndex]['end']) {
                $merged->put($lastIndex, [
                    'start' => $merged[$lastIndex]['start'],
                    'end' => max($merged[$lastIndex]['end'], $segment['end']),
                ]);
                continue;
            }

            $merged->push($segment);
        }

        return $merged;
    }

    private function splitAtDzuhur(array $segments, array $breaks): array
    {
        $parts = [[]];

        foreach ($segments as $segment) {
            $previous = $parts[array_key_last($parts)];
            if ($previous !== []) {
                $pause = $this->breakBetween($previous[array_key_last($previous)]['end'], $segment['start'], $breaks);
                if ($pause && $pause['type'] === 'dzuhur') {
                    $parts[] = [];
                }
            }
            $parts[array_key_last($parts)][] = $segment;
        }

        return $parts;
    }

    private function breakWindows(string $day): array
    {
        return in_array($day, self::BREAK_DAYS, true) ? self::BREAK_WINDOWS : [];
    }

    private function isBreakGap(string $end, string $start, array $breaks): bool
    {
        return $this->breakBetween($end, $start, $breaks) !== null;
    }

    private function breakBetween(string $end, string $start, array $breaks): ?array
    {
        foreach ($breaks as $break) {
            if ($end === $break['start'] && $start === $break['end']) {
                return $break;
            }
        }

        return null;
    }

    private function durationMinutes(string $start, string $end): int
    {
        return (int) ((Carbon::parse($end)->timestamp - Carbon::parse($start)->timestamp) / 60);
    }

    private function status(string $start, string $end, Carbon $now): string
    {
        $nowTime = $now->format('H:i:s');

        return $nowTime < $start
            ? 'Akan Datang'
            : ($nowTime >= $end ? 'Selesai' : 'Berlangsung');
    }
}
