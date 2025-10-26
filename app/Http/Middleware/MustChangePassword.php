<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MustChangePassword
{
    /**
     * Handle an incoming request.
     * 
     * Forza l'utente a cambiare password se must_change_password è true
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->must_change_password) {
            // Permetti accesso solo alla pagina profilo e logout
            if (!$request->is('profile*') && !$request->is('logout')) {
                return redirect()->route('profile.index')
                    ->with('warning', 'Devi cambiare la password prima di continuare.');
            }
        }

        return $next($request);
    }
}
