<?php

use App\Http\Controllers\Admin\AdminGuruController;
use App\Http\Controllers\Admin\AdminJadwalController;
use App\Http\Controllers\Admin\AdminKelasController;
use App\Http\Controllers\Admin\AttendanceReportController;
use App\Http\Controllers\Admin\AuthController as AdminAuth;
use App\Http\Controllers\Admin\DashboardAttendanceLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
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
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        Route::get('/data-guru', [AdminGuruController::class, 'index'])->name('admin.dataguru');
        Route::get('/admin/guru', [AdminGuruController::class, 'index'])->name('admin.guru.index');
        Route::post('/admin/guru', [AdminGuruController::class, 'store'])->name('admin.guru.store');
        Route::get('/admin/guru/template', [AdminGuruController::class, 'template'])->name('admin.guru.template');
        Route::post('/admin/guru/import', [AdminGuruController::class, 'import'])->name('admin.guru.import');
        Route::put('/admin/guru/{id}', [AdminGuruController::class, 'update'])->name('admin.guru.update');
        Route::delete('/admin/guru/{id}', [AdminGuruController::class, 'destroy'])->name('admin.guru.destroy');
        Route::get('/data-murid', [AdminSiswaController::class, 'index'])->name('admin.datamurid');
        Route::get('/data-murid/template', [AdminSiswaController::class, 'template'])->name('admin.siswa.template');
        Route::post('/data-murid/import', [AdminSiswaController::class, 'import'])->name('admin.siswa.import');
        Route::post('/data-murid/import-manual', [AdminSiswaController::class, 'manualImport'])->name('admin.siswa.manual-import');
        Route::put('/data-murid/{siswa}', [AdminSiswaController::class, 'update'])->name('admin.siswa.update');
        Route::delete('/data-murid/{siswa}', [AdminSiswaController::class, 'destroy'])->name('admin.siswa.destroy');
        Route::get('/data-kelas', [AdminKelasController::class, 'index'])->name('admin.datakelas');
        Route::post('/data-kelas', [AdminKelasController::class, 'store'])->name('admin.kelas.store');
        Route::put('/data-kelas/{kelas}', [AdminKelasController::class, 'update'])->name('admin.kelas.update');
        Route::delete('/data-kelas/{kelas}', [AdminKelasController::class, 'destroy'])->name('admin.kelas.destroy');
        Route::get('/mata-pelajaran', fn () => view('pages.admin.matapelajaran'))->name('admin.matapelajaran');
        Route::get('/jadwal-pelajaran', [AdminJadwalController::class, 'index'])->name('admin.jadwalpelajaran');
        Route::post('/jadwal-pelajaran/import', [AdminJadwalController::class, 'import'])->name('admin.jadwalpelajaran.import');
        Route::get('/jadwal-pelajaran/template', [AdminJadwalController::class, 'template'])->name('admin.jadwalpelajaran.template');
        Route::delete('/jadwal-pelajaran', [AdminJadwalController::class, 'destroyAll'])->name('admin.jadwalpelajaran.destroy-all');
        Route::delete('/jadwal-pelajaran/{jadwal}', [AdminJadwalController::class, 'destroy'])->name('admin.jadwalpelajaran.destroy');
        Route::get('/rekap-absensi', [AttendanceReportController::class, 'index'])->name('admin.rekapabsensi');
        Route::get('/rekap-absensi/data', [AttendanceReportController::class, 'studentData'])->name('admin.rekapabsensi.data');
        Route::get('/rekap-absensi/export', [AttendanceReportController::class, 'export'])->name('admin.rekapabsensi.export');
        Route::delete('/rekap-absensi', [AttendanceReportController::class, 'destroy'])->name('admin.rekapabsensi.destroy');
        Route::get('/dashboard/presensi-guru', [AttendanceReportController::class, 'guruPeriod'])->name('admin.dashboard.guru-period');
        Route::delete('/dashboard/presensi-guru-session/{sessionLog}', [DashboardAttendanceLogController::class, 'destroySession'])->name('admin.dashboard.guru-session-log.destroy');
        Route::delete('/dashboard/presensi-guru/{log}', [DashboardAttendanceLogController::class, 'destroy'])->name('admin.dashboard.guru-log.destroy');
        Route::delete('/dashboard/presensi-guru', [DashboardAttendanceLogController::class, 'destroyPeriod'])->name('admin.dashboard.guru-log.destroy-period');
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
        Route::delete('/presensi/riwayat', [PresensiGuruController::class, 'resetHistory'])->name('guru.presensi-riwayat.reset');
        Route::post('/presensi/masuk', [PresensiGuruController::class, 'masuk'])->middleware('throttle:5,1')->name('guru.presensi.masuk');
        Route::post('/presensi/izin', [PresensiGuruController::class, 'izin'])->middleware('throttle:5,1')->name('guru.presensi.izin');
        Route::post('/presensi/pulang', [PresensiGuruController::class, 'pulang'])->middleware('throttle:5,1')->name('guru.presensi.pulang');
        Route::get('/presensi-murid', [SesiPelajaranController::class, 'index'])->name('guru.presensi-murid');
        Route::post('/sesi/{jadwal}/buka', [SesiPelajaranController::class, 'open'])->name('guru.sesi.open');
        Route::post('/sesi/{sesi}/qr', [SesiPelajaranController::class, 'regenerate'])->name('guru.sesi.qr');
        Route::post('/sesi/{sesi}/tutup', [SesiPelajaranController::class, 'close'])->name('guru.sesi.close');
        Route::delete('/sesi/{sesi}', [SesiPelajaranController::class, 'destroy'])->name('guru.sesi.destroy');
        Route::get('/sesi/{sesi}/pdf', [SesiPelajaranController::class, 'exportPdf'])->name('guru.sesi.pdf');
        Route::get('/sesi/{sesi}/status', [SesiPelajaranController::class, 'status'])->name('guru.sesi.status');
        Route::put('/presensi-murid/{presensi}', [PresensiPelajaranController::class, 'updateManual'])->name('guru.presensi-murid.update');

        Route::get('/riwayat-presensi', [PortalGuruController::class, 'riwayat'])->name('guru.riwayat-presensi');
        Route::get('/profil', [PortalGuruController::class, 'profil'])->name('guru.profil');
        Route::put('/profil/ubah-password', [PortalGuruController::class, 'updatePassword'])->name('guru.profil.update-password');

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
        Route::post('/scan-qr', [PresensiPelajaranController::class, 'scan'])->middleware('throttle:5,1')->name('siswa.scan');
        Route::get('/riwayat', [SiswaController::class, 'riwayat'])->name('siswa.riwayat');
        Route::get('/riwayat/unduh-pdf', [SiswaController::class, 'exportPdf'])->name('siswa.riwayat.export');
        Route::post('/riwayat/hapus', [SiswaController::class, 'deleteHistory'])->name('siswa.riwayat.destroy');
        Route::get('/profil', [SiswaController::class, 'profil'])->name('siswa.profil');
        Route::put('/profil/ubah-password', [SiswaController::class, 'updatePassword'])->name('siswa.profil.update-password');

        Route::post('/logout', [SiswaAuth::class, 'logout'])->name('siswa.logout');
    });
});
