<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware per aggiungere header di sicurezza HTTP a tutte le risposte.
 * 
 * Questo middleware implementa le best practice di sicurezza per proteggere
 * l'applicazione da attacchi comuni come XSS, clickjacking, MIME sniffing, etc.
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Previene attacchi clickjacking impedendo l'embedding in iframe esterni
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Previene MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Abilita protezione XSS nel browser (legacy, ma utile per browser vecchi)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Controlla quali informazioni referrer vengono incluse nelle richieste
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permessi del browser - disabilita funzionalità non necessarie
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()');

        // Content Security Policy - Protegge da XSS e injection
        // Configurazione permissiva per compatibilità con Bootstrap/jQuery, ma sicura
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob:",  // Necessario per jQuery/Bootstrap/jsTree e Web Workers
            "style-src 'self' 'unsafe-inline'",                       // Necessario per stili inline
            "img-src 'self' data: blob:",                             // Permette immagini data URI
            "font-src 'self' data:",                                  // Permette font locali
            "worker-src 'self' blob:",                                // Permette Web Workers (jsTree)
            "connect-src 'self'",                                     // AJAX solo verso stesso dominio
            "frame-ancestors 'self'",                                 // Previene embedding
            "form-action 'self'",                                     // Form solo verso stesso dominio
            "base-uri 'self'",                                        // Previene base tag injection
            "object-src 'none'",                                      // Blocca plugin (Flash, etc.)
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        // Strict Transport Security - Forza HTTPS per le connessioni future
        // Applicato solo se la connessione corrente è HTTPS
        if ($request->secure() || $request->header('X-Forwarded-Proto') === 'https') {
            // max-age=31536000 = 1 anno, includeSubDomains per sicurezza completa
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Rimuove header che espongono informazioni sul server
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        // Cache control per pagine sensibili (non applicare a risorse statiche)
        if (!$this->isStaticResource($request)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }

    /**
     * Verifica se la richiesta è per una risorsa statica.
     */
    private function isStaticResource(Request $request): bool
    {
        $staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf', 'eot'];
        $extension = pathinfo($request->path(), PATHINFO_EXTENSION);
        
        return in_array(strtolower($extension), $staticExtensions);
    }
}
