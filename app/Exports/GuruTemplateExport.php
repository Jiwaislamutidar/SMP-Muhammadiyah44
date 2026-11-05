<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class GuruTemplateExport implements FromArray
{
    public function array(): array
    {
        return [
            ['nbm', 'nama_lengkap', 'kode_mapel', 'nama_mapel', 'jml_jam'],
            ['1104698', 'Maryadi Saputra, S.Pd', '2', 'IPS Terpadu', '8'],
        ];
    }
}
