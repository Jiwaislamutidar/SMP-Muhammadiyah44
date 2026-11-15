<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\PresensiPelajaran;
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
        $now = now();
        $sesi = DB::table('sesi_pelajarans')
            ->join('jadwal_pelajarans', 'jadwal_pelajarans.id', '=', 'sesi_pelajarans.jadwal_id')
            ->where('sesi_pelajarans.qr_token', $validated['token'])
            ->where('sesi_pelajarans.status_sesi', 'Berlangsung')
            ->where('sesi_pelajarans.qr_expires_at', '>', $now)
            ->select('sesi_pelajarans.id', 'jadwal_pelajarans.kelas_id')
            ->first();

        if (! $sesi) {
            return response()->json(['message' => 'QR Code tidak valid atau telah kedaluwarsa.'], 422);
        }

        $siswaId = Siswa::where('user_id', Auth::id())
            ->where('kelas_id', $sesi->kelas_id)
            ->value('id');

        if (! $siswaId) {
            abort_unless(Siswa::where('user_id', Auth::id())->exists(), 403, 'Profil murid belum terhubung ke akun ini.');

            return response()->json(['message' => 'QR Code ini bukan untuk kelas Anda.'], 403);
        }

        $presensiQuery = DB::table('presensi_pelajarans')
            ->where('sesi_pelajaran_id', $sesi->id)
            ->where('siswa_id', $siswaId);

        if ($presensiQuery->where('status', '<>', 'Belum Absen')->exists()) {
            $presensi = DB::table('presensi_pelajarans')
                ->where('sesi_pelajaran_id', $sesi->id)
                ->where('siswa_id', $siswaId)
                ->first(['status', 'waktu_scan']);

            return response()->json([
                'message' => 'Presensi Anda sudah tercatat.',
                'status' => $presensi->status,
                'waktu_scan' => $presensi->waktu_scan
                    ? \Illuminate\Support\Carbon::parse($presensi->waktu_scan)->toIso8601String()
                    : null,
            ]);
        }

        $updated = DB::table('presensi_pelajarans')
            ->where('sesi_pelajaran_id', $sesi->id)
            ->where('siswa_id', $siswaId)
            ->where('status', 'Belum Absen')
            ->update([
                'status' => 'Hadir',
                'waktu_scan' => $now,
                'updated_at' => $now,
            ]);

        if (! $updated) {
            $presensi = DB::table('presensi_pelajarans')
                ->where('sesi_pelajaran_id', $sesi->id)
                ->where('siswa_id', $siswaId)
                ->first(['status', 'waktu_scan']);

            abort_unless($presensi, 403, 'Murid tidak terdaftar pada sesi ini.');

            return response()->json([
                'message' => 'Presensi Anda sudah tercatat.',
                'status' => $presensi->status,
                'waktu_scan' => $presensi->waktu_scan
                    ? \Illuminate\Support\Carbon::parse($presensi->waktu_scan)->toIso8601String()
                    : null,
            ]);
        }

        return response()->json([
            'message' => 'Presensi berhasil dicatat.',
            'status' => 'Hadir',
            'waktu_scan' => $now->toIso8601String(),
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