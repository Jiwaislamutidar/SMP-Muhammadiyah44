<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresensiPelajaran extends Model
{
    protected $fillable = [
        'sesi_pelajaran_id',
        'siswa_id',
        'waktu_scan',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'waktu_scan' => 'datetime',
        'hidden_from_student_at' => 'datetime',
    ];

    public function sesiPelajaran(): BelongsTo
    {
        return $this->belongsTo(SesiPelajaran::class, 'sesi_pelajaran_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}