<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\SesiPelajaran;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalGuruController extends Controller
{
    public function dashboard(): View
    {
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');

        $jadwals = $this->jadwalsHariIni($guru);

        return view('pages.guru.dashboardguru', [
            'guru' => $guru,
            'jadwals' => $jadwals,
            'presensiHariIni' => $guru->presensiHarian()->whereDate('tanggal', today())->first(),
            'sesiAktif' => $jadwals->first(fn (JadwalPelajaran $jadwal) => $jadwal->sesiPelajarans->first()?->status_sesi === 'Berlangsung'),
        ]);
    }

    public function jadwal(): View
    {
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');

        return view('pages.guru.jadwalngajarguru', [
            'guru' => $guru,
            'jadwals' => $this->jadwalsHariIni($guru),
        ]);
    }

    public function profil(): View
    {
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');
        $guru->load(['mapels', 'jadwals.kelas', 'jadwals.mapel']);

        return view('pages.guru.profil', [
            'guru' => $guru,
            'user' => Auth::user(),
            'kelasDiampu' => $guru->jadwals->pluck('kelas.nama_kelas')->filter()->unique()->values(),
            'mapelDiampu' => $guru->mapels->pluck('nama_mapel')
                ->merge($guru->jadwals->pluck('mapel.nama_mapel'))
                ->filter()
                ->unique()
                ->values(),
        ]);
    }

    public function riwayat(): View
    {
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');

        $presensiSekolah = $guru->presensiHarian()->latest('tanggal')->get();
        $sesiMengajar = SesiPelajaran::with(['jadwal.mapel', 'jadwal.kelas'])
            ->where('guru_id', $guru->id)
            ->latest('tanggal')
            ->get();
        $aktivitas = $presensiSekolah->map(fn ($presensi) => [
            'tanggal' => $presensi->tanggal,
            'waktu' => $presensi->jam_masuk,
            'jenis' => 'Kehadiran Sekolah',
            'mapel' => '-',
            'kelas' => '-',
            'status' => $presensi->status,
            'urutan' => $presensi->tanggal->copy()->setTimeFromTimeString($presensi->jam_masuk ?? '00:00:00'),
        ])->concat($sesiMengajar->map(fn ($sesi) => [
            'tanggal' => $sesi->tanggal,
            'waktu' => $sesi->jadwal->jam_mulai,
            'jenis' => 'Presensi Mengajar',
            'mapel' => $sesi->jadwal->mapel->nama_mapel,
            'kelas' => $sesi->jadwal->kelas->nama_kelas,
            'status' => $sesi->status_sesi,
            'urutan' => $sesi->tanggal->copy()->setTimeFromTimeString($sesi->jadwal->jam_mulai),
        ]))->sortByDesc('urutan')->take(50)->values();

        return view('pages.guru.riwayatpresensi', [
            'guru' => $guru,
            'aktivitas' => $aktivitas,
            'totalHariHadir' => $presensiSekolah->where('status', 'Hadir')->count(),
            'totalSesi' => $sesiMengajar->count(),
            'sesiSelesai' => $sesiMengajar->where('status_sesi', 'Selesai')->count(),
            'sesiBelum' => $sesiMengajar->where('status_sesi', '!=', 'Selesai')->count(),
        ]);
    }

    private function jadwalsHariIni(Guru $guru)
    {
        $hari = Carbon::now()->locale('id')->isoFormat('dddd');

        return JadwalPelajaran::with([
            'kelas',
            'mapel',
            'sesiPelajarans' => fn ($query) => $query->with('presensiPelajarans')->whereDate('tanggal', today()),
        ])
            ->where('guru_id', $guru->id)
            ->whereRaw('LOWER(hari) = ?', [mb_strtolower($hari)])
            ->orderBy('jam_mulai')
            ->get();
    }
}