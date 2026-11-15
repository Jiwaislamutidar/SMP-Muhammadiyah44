<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\PresensiPelajaran;
use App\Models\Siswa;
use App\Services\AttendanceStatusService;
use App\Support\SiswaScheduleGrouper;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(AttendanceStatusService $attendanceStatus): View
    {
        $attendanceStatus->completeExpiredStudentSessions();
        $attendanceStatus->applyGuruAttendanceCutoff();

        $todayAttendance = PresensiPelajaran::query()
            ->whereHas('sesiPelajaran', fn ($query) => $query->whereDate('tanggal', today()));

        $hadirCount = (clone $todayAttendance)
            ->where('status', 'Hadir')
            ->distinct()
            ->count('siswa_id');
        $alfaCount = (clone $todayAttendance)
            ->where('status', 'Alfa')
            ->distinct()
            ->count('siswa_id');
        $izinSakitCount = (clone $todayAttendance)
            ->whereIn('status', ['Izin', 'Sakit'])
            ->distinct()
            ->count('siswa_id');
        $totalSiswaAktif = Siswa::query()->where('status', 'aktif')->count();

        $gurus = Guru::query()
            ->with([
                'mapels',
                'dashboardPresensiLogs' => fn ($query) => $query->whereDate('tanggal', today()),
                'dashboardAttendanceSessionLogs' => fn ($query) => $query->whereDate('tanggal', today()),
            ])
            ->orderBy('nama_lengkap')
            ->get();
        $guruHadir = $gurus
            ->filter(fn (Guru $guru): bool => $guru->dashboardPresensiLogs->contains('status', 'Hadir')
                || $guru->dashboardAttendanceSessionLogs->isNotEmpty())
            ->values();
        $guruTidakHadir = $gurus
            ->filter(fn (Guru $guru): bool => in_array(
                $guru->dashboardPresensiLogs->first()?->status,
                ['Izin', 'Sakit', 'Alfa'],
                true
            ))
            ->values();
        $guruBelumPresensi = $gurus
            ->filter(fn (Guru $guru): bool => $guru->dashboardPresensiLogs->isEmpty())
            ->values();

        $hariIni = mb_strtolower(Carbon::now()->locale('id')->isoFormat('dddd'));
        $jadwalsHariIni = JadwalPelajaran::query()
            ->with(['kelas', 'mapel', 'guru'])
            ->whereRaw('LOWER(hari) = ?', [$hariIni])
            ->orderBy('jam_mulai')
            ->get();
        $jadwalBloks = (new SiswaScheduleGrouper)->blocks($jadwalsHariIni, now());
        $kelasList = Kelas::query()->orderBy('nama_kelas')->get();

        return view('pages.admin.dashboardadmin', compact(
            'alfaCount',
            'guruBelumPresensi',
            'guruHadir',
            'guruTidakHadir',
            'hadirCount',
            'izinSakitCount',
            'jadwalBloks',
            'kelasList',
            'totalSiswaAktif'
        ));
    }
}
