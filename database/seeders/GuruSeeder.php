<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class GuruSeeder extends Seeder
{
    public function run(): void
    {
        Guru::with('user')->orderBy('id')->chunkById(100, function ($gurus) {
            foreach ($gurus as $guru) {
                DB::transaction(function () use ($guru) {
                    $username = 'GURU'.str_pad((string) $guru->id, 3, '0', STR_PAD_LEFT);
                    $user = $guru->user ?? User::where('username', $username)->first();

                    if ($user && Guru::where('user_id', $user->id)->where('id', '!=', $guru->id)->exists()) {
                        throw new RuntimeException("Akun {$username} sudah terhubung ke guru lain.");
                    }

                    if ($user && $user->username !== $username && User::where('username', $username)->where('id', '!=', $user->id)->exists()) {
                        throw new RuntimeException("Username {$username} sudah digunakan akun lain.");
                    }

                    $isNewUser = ! $user;
                    $passwordNeedsReset = $isNewUser || $user->username !== $username;
                    $user ??= new User();
                    $user->fill([
                        'name' => $guru->nama_lengkap,
                        'username' => $username,
                        'role' => 'guru',
                    ]);

                    if ($isNewUser) {
                        $user->email = null;
                    }
                    if ($passwordNeedsReset) {
                        $user->password = Hash::make($username);
                    }

                    $user->save();
                    $guru->user()->associate($user);
                    $guru->save();
                });
            }
        });
    }
}