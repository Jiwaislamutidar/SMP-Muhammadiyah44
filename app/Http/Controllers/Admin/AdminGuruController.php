<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\GuruTemplateExport;
use App\Imports\GuruImport;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
                        ->orWhere('nbm', 'like', "%{$search}%")
                        ->orWhereHas('mapels', fn ($mapels) => $mapels->where('nama_mapel', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')));

        $gurus = $query->orderBy('nama_lengkap')->get();

        if ($request->expectsJson()) {
            return response()->json([
                'gurus' => $gurus->map(fn (Guru $guru) => $this->guruResponseData($guru))->values(),
                'total_guru' => Guru::count(),
            ]);
        }

        return view('pages.admin.dataguru', [
            'gurus' => $gurus,
            'mapels' => Mapel::orderBy('nama_mapel')->get(),
            'totalGuru' => Guru::count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255', 'unique:gurus,nama_lengkap'],
            'nbm' => ['nullable', 'string', 'max:100', 'unique:gurus,nbm', 'unique:users,username'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'mapel_items' => ['nullable', 'array'],
            'mapel_items.*' => ['array'],
            'mapel_items.*.mapel_id' => ['nullable', 'integer', 'exists:mapels,id'],
            'mapel_items.*.nama_baru' => ['nullable', 'string', 'max:255'],
            'mapel_items.*.kode_baru' => ['nullable', 'string', 'max:100', 'unique:mapels,kode'],
            'mapel_items.*.jumlah_jam' => ['nullable', 'integer', 'between:1,65535'],
        ]);

        $guru = DB::transaction(function () use ($validated) {
            $syncData = $this->mapelSyncData($validated['mapel_items'] ?? []);
            $user = null;
            if (filled($validated['nbm'] ?? null)) {
                $user = User::create([
                    'name' => $validated['nama_lengkap'],
                    'username' => $validated['nbm'],
                    'email' => null,
                    'role' => 'guru',
                    'password' => Hash::make($validated['nbm']),
                ]);
            }

            $guru = Guru::create([
                'user_id' => $user?->id,
                'nama_lengkap' => $validated['nama_lengkap'],
                'nbm' => $validated['nbm'] ?? null,
                'status' => $validated['status'],
            ]);
            $guru->mapels()->sync($syncData);

            return $guru;
        });

        if ($request->expectsJson()) {
            $guru->load('mapels');

            return response()->json([
                'message' => 'Data guru berhasil ditambahkan.',
                'guru' => $this->guruResponseData($guru),
                'total_guru' => Guru::count(),
            ], 201);
        }

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

            $message = $import->importedCount > 0
                ? "Berhasil mengimpor {$import->importedCount} baris penugasan guru."
                : 'Tidak ada baris data guru valid pada file tersebut.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'imported_count' => $import->importedCount,
                    'total_guru' => Guru::count(),
                ], $import->importedCount > 0 ? 200 : 422);
            }

            return back()->with($import->importedCount > 0 ? 'success' : 'error', $message);
        } catch (Throwable $exception) {
            report($exception);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Import gagal. Periksa format kolom dan isi file Excel/CSV.',
                ], 422);
            }

            return back()->with('error', 'Import gagal. Periksa format kolom dan isi file Excel/CSV.');
        }
    }

    public function template()
    {
        return Excel::download(new GuruTemplateExport, 'template-data-guru.xlsx');
    }

    public function update(Request $request, $id)
    {
        $guru = Guru::with('user')->findOrFail($id);
        $linkedUserId = $guru->user_id ?? User::query()
            ->where('role', 'guru')
            ->where('username', $guru->nbm)
            ->value('id');
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255', 'unique:gurus,nama_lengkap,'.$guru->id],
            'nbm' => [
                $linkedUserId ? 'required' : 'nullable',
                'string',
                'max:100',
                'unique:gurus,nbm,'.$guru->id,
                Rule::unique('users', 'username')->ignore($linkedUserId),
            ],
            'status' => ['required', 'in:aktif,nonaktif'],
            'mapel_items' => ['nullable', 'array'],
            'mapel_items.*' => ['array'],
            'mapel_items.*.mapel_id' => ['nullable', 'integer', 'exists:mapels,id'],
            'mapel_items.*.nama_baru' => ['nullable', 'string', 'max:255'],
            'mapel_items.*.kode_baru' => ['nullable', 'string', 'max:100', Rule::unique('mapels', 'kode')],
            'mapel_items.*.jumlah_jam' => ['nullable', 'integer', 'between:1,65535'],
        ]);

        DB::transaction(function () use ($guru, $linkedUserId, $validated) {
            $user = $guru->user ?? ($linkedUserId ? User::findOrFail($linkedUserId) : null);
            if ($user && $user->role !== 'guru') {
                abort(409, 'Akun yang terhubung bukan akun guru.');
            }

            if (! $user && filled($validated['nbm'] ?? null)) {
                $user = new User([
                    'email' => null,
                    'role' => 'guru',
                    'password' => Hash::make($validated['nbm']),
                ]);
            }

            if ($user) {
                $user->name = $validated['nama_lengkap'];
                $user->username = $validated['nbm'];
                $user->save();
            }

            $guru->update([
                'nama_lengkap' => $validated['nama_lengkap'],
                'nbm' => $validated['nbm'] ?? null,
                'status' => $validated['status'],
                'user_id' => $user?->id,
            ]);

            $guru->mapels()->sync($this->mapelSyncData($validated['mapel_items'] ?? []));
        });

        if ($request->expectsJson()) {
            $guru->load('mapels');

            return response()->json([
                'message' => 'Data guru berhasil diperbarui.',
                'guru' => [
                    'id' => $guru->id,
                    'nama_lengkap' => $guru->nama_lengkap,
                    'nbm' => $guru->nbm,
                    'status' => $guru->status,
                    'mapels' => $guru->mapels->pluck('nama_mapel')->values(),
                    'mapel_items' => $guru->mapels->map(fn ($mapel) => [
                        'mapel_id' => $mapel->id,
                        'jumlah_jam' => $mapel->pivot->jumlah_jam,
                    ])->values(),
                    'total_jam' => $guru->mapels->sum('pivot.jumlah_jam'),
                ],
                'total_guru' => Guru::count(),
            ]);
        }

        return redirect()->route('admin.guru.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        DB::transaction(function () use ($id) {
            $guru = Guru::query()->lockForUpdate()->findOrFail($id);
            $user = $guru->user()->lockForUpdate()->first()
                ?? User::query()
                    ->where('role', 'guru')
                    ->where('username', $guru->nbm)
                    ->lockForUpdate()
                    ->first();

            if ($user && $user->role !== 'guru') {
                abort(409, 'Akun yang terhubung bukan akun guru.');
            }

            $guru->delete();
            $user?->delete();
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Data guru dan akun terkait berhasil dihapus.',
                'id' => (int) $id,
                'total_guru' => Guru::count(),
            ]);
        }

        return redirect()->route('admin.guru.index')->with('success', 'Data guru dan akun terkait berhasil dihapus.');
    }

    private function mapelSyncData(array $items): array
    {
        $syncData = [];

        foreach ($items as $index => $item) {
            $mapelId = $item['mapel_id'] ?? null;
            $namaBaru = trim((string) ($item['nama_baru'] ?? ''));
            $kodeBaru = trim((string) ($item['kode_baru'] ?? ''));
            $jumlahJam = $item['jumlah_jam'] ?? null;

            if (! $mapelId && $namaBaru === '' && $kodeBaru === '') {
                continue;
            }

            if ($mapelId && ($namaBaru !== '' || $kodeBaru !== '')) {
                throw ValidationException::withMessages([
                    "mapel_items.{$index}.nama_baru" => 'Pilih mapel yang sudah ada atau isi kode dan nama mapel baru, jangan keduanya.',
                ]);
            }

            if (! $mapelId && ($namaBaru === '' || $kodeBaru === '')) {
                throw ValidationException::withMessages([
                    "mapel_items.{$index}.kode_baru" => 'Kode dan nama mata pelajaran wajib diisi untuk membuat mapel baru.',
                ]);
            }

            if ($jumlahJam !== null && $jumlahJam !== '' && (! is_numeric($jumlahJam) || (int) $jumlahJam < 0 || (int) $jumlahJam > 65535)) {
                throw ValidationException::withMessages([
                    "mapel_items.{$index}.jumlah_jam" => 'Jumlah jam wajib berupa angka antara 0 dan 65535.',
                ]);
            }

            if (! $mapelId) {
                $mapel = Mapel::create(['kode' => $kodeBaru, 'nama_mapel' => $namaBaru]);
                $mapelId = $mapel->id;
            }

            $syncData[$mapelId] = ['jumlah_jam' => (int) ($jumlahJam ?: 0)];
        }

        return $syncData;
    }

    private function guruResponseData(Guru $guru): array
    {
        return [
            'id' => $guru->id,
            'nama_lengkap' => $guru->nama_lengkap,
            'nbm' => $guru->nbm,
            'status' => $guru->status,
            'mapels' => $guru->mapels->pluck('nama_mapel')->values(),
            'mapel_items' => $guru->mapels->map(fn ($mapel) => [
                'mapel_id' => $mapel->id,
                'jumlah_jam' => $mapel->pivot->jumlah_jam,
            ])->values(),
            'total_jam' => $guru->mapels->sum('pivot.jumlah_jam'),
        ];
    }
}
