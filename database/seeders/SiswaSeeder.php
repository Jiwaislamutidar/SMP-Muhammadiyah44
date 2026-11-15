<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Kelas;
use Illuminate\Support\Facades\Hash;

class SiswaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Master Kelas Default
        $kelases = ['7.1', '7.2', '7.3', '8.1', '8.2', '8.3', '9.1', '9.2'];
        foreach ($kelases as $namaKelas) {
            Kelas::firstOrCreate(['nama_kelas' => $namaKelas]);
        }

        // 2. Akun Admin Default (untuk kamu login pertama kali)
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name'     => 'Admin Sekolah',
                'email'    => 'admin@smpm44.sch.id',
                'role'     => 'admin',
                'password' => Hash::make('admin123'),
            ]
        );
    }
}