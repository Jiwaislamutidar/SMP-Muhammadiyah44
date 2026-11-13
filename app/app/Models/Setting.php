<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    protected $fillable = [
        'jam_masuk', 'jam_toleransi',
        'jam_pulang_senin', 'jam_pulang_selasa', 
        'jam_pulang_rabu', 'jam_pulang_kamis', 'jam_pulang_jumat'
    ];
}