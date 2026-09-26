<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\SiswaImport;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $query = Siswa::with(['user', 'kelas'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');

                $query->where(function ($query) use ($search) {
                    $query->where('nisn', 'like', "%{$search}%")
                        ->orWhere('nama_lengkap', 'like', "%{$search}%")
                        ->orWhereHas('kelas', fn ($kelas) => $kelas->where('nama_kelas', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('kelas_id'), fn ($query) => $query->where('kelas_id', $request->input('kelas_id')));

        return view('pages.admin.datamurid', [
            'siswas' => $query->orderBy('nama_lengkap')->paginate(8)->withQueryString(),
            'kelasList' => Kelas::orderBy('nama_kelas')->get(),
            'totalSiswa' => Siswa::count(),
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ]);

        try {
            $import = new SiswaImport();
            Excel::import($import, $request->file('file'));

            $response = $import->importedCount === 0
                ? back()->with('error', 'Tidak ada data siswa yang berhasil diimpor.')
                : back()->with('success', 'Berhasil mengimpor data siswa!');

            return $response;
        } catch (Throwable $exception) {
            return back()->with('error', 'Gagal mengimpor data: ' . $exception->getMessage());
        }
    }
}