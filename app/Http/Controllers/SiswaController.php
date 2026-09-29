<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\JadwalPelajaran;
use App\Models\PresensiPelajaran;
use App\Models\SesiPelajaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SiswaController extends Controller
{
    public function dashboard(): View
    {
        return view('pages.siswa.dashboardsiswa', $this->pageData());
    }

    public function scanQr(): View
    {
        return view('pages.siswa.scanqrsiswa', $this->pageData());
    }

    public function riwayat(): View
    {
        $data = $this->pageData();
        $siswaId = Siswa::where('user_id', Auth::id())->value('id');
        $data['riwayatPresensi'] = PresensiPelajaran::with([
            'sesiPelajaran.jadwal.mapel',
            'sesiPelajaran.jadwal.kelas',
        ])
            ->where('siswa_id', $siswaId)
            ->latest('updated_at')
            ->paginate(10);

        return view('pages.siswa.riwayatsiswa', $data);
    }

    public function profil(): View
    {
        return view('pages.siswa.profilsiswa', $this->pageData());
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = Auth::user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Kata sandi saat ini tidak sesuai.',
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('siswa.profil')->with('success', 'Kata sandi berhasil diperbarui!');
    }

    private function pageData(): array
    {
        $user = Auth::user();
        $siswaModel = Siswa::where('user_id', $user->id)->with('kelas')->first();
        $nama = $siswaModel->nama_lengkap ?? $user->name;
        $namaParts = preg_split('/\s+/', trim($nama), -1, PREG_SPLIT_NO_EMPTY);
        $inisial = collect($namaParts)
            ->take(2)
            ->map(fn (string $part) => mb_substr($part, 0, 1))
            ->implode('');
        $month = now()->month;
        $academicStartYear = $month >= 7 ? now()->year : now()->year - 1;
        $semesterName = $month >= 7 || $month <= 1 ? 'Ganjil' : 'Genap';
        $kelas = $siswaModel?->kelas?->nama_kelas ?? 'Belum Ada Kelas';
        $jadwalRecords = collect();

        if ($siswaModel?->kelas_id) {
            $hari = now()->locale('id')->isoFormat('dddd');
            $jadwalRecords = JadwalPelajaran::with([
                'guru',
                'kelas',
                'mapel',
                'sesiPelajarans' => fn ($query) => $query->whereDate('tanggal', today())
                    ->with(['presensiPelajarans' => fn ($attendance) => $attendance->where('siswa_id', $siswaModel->id)]),
            ])
                ->where('kelas_id', $siswaModel->kelas_id)
                ->whereRaw('LOWER(hari) = ?', [mb_strtolower($hari)])
                ->orderBy('jam_mulai')
                ->get();
        }

        $jadwalHariIni = $jadwalRecords->map(function (JadwalPelajaran $jadwal) {
            $sesi = $jadwal->sesiPelajarans->first();
            $status = match ($sesi?->status_sesi) {
                'Selesai' => 'Selesai',
                'Berlangsung' => 'Berlangsung',
                default => $jadwal->jam_mulai > now()->format('H:i:s') ? 'Akan Datang' : 'Belum',
            };

            return [
                'jam' => substr($jadwal->jam_mulai, 0, 5).' - '.substr($jadwal->jam_selesai, 0, 5),
                'mapel' => $jadwal->mapel->nama_mapel,
                'guru' => $jadwal->guru->nama_lengkap,
                'kelas' => $jadwal->kelas->nama_kelas,
                'status' => $status,
            ];
        })->all();

        $presensiTerbaru = $siswaModel
            ? PresensiPelajaran::with('sesiPelajaran.jadwal.mapel', 'sesiPelajaran.jadwal.guru')
                ->where('siswa_id', $siswaModel->id)
                ->whereHas('sesiPelajaran', fn ($query) => $query->whereDate('tanggal', today()))
                ->latest('updated_at')
                ->first()
            : null;

        $counts = $siswaModel
            ? $siswaModel->presensiPelajarans()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
            : collect();

        $absensiTerbaru = $siswaModel
            ? PresensiPelajaran::with('sesiPelajaran.jadwal.mapel')
                ->where('siswa_id', $siswaModel->id)
                ->latest('updated_at')
                ->take(5)
                ->get()
                ->map(fn (PresensiPelajaran $attendance) => [
                    'mapel' => $attendance->sesiPelajaran->jadwal->mapel->nama_mapel,
                    'tanggal' => $attendance->sesiPelajaran->tanggal->format('d/m/Y'),
                    'waktu' => $attendance->waktu_scan ? $attendance->waktu_scan->format('H:i').' WIB' : '-',
                    'status' => $attendance->status,
                ])->all()
            : [];

        $sesiAktif = $siswaModel
            ? SesiPelajaran::with(['jadwal.mapel', 'jadwal.kelas', 'jadwal.guru'])
                ->whereDate('tanggal', today())
                ->where('status_sesi', 'Berlangsung')
                ->whereHas('jadwal', fn ($query) => $query->where('kelas_id', $siswaModel->kelas_id))
                ->latest('updated_at')
                ->first()
            : null;

        $sesiBerikutnya = $jadwalRecords->first(fn (JadwalPelajaran $jadwal) => $jadwal->jam_mulai > now()->format('H:i:s'));

        return [
            'siswa' => (object) [
                'nama' => $nama,
                'nisn' => $siswaModel->nisn ?? $user->username,
                'username' => $user->username,
                'kelas' => $kelas,
                'status' => ucfirst($siswaModel->status ?? 'aktif'),
                'inisial' => mb_strtoupper($inisial),
                'sekolah' => 'SMP Muhammadiyah 44 Tangerang Selatan',
            ],
            'tanggalHariIni' => now()->locale('id')->translatedFormat('l, j F Y'),
            'semester' => 'Semester ' . $semesterName,
            'semesterInfo' => 'Semester ' . $semesterName . ' ' . $academicStartYear,
            'tahunAjaran' => $academicStartYear . '/' . ($academicStartYear + 1) . ' ' . $semesterName,
            'ringkasan' => [
                'hadir' => (int) $counts->get('Hadir', 0),
                'izin' => (int) $counts->get('Izin', 0),
                'alfa' => (int) $counts->get('Alfa', 0),
            ],
            'presensi' => [
                'status' => $presensiTerbaru?->status ?? 'Belum Absen',
                'verifikator' => $presensiTerbaru?->sesiPelajaran?->jadwal?->guru?->nama_lengkap ?? '-',
                'mapel' => $presensiTerbaru?->sesiPelajaran?->jadwal?->mapel?->nama_mapel ?? '-',
                'sesi' => $presensiTerbaru?->sesiPelajaran?->status_sesi ?? '-',
                'ruang' => $presensiTerbaru?->sesiPelajaran?->jadwal?->ruangan ?? '-',
                'jam' => $presensiTerbaru
                    ? substr($presensiTerbaru->sesiPelajaran->jadwal->jam_mulai, 0, 5).' - '.substr($presensiTerbaru->sesiPelajaran->jadwal->jam_selesai, 0, 5)
                    : '-',
                'waktu_tercatat' => $presensiTerbaru?->waktu_scan
                    ? $presensiTerbaru->waktu_scan->format('H:i').' WIB'
                    : '-',
            ],
            'jadwalHariIni' => $jadwalHariIni,
            'absensiTerbaru' => $absensiTerbaru,
            'sesiBerikutnya' => $sesiBerikutnya
                ? $sesiBerikutnya->mapel->nama_mapel.' ('.substr($sesiBerikutnya->jam_mulai, 0, 5).' WIB)'
                : '-',
            'sesi' => [
                'status' => $sesiAktif?->status_sesi ?? 'Tidak ada sesi aktif',
                'mapel' => $sesiAktif?->jadwal?->mapel?->nama_mapel ?? '-',
                'kelas_ruang' => $sesiAktif
                    ? $sesiAktif->jadwal->kelas->nama_kelas.' ('.($sesiAktif->jadwal->ruangan ?? '-').')'
                    : '-',
                'jam' => $sesiAktif
                    ? substr($sesiAktif->jadwal->jam_mulai, 0, 5).' - '.substr($sesiAktif->jadwal->jam_selesai, 0, 5).' WIB'
                    : '-',
                'guru' => $sesiAktif?->jadwal?->guru?->nama_lengkap ?? '-',
            ],
        ];
    }
}