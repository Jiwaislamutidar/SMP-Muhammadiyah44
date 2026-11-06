<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DashboardAttendanceLog;
use App\Models\DashboardAttendanceSessionLog;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\PresensiPelajaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
        ]);
        $date = $validated['date'] ?? today()->toDateString();

        return view('pages.admin.rekapabsensi', [
            'kelasList' => Kelas::query()->orderBy('nama_kelas')->get(),
            'selectedDate' => $date,
            'report' => $this->studentReport($date, null, null, ''),
        ]);
    }

    public function guruPeriod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periode' => ['required', 'in:hari,minggu,bulan'],
        ]);
        [$start, $end] = $this->dateRange($validated['periode']);

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

        return response()->json($this->studentReport(
            $filters['date'],
            $filters['kelas'],
            $filters['status'],
            $filters['search']
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedStudentFilters($request);
        $report = $this->studentReport(
            $filters['date'],
            $filters['kelas'],
            $filters['status'],
            $filters['search']
        );
        $rows = $report['rows'];

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Nama Murid', 'NISN', 'Kelas', 'Status', 'Tanggal', 'Mata Pelajaran', 'Jam Pelajaran', 'Waktu Presensi', 'Keterangan']);

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['nama'],
                    $row['nisn'],
                    $row['kelas'],
                    $row['status'],
                    $row['tanggal'],
                    $row['mapel'],
                    $row['jam_pelajaran'],
                    $row['waktu'],
                    $row['keterangan'],
                ]);
            }

            fclose($output);
        }, 'rekap-absensi-'.$filters['date'].'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $filters = $this->validatedStudentFilters($request);
        $matchingIds = $this->studentAttendanceQuery(
            $filters['date'],
            $filters['kelas'],
            $filters['status'],
            $filters['search']
        )->pluck('presensi_pelajarans.id');
        $deleted = 0;

        DB::transaction(function () use ($matchingIds, &$deleted): void {
            foreach ($matchingIds->chunk(500) as $chunk) {
                $deleted += DB::table('presensi_pelajarans')
                    ->whereIn('id', $chunk)
                    ->delete();
            }
        });

        return redirect()->route('admin.rekapabsensi')
            ->with('success', 'Data presensi berhasil dihapus: '.$deleted.' catatan.');
    }

    private function validatedStudentFilters(Request $request): array
    {
        return $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'kelas' => ['nullable', 'integer', 'exists:kelas,id'],
            'status' => ['nullable', 'in:Hadir,Izin,Sakit,Alfa'],
            'search' => ['nullable', 'string', 'max:120'],
        ]) + ['date' => today()->toDateString(), 'kelas' => null, 'status' => null, 'search' => ''];
    }

    private function studentReport(string $date, ?int $classId, ?string $status, ?string $search = null): array
    {
        $search = $search ?? '';
        $summaryQuery = $this->studentAttendanceQuery($date);
        $summary = [
            'hadir' => (clone $summaryQuery)->where('presensi_pelajarans.status', 'Hadir')->distinct()->count('presensi_pelajarans.siswa_id'),
            'izin_sakit' => (clone $summaryQuery)->whereIn('presensi_pelajarans.status', ['Izin', 'Sakit'])->distinct()->count('presensi_pelajarans.siswa_id'),
            'alfa' => (clone $summaryQuery)->where('presensi_pelajarans.status', 'Alfa')->distinct()->count('presensi_pelajarans.siswa_id'),
        ];

        $rows = $this->studentAttendanceQuery($date, $classId, $status, $search)
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
        ?string $search = null
    ): Builder {
        $search = $search ?? '';

        return PresensiPelajaran::query()
            ->select('presensi_pelajarans.*')
            ->join('siswas', 'siswas.id', '=', 'presensi_pelajarans.siswa_id')
            ->join('sesi_pelajarans', 'sesi_pelajarans.id', '=', 'presensi_pelajarans.sesi_pelajaran_id')
            ->whereDate('sesi_pelajarans.tanggal', $date)
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
