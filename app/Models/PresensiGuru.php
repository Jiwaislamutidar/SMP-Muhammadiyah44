<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresensiGuru extends Model
{
    protected $fillable = [
        'guru_id',
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

    protected static function booted(): void
    {
        static::created(fn (self $attendance) => DashboardAttendanceLog::recordFromAttendance($attendance));
        static::created(fn (self $attendance) => DashboardAttendanceSessionLog::recordFromAttendance($attendance));
        static::updated(function (self $attendance): void {
            DashboardAttendanceLog::syncFromAttendance($attendance);
            DashboardAttendanceSessionLog::syncFromAttendance($attendance);
        });
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }
}
