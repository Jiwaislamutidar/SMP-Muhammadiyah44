<?php

namespace Database\Seeders;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Mapel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class JadwalJumatSeeder extends Seeder
{
    public function run(): void
    {
        $kelases = Kelas::query()->orderBy('nama_kelas')->get();
        if ($kelases->isEmpty()) {
            throw new RuntimeException('Tidak ada data kelas untuk dibuatkan jadwal Jumat.');
        }

        $publicSpeaking = $this->mapelGuruOptions(['public speaking']);
        $literasiNumerasi = $this->mapelGuruOptions(['literasi & numerasi', 'literasi dan numerasi']);
        if (empty($literasiNumerasi)) {
            $literasiNumerasi = $this->mapelGuruOptions(['lifeskill']);
        }

        if (empty($publicSpeaking)) {
            throw new RuntimeException('Mapel Public Speaking atau relasi guru pengampunya tidak ditemukan.');
        }
        if (empty($literasiNumerasi)) {
            throw new RuntimeException('Mapel Literasi & Numerasi/Lifeskill atau relasi guru pengampunya tidak ditemukan.');
        }

        $slots = [
            ['09:00:00', '09:40:00', $publicSpeaking],
            ['09:40:00', '10:20:00', $publicSpeaking],
            ['10:20:00', '11:00:00', $literasiNumerasi],
            ['11:00:00', '11:40:00', $literasiNumerasi],
        ];

        DB::transaction(function () use ($kelases, $slots): void {
            foreach ($kelases as $index => $kelas) {
                foreach ($slots as [$jamMulai, $jamSelesai, $options]) {
                    $pengampu = $options[$index % count($options)];

                    JadwalPelajaran::updateOrCreate(
                        [
                            'kelas_id' => $kelas->id,
                            'hari' => 'Jumat',
                            'jam_mulai' => $jamMulai,
                        ],
                        [
                            'guru_id' => $pengampu['guru_id'],
                            'mapel_id' => $pengampu['mapel_id'],
                            'jam_selesai' => $jamSelesai,
                        ]
                    );
                }
            }
        });
    }

    /**
     * @param  list<string>  $namaMapel
     * @return list<array{mapel_id: int, guru_id: int}>
     */
    private function mapelGuruOptions(array $namaMapel): array
    {
        return Mapel::query()
            ->whereIn(DB::raw('LOWER(TRIM(nama_mapel))'), $namaMapel)
            ->with(['gurus' => fn ($query) => $query->orderBy('gurus.id')])
            ->orderBy('id')
            ->get()
            ->flatMap(fn (Mapel $mapel) => $mapel->gurus->map(fn ($guru) => [
                'mapel_id' => $mapel->id,
                'guru_id' => $guru->id,
            ]))
            ->values()
            ->all();
    }
}
