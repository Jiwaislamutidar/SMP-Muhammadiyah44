<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class SiswaTemplateExport implements FromArray
{
    public function array(): array
    {
        return [
            ['nisn', 'nama_lengkap', 'kode_kelas'],
            ['0012345678', 'Alya Putri', '7.1'],
        ];
    }
}
