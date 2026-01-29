<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware per forzare l'utilizzo di HTTPS in produzione.
 * 
 * Questo middleware:
 * - Reindirizza automaticamente le richieste HTTP a HTTPS in produzione
 * - Supporta la configurazione tramite variabile d'ambiente FORCE_HTTPS
 * - Gestisce correttamente i proxy (ngrok, cloudflare, load balancer)
 */
class ForceHttps
{
    /**
     * Percorsi esclusi dal redirect HTTPS (es. health check)
     */
    protected array $except = [
        'up',           // Laravel health check
        'health',       // Health check alternativo
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se HTTPS è forzato dalla configurazione
        $forceHttps = $this->shouldForceHttps();

        if ($forceHttps && !$this->isSecure($request) && !$this->isExcluded($request)) {
            // Costruisce l'URL HTTPS mantenendo path e query string
            $secureUrl = 'https://' . $request->getHost() . $request->getRequestUri();
            
            // Redirect permanente (301) per SEO e caching del browser
            return redirect()->to($secureUrl, 301);
        }

        // Se siamo su HTTPS, forza lo schema per la generazione URL
        if ($this->isSecure($request)) {
            URL::forceScheme('https');
        }

        return $next($request);
    }

    /**
     * Determina se HTTPS deve essere forzato basandosi sulla configurazione.
     */
    private function shouldForceHttps(): bool
    {
        // Se FORCE_HTTPS è esplicitamente configurato, usa quello
        $forceHttps = env('FORCE_HTTPS');
        
        if ($forceHttps !== null) {
            return filter_var($forceHttps, FILTER_VALIDATE_BOOLEAN);
        }

        // Altrimenti, forza HTTPS solo in produzione
        return app()->environment('production');
    }

    /**
     * Verifica se la richiesta è sicura (HTTPS).
     * Gestisce anche i proxy che terminano SSL.
     */
    private function isSecure(Request $request): bool
    {
        // Controlla prima l'header del proxy
        if ($request->header('X-Forwarded-Proto') === 'https') {
            return true;
        }

        // Controlla se la connessione diretta è HTTPS
        if ($request->secure()) {
            return true;
        }

        // Controlla header alternativi usati da alcuni proxy
        if ($request->header('X-Forwarded-Ssl') === 'on') {
            return true;
        }

        if ($request->header('X-Url-Scheme') === 'https') {
            return true;
        }

        return false;
    }

    /**
     * Verifica se il percorso corrente è escluso dal redirect.
     */
    private function isExcluded(Request $request): bool
    {
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
