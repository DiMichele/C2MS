<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Middleware personalizzato per la verifica CSRF con cookie sicuri.
 * 
 * Estende il middleware standard di Laravel per aggiungere attributi
 * di sicurezza aggiuntivi al cookie XSRF-TOKEN.
 * 
 * NOTA: Il cookie XSRF-TOKEN NON ha HttpOnly perché JavaScript deve
 * leggerlo per le richieste AJAX. Questo è il design intenzionale di Laravel.
 */
class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Aggiungi qui eventuali rotte da escludere dalla verifica CSRF
        // Esempio: 'api/*', 'webhook/*'
    ];

    /**
     * Indica se i cookie XSRF-TOKEN devono essere impostati sulla risposta.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * Crea un nuovo cookie XSRF-TOKEN con attributi di sicurezza.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $config
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    protected function newCookie($request, $config)
    {
        // Determina se usare Secure based on environment
        $secure = $this->shouldBeSecure($request);
        
        return new Cookie(
            'XSRF-TOKEN',
            $request->session()->token(),
            $this->availableAt(60 * $config['lifetime']),
            $config['path'],
            $config['domain'],
            $secure,                          // Secure: true in produzione/HTTPS
            false,                            // HttpOnly: DEVE essere false per JavaScript
            false,                            // Raw
            $config['same_site'] ?? 'lax'     // SameSite: protegge da CSRF cross-site
        );
    }

    /**
     * Determina se il cookie deve essere marcato come Secure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldBeSecure($request): bool
    {
        // In produzione, sempre secure
        if (app()->environment('production')) {
            return true;
        }

        // Se FORCE_HTTPS è abilitato
        if (filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        // Se la richiesta è già HTTPS
        if ($request->secure()) {
            return true;
        }

        // Se dietro proxy HTTPS
        if ($request->header('X-Forwarded-Proto') === 'https') {
            return true;
        }

        // Altrimenti, in sviluppo, non forzare secure
        return false;
    }
}
