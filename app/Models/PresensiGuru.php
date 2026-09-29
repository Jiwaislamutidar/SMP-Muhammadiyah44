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
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }
}