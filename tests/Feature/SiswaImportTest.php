<?php

namespace Tests\Feature;

use App\Imports\SiswaImport;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SiswaImportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_imports_students_and_skips_an_existing_nis(): void
    {
        do {
            $nis = '99' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (User::where('username', $nis)->exists());

        $nisKedua = '98' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $kelasPertama = '7.1';
        $kelasKedua = '8.2';
        $csv = "NO,NIS,NAMA\nKelas : {$kelasPertama},,\n1,{$nis},Alya Putri\nKelas : {$kelasKedua},,\n2,{$nisKedua},Fajar Ramadhan\n";
        $file = UploadedFile::fake()->createWithContent('siswa.csv', $csv);
        $import = new SiswaImport();

        Excel::import($import, $file);

        $this->assertSame(2, $import->importedCount);
        $this->assertDatabaseHas('users', [
            'name' => 'Alya Putri',
            'username' => $nis,
            'email' => null,
            'role' => 'siswa',
        ]);
        $this->assertTrue(Hash::check($nis, User::where('username', $nis)->value('password')));
        $this->assertDatabaseHas('kelas', ['nama_kelas' => $kelasPertama]);
        $this->assertDatabaseHas('siswas', [
            'nisn' => $nis,
            'nama_lengkap' => 'Alya Putri',
            'status' => 'aktif',
        ]);

        $duplicateImport = new SiswaImport();
        Excel::import($duplicateImport, UploadedFile::fake()->createWithContent(
            'duplikat.csv',
            "NO,NIS,NAMA\nKelas : {$kelasPertama},,\n1,{$nis},Alya Putri\n"
        ));

        $this->assertSame(0, $duplicateImport->importedCount);
        $this->assertSame(2, Siswa::whereIn('nisn', [$nis, $nisKedua])->count());
    }

    public function test_admin_can_upload_a_csv_through_the_import_route(): void
    {
        $admin = User::create([
            'name' => 'Admin Import Test',
            'username' => 'admin-import-' . bin2hex(random_bytes(4)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);
        $nis = '97' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $csv = "NO,NIS,NAMA\nKelas : 9.1,,\n1,{$nis},Murid Tes\n";

        $response = $this->actingAs($admin)->post(route('admin.siswa.import'), [
            'file' => UploadedFile::fake()->createWithContent('siswa.csv', $csv),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Berhasil mengimpor data siswa!');
        $this->assertDatabaseHas('siswas', ['nisn' => $nis, 'nama_lengkap' => 'Murid Tes']);
    }

    public function test_admin_can_manually_add_multiple_students_and_their_login_accounts(): void
    {
        $admin = User::create([
            'name' => 'Admin Manual Test',
            'username' => 'admin-manual-' . bin2hex(random_bytes(4)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);
        $kelas = Kelas::create(['nama_kelas' => '7.'.random_int(1, 9)]);
        $nisPertama = '96' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $nisKedua = '95' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

        $this->actingAs($admin)
            ->get(route('admin.datamurid'))
            ->assertOk()
            ->assertSee('+ Tambah Manual')
            ->assertSee('students[0][nama_lengkap]')
            ->assertSee('NIS / NISN');

        $this->actingAs($admin)
            ->post(route('admin.siswa.manual-import'), [
                'students' => [
                    ['nama_lengkap' => 'Murid Manual Satu', 'nisn' => $nisPertama, 'kelas_id' => $kelas->id],
                    ['nama_lengkap' => 'Murid Manual Dua', 'nisn' => $nisKedua, 'kelas_id' => ''],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('siswas', [
            'nisn' => $nisPertama,
            'nama_lengkap' => 'Murid Manual Satu',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);
        $this->assertDatabaseHas('siswas', [
            'nisn' => $nisKedua,
            'nama_lengkap' => 'Murid Manual Dua',
            'kelas_id' => null,
        ]);
        $this->assertDatabaseHas('users', ['username' => $nisPertama, 'role' => 'siswa']);
        $this->assertTrue(Hash::check($nisPertama, User::where('username', $nisPertama)->value('password')));
    }

    public function test_manual_student_import_rejects_duplicate_nis_without_partial_records(): void
    {
        $admin = User::create([
            'name' => 'Admin Manual Duplicate Test',
            'username' => 'admin-manual-duplicate-' . bin2hex(random_bytes(4)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);
        $nis = '94' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

        $this->actingAs($admin)
            ->from(route('admin.datamurid'))
            ->post(route('admin.siswa.manual-import'), [
                'students' => [
                    ['nama_lengkap' => 'Murid Duplikat Satu', 'nisn' => $nis, 'kelas_id' => ''],
                    ['nama_lengkap' => 'Murid Duplikat Dua', 'nisn' => $nis, 'kelas_id' => ''],
                ],
            ])
            ->assertRedirect(route('admin.datamurid'))
            ->assertSessionHasErrors('students.1.nisn');

        $this->assertDatabaseMissing('users', ['username' => $nis]);
        $this->assertDatabaseMissing('siswas', ['nisn' => $nis]);
    }
}