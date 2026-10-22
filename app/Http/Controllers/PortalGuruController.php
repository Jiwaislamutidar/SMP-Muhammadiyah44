<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\PresensiPelajaran;
use App\Models\SesiPelajaran;
use App\Support\SiswaScheduleGrouper;
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
        $jadwalBloks = (new SiswaScheduleGrouper)->blocks($jadwals, now());
        $waktuSekarang = now()->format('H:i:s');
        $hariIni = now()->locale('id')->translatedFormat('l');
        $jadwalSaatIni = JadwalPelajaran::with(['kelas', 'mapel'])
            ->where('guru_id', $guru->id)
            ->whereRaw('LOWER(hari) = ?', [mb_strtolower($hariIni)])
            ->whereTime('jam_mulai', '<=', $waktuSekarang)
            ->whereTime('jam_selesai', '>=', $waktuSekarang)
            ->get()
            ->sortBy(fn (JadwalPelajaran $jadwal) => Carbon::parse($jadwal->jam_mulai)
                ->diffInSeconds(Carbon::parse($jadwal->jam_selesai)))
            ->first();
        $sesiAktif = $jadwalSaatIni
            ? $jadwals->first(fn (JadwalPelajaran $jadwal) => $jadwal->id === $jadwalSaatIni->id
                && $jadwal->sesiPelajarans->first()?->status_sesi === 'Berlangsung')
            : null;
        $sesiBerlangsung = $sesiAktif?->sesiPelajarans->first();

        return view('pages.guru.dashboardguru', [
            'guru' => $guru,
            'jadwals' => $jadwals,
            'jadwalBloks' => $jadwalBloks,
            'presensiHariIni' => $guru->presensiHarian()->whereDate('tanggal', today())->first(),
            'sesiAktif' => $sesiAktif,
            'sesiBerlangsung' => $sesiBerlangsung,
            'jadwalSaatIni' => $jadwalSaatIni,
        ]);
    }

    public function jadwal(): View
    {
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');

        $jadwals = $this->jadwalsHariIni($guru);

        return view('pages.guru.jadwalngajarguru', [
            'guru' => $guru,
            'jadwals' => $jadwals,
            'jadwalBloks' => (new SiswaScheduleGrouper)->blocks($jadwals, now()),
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
        $jumlahSesiAktif = SesiPelajaran::where('guru_id', $guru->id)
            ->whereDate('tanggal', today())
            ->where('status_sesi', 'Berlangsung')
            ->count();
        $jumlahSiswaBelumAbsen = PresensiPelajaran::where('status', 'Belum Absen')
            ->whereHas('sesiPelajaran', fn ($query) => $query
                ->where('guru_id', $guru->id)
                ->whereDate('tanggal', today())
                ->where('status_sesi', 'Berlangsung'))
            ->count();
        $aktivitas = $presensiSekolah->map(fn ($presensi) => [
            'sesi_id' => null,
            'tanggal' => $presensi->tanggal,
            'waktu' => $presensi->jam_masuk,
            'jenis' => 'Kehadiran Sekolah',
            'mapel' => '-',
            'kelas' => '-',
            'status' => $presensi->status,
            'urutan' => $presensi->tanggal->copy()->setTimeFromTimeString($presensi->jam_masuk ?? '00:00:00'),
        ])->concat($sesiMengajar->map(fn ($sesi) => [
            'sesi_id' => $sesi->id,
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
            'jumlahSesiAktif' => $jumlahSesiAktif,
            'jumlahSiswaBelumAbsen' => $jumlahSiswaBelumAbsen,
        ]);
    }

    private function jadwalsHariIni(Guru $guru)
    {
        $hari = Carbon::now()->locale('id')->isoFormat('dddd');

        return JadwalPelajaran::with([
            'kelas',
            'mapel',
            'sesiPelajarans' => fn ($query) => $query->whereDate('tanggal', today()),
        ])
            ->where('guru_id', $guru->id)
            ->whereRaw('LOWER(hari) = ?', [mb_strtolower($hari)])
            ->orderBy('jam_mulai')
            ->get();
    }
}
