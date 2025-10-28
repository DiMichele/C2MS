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
        //
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
            $host = request()->header('X-Forwarded-Host') ?? request()->header('Host') ?? request()->getHost();
            
            // Log per debug
            \Log::debug('AppServiceProvider boot', [
                'host' => $host,
                'x_forwarded_host' => request()->header('X-Forwarded-Host'),
                'request_host' => request()->getHost(),
            ]);
            
            // Se stiamo usando ngrok
            if (str_contains($host ?? '', 'ngrok')) {
                URL::forceRootUrl('https://' . $host . '/C2MS/public');
                \Log::debug('Using ngrok URL: https://' . $host . '/C2MS/public');
            } 
            // Se stiamo usando cloudflare
            elseif (str_contains($host ?? '', 'trycloudflare')) {
                URL::forceRootUrl('https://' . $host . '/C2MS/public');
                \Log::debug('Using cloudflare URL: https://' . $host . '/C2MS/public');
            }
            // Altrimenti usa localhost per sviluppo locale
            elseif (request()->server('REQUEST_URI') && strpos(request()->server('REQUEST_URI'), '/C2MS/public/') !== false) {
                URL::forceRootUrl('http://localhost/C2MS/public');
                \Log::debug('Using localhost URL');
            }
        }
    }
}
