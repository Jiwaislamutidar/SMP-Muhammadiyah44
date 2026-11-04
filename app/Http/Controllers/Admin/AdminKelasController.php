<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminKelasController extends Controller
{
    public function index(Request $request): View
    {
        $kelasList = Kelas::query()
            ->when($request->filled('tingkat'), fn ($query) => $query->where('tingkat', $request->input('tingkat')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->input('search');
                $query->where(function ($query) use ($search): void {
                    $query->where('nama_kelas', 'like', '%'.$search.'%')
                        ->orWhere('wali_kelas', 'like', '%'.$search.'%')
                        ->orWhere('ruangan', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('nama_kelas')
            ->get();

        return view('pages.admin.datakelas', [
            'kelasList' => $kelasList,
            'totalKelas' => $kelasList->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Kelas::create($this->validatedClass($request));

        return redirect()->route('admin.datakelas')->with('success', 'Data kelas berhasil ditambahkan.');
    }

    public function update(Request $request, Kelas $kelas): RedirectResponse
    {
        $kelas->update($this->validatedClass($request));

        return redirect()->route('admin.datakelas')->with('success', 'Data kelas berhasil diperbarui.');
    }

    private function validatedClass(Request $request): array
    {
        return $request->validate([
            'nama_kelas' => ['required', 'string', 'max:100'],
            'tingkat' => ['required', 'in:VII,VIII,IX'],
            'wali_kelas' => ['required', 'string', 'max:255'],
            'ruangan' => ['required', 'string', 'max:100'],
            'jumlah_murid' => ['required', 'integer', 'between:0,1000'],
        ]);
    }
}
