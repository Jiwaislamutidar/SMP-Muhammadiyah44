<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
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
        return view('pages.siswa.riwayatsiswa', $this->pageData());
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
            'ringkasan' => ['hadir' => 18, 'izin' => 2, 'alfa' => 1],
            'presensi' => [
                'status' => 'Hadir',
                'verifikator' => 'Ustadz Pengampu',
                'mapel' => 'Matematika',
                'sesi' => 'Sesi 1 Selesai',
                'ruang' => 'Ruang Kelas 03',
                'jam' => '07.00 - 08.20',
                'waktu_tercatat' => '07.02 WIB',
            ],
            'jadwalHariIni' => [
                ['jam' => '07.00 - 08.20', 'mapel' => 'Matematika', 'guru' => 'Ustadz Ahmad Fauzi', 'kelas' => $kelas, 'status' => 'Selesai'],
                ['jam' => '08.20 - 09.40', 'mapel' => 'IPA Terpadu', 'guru' => 'Ustadzah Nur Aini', 'kelas' => $kelas, 'status' => 'Berlangsung'],
                ['jam' => '10.00 - 11.20', 'mapel' => 'Bahasa Indonesia', 'guru' => 'Ustadzah Siti Rahma', 'kelas' => $kelas, 'status' => 'Akan Datang'],
                ['jam' => '11.20 - 12.00', 'mapel' => 'Bahasa Arab', 'guru' => 'Ustadz Hasan', 'kelas' => $kelas, 'status' => 'Akan Datang'],
            ],
            'absensiTerbaru' => [
                ['mapel' => 'Matematika', 'tanggal' => now()->format('d/m/Y'), 'waktu' => '07.02 WIB', 'status' => 'Hadir'],
                ['mapel' => 'Bahasa Indonesia', 'tanggal' => now()->subDay()->format('d/m/Y'), 'waktu' => '08.18 WIB', 'status' => 'Hadir'],
                ['mapel' => 'IPA Terpadu', 'tanggal' => now()->subDays(2)->format('d/m/Y'), 'waktu' => '07.05 WIB', 'status' => 'Izin'],
            ],
            'sesiBerikutnya' => 'IPA Terpadu (08.20 WIB)',
            'sesi' => [
                'status' => 'Berlangsung',
                'mapel' => 'Matematika',
                'kelas_ruang' => $kelas . ' (Ruang Kelas 03)',
                'jam' => '08.20 - 09.40 WIB',
                'guru' => 'Ustadz Ahmad Fauzi, M.Pd.',
            ],
        ];
    }
}