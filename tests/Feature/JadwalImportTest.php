<?php

namespace Tests\Feature;

use App\Exports\JadwalTemplateExport;
use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
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
            ->assertSee($guru->nama_lengkap)
            ->assertSee('value="Senin"', false)
            ->assertSee('value="Selasa"', false)
            ->assertSee('value="Rabu"', false)
            ->assertSee('value="Kamis"', false)
            ->assertSee('value="Jumat"', false)
            ->assertSee('value="'.$kelas->id.'"', false);

        $this->actingAs($admin)
            ->get(route('admin.jadwalpelajaran', ['hari' => 'Selasa', 'kelas_id' => $kelas->id]))
            ->assertOk()
            ->assertSee('Belum ada jadwal tersimpan.')
            ->assertDontSee('class="schedule-subject"', false);
    }

    public function test_import_accepts_header_aliases_and_trims_case_differences(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $admin = $this->makeAdmin($suffix);
        $guru = Guru::create(['nama_lengkap' => 'Guru Alias QA '.$suffix, 'status' => 'aktif']);
        $kelas = Kelas::create(['nama_kelas' => 'Kelas Alias QA '.$suffix]);
        $mapel = Mapel::create(['kode' => 'ALIAS-'.$suffix, 'nama_mapel' => 'Mapel Alias QA']);
        $csv = "HARI,jam_mulai,Jam Selesai,MAPEL,GURU,KELAS,Ruangan\n".
            " sEnIn ,07:00,08:00, {$mapel->kode} , ".mb_strtolower($guru->nama_lengkap).' , '.mb_strtoupper($kelas->nama_kelas)." ,Ruang QA\n\n";

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

    public function test_import_resolves_mapel_codes_to_their_assigned_teacher(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $admin = $this->makeAdmin($suffix);
        $guru = Guru::create(['nama_lengkap' => 'Guru Kode QA '.$suffix, 'status' => 'aktif']);
        $kelas = Kelas::create(['nama_kelas' => 'Kelas Kode QA '.$suffix]);
        $mapelPertama = Mapel::create(['kode' => "2'1-{$suffix}", 'nama_mapel' => 'Mapel Kode Satu']);
        $mapelKedua = Mapel::create(['kode' => "2-{$suffix}", 'nama_mapel' => 'Mapel Kode Dua']);
        $guru->mapels()->attach([$mapelPertama->id, $mapelKedua->id]);
        $csv = "hari,jam_ke,waktu,kode_kelas,kode_mapel\n".
            "Senin,1,07:00-07:40,{$kelas->nama_kelas},{$mapelPertama->kode}\n".
            "Senin,2,,{$kelas->nama_kelas},{$mapelKedua->kode}\n";

        $this->actingAs($admin)
            ->post(route('admin.jadwalpelajaran.import'), [
                'file' => UploadedFile::fake()->createWithContent('jadwal-kode.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('jadwal_pelajarans', [
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapelPertama->id,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '07:40:00',
        ]);
        $this->assertDatabaseHas('jadwal_pelajarans', [
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapelKedua->id,
            'jam_mulai' => '07:40:00',
            'jam_selesai' => '08:20:00',
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

    public function test_template_download_is_xlsx_with_official_headers_and_database_examples(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $admin = $this->makeAdmin($suffix);
        $guru = Guru::create(['nama_lengkap' => 'Guru Template QA '.$suffix, 'status' => 'aktif']);
        $kelas = Kelas::create(['nama_kelas' => 'Kelas Template QA '.$suffix]);
        $mapel = Mapel::create(['kode' => 'TEMPLATE-'.$suffix, 'nama_mapel' => 'Mapel Template QA']);
        $guru->mapels()->attach($mapel->id, ['jumlah_jam' => 2]);

        $response = $this->actingAs($admin)->get(route('admin.jadwalpelajaran.template'));
        $response->assertOk()->assertDownload('template-jadwal-pelajaran.xlsx');

        $rows = (new JadwalTemplateExport)->array();
        $this->assertSame(['hari', 'jam_ke', 'waktu', 'kode_kelas', 'kode_mapel'], $rows[0]);
        $this->assertCount(11, $rows);

        foreach (array_slice($rows, 1) as $example) {
            $this->assertTrue(Kelas::where('nama_kelas', $example[3])->exists());
            $this->assertTrue(Mapel::where('kode', $example[4])->exists());
            $this->assertContains($example[0], ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']);
        }

        $scheduleCount = JadwalPelajaran::count();
        $xlsx = Excel::raw(new JadwalTemplateExport, \Maatwebsite\Excel\Excel::XLSX);
        $this->actingAs($admin)
            ->post(route('admin.jadwalpelajaran.import'), [
                'file' => UploadedFile::fake()->createWithContent('jadwal.xlsx', $xlsx),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($scheduleCount + 10, JadwalPelajaran::count());
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
