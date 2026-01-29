<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust proxies for ngrok/cloudflare tunnels
        $middleware->trustProxies(at: '*');
        
        // =====================================================================
        // CSRF MIDDLEWARE PERSONALIZZATO
        // =====================================================================
        // Sostituisce il middleware CSRF di default con uno che configura
        // correttamente gli attributi di sicurezza del cookie XSRF-TOKEN
        $middleware->web(replace: [
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class 
                => \App\Http\Middleware\VerifyCsrfToken::class,
        ]);
        
        // =====================================================================
        // SECURITY MIDDLEWARE (ordine importante!)
        // =====================================================================
        
        // 1. Force HTTPS in produzione (deve essere primo per sicurezza)
        $middleware->prepend(\App\Http\Middleware\ForceHttps::class);
        
        // 2. Force correct URL for tunnels
        $middleware->append(\App\Http\Middleware\ForceCorrectUrl::class);
        
        // 3. Security Headers - aggiunge header di sicurezza a tutte le risposte
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        
        // =====================================================================
        // APPLICATION MIDDLEWARE
        // =====================================================================
        
        // Middleware per controllo cambio password obbligatorio
        $middleware->append(\App\Http\Middleware\MustChangePassword::class);
        
        // Middleware con alias per routes
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'compagnia.access' => \App\Http\Middleware\EnforceCompagniaAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // =====================================================================
        // GESTIONE SICURA DELLE ECCEZIONI
        // =====================================================================
        
        // Non mostrare dettagli degli errori in produzione
        // Questo previene information disclosure
        $exceptions->dontReport([
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Auth\Access\AuthorizationException::class,
            \Illuminate\Validation\ValidationException::class,
        ]);
        
        // =====================================================================
        // AUDIT LOG PER ERRORI APPLICATIVI
        // =====================================================================
        // Traccia gli errori 500 e altre eccezioni critiche nel sistema di audit
        $exceptions->report(function (\Throwable $e) {
            // Lista eccezioni da NON loggare nell'audit (troppo frequenti o non critiche)
            $skipAuditFor = [
                \Illuminate\Auth\AuthenticationException::class,
                \Illuminate\Auth\Access\AuthorizationException::class,
                \Illuminate\Validation\ValidationException::class,
                \Illuminate\Session\TokenMismatchException::class,
                \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
                \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
            ];
            
            foreach ($skipAuditFor as $skipClass) {
                if ($e instanceof $skipClass) {
                    return false; // Non loggare nell'audit, ma continua con il logging normale
                }
            }
            
            // Log dell'errore nel sistema di audit
            try {
                $request = request();
                $user = auth()->user();
                
                // Determina il codice di stato
                $statusCode = method_exists($e, 'getStatusCode') 
                    ? $e->getStatusCode() 
                    : 500;
                
                // Costruisci una descrizione completa dell'errore
                $errorMessage = $e->getMessage();
                $errorFile = $e->getFile();
                $errorLine = $e->getLine();
                
                $fullDescription = "Errore {$statusCode}: " . class_basename($e) . "\n\n"
                    . "Messaggio: {$errorMessage}\n\n"
                    . "File: {$errorFile}\n"
                    . "Linea: {$errorLine}";
                
                // Crea il log di errore con descrizione completa
                \App\Models\AuditLog::create([
                    'user_id' => $user?->id,
                    'user_name' => $user?->name ?? 'Guest',
                    'action' => 'other',
                    'description' => $fullDescription,
                    'entity_type' => 'sistema',
                    'entity_name' => 'Errore Applicativo',
                    'status' => 'failed',
                    'ip_address' => $request->ip(),
                    'user_agent' => \Illuminate\Support\Str::limit($request->userAgent(), 255),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'compagnia_id' => $user?->compagnia_id,
                    'metadata' => [
                        'exception_class' => get_class($e),
                        'status_code' => $statusCode,
                        'file' => $errorFile,
                        'line' => $errorLine,
                        'trace' => $e->getTraceAsString(),
                    ],
                ]);
            } catch (\Throwable $auditException) {
                // Se il logging dell'audit fallisce, non bloccare l'applicazione
                // L'errore verrà comunque loggato nel sistema standard di Laravel
                \Illuminate\Support\Facades\Log::warning('Audit log failed for exception', [
                    'original_error' => $e->getMessage(),
                    'audit_error' => $auditException->getMessage(),
                ]);
            }
            
            return false; // Continua con il reporting normale di Laravel
        });
        
        // Rende le eccezioni generiche in produzione
        $exceptions->render(function (\Throwable $e, $request) {
            // In produzione, non esporre dettagli degli errori
            if (app()->environment('production')) {
                // Se è una richiesta API, restituisci JSON generico
                if ($request->expectsJson()) {
                    $status = method_exists($e, 'getStatusCode') 
                        ? $e->getStatusCode() 
                        : 500;
                    
                    return response()->json([
                        'message' => 'Si è verificato un errore.',
                        'error' => true
                    ], $status);
                }
            }
            
            // Altrimenti usa il comportamento di default di Laravel
            return null;
        });
    })->create();
