<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mapel extends Model
{
    protected $guarded = [];

    public function gurus(): BelongsToMany
    {
        return $this->belongsToMany(Guru::class, 'guru_mapel')
            ->withPivot('jumlah_jam')
            ->withTimestamps();
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class);
    }
}
