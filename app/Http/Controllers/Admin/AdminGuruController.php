<?php

namespace App\Http\Controllers\Admin;

use App\Exports\GuruTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\GuruImport;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
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
        $gurus = $this->filteredGuruQuery($request)
            ->orderBy('nama_lengkap')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'gurus' => $gurus->map(fn (Guru $guru) => $this->guruResponseData($guru))->values(),
                'total_guru' => Guru::count(),
            ]);
        }

        return view('pages.admin.dataguru', [
            'gurus' => $gurus,
            'totalGuru' => Guru::count(),
        ]);
    }

    public function exportPdf(Request $request)
    {
        $gurus = $this->filteredGuruQuery($request)
            ->orderBy('nama_lengkap')
            ->get();
        $generatedAt = now();

        return Pdf::loadView('pages.admin.dataguru-pdf', [
            'gurus' => $gurus,
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'generatedAt' => $generatedAt,
        ])
            ->setPaper('a4', 'landscape')
            ->download('data-guru-'.$generatedAt->format('Ymd').'.pdf');
    }

    private function filteredGuruQuery(Request $request): Builder
    {
        return Guru::with('mapels')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($query) use ($search) {
                    $query->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nbm', 'like', "%{$search}%")
                        ->orWhereHas('mapels', fn ($mapels) => $mapels->where('nama_mapel', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')));
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
            'mapel_items.*.nama_mapel' => ['nullable', 'string', 'max:255'],
            'mapel_items.*.kode_mapel' => ['nullable', 'string', 'max:100'],
            'mapel_items.*.jml_jam' => ['nullable', 'integer', 'between:1,65535'],
            'mapel_items.*.nama_baru' => ['nullable', 'string', 'max:255'],
            'mapel_items.*.kode_baru' => ['nullable', 'string', 'max:100'],
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
            'reset_password' => ['nullable', 'in:0,1'],
            'mapel_items' => ['nullable', 'array'],
            'mapel_items.*' => ['array'],
            'mapel_items.*.mapel_id' => ['nullable', 'integer', 'exists:mapels,id'],
            'mapel_items.*.nama_mapel' => ['nullable', 'string', 'max:255'],
            'mapel_items.*.kode_mapel' => ['nullable', 'string', 'max:100'],
            'mapel_items.*.jml_jam' => ['nullable', 'integer', 'between:1,65535'],
            'mapel_items.*.nama_baru' => ['nullable', 'string', 'max:255'],
            'mapel_items.*.kode_baru' => ['nullable', 'string', 'max:100'],
            'mapel_items.*.jumlah_jam' => ['nullable', 'integer', 'between:1,65535'],
        ]);

        if (($validated['reset_password'] ?? '0') === '1' && ! filled($validated['nbm'] ?? null)) {
            throw ValidationException::withMessages([
                'nbm' => 'NBM wajib diisi untuk melakukan reset password default.',
            ]);
        }

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
                if (($validated['reset_password'] ?? '0') === '1') {
                    $user->password = Hash::make($validated['nbm']);
                }
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
                'guru' => $this->guruResponseData($guru),
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
            $seenKodes = [];

            foreach ($items as $index => $item) {
                $mapelId = $item['mapel_id'] ?? null;
                $namaMapel = trim((string) ($item['nama_mapel'] ?? $item['nama_baru'] ?? ''));
                $kodeMapel = trim((string) ($item['kode_mapel'] ?? $item['kode_baru'] ?? ''));
                $jumlahJam = $item['jml_jam'] ?? $item['jumlah_jam'] ?? null;

                if (! $mapelId && $namaMapel === '' && $kodeMapel === '') {
                    continue;
                }

                if ($mapelId && ($namaMapel !== '' || $kodeMapel !== '')) {
                    throw ValidationException::withMessages([
                        "mapel_items.{$index}.nama_mapel" => 'Isi kode dan nama mapel, atau gunakan referensi mapel yang sudah ada.',
                    ]);
                }

                if (! $mapelId && ($namaMapel === '' || $kodeMapel === '')) {
                    throw ValidationException::withMessages([
                        "mapel_items.{$index}.kode_mapel" => 'Kode dan nama mata pelajaran wajib diisi.',
                    ]);
                }

                // Validasi duplikasi kode mapel yang diinput dobel dalam satu form yang sama
                if (! $mapelId && $kodeMapel !== '') {
                    $kodeLower = mb_strtolower($kodeMapel);
                    if (in_array($kodeLower, $seenKodes, true)) {
                        throw ValidationException::withMessages([
                            "mapel_items.{$index}.kode_mapel" => "Kode mapel '{$kodeMapel}' diinput lebih dari satu kali dalam form ini.",
                        ]);
                    }
                    $seenKodes[] = $kodeLower;
                }

                if ($jumlahJam !== null && $jumlahJam !== '' && (! is_numeric($jumlahJam) || (int) $jumlahJam < 0 || (int) $jumlahJam > 65535)) {
                    throw ValidationException::withMessages([
                        "mapel_items.{$index}.jml_jam" => 'Jumlah jam wajib berupa angka antara 0 dan 65535.',
                    ]);
                }

                if (! $mapelId) {
                    // Gunakan updateOrCreate: jika kode mapel sudah ada (walau bekas data lama), 
                    // sistem otomatis memperbarui nama mapelnya dan memperbolehkannya dipakai kembali tanpa error!
                    $mapel = Mapel::updateOrCreate(
                        ['kode' => $kodeMapel],
                        ['nama_mapel' => $namaMapel]
                    );
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
                'nama_mapel' => $mapel->nama_mapel,
                'kode_mapel' => $mapel->kode,
                'jml_jam' => $mapel->pivot->jumlah_jam,
                'jumlah_jam' => $mapel->pivot->jumlah_jam,
            ])->values(),
            'total_jam' => $guru->mapels->sum('pivot.jumlah_jam'),
        ];
    }
}