<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
        // MySQL / MariaDB index compatibility
        Schema::defaultStringLength(191);

        // Force HTTPS in production or when APP_URL starts with https
        if (app()->environment('production') || str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
