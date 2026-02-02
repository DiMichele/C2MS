<?php

namespace App\Http\Controllers;

use App\Models\OrganizationalUnitType;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controller per la gestione dei tipi di unità organizzative.
 * 
 * Permette agli amministratori di configurare i tipi di nodi
 * che possono esistere nella gerarchia (reggimento, battaglione, ecc.)
 */
class OrganizationalUnitTypeController extends Controller
{
    /**
     * Lista tutti i tipi di unità
     */
    public function index()
    {
        $types = OrganizationalUnitType::ordered()
            ->withCount('units')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }
    
    /**
     * Crea un nuovo tipo di unità
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:organizational_unit_types,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'icon' => 'required|string|max:50',
            'color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'default_depth_level' => 'nullable|integer|min:0|max:10',
            'can_contain_types' => 'nullable|array',
            'can_contain_types.*' => 'exists:organizational_unit_types,code',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        
        $type = OrganizationalUnitType::create($validated);
        
        AuditService::log('create', $type, "Creato tipo unità: {$type->name}");
        
        return response()->json([
            'success' => true,
            'data' => $type,
            'message' => 'Tipo unità creato con successo',
        ], 201);
    }
    
    /**
     * Mostra un tipo specifico
     */
    public function show(OrganizationalUnitType $type)
    {
        $type->loadCount('units');
        
        return response()->json([
            'success' => true,
            'data' => $type,
        ]);
    }
    
    /**
     * Aggiorna un tipo esistente
     */
    public function update(Request $request, OrganizationalUnitType $type)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'icon' => 'required|string|max:50',
            'color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'default_depth_level' => 'nullable|integer|min:0|max:10',
            'can_contain_types' => 'nullable|array',
            'can_contain_types.*' => 'exists:organizational_unit_types,code',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);
        
        $type->update($validated);
        
        AuditService::log('update', $type, "Aggiornato tipo unità: {$type->name}");
        
        return response()->json([
            'success' => true,
            'data' => $type->fresh(),
            'message' => 'Tipo unità aggiornato',
        ]);
    }
    
    /**
     * Elimina un tipo (solo se non in uso)
     */
    public function destroy(OrganizationalUnitType $type)
    {
        // Verifica se il tipo è in uso
        $unitsCount = $type->units()->count();
        
        if ($unitsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Impossibile eliminare: il tipo è utilizzato da {$unitsCount} unità",
            ], 400);
        }
        
        // Verifica se è un tipo di sistema (non eliminabile)
        $systemTypes = ['reggimento', 'battaglione', 'compagnia', 'plotone'];
        if (in_array($type->code, $systemTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Impossibile eliminare un tipo di sistema',
            ], 400);
        }
        
        $typeName = $type->name;
        $type->delete();
        
        AuditService::log('delete', null, "Eliminato tipo unità: {$typeName}");
        
        return response()->json([
            'success' => true,
            'message' => 'Tipo unità eliminato',
        ]);
    }
    
    /**
     * Riordina i tipi di unità
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|exists:organizational_unit_types,id',
            'order.*.sort_order' => 'required|integer|min:0',
        ]);
        
        foreach ($validated['order'] as $item) {
            OrganizationalUnitType::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Ordinamento aggiornato',
        ]);
    }
    
    /**
     * Ottiene i tipi che possono essere figli di un tipo specifico
     */
    public function containableTypes(OrganizationalUnitType $type)
    {
        $containable = $type->getContainableTypes();
        
        $types = OrganizationalUnitType::active()
            ->whereIn('code', $containable)
            ->ordered()
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }
}
