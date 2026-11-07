<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\SiswaTemplateExport;
use App\Imports\SiswaImport;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredStudents($request);

        return view('pages.admin.datamurid', [
            'siswas' => $query->orderBy('nama_lengkap')->paginate(8)->withQueryString(),
            'kelasList' => Kelas::orderBy('nama_kelas')->get(),
            'totalSiswa' => Siswa::count(),
        ]);
    }

    public function exportPdf(Request $request)
    {
        $siswas = $this->filteredStudents($request)
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap')
            ->get();
        $kelasName = $request->filled('kelas_id')
            ? Kelas::query()->whereKey($request->input('kelas_id'))->value('nama_kelas')
            : null;

        return Pdf::loadView('pages.admin.datamurid-pdf', [
            'siswas' => $siswas,
            'search' => $request->input('search'),
            'kelasName' => $kelasName,
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->download('data-murid-'.now()->format('Ymd').'.pdf');
    }

    private function filteredStudents(Request $request): Builder
    {
        return Siswa::with(['user', 'kelas'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');

                $query->where(function ($query) use ($search) {
                    $query->where('nisn', 'like', "%{$search}%")
                        ->orWhere('nama_lengkap', 'like', "%{$search}%")
                        ->orWhereHas('kelas', fn ($kelas) => $kelas->where('nama_kelas', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('kelas_id'), fn ($query) => $query->where('kelas_id', $request->input('kelas_id')));
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

    public function template()
    {
        return Excel::download(new SiswaTemplateExport, 'template-data-siswa.xlsx');
    }

    public function manualImport(Request $request)
    {
        $students = collect($request->input('students', []))
            ->map(fn ($student) => is_array($student)
                ? array_map(fn ($value) => is_string($value) ? trim($value) : $value, $student)
                : $student)
            ->all();
        $request->merge(['students' => $students]);

        $validated = $request->validate([
            'students' => ['required', 'array', 'min:1', 'max:50'],
            'students.*.nama_lengkap' => ['required', 'string', 'max:255'],
            'students.*.nisn' => ['required', 'string', 'max:255', 'distinct', 'unique:siswas,nisn', 'unique:users,username'],
            'students.*.kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
        ], [
            'students.required' => 'Tambahkan setidaknya satu data siswa.',
            'students.max' => 'Maksimal 50 siswa dapat ditambahkan sekaligus.',
            'students.*.nama_lengkap.required' => 'Nama siswa wajib diisi.',
            'students.*.nisn.required' => 'NIS/NISN wajib diisi karena digunakan sebagai username.',
            'students.*.nisn.distinct' => 'NIS/NISN ini dimasukkan lebih dari satu kali.',
            'students.*.nisn.unique' => 'NIS/NISN ini sudah terdaftar.',
            'students.*.kelas_id.exists' => 'Kelas yang dipilih tidak tersedia.',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['students'] as $student) {
                $nisn = trim($student['nisn']);
                $nama = trim($student['nama_lengkap']);
                $user = User::create([
                    'name' => $nama,
                    'username' => $nisn,
                    'email' => null,
                    'role' => 'siswa',
                    'password' => Hash::make($nisn),
                ]);

                Siswa::create([
                    'user_id' => $user->id,
                    'nisn' => $nisn,
                    'nama_lengkap' => $nama,
                    'kelas_id' => $student['kelas_id'] ?? null,
                    'status' => 'aktif',
                ]);
            }
        });

        $count = count($validated['students']);

        return back()->with('success', "Berhasil menambahkan {$count} data siswa. Username dan password awal setiap akun menggunakan NIS/NISN.");
    }

    public function update(Request $request, Siswa $siswa): RedirectResponse
    {
        $validated = $request->validate([
            'nisn' => ['required', 'string', 'max:255', Rule::unique('siswas', 'nisn')->ignore($siswa->id), Rule::unique('users', 'username')->ignore($siswa->user_id)],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'kode_kelas' => ['nullable', 'string', 'max:255', 'exists:kelas,nama_kelas'],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
            'edit_siswa_id' => ['required', 'integer', Rule::in([$siswa->id])],
            'form_context' => ['required', Rule::in(['edit'])],
        ], [
            'nisn.unique' => 'NIS/NISN tersebut sudah digunakan.',
            'kode_kelas.exists' => 'Kelas yang dipilih tidak tersedia.',
            'password.min' => 'Password harus terdiri dari minimal 6 karakter.',
        ]);

        DB::transaction(function () use ($siswa, $validated): void {
            $student = Siswa::query()->lockForUpdate()->findOrFail($siswa->id);
            $user = User::query()->lockForUpdate()->findOrFail($student->user_id);

            if ($user->role !== 'siswa') {
                abort(409, 'Akun yang terhubung bukan akun murid.');
            }

            $student->update([
                'nisn' => $validated['nisn'],
                'nama_lengkap' => $validated['nama_lengkap'],
                'kelas_id' => filled($validated['kode_kelas'] ?? null)
                    ? Kelas::query()->where('nama_kelas', $validated['kode_kelas'])->value('id')
                    : null,
            ]);

            $user->name = $validated['nama_lengkap'];
            $user->username = $validated['nisn'];
            if (filled($validated['password'] ?? null)) {
                $user->password = Hash::make($validated['password']);
            }
            $user->save();
        });

        return redirect()->route('admin.datamurid')->with('success', 'Data murid berhasil diperbarui.');
    }

    public function destroy(Siswa $siswa): RedirectResponse
    {
        DB::transaction(function () use ($siswa): void {
            $student = Siswa::query()->lockForUpdate()->findOrFail($siswa->id);
            $user = User::query()->lockForUpdate()->findOrFail($student->user_id);

            if ($user->role !== 'siswa') {
                abort(409, 'Akun yang terhubung bukan akun murid.');
            }

            $student->delete();
            $user->delete();
        });

        return redirect()->route('admin.datamurid')->with('success', 'Data murid dan akun terkait berhasil dihapus.');
    }
}