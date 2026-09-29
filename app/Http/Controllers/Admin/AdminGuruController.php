<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\GuruImport;
use App\Models\Guru;
use App\Models\Mapel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class AdminGuruController extends Controller
{
    public function index(Request $request)
    {
        $query = Guru::with('mapels')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($query) use ($search) {
                    $query->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%")
                        ->orWhereHas('mapels', fn ($mapels) => $mapels->where('nama_mapel', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')));

        return view('pages.admin.dataguru', [
            'gurus' => $query->orderBy('nama_lengkap')->paginate(10)->withQueryString(),
            'mapels' => Mapel::orderBy('nama_mapel')->get(),
            'totalGuru' => Guru::count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255', 'unique:gurus,nama_lengkap'],
            'nip' => ['nullable', 'string', 'max:100', 'unique:gurus,nip'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'mapel_items' => ['nullable', 'array'],
            'mapel_items.*' => ['array'],
            'mapel_items.*.mapel_id' => ['nullable', 'integer', 'exists:mapels,id'],
            'mapel_items.*.nama_baru' => ['nullable', 'string', 'max:255'],
            'mapel_items.*.jumlah_jam' => ['nullable', 'integer', 'between:1,65535'],
        ]);

        DB::transaction(function () use ($validated) {
            $syncData = $this->mapelSyncData($validated['mapel_items'] ?? []);
            $guru = Guru::create([
                'nama_lengkap' => $validated['nama_lengkap'],
                'nip' => $validated['nip'] ?? null,
                'status' => $validated['status'],
            ]);
            $guru->mapels()->sync($syncData);
        });

        return redirect()->route('admin.guru.index')->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:10240'],
        ]);

        try {
            $import = new GuruImport;
            DB::transaction(fn () => Excel::import($import, $request->file('file')));

            return back()->with(
                $import->importedCount > 0 ? 'success' : 'error',
                $import->importedCount > 0
                    ? "Berhasil mengimpor {$import->importedCount} baris penugasan guru."
                    : 'Tidak ada baris data guru valid pada file tersebut.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Import gagal. Periksa format kolom dan isi file Excel/CSV.');
        }
    }

    public function template()
    {
        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Kode', 'Mata Pelajaran', 'Nama Guru', 'Jml. Jam']);
            fclose($output);
        }, 'template-data-guru.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function update(Request $request, $id)
    {
        $guru = Guru::findOrFail($id);
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255', 'unique:gurus,nama_lengkap,'.$guru->id],
            'nip' => ['nullable', 'string', 'max:100', 'unique:gurus,nip,'.$guru->id],
            'status' => ['required', 'in:aktif,nonaktif'],
            'mapel_items' => ['nullable', 'array'],
            'mapel_items.*' => ['array'],
            'mapel_items.*.mapel_id' => ['nullable', 'integer', 'exists:mapels,id'],
            'mapel_items.*.nama_baru' => ['nullable', 'string', 'max:255'],
            'mapel_items.*.jumlah_jam' => ['nullable', 'integer', 'between:1,65535'],
        ]);

        DB::transaction(function () use ($guru, $validated) {
            $guru->update([
                'nama_lengkap' => $validated['nama_lengkap'],
                'nip' => $validated['nip'] ?? null,
                'status' => $validated['status'],
            ]);

            $guru->mapels()->sync($this->mapelSyncData($validated['mapel_items'] ?? []));
        });

        return redirect()->route('admin.guru.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $guru = Guru::findOrFail($id);
        $guru->update(['status' => $guru->status === 'aktif' ? 'nonaktif' : 'aktif']);

        return redirect()->route('admin.guru.index')->with('success', 'Status guru berhasil diperbarui.');
    }

    private function mapelSyncData(array $items): array
    {
        $syncData = [];

        foreach ($items as $index => $item) {
            $mapelId = $item['mapel_id'] ?? null;
            $namaBaru = trim((string) ($item['nama_baru'] ?? ''));
            $jumlahJam = $item['jumlah_jam'] ?? null;

            if (! $mapelId && $namaBaru === '') {
                continue;
            }

            if ($mapelId && $namaBaru !== '') {
                throw ValidationException::withMessages([
                    "mapel_items.{$index}.nama_baru" => 'Pilih mapel yang sudah ada atau isi nama mapel baru, jangan keduanya.',
                ]);
            }

            if (! is_numeric($jumlahJam) || (int) $jumlahJam < 1 || (int) $jumlahJam > 65535) {
                throw ValidationException::withMessages([
                    "mapel_items.{$index}.jumlah_jam" => 'Jumlah jam wajib berupa angka antara 1 dan 65535.',
                ]);
            }

            if (! $mapelId) {
                $mapel = Mapel::firstOrCreate(
                    ['nama_mapel' => $namaBaru],
                    ['kode' => 'MANUAL-'.Str::upper(Str::random(12))]
                );
                $mapelId = $mapel->id;
            }

            $syncData[$mapelId] = ['jumlah_jam' => (int) $jumlahJam];
        }

        return $syncData;
    }
}
