<?php

namespace App\Http\Controllers;

use App\Models\OrganizationalUnit;
use App\Models\OrganizationalUnitType;
use App\Models\UnitAssignment;
use App\Services\AuditService;
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
     * 
     * @param bool active_only Solo unità attive (default: true)
     * @param bool include_militari Includere militari come nodi foglia (default: false)
     */
    public function getTree(Request $request): JsonResponse
    {
        try {
            $activeOnly = $request->boolean('active_only', true);
            $includeMilitari = $request->boolean('include_militari', false);
            
            $tree = $this->hierarchyService->getTreeForUser(
                auth()->user(), 
                $activeOnly,
                $includeMilitari
            );

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
            $unit = OrganizationalUnit::where('uuid', $uuid)
                ->with(['type', 'parent.type'])
                ->first();

            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unità non trovata.',
                ], 404);
            }

            $stats = $this->hierarchyService->getUnitStats($unit);
            $breadcrumb = $unit->getBreadcrumb();

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
                    'breadcrumb' => $breadcrumb,
                    'stats' => $stats,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Errore nel recupero dettagli unità', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
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
            'template_unit_id' => 'nullable|exists:organizational_units,id',
            'skip_seed' => 'nullable|boolean',
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

            $seedMessage = null;

            // AUTO-SEED: Se è un'unità di tipo battaglione (depth=1), copia configurazioni da template
            $skipSeed = $validated['skip_seed'] ?? false;
            if (!$skipSeed && $unit->depth === 1) {
                $templateUnitId = $validated['template_unit_id'] ?? $this->hierarchyService->getDefaultTemplateUnitId();
                
                if ($templateUnitId && $templateUnitId !== $unit->id) {
                    try {
                        $seedResults = $this->hierarchyService->seedConfigurationsFromTemplate(
                            $unit->id,
                            $templateUnitId,
                            false // Non copiare codici CPT di default
                        );

                        $templateUnit = OrganizationalUnit::find($templateUnitId);
                        $seedMessage = "Configurazioni copiate da {$templateUnit->name}: " .
                            "{$seedResults['campi_anagrafica']} campi anagrafica, " .
                            "{$seedResults['ruolini']} ruolini.";

                        Log::info('Auto-seed configurazioni per nuova unità battaglione', [
                            'unit_id' => $unit->id,
                            'unit_name' => $unit->name,
                            'template_unit_id' => $templateUnitId,
                            'results' => $seedResults,
                        ]);
                    } catch (\Throwable $e) {
                        // Non bloccare la creazione se il seed fallisce
                        Log::warning('Auto-seed fallito per nuova unità', [
                            'unit_id' => $unit->id,
                            'template_unit_id' => $templateUnitId,
                            'error' => $e->getMessage(),
                        ]);
                        $seedMessage = "Attenzione: seed configurazioni fallito. Eseguire manualmente: php artisan unit:seed {$unit->id}";
                    }
                }
            }

            $message = 'Unità creata con successo.';
            if ($seedMessage) {
                $message .= ' ' . $seedMessage;
            }

            return response()->json([
                'success' => true,
                'message' => $message,
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

        // VALIDAZIONE: Blocca eliminazione se ha sotto-unità
        $childrenCount = $unit->children()->count();
        if ($childrenCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Impossibile eliminare: l'unità contiene {$childrenCount} sotto-unità. Elimina o sposta prima le sotto-unità.",
            ], 400);
        }

        $validated = $request->validate([
            'child_strategy' => 'nullable|in:orphan',
        ]);

        try {
            // Conta militari che diventeranno orphan
            $militariCount = $unit->militari()->count();
            
            // Rendi orphan i militari (organizational_unit_id = null)
            if ($militariCount > 0) {
                $unit->militari()->update(['organizational_unit_id' => null]);
            }
            
            // Registra nell'audit log
            AuditService::log('delete', "Eliminata unità: {$unit->name} ({$militariCount} militari resi orphan)", $unit);
            
            // Elimina l'unità
            $unit->delete();

            return response()->json([
                'success' => true,
                'message' => "Unità eliminata. {$militariCount} militari devono essere riassegnati.",
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
            $parent = OrganizationalUnit::with('type')->where('uuid', $parentUuid)->first();
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

    // =========================================================================
    // EXPORT
    // =========================================================================

    /**
     * Esporta l'organigramma in formato Excel.
     * 
     * GET /gerarchia-organizzativa/export/excel
     * 
     * PAGINA GLOBALE: Esporta tutte le unità accessibili all'utente.
     * - Admin globali: esportano tutte le unità attive
     * - Altri utenti: esportano solo le unità accessibili
     */
    public function exportExcel(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $user = auth()->user();
        
        // Query base con relazioni
        $unitsQuery = OrganizationalUnit::with(['type', 'parent', 'militari'])
            ->active()
            ->orderBy('path');
        
        // Filtro per unità accessibili (se non admin globale)
        if (!$user->hasRole('admin')) {
            $accessibleUnitIds = $user->getVisibleUnitIds();
            if (!empty($accessibleUnitIds)) {
                $unitsQuery->whereIn('id', $accessibleUnitIds);
            } else {
                // Nessuna unità accessibile - esporta vuoto
                $unitsQuery->whereRaw('1 = 0');
            }
        }
        
        $units = $unitsQuery->get();
        
        // Crea il foglio Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Organigramma');
        
        // Colori
        $navyColor = '0A2342';
        $goldColor = 'BF9D5E';
        $grayLight = 'F5F7F9';
        
        // Headers
        $headers = ['Nome Unità', 'Tipo', 'Parent', 'Livello', 'Numero Militari', 'Codice', 'Stato'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        
        // Stile header
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => $navyColor],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
        
        // Dati
        $row = 2;
        foreach ($units as $unit) {
            $indent = str_repeat('  ', $unit->depth);
            
            $sheet->setCellValue('A' . $row, $indent . $unit->name);
            $sheet->setCellValue('B' . $row, $unit->type?->name ?? 'N/D');
            $sheet->setCellValue('C' . $row, $unit->parent?->name ?? '—');
            $sheet->setCellValue('D' . $row, $unit->depth);
            $sheet->setCellValue('E' . $row, $unit->militari->count());
            $sheet->setCellValue('F' . $row, $unit->code ?? '');
            $sheet->setCellValue('G' . $row, $unit->is_active ? 'Attiva' : 'Inattiva');
            
            // Alternanza colori righe
            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':G' . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($grayLight);
            }
            
            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;
        }
        
        // Bordi
        $sheet->getStyle('A1:G' . ($row - 1))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'DEE2E6'],
                ],
            ],
        ]);
        
        // Auto-size colonne
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Freeze pane
        $sheet->freezePane('A2');
        
        // Footer con data generazione
        $sheet->setCellValue('A' . $row, 'Generato il: ' . now()->format('d/m/Y H:i'));
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setItalic(true)->setSize(9);
        
        // Genera file
        $filename = 'organigramma_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'organigramma_');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // =========================================================================
    // API - SINCRONIZZAZIONE CAMPI LEGACY
    // =========================================================================

    /**
     * Ottiene i campi legacy (compagnia_id, plotone_id) per un'unità organizzativa.
     * 
     * GET /api/organizational-hierarchy/units/{id}/legacy-fields
     * 
     * Usato per sincronizzare automaticamente i dropdown nell'anagrafica
     * quando l'utente seleziona un'unità organizzativa.
     */
    public function getLegacyFields(int $id): JsonResponse
    {
        $unit = OrganizationalUnit::with(['type', 'parent.type', 'parent.parent.type'])->find($id);

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unità non trovata.',
            ], 404);
        }

        $compagniaId = $this->findLegacyCompagniaId($unit);
        $plotoneId = $this->findLegacyPlotoneId($unit);

        return response()->json([
            'success' => true,
            'data' => [
                'organizational_unit_id' => $unit->id,
                'organizational_unit_name' => $unit->name,
                'compagnia_id' => $compagniaId,
                'plotone_id' => $plotoneId,
                'unit_type' => $unit->type?->code,
            ],
        ]);
    }

    /**
     * Risale la gerarchia per trovare il legacy_compagnia_id.
     */
    private function findLegacyCompagniaId(?OrganizationalUnit $unit): ?int
    {
        if (!$unit) {
            return null;
        }

        // Se questa unità ha un legacy_compagnia_id, restituiscilo
        if ($unit->legacy_compagnia_id) {
            return $unit->legacy_compagnia_id;
        }

        // Altrimenti, risali al parent
        if ($unit->parent_id) {
            $parent = $unit->relationLoaded('parent') 
                ? $unit->parent 
                : OrganizationalUnit::find($unit->parent_id);
            return $this->findLegacyCompagniaId($parent);
        }

        return null;
    }

    /**
     * Restituisce il legacy_plotone_id se l'unità è un plotone.
     */
    private function findLegacyPlotoneId(?OrganizationalUnit $unit): ?int
    {
        if (!$unit) {
            return null;
        }

        // Solo se l'unità è di tipo plotone
        if ($unit->type?->code === 'plotone') {
            return $unit->legacy_plotone_id;
        }

        return null;
    }
}
