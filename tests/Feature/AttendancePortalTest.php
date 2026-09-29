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
        [$guruUser, , $jadwal] = $this->makeSchedule();
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
            ->get(route('guru.jadwal'))
            ->assertSee($jadwal->mapel->nama_mapel)
            ->assertSee($jadwal->kelas->nama_kelas);
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