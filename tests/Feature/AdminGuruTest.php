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

        $this->actingAs($admin)
            ->get(route('admin.guru.index'))
            ->assertOk()
            ->assertSee('Data Guru')
            ->assertSee('Tambah Guru Manual')
            ->assertSee('Tambah Baris Mapel & Jam')
            ->assertSee('mapel_items[__INDEX__][jumlah_jam]');

        $this->actingAs($admin)
            ->post(route('admin.guru.store'), [
                'nama_lengkap' => 'Guru QA '.bin2hex(random_bytes(3)),
                'nip' => 'NIP-'.bin2hex(random_bytes(4)),
                'status' => 'aktif',
                'mapel_items' => [
                    ['mapel_id' => $mapel->id, 'nama_baru' => '', 'jumlah_jam' => 6],
                    ['mapel_id' => '', 'nama_baru' => 'Mapel Manual QA', 'jumlah_jam' => 3],
                ],
            ])
            ->assertRedirect(route('admin.guru.index'));

        $guru = Guru::where('nip', 'like', 'NIP-%')->latest('id')->firstOrFail();
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
            ->put(route('admin.guru.update', $guru->id), [
                'nama_lengkap' => 'Guru Edit QA Selesai',
                'nip' => 'QA-UPDATE-'.bin2hex(random_bytes(3)),
                'status' => 'nonaktif',
                'mapel_items' => [
                    ['mapel_id' => $firstMapel->id, 'nama_baru' => '', 'jumlah_jam' => 12],
                    ['mapel_id' => $secondMapel->id, 'nama_baru' => '', 'jumlah_jam' => 5],
                ],
            ])
            ->assertRedirect(route('admin.guru.index'));

        $this->assertSame('nonaktif', $guru->fresh()->status);
        $this->assertSame(12, $guru->fresh()->mapels->find($firstMapel->id)->pivot->jumlah_jam);
        $this->assertSame(5, $guru->fresh()->mapels->find($secondMapel->id)->pivot->jumlah_jam);
    }

    public function test_admin_can_toggle_guru_status(): void
    {
        $admin = $this->makeAdmin();
        $guru = Guru::create(['nama_lengkap' => 'Guru Toggle QA '.bin2hex(random_bytes(3)), 'nip' => null, 'status' => 'aktif']);

        $this->actingAs($admin)
            ->delete(route('admin.guru.destroy', $guru->id))
            ->assertRedirect(route('admin.guru.index'));

        $this->assertSame('nonaktif', $guru->fresh()->status);
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
