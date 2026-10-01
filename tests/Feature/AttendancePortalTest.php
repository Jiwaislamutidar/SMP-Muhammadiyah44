<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\PresensiGuru;
use App\Models\PresensiPelajaran;
use App\Models\SesiPelajaran;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\GuruSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendancePortalTest extends TestCase
{
    use DatabaseTransactions;

    public function test_student_scan_expiry_and_session_close_update_attendance(): void
    {
        [$guruUser, $guru, $jadwal, $kelas] = $this->makeSchedule();
        [$studentUser, $siswa] = $this->makeStudent($kelas);
        [, $unscannedSiswa] = $this->makeStudent($kelas);

        $opened = $this->actingAs($guruUser)
            ->postJson(route('guru.sesi.open', $jadwal->id))
            ->assertOk()
            ->assertJsonPath('status_sesi', 'Berlangsung')
            ->assertJsonPath('jadwal_id', $jadwal->id)
            ->assertJsonPath('jadwal.id', $jadwal->id)
            ->assertJsonPath('jadwal.mapel', $jadwal->mapel->nama_mapel)
            ->assertJsonPath('jadwal.kelas', $kelas->nama_kelas)
            ->assertJsonStructure(['qr_svg', 'qr_expires_at']);

        $sesi = SesiPelajaran::where('jadwal_id', $jadwal->id)->firstOrFail();
        $this->assertSame(2, $sesi->presensiPelajarans()->count());

        $this->actingAs($studentUser)
            ->postJson(route('siswa.scan'), ['token' => $sesi->qr_token])
            ->assertOk()
            ->assertJsonPath('status', 'Hadir');

        $sesi->update(['qr_expires_at' => now()->subSecond()]);
        $this->actingAs($studentUser)
            ->postJson(route('siswa.scan'), ['token' => $sesi->qr_token])
            ->assertUnprocessable();

        $this->actingAs($guruUser)
            ->postJson(route('guru.sesi.close', $sesi->id))
            ->assertOk()
            ->assertJsonPath('status_sesi', 'Selesai');

        $this->assertSame('Hadir', PresensiPelajaran::where('siswa_id', $siswa->id)->value('status'));
        $this->assertSame('Alfa', PresensiPelajaran::where('siswa_id', $unscannedSiswa->id)->value('status'));
    }

    public function test_guru_can_cancel_a_session_and_cascade_delete_student_attendance(): void
    {
        [$guruUser, , $jadwal, $kelas] = $this->makeSchedule();
        $this->makeStudent($kelas);

        $this->actingAs($guruUser)->postJson(route('guru.sesi.open', $jadwal->id))->assertOk();
        $sesi = SesiPelajaran::where('jadwal_id', $jadwal->id)->firstOrFail();
        $presensiId = $sesi->presensiPelajarans()->value('id');

        $this->deleteJson(route('guru.sesi.destroy', $sesi->id))
            ->assertOk()
            ->assertJsonPath('id', $sesi->id)
            ->assertJsonPath('status_sesi', 'Dibatalkan');

        $this->assertDatabaseMissing('sesi_pelajarans', ['id' => $sesi->id]);
        $this->assertDatabaseMissing('presensi_pelajarans', ['id' => $presensiId]);
    }

    public function test_guru_cannot_delete_another_teachers_session(): void
    {
        [$owner, , $jadwal, $kelas] = $this->makeSchedule();
        [$otherTeacher] = $this->makeTeacher();
        $this->makeStudent($kelas);
        $this->actingAs($owner)->postJson(route('guru.sesi.open', $jadwal->id))->assertOk();
        $sesi = SesiPelajaran::where('jadwal_id', $jadwal->id)->firstOrFail();

        $this->actingAs($otherTeacher)
            ->deleteJson(route('guru.sesi.destroy', $sesi->id))
            ->assertForbidden();

        $this->assertDatabaseHas('sesi_pelajarans', ['id' => $sesi->id]);
        $this->assertSame(1, $sesi->presensiPelajarans()->count());
    }

    public function test_riwayat_can_delete_session_logs_without_deleting_teacher_daily_attendance(): void
    {
        [$guruUser, $guru, $jadwal, $kelas] = $this->makeSchedule();
        $this->makeStudent($kelas);
        $this->actingAs($guruUser)->postJson(route('guru.sesi.open', $jadwal->id))->assertOk();
        $sesi = SesiPelajaran::where('jadwal_id', $jadwal->id)->firstOrFail();
        $presensiId = $sesi->presensiPelajarans()->value('id');
        $this->actingAs($guruUser)->postJson(route('guru.sesi.close', $sesi->id))->assertOk();
        $presensiHarian = PresensiGuru::create([
            'guru_id' => $guru->id,
            'tanggal' => today(),
            'status' => 'Hadir',
        ]);

        $this->actingAs($guruUser)
            ->get(route('guru.riwayat-presensi'))
            ->assertOk()
            ->assertSee('Hapus Log');

        $this->actingAs($guruUser)
            ->deleteJson(route('guru.sesi.destroy', $sesi->id))
            ->assertOk();

        $this->assertDatabaseMissing('sesi_pelajarans', ['id' => $sesi->id]);
        $this->assertDatabaseMissing('presensi_pelajarans', ['id' => $presensiId]);
        $this->assertDatabaseHas('presensi_gurus', ['id' => $presensiHarian->id]);
    }

    public function test_teacher_attendance_photos_are_saved_to_public_storage(): void
    {
        [$guruUser, $guru] = $this->makeTeacher();
        Storage::fake('public');

        $this->actingAs($guruUser)
            ->post(route('guru.presensi.masuk'), ['foto' => UploadedFile::fake()->image('masuk.jpg')])
            ->assertRedirect();

        $presensi = PresensiGuru::where('guru_id', $guru->id)->whereDate('tanggal', today())->firstOrFail();
        Storage::disk('public')->assertExists($presensi->foto_masuk);
        $this->assertNotNull($presensi->jam_masuk);

        $this->actingAs($guruUser)
            ->post(route('guru.presensi.pulang'), ['foto' => UploadedFile::fake()->image('pulang.jpg')])
            ->assertRedirect();

        $this->assertNotNull($presensi->fresh()->jam_pulang);
        Storage::disk('public')->assertExists($presensi->fresh()->foto_pulang);
    }

    public function test_guru_portal_pages_render_schedule_data(): void
    {
        [$guruUser, $guru, $jadwal] = $this->makeSchedule();
        [, $guruLain] = $this->makeTeacher();
        $kelasLain = Kelas::create(['nama_kelas' => 'Kelas Lain QA '.bin2hex(random_bytes(3))]);
        $mapelLain = Mapel::create(['kode' => 'QA-'.bin2hex(random_bytes(4)), 'nama_mapel' => 'Mapel Guru Lain QA']);
        JadwalPelajaran::create([
            'guru_id' => $guruLain->id,
            'kelas_id' => $kelasLain->id,
            'mapel_id' => $mapelLain->id,
            'hari' => now()->locale('id')->isoFormat('dddd'),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:00:00',
            'ruangan' => 'Ruang Lain QA',
        ]);
        $routeNames = [
            'guru.dashboard',
            'guru.jadwal',
            'guru.presensi-guru',
            'guru.presensi-murid',
            'guru.profil',
            'guru.riwayat-presensi',
        ];

        foreach ($routeNames as $routeName) {
            $this->actingAs($guruUser)
                ->get(route($routeName))
                ->assertOk();
        }

        $this->actingAs($guruUser)
            ->get(route('guru.presensi-murid'))
            ->assertSee($jadwal->mapel->nama_mapel)
            ->assertSee($jadwal->kelas->nama_kelas)
            ->assertSee($guru->nama_lengkap)
            ->assertSee('Mulai presensi?')
            ->assertSee('Ya, Mulai')
            ->assertSee('Batalkan Presensi')
            ->assertDontSee($mapelLain->nama_mapel)
            ->assertDontSee('Ust. Ahmad Fauzi');
    }

    public function test_guru_role_can_open_dashboard_without_prelinked_profile(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $guruUser = User::create([
            'name' => 'Guru Orphan QA '.$suffix,
            'username' => 'guru-orphan-'.$suffix,
            'email' => null,
            'role' => 'guru',
            'password' => Hash::make('test-password'),
        ]);

        $this->actingAs($guruUser)
            ->get(route('guru.dashboard'))
            ->assertOk();

        $this->assertDatabaseHas('gurus', [
            'user_id' => $guruUser->id,
            'nama_lengkap' => $guruUser->name,
        ]);
    }

    public function test_guru_seeder_creates_deterministic_login_and_preserves_schedule_assignment(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $guru = Guru::create([
            'nama_lengkap' => 'Guru Seeder QA '.$suffix,
            'status' => 'aktif',
        ]);
        $kelas = Kelas::create(['nama_kelas' => 'Seeder QA '.$suffix]);
        $mapel = Mapel::create(['kode' => 'SEED-'.$suffix, 'nama_mapel' => 'Mapel Seeder QA']);
        $jadwal = JadwalPelajaran::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'hari' => now()->locale('id')->isoFormat('dddd'),
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:00:00',
            'ruangan' => 'Ruang Seeder QA',
        ]);

        (new GuruSeeder())->run();

        $username = 'GURU'.str_pad((string) $guru->id, 3, '0', STR_PAD_LEFT);
        $guru->refresh();
        $user = $guru->user;

        $this->assertNotNull($user);
        $this->assertSame($username, $user->username);
        $this->assertSame('guru', $user->role);
        $this->assertTrue(Hash::check($username, $user->password));
        $this->assertSame($guru->id, $jadwal->fresh()->guru_id);

        (new GuruSeeder())->run();
        $this->assertSame(1, User::where('username', $username)->count());
        $this->assertSame($user->id, $guru->fresh()->user_id);

        $this->post(route('guru.login'), [
            'username' => $username,
            'password' => $username,
        ])->assertRedirect(route('guru.dashboard'));
    }

    private function makeSchedule(): array
    {
        [$guruUser, $guru] = $this->makeTeacher();
        $kelas = Kelas::create(['nama_kelas' => 'QA-'.bin2hex(random_bytes(4))]);
        $mapel = Mapel::create(['kode' => 'QA-'.bin2hex(random_bytes(4)), 'nama_mapel' => 'Mapel QA']);
        $jadwal = JadwalPelajaran::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'hari' => now()->locale('id')->isoFormat('dddd'),
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:00:00',
            'ruangan' => 'Ruang QA',
        ]);

        return [$guruUser, $guru, $jadwal, $kelas];
    }

    private function makeTeacher(): array
    {
        $suffix = bin2hex(random_bytes(4));
        $user = User::create([
            'name' => 'Guru QA '.$suffix,
            'username' => 'guru-qa-'.$suffix,
            'email' => null,
            'role' => 'guru',
            'password' => Hash::make('test-password'),
        ]);
        $guru = Guru::create([
            'user_id' => $user->id,
            'nama_lengkap' => $user->name,
            'status' => 'aktif',
        ]);

        return [$user, $guru];
    }

    private function makeStudent(Kelas $kelas): array
    {
        $unique = bin2hex(random_bytes(4));
        $user = User::create([
            'name' => 'Siswa QA '.$unique,
            'username' => 'siswa-qa-'.$unique,
            'email' => null,
            'role' => 'siswa',
            'password' => Hash::make('test-password'),
        ]);
        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nisn' => 'qa-'.$unique,
            'nama_lengkap' => 'Siswa QA '.$unique,
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);

        return [$user, $siswa];
    }
}