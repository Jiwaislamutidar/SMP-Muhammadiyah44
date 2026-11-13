<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SesiPelajaran extends Model
{
    protected $fillable = [
        'jadwal_id',
        'guru_id',
        'tanggal',
        'qr_token',
        'qr_expires_at',
        'status_sesi',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'qr_expires_at' => 'datetime',
    ];

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalPelajaran::class, 'jadwal_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function presensiPelajarans(): HasMany
    {
        return $this->hasMany(PresensiPelajaran::class, 'sesi_pelajaran_id');
    }
}