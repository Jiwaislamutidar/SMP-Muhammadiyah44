<?php

namespace Tests\Feature;

use App\Exports\SiswaTemplateExport;
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
        $kelasPertama = Kelas::create(['nama_kelas' => 'Import 7.'.random_int(1, 9)]);
        $kelasKedua = Kelas::create(['nama_kelas' => 'Import 8.'.random_int(1, 9)]);
        $csv = "nisn,nama_lengkap,kode_kelas\n{$nis},Alya Putri,{$kelasPertama->nama_kelas}\n{$nisKedua},Fajar Ramadhan,{$kelasKedua->nama_kelas}\n";
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
        $this->assertDatabaseHas('siswas', [
            'nisn' => $nis,
            'nama_lengkap' => 'Alya Putri',
            'kelas_id' => $kelasPertama->id,
            'status' => 'aktif',
        ]);
        $this->assertSame($kelasKedua->id, Siswa::where('nisn', $nisKedua)->value('kelas_id'));

        $duplicateImport = new SiswaImport();
        Excel::import($duplicateImport, UploadedFile::fake()->createWithContent(
            'duplikat.csv',
            "nama_lengkap,kode_kelas,nisn\nAlya Putri,{$kelasPertama->nama_kelas},{$nis}\n"
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
        $kelas = Kelas::create(['nama_kelas' => '9.'.random_int(1, 9)]);
        $csv = "nisn,nama_lengkap,kode_kelas\n{$nis},Murid Tes,{$kelas->nama_kelas}\n";

        $response = $this->actingAs($admin)->post(route('admin.siswa.import'), [
            'file' => UploadedFile::fake()->createWithContent('siswa.csv', $csv),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Berhasil mengimpor data siswa!');
        $this->assertDatabaseHas('siswas', ['nisn' => $nis, 'nama_lengkap' => 'Murid Tes']);
        $this->assertTrue(Hash::check($nis, User::where('username', $nis)->value('password')));
    }

    public function test_admin_can_download_student_template_with_three_columns_and_sample_data(): void
    {
        $admin = User::create([
            'name' => 'Admin Siswa Template',
            'username' => 'admin-siswa-template-'.bin2hex(random_bytes(4)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.siswa.template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition', 'attachment; filename=template-data-siswa.xlsx');

        $template = (new SiswaTemplateExport)->array();
        $this->assertSame(['nisn', 'nama_lengkap', 'kode_kelas'], $template[0]);
        $this->assertCount(3, $template[0]);
        $this->assertCount(2, $template);
        $this->assertSame('Alya Putri', $template[1][1]);
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
            ->assertSee('+ Tambah Siswa')
            ->assertSee('Import Excel')
            ->assertSee('Unduh Template')
            ->assertSee('nisn, nama_lengkap, kode_kelas')
            ->assertSee('id="manualImportModal"', false)
            ->assertSee('id="importModal"', false)
            ->assertSee('students[0][nama_lengkap]')
            ->assertSee('NIS / NISN')
            ->assertSee('Hapus')
            ->assertDontSee('Detail');

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

    public function test_admin_can_delete_a_student_and_the_linked_login_account(): void
    {
        $admin = User::create([
            'name' => 'Admin Student Delete Test',
            'username' => 'admin-student-delete-'.bin2hex(random_bytes(4)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);
        $user = User::create([
            'name' => 'Murid Hapus Test',
            'username' => 'murid-hapus-'.bin2hex(random_bytes(4)),
            'email' => null,
            'role' => 'siswa',
            'password' => Hash::make('test-password'),
        ]);
        $student = Siswa::create([
            'user_id' => $user->id,
            'nisn' => 'nisn-hapus-'.bin2hex(random_bytes(4)),
            'nama_lengkap' => 'Murid Hapus Test',
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.siswa.destroy', $student))
            ->assertRedirect(route('admin.datamurid'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('siswas', ['id' => $student->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_can_edit_student_data_and_optionally_change_the_password(): void
    {
        $admin = User::create([
            'name' => 'Admin Student Edit Test',
            'username' => 'admin-student-edit-'.bin2hex(random_bytes(4)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);
        $kelasAwal = Kelas::create(['nama_kelas' => '7.'.random_int(1, 9)]);
        $kelasBaru = Kelas::create(['nama_kelas' => '8.'.random_int(1, 9)]);
        $nisnAwal = 'nisn-awal-'.bin2hex(random_bytes(4));
        $nisnBaru = 'nisn-baru-'.bin2hex(random_bytes(4));
        $passwordAwal = 'password-awal';
        $user = User::create([
            'name' => 'Murid Sebelum Edit',
            'username' => $nisnAwal,
            'email' => null,
            'role' => 'siswa',
            'password' => Hash::make($passwordAwal),
        ]);
        $student = Siswa::create([
            'user_id' => $user->id,
            'nisn' => $nisnAwal,
            'nama_lengkap' => 'Murid Sebelum Edit',
            'kelas_id' => $kelasAwal->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.datamurid'))
            ->assertOk()
            ->assertSee('data-edit-student', false)
            ->assertSee('id="editStudentModal"', false)
            ->assertSee('name="kode_kelas"', false)
            ->assertSee('Password Baru')
            ->assertSee('Biarkan kosong jika tidak ingin mengubah password siswa saat ini.');

        $this->actingAs($admin)
            ->put(route('admin.siswa.update', $student), [
                'form_context' => 'edit',
                'edit_siswa_id' => $student->id,
                'nisn' => $nisnBaru,
                'nama_lengkap' => 'Murid Sesudah Edit',
                'kode_kelas' => $kelasBaru->nama_kelas,
                'password' => '',
            ])
            ->assertRedirect(route('admin.datamurid'))
            ->assertSessionHas('success', 'Data murid berhasil diperbarui.');

        $this->assertDatabaseHas('siswas', [
            'id' => $student->id,
            'nisn' => $nisnBaru,
            'nama_lengkap' => 'Murid Sesudah Edit',
            'kelas_id' => $kelasBaru->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => $nisnBaru,
            'name' => 'Murid Sesudah Edit',
        ]);
        $this->assertTrue(Hash::check($passwordAwal, $user->fresh()->password));

        $passwordBaru = 'password-baru';
        $this->actingAs($admin)
            ->put(route('admin.siswa.update', $student), [
                'form_context' => 'edit',
                'edit_siswa_id' => $student->id,
                'nisn' => $nisnBaru,
                'nama_lengkap' => 'Murid Sesudah Edit',
                'kode_kelas' => $kelasBaru->nama_kelas,
                'password' => $passwordBaru,
            ])
            ->assertRedirect(route('admin.datamurid'));

        $this->assertTrue(Hash::check($passwordBaru, $user->fresh()->password));
    }
}