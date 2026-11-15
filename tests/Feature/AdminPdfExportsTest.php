<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPdfExportsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_download_pdf_exports_for_all_core_admin_modules(): void
    {
        $admin = User::create([
            'name' => 'Admin PDF Test',
            'username' => 'admin-pdf-'.bin2hex(random_bytes(5)),
            'email' => null,
            'role' => 'admin',
            'password' => Hash::make('test-password'),
        ]);
        $this->actingAs($admin);

        foreach ([
            ['admin.dataguru', 'Unduh Data Guru'],
            ['admin.datamurid', 'Unduh Data Murid'],
            ['admin.datakelas', 'Unduh Data Kelas'],
            ['admin.jadwalpelajaran', 'Unduh Jadwal PDF'],
            ['admin.rekapabsensi', 'Unduh Rekap PDF'],
        ] as [$pageRoute, $buttonLabel]) {
            $this->get(route($pageRoute))
                ->assertOk()
                ->assertSee('data-pdf-export', false)
                ->assertSee($buttonLabel);
        }

        $exports = [
            ['admin.guru.export-pdf', [], 'application/pdf'],
            ['admin.siswa.export-pdf', [], 'application/pdf'],
            ['admin.kelas.export-pdf', [], 'application/pdf'],
            ['admin.jadwalpelajaran.export-pdf', [], 'application/pdf'],
            [
                'admin.rekapabsensi.export',
                ['date_from' => today()->subDay()->toDateString(), 'date_to' => today()->toDateString()],
                'application/pdf',
            ],
        ];

        foreach ($exports as [$route, $filters, $contentType]) {
            $response = $this->get(route($route, $filters));

            $response->assertOk()->assertHeader('content-type', $contentType);
            $this->assertStringStartsWith('%PDF-', $response->getContent(), $route.' must return a PDF document.');
        }
    }
}
