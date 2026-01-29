<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cookie;

/**
 * Service Provider per configurazioni di sicurezza avanzate.
 * 
 * Gestisce:
 * - Configurazione sicura dei cookie
 * - Headers di sicurezza aggiuntivi
 * - Protezioni varie
 */
class SecurityServiceProvider extends ServiceProvider
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
        // Configura i cookie di default con impostazioni sicure
        $this->configureSecureCookies();
    }

    /**
     * Configura le impostazioni di sicurezza per i cookie.
     */
    private function configureSecureCookies(): void
    {
        // In produzione o quando FORCE_HTTPS è attivo, usa cookie sicuri
        $isSecure = app()->environment('production') 
            || filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN);

        // Imposta i valori di default per i cookie
        Cookie::setDefaultPathAndDomain(
            config('session.path', '/'),
            config('session.domain'),
            $isSecure,
            config('session.same_site', 'lax')
        );
    }
}
