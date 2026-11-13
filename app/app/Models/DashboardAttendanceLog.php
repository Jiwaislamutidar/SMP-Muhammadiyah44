<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardAttendanceLog extends Model
{
    protected $table = 'dashboard_presensi_guru_logs';

    protected $fillable = [
        'source_presensi_id',
        'guru_id',
        'guru_nama',
        'tanggal',
        'jam_masuk',
        'foto_masuk',
        'jam_pulang',
        'foto_pulang',
        'status',
        'bukti_izin',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public static function recordFromAttendance(PresensiGuru $attendance): self
    {
        return static::firstOrCreate(
            ['source_presensi_id' => $attendance->id],
            static::attendanceAttributes($attendance)
        );
    }

    public static function syncFromAttendance(PresensiGuru $attendance): void
    {
        static::query()
            ->where('source_presensi_id', $attendance->id)
            ->update(static::attendanceAttributes($attendance));
    }

    private static function attendanceAttributes(PresensiGuru $attendance): array
    {
        return [
            'guru_id' => $attendance->guru_id,
            'guru_nama' => $attendance->guru?->nama_lengkap ?? 'Guru',
            'tanggal' => $attendance->tanggal,
            'jam_masuk' => $attendance->jam_masuk,
            'foto_masuk' => $attendance->foto_masuk,
            'jam_pulang' => $attendance->jam_pulang,
            'foto_pulang' => $attendance->foto_pulang,
            'status' => $attendance->status,
            'bukti_izin' => $attendance->bukti_izin,
        ];
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }
}
