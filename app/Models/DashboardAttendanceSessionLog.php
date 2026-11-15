<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardAttendanceSessionLog extends Model
{
    protected $table = 'dashboard_presensi_guru_session_logs';

    protected $fillable = [
        'source_presensi_id',
        'guru_id',
        'guru_nama',
        'tanggal',
        'session_type',
        'jam',
        'foto',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public static function recordFromAttendance(PresensiGuru $attendance): void
    {
        foreach (static::sessionAttributes($attendance) as $sessionType => $attributes) {
            if ($attributes['jam'] || $attributes['foto']) {
                static::query()->firstOrCreate(
                    [
                        'source_presensi_id' => $attendance->id,
                        'session_type' => $sessionType,
                    ],
                    $attributes
                );
            }
        }
    }

    public static function syncFromAttendance(PresensiGuru $attendance): void
    {
        foreach (static::sessionAttributes($attendance) as $sessionType => $attributes) {
            $log = static::query()
                ->where('source_presensi_id', $attendance->id)
                ->where('session_type', $sessionType)
                ->first();

            if ($log) {
                $log->update($attributes);

                continue;
            }

            $originalTime = $attendance->getOriginal('jam_'.$sessionType);
            $originalPhoto = $attendance->getOriginal('foto_'.$sessionType);
            if ((! $originalTime && ! $originalPhoto) && ($attributes['jam'] || $attributes['foto'])) {
                static::query()->create([
                    'source_presensi_id' => $attendance->id,
                    'session_type' => $sessionType,
                ] + $attributes);
            }
        }
    }

    private static function sessionAttributes(PresensiGuru $attendance): array
    {
        return [
            'masuk' => [
                'guru_id' => $attendance->guru_id,
                'guru_nama' => $attendance->guru?->nama_lengkap ?? 'Guru',
                'tanggal' => $attendance->tanggal,
                'jam' => $attendance->jam_masuk,
                'foto' => $attendance->foto_masuk,
            ],
            'pulang' => [
                'guru_id' => $attendance->guru_id,
                'guru_nama' => $attendance->guru?->nama_lengkap ?? 'Guru',
                'tanggal' => $attendance->tanggal,
                'jam' => $attendance->jam_pulang,
                'foto' => $attendance->foto_pulang,
            ],
        ];
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }
}
