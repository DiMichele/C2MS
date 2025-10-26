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
        
        // Force correct URL for tunnels (must be first)
        $middleware->prepend(\App\Http\Middleware\ForceCorrectUrl::class);
        
        // Middleware per controllo cambio password obbligatorio
        $middleware->append(\App\Http\Middleware\MustChangePassword::class);
        
        // Middleware con alias per routes
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
