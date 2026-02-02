<?php

namespace App\Http\Controllers;

use App\Models\OrganizationalUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Controller per la gestione dello switch dell'unità organizzativa attiva.
 * 
 * Gestisce il cambio di unità attiva per l'utente e il recupero
 * delle unità accessibili.
 */
class UnitSwitchController extends Controller
{
    /**
     * Cambia l'unità organizzativa attiva per l'utente.
     */
    public function switch(Request $request): JsonResponse
    {
        $request->validate([
            'unit_id' => 'required|integer|exists:organizational_units,id',
        ]);

        $user = Auth::user();
        $unitId = $request->input('unit_id');

        // Verifica che l'utente possa accedere a questa unità
        if (!$user->canAccessUnit($unitId)) {
            Log::warning('Tentativo di accesso a unità non autorizzata', [
                'user_id' => $user->id,
                'unit_id' => $unitId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Non hai accesso a questa unità organizzativa.',
            ], 403);
        }

        // Imposta l'unità attiva in sessione
        session(['active_unit_id' => $unitId]);

        // Carica l'unità per il log
        $unit = OrganizationalUnit::find($unitId);

        Log::info('Switch unità organizzativa', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'unit_id' => $unitId,
            'unit_name' => $unit->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Unità cambiata con successo.',
            'unit' => [
                'id' => $unit->id,
                'name' => $unit->name,
                'code' => $unit->code,
                'type' => $unit->type ? [
                    'code' => $unit->type->code,
                    'name' => $unit->type->name,
                    'color' => $unit->type->color,
                ] : null,
            ],
        ]);
    }

    /**
     * Ottiene le unità accessibili all'utente corrente.
     */
    public function getAccessibleUnits(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Se admin, ritorna tutte le unità attive
        if ($user->isGlobalAdmin()) {
            $units = OrganizationalUnit::with('type')
                ->active()
                ->orderBy('depth')
                ->orderBy('name')
                ->get();
        } else {
            $visibleUnitIds = $user->getVisibleUnitIds();
            $units = OrganizationalUnit::with('type')
                ->whereIn('id', $visibleUnitIds)
                ->active()
                ->orderBy('depth')
                ->orderBy('name')
                ->get();
        }

        return response()->json([
            'success' => true,
            'units' => $units->map(fn($unit) => [
                'id' => $unit->id,
                'name' => $unit->name,
                'code' => $unit->code,
                'depth' => $unit->depth,
                'type' => $unit->type ? [
                    'code' => $unit->type->code,
                    'name' => $unit->type->name,
                    'color' => $unit->type->color,
                    'icon' => $unit->type->icon,
                ] : null,
            ]),
            'active_unit_id' => activeUnitId(),
        ]);
    }

    /**
     * Ottiene i dettagli dell'unità attiva corrente.
     */
    public function getActiveUnit(Request $request): JsonResponse
    {
        $unit = activeUnit();

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Nessuna unità attiva.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'unit' => [
                'id' => $unit->id,
                'name' => $unit->name,
                'code' => $unit->code,
                'depth' => $unit->depth,
                'type' => $unit->type ? [
                    'code' => $unit->type->code,
                    'name' => $unit->type->name,
                    'color' => $unit->type->color,
                    'icon' => $unit->type->icon,
                ] : null,
            ],
        ]);
    }
}
