<?php

namespace App\Providers;

use App\Models\Sekolah;
use App\Observers\SekolahObserver;
use Illuminate\Support\ServiceProvider;

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
        Sekolah::observe(SekolahObserver::class);
    }
}