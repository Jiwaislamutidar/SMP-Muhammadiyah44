<?php

namespace Tests\Feature;

use App\Imports\SiswaImport;
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
}