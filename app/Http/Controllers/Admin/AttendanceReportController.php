<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DashboardAttendanceLog;
use App\Models\DashboardAttendanceSessionLog;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\PresensiPelajaran;
use App\Models\Attendance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedStudentFilters($request);
        $dateTo = $filters['date_to'] ?? $filters['date'];

        return view('pages.admin.rekapabsensi', [
            'kelasList' => Kelas::query()->orderBy('nama_kelas')->get(),
            'selectedDate' => $filters['date'],
            'selectedDateTo' => $dateTo,
            'selectedClass' => $filters['kelas'],
            'selectedStatus' => $filters['status'],
            'selectedSearch' => $filters['search'],
            'report' => $this->studentReport(
                $filters['date'],
                $filters['kelas'],
                $filters['status'],
                $filters['search'],
                $dateTo
            ),
        ]);
    }

    public function guruPeriod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periode' => ['nullable', 'in:hari,minggu,bulan'],
            'type' => ['nullable', 'string', 'in:mapel'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'nama_guru' => ['nullable', 'string', 'max:120'],
            'nama_mapel' => ['nullable', 'string', 'max:120'],
        ]);

        if ($request->get('type') === 'mapel') {
            $dateFrom = $validated['date'] ?? today()->toDateString();
            $dateTo = $validated['date_to'] ?? $dateFrom;
            $namaGuru = $validated['nama_guru'] ?? null;
            $namaMapel = $validated['nama_mapel'] ?? null;

            $query = DB::table('presensi_guru_mapels')
                ->join('gurus', 'gurus.id', '=', 'presensi_guru_mapels.guru_id')
                ->join('jadwal_pelajarans', 'jadwal_pelajarans.id', '=', 'presensi_guru_mapels.jadwal_id')
                ->join('mapels', 'mapels.id', '=', 'jadwal_pelajarans.mapel_id')
                ->join('kelas', 'kelas.id', '=', 'jadwal_pelajarans.kelas_id')
                ->whereDate('presensi_guru_mapels.tanggal', '>=', $dateFrom)
                ->whereDate('presensi_guru_mapels.tanggal', '<=', $dateTo);

            if ($namaGuru) {
                $query->where('gurus.nama_lengkap', 'like', "%{$namaGuru}%");
            }
            if ($namaMapel) {
                $query->where('mapels.nama_mapel', 'like', "%{$namaMapel}%");
            }

            $mapelLogs = $query->select(
                    'presensi_guru_mapels.*',
                    'gurus.nama_lengkap as nama_guru',
                    'mapels.nama_mapel',
                    'kelas.nama_kelas',
                    'jadwal_pelajarans.ruangan',
                    'jadwal_pelajarans.jam_mulai',
                    'jadwal_pelajarans.jam_selesai'
                )
                ->orderByDesc('presensi_guru_mapels.tanggal')
                ->orderBy('jadwal_pelajarans.jam_mulai')
                ->get()
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'tanggal' => $log->tanggal,
                    'nama_guru' => $log->nama_guru,
                    'nama_mapel' => $log->nama_mapel,
                    'nama_kelas' => $log->nama_kelas,
                    'ruangan' => $log->ruangan,
                    'jam_mulai' => $log->jam_mulai,
                    'jam_selesai' => $log->jam_selesai,
                    'jam_mulai_mengajar' => $log->jam_mulai_mengajar,
                    'jam_selesai_mengajar' => $log->jam_selesai_mengajar,
                    'foto_mulai' => $log->foto_mulai ? asset('storage/'.$log->foto_mulai) : null,
                    'foto_selesai' => $log->foto_selesai ? asset('storage/'.$log->foto_selesai) : null,
                ])
                ->values();

            return response()->json(['mapel_logs' => $mapelLogs]);
        }

        [$start, $end] = $this->dateRange($validated['periode'] ?? 'hari');

        $gurus = Guru::query()
            ->with([
                'mapels',
                'dashboardPresensiLogs' => fn ($query) => $query
                    ->whereDate('tanggal', '>=', $start)
                    ->whereDate('tanggal', '<=', $end),
                'dashboardAttendanceSessionLogs' => fn ($query) => $query
                    ->whereDate('tanggal', '>=', $start)
                    ->whereDate('tanggal', '<=', $end),
            ])
            ->orderBy('nama_lengkap')
            ->get();

        $mapGuru = fn (Guru $guru): array => [
            'id' => $guru->id,
            'nama' => $guru->nama_lengkap,
            'mapel' => $guru->mapels->pluck('nama_mapel')->join(', ') ?: 'Guru',
        ];
        $hadir = $gurus
            ->filter(fn (Guru $guru): bool => $guru->dashboardPresensiLogs->contains('status', 'Hadir')
                || $guru->dashboardAttendanceSessionLogs->isNotEmpty())
            ->map(fn (Guru $guru): array => $mapGuru($guru) + [
                'tanggal' => $guru->dashboardAttendanceSessionLogs
                    ->map(fn (DashboardAttendanceSessionLog $session): array => [
                        'id' => $session->id,
                        'tanggal' => $session->tanggal->toDateString(),
                        'session_type' => $session->session_type,
                        'jam_masuk' => $session->session_type === 'masuk' ? $session->jam : null,
                        'jam_pulang' => $session->session_type === 'pulang' ? $session->jam : null,
                        'foto_masuk' => $session->session_type === 'masuk' && $session->foto ? asset('storage/'.$session->foto) : null,
                        'foto_pulang' => $session->session_type === 'pulang' && $session->foto ? asset('storage/'.$session->foto) : null,
                        'foto_bukti' => $session->foto ? asset('storage/'.$session->foto) : null,
                    ])
                    ->values(),
            ])
            ->values();
        $tidakHadir = $gurus
            ->filter(fn (Guru $guru): bool => $guru->dashboardPresensiLogs->contains(
                fn ($attendance): bool => in_array($attendance->status, ['Izin', 'Sakit', 'Alfa'], true)
            ))
            ->map(fn (Guru $guru): array => $mapGuru($guru) + [
                'tanggal' => $guru->dashboardPresensiLogs
                    ->whereIn('status', ['Izin', 'Sakit', 'Alfa'])
                    ->map(fn (DashboardAttendanceLog $attendance): array => [
                        'id' => $attendance->id,
                        'tanggal' => $attendance->tanggal->toDateString(),
                        'status' => $attendance->status,
                        'jam_masuk' => $attendance->jam_masuk,
                        'jam_pulang' => $attendance->jam_pulang,
                        'foto_masuk' => $attendance->foto_masuk ? asset('storage/'.$attendance->foto_masuk) : null,
                        'foto_pulang' => $attendance->foto_pulang ? asset('storage/'.$attendance->foto_pulang) : null,
                        'foto_bukti' => $attendance->foto_masuk ? asset('storage/'.$attendance->foto_masuk) : null,
                        'bukti_izin' => $attendance->bukti_izin ? asset('storage/'.$attendance->bukti_izin) : null,
                    ])
                    ->values(),
            ])
            ->values();
        $belumPresensi = $gurus
            ->filter(fn (Guru $guru): bool => $guru->dashboardPresensiLogs->isEmpty())
            ->map($mapGuru)
            ->values();

        return response()->json([
            'hadir' => $hadir,
            'tidak_hadir' => $tidakHadir,
            'belum_presensi' => $belumPresensi,
            'counts' => [
                'hadir' => $hadir->count(),
                'tidak_hadir' => $tidakHadir->count(),
                'belum_presensi' => $belumPresensi->count(),
            ],
        ]);
    }

    public function studentData(Request $request): JsonResponse
    {
        $filters = $this->validatedStudentFilters($request);
        $dateTo = $filters['date_to'] ?? $filters['date'];

        return response()->json($this->studentReport(
            $filters['date'],
            $filters['kelas'],
            $filters['status'],
            $filters['search'],
            $dateTo
        ));
    }

    public function export(Request $request)
    {
        $filters = $this->validatedExportFilters($request);
        $dateFrom = $filters['date_from'] ?? $filters['date'];
        $dateTo = $filters['date_to'] ?? ($filters['date_from'] ?? $filters['date']);
        $report = $this->studentReportForFilters($filters);
        $kelasName = $filters['kelas']
            ? Kelas::query()->whereKey($filters['kelas'])->value('nama_kelas')
            : null;

        return Pdf::loadView('pages.admin.rekapabsensi-pdf', [
            'rows' => $report['rows'],
            'filters' => $filters,
            'kelasName' => $kelasName,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->download('rekap-absensi-'.$dateFrom.($dateTo === $dateFrom ? '' : '-'.$dateTo).'.pdf');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $type = $request->input('type', 'murid');

        if ($type === 'murid') {
            $dateFrom = $request->input('date', today()->toDateString());
            $dateTo = $request->input('date_to', $dateFrom);
            $kelas = $request->input('kelas');
            $status = $request->input('status');
            $search = $request->input('search');

            $query = PresensiPelajaran::query()
                ->join('sesi_pelajarans', 'sesi_pelajarans.id', '=', 'presensi_pelajarans.sesi_pelajaran_id')
                ->whereDate('sesi_pelajarans.tanggal', '>=', $dateFrom)
                ->whereDate('sesi_pelajarans.tanggal', '<=', $dateTo);

            if ($kelas) {
                $query->join('siswas', 'siswas.id', '=', 'presensi_pelajarans.siswa_id')
                      ->where('siswas.kelas_id', $kelas);
            }
            if ($status) {
                $query->where('presensi_pelajarans.status', $status);
            }
            if ($search) {
                $query->join('siswas', 'siswas.id', '=', 'presensi_pelajarans.siswa_id')
                      ->where(function ($q) use ($search) {
                          $q->where('siswas.nama_lengkap', 'like', "%{$search}%")
                            ->orWhere('siswas.nisn', 'like', "%{$search}%");
                      });
            }

            // Hapus data presensi pelajaran siswa yang sesuai
            PresensiPelajaran::whereIn('id', $query->pluck('presensi_pelajarans.id'))->delete();

        } elseif ($type === 'mapel') {
            $dateFrom = $request->input('date', today()->toDateString());
            $dateTo = $request->input('date_to', $dateFrom);
            $namaGuru = $request->input('nama_guru');
            $namaMapel = $request->input('nama_mapel');

            $query = DB::table('presensi_guru_mapels')
                ->join('gurus', 'gurus.id', '=', 'presensi_guru_mapels.guru_id')
                ->join('jadwal_pelajarans', 'jadwal_pelajarans.id', '=', 'presensi_guru_mapels.jadwal_id')
                ->join('mapels', 'mapels.id', '=', 'jadwal_pelajarans.mapel_id')
                ->whereDate('presensi_guru_mapels.tanggal', '>=', $dateFrom)
                ->whereDate('presensi_guru_mapels.tanggal', '<=', $dateTo);

            if ($namaGuru) {
                $query->where('gurus.nama_lengkap', 'like', "%{$namaGuru}%");
            }
            if ($namaMapel) {
                $query->where('mapels.nama_mapel', 'like', "%{$namaMapel}%");
            }

            $ids = $query->pluck('presensi_guru_mapels.id');
            if ($ids->isNotEmpty()) {
                DB::table('presensi_guru_mapels')->whereIn('id', $ids)->delete();
            }

        } elseif ($type === 'guru') {
            $periode = $request->input('periode', 'hari');
            [$start, $end] = $this->dateRange($periode);
            
            // Hapus log kehadiran harian guru pada rentang periode tersebut jika ada
            DashboardAttendanceLog::whereDate('tanggal', '>=', $start)
                ->whereDate('tanggal', '<=', $end)
                ->delete();
            
            DashboardAttendanceSessionLog::whereDate('tanggal', '>=', $start)
                ->whereDate('tanggal', '<=', $end)
                ->delete();
        }

        return redirect()->back()->with('success', 'Data presensi berhasil dihapus.');
    }

    private function validatedStudentFilters(Request $request): array
    {
        $filters = $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'kelas' => ['nullable', 'integer', 'exists:kelas,id'],
            'status' => ['nullable', 'in:Hadir,Izin,Sakit,Alfa'],
            'search' => ['nullable', 'string', 'max:120'],
        ]) + ['date' => today()->toDateString(), 'date_to' => null, 'kelas' => null, 'status' => null, 'search' => ''];

        return $filters;
    }

    private function validatedExportFilters(Request $request): array
    {
        $filters = $this->validatedStudentFilters($request);
        $range = $request->validate([
            'date_from' => ['nullable', 'required_with:date_to', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $filters = array_merge($filters, $range);

        if (filled($filters['date_from'] ?? null)
            && filled($filters['date_to'] ?? null)
            && $filters['date_to'] < $filters['date_from']) {
            throw ValidationException::withMessages([
                'date_to' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            ]);
        }

        return $filters;
    }

    private function studentReportForFilters(array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? $filters['date'];
        $dateTo = $filters['date_to'] ?? ($filters['date_from'] ?? $filters['date']);

        return $this->studentReport(
            $dateFrom,
            $filters['kelas'],
            $filters['status'],
            $filters['search'],
            $dateTo
        );
    }

    private function studentReport(string $date, ?int $classId, ?string $status, ?string $search = null, ?string $dateTo = null): array
    {
        $search = $search ?? '';
        $dateTo = $dateTo ?? $date;
        $summaryQuery = $this->studentAttendanceQuery($date, $classId, $status, $search, $dateTo);
        $summary = [
            'hadir' => (clone $summaryQuery)->where('presensi_pelajarans.status', 'Hadir')->distinct()->count('presensi_pelajarans.siswa_id'),
            'izin_sakit' => (clone $summaryQuery)->whereIn('presensi_pelajarans.status', ['Izin', 'Sakit'])->distinct()->count('presensi_pelajarans.siswa_id'),
            'alfa' => (clone $summaryQuery)->where('presensi_pelajarans.status', 'Alfa')->distinct()->count('presensi_pelajarans.siswa_id'),
        ];

        $rows = $this->studentAttendanceQuery($date, $classId, $status, $search, $dateTo)
            ->with(['siswa.kelas', 'sesiPelajaran.jadwal.mapel'])
            ->orderBy('siswas.nama_lengkap')
            ->orderBy('sesi_pelajarans.tanggal')
            ->orderBy('sesi_pelajarans.id')
            ->get()
            ->map(fn (PresensiPelajaran $attendance): array => [
                'nama' => $attendance->siswa?->nama_lengkap ?? '-',
                'nisn' => $attendance->siswa?->nisn ?? '-',
                'kelas' => $attendance->siswa?->kelas?->nama_kelas ?? '-',
                'kelas_id' => $attendance->siswa?->kelas_id,
                'status' => $attendance->status,
                'tanggal' => $attendance->sesiPelajaran?->tanggal?->format('Y-m-d') ?? '-',
                'mapel' => $attendance->sesiPelajaran?->jadwal?->mapel?->nama_mapel ?? '-',
                'jam_pelajaran' => $attendance->sesiPelajaran?->jadwal
                    ? substr($attendance->sesiPelajaran->jadwal->jam_mulai, 0, 5)
                        .'–'.substr($attendance->sesiPelajaran->jadwal->jam_selesai, 0, 5)
                    : '-',
                'waktu' => $attendance->waktu_scan?->format('H:i:s') ?? '-',
                'keterangan' => $attendance->keterangan ?? '',
            ])
            ->values();

        return [
            'summary' => $summary,
            'rows' => $rows,
            'total' => $rows->count(),
            'date' => $date,
        ];
    }

    private function studentAttendanceQuery(
        string $date,
        ?int $classId = null,
        ?string $status = null,
        ?string $search = null,
        ?string $dateTo = null
    ): Builder {
        $search = $search ?? '';

        return PresensiPelajaran::query()
            ->select('presensi_pelajarans.*')
            ->join('siswas', 'siswas.id', '=', 'presensi_pelajarans.siswa_id')
            ->join('sesi_pelajarans', 'sesi_pelajarans.id', '=', 'presensi_pelajarans.sesi_pelajaran_id')
            ->whereDate('sesi_pelajarans.tanggal', '>=', $date)
            ->whereDate('sesi_pelajarans.tanggal', '<=', $dateTo ?? $date)
            ->where('presensi_pelajarans.status', '!=', 'Belum Absen')
            ->when($classId, fn (Builder $query) => $query->where('siswas.kelas_id', $classId))
            ->when($status, fn (Builder $query) => $query->where('presensi_pelajarans.status', $status))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('siswas.nama_lengkap', 'like', '%'.$search.'%')
                    ->orWhere('siswas.nisn', 'like', '%'.$search.'%');
            }));
    }

    private function dateRange(string $period): array
    {
        $end = today();
        $start = match ($period) {
            'minggu' => $end->copy()->subDays(6),
            'bulan' => $end->copy()->subMonth(),
            default => $end->copy(),
        };

        return [$start, $end];
    }
}