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

    public function test_break_gap_is_one_student_row_with_net_learning_duration_and_running_status(): void
    {
        $rows = collect([
            $this->schedule(1, 1, '09:20:00', '09:55:00'),
            $this->schedule(1, 1, '10:15:00', '10:45:00'),
        ]);

        $result = (new SiswaScheduleGrouper)->merge($rows, Carbon::parse('2026-10-14 10:00:00'));

        $this->assertCount(1, $result);
        $this->assertSame('09:20 - 10:45', $result->first()['jam']);
        $this->assertSame('1 jam 5 menit', $result->first()['durasi']);
        $this->assertSame('Berlangsung', $result->first()['status']);
    }

    public function test_dzuhur_gap_keeps_two_lesson_rows_and_marks_first_row_running_until_second_finishes(): void
    {
        $rows = collect([
            $this->schedule(1, 1, '11:20:00', '11:55:00'),
            $this->schedule(1, 1, '13:00:00', '13:30:00'),
        ]);

        $result = (new SiswaScheduleGrouper)->merge($rows, Carbon::parse('2026-10-14 12:15:00'));

        $this->assertCount(3, $result);
        $this->assertSame('lesson', $result[0]['kind']);
        $this->assertSame('Berlangsung', $result[0]['status']);
        $this->assertSame('break', $result[1]['kind']);
        $this->assertSame('Istirahat', $result[1]['label']);
        $this->assertSame('11:55 - 13:00', $result[1]['jam']);
        $this->assertSame('Akan Datang', $result[2]['status']);
    }

    public function test_overlapping_hour_rows_and_combined_row_render_once_as_the_widest_range(): void
    {
        $rows = collect([
            $this->schedule(1, 1, '10:45:00', '11:20:00'),
            $this->schedule(1, 1, '11:20:00', '11:55:00'),
            $this->schedule(1, 1, '10:45:00', '11:55:00'),
        ]);

        $result = (new SiswaScheduleGrouper)->merge($rows, Carbon::parse('2026-10-14 10:50:00'));

        $this->assertCount(1, $result);
        $this->assertSame('10:45 - 11:55', $result->first()['jam']);
    }

    public function test_presence_block_exposes_clean_break_segments_and_net_duration(): void
    {
        $rows = collect([
            $this->schedule(1, 1, '09:20:00', '09:55:00'),
            $this->schedule(1, 1, '10:15:00', '10:45:00'),
        ]);

        $block = (new SiswaScheduleGrouper)->blocks($rows, Carbon::parse('2026-10-14 10:00:00'))->first();

        $this->assertSame('09:20 - 09:55, 10:15 - 10:45', $block['jam']);
        $this->assertSame('1 jam 5 menit', $block['durasi']);
        $this->assertSame('Berlangsung', $block['status']);
    }

    public function test_presence_block_remains_running_through_dzuhur_and_keeps_both_segments(): void
    {
        $rows = collect([
            $this->schedule(1, 1, '11:20:00', '11:55:00'),
            $this->schedule(1, 1, '13:00:00', '13:30:00'),
        ]);

        $block = (new SiswaScheduleGrouper)->blocks($rows, Carbon::parse('2026-10-14 12:15:00'))->first();

        $this->assertSame('11:20 - 11:55, 13:00 - 13:30', $block['jam']);
        $this->assertSame('1 jam 5 menit', $block['durasi']);
        $this->assertSame('Berlangsung', $block['status']);
    }

    public function test_presence_block_uses_widest_canonical_schedule_and_keeps_other_rows_in_the_block(): void
    {
        $earliest = $this->schedule(1, 1, '10:45:00', '11:20:00');
        $widest = $this->schedule(1, 1, '10:45:00', '11:55:00');
        $rows = collect([$earliest, $widest, $this->schedule(1, 1, '11:20:00', '11:55:00')]);

        $block = (new SiswaScheduleGrouper)->blocks($rows, Carbon::parse('2026-10-14 10:50:00'))->first();

        $this->assertSame($widest, $block['jadwal']);
        $this->assertCount(3, $block['jadwals']);
        $this->assertSame('10:45 - 11:55', $block['jam']);
    }

    public function test_non_break_gap_different_teacher_and_friday_break_gap_are_not_merged(): void
    {
        $sameSubjectDifferentTeacher = collect([
            $this->schedule(1, 1, '09:20:00', '09:55:00'),
            $this->schedule(1, 1, '10:15:00', '10:45:00', 'Rabu', 2),
        ]);
        $nonBreakGap = collect([
            $this->schedule(1, 1, '09:20:00', '09:55:00'),
            $this->schedule(1, 1, '10:20:00', '10:45:00'),
        ]);
        $friday = collect([
            $this->schedule(1, 1, '09:20:00', '09:55:00', 'Jumat'),
            $this->schedule(1, 1, '10:15:00', '10:45:00', 'Jumat'),
        ]);

        $this->assertCount(2, (new SiswaScheduleGrouper)->merge($sameSubjectDifferentTeacher, Carbon::parse('2026-10-14 09:00:00')));
        $this->assertCount(2, (new SiswaScheduleGrouper)->merge($nonBreakGap, Carbon::parse('2026-10-14 09:00:00')));
        $this->assertCount(2, (new SiswaScheduleGrouper)->merge($friday, Carbon::parse('2026-10-16 09:00:00')));
        $this->assertCount(2, (new SiswaScheduleGrouper)->blocks($sameSubjectDifferentTeacher, Carbon::parse('2026-10-14 09:00:00')));
        $this->assertCount(2, (new SiswaScheduleGrouper)->blocks($nonBreakGap, Carbon::parse('2026-10-14 09:00:00')));
        $this->assertCount(2, (new SiswaScheduleGrouper)->blocks($friday, Carbon::parse('2026-10-16 09:00:00')));
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

    private function schedule(int $mapelId, int $guruId, string $start, string $end, string $day = 'Rabu', ?int $actualGuruId = null): JadwalPelajaran
    {
        $jadwal = new JadwalPelajaran([
            'kelas_id' => 1,
            'hari' => $day,
            'mapel_id' => $mapelId,
            'guru_id' => $actualGuruId ?? $guruId,
            'jam_mulai' => $start,
            'jam_selesai' => $end,
        ]);
        $jadwal->setRelation('mapel', new Mapel(['nama_mapel' => 'Mapel '.$mapelId]));
        $jadwal->setRelation('guru', new Guru(['nama_lengkap' => 'Guru '.($actualGuruId ?? $guruId)]));
        $jadwal->setRelation('kelas', new Kelas(['nama_kelas' => '7.1']));

        return $jadwal;
    }
}
