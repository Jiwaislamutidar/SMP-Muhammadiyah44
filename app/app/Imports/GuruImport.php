<?php

namespace App\Imports;

use App\Models\Guru;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use RuntimeException;

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
            'nbm' => $this->findColumn($headers, ['nbm', 'nip']),
            'nama_guru' => $this->findColumn($headers, ['nama_guru', 'nama_lengkap', 'nama']),
            'kode_mapel' => $this->findColumn($headers, ['kode_mapel', 'mapel_kode', 'kode']),
            'nama_mapel' => $this->findColumn($headers, ['nama_mapel', 'mata_pelajaran', 'mapel']),
            'jumlah_jam' => $this->findColumn($headers, ['jml_jam', 'jumlah_jam']),
        ];

        if ($columns['nama_guru'] === false) {
            throw new RuntimeException('Kolom Excel wajib memuat Nama Guru.');
        }

        $rowNumber = 2;
        foreach ($rows as $row) {
            $nbm = trim((string) $this->value($row, $columns['nbm']));
            $namaGuru = trim((string) $this->value($row, $columns['nama_guru']));
            $kodeMapel = trim((string) $this->value($row, $columns['kode_mapel']));
            $namaMapel = trim((string) $this->value($row, $columns['nama_mapel']));
            $jumlahJam = trim((string) $this->value($row, $columns['jumlah_jam']));

            if ($nbm === '' && $namaGuru === '' && $kodeMapel === '' && $namaMapel === '' && $jumlahJam === '') {
                $rowNumber++;
                continue;
            }

            if ($namaGuru === '') {
                $rowNumber++;
                continue;
            }

            DB::transaction(function () use ($nbm, $namaGuru, $kodeMapel, $namaMapel, $jumlahJam): void {
                $guru = $nbm !== ''
                    ? Guru::query()->where('nbm', $nbm)->first()
                    : null;

                $guru ??= Guru::query()
                    ->whereRaw('LOWER(TRIM(nama_lengkap)) = ?', [mb_strtolower($namaGuru)])
                    ->first();

                if (! $guru) {
                    $guru = new Guru(['status' => 'aktif']);
                }

                $guru->nama_lengkap = $namaGuru;
                if ($nbm !== '') {
                    $guru->nbm = $nbm;
                }
                $guru->save();

                if ($nbm !== '') {
                    $user = $guru->user ?? User::query()->where('username', $nbm)->first();
                    if ($user && $user->role !== 'guru') {
                        throw new RuntimeException("Username NBM '{$nbm}' sudah digunakan akun lain.");
                    }

                    $user ??= new User([
                        'email' => null,
                        'role' => 'guru',
                        'password' => Hash::make($nbm),
                    ]);
                    $user->name = $namaGuru;
                    $user->username = $nbm;
                    $user->role = 'guru';
                    if (blank($user->password)) {
                        $user->password = Hash::make($nbm);
                    }
                    $user->save();

                    if (! $guru->user_id) {
                        $guru->user()->associate($user);
                        $guru->save();
                    }
                }

                $mapel = $this->findOrUpdateMapel($kodeMapel, $namaMapel);
                if (! $mapel) {
                    return;
                }

                $existingHours = DB::table('guru_mapel')
                    ->where('guru_id', $guru->id)
                    ->where('mapel_id', $mapel->id)
                    ->value('jumlah_jam');
                $hours = is_numeric($jumlahJam)
                    ? min(65535, max(0, (int) $jumlahJam))
                    : (int) ($existingHours ?? 0);

                $guru->mapels()->syncWithoutDetaching([
                    $mapel->id => ['jumlah_jam' => $hours],
                ]);
            });

            $this->importedCount++;
            $rowNumber++;
        }
    }

    private function findColumn(Collection $headers, array $aliases): int|false
    {
        foreach ($aliases as $alias) {
            $position = $headers->search($alias, true);
            if ($position !== false) {
                return $position;
            }
        }

        return false;
    }

    private function value(Collection|array $row, int|false $column): mixed
    {
        return $column === false ? '' : ($row[$column] ?? '');
    }

    private function findOrUpdateMapel(string $kode, string $nama): ?Mapel
    {
        if ($kode !== '') {
            $mapel = Mapel::query()->where('kode', $kode)->first();
            if ($mapel) {
                if ($nama !== '') {
                    $mapel->nama_mapel = $nama;
                    $mapel->save();
                }

                return $mapel;
            }

            return $nama !== ''
                ? Mapel::query()->create(['kode' => $kode, 'nama_mapel' => $nama])
                : null;
        }

        return $nama === ''
            ? null
            : Mapel::query()
                ->whereRaw('LOWER(TRIM(nama_mapel)) = ?', [mb_strtolower($nama)])
                ->first();
    }
}
