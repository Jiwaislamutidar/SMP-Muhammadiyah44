<?php

namespace App\Imports;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Mapel;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class JadwalImport implements ToCollection
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
        $required = ['hari', 'jam_mulai', 'jam_selesai', 'kode_mapel', 'nama_guru', 'nama_kelas', 'ruangan'];
        $columns = [];

        foreach ($required as $header) {
            $columns[$header] = $headers->search($header);
            if ($columns[$header] === false) {
                throw new \RuntimeException('Kolom template wajib: Hari, Jam Mulai, Jam Selesai, Kode Mapel, Nama Guru, Nama Kelas, Ruangan.');
            }
        }

        foreach ($rows as $index => $row) {
            $values = collect($columns)->map(fn ($column) => trim((string) ($row[$column] ?? '')));
            if ($values->every(fn ($value) => $value === '')) {
                continue;
            }

            $guru = Guru::where('nama_lengkap', $values['nama_guru'])->first();
            $kelas = Kelas::where('nama_kelas', $values['nama_kelas'])->first();
            $mapel = Mapel::where('kode', $values['kode_mapel'])->first();

            if (! $guru || ! $kelas || ! $mapel) {
                throw new \RuntimeException('Guru, kelas, atau kode mapel tidak ditemukan pada baris '.($index + 2).'.');
            }

            $hari = ucfirst(mb_strtolower($values['hari']));
            $jamMulai = $this->formatTime($values['jam_mulai']);
            $jamSelesai = $this->formatTime($values['jam_selesai']);

            if ($jamMulai >= $jamSelesai) {
                throw new \RuntimeException('Jam selesai harus setelah jam mulai pada baris '.($index + 2).'.');
            }

            JadwalPelajaran::updateOrCreate(
                [
                    'guru_id' => $guru->id,
                    'kelas_id' => $kelas->id,
                    'mapel_id' => $mapel->id,
                    'hari' => $hari,
                    'jam_mulai' => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                ],
                ['ruangan' => $values['ruangan'] ?: null]
            );
            $this->importedCount++;
        }
    }

    private function formatTime(string $value): string
    {
        if (is_numeric($value)) {
            $seconds = (int) round((((float) $value) % 1) * 86400);

            return gmdate('H:i:s', $seconds);
        }

        $date = date_create($value);
        if (! $date) {
            throw new \RuntimeException('Format jam harus HH:MM atau HH:MM:SS.');
        }

        return $date->format('H:i:s');
    }
}