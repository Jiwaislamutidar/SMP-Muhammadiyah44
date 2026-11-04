<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminKelasTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_and_update_class_details(): void
    {
        $admin = $this->makeAdmin();
        $className = 'Kelas QA '.bin2hex(random_bytes(4));

        $this->actingAs($admin)
            ->post(route('admin.kelas.store'), [
                'nama_kelas' => $className,
                'tingkat' => 'VII',
                'wali_kelas' => 'Guru QA',
                'ruangan' => 'Ruang QA',
                'jumlah_murid' => 28,
            ])
            ->assertRedirect(route('admin.datakelas'));

        $kelas = Kelas::where('nama_kelas', $className)->firstOrFail();
        $this->assertDatabaseHas('kelas', [
            'id' => $kelas->id,
            'tingkat' => 'VII',
            'wali_kelas' => 'Guru QA',
            'ruangan' => 'Ruang QA',
            'jumlah_murid' => 28,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.kelas.update', $kelas), [
                'nama_kelas' => $className,
                'tingkat' => 'VIII',
                'wali_kelas' => 'Guru QA Updated',
                'ruangan' => 'Ruang Baru',
                'jumlah_murid' => 30,
            ])
            ->assertRedirect(route('admin.datakelas'));

        $this->assertDatabaseHas('kelas', [
            'id' => $kelas->id,
            'tingkat' => 'VIII',
            'wali_kelas' => 'Guru QA Updated',
            'ruangan' => 'Ruang Baru',
            'jumlah_murid' => 30,
        ]);
    }

    public function test_class_page_has_refactored_columns_and_persisted_records(): void
    {
        $admin = $this->makeAdmin();
        $kelas = Kelas::create([
            'nama_kelas' => '8A QA',
            'tingkat' => 'VIII',
            'wali_kelas' => 'Guru QA',
            'ruangan' => 'Ruang 201',
            'jumlah_murid' => 31,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.datakelas'))
            ->assertOk()
            ->assertSee('Jumlah Murid')
            ->assertSee('Edit')
            ->assertSee('31 Murid')
            ->assertSee('Guru QA')
            ->assertDontSee('Kapasitas Murid')
            ->assertDontSee('<th>Status</th>', false)
            ->assertDontSee('Detail');
    }

    public function test_attendance_report_accepts_a_null_search_filter(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson(route('admin.rekapabsensi.data', ['search' => null]))
            ->assertOk()
            ->assertJsonStructure(['summary', 'rows']);

        $this->actingAs($admin)
            ->delete(route('admin.rekapabsensi.destroy'), ['search' => null])
            ->assertRedirect(route('admin.rekapabsensi'));
    }

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'Admin Kelas Test',
            'username' => 'admin-kelas-'.bin2hex(random_bytes(5)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);
    }
}
