<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * SUGECO: Middleware per Enforcement Accesso Compagnia
 * 
 * Questo middleware verifica che l'utente abbia accesso ai dati richiesti
 * basandosi sulla sua compagnia di appartenenza.
 * 
 * Funzionalità:
 * - Verifica accesso a risorse specifiche (es. /militare/{id})
 * - Blocca accesso a dati di altre compagnie
 * - Registra tentativi di accesso non autorizzati
 * 
 * @package App\Http\Middleware
 * @version 1.0
 * @author Michele Di Gennaro
 */
class EnforceCompagniaAccess
{
    /**
     * Modelli e parametri route da verificare
     * 
     * Formato: 'parametro_route' => 'ModelClass'
     */
    protected array $modelBindings = [
        'militare' => \App\Models\Militare::class,
        'id' => null, // Determinato dal contesto
    ];
    
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $modelClass Classe del modello da verificare
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string $modelClass = null): Response
    {
        // Se non autenticato, il middleware auth gestisce
        if (!Auth::check()) {
            return $next($request);
        }
        
        $user = Auth::user();
        
        // Admin hanno accesso globale
        if ($user->hasGlobalVisibility()) {
            return $next($request);
        }
        
        // Verifica accesso ai parametri route
        if ($modelClass) {
            $this->verifyModelAccess($request, $modelClass, $user);
        }
        
        // Verifica parametri compagnia_id nelle richieste
        $this->verifyCompagniaParameter($request, $user);
        
        return $next($request);
    }
    
    /**
     * Verifica l'accesso a un modello specifico
     * 
     * @param Request $request
     * @param string $modelClass
     * @param \App\Models\User $user
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function verifyModelAccess(Request $request, string $modelClass, $user): void
    {
        // Cerca il parametro route che corrisponde al modello
        foreach ($request->route()->parameters() as $key => $value) {
            // Se è già un'istanza del modello (route model binding)
            if ($value instanceof $modelClass) {
                $this->checkModelCompagnia($value, $user);
                continue;
            }
            
            // Se è un ID, carica il modello
            if (is_numeric($value) && class_exists($modelClass)) {
                $model = $modelClass::withoutGlobalScopes()->find($value);
                if ($model) {
                    $this->checkModelCompagnia($model, $user);
                }
            }
        }
    }
    
    /**
     * Verifica che il modello appartenga alla compagnia dell'utente
     * 
     * @param mixed $model
     * @param \App\Models\User $user
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function checkModelCompagnia($model, $user): void
    {
        // Verifica se il modello ha compagnia_id
        if (!isset($model->compagnia_id)) {
            return; // Il modello non ha segregazione per compagnia
        }
        
        // Verifica accesso
        if ($model->compagnia_id !== $user->compagnia_id) {
            // Log del tentativo di accesso non autorizzato
            \Log::warning('Tentativo accesso dati altra compagnia', [
                'user_id' => $user->id,
                'user_compagnia' => $user->compagnia_id,
                'model_class' => get_class($model),
                'model_id' => $model->id,
                'model_compagnia' => $model->compagnia_id,
                'ip' => request()->ip(),
                'url' => request()->fullUrl(),
            ]);
            
            abort(403, 'Non hai accesso a questa risorsa.');
        }
    }
    
    /**
     * Verifica che i parametri compagnia_id nella richiesta siano validi
     * 
     * @param Request $request
     * @param \App\Models\User $user
     */
    protected function verifyCompagniaParameter(Request $request, $user): void
    {
        // Verifica parametro compagnia_id in query string o body
        $compagniaId = $request->input('compagnia_id') ?? $request->query('compagnia_id');
        
        if ($compagniaId && (int)$compagniaId !== $user->compagnia_id) {
            // Ignora silenziosamente il parametro e usa la compagnia dell'utente
            // Questo previene tentativi di manipolazione
            $request->merge(['compagnia_id' => $user->compagnia_id]);
            
            \Log::info('Parametro compagnia_id sovrascritto per sicurezza', [
                'user_id' => $user->id,
                'requested_compagnia' => $compagniaId,
                'actual_compagnia' => $user->compagnia_id,
            ]);
        }
    }
}

