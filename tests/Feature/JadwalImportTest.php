<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class JadwalImportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_import_and_list_database_schedules(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $admin = User::create([
            'name' => 'Admin Jadwal QA',
            'username' => 'admin-jadwal-'.$suffix,
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);
        $guru = Guru::create(['nama_lengkap' => 'Guru Jadwal QA '.$suffix, 'status' => 'aktif']);
        $kelas = Kelas::create(['nama_kelas' => 'Kelas QA '.$suffix]);
        $mapel = Mapel::create(['kode' => 'MAPEL-'.$suffix, 'nama_mapel' => 'Mapel Jadwal QA']);
        $csv = "Hari,Jam Mulai,Jam Selesai,Kode Mapel,Nama Guru,Nama Kelas,Ruangan\n".
            "Senin,07:00,08:00,{$mapel->kode},{$guru->nama_lengkap},{$kelas->nama_kelas},Ruang QA\n";

        $this->actingAs($admin)
            ->post(route('admin.jadwalpelajaran.import'), [
                'file' => UploadedFile::fake()->createWithContent('jadwal.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('admin.jadwalpelajaran.import'), [
                'file' => UploadedFile::fake()->createWithContent('jadwal.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, JadwalPelajaran::where('guru_id', $guru->id)->count());
        $this->actingAs($admin)
            ->get(route('admin.jadwalpelajaran'))
            ->assertOk()
            ->assertSee($mapel->nama_mapel)
            ->assertSee($kelas->nama_kelas)
            ->assertSee($guru->nama_lengkap);
    }
}