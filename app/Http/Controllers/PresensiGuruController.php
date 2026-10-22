<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PresensiGuruController extends Controller
{
    public function index(Request $request): View
    {
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
            ->flatMap(fn ($record) => [$record->foto_masuk, $record->foto_pulang])
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

    public function masuk(Request $request): RedirectResponse
    {
        return $this->catat($request, 'masuk');
    }

    public function pulang(Request $request): RedirectResponse
    {
        return $this->catat($request, 'pulang');
    }

    private function catat(Request $request, string $jenis): RedirectResponse
    {
        $validated = $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');

        $presensi = $guru->presensiHarian()->firstOrNew(['tanggal' => today()]);

        if ($jenis === 'masuk' && $presensi->jam_masuk) {
            return back()->withErrors(['foto' => 'Absen masuk hari ini sudah tercatat.']);
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
