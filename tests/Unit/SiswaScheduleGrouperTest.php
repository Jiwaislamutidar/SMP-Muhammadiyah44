<?php

namespace Tests\Unit;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Support\SiswaScheduleGrouper;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class SiswaScheduleGrouperTest extends TestCase
{
    public function test_overlapping_and_adjacent_rows_for_same_subject_are_merged(): void
    {
        $rows = collect([
            $this->schedule(1, 1, '07:35:00', '08:10:00'),
            $this->schedule(1, 1, '07:35:00', '08:45:00'),
            $this->schedule(1, 1, '08:10:00', '08:45:00'),
        ]);

        $result = (new SiswaScheduleGrouper)->merge($rows, Carbon::parse('2026-10-14 08:00:00'));

        $this->assertCount(1, $result);
        $this->assertSame('07:35 - 08:45', $result->first()['jam']);
        $this->assertSame('1 jam 10 menit', $result->first()['durasi']);
        $this->assertSame('Berlangsung', $result->first()['status']);
    }

    public function test_gap_and_different_subject_are_not_merged(): void
    {
        $rows = collect([
            $this->schedule(1, 1, '07:00:00', '07:30:00'),
            $this->schedule(1, 1, '07:31:00', '08:00:00'),
            $this->schedule(2, 1, '07:00:00', '07:30:00'),
        ]);

        $this->assertCount(3, (new SiswaScheduleGrouper)->merge($rows, Carbon::parse('2026-10-14 06:00:00')));
    }

    public function test_finished_range_is_marked_selesai(): void
    {
        $result = (new SiswaScheduleGrouper)->merge(
            collect([$this->schedule(1, 1, '07:00:00', '07:45:00')]),
            Carbon::parse('2026-10-14 08:00:00')
        );

        $this->assertSame('Selesai', $result->first()['status']);
    }

    public function test_duration_uses_indonesian_hour_and_minute_units(): void
    {
        $this->assertSame('45 menit', SiswaScheduleGrouper::formatDuration(45));
        $this->assertSame('2 jam', SiswaScheduleGrouper::formatDuration(120));
        $this->assertSame('1 jam 10 menit', SiswaScheduleGrouper::formatDuration(70));
    }

    private function schedule(int $mapelId, int $guruId, string $start, string $end): JadwalPelajaran
    {
        $jadwal = new JadwalPelajaran([
            'kelas_id' => 1,
            'hari' => 'Rabu',
            'mapel_id' => $mapelId,
            'guru_id' => $guruId,
            'jam_mulai' => $start,
            'jam_selesai' => $end,
        ]);
        $jadwal->setRelation('mapel', new Mapel(['nama_mapel' => 'Mapel '.$mapelId]));
        $jadwal->setRelation('guru', new Guru(['nama_lengkap' => 'Guru '.$guruId]));
        $jadwal->setRelation('kelas', new Kelas(['nama_kelas' => '7.1']));

        return $jadwal;
    }
}
