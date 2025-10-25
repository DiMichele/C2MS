<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ForceCorrectUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ottieni l'host dalla richiesta
        $host = $request->header('X-Forwarded-Host') 
                ?? $request->header('Host') 
                ?? $request->getHost();
        
        // Se è un tunnel (ngrok o cloudflare), forza l'URL completo
        if (str_contains($host, 'ngrok') || str_contains($host, 'trycloudflare')) {
            URL::forceRootUrl('https://' . $host . '/C2MS/public');
            URL::forceScheme('https');
        }
        
        return $next($request);
    }
}

