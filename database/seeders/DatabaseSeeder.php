<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Kelas;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Master Kelas (7.1 s/d 9.2)
        $kelases = ['7.1', '7.2', '7.3', '8.1', '8.2', '8.3', '9.1', '9.2'];
        foreach ($kelases as $namaKelas) {
            Kelas::firstOrCreate(['nama_kelas' => $namaKelas]);
        }

        // 2. Akun Admin
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name'     => 'admin',
                'email'    => 'admin@smpm44.sch.id',
                'role'     => 'admin',
                'password' => Hash::make('admin123'),
            ]
        );

        // 3. Akun Guru
        User::firstOrCreate(
            ['username' => 'guru'],
            [
                'name'     => 'guru',
                'email'    => 'guru@smpm44.sch.id',
                'role'     => 'guru',
                'password' => Hash::make('guru123'),
            ]
        );

        // 4. Akun Siswa Dummy (Untuk Uji Coba Quick Login)
        User::firstOrCreate(
            ['username' => 'siswa'],
            [
                'name'     => 'siswa',
                'email'    => 'siswa@smpm44.sch.id',
                'role'     => 'siswa',
                'password' => Hash::make('siswa123'),
            ]
        );
    }
}