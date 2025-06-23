<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Configura l'URL base per XAMPP quando si accede tramite public/
        if (!app()->runningInConsole() && request()->server('REQUEST_URI') && strpos(request()->server('REQUEST_URI'), '/C2MS/public/') !== false) {
            URL::forceRootUrl('http://localhost/C2MS/public');
        }
    }
}
