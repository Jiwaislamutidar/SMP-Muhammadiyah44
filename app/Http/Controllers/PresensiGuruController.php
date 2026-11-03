<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\PresensiGuru;
use App\Services\AttendanceStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PresensiGuruController extends Controller
{
    public function index(Request $request, AttendanceStatusService $attendanceStatus): View
    {
        $attendanceStatus->applyGuruAttendanceCutoff();
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');

        $validated = $request->validate([
            'periode' => ['nullable', 'in:hari,minggu,bulan'],
        ]);
        $periode = $validated['periode'] ?? 'bulan';
        $tanggalMulai = match ($periode) {
            'hari' => today(),
            'minggu' => today()->subDays(6),
            'bulan' => today()->subMonth(),
        };

        return view('pages.guru.presensiguru', [
            'guru' => $guru,
            'presensi' => $guru->presensiHarian()->whereDate('tanggal', today())->first(),
            'presensiRiwayat' => $guru->presensiHarian()
                ->whereDate('tanggal', '>=', $tanggalMulai)
                ->whereDate('tanggal', '<=', today())
                ->orderByDesc('tanggal')
                ->get(),
            'periode' => $periode,
        ]);
    }

    public function resetHistory(): RedirectResponse
    {
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');

        $presensi = $guru->presensiHarian()->get();
        $foto = $presensi
            ->flatMap(fn ($record) => [$record->foto_masuk, $record->foto_pulang, $record->bukti_izin])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $disk = Storage::disk('public');
        foreach ($foto as $path) {
            if ($disk->exists($path) && ! $disk->delete($path)) {
                throw new \RuntimeException('Foto presensi tidak berhasil dihapus; riwayat tidak direset.');
            }
        }

        $guru->presensiHarian()->delete();

        return redirect()->route('guru.presensi-guru')
            ->with('success', 'Seluruh riwayat presensi berhasil direset.');
    }

    public function masuk(Request $request, AttendanceStatusService $attendanceStatus): RedirectResponse
    {
        return $this->catat($request, 'masuk', $attendanceStatus);
    }

    public function pulang(Request $request, AttendanceStatusService $attendanceStatus): RedirectResponse
    {
        return $this->catat($request, 'pulang', $attendanceStatus);
    }

    public function izin(Request $request, AttendanceStatusService $attendanceStatus): RedirectResponse
    {
        $attendanceStatus->applyGuruAttendanceCutoff();
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');

        if (now()->gte(today()->setTime(15, 0))) {
            return back()->withErrors(['bukti_izin' => 'Batas pencatatan presensi hari ini sudah berakhir.']);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:Izin,Sakit'],
            'bukti_izin' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($guru->presensiHarian()->whereDate('tanggal', today())->exists()) {
            return back()->withErrors(['status' => 'Presensi hari ini sudah tercatat.']);
        }

        $now = now();
        $path = $validated['bukti_izin']->store(
            'presensi_guru/'.$now->format('Y').'/'.$now->format('m'),
            'public'
        );

        PresensiGuru::create([
            'guru_id' => $guru->id,
            'tanggal' => today(),
            'status' => $validated['status'],
            'bukti_izin' => $path,
        ]);

        return back()->with('success', 'Presensi '.$validated['status'].' berhasil dicatat beserta bukti.');
    }

    private function catat(Request $request, string $jenis, AttendanceStatusService $attendanceStatus): RedirectResponse
    {
        $attendanceStatus->applyGuruAttendanceCutoff();
        if ($jenis === 'masuk' && now()->lt(today()->setTime(7, 0))) {
            return back()->withErrors(['foto' => 'Absen masuk dibuka mulai pukul 07.00.']);
        }
        if ($jenis === 'masuk' && now()->gte(today()->setTime(15, 0))) {
            return back()->withErrors(['foto' => 'Batas absen masuk hari ini sudah berakhir pukul 15.00.']);
        }

        $validated = $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');

        $presensi = $guru->presensiHarian()->firstOrNew(['tanggal' => today()]);

        if ($jenis === 'masuk' && $presensi->jam_masuk) {
            return back()->withErrors(['foto' => 'Absen masuk hari ini sudah tercatat.']);
        }

        if ($jenis === 'masuk' && $presensi->exists && $presensi->status !== 'Hadir') {
            return back()->withErrors(['foto' => 'Status presensi hari ini sudah tercatat sebagai '.$presensi->status.'.']);
        }

        if ($jenis === 'pulang' && ! $presensi->jam_masuk) {
            return back()->withErrors(['foto' => 'Lakukan absen masuk sebelum absen pulang.']);
        }

        if ($jenis === 'pulang' && $presensi->jam_pulang) {
            return back()->withErrors(['foto' => 'Absen pulang hari ini sudah tercatat.']);
        }

        $now = now();
        $path = $validated['foto']->store(
            'presensi_guru/'.$now->format('Y').'/'.$now->format('m'),
            'public'
        );
        $presensi->status = 'Hadir';
        $presensi->{'jam_'.$jenis} = $now->format('H:i:s');
        $presensi->{'foto_'.$jenis} = $path;
        $presensi->save();

        return back()->with('success', 'Presensi '.($jenis === 'masuk' ? 'masuk' : 'pulang').' berhasil dicatat.');
    }
}
