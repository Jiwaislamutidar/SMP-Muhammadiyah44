<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\JadwalImport;
use App\Imports\JadwalImportException;
use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Mapel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class AdminJadwalController extends Controller
{
    public function index(Request $request)
    {
        $jadwals = JadwalPelajaran::with(['guru', 'kelas', 'mapel'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($query) use ($search) {
                    $query->whereHas('guru', fn ($guru) => $guru->where('nama_lengkap', 'like', "%{$search}%"))
                        ->orWhereHas('kelas', fn ($kelas) => $kelas->where('nama_kelas', 'like', "%{$search}%"))
                        ->orWhereHas('mapel', fn ($mapel) => $mapel->where('nama_mapel', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('hari'), fn ($query) => $query->where('hari', $request->input('hari')))
            ->when($request->filled('kelas_id'), fn ($query) => $query->where('kelas_id', $request->integer('kelas_id')))
            ->when($request->filled('mapel_id'), fn ($query) => $query->where('mapel_id', $request->integer('mapel_id')))
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->paginate(15)
            ->withQueryString();

        return view('pages.admin.jadwalpelajaran', [
            'jadwals' => $jadwals,
            'hariList' => JadwalPelajaran::query()->select('hari')->distinct()->orderBy('hari')->pluck('hari'),
            'kelasList' => Kelas::orderBy('nama_kelas')->get(),
            'mapelList' => Mapel::orderBy('nama_mapel')->get(),
            'totalJadwal' => JadwalPelajaran::count(),
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            $import = new JadwalImport;
            DB::transaction(fn () => Excel::import($import, $request->file('file')));

            return back()->with(
                $import->importedCount > 0 ? 'success' : 'error',
                $import->importedCount > 0
                    ? "Berhasil mengimpor {$import->importedCount} baris jadwal."
                    : 'Tidak ada baris jadwal valid pada file tersebut.'
            );
        } catch (JadwalImportException $exception) {
            return back()->with('error', '❌ '.$exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Import jadwal gagal. Periksa template, jam, dan data guru/kelas/mapel.');
        }
    }

    public function template()
    {
        $examples = $this->templateExamples();

        return response()->streamDownload(function () use ($examples) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Hari', 'Jam Mulai', 'Jam Selesai', 'Kode Mapel', 'Nama Guru', 'Nama Kelas', 'Ruangan']);
            foreach ($examples as $example) {
                fputcsv($output, $example);
            }
            fclose($output);
        }, 'template-jadwal-pelajaran.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function templateExamples(): array
    {
        $assignments = DB::table('guru_mapel')
            ->join('gurus', 'gurus.id', '=', 'guru_mapel.guru_id')
            ->join('mapels', 'mapels.id', '=', 'guru_mapel.mapel_id')
            ->select('mapels.kode', 'gurus.nama_lengkap')
            ->orderBy('guru_mapel.id')
            ->limit(2)
            ->get();

        if ($assignments->isEmpty()) {
            $guru = Guru::orderBy('nama_lengkap')->first();
            $mapel = Mapel::orderBy('nama_mapel')->first();
            if ($guru && $mapel) {
                $assignments = collect([(object) [
                    'kode' => $mapel->kode,
                    'nama_lengkap' => $guru->nama_lengkap,
                ]]);
            }
        }

        $kelasList = Kelas::orderBy('nama_kelas')->limit(2)->get();
        if ($assignments->isEmpty() || $kelasList->isEmpty()) {
            return [];
        }

        return $assignments->values()->map(fn ($assignment, $index) => [
            $index === 0 ? 'Senin' : 'Selasa',
            $index === 0 ? '07:00' : '08:00',
            $index === 0 ? '08:00' : '09:00',
            $assignment->kode,
            $assignment->nama_lengkap,
            $kelasList[$index % $kelasList->count()]->nama_kelas,
            'Ruang Contoh '.($index + 1),
        ])->all();
    }
}