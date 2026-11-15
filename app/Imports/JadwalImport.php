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
        'jam_ke' => ['jam_ke'],
        'waktu' => ['waktu'],
        'jam_mulai' => ['jam_mulai'],
        'jam_selesai' => ['jam_selesai'],
        'kode_mapel' => ['kode_mapel', 'mapel'],
        'nama_guru' => ['nama_guru', 'guru'],
        'kode_kelas' => ['kode_kelas', 'nama_kelas', 'kelas'],
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

        }

        foreach (['hari', 'kode_mapel', 'kode_kelas'] as $required) {
            if ($columns[$required] === false) {
                throw new JadwalImportException("Header wajib '".str_replace('_', ' ', $required)."' tidak ditemukan pada baris 1.");
            }
        }

        $usesPeriods = $columns['jam_ke'] !== false;
        if (! $usesPeriods && ($columns['jam_mulai'] === false || $columns['jam_selesai'] === false)) {
            throw new JadwalImportException("Header wajib 'jam_mulai' dan 'jam_selesai' tidak ditemukan pada baris 1.");
        }

        DB::transaction(function () use ($rows, $columns, $usesPeriods) {
            $rowNumber = 2;
            foreach ($rows as $row) {
                $values = collect($columns)->map(fn ($column) => $column === false
                    ? ''
                    : trim((string) ($row[$column] ?? '')));
                if ($values->every(fn ($value) => $value === '')) {
                    $rowNumber++;

                    continue;
                }

                $hari = $this->normalizeHari($values['hari'], $rowNumber);
                $kelasInput = preg_replace('/\s+/', ' ', $values['kode_kelas']);
                $kelas = Kelas::whereRaw('LOWER(TRIM(nama_kelas)) = ?', [mb_strtolower($kelasInput)])->first();
                if (! $kelas) {
                    throw new JadwalImportException("Baris {$rowNumber}: Kelas '{$values['kode_kelas']}' tidak ditemukan di database.");
                }

                $guru = null;
                if ($values['nama_guru'] !== '') {
                    $namaGuru = preg_replace('/\s+/', ' ', $values['nama_guru']);
                    $guru = Guru::whereRaw('LOWER(TRIM(nama_lengkap)) = ?', [mb_strtolower($namaGuru)])->first();
                    if (! $guru) {
                        throw new JadwalImportException("Baris {$rowNumber}: Guru '{$values['nama_guru']}' tidak ditemukan di database.");
                    }
                }

                $mapel = $this->findMapel($values['kode_mapel'], $guru?->id);
                if (! $mapel) {
                    throw new JadwalImportException("Baris {$rowNumber}: Kode/Nama Mapel '{$values['kode_mapel']}' tidak ditemukan di database.");
                }

                $guru ??= $this->findGuruForMapel($mapel, $rowNumber);
                [$jamMulai, $jamSelesai] = $this->resolveTimes(
                    $values,
                    $usesPeriods,
                    $rowNumber
                );

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
    private function findMapel(string $input, ?int $guruId): ?Mapel
    {
        // samakan variasi tanda apostrof: ’ ‘ ´ ` ′ → '
        $input = str_replace(['’', '‘', '´', '`', '′'], "'", $input);
        $input = mb_strtolower(trim($input));

        $mapel = Mapel::whereRaw('LOWER(TRIM(kode)) = ?', [$input])->first();
        if ($mapel) {
            return $mapel;
        }

        $mapels = Mapel::query()
            ->whereRaw('LOWER(TRIM(nama_mapel)) = ?', [$input]);

        if ($guruId !== null) {
            $mapels->whereHas('gurus', fn ($query) => $query->where('gurus.id', $guruId));
        }

        $matches = $mapels->limit(2)->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function findGuruForMapel(Mapel $mapel, int $rowNumber): Guru
    {
        $gurus = $mapel->gurus()->orderBy('gurus.id')->get();

        if ($gurus->isEmpty()) {
            throw new JadwalImportException("Baris {$rowNumber}: Tidak ada guru pengampu yang terhubung ke kode mapel '{$mapel->kode}'.");
        }

        if ($gurus->count() > 1) {
            throw new JadwalImportException("Baris {$rowNumber}: Kode mapel '{$mapel->kode}' memiliki lebih dari satu guru pengampu. Tambahkan kolom Nama Guru untuk menentukan pengampu.");
        }

        return $gurus->first();
    }

    private function resolveTimes(Collection $values, bool $usesPeriods, int $rowNumber): array
    {
        if (! $usesPeriods) {
            return [
                $this->formatTime($values['jam_mulai'], $rowNumber, 'Jam Mulai'),
                $this->formatTime($values['jam_selesai'], $rowNumber, 'Jam Selesai'),
            ];
        }

        $jamKe = $this->normalizePeriod($values['jam_ke'], $rowNumber);
        $waktu = trim($values['waktu']);

        if ($waktu === '') {
            $start = (new \DateTimeImmutable('07:00'))->modify('+'.(($jamKe - 1) * 40).' minutes');

            return [
                $start->format('H:i:s'),
                $start->modify('+40 minutes')->format('H:i:s'),
            ];
        }

        $parts = preg_split('/\s*(?:-|–|—)\s*/u', $waktu);
        if (count($parts) !== 2 || trim($parts[0]) === '' || trim($parts[1]) === '') {
            throw new JadwalImportException("Baris {$rowNumber}: format Waktu '{$waktu}' tidak valid. Gunakan rentang seperti 07:00-07:40.");
        }

        return [
            $this->formatTime(trim($parts[0]), $rowNumber, 'Waktu Mulai'),
            $this->formatTime(trim($parts[1]), $rowNumber, 'Waktu Selesai'),
        ];
    }

    private function normalizePeriod(string $value, int $rowNumber): int
    {
        if (! preg_match('/^(10|[1-9])(?:\.0+)?$/', trim($value))) {
            throw new JadwalImportException("Baris {$rowNumber}: Jam Ke '{$value}' harus berupa angka 1 sampai 10.");
        }

        return (int) $value;
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
