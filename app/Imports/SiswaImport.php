<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use RuntimeException;

class SiswaImport implements ToCollection
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
            'nisn' => $headers->search('nisn', true),
            'nama_lengkap' => $headers->search('nama_lengkap', true),
            'kode_kelas' => $headers->search('kode_kelas', true),
        ];

        if (in_array(false, $columns, true)) {
            throw new RuntimeException('Template siswa wajib memuat kolom nisn, nama_lengkap, dan kode_kelas.');
        }

        foreach ($rows as $row) {
            $nisn = trim((string) ($row[$columns['nisn']] ?? ''));
            $nama = trim((string) ($row[$columns['nama_lengkap']] ?? ''));
            $kodeKelas = trim((string) ($row[$columns['kode_kelas']] ?? ''));

            if ($nisn === '' || $nama === '') {
                continue;
            }

            if (User::query()->where('username', $nisn)->exists()
                || Siswa::query()->where('nisn', $nisn)->exists()) {
                continue;
            }

            DB::transaction(function () use ($nisn, $nama, $kodeKelas): void {
                $kelas = $kodeKelas === ''
                    ? null
                    : Kelas::query()->firstOrCreate(['nama_kelas' => $kodeKelas]);
                $user = User::create([
                    'name' => $nama,
                    'username' => $nisn,
                    'email' => null,
                    'role' => 'siswa',
                    'password' => Hash::make($nisn),
                ]);

                Siswa::create([
                    'user_id' => $user->id,
                    'nisn' => $nisn,
                    'nama_lengkap' => $nama,
                    'kelas_id' => $kelas?->id,
                    'status' => 'aktif',
                ]);
            });

            $this->importedCount++;
        }
    }
}
