<?php

namespace App\Imports;

use App\Models\Guru;
use App\Models\Mapel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class GuruImport implements ToCollection
{
    public int $importedCount = 0;

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $headers = $rows->shift()->map(fn ($header) => Str::of((string) $header)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString());

        $columns = [
            'kode' => $headers->search('kode'),
            'nama_mapel' => $headers->search('mata_pelajaran'),
            'nama_guru' => $headers->search('nama_guru'),
            'jumlah_jam' => $headers->search('jml_jam'),
        ];

        foreach ($columns as $column) {
            if ($column === false) {
                throw new \RuntimeException('Kolom Excel harus memuat Kode, Mata Pelajaran, Nama Guru, dan Jml. Jam.');
            }
        }

        foreach ($rows as $row) {
            $kode = trim((string) ($row[$columns['kode']] ?? ''));
            $namaMapel = trim((string) ($row[$columns['nama_mapel']] ?? ''));
            $namaGuru = trim((string) ($row[$columns['nama_guru']] ?? ''));
            $jumlahJam = $row[$columns['jumlah_jam']] ?? 0;

            if ($kode === '' || $namaMapel === '' || $namaGuru === '' || ! is_numeric($jumlahJam)) {
                continue;
            }

            DB::transaction(function () use ($kode, $namaMapel, $namaGuru, $jumlahJam) {
                $guruStatus = Guru::where('nama_lengkap', $namaGuru)->value('status') ?? 'aktif';
                $guru = Guru::updateOrCreate(
                    ['nama_lengkap' => $namaGuru],
                    ['status' => $guruStatus]
                );
                $mapel = Mapel::updateOrCreate(
                    ['kode' => $kode],
                    ['nama_mapel' => $namaMapel]
                );

                $guru->mapels()->syncWithoutDetaching([
                    $mapel->id => ['jumlah_jam' => max(0, (int) $jumlahJam)],
                ]);
            });

            $this->importedCount++;
        }
    }
}
