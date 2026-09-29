<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PresensiGuruController extends Controller
{
    public function index(): View
    {
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');

        return view('pages.guru.presensiguru', [
            'guru' => $guru,
            'presensi' => $guru->presensiHarian()->whereDate('tanggal', today())->first(),
            'jadwals' => JadwalPelajaran::with([
                'kelas',
                'mapel',
                'sesiPelajarans' => fn ($query) => $query->whereDate('tanggal', today()),
            ])->where('guru_id', $guru->id)
                ->whereRaw('LOWER(hari) = ?', [mb_strtolower(Carbon::now()->locale('id')->isoFormat('dddd'))])
                ->orderBy('jam_mulai')
                ->get(),
        ]);
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