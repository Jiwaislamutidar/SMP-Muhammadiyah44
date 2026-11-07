<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mapel;
use Illuminate\View\View;

class AdminMapelController extends Controller
{
    public function index(): View
    {
        // 1. Ambil HANYA mapel yang benar-benar ada relasinya dengan guru di database
        $mapels = Mapel::query()
            ->has('gurus') // Hanya mapel yang punya guru pengampu
            ->with(['gurus' => fn ($query) => $query->orderBy('nama_lengkap')])
            ->orderBy('nama_mapel')
            ->get();

        // 2. Lakukan grouping berdasarkan nama mapel agar unik (1 mapel = 1 card)
        $mapels = $mapels->groupBy(fn ($item) => strtolower(trim($item->nama_mapel)))->map(function ($group) {
            $first = $group->first();
            
            // Gabungkan guru-guru pengampu yang unik untuk mapel tersebut
            $allGurus = $group->flatMap->gurus->unique('id')->sortBy('nama_lengkap')->values();
            $first->setRelation('gurus', $allGurus);

            return $first;
        })->values();

        return view('pages.admin.matapelajaran', compact('mapels'));
    }
}