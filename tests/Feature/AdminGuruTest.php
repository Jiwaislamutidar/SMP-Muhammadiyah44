<?php

namespace Tests\Feature;

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
        $nbm = 'NBM-'.bin2hex(random_bytes(4));

        $this->actingAs($admin)
            ->get(route('admin.guru.index'))
            ->assertOk()
            ->assertSee('Data Guru')
            ->assertSee('Tambah Guru')
            ->assertSee('Tambah Baris Mapel & Jam')
            ->assertSee('mapel_items[__INDEX__][jumlah_jam]')
            ->assertSee('data-delete-guru')
            ->assertDontSee('Nonaktifkan')
            ->assertDontSee('Aktifkan');

        $this->actingAs($admin)
            ->post(route('admin.guru.store'), [
                'nama_lengkap' => 'Guru QA '.bin2hex(random_bytes(3)),
                'nip' => $nbm,
                'status' => 'aktif',
                'mapel_items' => [
                    ['mapel_id' => $mapel->id, 'nama_baru' => '', 'jumlah_jam' => 6],
                    ['mapel_id' => '', 'nama_baru' => 'Mapel Manual QA', 'jumlah_jam' => 3],
                ],
            ])
            ->assertRedirect(route('admin.guru.index'));

        $guru = Guru::where('nip', $nbm)->firstOrFail();
        $this->assertSame('guru', $guru->user->role);
        $this->assertSame($nbm, $guru->user->username);
        $this->assertTrue(Hash::check($nbm, $guru->user->password));
        $this->assertSame(6, $guru->mapels->find($mapel->id)->pivot->jumlah_jam);
        $mapelBaru = Mapel::where('nama_mapel', 'Mapel Manual QA')->firstOrFail();
        $this->assertSame(3, $guru->mapels->find($mapelBaru->id)->pivot->jumlah_jam);
        $this->assertStringStartsWith('MANUAL-', $mapelBaru->kode);
    }

    public function test_admin_can_update_guru_and_sync_subjects(): void
    {
        $admin = $this->makeAdmin();
        $guru = Guru::create(['nama_lengkap' => 'Guru Edit QA '.bin2hex(random_bytes(3)), 'nip' => null, 'status' => 'aktif']);
        $firstMapel = Mapel::create(['kode' => 'QA-A-'.bin2hex(random_bytes(3)), 'nama_mapel' => 'Mapel Lama']);
        $secondMapel = Mapel::create(['kode' => 'QA-B-'.bin2hex(random_bytes(3)), 'nama_mapel' => 'Mapel Baru']);
        $guru->mapels()->attach($firstMapel->id, ['jumlah_jam' => 12]);

        $this->actingAs($admin)
            ->post(route('admin.guru.update', $guru->id), [
                '_method' => 'PUT',
                'nama_lengkap' => 'Guru Edit QA Selesai',
                'nip' => 'QA-UPDATE-'.bin2hex(random_bytes(3)),
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
        $this->assertSame($updatedGuru->nip, $updatedGuru->user->username);
        $this->assertTrue(Hash::check($updatedGuru->nip, $updatedGuru->user->password));
        $this->assertSame(12, $guru->fresh()->mapels->find($firstMapel->id)->pivot->jumlah_jam);
        $this->assertSame(5, $guru->fresh()->mapels->find($secondMapel->id)->pivot->jumlah_jam);
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
            'nip' => $nbm,
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
            'nip' => null,
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->putJson(route('admin.guru.update', $guru->id), [
                'nama_lengkap' => $guru->nama_lengkap,
                'nip' => null,
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
            'nip' => $nbm,
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
