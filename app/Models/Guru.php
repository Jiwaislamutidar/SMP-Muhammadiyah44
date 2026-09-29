<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guru extends Model
{
    protected $guarded = [];

    public static function forUser(User $user): ?self
    {
        $guru = static::where('user_id', $user->id)->first();

        if (! $guru) {
            $guru = static::query()
                ->whereNull('user_id')
                ->where(function ($query) use ($user) {
                    $query->where('nip', $user->username)
                        ->orWhere('nama_lengkap', $user->name);
                })
                ->first();
        }

        if ($guru && ! $guru->user_id) {
            $guru->user()->associate($user);
            $guru->save();
        }

        return $guru ?? static::create([
            'user_id' => $user->id,
            'nama_lengkap' => $user->name,
            'status' => 'aktif',
        ]);
    }

    public function mapels(): BelongsToMany
    {
        return $this->belongsToMany(Mapel::class, 'guru_mapel')
            ->withPivot('jumlah_jam')
            ->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class);
    }

    public function presensiHarian(): HasMany
    {
        return $this->hasMany(PresensiGuru::class);
    }

    public function sesiPelajarans(): HasMany
    {
        return $this->hasMany(SesiPelajaran::class);
    }
}
