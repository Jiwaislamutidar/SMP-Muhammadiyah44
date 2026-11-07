<?php

namespace Tests\Feature;

use App\Exports\GuruTemplateExport;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminGuruTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_view_guru_and_create_guru_with_subjects(): void
    {
        $admin = $this->makeAdmin();
        $mapel = Mapel::create(['kode' => 'QA-'.bin2hex(random_bytes(3)), 'nama_mapel' => 'Matematika QA']);
        $existingGuru = Guru::create([
            'nama_lengkap' => 'Guru Mapel QA '.bin2hex(random_bytes(3)),
            'nbm' => null,
            'status' => 'aktif',
        ]);
        $existingGuru->mapels()->attach($mapel->id, ['jumlah_jam' => 2]);
        $nbm = 'NBM-'.bin2hex(random_bytes(4));
        $newMapelCode = "2'1-".bin2hex(random_bytes(3));

        $this->actingAs($admin)
            ->get(route('admin.guru.index'))
            ->assertOk()
            ->assertSee('Data Guru')
            ->assertSee('Unduh Data Guru')
            ->assertSee('Matematika QA')
            ->assertSee('('.$mapel->kode.')')
            ->assertSee('Tambah Guru')
            ->assertSee('Password Baru')
            ->assertSee('Biarkan kosong jika tidak ingin mengubah password guru saat ini.')
            ->assertSee('Password awal otomatis menggunakan NBM yang diisi.')
            ->assertSee('name="nbm"', false)
            ->assertSee('Kode Mapel')
            ->assertSee('Tambah Baris Mapel & Jam')
            ->assertSee('mapel_items[__INDEX__][nama_mapel]')
            ->assertSee('mapel_items[__INDEX__][kode_mapel]')
            ->assertSee('mapel_items[__INDEX__][jml_jam]')
            ->assertDontSee('data-mapel-select')
            ->assertSee('data-delete-guru')
            ->assertDontSee('Nonaktifkan')
            ->assertDontSee('Aktifkan');

        $this->actingAs($admin)
            ->post(route('admin.guru.store'), [
                'nama_lengkap' => 'Guru QA '.bin2hex(random_bytes(3)),
                'nbm' => $nbm,
                'password' => '',
                'status' => 'aktif',
                'mapel_items' => [
                    ['mapel_id' => $mapel->id, 'nama_baru' => '', 'jumlah_jam' => 6],
                    ['mapel_id' => '', 'nama_baru' => 'Mapel Manual QA', 'kode_baru' => $newMapelCode, 'jumlah_jam' => 3],
                ],
            ])
            ->assertRedirect(route('admin.guru.index'));

        $guru = Guru::where('nbm', $nbm)->firstOrFail();
        $this->assertSame('guru', $guru->user->role);
        $this->assertSame($nbm, $guru->user->username);
        $this->assertTrue(Hash::check($nbm, $guru->user->password));
        $this->assertSame(6, $guru->mapels->find($mapel->id)->pivot->jumlah_jam);
        $mapelBaru = Mapel::where('nama_mapel', 'Mapel Manual QA')->firstOrFail();
        $this->assertSame(3, $guru->mapels->find($mapelBaru->id)->pivot->jumlah_jam);
        $this->assertSame($newMapelCode, $mapelBaru->kode);
    }

    public function test_admin_can_download_guru_pdf_for_the_selected_filters(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.guru.export-pdf', ['search' => 'Guru QA', 'status' => 'aktif']));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('data-guru-'.now()->format('Ymd').'.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_admin_can_create_and_update_guru_with_direct_subject_fields(): void
    {
        $admin = $this->makeAdmin();
        $existingMapel = Mapel::create([
            'kode' => 'DIRECT-'.bin2hex(random_bytes(3)),
            'nama_mapel' => 'Mapel Langsung',
        ]);
        $otherGuru = Guru::create([
            'nama_lengkap' => 'Guru Relasi QA '.bin2hex(random_bytes(3)),
            'nbm' => null,
            'status' => 'aktif',
        ]);
        $otherGuru->mapels()->attach($existingMapel->id, ['jumlah_jam' => 9]);
        $newMapelCode = 'DIRECT-'.bin2hex(random_bytes(3));
        $namaGuru = 'Guru Input Langsung QA '.bin2hex(random_bytes(3));

        $this->actingAs($admin)
            ->postJson(route('admin.guru.store'), [
                'nama_lengkap' => $namaGuru,
                'nbm' => '',
                'status' => 'aktif',
                'mapel_items' => [
                    ['nama_mapel' => $existingMapel->nama_mapel, 'kode_mapel' => $existingMapel->kode, 'jml_jam' => 6],
                    ['nama_mapel' => 'IPA Langsung', 'kode_mapel' => $newMapelCode, 'jml_jam' => 3],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('guru.mapel_items.0.nama_mapel', 'Mapel Langsung')
            ->assertJsonPath('guru.mapel_items.0.kode_mapel', $existingMapel->kode);

        $guru = Guru::where('nama_lengkap', $namaGuru)->firstOrFail();
        $this->assertSame(1, Mapel::where('kode', $existingMapel->kode)->count());
        $this->assertSame(6, $guru->mapels->find($existingMapel->id)->pivot->jumlah_jam);
        $this->assertSame(9, $otherGuru->fresh()->mapels->find($existingMapel->id)->pivot->jumlah_jam);
        $newMapel = Mapel::where('kode', $newMapelCode)->firstOrFail();
        $this->assertSame(3, $guru->mapels->find($newMapel->id)->pivot->jumlah_jam);

        $this->actingAs($admin)
            ->putJson(route('admin.guru.update', $guru->id), [
                'nama_lengkap' => $namaGuru,
                'nbm' => '',
                'status' => 'aktif',
                'mapel_items' => [
                    ['nama_mapel' => 'Mapel Langsung Diperbarui', 'kode_mapel' => $existingMapel->kode, 'jml_jam' => 7],
                    ['nama_mapel' => 'IPA Langsung', 'kode_mapel' => $newMapelCode, 'jml_jam' => 4],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('guru.mapel_items.0.jml_jam', 7);

        $this->assertSame(1, Mapel::where('kode', $existingMapel->kode)->count());
        $this->assertSame(7, $guru->fresh()->mapels->find($existingMapel->id)->pivot->jumlah_jam);
        $this->assertSame(4, $guru->fresh()->mapels->find($newMapel->id)->pivot->jumlah_jam);
        $this->assertSame(9, $otherGuru->fresh()->mapels->find($existingMapel->id)->pivot->jumlah_jam);
        $this->assertSame('Mapel Langsung Diperbarui', $otherGuru->fresh()->mapels->find($existingMapel->id)->nama_mapel);
    }

    public function test_admin_can_update_guru_and_sync_subjects(): void
    {
        $admin = $this->makeAdmin();
        $guru = Guru::create(['nama_lengkap' => 'Guru Edit QA '.bin2hex(random_bytes(3)), 'nbm' => null, 'status' => 'aktif']);
        $firstMapel = Mapel::create(['kode' => 'QA-A-'.bin2hex(random_bytes(3)), 'nama_mapel' => 'Mapel Lama']);
        $secondMapel = Mapel::create(['kode' => 'QA-B-'.bin2hex(random_bytes(3)), 'nama_mapel' => 'Mapel Baru']);
        $guru->mapels()->attach($firstMapel->id, ['jumlah_jam' => 12]);

        $this->actingAs($admin)
            ->post(route('admin.guru.update', $guru->id), [
                '_method' => 'PUT',
                'nama_lengkap' => 'Guru Edit QA Selesai',
                'nbm' => 'QA-UPDATE-'.bin2hex(random_bytes(3)),
                'status' => 'nonaktif',
                'mapel_items' => [
                    ['mapel_id' => $firstMapel->id, 'nama_baru' => '', 'jumlah_jam' => 12],
                    ['mapel_id' => $secondMapel->id, 'nama_baru' => '', 'jumlah_jam' => 5],
                ],
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('guru.status', 'nonaktif');

        $updatedGuru = $guru->fresh();
        $this->assertSame('nonaktif', $updatedGuru->status);
        $this->assertSame($updatedGuru->nbm, $updatedGuru->user->username);
        $this->assertTrue(Hash::check($updatedGuru->nbm, $updatedGuru->user->password));
        $this->assertSame(12, $guru->fresh()->mapels->find($firstMapel->id)->pivot->jumlah_jam);
        $this->assertSame(5, $guru->fresh()->mapels->find($secondMapel->id)->pivot->jumlah_jam);
    }

    public function test_admin_can_reset_teacher_password_and_keep_it_when_password_is_blank(): void
    {
        $admin = $this->makeAdmin();
        $originalPassword = 'old-teacher-password';
        $user = User::create([
            'name' => 'Guru Password',
            'username' => 'NBM-PASSWORD-'.bin2hex(random_bytes(3)),
            'email' => null,
            'role' => 'guru',
            'password' => Hash::make($originalPassword),
        ]);
        $guru = Guru::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Guru Password QA '.bin2hex(random_bytes(3)),
            'nbm' => $user->username,
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->putJson(route('admin.guru.update', $guru->id), [
                'nama_lengkap' => $guru->nama_lengkap,
                'nbm' => $guru->nbm,
                'status' => 'aktif',
                'password' => '',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check($originalPassword, $user->fresh()->password));

        $newPassword = 'new-secure-password';
        $this->actingAs($admin)
            ->putJson(route('admin.guru.update', $guru->id), [
                'nama_lengkap' => $guru->nama_lengkap,
                'nbm' => $guru->nbm,
                'status' => 'aktif',
                'password' => $newPassword,
            ])
            ->assertOk();

        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
    }

    public function test_admin_can_delete_guru_and_linked_login_account(): void
    {
        $admin = $this->makeAdmin();
        $nbm = 'NBM-DELETE-'.bin2hex(random_bytes(3));
        $user = User::create([
            'name' => 'Guru Hapus',
            'username' => $nbm,
            'email' => null,
            'role' => 'guru',
            'password' => Hash::make($nbm),
        ]);
        $guru = Guru::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Guru Hapus QA '.bin2hex(random_bytes(3)),
            'nbm' => $nbm,
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.guru.destroy', $guru->id), ['_method' => 'DELETE'], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('id', $guru->id);

        $this->assertDatabaseMissing('gurus', ['id' => $guru->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_can_update_status_for_teacher_without_nbm(): void
    {
        $admin = $this->makeAdmin();
        $guru = Guru::create([
            'nama_lengkap' => 'Guru Tanpa NBM QA '.bin2hex(random_bytes(3)),
            'nbm' => null,
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->putJson(route('admin.guru.update', $guru->id), [
                'nama_lengkap' => $guru->nama_lengkap,
                'nbm' => null,
                'status' => 'nonaktif',
                'mapel_items' => [],
            ])
            ->assertOk()
            ->assertJsonPath('guru.status', 'nonaktif');

        $this->assertSame('nonaktif', $guru->fresh()->status);
        $this->assertNull($guru->fresh()->user_id);
    }

    public function test_nonactive_teacher_cannot_log_in(): void
    {
        $nbm = 'NBM-INACTIVE-'.bin2hex(random_bytes(3));
        $user = User::create([
            'name' => 'Guru Nonaktif',
            'username' => $nbm,
            'email' => null,
            'role' => 'guru',
            'password' => Hash::make($nbm),
        ]);
        Guru::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Guru Nonaktif QA '.bin2hex(random_bytes(3)),
            'nbm' => $nbm,
            'status' => 'nonaktif',
        ]);

        $this->post(route('guru.login'), [
            'username' => $nbm,
            'password' => $nbm,
        ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Akun guru sedang nonaktif. Hubungi administrator.');

        $this->assertGuest();
    }

    public function test_admin_can_import_csv_and_upsert_guru_mapel_and_hours(): void
    {
        $admin = $this->makeAdmin();
        $namaGuru = 'Guru Import QA '.bin2hex(random_bytes(3));
        $kodeMapel = 'QA-'.bin2hex(random_bytes(4));
        $csv = "Kode,Mata Pelajaran,Nama Guru,Jml. Jam\n{$kodeMapel},IPA Terpadu,{$namaGuru},18\n";

        $this->actingAs($admin)
            ->post(route('admin.guru.import'), [
                'file' => UploadedFile::fake()->createWithContent('guru.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $guru = Guru::where('nama_lengkap', $namaGuru)->firstOrFail();
        $mapel = Mapel::where('kode', $kodeMapel)->firstOrFail();
        $this->assertSame(18, $guru->mapels->find($mapel->id)->pivot->jumlah_jam);

        $updatedCsv = "Kode,Mata Pelajaran,Nama Guru,Jml. Jam\n{$kodeMapel},IPA Terpadu,{$namaGuru},20\n";
        $this->actingAs($admin)
            ->post(route('admin.guru.import'), [
                'file' => UploadedFile::fake()->createWithContent('guru-update.csv', $updatedCsv),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, Guru::where('nama_lengkap', $namaGuru)->count());
        $this->assertSame(1, Mapel::where('kode', $kodeMapel)->count());
        $this->assertSame(20, $guru->fresh()->mapels->find($mapel->id)->pivot->jumlah_jam);

        $emptyHoursCsv = "NBM,Nama Guru,Kode Mapel,Mata Pelajaran,Jml. Jam\n,{$namaGuru},{$kodeMapel},,\n";
        $this->actingAs($admin)
            ->post(route('admin.guru.import'), [
                'file' => UploadedFile::fake()->createWithContent('guru-empty-hours.csv', $emptyHoursCsv),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(20, $guru->fresh()->mapels->find($mapel->id)->pivot->jumlah_jam);
    }

    public function test_imported_teacher_gets_nbm_as_default_password_and_username(): void
    {
        $admin = $this->makeAdmin();
        $nbm = 'NBM-IMPORT-'.bin2hex(random_bytes(3));
        $namaGuru = 'Guru Import Credential QA '.bin2hex(random_bytes(3));
        $csv = "NBM,Nama Guru\n{$nbm},{$namaGuru}\n";

        $this->actingAs($admin)
            ->post(route('admin.guru.import'), [
                'file' => UploadedFile::fake()->createWithContent('guru-credentials.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $guru = Guru::where('nbm', $nbm)->firstOrFail();
        $this->assertSame($nbm, $guru->user->username);
        $this->assertTrue(Hash::check($nbm, $guru->user->password));
    }

    public function test_admin_can_import_teacher_with_only_a_trimmed_name(): void
    {
        $admin = $this->makeAdmin();
        $namaGuru = 'Dr. Siti Aminah, S.Pd.';
        $csv = "Nama Guru\n\"  {$namaGuru}  \"\n";

        $this->actingAs($admin)
            ->post(route('admin.guru.import'), [
                'file' => UploadedFile::fake()->createWithContent('guru.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $guru = Guru::where('nama_lengkap', $namaGuru)->firstOrFail();
        $this->assertNull($guru->nbm);
        $this->assertNull($guru->user_id);
        $this->assertCount(0, $guru->mapels);

        $this->actingAs($admin)
            ->get(route('admin.guru.index'))
            ->assertOk()
            ->assertSee($namaGuru)
            ->assertSee('NBM belum diisi');
    }

    public function test_admin_can_create_teacher_without_nbm_or_subjects(): void
    {
        $admin = $this->makeAdmin();
        $namaGuru = 'Guru Manual Tanpa NBM '.bin2hex(random_bytes(3));

        $this->actingAs($admin)
            ->post(route('admin.guru.store'), [
                'nama_lengkap' => $namaGuru,
                'nbm' => '',
                'status' => 'aktif',
                'mapel_items' => [],
            ])
            ->assertRedirect(route('admin.guru.index'));

        $guru = Guru::where('nama_lengkap', $namaGuru)->firstOrFail();
        $this->assertNull($guru->nbm);
        $this->assertNull($guru->user_id);
        $this->assertCount(0, $guru->mapels);
    }

    public function test_admin_can_create_guru_with_ajax_json_response(): void
    {
        $admin = $this->makeAdmin();
        $namaGuru = 'Guru AJAX QA '.bin2hex(random_bytes(3));

        $this->actingAs($admin)
            ->postJson(route('admin.guru.store'), [
                'nama_lengkap' => $namaGuru,
                'nbm' => '',
                'status' => 'aktif',
                'mapel_items' => [],
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Data guru berhasil ditambahkan.')
            ->assertJsonPath('guru.nama_lengkap', $namaGuru);

        $this->actingAs($admin)
            ->getJson(route('admin.guru.index'))
            ->assertOk()
            ->assertJsonFragment(['nama_lengkap' => $namaGuru]);
    }

    public function test_admin_can_import_gurus_with_ajax_json_response(): void
    {
        $admin = $this->makeAdmin();
        $namaGuru = 'Guru Import AJAX QA '.bin2hex(random_bytes(3));
        $kodeMapel = 'AJAX-'.bin2hex(random_bytes(3));
        $csv = "Kode,Mata Pelajaran,Nama Guru,Jml. Jam\n{$kodeMapel},IPA AJAX,{$namaGuru},6\n";

        $this->actingAs($admin)
            ->postJson(route('admin.guru.import'), [
                'file' => UploadedFile::fake()->createWithContent('guru.csv', $csv),
            ])
            ->assertOk()
            ->assertJsonPath('imported_count', 1)
            ->assertJsonPath('message', 'Berhasil mengimpor 1 baris penugasan guru.');

        $this->assertDatabaseHas('gurus', ['nama_lengkap' => $namaGuru]);
        $this->assertDatabaseHas('mapels', ['kode' => $kodeMapel, 'nama_mapel' => 'IPA AJAX']);
    }

    public function test_admin_import_uses_nama_mapel_to_create_and_link_subject_and_allows_incomplete_subject_data(): void
    {
        $admin = $this->makeAdmin();
        $nbmWithSubject = 'NBM-'.bin2hex(random_bytes(4));
        $nbmWithoutSubject = 'NBM-'.bin2hex(random_bytes(4));
        $kodeMapel = 'IMPORT-'.bin2hex(random_bytes(3));
        $kodeMapelToUpdate = 'IMPORT-'.bin2hex(random_bytes(3));
        $namaGuruWithSubject = 'Guru Import Subject QA '.bin2hex(random_bytes(3));
        $namaGuruWithoutSubject = 'Guru Import Incomplete QA '.bin2hex(random_bytes(3));
        $namaGuruWithUpdatedSubject = 'Guru Import Updated Subject QA '.bin2hex(random_bytes(3));
        $existingMapel = Mapel::create(['kode' => $kodeMapelToUpdate, 'nama_mapel' => 'Nama Mapel Lama']);
        $csv = "nbm,nama_lengkap,kode_mapel,nama_mapel,jml_jam\n".
            "{$nbmWithSubject},{$namaGuruWithSubject},{$kodeMapel},IPS Terpadu,8\n".
            'NBM-'.bin2hex(random_bytes(4)).",{$namaGuruWithUpdatedSubject},{$kodeMapelToUpdate},IPS,4\n".
            "{$nbmWithoutSubject},{$namaGuruWithoutSubject},,,\n";

        $this->actingAs($admin)
            ->postJson(route('admin.guru.import'), [
                'file' => UploadedFile::fake()->createWithContent('guru.csv', $csv),
            ])
            ->assertOk()
            ->assertJsonPath('imported_count', 3);

        $mapel = Mapel::where('kode', $kodeMapel)->firstOrFail();
        $guru = Guru::where('nbm', $nbmWithSubject)->firstOrFail();
        $this->assertSame('IPS Terpadu', $mapel->nama_mapel);
        $this->assertSame(8, $guru->mapels->find($mapel->id)->pivot->jumlah_jam);
        $updatedMapel = $existingMapel->fresh();
        $updatedGuru = Guru::where('nama_lengkap', $namaGuruWithUpdatedSubject)->firstOrFail();
        $this->assertSame('IPS', $updatedMapel->nama_mapel);
        $this->assertSame(4, $updatedGuru->mapels->find($updatedMapel->id)->pivot->jumlah_jam);
        $this->assertCount(0, Guru::where('nbm', $nbmWithoutSubject)->firstOrFail()->mapels);
    }

    public function test_admin_can_download_excel_template_with_headers_and_sample_teacher(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.guru.template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition', 'attachment; filename=template-data-guru.xlsx');

        $this->assertSame(
            [
                ['nbm', 'nama_lengkap', 'kode_mapel', 'nama_mapel', 'jml_jam'],
                ['1104698', 'Maryadi Saputra, S.Pd', '2', 'IPS Terpadu', '8'],
            ],
            (new GuruTemplateExport)->array()
        );
    }

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'Admin Guru Test',
            'username' => 'admin-guru-'.bin2hex(random_bytes(5)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);
    }
}
