<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\PresensiGuru;
use App\Models\Setting;
use App\Services\AttendanceStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $setting = Setting::first();

        // Mapping hari ini ke bahasa Indonesia & Inggris untuk pencocokan jadwal
        $hariIndonesia = match(now()->format('l')) {
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
            'Sunday'    => 'Minggu',
            default     => 'Senin'
        };
        $hariInggris = now()->format('l');

        // Ambil jadwal pelajaran guru hari ini secara langsung & independen
        $jadwalList = DB::table('jadwal_pelajarans')
            ->join('mapels', 'mapels.id', '=', 'jadwal_pelajarans.mapel_id')
            ->join('kelas', 'kelas.id', '=', 'jadwal_pelajarans.kelas_id')
            ->where('jadwal_pelajarans.guru_id', $guru->id)
            ->where(function($q) use ($hariIndonesia, $hariInggris) {
                $q->where('jadwal_pelajarans.hari', $hariIndonesia)
                  ->orWhere('jadwal_pelajarans.hari', $hariInggris);
            })
            ->select(
                'jadwal_pelajarans.id as id',
                'jadwal_pelajarans.jam_mulai',
                'jadwal_pelajarans.jam_selesai',
                'jadwal_pelajarans.ruangan',
                'mapels.nama_mapel',
                'kelas.nama_kelas'
            )
            ->orderBy('jadwal_pelajarans.jam_mulai')
            ->get();

        return view('pages.guru.presensiguru', [
            'guru' => $guru,
            'presensi' => $guru->presensiHarian()->whereDate('tanggal', today())->first(),
            'presensiRiwayat' => $guru->presensiHarian()
                ->whereDate('tanggal', '>=', $tanggalMulai)
                ->whereDate('tanggal', '<=', today())
                ->orderByDesc('tanggal')
                ->get(),
            'periode' => $periode,
            'setting' => $setting,
            'jadwalList' => $jadwalList,
        ]);
    }

    public function resetHistory(): RedirectResponse
    {
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');

        $guru->presensiHarian()->delete();

        return redirect()->route('guru.presensi-guru')
            ->with('success', 'Riwayat presensi pribadi berhasil direset. Log dashboard admin tetap tersimpan.');
    }

    public function masuk(Request $request, AttendanceStatusService $attendanceStatus): RedirectResponse
    {
        return $this->catat($request, 'masuk', $attendanceStatus);
    }

    public function pulang(Request $request, AttendanceStatusService $attendanceStatus): RedirectResponse
    {
        return $this->catat($request, 'pulang', $attendanceStatus);
    }

    public function mulaiMengajar(Request $request, int $sesiId): RedirectResponse
    {
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');

        $validated = $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $jadwal = DB::table('jadwal_pelajarans')
            ->where('id', $sesiId)
            ->where('guru_id', $guru->id)
            ->first();
            
        abort_unless($jadwal, 404, 'Jadwal pelajaran tidak ditemukan.');

        $path = $validated['foto']->store('presensi_mapel/' . now()->format('Y') . '/' . now()->format('m'), 'public');

        DB::table('presensi_guru_mapels')->updateOrInsert(
            ['jadwal_id' => $sesiId, 'tanggal' => today()],
            [
                'guru_id' => $guru->id,
                'jam_mulai_mengajar' => now()->format('H:i:s'),
                'foto_mulai' => $path,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('success', 'Presensi mulai mengajar berhasil dicatat.');
    }

    public function selesaiMengajar(Request $request, int $sesiId): RedirectResponse
    {
        $guru = Guru::forUser(Auth::user());
        abort_unless($guru, 403, 'Profil guru belum terhubung ke akun ini.');

        $validated = $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $jadwal = DB::table('jadwal_pelajarans')
            ->where('id', $sesiId)
            ->where('guru_id', $guru->id)
            ->first();
            
        abort_unless($jadwal, 404, 'Jadwal pelajaran tidak ditemukan.');

        $path = $validated['foto']->store('presensi_mapel/' . now()->format('Y') . '/' . now()->format('m'), 'public');

        DB::table('presensi_guru_mapels')->updateOrInsert(
            ['jadwal_id' => $sesiId, 'tanggal' => today()],
            [
                'guru_id' => $guru->id,
                'jam_selesai_mengajar' => now()->format('H:i:s'),
                'foto_selesai' => $path,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('success', 'Presensi selesai mengajar berhasil dicatat.');
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
        $setting = Setting::first();
        $now = now();

        $jamMasuk = $setting?->jam_masuk ?? '07:00:00';
        $jamToleransi = $setting?->jam_toleransi ?? '07:15:00';

        $dayMap = [
            'Monday'    => 'jam_pulang_senin',
            'Tuesday'   => 'jam_pulang_selasa',
            'Wednesday' => 'jam_pulang_rabu',
            'Thursday'  => 'jam_pulang_kamis',
            'Friday'    => 'jam_pulang_jumat',
            'Saturday'  => 'jam_pulang_senin',
            'Sunday'    => 'jam_pulang_senin',
        ];
        $todayEnglish = $now->format('l');
        $kolomPulang = $dayMap[$todayEnglish] ?? 'jam_pulang_senin';
        $jamPulang = $setting?->$kolomPulang ?? '14:30:00';

        if ($jenis === 'masuk' && $now->toTimeString() < $jamMasuk) {
            return back()->withErrors(['foto' => 'Absen masuk dibuka mulai pukul ' . substr($jamMasuk, 0, 5) . '.']);
        }

        if ($jenis === 'pulang' && $now->toTimeString() < $jamPulang) {
            return back()->withErrors(['foto' => 'Absen pulang dibuka mulai pukul ' . substr($jamPulang, 0, 5) . '.']);
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

        if ($jenis === 'masuk' && $presensi->exists && !in_array($presensi->status, ['Hadir', 'Terlambat'], true)) {
            return back()->withErrors(['foto' => 'Status presensi hari ini sudah tercatat sebagai '.$presensi->status.'.']);
        }

        if ($jenis === 'pulang' && ! $presensi->jam_masuk) {
            return back()->withErrors(['foto' => 'Lakukan absen masuk sebelum absen pulang.']);
        }

        if ($jenis === 'pulang' && $presensi->jam_pulang) {
            return back()->withErrors(['foto' => 'Absen pulang hari ini sudah tercatat.']);
        }

        $path = $validated['foto']->store(
            'presensi_guru/'.$now->format('Y').'/'.$now->format('m'),
            'public'
        );

        if ($jenis === 'masuk') {
            $status = $now->toTimeString() > $jamToleransi ? 'Terlambat' : 'Hadir';
            $presensi->status = $status;
            $presensi->jam_masuk = $now->format('H:i:s');
            $presensi->foto_masuk = $path;
        } else {
            $presensi->jam_pulang = $now->format('H:i:s');
            $presensi->foto_pulang = $path;
        }

        $presensi->save();

        return back()->with('success', 'Presensi '.($jenis === 'masuk' ? 'masuk' : 'pulang').' berhasil dicatat.');
    }
}