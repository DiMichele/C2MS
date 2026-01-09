<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use App\Models\Militare;
use App\Models\CompagniaSetting;
use App\Models\ConfigurazioneRuolino;
use App\Policies\MilitarePolicy;
use App\Policies\CompagniaSettingPolicy;
use App\Services\CompagniaSettingsService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registra il service delle impostazioni compagnia
        // NON è un singleton: il contesto (compagnia) può cambiare
        // Usa i factory methods: CompagniaSettingsService::forCurrentUser(), forCompagnia(), forUser()
        $this->app->bind(CompagniaSettingsService::class, function ($app) {
            return CompagniaSettingsService::forCurrentUser();
        });
        
        // Registra un callback per modificare l'URL generator dopo che è stato creato
        $this->app->resolving('url', function ($url, $app) {
            // Ottieni l'host dalla richiesta se disponibile
            if ($app->has('request')) {
                $request = $app->make('request');
                $host = $request->header('Host') ?? $request->getHost();
                
                // Se è un tunnel cloudflare o ngrok, forza l'URL
                if (str_contains($host ?? '', 'trycloudflare') || str_contains($host ?? '', 'ngrok')) {
                    $tunnelUrl = 'https://' . $host . '/SUGECO/public';
                    $url->forceRootUrl($tunnelUrl);
                    $url->forceScheme('https');
                }
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registra Observers
        \App\Models\TipoServizio::observe(\App\Observers\TipoServizioObserver::class);
        
        // Registra Policy per Militare (gestione owner/acquired)
        Gate::policy(Militare::class, MilitarePolicy::class);
        
        // Registra Policy per impostazioni compagnia (ruolini)
        Gate::policy(CompagniaSetting::class, CompagniaSettingPolicy::class);
        Gate::policy(ConfigurazioneRuolino::class, CompagniaSettingPolicy::class);
        
        // Registra Gate per i permessi personalizzati
        // Questo permette a @can di usare il nostro sistema di permessi
        Gate::before(function ($user, $ability) {
            // L'admin e l'amministratore hanno SEMPRE tutti i permessi
            if ($user->hasRole('admin') || $user->hasRole('amministratore')) {
                return true;
            }
            
            // Altrimenti usa il sistema di permessi standard
            return $user->hasPermission($ability) ?: null;
        });
        
        // Forza HTTPS quando si usa un tunnel (ngrok/cloudflare)
        if (request()->header('X-Forwarded-Proto') === 'https' || 
            request()->header('X-Forwarded-Host') ||
            request()->server('HTTP_X_FORWARDED_PROTO') === 'https') {
            URL::forceScheme('https');
        }
        
        // Configura l'URL base per tunnel o XAMPP locale
        if (!app()->runningInConsole()) {
            // Cloudflare tunnel usa CF-Visitor e altri header
            $host = request()->header('CF-Connecting-IP') ? request()->header('Host') : 
                   (request()->header('X-Forwarded-Host') ?? request()->header('Host') ?? request()->getHost());
            
            // Se stiamo usando ngrok o cloudflare tunnel
            // Il tunnel punta a XAMPP che serve da /SUGECO/public/
            if (str_contains($host ?? '', 'ngrok') || str_contains($host ?? '', 'trycloudflare')) {
                $tunnelUrl = 'https://' . $host . '/SUGECO/public';
                URL::forceRootUrl($tunnelUrl);
                // IMPORTANTE: Forza anche l'asset URL
                config(['app.asset_url' => $tunnelUrl]);
            }
            // Per accesso locale/intranet - usa l'host dalla richiesta
            elseif (request()->server('REQUEST_URI') && strpos(request()->server('REQUEST_URI'), '/SUGECO/public/') !== false) {
                // Usa l'host effettivo della richiesta (IP o hostname)
                $currentHost = request()->getHost();
                $scheme = request()->getScheme(); // http o https
                $localUrl = $scheme . '://' . $currentHost . '/SUGECO/public';
                URL::forceRootUrl($localUrl);
                config(['app.asset_url' => $localUrl]);
            }
        }
    }
}
