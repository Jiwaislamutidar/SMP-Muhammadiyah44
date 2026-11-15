<?php

namespace App\Providers;

use App\Models\Guru;
use App\Services\GuruNotificationService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('pages.guru.*', function (ViewInstance $view): void {
            $guru = $view->getData()['guru'] ?? null;

            if ($guru instanceof Guru) {
                $view->with('notifikasiGuru', app(GuruNotificationService::class)->forGuru($guru));
            }
        });
    }
}
