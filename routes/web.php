<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuth;
use App\Http\Controllers\Guru\AuthController as GuruAuth;
use App\Http\Controllers\Siswa\AuthController as SiswaAuth;

// Kunci domain utama di sini agar subdomain tidak meleset di Laragon
$domain = 'smpmuh44.test';

// 1. SUBDOMAIN ADMIN (admin.smpmuh44.test)
Route::domain('admin.' . $domain)->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.login');
    });

    Route::get('/login', [AdminAuth::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminAuth::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(['auth', 'role:admin', 'throttle:60,1'])->group(function () {
        Route::get('/dashboard', function () {
            return view('pages.admin.dashboardadmin');
        })->name('admin.dashboard');

        Route::get('/data-guru', fn () => view('pages.admin.dataguru'))->name('admin.dataguru');
        Route::get('/data-murid', fn () => view('pages.admin.datamurid'))->name('admin.datamurid');
        Route::get('/data-kelas', fn () => view('pages.admin.datakelas'))->name('admin.datakelas');
        Route::get('/mata-pelajaran', fn () => view('pages.admin.matapelajaran'))->name('admin.matapelajaran');
        Route::get('/jadwal-pelajaran', fn () => view('pages.admin.jadwalpelajaran'))->name('admin.jadwalpelajaran');
        Route::get('/rekap-absensi', fn () => view('pages.admin.rekapabsensi'))->name('admin.rekapabsensi');
        Route::get('/profil', fn () => view('pages.admin.profil'))->name('admin.profil');

        Route::post('/logout', [AdminAuth::class, 'logout'])->name('admin.logout');
    });
});

// 2. SUBDOMAIN GURU (guru.smpmuh44.test)
Route::domain('guru.' . $domain)->group(function () {
    Route::get('/', function () {
        return redirect()->route('guru.login');
    });

    Route::get('/login', [GuruAuth::class, 'showLogin'])->name('guru.login');
    Route::post('/login', [GuruAuth::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(['auth', 'role:guru', 'throttle:60,1'])->group(function () {
        Route::get('/dashboard', function () {
            return view('pages.guru.dashboardguru');
        })->name('guru.dashboard');

        Route::get('/jadwal-mengajar', function () {
            return view('pages.guru.jadwalngajarguru');
        })->name('guru.jadwal');

        Route::get('/presensi', function () {
            return view('pages.guru.presensiguru');
        })->name('guru.presensi-guru');

        Route::get('/presensi-murid', function () {
            return view('pages.guru.presensimurid');
        })->name('guru.presensi-murid');

        Route::get('/riwayat-presensi', function () {
            return view('pages.guru.riwayatpresensi');
        })->name('guru.riwayat-presensi');

        Route::get('/profil', function () {
            return view('pages.guru.profil');
        })->name('guru.profil');

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
        Route::get('/dashboard', function () {
            return view('pages.siswa.dashboardsiswa');
        })->name('siswa.dashboard');

        Route::get('/scan-qr', function () {
            return view('pages.siswa.scanqrsiswa');
        })->name('siswa.scan-qr');

        Route::get('/riwayat', function () {
            return view('pages.siswa.riwayatsiswa');
        })->name('siswa.riwayat');

        Route::get('/profil', function () {
            return view('pages.siswa.profilsiswa');
        })->name('siswa.profil');

        Route::post('/logout', [SiswaAuth::class, 'logout'])->name('siswa.logout');
    });
});