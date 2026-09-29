<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\PresensiPelajaran;
use App\Models\SesiPelajaran;
use App\Models\Siswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PresensiPelajaranController extends Controller
{
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);
        $sesi = SesiPelajaran::with('jadwal')->where('qr_token', $validated['token'])->first();

        if (! $sesi || $sesi->status_sesi !== 'Berlangsung' || ! $sesi->qr_expires_at || now()->greaterThan($sesi->qr_expires_at)) {
            return response()->json(['message' => 'QR Code tidak valid atau telah kedaluwarsa.'], 422);
        }

        $siswa = Siswa::where('user_id', Auth::id())->first();
        abort_unless($siswa, 403, 'Profil murid belum terhubung ke akun ini.');

        if ((int) $siswa->kelas_id !== (int) $sesi->jadwal->kelas_id) {
            return response()->json(['message' => 'QR Code ini bukan untuk kelas Anda.'], 403);
        }

        $presensi = DB::transaction(function () use ($sesi, $siswa) {
            $presensi = $sesi->presensiPelajarans()->where('siswa_id', $siswa->id)->lockForUpdate()->first();
            if (! $presensi) {
                abort(403, 'Murid tidak terdaftar pada sesi ini.');
            }

            if ($presensi->status === 'Belum Absen') {
                $presensi->update(['status' => 'Hadir', 'waktu_scan' => now()]);
            }

            return $presensi->fresh();
        });

        return response()->json([
            'message' => $presensi->status === 'Hadir' ? 'Presensi berhasil dicatat.' : 'Presensi Anda sudah tercatat.',
            'status' => $presensi->status,
            'waktu_scan' => $presensi->waktu_scan?->toIso8601String(),
        ]);
    }

    public function updateManual(Request $request, PresensiPelajaran $presensi): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Hadir,Izin,Sakit,Alfa'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ]);
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');
        $presensi->load('sesiPelajaran');
        abort_unless($presensi->sesiPelajaran->guru_id === $guru->id, 403);
        abort_unless($presensi->sesiPelajaran->status_sesi === 'Berlangsung', 422, 'Presensi hanya dapat diubah saat sesi berlangsung.');

        $presensi->update([
            'status' => $validated['status'],
            'keterangan' => $validated['keterangan'] ?? null,
            'waktu_scan' => $validated['status'] === 'Hadir' ? ($presensi->waktu_scan ?? now()) : null,
        ]);

        return response()->json([
            'message' => 'Status presensi berhasil diperbarui.',
            'status' => $presensi->status,
            'waktu_scan' => $presensi->waktu_scan?->format('H:i:s'),
        ]);
    }
}