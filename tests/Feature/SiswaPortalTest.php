<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\PresensiPelajaran;
use App\Models\SesiPelajaran;
use App\Models\Siswa;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
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
                ->assertSee('Nadia Rahma QA')
                ->assertSee('siswa-ui.css?v=4')
                ->assertSee(match ($routeName) {
                    'siswa.dashboard' => 'dashboardsiswa.css?v=12',
                    'siswa.scan-qr' => 'scanqrsiswa.css?v=7',
                    'siswa.riwayat' => 'riwayatsiswa.css?v=9',
                    'siswa.profil' => 'profilsiswa.css?v=5',
                })
                ->assertSee('siswa-ui.js?v=3')
                ->assertSee('aria-haspopup="dialog"', false)
                ->assertSee('Notifikasi')
                ->assertSee('Belum ada notifikasi presensi.');
        }

        $this->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('dashboardsiswa.css?v=12');

        $this->get(route('siswa.riwayat'))
            ->assertOk()
            ->assertSee('riwayatsiswa.css?v=9')
            ->assertSee('name="periode"', false)
            ->assertSee('name="mapel_id"', false)
            ->assertSee('name="status"', false);

        $this->get(route('siswa.scan-qr'))
            ->assertDontSee('Ahmad Fauzan')
            ->assertSee('NR')
            ->assertSee('html5-qrcode.min.js?v=2.3.8');
    }

    public function test_inline_scripts_on_student_pages_are_valid_javascript(): void
    {
        $node = (new ExecutableFinder())->find('node');
        if ($node === null) {
            $this->markTestSkipped('Node.js is required to validate rendered inline scripts.');
        }

        [$user] = $this->makeStudent();
        $this->actingAs($user);

        foreach (['siswa.dashboard', 'siswa.scan-qr', 'siswa.riwayat', 'siswa.profil'] as $routeName) {
            $html = $this->get(route($routeName))->assertOk()->getContent();
            preg_match_all('~<script\b(?![^>]*\bsrc\s*=)[^>]*>(.*?)</script\s*>~is', $html, $matches);
            $this->assertNotEmpty($matches[1], "No inline scripts found for {$routeName}.");

            foreach ($matches[1] as $scriptIndex => $script) {
                $temporaryFile = tempnam(sys_get_temp_dir(), 'student-inline-script-');
                $this->assertNotFalse($temporaryFile);
                $javascriptFile = $temporaryFile.'.cjs';
                $this->assertTrue(rename($temporaryFile, $javascriptFile));

                try {
                    $this->assertNotFalse(file_put_contents($javascriptFile, $script));
                    $process = new Process([$node, '--check', $javascriptFile]);
                    $process->run();
                    $this->assertTrue(
                        $process->isSuccessful(),
                        "{$routeName} inline script {$scriptIndex}: ".$process->getErrorOutput()
                    );
                } finally {
                    if (is_file($javascriptFile)) {
                        unlink($javascriptFile);
                    }
                }
            }
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

    public function test_dashboard_attendance_rate_counts_sick_as_not_present(): void
    {
        [$user, $student] = $this->makeStudent();
        $presentAttendance = $this->makeHistoryAttendance($student);
        $sickAttendance = $this->makeHistoryAttendance($student);
        $sickAttendance->update(['status' => 'Sakit']);

        $this->actingAs($user)
            ->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('ring-num">50<small>%</small>', false);

        $this->assertSame('Hadir', $presentAttendance->fresh()->status);
        $this->assertSame('Sakit', $sickAttendance->fresh()->status);
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
            ->assertSessionHas('success', '1 riwayat presensi disembunyikan dari daftar Anda.');

        $this->assertDatabaseHas('presensi_pelajarans', ['id' => $studentAttendance->id]);
        $this->assertNotNull($studentAttendance->fresh()->hidden_from_student_at);
        $this->assertNull($otherAttendance->fresh()->hidden_from_student_at);
    }

    public function test_student_history_delete_hides_only_hadir_and_izin(): void
    {
        [$user, $student] = $this->makeStudent();
        $attendances = collect(['Hadir', 'Izin', 'Sakit', 'Alfa'])
            ->map(function (string $status) use ($student): PresensiPelajaran {
                $attendance = $this->makeHistoryAttendance($student);
                $attendance->update(['status' => $status]);

                return $attendance;
            });

        $this->actingAs($user)
            ->post(route('siswa.riwayat.destroy'), [])
            ->assertRedirect(route('siswa.riwayat'))
            ->assertSessionHas('success', '2 riwayat presensi disembunyikan dari daftar Anda.');

        foreach ($attendances as $attendance) {
            $this->assertSame(
                in_array($attendance->status, ['Hadir', 'Izin'], true),
                $attendance->fresh()->hidden_from_student_at !== null
            );
        }

        $this->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('class="ring-num">0<small>%</small>', false)
            ->assertViewHas('ringkasan', fn (array $summary): bool => $summary['hadir'] === 0
                && $summary['izin'] === 0
                && $summary['sakit'] === 1
                && $summary['alfa'] === 1);
    }

    public function test_hidden_sick_and_alfa_remain_visible_and_included_in_student_summaries_and_pdf(): void
    {
        [$user, $student] = $this->makeStudent();
        $hiddenPresent = $this->makeHistoryAttendance($student);
        $hiddenPresent->forceFill(['hidden_from_student_at' => now()])->save();
        $hiddenSick = $this->makeHistoryAttendance($student);
        $hiddenSick->forceFill(['status' => 'Sakit', 'hidden_from_student_at' => now()])->save();
        $hiddenAlfa = $this->makeHistoryAttendance($student);
        $hiddenAlfa->forceFill(['status' => 'Alfa', 'hidden_from_student_at' => now()])->save();

        $this->actingAs($user)
            ->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('class="ring-num">0<small>%</small>', false)
            ->assertViewHas('ringkasan', fn (array $summary): bool => $summary['sakit'] === 1 && $summary['alfa'] === 1)
            ->assertViewHas('notifikasiPresensi', fn (array $notifications): bool => collect($notifications)->pluck('status')->sort()->values()->all() === ['Alfa', 'Sakit']);

        $this->get(route('siswa.riwayat'))
            ->assertOk()
            ->assertSee('badge-sakit')
            ->assertSee('badge-alfa')
            ->assertSee('Tidak ada riwayat yang dapat dihapus.')
            ->assertSee('disabled', false)
            ->assertViewHas('ringkasan', fn (array $summary): bool => $summary['sakit'] === 1 && $summary['alfa'] === 1);

        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('download')->once()->andReturn(response('PDF test'));
        Pdf::shouldReceive('loadView')
            ->once()
            ->with('pages.siswa.rekap-presensi-pdf', \Mockery::on(function (array $data) use ($student, $hiddenSick, $hiddenAlfa): bool {
                $this->assertSame([$hiddenSick->id, $hiddenAlfa->id], $data['riwayatPresensi']->modelKeys());
                $this->assertSame($student->id, $data['riwayatPresensi']->first()->siswa_id);

                return true;
            }))
            ->andReturn($pdf);

        $this->get(route('siswa.riwayat.export'))
            ->assertOk()
            ->assertSee('PDF test');
    }

    public function test_student_cannot_hide_sick_or_alfa_by_filtering_history(): void
    {
        [$user, $student] = $this->makeStudent();
        $sickAttendance = $this->makeHistoryAttendance($student);
        $sickAttendance->update(['status' => 'Sakit']);

        $this->actingAs($user)
            ->from(route('siswa.riwayat', ['status' => 'Sakit']))
            ->post(route('siswa.riwayat.destroy'), ['status' => 'Sakit'])
            ->assertRedirect(route('siswa.riwayat', ['status' => 'Sakit']))
            ->assertSessionHas('error', 'Tidak ada riwayat yang dapat dihapus.');

        $this->assertNull($sickAttendance->fresh()->hidden_from_student_at);
    }

    public function test_hidden_hadir_still_blocks_scanning_again_for_the_same_session(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(10));
        $kelas = Kelas::create(['nama_kelas' => 'QA-HIDDEN-SCAN-' . bin2hex(random_bytes(4))]);
        [$user, $student] = $this->makeStudent($kelas);
        [$sesi] = $this->makeOpenSession($kelas);
        $attendance = $this->makeSessionAttendance($sesi, $student);
        $attendance->forceFill([
            'status' => 'Hadir',
            'waktu_scan' => now()->subMinute(),
            'hidden_from_student_at' => now(),
        ])->save();

        $this->actingAs($user)
            ->postJson(route('siswa.scan'), ['token' => $sesi->qr_token])
            ->assertOk()
            ->assertJsonPath('message', 'Presensi Anda sudah tercatat.')
            ->assertJsonPath('status', 'Hadir');

        $this->assertSame('Hadir', $attendance->fresh()->status);
        $this->assertNotNull($attendance->fresh()->hidden_from_student_at);
        $this->travelBack();
    }

    public function test_scanned_history_and_dashboard_are_scoped_to_the_logged_in_student(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(10));
        $kelas = Kelas::create(['nama_kelas' => 'QA-SAME-' . bin2hex(random_bytes(4))]);
        [$userA, $studentA] = $this->makeStudent($kelas);
        [$userB, $studentB] = $this->makeStudent($kelas);
        $differentClass = Kelas::create(['nama_kelas' => 'QA-OTHER-' . bin2hex(random_bytes(4))]);
        [$userC, $studentC] = $this->makeStudent($differentClass);

        [$sesi, $mapel] = $this->makeOpenSession($kelas);
        $attendanceA = $this->makeSessionAttendance($sesi, $studentA);
        $attendanceB = $this->makeSessionAttendance($sesi, $studentB);
        [$otherSesi, $otherMapel] = $this->makeOpenSession($differentClass);
        $attendanceC = $this->makeSessionAttendance($otherSesi, $studentC);

        $this->actingAs($userA)
            ->postJson(route('siswa.scan'), ['token' => $sesi->qr_token])
            ->assertOk()
            ->assertJsonPath('status', 'Hadir');

        $this->assertSame('Hadir', $attendanceA->fresh()->status);
        $this->assertSame($studentA->id, $attendanceA->fresh()->siswa_id);
        $this->assertSame('Belum Absen', $attendanceB->fresh()->status);
        $this->assertSame($studentB->id, $attendanceB->fresh()->siswa_id);

        $this->actingAs($userA)
            ->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('notification-item', false)
            ->assertSee($mapel->nama_mapel);

        $this->actingAs($userB)
            ->get(route('siswa.riwayat'))
            ->assertOk()
            ->assertSee($mapel->nama_mapel)
            ->assertSee('badge-belum-absen')
            ->assertDontSee('badge-hadir')
            ->assertDontSee($otherMapel->nama_mapel);

        $this->actingAs($userB)
            ->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('Belum Absen');

        $this->actingAs($userB)
            ->get(route('siswa.profil'))
            ->assertOk()
            ->assertSee('Presensi Belum Absen')
            ->assertDontSee('Presensi Hadir');

        $this->actingAs($userA)
            ->post(route('siswa.riwayat.destroy'), [])
            ->assertRedirect(route('siswa.riwayat'))
            ->assertSessionHas('success');

        $this->assertNotNull($attendanceA->fresh()->hidden_from_student_at);
        $this->assertNull($attendanceB->fresh()->hidden_from_student_at);

        $this->actingAs($userB)
            ->get(route('siswa.riwayat'))
            ->assertOk()
            ->assertSee($mapel->nama_mapel)
            ->assertSee('badge-belum-absen');

        $this->actingAs($userC)
            ->get(route('siswa.riwayat'))
            ->assertOk()
            ->assertSee($otherMapel->nama_mapel)
            ->assertDontSee($mapel->nama_mapel);

        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->twice()->with('a4', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('download')->twice()->andReturn(response('PDF test'));
        Pdf::shouldReceive('loadView')
            ->once()
            ->with('pages.siswa.rekap-presensi-pdf', \Mockery::on(function (array $data) use ($studentB, $attendanceB): bool {
                $this->assertSame([$attendanceB->id], $data['riwayatPresensi']->modelKeys());
                $this->assertSame($studentB->id, $data['riwayatPresensi']->first()->siswa_id);

                return true;
            }))
            ->andReturn($pdf);
        Pdf::shouldReceive('loadView')
            ->once()
            ->with('pages.siswa.rekap-presensi-pdf', \Mockery::on(function (array $data) use ($studentC, $attendanceC): bool {
                $this->assertSame([$attendanceC->id], $data['riwayatPresensi']->modelKeys());
                $this->assertSame($studentC->id, $data['riwayatPresensi']->first()->siswa_id);

                return true;
            }))
            ->andReturn($pdf);

        $this->actingAs($userB)
            ->get(route('siswa.riwayat.export'))
            ->assertOk()
            ->assertSee('PDF test');

        $this->actingAs($userC)
            ->get(route('siswa.riwayat.export'))
            ->assertOk()
            ->assertSee('PDF test');

        $this->travelBack();
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

    private function makeStudent(?Kelas $kelas = null): array
    {
        $suffix = bin2hex(random_bytes(5));
        $user = User::create([
            'name' => 'Nadia Rahma QA',
            'username' => 'qa-siswa-' . $suffix,
            'email' => null,
            'role' => 'siswa',
            'password' => Hash::make('old-secret'),
        ]);
        $kelas ??= Kelas::create(['nama_kelas' => 'QA-' . $suffix]);
        $siswa = Siswa::create([
            'user_id' => $user->id,
            'nisn' => 'qa-' . $suffix,
            'nama_lengkap' => 'Nadia Rahma QA',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);

        return [$user, $siswa];
    }

    private function makeOpenSession(Kelas $kelas): array
    {
        $suffix = bin2hex(random_bytes(5));
        $guru = Guru::create([
            'nama_lengkap' => 'Guru Scan QA ' . $suffix,
            'status' => 'aktif',
        ]);
        $mapel = Mapel::create([
            'kode' => 'SCAN-' . $suffix,
            'nama_mapel' => 'Mapel Scan QA ' . $suffix,
        ]);
        $jadwal = JadwalPelajaran::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'hari' => now()->locale('id')->isoFormat('dddd'),
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '11:00:00',
        ]);
        $sesi = SesiPelajaran::create([
            'jadwal_id' => $jadwal->id,
            'guru_id' => $guru->id,
            'tanggal' => today()->toDateString(),
            'qr_token' => 'scan-'.$suffix,
            'qr_expires_at' => now()->addHour(),
            'status_sesi' => 'Berlangsung',
        ]);

        return [$sesi, $mapel];
    }

    private function makeSessionAttendance(SesiPelajaran $sesi, Siswa $student): PresensiPelajaran
    {
        return PresensiPelajaran::create([
            'sesi_pelajaran_id' => $sesi->id,
            'siswa_id' => $student->id,
            'status' => 'Belum Absen',
        ]);
    }
}
