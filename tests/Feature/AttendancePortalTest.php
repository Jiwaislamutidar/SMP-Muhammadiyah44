<?php

namespace Tests\Feature;

use App\Models\DashboardAttendanceLog;
use App\Models\DashboardAttendanceSessionLog;
use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\PresensiGuru;
use App\Models\PresensiPelajaran;
use App\Models\SesiPelajaran;
use App\Models\Siswa;
use App\Models\User;
use App\Services\AttendanceStatusService;
use Carbon\Carbon;
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

        $waktuScan = PresensiPelajaran::where('siswa_id', $siswa->id)->value('waktu_scan');
        $this->actingAs($studentUser)
            ->postJson(route('siswa.scan'), ['token' => $sesi->qr_token])
            ->assertOk()
            ->assertJsonPath('message', 'Presensi Anda sudah tercatat.')
            ->assertJsonPath('status', 'Hadir');
        $this->assertEquals($waktuScan, PresensiPelajaran::where('siswa_id', $siswa->id)->value('waktu_scan'));

        $sesi->update(['qr_expires_at' => now()->addMinute()]);
        $this->travelTo($sesi->fresh()->qr_expires_at);
        $this->actingAs($studentUser)
            ->postJson(route('siswa.scan'), ['token' => $sesi->qr_token])
            ->assertUnprocessable();
        $this->travelBack();

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

    public function test_teacher_can_download_pdf_for_a_completed_historical_session_only(): void
    {
        [$guruUser, , $jadwal, $kelas] = $this->makeSchedule();
        [$otherGuruUser] = $this->makeTeacher();
        [, $siswa] = $this->makeStudent($kelas);
        $sesi = SesiPelajaran::create([
            'jadwal_id' => $jadwal->id,
            'guru_id' => $jadwal->guru_id,
            'tanggal' => now()->subDay()->toDateString(),
            'status_sesi' => 'Selesai',
        ]);
        PresensiPelajaran::create([
            'sesi_pelajaran_id' => $sesi->id,
            'siswa_id' => $siswa->id,
            'status' => 'Hadir',
        ]);
        $rekapHtml = view('pages.guru.rekap-presensi-pdf', [
            'sesi' => $sesi->load(['jadwal.mapel', 'jadwal.kelas', 'presensiPelajarans.siswa.kelas']),
        ])->render();
        foreach ([$jadwal->mapel->nama_mapel, $kelas->nama_kelas, $siswa->nama_lengkap, '07:00', 'Hadir'] as $expected) {
            $this->assertStringContainsString($expected, $rekapHtml);
        }

        $this->actingAs($guruUser)
            ->get(route('guru.presensi-murid', ['sesi' => $sesi->id]))
            ->assertOk()
            ->assertSee('data-session-id="'.$sesi->id.'"', false)
            ->assertSee('id="downloadSessionPdf" type="button"', false)
            ->assertDontSee('id="downloadSessionPdf" type="button" disabled', false)
            ->assertSee('Unduh PDF Rekap');

        $this->actingAs($guruUser)
            ->get(route('guru.sesi.pdf', $sesi->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($otherGuruUser)
            ->get(route('guru.sesi.pdf', $sesi->id))
            ->assertForbidden();

        $sesiBerlangsung = SesiPelajaran::create([
            'jadwal_id' => $jadwal->id,
            'guru_id' => $jadwal->guru_id,
            'tanggal' => today()->toDateString(),
            'status_sesi' => 'Berlangsung',
        ]);

        $this->travelTo(today()->setTime(7, 30));
        $this->actingAs($guruUser)
            ->get(route('guru.presensi-murid', ['sesi' => $sesiBerlangsung->id]))
            ->assertOk()
            ->assertSee('id="downloadSessionPdf" type="button" disabled', false);

        $this->actingAs($guruUser)
            ->get(route('guru.sesi.pdf', $sesiBerlangsung->id))
            ->assertStatus(409);
        $this->travelBack();
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
        $this->travelTo(today()->setTime(7, 30));

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
        $this->assertDatabaseHas('dashboard_presensi_guru_logs', [
            'source_presensi_id' => $presensi->id,
            'foto_pulang' => $presensi->fresh()->foto_pulang,
        ]);
        $admin = User::create([
            'name' => 'Admin QA '.bin2hex(random_bytes(4)),
            'username' => 'admin-qa-'.bin2hex(random_bytes(4)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);
        $periodResponse = $this->actingAs($admin)
            ->getJson(route('admin.dashboard.guru-period', ['periode' => 'hari']))
            ->assertOk()
            ->json();
        $teacherSessions = collect($periodResponse['hadir'])->firstWhere('id', $guru->id)['tanggal'];
        $masukEntry = collect($teacherSessions)->firstWhere('session_type', 'masuk');
        $pulangEntry = collect($teacherSessions)->firstWhere('session_type', 'pulang');
        $this->assertSame($presensi->fresh()->jam_masuk, $masukEntry['jam_masuk']);
        $this->assertSame($presensi->fresh()->jam_pulang, $pulangEntry['jam_pulang']);
        $this->assertSame(asset('storage/'.$presensi->fresh()->foto_masuk), $masukEntry['foto_masuk']);
        $this->assertSame(asset('storage/'.$presensi->fresh()->foto_pulang), $pulangEntry['foto_pulang']);

        $dashboard = $this->get(route('admin.dashboard'))->assertOk();
        $dashboard
            ->assertSee('value="semua"', false)
            ->assertSee('value="masuk"', false)
            ->assertSee('value="pulang"', false)
            ->assertSee('data-attendance-session="masuk"', false)
            ->assertSee('data-attendance-session="pulang"', false)
            ->assertSee('Lihat Foto')
            ->assertDontSee('<img src="'.asset('storage/'.$presensi->fresh()->foto_masuk), false)
            ->assertDontSee('<img src="'.asset('storage/'.$presensi->fresh()->foto_pulang), false);
        $this->travelBack();
    }

    public function test_teacher_history_reset_keeps_independent_admin_log_and_photo(): void
    {
        [$guruUser, $guru] = $this->makeTeacher();
        Storage::fake('public');
        $photoPath = 'presensi_guru/2026/11/check-in.jpg';
        Storage::disk('public')->put($photoPath, 'photo proof');
        $attendance = PresensiGuru::create([
            'guru_id' => $guru->id,
            'tanggal' => today(),
            'jam_masuk' => '07:30:00',
            'foto_masuk' => $photoPath,
            'status' => 'Hadir',
        ]);
        $log = DashboardAttendanceLog::where('source_presensi_id', $attendance->id)->firstOrFail();
        $sessionLog = DashboardAttendanceSessionLog::where('source_presensi_id', $attendance->id)
            ->where('session_type', 'masuk')
            ->firstOrFail();

        $this->actingAs($guruUser)
            ->delete(route('guru.presensi-riwayat.reset'))
            ->assertRedirect(route('guru.presensi-guru'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('presensi_gurus', ['id' => $attendance->id]);
        $this->assertDatabaseHas('dashboard_presensi_guru_logs', [
            'id' => $log->id,
            'foto_masuk' => $photoPath,
        ]);
        Storage::disk('public')->assertExists($photoPath);

        $admin = User::create([
            'name' => 'Admin QA '.bin2hex(random_bytes(4)),
            'username' => 'admin-qa-'.bin2hex(random_bytes(4)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);
        $periodResponse = $this->actingAs($admin)
            ->getJson(route('admin.dashboard.guru-period', ['periode' => 'hari']))
            ->assertOk()
            ->json();
        $teacherEntry = collect($periodResponse['hadir'])->firstWhere('id', $guru->id);
        $sessionEntry = collect($teacherEntry['tanggal'])->firstWhere('session_type', 'masuk');
        $this->assertSame($sessionLog->id, $sessionEntry['id']);
        $this->assertSame(asset('storage/'.$photoPath), $sessionEntry['foto_bukti']);
    }

    public function test_admin_can_delete_dashboard_logs_without_deleting_teacher_attendance(): void
    {
        [, $guru] = $this->makeTeacher();
        $attendance = PresensiGuru::create([
            'guru_id' => $guru->id,
            'tanggal' => today(),
            'status' => 'Hadir',
        ]);
        $log = DashboardAttendanceLog::where('source_presensi_id', $attendance->id)->firstOrFail();
        $admin = User::create([
            'name' => 'Admin QA '.bin2hex(random_bytes(4)),
            'username' => 'admin-qa-'.bin2hex(random_bytes(4)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);
        $this->actingAs($admin)
            ->delete(route('admin.dashboard.guru-log.destroy', $log))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('presensi_gurus', ['id' => $attendance->id]);
        $this->assertDatabaseMissing('dashboard_presensi_guru_logs', ['id' => $log->id]);
    }

    public function test_admin_can_delete_masuk_and_pulang_session_logs_independently_by_id(): void
    {
        [, $guruMasuk] = $this->makeTeacher();
        $attendanceMasuk = PresensiGuru::create([
            'guru_id' => $guruMasuk->id,
            'tanggal' => today(),
            'jam_masuk' => '07:30:00',
            'foto_masuk' => 'masuk.jpg',
            'jam_pulang' => '15:00:00',
            'foto_pulang' => 'pulang.jpg',
            'status' => 'Hadir',
        ]);
        $masukLog = DashboardAttendanceSessionLog::where('source_presensi_id', $attendanceMasuk->id)
            ->where('session_type', 'masuk')
            ->firstOrFail();
        $pulangLog = DashboardAttendanceSessionLog::where('source_presensi_id', $attendanceMasuk->id)
            ->where('session_type', 'pulang')
            ->firstOrFail();
        $admin = User::create([
            'name' => 'Admin QA '.bin2hex(random_bytes(4)),
            'username' => 'admin-qa-'.bin2hex(random_bytes(4)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.dashboard.guru-session-log.destroy', $masukLog), false)
            ->assertSee(route('admin.dashboard.guru-session-log.destroy', $pulangLog), false);

        $this->actingAs($admin)
            ->delete(route('admin.dashboard.guru-session-log.destroy', $masukLog))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('dashboard_presensi_guru_session_logs', ['id' => $masukLog->id]);
        $this->assertDatabaseHas('dashboard_presensi_guru_session_logs', ['id' => $pulangLog->id]);
        $this->assertDatabaseHas('presensi_gurus', ['id' => $attendanceMasuk->id]);
        $this->assertDatabaseHas('dashboard_presensi_guru_logs', [
            'source_presensi_id' => $attendanceMasuk->id,
            'jam_masuk' => '07:30:00',
            'jam_pulang' => '15:00:00',
        ]);
        $attendanceMasuk->update(['jam_pulang' => '15:01:00']);
        $this->assertDatabaseMissing('dashboard_presensi_guru_session_logs', ['id' => $masukLog->id]);
        $this->assertDatabaseHas('dashboard_presensi_guru_session_logs', ['id' => $pulangLog->id]);

        [, $guruPulang] = $this->makeTeacher();
        $attendancePulang = PresensiGuru::create([
            'guru_id' => $guruPulang->id,
            'tanggal' => today(),
            'jam_masuk' => '07:45:00',
            'foto_masuk' => 'masuk-2.jpg',
            'jam_pulang' => '15:10:00',
            'foto_pulang' => 'pulang-2.jpg',
            'status' => 'Hadir',
        ]);
        $masukLog = DashboardAttendanceSessionLog::where('source_presensi_id', $attendancePulang->id)
            ->where('session_type', 'masuk')
            ->firstOrFail();
        $pulangLog = DashboardAttendanceSessionLog::where('source_presensi_id', $attendancePulang->id)
            ->where('session_type', 'pulang')
            ->firstOrFail();

        $this->delete(route('admin.dashboard.guru-session-log.destroy', $pulangLog))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('dashboard_presensi_guru_session_logs', ['id' => $masukLog->id]);
        $this->assertDatabaseMissing('dashboard_presensi_guru_session_logs', ['id' => $pulangLog->id]);
        $this->assertDatabaseHas('presensi_gurus', ['id' => $attendancePulang->id]);
        $attendancePulang->update(['jam_masuk' => '07:46:00']);
        $this->assertDatabaseHas('dashboard_presensi_guru_session_logs', ['id' => $masukLog->id]);
        $this->assertDatabaseMissing('dashboard_presensi_guru_session_logs', ['id' => $pulangLog->id]);
    }

    public function test_admin_bulk_delete_only_removes_dashboard_logs_in_selected_period(): void
    {
        [, $recentGuru] = $this->makeTeacher();
        [, $oldGuru] = $this->makeTeacher();
        $recentAttendance = PresensiGuru::create([
            'guru_id' => $recentGuru->id,
            'tanggal' => today(),
            'jam_masuk' => '07:30:00',
            'jam_pulang' => '15:00:00',
            'status' => 'Hadir',
        ]);
        $oldAttendance = PresensiGuru::create([
            'guru_id' => $oldGuru->id,
            'tanggal' => today()->subDays(40),
            'jam_masuk' => '07:30:00',
            'jam_pulang' => '15:00:00',
            'status' => 'Hadir',
        ]);
        $recentLog = DashboardAttendanceLog::where('source_presensi_id', $recentAttendance->id)->firstOrFail();
        $oldLog = DashboardAttendanceLog::where('source_presensi_id', $oldAttendance->id)->firstOrFail();
        $admin = User::create([
            'name' => 'Admin QA '.bin2hex(random_bytes(4)),
            'username' => 'admin-qa-'.bin2hex(random_bytes(4)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.dashboard.guru-log.destroy-period'), ['periode' => 'bulan'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('dashboard_presensi_guru_logs', ['id' => $recentLog->id]);
        $this->assertDatabaseHas('dashboard_presensi_guru_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseMissing('dashboard_presensi_guru_session_logs', [
            'source_presensi_id' => $recentAttendance->id,
        ]);
        $this->assertDatabaseHas('dashboard_presensi_guru_session_logs', [
            'source_presensi_id' => $oldAttendance->id,
        ]);
        $this->assertDatabaseHas('presensi_gurus', ['id' => $recentAttendance->id]);
        $this->assertDatabaseHas('presensi_gurus', ['id' => $oldAttendance->id]);
    }

    public function test_expired_open_sessions_become_complete_and_unscanned_students_become_alfa(): void
    {
        [, , $jadwal, $kelas] = $this->makeSchedule();
        [, $siswa] = $this->makeStudent($kelas);
        [, , $jadwalBelumDibuka, $kelasBelumDibuka] = $this->makeSchedule();
        [, $siswaBelumDibuka] = $this->makeStudent($kelasBelumDibuka);

        $sesiBerlangsung = SesiPelajaran::create([
            'jadwal_id' => $jadwal->id,
            'guru_id' => $jadwal->guru_id,
            'tanggal' => today(),
            'status_sesi' => 'Berlangsung',
        ]);
        $presensiBerlangsung = PresensiPelajaran::create([
            'sesi_pelajaran_id' => $sesiBerlangsung->id,
            'siswa_id' => $siswa->id,
            'status' => 'Belum Absen',
        ]);
        $sesiBelumDibuka = SesiPelajaran::create([
            'jadwal_id' => $jadwalBelumDibuka->id,
            'guru_id' => $jadwalBelumDibuka->guru_id,
            'tanggal' => today(),
            'status_sesi' => 'Belum',
        ]);
        $presensiBelumDibuka = PresensiPelajaran::create([
            'sesi_pelajaran_id' => $sesiBelumDibuka->id,
            'siswa_id' => $siswaBelumDibuka->id,
            'status' => 'Belum Absen',
        ]);

        $this->travelTo(today()->setTime(8, 1));
        app(AttendanceStatusService::class)->completeExpiredStudentSessions();

        $this->assertSame('Selesai', $sesiBerlangsung->fresh()->status_sesi);
        $this->assertSame('Alfa', $presensiBerlangsung->fresh()->status);
        $this->assertSame('Belum', $sesiBelumDibuka->fresh()->status_sesi);
        $this->assertSame('Belum Absen', $presensiBelumDibuka->fresh()->status);
        $this->travelBack();
    }

    public function test_guru_cutoff_marks_only_missing_attendance_records_as_alfa(): void
    {
        [, $guruRecorded] = $this->makeTeacher();
        [, $guruMissing] = $this->makeTeacher();
        PresensiGuru::create([
            'guru_id' => $guruRecorded->id,
            'tanggal' => today(),
            'status' => 'Izin',
        ]);

        $attendanceStatus = app(AttendanceStatusService::class);
        $this->travelTo(today()->setTime(14, 59));
        $attendanceStatus->applyGuruAttendanceCutoff();
        $this->assertDatabaseMissing('presensi_gurus', [
            'guru_id' => $guruMissing->id,
            'tanggal' => today()->toDateString(),
        ]);

        $this->travelTo(today()->setTime(15, 0));
        $attendanceStatus->applyGuruAttendanceCutoff();

        $this->assertDatabaseHas('presensi_gurus', [
            'guru_id' => $guruRecorded->id,
            'tanggal' => today()->toDateString(),
            'status' => 'Izin',
        ]);
        $this->assertDatabaseHas('presensi_gurus', [
            'guru_id' => $guruMissing->id,
            'tanggal' => today()->toDateString(),
            'status' => 'Alfa',
            'jam_masuk' => null,
            'foto_masuk' => null,
            'jam_pulang' => null,
            'foto_pulang' => null,
        ]);
        $this->travelBack();
    }

    public function test_admin_dashboard_uses_distinct_student_attendance_and_renders_today_schedule_filters(): void
    {
        [, , $jadwalHadir] = $this->makeSchedule();
        [, , $jadwalAlfa] = $this->makeSchedule();
        [, , $jadwalIzin] = $this->makeSchedule();
        [, $siswaHadirDanAlfa] = $this->makeStudent($jadwalHadir->kelas);
        [, $siswaIzin] = $this->makeStudent($jadwalIzin->kelas);
        $userAdmin = User::create([
            'name' => 'Admin QA '.bin2hex(random_bytes(4)),
            'username' => 'admin-qa-'.bin2hex(random_bytes(4)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);
        $sesiHadir = SesiPelajaran::create([
            'jadwal_id' => $jadwalHadir->id,
            'guru_id' => $jadwalHadir->guru_id,
            'tanggal' => today(),
            'status_sesi' => 'Selesai',
        ]);
        $sesiAlfa = SesiPelajaran::create([
            'jadwal_id' => $jadwalAlfa->id,
            'guru_id' => $jadwalAlfa->guru_id,
            'tanggal' => today(),
            'status_sesi' => 'Selesai',
        ]);
        $sesiIzin = SesiPelajaran::create([
            'jadwal_id' => $jadwalIzin->id,
            'guru_id' => $jadwalIzin->guru_id,
            'tanggal' => today(),
            'status_sesi' => 'Selesai',
        ]);
        PresensiPelajaran::create([
            'sesi_pelajaran_id' => $sesiHadir->id,
            'siswa_id' => $siswaHadirDanAlfa->id,
            'status' => 'Hadir',
        ]);
        PresensiPelajaran::create([
            'sesi_pelajaran_id' => $sesiAlfa->id,
            'siswa_id' => $siswaHadirDanAlfa->id,
            'status' => 'Alfa',
        ]);
        PresensiPelajaran::create([
            'sesi_pelajaran_id' => $sesiIzin->id,
            'siswa_id' => $siswaIzin->id,
            'status' => 'Izin',
            'keterangan' => 'Pemeriksaan kesehatan',
        ]);
        PresensiGuru::create([
            'guru_id' => $jadwalHadir->guru_id,
            'tanggal' => today(),
            'status' => 'Hadir',
        ]);
        PresensiGuru::create([
            'guru_id' => $jadwalAlfa->guru_id,
            'tanggal' => today()->subDay(),
            'status' => 'Alfa',
        ]);
        $this->travelTo(today()->setTime(14, 59));

        $dashboard = $this->actingAs($userAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk();
        $dashboard
            ->assertSee('Murid Hadir')
            ->assertSee('Murid Alfa')
            ->assertSee('Murid Izin/Sakit')
            ->assertSee('dari '.Siswa::query()->where('status', 'aktif')->count().' siswa aktif')
            ->assertSee('Semua Kelas')
            ->assertSee($jadwalHadir->kelas->nama_kelas)
            ->assertDontSee('Lihat Daftar')
            ->assertDontSee('alfaModal')
            ->assertDontSee('izinSakitModal')
            ->assertDontSee('dash-quick-actions', false)
            ->assertDontSee('+ Guru')
            ->assertDontSee('+ Murid');
        $this->assertSame(3, substr_count($dashboard->getContent(), 'class="dash-kpi-num">1</div>'));
        $dashboard->assertViewHas('guruHadir', fn ($gurus) => $gurus->modelKeys() === [$jadwalHadir->guru_id]);
        $dashboard->assertViewHas('guruTidakHadir', fn ($gurus) => ! $gurus->contains('id', $jadwalAlfa->guru_id));

        $this->travelBack();
    }

    public function test_guru_must_upload_evidence_when_recording_izin_or_sakit(): void
    {
        [$guruUser, $guru] = $this->makeTeacher();
        $this->travelTo(today()->setTime(10, 0));

        $this->actingAs($guruUser)
            ->post(route('guru.presensi.izin'), ['status' => 'Izin'])
            ->assertSessionHasErrors('bukti_izin');

        $this->assertDatabaseMissing('presensi_gurus', [
            'guru_id' => $guru->id,
            'tanggal' => today()->toDateString(),
        ]);
        $this->travelBack();
    }

    public function test_admin_guru_attendance_period_groups_each_teacher_and_all_absence_dates(): void
    {
        [, $guruAbsent] = $this->makeTeacher();
        [, $guruPresent] = $this->makeTeacher();
        PresensiGuru::create([
            'guru_id' => $guruAbsent->id,
            'tanggal' => today()->subDays(5),
            'status' => 'Izin',
        ]);
        PresensiGuru::create([
            'guru_id' => $guruAbsent->id,
            'tanggal' => today()->subDay(),
            'status' => 'Sakit',
        ]);
        PresensiGuru::create([
            'guru_id' => $guruPresent->id,
            'tanggal' => today()->subDay(),
            'status' => 'Hadir',
        ]);
        PresensiGuru::create([
            'guru_id' => $guruPresent->id,
            'tanggal' => today(),
            'status' => 'Hadir',
        ]);
        $admin = User::create([
            'name' => 'Admin QA '.bin2hex(random_bytes(4)),
            'username' => 'admin-qa-'.bin2hex(random_bytes(4)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);

        $periodResponse = $this->actingAs($admin)
            ->getJson(route('admin.dashboard.guru-period', ['periode' => 'minggu']))
            ->assertOk()
            ->json();
        $absentGuru = collect($periodResponse['tidak_hadir'])->firstWhere('id', $guruAbsent->id);
        $presentGuru = collect($periodResponse['hadir'])->firstWhere('id', $guruPresent->id);
        $this->assertNotNull($absentGuru);
        $this->assertCount(2, $absentGuru['tanggal']);
        $this->assertSame(['Izin', 'Sakit'], collect($absentGuru['tanggal'])->pluck('status')->all());
        $this->assertNotNull($presentGuru);
        $this->assertNull(collect($periodResponse['tidak_hadir'])->firstWhere('id', $guruPresent->id));
        $this->assertSame(count($periodResponse['hadir']), $periodResponse['counts']['hadir']);
        $this->assertSame(count($periodResponse['tidak_hadir']), $periodResponse['counts']['tidak_hadir']);
    }

    public function test_admin_student_report_filters_rows_summaries_export_and_bulk_delete(): void
    {
        [, , $jadwal] = $this->makeSchedule();
        [, $siswa] = $this->makeStudent($jadwal->kelas);
        $sesiHariIni = SesiPelajaran::create([
            'jadwal_id' => $jadwal->id,
            'guru_id' => $jadwal->guru_id,
            'tanggal' => today(),
            'status_sesi' => 'Selesai',
        ]);
        $sesiKemarin = SesiPelajaran::create([
            'jadwal_id' => $jadwal->id,
            'guru_id' => $jadwal->guru_id,
            'tanggal' => today()->subDay(),
            'status_sesi' => 'Selesai',
        ]);
        $hadir = PresensiPelajaran::create([
            'sesi_pelajaran_id' => $sesiHariIni->id,
            'siswa_id' => $siswa->id,
            'status' => 'Hadir',
        ]);
        $izin = PresensiPelajaran::create([
            'sesi_pelajaran_id' => $sesiKemarin->id,
            'siswa_id' => $siswa->id,
            'status' => 'Izin',
        ]);
        $admin = User::create([
            'name' => 'Admin QA '.bin2hex(random_bytes(4)),
            'username' => 'admin-qa-'.bin2hex(random_bytes(4)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.rekapabsensi'))
            ->assertOk()
            ->assertSee($siswa->nama_lengkap)
            ->assertSee('Hapus Data')
            ->assertSee('Export Rekap')
            ->assertSee('type="date"', false)
            ->assertDontSee('00654321');

        $this->actingAs($admin)
            ->getJson(route('admin.rekapabsensi.data', ['date' => today()->toDateString()]))
            ->assertOk()
            ->assertJsonPath('summary.hadir', 1)
            ->assertJsonPath('summary.izin_sakit', 0)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.tanggal', today()->toDateString());

        $this->actingAs($admin)
            ->getJson(route('admin.rekapabsensi.data', [
                'date' => today()->subDay()->toDateString(),
                'kelas' => $jadwal->kelas_id,
                'status' => 'Izin',
            ]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.status', 'Izin');

        $export = $this->actingAs($admin)
            ->get(route('admin.rekapabsensi.export', [
                'date' => today()->subDay()->toDateString(),
                'kelas' => $jadwal->kelas_id,
                'status' => 'Izin',
            ]))
            ->assertOk();
        $this->assertStringContainsString($siswa->nama_lengkap, $export->streamedContent());

        $this->actingAs($admin)
            ->delete(route('admin.rekapabsensi.destroy'), [
                'date' => today()->subDay()->toDateString(),
                'kelas' => $jadwal->kelas_id,
                'status' => 'Izin',
            ])
            ->assertRedirect(route('admin.rekapabsensi'));
        $this->assertDatabaseHas('presensi_pelajarans', ['id' => $hadir->id]);
        $this->assertDatabaseMissing('presensi_pelajarans', ['id' => $izin->id]);
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
                ->assertOk()
                ->assertSee('aria-current="page"', false);
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

    public function test_guru_can_update_password_from_profile(): void
    {
        [$guruUser] = $this->makeTeacher();

        $this->actingAs($guruUser)
            ->put(route('guru.profil.update-password'), [
                'current_password' => 'test-password',
                'password' => 'new-test-password',
                'password_confirmation' => 'new-test-password',
            ])
            ->assertRedirect(route('guru.profil'))
            ->assertSessionHas('success', 'Kata sandi berhasil diperbarui!');

        $this->assertTrue(Hash::check('new-test-password', $guruUser->fresh()->password));
        $this->assertFalse(Hash::check('test-password', $guruUser->fresh()->password));
    }

    public function test_guru_password_update_rejects_incorrect_or_invalid_password_data(): void
    {
        [$guruUser] = $this->makeTeacher();

        $this->actingAs($guruUser)
            ->from(route('guru.profil'))
            ->put(route('guru.profil.update-password'), [
                'current_password' => 'incorrect-password',
                'password' => 'new-test-password',
                'password_confirmation' => 'new-test-password',
            ])
            ->assertRedirect(route('guru.profil'))
            ->assertSessionHasErrors('current_password');

        $this->actingAs($guruUser)
            ->from(route('guru.profil'))
            ->put(route('guru.profil.update-password'), [
                'current_password' => 'test-password',
                'password' => 'short',
                'password_confirmation' => 'different',
            ])
            ->assertRedirect(route('guru.profil'))
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('test-password', $guruUser->fresh()->password));
    }

    public function test_guru_profile_has_password_dialog_without_edit_or_device_information(): void
    {
        [$guruUser] = $this->makeTeacher();

        $this->actingAs($guruUser)
            ->get(route('guru.profil'))
            ->assertOk()
            ->assertSee('action="'.route('guru.profil.update-password').'"', false)
            ->assertSee('name="current_password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertDontSee('Edit Profil')
            ->assertDontSee('Browser Saat Ini')
            ->assertDontSee('IP Address')
            ->assertDontSee('<i></i>Aktif', false);
    }

    public function test_guru_schedule_displays_each_presence_block_once_and_opens_canonical_schedule(): void
    {
        $this->travelTo(Carbon::parse('2026-10-21 10:30:00'));
        [$guruUser, $guru, $jadwal, $kelas] = $this->makeSchedule();
        $hari = $jadwal->hari;
        $jadwal->update(['jam_mulai' => '09:20:00', 'jam_selesai' => '09:55:00']);
        $jadwalIstirahatKedua = JadwalPelajaran::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $jadwal->mapel_id,
            'hari' => $hari,
            'jam_mulai' => '10:15:00',
            'jam_selesai' => '10:45:00',
            'ruangan' => 'Ruang QA',
        ]);

        $mapelDuplikat = Mapel::create(['kode' => 'QA-'.bin2hex(random_bytes(4)), 'nama_mapel' => 'Mapel Duplikat QA']);
        $duplikat = collect([
            ['jam_mulai' => '10:45:00', 'jam_selesai' => '11:20:00'],
            ['jam_mulai' => '11:20:00', 'jam_selesai' => '11:55:00'],
            ['jam_mulai' => '10:45:00', 'jam_selesai' => '11:55:00'],
        ])->map(fn (array $jam) => JadwalPelajaran::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapelDuplikat->id,
            'hari' => $hari,
            ...$jam,
        ]));

        $mapelDzuhur = Mapel::create(['kode' => 'QA-'.bin2hex(random_bytes(4)), 'nama_mapel' => 'Mapel Dzuhur QA']);
        $jadwalDzuhurPertama = JadwalPelajaran::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapelDzuhur->id,
            'hari' => $hari,
            'jam_mulai' => '11:20:00',
            'jam_selesai' => '11:55:00',
        ]);
        JadwalPelajaran::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapelDzuhur->id,
            'hari' => $hari,
            'jam_mulai' => '13:00:00',
            'jam_selesai' => '13:30:00',
        ]);

        $this->actingAs($guruUser)->get(route('guru.jadwal'))->assertOk()
            ->assertSee('href="'.route('guru.presensi-murid', ['jadwal' => $jadwal->id]).'"', false)
            ->assertSee('3 jadwal hari ini');

        $response = $this->actingAs($guruUser)->get(route('guru.presensi-murid'))->assertOk()
            ->assertSee('3 jadwal dari database')
            ->assertSee('09:20 - 09:55, 10:15 - 10:45')
            ->assertSee('1 jam 5 menit')
            ->assertSee('10:45 - 11:55')
            ->assertSee('11:20 - 11:55, 13:00 - 13:30')
            ->assertSee('Mapel Duplikat QA')
            ->assertSee('Mapel Dzuhur QA');

        $this->assertSame(1, substr_count(
            $response->getContent(),
            'data-url="'.route('guru.sesi.open', ['jadwal' => $jadwal->id]).'"'
        ));
        $this->assertSame(0, substr_count(
            $response->getContent(),
            'data-url="'.route('guru.sesi.open', ['jadwal' => $jadwalIstirahatKedua->id]).'"'
        ));
        $this->assertSame(1, substr_count(
            $response->getContent(),
            'data-url="'.route('guru.sesi.open', ['jadwal' => $duplikat[2]->id]).'"'
        ));
        $this->assertSame(1, substr_count(
            $response->getContent(),
            'data-url="'.route('guru.sesi.open', ['jadwal' => $jadwalDzuhurPertama->id]).'"'
        ));
        $this->travelBack();
    }

    public function test_guru_notifications_cover_upcoming_ongoing_and_finished_sessions_for_the_owner(): void
    {
        [$guruUser, , $jadwal, $kelas] = $this->makeSchedule();
        [, $siswa] = $this->makeStudent($kelas);
        [, $siswaBelumAbsen] = $this->makeStudent($kelas);
        [$guruLainUser, , $jadwalGuruLain] = $this->makeSchedule();
        $jadwalGuruLain->mapel->update(['nama_mapel' => 'Mapel Rahasia Guru Lain QA']);

        $hariIni = now('Asia/Jakarta')->startOfDay();
        $this->travelTo($hariIni->copy()->setTime(6, 30));

        $this->actingAs($guruUser)
            ->get(route('guru.dashboard'))
            ->assertOk()
            ->assertSee('Jadwal mengajar berikutnya')
            ->assertSee($jadwal->mapel->nama_mapel)
            ->assertSee('dalam 30 menit')
            ->assertDontSee('Mapel Rahasia Guru Lain QA');

        $sesi = SesiPelajaran::create([
            'jadwal_id' => $jadwal->id,
            'guru_id' => $jadwal->guru_id,
            'tanggal' => $hariIni->toDateString(),
            'status_sesi' => 'Berlangsung',
        ]);
        PresensiPelajaran::create([
            'sesi_pelajaran_id' => $sesi->id,
            'siswa_id' => $siswa->id,
            'status' => 'Hadir',
        ]);
        PresensiPelajaran::create([
            'sesi_pelajaran_id' => $sesi->id,
            'siswa_id' => $siswaBelumAbsen->id,
            'status' => 'Belum Absen',
        ]);

        $this->travelTo($hariIni->copy()->setTime(7, 30));
        $this->actingAs($guruUser)
            ->get(route('guru.dashboard'))
            ->assertOk()
            ->assertSee('Sesi sedang berlangsung')
            ->assertSee('1 dari 2 siswa sudah absen')
            ->assertDontSee('Mapel Rahasia Guru Lain QA');

        PresensiPelajaran::where('sesi_pelajaran_id', $sesi->id)
            ->where('siswa_id', $siswaBelumAbsen->id)
            ->update(['status' => 'Alfa']);
        $sesi->update(['status_sesi' => 'Selesai']);

        $this->actingAs($guruUser)
            ->get(route('guru.dashboard'))
            ->assertOk()
            ->assertSee('Sesi selesai, presensi perlu diperiksa')
            ->assertSee('1 siswa belum absen atau Alfa')
            ->assertDontSee('Mapel Rahasia Guru Lain QA');

        $this->actingAs($guruLainUser)
            ->get(route('guru.dashboard'))
            ->assertOk()
            ->assertSee('Mapel Rahasia Guru Lain QA')
            ->assertDontSee($jadwal->mapel->nama_mapel);

        $this->travelBack();
    }

    public function test_guru_notifications_show_a_clean_empty_state(): void
    {
        [$guruUser] = $this->makeTeacher();

        $this->actingAs($guruUser)
            ->get(route('guru.dashboard'))
            ->assertOk()
            ->assertSee('Belum ada notifikasi mengajar hari ini.');
    }

    public function test_teacher_attendance_navbar_renders_long_teacher_names_and_role(): void
    {
        [$guruUser, $guru] = $this->makeTeacher();

        foreach (['Nurlailatul Jannah, L.C', 'Maryadi Saputra, S.Pd'] as $name) {
            $name .= ' QA '.bin2hex(random_bytes(3));
            $guru->update(['nama_lengkap' => $name]);

            $this->actingAs($guruUser)
                ->get(route('guru.presensi-guru'))
                ->assertOk()
                ->assertSee('class="profile-meta"', false)
                ->assertSee('class="name">'.$name.'</div>', false)
                ->assertSee('class="role">Guru</div>', false);
        }
    }

    public function test_student_pages_show_all_todays_schedules_and_the_schedule_active_by_time(): void
    {
        [, $guru, $jadwal, $kelas] = $this->makeSchedule();
        [$studentUser] = $this->makeStudent($kelas);
        $jadwal->update(['jam_mulai' => '07:00:00', 'jam_selesai' => '08:00:00']);

        $activeMapel = Mapel::create([
            'kode' => 'QA-'.bin2hex(random_bytes(4)),
            'nama_mapel' => 'Mapel Aktif QA',
        ]);
        JadwalPelajaran::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $activeMapel->id,
            'hari' => now()->locale('id')->isoFormat('dddd'),
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'ruangan' => 'Ruang Aktif QA',
        ]);

        $nextMapel = Mapel::create([
            'kode' => 'QA-'.bin2hex(random_bytes(4)),
            'nama_mapel' => 'Mapel Berikutnya QA',
        ]);
        JadwalPelajaran::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mapel_id' => $nextMapel->id,
            'hari' => now()->locale('id')->isoFormat('dddd'),
            'jam_mulai' => '14:00:00',
            'jam_selesai' => '15:00:00',
            'ruangan' => 'Ruang Berikutnya QA',
        ]);

        $kelasLain = Kelas::create(['nama_kelas' => 'Kelas Lain QA '.bin2hex(random_bytes(3))]);
        $mapelKelasLain = Mapel::create([
            'kode' => 'QA-'.bin2hex(random_bytes(4)),
            'nama_mapel' => 'Mapel Kelas Lain QA',
        ]);
        JadwalPelajaran::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelasLain->id,
            'mapel_id' => $mapelKelasLain->id,
            'hari' => now()->locale('id')->isoFormat('dddd'),
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'ruangan' => 'Ruang Kelas Lain QA',
        ]);

        $this->travelTo(now()->setTime(10, 30));
        $this->actingAs($studentUser)
            ->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee($jadwal->mapel->nama_mapel)
            ->assertSee($activeMapel->nama_mapel)
            ->assertSee($nextMapel->nama_mapel)
            ->assertDontSee($mapelKelasLain->nama_mapel)
            ->assertSee('10:00 - 11:00')
            ->assertSee('Berlangsung')
            ->assertSee('14:00 WIB');

        $this->actingAs($studentUser)
            ->get(route('siswa.scan-qr'))
            ->assertOk()
            ->assertSee($activeMapel->nama_mapel)
            ->assertSee('10:00 - 11:00 WIB')
            ->assertSee($guru->nama_lengkap);

        $this->travelTo(now()->setTime(12, 0));
        $this->actingAs($studentUser)
            ->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('Tidak ada sesi aktif')
            ->assertSee('Mapel Berikutnya QA (14:00 WIB)');

        $this->travelBack();
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

    public function test_guru_login_rejects_non_string_credentials(): void
    {
        $this->postJson(route('guru.login'), [
            'username' => ['malformed'],
            'password' => ['malformed'],
        ])->assertUnprocessable()->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_opening_guru_login_does_not_log_out_authenticated_guru(): void
    {
        [$guruUser] = $this->makeTeacher();

        $this->actingAs($guruUser)
            ->get(route('guru.login'))
            ->assertRedirect(route('guru.dashboard'));

        $this->assertAuthenticatedAs($guruUser);
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

        (new GuruSeeder)->run();

        $username = 'GURU'.str_pad((string) $guru->id, 3, '0', STR_PAD_LEFT);
        $guru->refresh();
        $user = $guru->user;

        $this->assertNotNull($user);
        $this->assertSame($username, $user->username);
        $this->assertSame('guru', $user->role);
        $this->assertTrue(Hash::check($username, $user->password));
        $this->assertSame($guru->id, $jadwal->fresh()->guru_id);

        (new GuruSeeder)->run();
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
