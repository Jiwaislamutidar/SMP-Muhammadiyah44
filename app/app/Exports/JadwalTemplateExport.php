<?php

namespace App\Exports;

use App\Models\Kelas;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;

class JadwalTemplateExport implements FromArray
{
    public function array(): array
    {
        $kelasCodes = Kelas::query()
            ->orderBy('nama_kelas')
            ->pluck('nama_kelas')
            ->all();

        $uniquelyAssignedMapels = DB::table('guru_mapel')
            ->select('mapel_id')
            ->groupBy('mapel_id')
            ->havingRaw('COUNT(*) = 1');

        $mapelCodes = DB::table('mapels')
            ->whereIn('id', $uniquelyAssignedMapels)
            ->orderBy('kode')
            ->pluck('mapels.kode')
            ->all();

        $kelasCodes = $kelasCodes ?: ['7.1', '7.2'];
        $mapelCodes = $mapelCodes ?: ['1', "2'1"];
        $rows = [['hari', 'jam_ke', 'waktu', 'kode_kelas', 'kode_mapel']];
        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        for ($index = 0; $index < 10; $index++) {
            $hari = $hariList[$index % count($hariList)];
            $jamKe = intdiv($index, count($hariList)) + 1;
            $waktuMulai = (new \DateTimeImmutable('07:00'))
                ->modify('+'.(($jamKe - 1) * 40).' minutes');

            $rows[] = [
                $hari,
                $jamKe,
                $waktuMulai->format('H:i').'-'.$waktuMulai->modify('+40 minutes')->format('H:i'),
                $kelasCodes[$index % count($kelasCodes)],
                $mapelCodes[$index % count($mapelCodes)],
            ];
        }

        return $rows;
    }
}
