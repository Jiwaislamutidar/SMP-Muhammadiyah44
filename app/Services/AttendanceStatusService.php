<?php

namespace App\Services;

use App\Models\DashboardAttendanceLog;
use App\Models\Guru;
use App\Models\PresensiGuru;
use App\Models\SesiPelajaran;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceStatusService
{
    public function completeExpiredStudentSessions(): void
    {
        $sessionIds = SesiPelajaran::query()
            ->where('status_sesi', 'Berlangsung')
            ->whereDate('tanggal', '<=', today())
            ->pluck('id');

        foreach ($sessionIds as $sessionId) {
            DB::transaction(function () use ($sessionId): void {
                $session = SesiPelajaran::query()
                    ->with('jadwal')
                    ->lockForUpdate()
                    ->find($sessionId);

                if (! $session || $session->status_sesi !== 'Berlangsung') {
                    return;
                }

                $scheduledEnd = Carbon::parse(
                    $session->tanggal->toDateString().' '.$session->jadwal->jam_selesai
                );

                if ($scheduledEnd->isFuture()) {
                    return;
                }

                $session->presensiPelajarans()
                    ->where('status', 'Belum Absen')
                    ->update([
                        'status' => 'Alfa',
                        'updated_at' => now(),
                    ]);

                $session->status_sesi = 'Selesai';
                $session->qr_token = null;
                $session->qr_expires_at = null;
                $session->save();
            });
        }
    }

    public function applyGuruAttendanceCutoff(): void
    {
        if (now()->lt(today()->setTime(15, 0))) {
            return;
        }

        $missingGuruIds = Guru::query()
            ->whereDoesntHave('presensiHarian', fn ($query) => $query->whereDate('tanggal', today()))
            ->pluck('id');

        foreach ($missingGuruIds->chunk(500) as $guruIds) {
            $timestamp = now();
            DB::table('presensi_gurus')->insertOrIgnore(
                $guruIds->map(fn (int $guruId): array => [
                    'guru_id' => $guruId,
                    'tanggal' => today()->toDateString(),
                    'jam_masuk' => null,
                    'foto_masuk' => null,
                    'jam_pulang' => null,
                    'foto_pulang' => null,
                    'status' => 'Alfa',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])->all()
            );

            PresensiGuru::query()
                ->whereIn('guru_id', $guruIds)
                ->whereDate('tanggal', today())
                ->where('status', 'Alfa')
                ->get()
                ->each(fn (PresensiGuru $attendance) => DashboardAttendanceLog::recordFromAttendance($attendance));
        }
    }
}
