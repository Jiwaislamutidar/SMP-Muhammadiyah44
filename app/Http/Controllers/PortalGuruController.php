<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\PresensiPelajaran;
use App\Models\SesiPelajaran;
use App\Support\SiswaScheduleGrouper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PortalGuruController extends Controller
{
    public function dashboard(): View
    {
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');

        $now = now();
        $jadwals = $this->jadwalsHariIni($guru, $now);
        $jadwalBloks = (new SiswaScheduleGrouper)->blocks($jadwals, $now);
        $waktuSekarang = $now->format('H:i:s');
        $jadwalSaatIni = $jadwals
            ->filter(fn (JadwalPelajaran $jadwal): bool => substr($jadwal->jam_mulai, 0, 8) <= $waktuSekarang
                && $waktuSekarang < substr($jadwal->jam_selesai, 0, 8))
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

        $jadwals = JadwalPelajaran::with(['guru', 'mapel', 'kelas:id,nama_kelas,ruangan'])
            ->where(function ($query) use ($guru) {
                $query->where('guru_id', $guru->id)
                    ->orWhereHas('mapel.gurus', fn ($query) => $query->whereKey($guru->id));
            })
            ->orderByRaw("CASE LOWER(hari) WHEN 'senin' THEN 1 WHEN 'selasa' THEN 2 WHEN 'rabu' THEN 3 WHEN 'kamis' THEN 4 WHEN 'jumat' THEN 5 ELSE 6 END")
            ->orderBy('jam_mulai')
            ->get();

        return view('pages.guru.jadwalngajarguru', [
            'guru' => $guru,
            'jadwals' => $jadwals,
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

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = Auth::user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Kata sandi saat ini tidak sesuai.',
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('guru.profil')->with('success', 'Kata sandi berhasil diperbarui!');
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

    private function jadwalsHariIni(Guru $guru, Carbon $now)
    {
        $hari = mb_strtolower($now->copy()->locale('id')->isoFormat('dddd'));

        return JadwalPelajaran::with([
            'guru',
            'kelas:id,nama_kelas,ruangan',
            'mapel',
            'sesiPelajarans' => fn ($query) => $query->whereDate('tanggal', today()),
        ])
            ->where('guru_id', $guru->id)
            ->whereRaw('LOWER(TRIM(hari)) = ?', [$hari])
            ->orderBy('jam_mulai')
            ->get();
    }
}
