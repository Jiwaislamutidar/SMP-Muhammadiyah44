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

    public function test_import_accepts_header_aliases_and_trims_case_differences(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $admin = $this->makeAdmin($suffix);
        $guru = Guru::create(['nama_lengkap' => 'Guru Alias QA '.$suffix, 'status' => 'aktif']);
        $kelas = Kelas::create(['nama_kelas' => 'Kelas Alias QA '.$suffix]);
        $mapel = Mapel::create(['kode' => 'ALIAS-'.$suffix, 'nama_mapel' => 'Mapel Alias QA']);
        $csv = "HARI,jam_mulai,Jam Selesai,MAPEL,GURU,KELAS,Ruangan\n".
            " sEnIn ,07:00,08:00, {$mapel->kode} , ".mb_strtolower($guru->nama_lengkap)." , ".mb_strtoupper($kelas->nama_kelas)." ,Ruang QA\n\n";

        $this->actingAs($admin)
            ->post(route('admin.jadwalpelajaran.import'), [
                'file' => UploadedFile::fake()->createWithContent('jadwal-alias.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('jadwal_pelajarans', [
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'hari' => 'Senin',
        ]);
    }

    public function test_import_error_includes_row_and_missing_master_value(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $admin = $this->makeAdmin($suffix);
        $kelas = Kelas::create(['nama_kelas' => 'Kelas Error QA '.$suffix]);
        $mapel = Mapel::create(['kode' => 'ERROR-'.$suffix, 'nama_mapel' => 'Mapel Error QA']);
        $csv = "Hari,Jam Mulai,Jam Selesai,Kode Mapel,Nama Guru,Nama Kelas,Ruangan\n".
            "Senin,07:00,08:00,{$mapel->kode},Dolay S.Pd.I,{$kelas->nama_kelas},Ruang QA\n";

        $this->actingAs($admin)
            ->post(route('admin.jadwalpelajaran.import'), [
                'file' => UploadedFile::fake()->createWithContent('jadwal-error.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('error', "❌ Baris 2: Guru 'Dolay S.Pd.I' tidak ditemukan di database.");
    }

    public function test_template_download_includes_official_headers_and_database_examples(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $admin = $this->makeAdmin($suffix);
        $guru = Guru::create(['nama_lengkap' => 'Guru Template QA '.$suffix, 'status' => 'aktif']);
        $kelas = Kelas::create(['nama_kelas' => 'Kelas Template QA '.$suffix]);
        $mapel = Mapel::create(['kode' => 'TEMPLATE-'.$suffix, 'nama_mapel' => 'Mapel Template QA']);
        $guru->mapels()->attach($mapel->id, ['jumlah_jam' => 2]);

        $response = $this->actingAs($admin)->get(route('admin.jadwalpelajaran.template'));
        $response->assertOk();
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $response->streamedContent());
        $lines = array_values(array_filter(explode("\n", trim($content)), fn ($line) => trim($line) !== ''));
        $rows = array_map('str_getcsv', $lines);

        $this->assertSame(['Hari', 'Jam Mulai', 'Jam Selesai', 'Kode Mapel', 'Nama Guru', 'Nama Kelas', 'Ruangan'], $rows[0]);
        $this->assertGreaterThanOrEqual(1, count($rows) - 1);
        $this->assertLessThanOrEqual(2, count($rows) - 1);

        foreach (array_slice($rows, 1) as $example) {
            $this->assertTrue(Guru::where('nama_lengkap', $example[4])->exists());
            $this->assertTrue(Kelas::where('nama_kelas', $example[5])->exists());
            $this->assertTrue(Mapel::where('kode', $example[3])->exists());
        }
    }

    private function makeAdmin(string $suffix): User
    {
        return User::create([
            'name' => 'Admin Jadwal QA '.$suffix,
            'username' => 'admin-jadwal-'.$suffix,
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);
    }
}