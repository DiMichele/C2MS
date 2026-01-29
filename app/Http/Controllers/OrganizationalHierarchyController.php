<?php

namespace App\Http\Controllers;

use App\Models\OrganizationalUnit;
use App\Models\OrganizationalUnitType;
use App\Models\UnitAssignment;
use App\Services\OrganizationalHierarchyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Controller per la gestione della gerarchia organizzativa.
 * 
 * Fornisce:
 * - Vista principale per visualizzazione/editing dell'albero
 * - API REST per operazioni CRUD sui nodi
 * - API per ottenere l'albero in formato JSON
 */
class OrganizationalHierarchyController extends Controller
{
    public function __construct(
        protected OrganizationalHierarchyService $hierarchyService
    ) {}

    // =========================================================================
    // VISTE
    // =========================================================================

    /**
     * Vista principale della gerarchia organizzativa.
     */
    public function index(): View
    {
        $user = auth()->user();
        $canEdit = $user->hasPermission('gerarchia.edit') || $user->hasRole('admin');

        return view('organizational-hierarchy.index', [
            'canEdit' => $canEdit,
            'unitTypes' => OrganizationalUnitType::active()->ordered()->get(),
        ]);
    }

    // =========================================================================
    // API - ALBERO
    // =========================================================================

    /**
     * Ottiene l'albero completo in formato JSON.
     * 
     * GET /api/organizational-hierarchy/tree
     */
    public function getTree(Request $request): JsonResponse
    {
        try {
            $activeOnly = $request->boolean('active_only', true);
            $tree = $this->hierarchyService->getTreeForUser(auth()->user(), $activeOnly);

            return response()->json([
                'success' => true,
                'data' => $tree,
            ]);
        } catch (\Exception $e) {
            Log::error('Errore nel recupero dell\'albero gerarchico', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero della struttura organizzativa.',
            ], 500);
        }
    }

    /**
     * Ottiene un sotto-albero a partire da un nodo.
     * 
     * GET /api/organizational-hierarchy/subtree/{uuid}
     */
    public function getSubtree(Request $request, string $uuid): JsonResponse
    {
        try {
            $unit = OrganizationalUnit::findByUuid($uuid);

            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unità non trovata.',
                ], 404);
            }

            $maxDepth = $request->integer('max_depth', null);
            $activeOnly = $request->boolean('active_only', true);
            $subtree = $this->hierarchyService->getSubtree($unit, $maxDepth, $activeOnly);

            return response()->json([
                'success' => true,
                'data' => $subtree,
            ]);
        } catch (\Exception $e) {
            Log::error('Errore nel recupero del sotto-albero', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero del sotto-albero.',
            ], 500);
        }
    }

    /**
     * Cerca unità per nome o codice.
     * 
     * GET /api/organizational-hierarchy/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $results = $this->hierarchyService->searchUnits(
                $request->input('q'),
                $request->integer('limit', 20)
            );

            return response()->json([
                'success' => true,
                'data' => $results->map(fn($unit) => [
                    'id' => $unit->id,
                    'uuid' => $unit->uuid,
                    'name' => $unit->name,
                    'code' => $unit->code,
                    'type' => $unit->type?->name,
                    'path' => $unit->getBreadcrumb(),
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('Errore nella ricerca unità', [
                'query' => $request->input('q'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nella ricerca.',
            ], 500);
        }
    }

    // =========================================================================
    // API - CRUD UNITÀ
    // =========================================================================

    /**
     * Ottiene i dettagli di un'unità.
     * 
     * GET /api/organizational-hierarchy/units/{uuid}
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $unit = OrganizationalUnit::with(['type', 'parent.type'])
                ->findByUuid($uuid);

            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unità non trovata.',
                ], 404);
            }

            $stats = $this->hierarchyService->getUnitStats($unit);

            return response()->json([
                'success' => true,
                'data' => [
                    'unit' => [
                        'id' => $unit->id,
                        'uuid' => $unit->uuid,
                        'name' => $unit->name,
                        'code' => $unit->code,
                        'description' => $unit->description,
                        'type' => $unit->type ? [
                            'id' => $unit->type->id,
                            'code' => $unit->type->code,
                            'name' => $unit->type->name,
                            'icon' => $unit->type->icon,
                            'color' => $unit->type->color,
                        ] : null,
                        'parent' => $unit->parent ? [
                            'id' => $unit->parent->id,
                            'uuid' => $unit->parent->uuid,
                            'name' => $unit->parent->name,
                        ] : null,
                        'depth' => $unit->depth,
                        'sort_order' => $unit->sort_order,
                        'settings' => $unit->settings,
                        'is_active' => $unit->is_active,
                        'created_at' => $unit->created_at,
                        'updated_at' => $unit->updated_at,
                    ],
                    'breadcrumb' => $unit->getBreadcrumb(),
                    'stats' => $stats,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Errore nel recupero dettagli unità', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero dei dettagli.',
            ], 500);
        }
    }

    /**
     * Crea una nuova unità.
     * 
     * POST /api/organizational-hierarchy/units
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', OrganizationalUnit::class);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'type_id' => 'required|exists:organizational_unit_types,id',
            'parent_uuid' => 'nullable|exists:organizational_units,uuid',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'settings' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $parent = null;
            if (!empty($validated['parent_uuid'])) {
                $parent = OrganizationalUnit::findByUuid($validated['parent_uuid']);
            }

            $unit = $this->hierarchyService->createUnit([
                'name' => $validated['name'],
                'type_id' => $validated['type_id'],
                'code' => $validated['code'] ?? null,
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'settings' => $validated['settings'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ], $parent);

            return response()->json([
                'success' => true,
                'message' => 'Unità creata con successo.',
                'data' => $unit->toTreeArray(),
            ], 201);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Errore nella creazione unità', [
                'data' => $validated,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nella creazione dell\'unità.',
            ], 500);
        }
    }

    /**
     * Aggiorna un'unità esistente.
     * 
     * PUT /api/organizational-hierarchy/units/{uuid}
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $unit = OrganizationalUnit::findByUuid($uuid);

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unità non trovata.',
            ], 404);
        }

        $this->authorize('update', $unit);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'type_id' => 'sometimes|required|exists:organizational_unit_types,id',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'settings' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $unit = $this->hierarchyService->updateUnit($unit, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Unità aggiornata con successo.',
                'data' => $unit->toTreeArray(),
            ]);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiornamento unità', [
                'uuid' => $uuid,
                'data' => $validated,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'aggiornamento dell\'unità.',
            ], 500);
        }
    }

    /**
     * Sposta un'unità sotto un nuovo parent.
     * 
     * POST /api/organizational-hierarchy/units/{uuid}/move
     */
    public function move(Request $request, string $uuid): JsonResponse
    {
        $unit = OrganizationalUnit::findByUuid($uuid);

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unità non trovata.',
            ], 404);
        }

        $this->authorize('update', $unit);

        $validated = $request->validate([
            'parent_uuid' => 'nullable|exists:organizational_units,uuid',
        ]);

        try {
            $newParent = null;
            if (!empty($validated['parent_uuid'])) {
                $newParent = OrganizationalUnit::findByUuid($validated['parent_uuid']);
            }

            $unit = $this->hierarchyService->moveUnit($unit, $newParent);

            return response()->json([
                'success' => true,
                'message' => 'Unità spostata con successo.',
                'data' => $unit->toTreeArray(),
            ]);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Errore nello spostamento unità', [
                'uuid' => $uuid,
                'new_parent_uuid' => $validated['parent_uuid'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nello spostamento dell\'unità.',
            ], 500);
        }
    }

    /**
     * Riordina le unità figlie.
     * 
     * POST /api/organizational-hierarchy/units/{uuid}/reorder
     */
    public function reorder(Request $request, string $uuid = null): JsonResponse
    {
        $parent = null;
        if ($uuid) {
            $parent = OrganizationalUnit::findByUuid($uuid);
            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unità parent non trovata.',
                ], 404);
            }
            $this->authorize('update', $parent);
        }

        $validated = $request->validate([
            'ordered_ids' => 'required|array|min:1',
            'ordered_ids.*' => 'required|integer|exists:organizational_units,id',
        ]);

        try {
            $this->hierarchyService->reorderChildren($parent, $validated['ordered_ids']);

            return response()->json([
                'success' => true,
                'message' => 'Ordine aggiornato con successo.',
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nel riordinamento', [
                'parent_uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel riordinamento.',
            ], 500);
        }
    }

    /**
     * Elimina un'unità.
     * 
     * DELETE /api/organizational-hierarchy/units/{uuid}
     */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $unit = OrganizationalUnit::findByUuid($uuid);

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unità non trovata.',
            ], 404);
        }

        $this->authorize('delete', $unit);

        $validated = $request->validate([
            'child_strategy' => 'nullable|in:orphan,cascade,promote',
        ]);

        try {
            $this->hierarchyService->deleteUnit(
                $unit,
                $validated['child_strategy'] ?? 'orphan'
            );

            return response()->json([
                'success' => true,
                'message' => 'Unità eliminata con successo.',
            ]);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Errore nell\'eliminazione unità', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'eliminazione dell\'unità.',
            ], 500);
        }
    }

    // =========================================================================
    // API - TIPI UNITÀ
    // =========================================================================

    /**
     * Ottiene i tipi di unità disponibili.
     * 
     * GET /api/organizational-hierarchy/types
     */
    public function getTypes(): JsonResponse
    {
        $types = OrganizationalUnitType::active()
            ->ordered()
            ->get()
            ->map(fn($type) => [
                'id' => $type->id,
                'code' => $type->code,
                'name' => $type->name,
                'description' => $type->description,
                'icon' => $type->icon,
                'color' => $type->color,
                'default_depth_level' => $type->default_depth_level,
                'can_contain_types' => $type->can_contain_types,
            ]);

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * Ottiene i tipi che possono essere contenuti da un parent.
     * 
     * GET /api/organizational-hierarchy/types/containable/{parent_uuid?}
     */
    public function getContainableTypes(string $parentUuid = null): JsonResponse
    {
        $parent = null;
        if ($parentUuid) {
            $parent = OrganizationalUnit::with('type')->findByUuid($parentUuid);
            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unità parent non trovata.',
                ], 404);
            }
        }

        $containableTypeCodes = $parent?->type?->can_contain_types;

        $query = OrganizationalUnitType::active()->ordered();

        if ($containableTypeCodes !== null) {
            $query->whereIn('code', $containableTypeCodes);
        }

        $types = $query->get()->map(fn($type) => [
            'id' => $type->id,
            'code' => $type->code,
            'name' => $type->name,
            'icon' => $type->icon,
            'color' => $type->color,
        ]);

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    // =========================================================================
    // API - ASSEGNAZIONI
    // =========================================================================

    /**
     * Ottiene le assegnazioni di un'unità.
     * 
     * GET /api/organizational-hierarchy/units/{uuid}/assignments
     */
    public function getAssignments(Request $request, string $uuid): JsonResponse
    {
        $unit = OrganizationalUnit::findByUuid($uuid);

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unità non trovata.',
            ], 404);
        }

        $assignments = $unit->assignments()
            ->with('assignable')
            ->when($request->input('type'), fn($q, $type) => $q->forType($type))
            ->when($request->boolean('active_only', true), fn($q) => $q->active())
            ->get()
            ->map(fn($assignment) => [
                'id' => $assignment->id,
                'role' => $assignment->role,
                'role_name' => $assignment->getRoleName(),
                'is_primary' => $assignment->is_primary,
                'start_date' => $assignment->start_date?->format('Y-m-d'),
                'end_date' => $assignment->end_date?->format('Y-m-d'),
                'assignable_type' => class_basename($assignment->assignable_type),
                'assignable' => $assignment->assignable ? [
                    'id' => $assignment->assignable->id,
                    'name' => $this->getAssignableName($assignment->assignable),
                ] : null,
            ]);

        return response()->json([
            'success' => true,
            'data' => $assignments,
        ]);
    }

    /**
     * Ottiene il nome visualizzabile di un'entità assegnabile.
     */
    protected function getAssignableName($assignable): string
    {
        if (method_exists($assignable, 'getFullNameAttribute')) {
            return $assignable->full_name;
        }

        if (isset($assignable->cognome, $assignable->nome)) {
            return "{$assignable->cognome} {$assignable->nome}";
        }

        if (isset($assignable->name)) {
            return $assignable->name;
        }

        return "#{$assignable->id}";
    }
}
