<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\SesiPelajaran;
use App\Models\Siswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SesiPelajaranController extends Controller
{
    public function index(): View
    {
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');
        $guru->loadMissing('mapels');
        $hari = Carbon::now()->locale('id')->isoFormat('dddd');
        $jadwals = JadwalPelajaran::with([
            'kelas',
            'mapel',
            'sesiPelajarans' => fn ($query) => $query->with('presensiPelajarans')->whereDate('tanggal', today()),
        ])
            ->where('guru_id', $guru->id)
            ->whereRaw('LOWER(hari) = ?', [mb_strtolower($hari)])
            ->orderBy('jam_mulai')
            ->get();
        $sesiId = request()->integer('sesi');
        $sesi = $sesiId
            ? SesiPelajaran::with(['jadwal.mapel', 'jadwal.kelas', 'presensiPelajarans.siswa'])
                ->where('guru_id', $guru->id)->whereDate('tanggal', today())->find($sesiId)
            : $jadwals->first(fn (JadwalPelajaran $jadwal) => $jadwal->sesiPelajarans->first()?->status_sesi === 'Berlangsung')
                ?->sesiPelajarans->first();

        $mapelGuru = $guru->mapels->pluck('nama_mapel')
            ->merge($jadwals->pluck('mapel.nama_mapel'))
            ->filter()
            ->unique()
            ->implode(' • ');

        return view('pages.guru.presensimurid', compact('guru', 'jadwals', 'sesi', 'mapelGuru'));
    }

    public function open(Request $request, JadwalPelajaran $jadwal): JsonResponse
    {
        $request->validate([
            'jadwal_pelajaran_id' => ['sometimes', 'integer', 'in:'.$jadwal->id],
        ]);
        $guru = $this->guru();
        $this->assertTodaysSchedule($jadwal, $guru);

        $sesi = DB::transaction(function () use ($jadwal, $guru) {
            $sesi = SesiPelajaran::query()
                ->where('jadwal_id', $jadwal->id)
                ->whereDate('tanggal', today())
                ->lockForUpdate()
                ->first();

            if ($sesi?->status_sesi === 'Selesai') {
                abort(422, 'Sesi jadwal ini sudah selesai.');
            }

            $sesi ??= new SesiPelajaran([
                'jadwal_id' => $jadwal->id,
                'guru_id' => $guru->id,
                'tanggal' => today(),
            ]);
            $sesi->status_sesi = 'Berlangsung';
            $this->refreshToken($sesi);
            $sesi->save();

            Siswa::where('kelas_id', $jadwal->kelas_id)->where('status', 'aktif')
                ->select('id')->chunkById(500, function ($siswas) use ($sesi) {
                    foreach ($siswas as $siswa) {
                        $sesi->presensiPelajarans()->firstOrCreate(
                            ['siswa_id' => $siswa->id],
                            ['status' => 'Belum Absen']
                        );
                    }
                });

            return $sesi;
        });

        return $this->sesiResponse($sesi->fresh(['jadwal.mapel', 'jadwal.kelas', 'presensiPelajarans.siswa']));
    }

    public function regenerate(SesiPelajaran $sesi): JsonResponse
    {
        $this->authorizeSesi($sesi);
        abort_unless($sesi->status_sesi === 'Berlangsung', 422, 'QR hanya dapat diperbarui saat sesi berlangsung.');

        $this->refreshToken($sesi);
        $sesi->save();

        return $this->sesiResponse($sesi->fresh(['jadwal.mapel', 'jadwal.kelas', 'presensiPelajarans.siswa']));
    }

    public function close(SesiPelajaran $sesi): JsonResponse
    {
        $this->authorizeSesi($sesi);
        abort_unless($sesi->status_sesi === 'Berlangsung', 422, 'Sesi tidak sedang berlangsung.');

        DB::transaction(function () use ($sesi) {
            $sesi->presensiPelajarans()->where('status', 'Belum Absen')->update([
                'status' => 'Alfa',
                'updated_at' => now(),
            ]);
            $sesi->status_sesi = 'Selesai';
            $sesi->qr_token = null;
            $sesi->qr_expires_at = null;
            $sesi->save();
        });

        return $this->status($sesi->fresh(['jadwal.mapel', 'jadwal.kelas', 'presensiPelajarans.siswa']));
    }

    public function destroy(SesiPelajaran $sesi): JsonResponse
    {
        $this->authorizeSesi($sesi);
        $sesiId = $sesi->id;
        $jadwalId = $sesi->jadwal_id;

        DB::transaction(fn () => $sesi->delete());

        return response()->json([
            'id' => $sesiId,
            'jadwal_id' => $jadwalId,
            'status_sesi' => 'Dibatalkan',
            'message' => 'Sesi presensi dan seluruh data kehadiran murid berhasil dihapus.',
        ]);
    }

    public function status(SesiPelajaran $sesi): JsonResponse
    {
        $this->authorizeSesi($sesi);
        $sesi->loadMissing(['jadwal.mapel', 'jadwal.kelas', 'presensiPelajarans.siswa']);

        return response()->json([
            'id' => $sesi->id,
            'jadwal_id' => $sesi->jadwal_id,
            'status_sesi' => $sesi->status_sesi,
            'qr_expires_at' => $sesi->qr_expires_at?->toIso8601String(),
            'expired' => ! $sesi->qr_expires_at || $sesi->qr_expires_at->isPast(),
            'presensi' => $sesi->presensiPelajarans->map(fn ($presensi) => [
                'id' => $presensi->id,
                'siswa_id' => $presensi->siswa_id,
                'nama' => $presensi->siswa->nama_lengkap,
                'nisn' => $presensi->siswa->nisn,
                'status' => $presensi->status,
                'waktu_scan' => $presensi->waktu_scan?->format('H:i:s'),
            ])->values(),
            'counts' => $sesi->presensiPelajarans->countBy('status'),
        ]);
    }

    private function sesiResponse(SesiPelajaran $sesi): JsonResponse
    {
        return response()->json([
            'id' => $sesi->id,
            'jadwal_id' => $sesi->jadwal_id,
            'status_sesi' => $sesi->status_sesi,
            'qr_expires_at' => $sesi->qr_expires_at->toIso8601String(),
            'qr_svg' => (string) QrCode::format('svg')->size(360)->margin(2)->generate($sesi->qr_token),
            'jadwal' => [
                'id' => $sesi->jadwal->id,
                'mapel' => $sesi->jadwal->mapel->nama_mapel,
                'kelas' => $sesi->jadwal->kelas->nama_kelas,
                'jam_mulai' => $sesi->jadwal->jam_mulai,
                'jam_selesai' => $sesi->jadwal->jam_selesai,
                'ruangan' => $sesi->jadwal->ruangan,
            ],
            'presensi' => $sesi->presensiPelajarans->map(fn ($presensi) => [
                'id' => $presensi->id,
                'siswa_id' => $presensi->siswa_id,
                'nama' => $presensi->siswa->nama_lengkap,
                'nisn' => $presensi->siswa->nisn,
                'status' => $presensi->status,
            ])->values(),
        ]);
    }

    private function refreshToken(SesiPelajaran $sesi): void
    {
        $sesi->qr_token = Str::random(64);
        $sesi->qr_expires_at = now()->addMinutes(5);
    }

    private function assertTodaysSchedule(JadwalPelajaran $jadwal, Guru $guru): void
    {
        $hari = Carbon::now()->locale('id')->isoFormat('dddd');
        abort_unless($jadwal->guru_id === $guru->id, 403);
        abort_unless(mb_strtolower($jadwal->hari) === mb_strtolower($hari), 422, 'Sesi hanya dapat dibuka pada hari jadwal berlangsung.');
    }

    private function authorizeSesi(SesiPelajaran $sesi): void
    {
        abort_unless($sesi->guru_id === $this->guru()->id, 403);
    }

    private function guru(): Guru
    {
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');

        return $guru;
    }
}