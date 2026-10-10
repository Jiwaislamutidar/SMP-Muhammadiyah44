<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Mapel;
use App\Models\PresensiPelajaran;
use App\Models\Siswa;
use App\Models\SesiPelajaran;
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
                ->assertSee('Nadia Rahma QA');
        }

        $this->get(route('siswa.scan-qr'))
            ->assertDontSee('Ahmad Fauzan')
            ->assertSee('NR');
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

    public function test_student_login_rejects_non_string_credentials(): void
    {
        $this->postJson(route('login'), [
            'username' => ['malformed'],
            'password' => ['malformed'],
        ])->assertUnprocessable()->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_opening_student_login_does_not_log_out_authenticated_student(): void
    {
        [$user] = $this->makeStudent();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('siswa.dashboard'));

        $this->assertAuthenticatedAs($user);
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

    public function test_student_can_hide_only_their_own_history_without_deleting_attendance_records(): void
    {
        [$user, $student] = $this->makeStudent();
        [, $otherStudent] = $this->makeStudent();
        $studentAttendance = $this->makeHistoryAttendance($student);
        $otherAttendance = $this->makeHistoryAttendance($otherStudent);

        $this->actingAs($user)
            ->post(route('siswa.riwayat.destroy'), [])
            ->assertRedirect(route('siswa.riwayat'))
            ->assertSessionHas('success', 'Riwayat presensi berhasil dihapus dari daftar Anda.');

        $this->assertDatabaseHas('presensi_pelajarans', ['id' => $studentAttendance->id]);
        $this->assertNotNull($studentAttendance->fresh()->hidden_from_student_at);
        $this->assertNull($otherAttendance->fresh()->hidden_from_student_at);
    }

    public function test_guest_cannot_hide_student_history(): void
    {
        [, $student] = $this->makeStudent();
        $attendance = $this->makeHistoryAttendance($student);

        $this->post(route('siswa.riwayat.destroy'), [])
            ->assertRedirect(route('login'));

        $this->assertNull($attendance->fresh()->hidden_from_student_at);
    }

    private function makeHistoryAttendance(Siswa $student): PresensiPelajaran
    {
        $suffix = bin2hex(random_bytes(5));
        $guru = Guru::create([
            'nama_lengkap' => 'Guru Riwayat QA '.$suffix,
            'status' => 'aktif',
        ]);
        $mapel = Mapel::create([
            'kode' => 'RIWAYAT-'.$suffix,
            'nama_mapel' => 'Mapel Riwayat QA '.$suffix,
        ]);
        $jadwal = JadwalPelajaran::create([
            'guru_id' => $guru->id,
            'kelas_id' => $student->kelas_id,
            'mapel_id' => $mapel->id,
            'hari' => 'Senin',
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '10:00:00',
        ]);
        $sesi = SesiPelajaran::create([
            'jadwal_id' => $jadwal->id,
            'guru_id' => $guru->id,
            'tanggal' => today()->toDateString(),
            'status_sesi' => 'Selesai',
        ]);

        return PresensiPelajaran::create([
            'sesi_pelajaran_id' => $sesi->id,
            'siswa_id' => $student->id,
            'status' => 'Hadir',
        ]);
    }

    private function makeStudent(): array
    {
        $suffix = bin2hex(random_bytes(5));
        $user = User::create([
            'name' => 'Nadia Rahma QA',
            'username' => 'qa-siswa-' . $suffix,
            'email' => null,
            'role' => 'siswa',
            'password' => Hash::make('old-secret'),
        ]);
        $kelas = Kelas::create(['nama_kelas' => 'QA-' . $suffix]);
        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nisn' => 'qa-' . $suffix,
            'nama_lengkap' => 'Nadia Rahma QA',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);

        return [$user, $siswa];
    }
}