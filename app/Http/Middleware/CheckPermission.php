<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$request->user()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Autenticazione richiesta.'
                ], 401);
            }
            return redirect()->route('login');
        }

        if (!$request->user()->hasPermission($permission)) {
            // Gestione per richieste AJAX
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non hai i permessi necessari per eseguire questa azione.',
                    'permission_required' => $permission
                ], 403);
            }
            
            // Gestione per richieste normali
            abort(403, 'Non hai i permessi necessari per accedere a questa sezione.');
        }

        return $next($request);
    }
}
