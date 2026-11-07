<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mapel;
use Illuminate\View\View;

class AdminMapelController extends Controller
{
    public function index(): View
    {
        $mapels = Mapel::query()
            ->whereHas('gurus')
            ->whereNotNull('nama_mapel')
            ->whereRaw("TRIM(nama_mapel) <> ''")
            ->whereNotNull('kode')
            ->whereRaw("TRIM(kode) <> ''")
            ->with(['gurus' => fn ($query) => $query->orderBy('nama_lengkap')])
            ->orderBy('nama_mapel')
            ->orderBy('id')
            ->get();

        return view('pages.admin.matapelajaran', compact('mapels'));
    }
}
