<?php

use App\Http\Controllers\Admin\AdminGuruController;
use App\Http\Controllers\Admin\AdminJadwalController;
use App\Http\Controllers\Admin\AuthController as AdminAuth;
use App\Http\Controllers\Admin\SiswaController as AdminSiswaController;
use App\Http\Controllers\Guru\AuthController as GuruAuth;
use App\Http\Controllers\PortalGuruController;
use App\Http\Controllers\PresensiGuruController;
use App\Http\Controllers\PresensiPelajaranController;
use App\Http\Controllers\SesiPelajaranController;
use App\Http\Controllers\Siswa\AuthController as SiswaAuth;
use App\Http\Controllers\SiswaController;
use Illuminate\Support\Facades\Route;

// Kunci domain utama di sini agar subdomain tidak meleset di Laragon
$domain = 'smpmuh44.test';

// 1. SUBDOMAIN ADMIN (admin.smpmuh44.test)
Route::domain('admin.'.$domain)->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.login');
    });

    Route::get('/login', [AdminAuth::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminAuth::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(['auth', 'role:admin', 'throttle:60,1'])->group(function () {
        Route::get('/dashboard', function () {
            return view('pages.admin.dashboardadmin');
        })->name('admin.dashboard');

        Route::get('/data-guru', [AdminGuruController::class, 'index'])->name('admin.dataguru');
        Route::get('/admin/guru', [AdminGuruController::class, 'index'])->name('admin.guru.index');
        Route::post('/admin/guru', [AdminGuruController::class, 'store'])->name('admin.guru.store');
        Route::get('/admin/guru/template', [AdminGuruController::class, 'template'])->name('admin.guru.template');
        Route::post('/admin/guru/import', [AdminGuruController::class, 'import'])->name('admin.guru.import');
        Route::put('/admin/guru/{id}', [AdminGuruController::class, 'update'])->name('admin.guru.update');
        Route::delete('/admin/guru/{id}', [AdminGuruController::class, 'destroy'])->name('admin.guru.destroy');
        Route::get('/data-murid', [AdminSiswaController::class, 'index'])->name('admin.datamurid');
        Route::post('/data-murid/import', [AdminSiswaController::class, 'import'])->name('admin.siswa.import');
        Route::get('/data-kelas', fn () => view('pages.admin.datakelas'))->name('admin.datakelas');
        Route::get('/mata-pelajaran', fn () => view('pages.admin.matapelajaran'))->name('admin.matapelajaran');
        Route::get('/jadwal-pelajaran', [AdminJadwalController::class, 'index'])->name('admin.jadwalpelajaran');
        Route::post('/jadwal-pelajaran/import', [AdminJadwalController::class, 'import'])->name('admin.jadwalpelajaran.import');
        Route::get('/jadwal-pelajaran/template', [AdminJadwalController::class, 'template'])->name('admin.jadwalpelajaran.template');
        Route::get('/rekap-absensi', fn () => view('pages.admin.rekapabsensi'))->name('admin.rekapabsensi');
        Route::get('/profil', fn () => view('pages.admin.profil'))->name('admin.profil');

        Route::post('/logout', [AdminAuth::class, 'logout'])->name('admin.logout');
    });
});

// 2. SUBDOMAIN GURU (guru.smpmuh44.test)
Route::domain('guru.'.$domain)->group(function () {
    Route::get('/', function () {
        return redirect()->route('guru.login');
    });

    Route::get('/login', [GuruAuth::class, 'showLogin'])->name('guru.login');
    Route::post('/login', [GuruAuth::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(['auth', 'role:guru', 'throttle:60,1'])->group(function () {
        Route::get('/dashboard', [PortalGuruController::class, 'dashboard'])->name('guru.dashboard');
        Route::get('/jadwal-mengajar', [PortalGuruController::class, 'jadwal'])->name('guru.jadwal');
        Route::get('/presensi', [PresensiGuruController::class, 'index'])->name('guru.presensi-guru');
        Route::post('/presensi/masuk', [PresensiGuruController::class, 'masuk'])->name('guru.presensi.masuk');
        Route::post('/presensi/pulang', [PresensiGuruController::class, 'pulang'])->name('guru.presensi.pulang');
        Route::get('/presensi-murid', [SesiPelajaranController::class, 'index'])->name('guru.presensi-murid');
        Route::post('/sesi/{jadwal}/buka', [SesiPelajaranController::class, 'open'])->name('guru.sesi.open');
        Route::post('/sesi/{sesi}/qr', [SesiPelajaranController::class, 'regenerate'])->name('guru.sesi.qr');
        Route::post('/sesi/{sesi}/tutup', [SesiPelajaranController::class, 'close'])->name('guru.sesi.close');
        Route::get('/sesi/{sesi}/status', [SesiPelajaranController::class, 'status'])->name('guru.sesi.status');
        Route::put('/presensi-murid/{presensi}', [PresensiPelajaranController::class, 'updateManual'])->name('guru.presensi-murid.update');

        Route::get('/riwayat-presensi', [PortalGuruController::class, 'riwayat'])->name('guru.riwayat-presensi');
        Route::get('/profil', [PortalGuruController::class, 'profil'])->name('guru.profil');

        Route::post('/logout', [GuruAuth::class, 'logout'])->name('guru.logout');
    });
});

// 3. DOMAIN UTAMA / SISWA (smpmuh44.test)
Route::domain($domain)->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });

    Route::get('/login', [SiswaAuth::class, 'showLogin'])->name('login');
    Route::post('/login', [SiswaAuth::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(['auth', 'role:siswa', 'throttle:60,1'])->prefix('siswa')->group(function () {
        Route::get('/dashboard', [SiswaController::class, 'dashboard'])->name('siswa.dashboard');
        Route::get('/scan-qr', [SiswaController::class, 'scanQr'])->name('siswa.scan-qr');
        Route::post('/scan-qr', [PresensiPelajaranController::class, 'scan'])->name('siswa.scan');
        Route::get('/riwayat', [SiswaController::class, 'riwayat'])->name('siswa.riwayat');
        Route::get('/profil', [SiswaController::class, 'profil'])->name('siswa.profil');
        Route::put('/profil/ubah-password', [SiswaController::class, 'updatePassword'])->name('siswa.profil.update-password');

        Route::post('/logout', [SiswaAuth::class, 'logout'])->name('siswa.logout');
    });
});
