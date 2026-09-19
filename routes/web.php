<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuth;
use App\Http\Controllers\Guru\AuthController as GuruAuth;
use App\Http\Controllers\Siswa\AuthController as SiswaAuth;

// 1. SUBDOMAIN ADMIN 
Route::domain('admin.' . config('app.short_url'))->group(function () {
    
    Route::middleware(['guest'])->group(function () {
        Route::get('/login', [AdminAuth::class, 'showLogin'])->name('admin.login');
        Route::post('/login', [AdminAuth::class, 'login'])->middleware('throttle:5,1');
    });

    Route::middleware(['auth'])->group(function () {
        Route::get('/dashboard', function () {
            return view('pages.admin.dashboardadmin');
        })->name('admin.dashboard');
    });
});

// 2. SUBDOMAIN GURU 
Route::domain('guru.' . config('app.short_url'))->group(function () {
    
    Route::middleware(['guest'])->group(function () {
        Route::get('/login', [GuruAuth::class, 'showLogin'])->name('guru.login');
        Route::post('/login', [GuruAuth::class, 'login'])->middleware('throttle:5,1');
    });

    // Halaman Guru 
    Route::middleware(['auth', 'throttle:60,1'])->group(function () {
        Route::get('/dashboard', function () {
            return view('pages.guru.dashboardguru');
        })->name('guru.dashboard');

        Route::get('/scan-qr', function () {
            return view('pages.guru.scanqrguru');
        })->name('guru.scan-qr');

        Route::get('/rekap', function () {
            return view('pages.guru.rekapguru');
        })->name('guru.rekap');

        Route::get('/profil', function () {
            return view('pages.guru.profilguru');
        })->name('guru.profil');

        Route::post('/logout', [GuruAuth::class, 'logout'])->name('guru.logout');
    });
});

// 3. DOMAIN SISWA
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [SiswaAuth::class, 'showLogin'])->name('login');
    Route::post('/login', [SiswaAuth::class, 'login'])->middleware('throttle:5,1');
});

// Halaman Siswa
Route::middleware(['auth', 'throttle:60,1'])->prefix('siswa')->group(function () {
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