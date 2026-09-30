<?php

namespace App\Imports;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Mapel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class JadwalImport implements ToCollection
{
    public int $importedCount = 0;

    private const HEADER_ALIASES = [
        'hari' => ['hari'],
        'jam_mulai' => ['jam_mulai'],
        'jam_selesai' => ['jam_selesai'],
        'kode_mapel' => ['kode_mapel', 'mapel'],
        'nama_guru' => ['nama_guru', 'guru'],
        'nama_kelas' => ['nama_kelas', 'kelas'],
        'ruangan' => ['ruangan'],
    ];

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        // FIX 1: CSV hasil Excel Indonesia biasanya pakai pemisah ";" bukan ",".
        // Kalau header terbaca cuma 1 kolom yang isinya ada ";", pecah manual.
        $rows = $this->fixSemicolonCsv($rows);

        $headers = $rows->shift()->map(fn ($header) => $this->normalizeHeader((string) $header));
        $columns = [];

        foreach (self::HEADER_ALIASES as $field => $aliases) {
            $columns[$field] = false;
            foreach ($aliases as $alias) {
                $position = $headers->search($alias, true);
                if ($position !== false) {
                    $columns[$field] = $position;
                    break;
                }
            }

            // 'ruangan' boleh kosong, kolomnya tetap harus ada di template
            if ($columns[$field] === false) {
                $label = str_replace('_', ' ', $field);
                throw new JadwalImportException("Header wajib '{$label}' tidak ditemukan pada baris 1.");
            }
        }

        // FIX 2: semua-atau-tidak-sama-sekali. Kalau ada 1 baris error,
        // baris sebelumnya tidak tersimpan setengah-setengah.
        DB::transaction(function () use ($rows, $columns) {
            $rowNumber = 2;
            foreach ($rows as $row) {
                $values = collect($columns)->map(fn ($column) => trim((string) ($row[$column] ?? '')));
                if ($values->every(fn ($value) => $value === '')) {
                    $rowNumber++;
                    continue;
                }

                $hari = $this->normalizeHari($values['hari'], $rowNumber);

                $namaGuru = preg_replace('/\s+/', ' ', $values['nama_guru']);
                $guru = Guru::whereRaw('LOWER(TRIM(nama_lengkap)) = ?', [mb_strtolower($namaGuru)])->first();
                if (! $guru) {
                    throw new JadwalImportException("Baris {$rowNumber}: Guru '{$values['nama_guru']}' tidak ditemukan di database.");
                }

                $kelas = Kelas::whereRaw('LOWER(TRIM(nama_kelas)) = ?', [mb_strtolower($values['nama_kelas'])])->first();
                if (! $kelas) {
                    throw new JadwalImportException("Baris {$rowNumber}: Kelas '{$values['nama_kelas']}' tidak ditemukan di database.");
                }

                $mapel = $this->findMapel($values['kode_mapel'], $guru->id);
                if (! $mapel) {
                    throw new JadwalImportException("Baris {$rowNumber}: Kode/Nama Mapel '{$values['kode_mapel']}' tidak ditemukan di database.");
                }

                $jamMulai = $this->formatTime($values['jam_mulai'], $rowNumber, 'Jam Mulai');
                $jamSelesai = $this->formatTime($values['jam_selesai'], $rowNumber, 'Jam Selesai');

                if ($jamMulai >= $jamSelesai) {
                    throw new JadwalImportException("Baris {$rowNumber}: Jam Selesai ({$jamSelesai}) harus setelah Jam Mulai ({$jamMulai}).");
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
                $rowNumber++;
            }
        });
    }

    private function fixSemicolonCsv(Collection $rows): Collection
    {
        $first = $rows->first();
        $cells = $first instanceof Collection ? $first->all() : (array) $first;
        $cells = array_values(array_filter($cells, fn ($c) => trim((string) $c) !== ''));

        if (count($cells) === 1 && str_contains((string) $cells[0], ';')) {
            return $rows->map(fn ($row) => collect(
                str_getcsv((string) (($row instanceof Collection ? $row->first() : ((array) $row)[0]) ?? ''), ';')
            ));
        }

        return $rows;
    }

    /**
     * Cari mapel berdasarkan KODE dulu (unik). Kalau tidak ketemu, baru pakai NAMA,
     * dibatasi ke mapel yang diampu guru tsb (karena nama seperti "Lifeskill",
     * "Public Speaking", "Pend. Agama Islam" dipakai banyak guru).
     */
    private function findMapel(string $input, int $guruId): ?Mapel
    {
        // samakan variasi tanda apostrof: ’ ‘ ´ ` ′ → '
        $input = str_replace(['’', '‘', '´', '`', '′'], "'", $input);
        $input = mb_strtolower(trim($input));

        $mapel = Mapel::whereRaw('LOWER(TRIM(kode)) = ?', [$input])->first();
        if ($mapel) {
            return $mapel;
        }

        $mapelIdGuru = DB::table('guru_mapel')->where('guru_id', $guruId)->pluck('mapel_id');

        return Mapel::whereIn('id', $mapelIdGuru)
            ->whereRaw('LOWER(TRIM(nama_mapel)) = ?', [$input])
            ->first();
    }

    private function normalizeHari(string $value, int $rowNumber): string
    {
        if ($value === '') {
            throw new JadwalImportException("Baris {$rowNumber}: kolom Hari wajib diisi.");
        }

        $key = preg_replace('/[^a-z]/', '', mb_strtolower($value)); // "Jum'at" → "jumat"
        $map = [
            'senin' => 'Senin', 'selasa' => 'Selasa', 'rabu' => 'Rabu',
            'kamis' => 'Kamis', 'jumat' => 'Jumat', 'sabtu' => 'Sabtu', 'minggu' => 'Minggu',
        ];

        if (! isset($map[$key])) {
            throw new JadwalImportException("Baris {$rowNumber}: Hari '{$value}' tidak valid.");
        }

        return $map[$key];
    }

    private function normalizeHeader(string $header): string
    {
        return Str::of($header)
            ->replace("\xEF\xBB\xBF", '') // buang BOM UTF-8
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    private function formatTime(string $value, int $rowNumber, string $label): string
    {
        if ($value === '') {
            throw new JadwalImportException("Baris {$rowNumber}: kolom {$label} wajib diisi.");
        }

        // HH.MM atau HH,MM (Excel bisa membuang nol: "7.1" artinya 07.10)
        if (preg_match('/^(\d{1,2})[.,](\d{1,2})$/', $value, $m)) {
            $hour = (int) $m[1];
            $minute = (int) str_pad($m[2], 2, '0'); // "1" → "10"
            if ($hour < 24 && $minute < 60) {
                return sprintf('%02d:%02d:00', $hour, $minute);
            }
        }

        // Sel waktu Excel: pecahan hari
        if (is_numeric($value) && (float) $value >= 0 && (float) $value < 1) {
            $seconds = (int) round(((float) $value) * 86400);
            $seconds = min($seconds, 86399);

            return gmdate('H:i:s', $seconds);
        }

        // HH:MM atau HH:MM:SS
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $m)) {
            $hour = (int) $m[1];
            $minute = (int) $m[2];
            $second = (int) ($m[3] ?? 0);
            if ($hour < 24 && $minute < 60 && $second < 60) {
                return sprintf('%02d:%02d:%02d', $hour, $minute, $second);
            }
        }

        throw new JadwalImportException("Baris {$rowNumber}: format {$label} '{$value}' tidak valid. Gunakan HH.MM (contoh 07.35) atau HH:MM.");
    }
}