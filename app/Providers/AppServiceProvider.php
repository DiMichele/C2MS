<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
        // Registra Gate per i permessi personalizzati
        // Questo permette a @can di usare il nostro sistema di permessi
        Gate::before(function ($user, $ability) {
            // L'amministratore ha SEMPRE tutti i permessi
            if ($user->hasRole('amministratore')) {
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
            
            // Log per debug
            \Log::debug('AppServiceProvider boot', [
                'host' => $host,
                'x_forwarded_host' => request()->header('X-Forwarded-Host'),
                'cf_connecting_ip' => request()->header('CF-Connecting-IP'),
                'request_host' => request()->getHost(),
                'asset_url_before' => config('app.asset_url'),
            ]);
            
            // Se stiamo usando ngrok o cloudflare tunnel
            // Il tunnel punta a XAMPP che serve da /SUGECO/public/
            if (str_contains($host ?? '', 'ngrok') || str_contains($host ?? '', 'trycloudflare')) {
                $tunnelUrl = 'https://' . $host . '/SUGECO/public';
                URL::forceRootUrl($tunnelUrl);
                // IMPORTANTE: Forza anche l'asset URL
                config(['app.asset_url' => $tunnelUrl]);
                \Log::debug('Using tunnel URL: ' . $tunnelUrl . ' | Asset URL: ' . config('app.asset_url'));
            }
            // Altrimenti usa localhost per sviluppo locale
            elseif (request()->server('REQUEST_URI') && strpos(request()->server('REQUEST_URI'), '/SUGECO/public/') !== false) {
                $localUrl = 'http://localhost/SUGECO/public';
                URL::forceRootUrl($localUrl);
                config(['app.asset_url' => $localUrl]);
                \Log::debug('Using localhost URL: ' . $localUrl);
            }
        }
    }
}
