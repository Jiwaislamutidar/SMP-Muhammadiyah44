<?php

namespace App\Http\Controllers\Admin;

use App\Exports\JadwalTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\JadwalImport;
use App\Imports\JadwalImportException;
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
            ->orderByRaw("CASE hari WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 ELSE 6 END")
            ->orderBy('jam_mulai')
            ->paginate(15)
            ->withQueryString();

        return view('pages.admin.jadwalpelajaran', [
            'jadwals' => $jadwals,
            'hariList' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
            'kelasList' => Kelas::query()->orderBy('nama_kelas')->get(),
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
        return Excel::download(new JadwalTemplateExport, 'template-jadwal-pelajaran.xlsx');
    }
}
