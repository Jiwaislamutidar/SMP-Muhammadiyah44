<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Siswa;
use App\Models\Kelas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class SiswaImport implements ToCollection
{
    public int $importedCount = 0;

    public function collection(Collection $rows)
    {
        $currentKelasId = null;

        foreach ($rows as $row) {
            // 1. Cari penanda "Kelas : X.X" di seluruh sel baris ini
            $rowString = implode(' ', array_filter($row->toArray()));

            if (preg_match('/Kelas\s*:\s*([789]\.[123])/i', $rowString, $matches)) {
                $namaKelas = trim($matches[1]);
                $kelas = Kelas::firstOrCreate(['nama_kelas' => $namaKelas]);
                $currentKelasId = $kelas->id;
                continue;
            }

            // 2. Ambil Kolom 0 (NO), Kolom 1 (NIS), Kolom 2 (NAMA)
            $no   = trim((string) ($row[0] ?? ''));
            $nis  = trim((string) ($row[1] ?? ''));
            $nama = trim((string) ($row[2] ?? ''));

            // 3. Validasi: Baris siswa valid jika NO & NIS berupa angka dan panjang NIS >= 6 digit
            if (is_numeric($no) && is_numeric($nis) && strlen($nis) >= 6 && !empty($nama)) {
                if (!$currentKelasId) {
                    continue; // Lewati jika belum ketemu penanda kelas
                }

                // Lewati jika NIS/Username sudah ada di database (mencegah duplikat)
                if (User::where('username', $nis)->exists()) {
                    continue;
                }

                DB::transaction(function () use ($nis, $nama, $currentKelasId) {
                    // Buat Akun Login User
                    $user = User::create([
                        'name'     => $nama,
                        'username' => $nis,
                        'email'    => null,
                        'role'     => 'siswa',
                        'password' => Hash::make($nis),
                    ]);

                    // Buat Profil Siswa
                    Siswa::create([
                        'user_id'      => $user->id,
                        'nisn'         => $nis,
                        'nama_lengkap' => $nama,
                        'kelas_id'     => $currentKelasId,
                        'status'       => 'aktif',
                    ]);
                });

                $this->importedCount++;
            }
        }
    }
}