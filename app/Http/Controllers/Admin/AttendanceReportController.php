<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
    public function index(): View
    {
        return view('pages.admin.rekapabsensi', [
            'kelasList' => Kelas::query()->orderBy('nama_kelas')->get(),
            'report' => $this->studentReport('hari', null, null, ''),
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
                'presensiHarian' => fn ($query) => $query
                    ->whereDate('tanggal', '>=', $start)
                    ->whereDate('tanggal', '<=', $end)
                    ->orderBy('tanggal'),
            ])
            ->orderBy('nama_lengkap')
            ->get();

        $mapGuru = fn (Guru $guru): array => [
            'id' => $guru->id,
            'nama' => $guru->nama_lengkap,
            'mapel' => $guru->mapels->pluck('nama_mapel')->join(', ') ?: 'Guru',
        ];
        $hadir = $gurus
            ->filter(fn (Guru $guru): bool => $guru->presensiHarian->contains('status', 'Hadir'))
            ->map(fn (Guru $guru): array => $mapGuru($guru) + [
                'tanggal' => $guru->presensiHarian
                    ->where('status', 'Hadir')
                    ->map(fn ($attendance): array => [
                        'tanggal' => $attendance->tanggal->toDateString(),
                        'jam_masuk' => $attendance->jam_masuk,
                    ])
                    ->values(),
            ])
            ->values();
        $tidakHadir = $gurus
            ->filter(fn (Guru $guru): bool => $guru->presensiHarian->contains(
                fn ($attendance): bool => in_array($attendance->status, ['Izin', 'Sakit', 'Alfa'], true)
            ))
            ->map(fn (Guru $guru): array => $mapGuru($guru) + [
                'tanggal' => $guru->presensiHarian
                    ->whereIn('status', ['Izin', 'Sakit', 'Alfa'])
                    ->map(fn ($attendance): array => [
                        'tanggal' => $attendance->tanggal->toDateString(),
                        'status' => $attendance->status,
                    ])
                    ->values(),
            ])
            ->values();
        $belumPresensi = $gurus
            ->filter(fn (Guru $guru): bool => $guru->presensiHarian->isEmpty())
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
            $filters['periode'],
            $filters['kelas'],
            $filters['status'],
            $filters['search']
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedStudentFilters($request);
        $report = $this->studentReport(
            $filters['periode'],
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
        }, 'rekap-absensi-'.$filters['periode'].'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $filters = $this->validatedStudentFilters($request);
        $matchingIds = $this->studentAttendanceQuery(
            $filters['periode'],
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
            'periode' => ['sometimes', 'in:hari,minggu,bulan'],
            'kelas' => ['nullable', 'integer', 'exists:kelas,id'],
            'status' => ['nullable', 'in:Hadir,Izin,Sakit,Alfa'],
            'search' => ['nullable', 'string', 'max:120'],
        ]) + ['periode' => 'hari', 'kelas' => null, 'status' => null, 'search' => ''];
    }

    private function studentReport(string $period, ?int $classId, ?string $status, ?string $search = null): array
    {
        $search = $search ?? '';
        [$start, $end] = $this->dateRange($period);
        $summaryQuery = $this->studentAttendanceQuery($period);
        $summary = [
            'hadir' => (clone $summaryQuery)->where('presensi_pelajarans.status', 'Hadir')->distinct()->count('presensi_pelajarans.siswa_id'),
            'izin_sakit' => (clone $summaryQuery)->whereIn('presensi_pelajarans.status', ['Izin', 'Sakit'])->distinct()->count('presensi_pelajarans.siswa_id'),
            'alfa' => (clone $summaryQuery)->where('presensi_pelajarans.status', 'Alfa')->distinct()->count('presensi_pelajarans.siswa_id'),
        ];

        $rows = $this->studentAttendanceQuery($period, $classId, $status, $search)
            ->with(['siswa.kelas', 'sesiPelajaran.jadwal.mapel'])
            ->orderBy('siswas.nama_lengkap')
            ->orderBy('sesi_pelajarans.tanggal')
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
            'period' => $period,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ];
    }

    private function studentAttendanceQuery(
        string $period,
        ?int $classId = null,
        ?string $status = null,
        ?string $search = null
    ): Builder {
        $search = $search ?? '';
        [$start, $end] = $this->dateRange($period);

        return PresensiPelajaran::query()
            ->select('presensi_pelajarans.*')
            ->join('siswas', 'siswas.id', '=', 'presensi_pelajarans.siswa_id')
            ->join('sesi_pelajarans', 'sesi_pelajarans.id', '=', 'presensi_pelajarans.sesi_pelajaran_id')
            ->whereDate('sesi_pelajarans.tanggal', '>=', $start)
            ->whereDate('sesi_pelajarans.tanggal', '<=', $end)
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
