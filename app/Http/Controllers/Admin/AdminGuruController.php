<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\GuruImport;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
                        ->orWhere('nip', 'like', "%{$search}%")
                        ->orWhereHas('mapels', fn ($mapels) => $mapels->where('nama_mapel', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')));

        return view('pages.admin.dataguru', [
            'gurus' => $query->orderBy('nama_lengkap')->get(),
            'mapels' => Mapel::orderBy('nama_mapel')->get(),
            'totalGuru' => Guru::count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255', 'unique:gurus,nama_lengkap'],
            'nip' => ['required', 'string', 'max:100', 'unique:gurus,nip', 'unique:users,username'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'mapel_items' => ['nullable', 'array'],
            'mapel_items.*' => ['array'],
            'mapel_items.*.mapel_id' => ['nullable', 'integer', 'exists:mapels,id'],
            'mapel_items.*.nama_baru' => ['nullable', 'string', 'max:255'],
            'mapel_items.*.jumlah_jam' => ['nullable', 'integer', 'between:1,65535'],
        ]);

        DB::transaction(function () use ($validated) {
            $syncData = $this->mapelSyncData($validated['mapel_items'] ?? []);
            $user = User::create([
                'name' => $validated['nama_lengkap'],
                'username' => $validated['nip'],
                'email' => null,
                'role' => 'guru',
                'password' => Hash::make($validated['nip']),
            ]);
            $guru = Guru::create([
                'user_id' => $user->id,
                'nama_lengkap' => $validated['nama_lengkap'],
                'nip' => $validated['nip'],
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
        $guru = Guru::with('user')->findOrFail($id);
        $linkedUserId = $guru->user_id ?? User::query()
            ->where('role', 'guru')
            ->where('username', $guru->nip)
            ->value('id');
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255', 'unique:gurus,nama_lengkap,'.$guru->id],
            'nip' => [
                $linkedUserId ? 'required' : 'nullable',
                'string',
                'max:100',
                'unique:gurus,nip,'.$guru->id,
                Rule::unique('users', 'username')->ignore($linkedUserId),
            ],
            'status' => ['required', 'in:aktif,nonaktif'],
            'mapel_items' => ['nullable', 'array'],
            'mapel_items.*' => ['array'],
            'mapel_items.*.mapel_id' => ['nullable', 'integer', 'exists:mapels,id'],
            'mapel_items.*.nama_baru' => ['nullable', 'string', 'max:255'],
            'mapel_items.*.jumlah_jam' => ['nullable', 'integer', 'between:1,65535'],
        ]);

        DB::transaction(function () use ($guru, $linkedUserId, $validated) {
            $user = $guru->user ?? ($linkedUserId ? User::findOrFail($linkedUserId) : null);
            if ($user && $user->role !== 'guru') {
                abort(409, 'Akun yang terhubung bukan akun guru.');
            }

            if (! $user && filled($validated['nip'] ?? null)) {
                $user = new User([
                    'email' => null,
                    'role' => 'guru',
                    'password' => Hash::make($validated['nip']),
                ]);
            }

            if ($user) {
                $user->name = $validated['nama_lengkap'];
                $user->username = $validated['nip'];
                $user->save();
            }

            $guru->update([
                'nama_lengkap' => $validated['nama_lengkap'],
                'nip' => $validated['nip'] ?? null,
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
                    'nip' => $guru->nip,
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
                    ->where('username', $guru->nip)
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
