<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Account Admin
        User::create([
            'name' => 'sang admin',
            'username' => 'admin',
            'email' => 'admin@smpm44.sch.id',
            'role' => 'admin',
            'password' => Hash::make('admin123'),
        ]);

        // Account Guru
        User::create([
            'name' => 'pak guru',
            'username' => 'gurbudi',
            'email' => 'guru@smpm44.sch.id',
            'role' => 'guru',
            'password' => Hash::make('guru123'),
        ]);

        // Account Siswa
        User::create([
            'name' => 'jiwa',
            'username' => 'siswa',
            'email' => 'siswa@smpm44.sch.id',
            'role' => 'siswa',
            'password' => Hash::make('siswa123'),
        ]);
    }
}