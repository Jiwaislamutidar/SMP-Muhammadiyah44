<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SiswaPortalTest extends TestCase
{
    use DatabaseTransactions;

    public function test_student_pages_render_with_database_profile_data(): void
    {
        [$user] = $this->makeStudent();
        $this->actingAs($user);

        foreach (['siswa.dashboard', 'siswa.scan-qr', 'siswa.riwayat', 'siswa.profil'] as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee('Ahmad Fauzan');
        }
    }

    public function test_student_can_change_password_with_the_current_password(): void
    {
        [$user] = $this->makeStudent();

        $this->actingAs($user)
            ->put(route('siswa.profil.update-password'), [
                'current_password' => 'old-secret',
                'password' => 'new-secret',
                'password_confirmation' => 'new-secret',
            ])
            ->assertRedirect(route('siswa.profil'))
            ->assertSessionHas('success', 'Kata sandi berhasil diperbarui!');

        $this->assertTrue(Hash::check('new-secret', $user->fresh()->password));
    }

    public function test_student_cannot_change_password_with_an_incorrect_current_password(): void
    {
        [$user] = $this->makeStudent();
        $originalPassword = $user->password;

        $this->actingAs($user)
            ->from(route('siswa.profil'))
            ->put(route('siswa.profil.update-password'), [
                'current_password' => 'incorrect-secret',
                'password' => 'new-secret',
                'password_confirmation' => 'new-secret',
            ])
            ->assertRedirect(route('siswa.profil'))
            ->assertSessionHasErrors('current_password');

        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    private function makeStudent(): array
    {
        $suffix = bin2hex(random_bytes(5));
        $user = User::create([
            'name' => 'Ahmad Fauzan',
            'username' => 'qa-siswa-' . $suffix,
            'email' => null,
            'role' => 'siswa',
            'password' => Hash::make('old-secret'),
        ]);
        $kelas = Kelas::create(['nama_kelas' => 'QA-' . $suffix]);
        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nisn' => 'qa-' . $suffix,
            'nama_lengkap' => 'Ahmad Fauzan',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);

        return [$user, $siswa];
    }
}