<?php

namespace App\Services;

use App\Models\OrganizationalUnit;
use App\Models\OrganizationalUnitType;
use App\Models\UnitClosure;
use App\Models\UnitAssignment;
use App\Models\User;
use App\Models\ConfigurazioneCampoAnagrafica;
use App\Models\ConfigurazioneRuolino;
use App\Models\CodiciServizioGerarchia;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Service per la gestione della gerarchia organizzativa.
 * 
 * Gestisce tutte le operazioni CRUD sulle unità organizzative,
 * mantenendo sincronizzate le strutture di supporto (closure table, path).
 */
class OrganizationalHierarchyService
{
    /**
     * Crea una nuova unità organizzativa.
     *
     * @param array $data Dati dell'unità
     * @param OrganizationalUnit|null $parent Unità padre (null per root)
     * @return OrganizationalUnit
     * @throws InvalidArgumentException
     */
    public function createUnit(array $data, ?OrganizationalUnit $parent = null): OrganizationalUnit
    {
        // Validazione tipo
        $type = $this->resolveType($data['type_id'] ?? $data['type'] ?? null);
        if (!$type) {
            throw new InvalidArgumentException('Tipo di unità non valido o non specificato.');
        }

        // Validazione vincoli tipo se c'è un parent
        if ($parent && !$parent->type->canContain($type)) {
            throw new InvalidArgumentException(
                "Il tipo '{$type->name}' non può essere contenuto in '{$parent->type->name}'."
            );
        }

        return DB::transaction(function () use ($data, $parent, $type) {
            // Prepara i dati
            $unitData = [
                'uuid' => $data['uuid'] ?? (string) Str::uuid(),
                'type_id' => $type->id,
                'parent_id' => $parent?->id,
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'description' => $data['description'] ?? null,
                'sort_order' => $data['sort_order'] ?? $this->getNextSortOrder($parent),
                'settings' => $data['settings'] ?? null,
                'legacy_compagnia_id' => $data['legacy_compagnia_id'] ?? null,
                'legacy_plotone_id' => $data['legacy_plotone_id'] ?? null,
                'legacy_polo_id' => $data['legacy_polo_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ];

            // Calcola path e depth
            if ($parent) {
                $unitData['path'] = $parent->path 
                    ? $parent->path . '.' . $parent->id 
                    : (string) $parent->id;
                $unitData['depth'] = $parent->depth + 1;
            } else {
                $unitData['path'] = '';
                $unitData['depth'] = 0;
            }

            // Crea l'unità
            $unit = OrganizationalUnit::create($unitData);

            // Aggiorna la closure table
            UnitClosure::insertForNode($unit);

            // Log dell'operazione
            Log::info('Unità organizzativa creata', [
                'unit_id' => $unit->id,
                'name' => $unit->name,
                'type' => $type->code,
                'parent_id' => $parent?->id,
            ]);

            return $unit->fresh(['type', 'parent']);
        });
    }

    /**
     * Aggiorna un'unità organizzativa.
     *
     * @param OrganizationalUnit $unit Unità da aggiornare
     * @param array $data Nuovi dati
     * @return OrganizationalUnit
     */
    public function updateUnit(OrganizationalUnit $unit, array $data): OrganizationalUnit
    {
        return DB::transaction(function () use ($unit, $data) {
            // Se cambia il tipo, verifica i vincoli
            if (isset($data['type_id']) && $data['type_id'] !== $unit->type_id) {
                $newType = $this->resolveType($data['type_id']);
                if ($unit->parent && !$unit->parent->type->canContain($newType)) {
                    throw new InvalidArgumentException(
                        "Il tipo '{$newType->name}' non può essere contenuto nel parent attuale."
                    );
                }
            }

            // Aggiorna i campi
            $unit->fill([
                'type_id' => $data['type_id'] ?? $unit->type_id,
                'name' => $data['name'] ?? $unit->name,
                'code' => array_key_exists('code', $data) ? $data['code'] : $unit->code,
                'description' => array_key_exists('description', $data) ? $data['description'] : $unit->description,
                'sort_order' => $data['sort_order'] ?? $unit->sort_order,
                'settings' => array_key_exists('settings', $data) ? $data['settings'] : $unit->settings,
                'is_active' => $data['is_active'] ?? $unit->is_active,
            ]);

            $unit->save();

            Log::info('Unità organizzativa aggiornata', [
                'unit_id' => $unit->id,
                'changes' => $unit->getChanges(),
            ]);

            return $unit->fresh(['type', 'parent']);
        });
    }

    /**
     * Sposta un'unità sotto un nuovo parent.
     *
     * @param OrganizationalUnit $unit Unità da spostare
     * @param OrganizationalUnit|null $newParent Nuovo parent (null per root)
     * @return OrganizationalUnit
     * @throws InvalidArgumentException
     */
    public function moveUnit(OrganizationalUnit $unit, ?OrganizationalUnit $newParent): OrganizationalUnit
    {
        // Verifica se lo spostamento è valido
        if (!$unit->canMoveTo($newParent)) {
            throw new InvalidArgumentException('Spostamento non valido: creerebbe un ciclo o viola i vincoli di tipo.');
        }

        // Se il parent non cambia, non fare nulla
        if ($unit->parent_id === $newParent?->id) {
            return $unit;
        }

        return DB::transaction(function () use ($unit, $newParent) {
            $oldParentId = $unit->parent_id;

            // Ottieni tutti i discendenti (per aggiornare path e depth)
            $descendants = $unit->descendants()->get();

            // Aggiorna la closure table
            UnitClosure::updateAfterMove($unit, $newParent);

            // Calcola nuovi path e depth
            if ($newParent) {
                $newPath = $newParent->path 
                    ? $newParent->path . '.' . $newParent->id 
                    : (string) $newParent->id;
                $newDepth = $newParent->depth + 1;
            } else {
                $newPath = '';
                $newDepth = 0;
            }

            $depthDiff = $newDepth - $unit->depth;
            $oldPath = $unit->path ? $unit->path . '.' . $unit->id : (string) $unit->id;
            $newFullPath = $newPath ? $newPath . '.' . $unit->id : (string) $unit->id;

            // Aggiorna l'unità
            $unit->update([
                'parent_id' => $newParent?->id,
                'path' => $newPath,
                'depth' => $newDepth,
            ]);

            // Aggiorna tutti i discendenti
            foreach ($descendants as $descendant) {
                $descendantNewPath = str_replace($oldPath, $newFullPath, $descendant->path);
                $descendant->update([
                    'path' => $descendantNewPath,
                    'depth' => $descendant->depth + $depthDiff,
                ]);
            }

            Log::info('Unità organizzativa spostata', [
                'unit_id' => $unit->id,
                'old_parent_id' => $oldParentId,
                'new_parent_id' => $newParent?->id,
            ]);

            return $unit->fresh(['type', 'parent']);
        });
    }

    /**
     * Elimina un'unità organizzativa.
     *
     * @param OrganizationalUnit $unit Unità da eliminare
     * @param string $childStrategy Strategia per i figli: 'orphan' (sposta a root), 'cascade' (elimina), 'promote' (sposta al parent)
     * @return bool
     * @throws InvalidArgumentException
     */
    public function deleteUnit(OrganizationalUnit $unit, string $childStrategy = 'orphan'): bool
    {
        if (!in_array($childStrategy, ['orphan', 'cascade', 'promote'])) {
            throw new InvalidArgumentException("Strategia non valida: {$childStrategy}");
        }

        return DB::transaction(function () use ($unit, $childStrategy) {
            $children = $unit->children()->get();

            // Gestisci i figli secondo la strategia
            switch ($childStrategy) {
                case 'cascade':
                    // Elimina ricorsivamente tutti i discendenti
                    foreach ($children as $child) {
                        $this->deleteUnit($child, 'cascade');
                    }
                    break;

                case 'promote':
                    // Sposta i figli al parent dell'unità eliminata
                    foreach ($children as $child) {
                        $this->moveUnit($child, $unit->parent);
                    }
                    break;

                case 'orphan':
                default:
                    // Sposta i figli a root
                    foreach ($children as $child) {
                        $this->moveUnit($child, null);
                    }
                    break;
            }

            // Rimuovi le assegnazioni
            $unit->assignments()->delete();

            // Rimuovi dalla closure table
            UnitClosure::removeForNode($unit);

            // Soft delete dell'unità
            $unit->delete();

            Log::info('Unità organizzativa eliminata', [
                'unit_id' => $unit->id,
                'name' => $unit->name,
                'child_strategy' => $childStrategy,
            ]);

            return true;
        });
    }

    /**
     * Riordina le unità figlie di un parent.
     *
     * @param OrganizationalUnit|null $parent Parent delle unità da riordinare (null per root)
     * @param array $orderedIds Array ordinato degli ID delle unità
     * @return bool
     */
    public function reorderChildren(?OrganizationalUnit $parent, array $orderedIds): bool
    {
        return DB::transaction(function () use ($parent, $orderedIds) {
            foreach ($orderedIds as $index => $unitId) {
                OrganizationalUnit::where('id', $unitId)
                    ->where('parent_id', $parent?->id)
                    ->update(['sort_order' => $index]);
            }

            return true;
        });
    }

    /**
     * Ricostruisce la closure table.
     * Utile per riparare inconsistenze.
     */
    public function rebuildClosureTable(): void
    {
        UnitClosure::rebuildAll();
        Log::info('Closure table ricostruita');
    }

    /**
     * Verifica l'integrità della gerarchia.
     *
     * @return array Array di errori trovati
     */
    public function checkIntegrity(): array
    {
        $errors = [];

        // Verifica closure table
        $closureErrors = UnitClosure::checkIntegrity();
        $errors = array_merge($errors, $closureErrors);

        // Verifica path consistency
        $units = OrganizationalUnit::with('parent')->get();
        foreach ($units as $unit) {
            $expectedPath = $this->calculateExpectedPath($unit);
            if ($unit->path !== $expectedPath) {
                $errors[] = "Path inconsistente per unità {$unit->id}: trovato '{$unit->path}', atteso '{$expectedPath}'";
            }

            $expectedDepth = $this->calculateExpectedDepth($unit);
            if ($unit->depth !== $expectedDepth) {
                $errors[] = "Depth inconsistente per unità {$unit->id}: trovato {$unit->depth}, atteso {$expectedDepth}";
            }
        }

        // Verifica cicli (non dovrebbero esistere)
        foreach ($units as $unit) {
            if ($unit->parent_id && $this->detectCycle($unit)) {
                $errors[] = "Ciclo rilevato per unità {$unit->id}";
            }
        }

        return $errors;
    }

    /**
     * Ripara le inconsistenze nella gerarchia.
     *
     * @return array Array di correzioni applicate
     */
    public function repairIntegrity(): array
    {
        $repairs = [];

        return DB::transaction(function () use (&$repairs) {
            // Ricalcola path e depth per tutte le unità
            $units = OrganizationalUnit::orderBy('depth')->get();
            foreach ($units as $unit) {
                $expectedPath = $this->calculateExpectedPath($unit);
                $expectedDepth = $this->calculateExpectedDepth($unit);

                if ($unit->path !== $expectedPath || $unit->depth !== $expectedDepth) {
                    $unit->update([
                        'path' => $expectedPath,
                        'depth' => $expectedDepth,
                    ]);
                    $repairs[] = "Corretti path/depth per unità {$unit->id}";
                }
            }

            // Ricostruisci closure table
            $this->rebuildClosureTable();
            $repairs[] = "Closure table ricostruita";

            return $repairs;
        });
    }

    // =========================================================================
    // METODI PER QUERY ALBERO
    // =========================================================================

    /**
     * Ottiene l'albero completo per un utente.
     *
     * @param User|null $user Utente (null per albero completo senza filtri)
     * @param bool $activeOnly Solo unità attive
     * @param bool $includeMilitari Includere militari come nodi foglia
     * @return Collection
     */
    public function getTreeForUser(?User $user = null, bool $activeOnly = true, bool $includeMilitari = false): Collection
    {
        $query = OrganizationalUnit::roots()->with(['type', 'activeChildren.type']);

        if ($activeOnly) {
            $query->active();
        }

        $roots = $query->orderBy('sort_order')->orderBy('name')->get();

        return $roots->map(fn($root) => $this->buildTreeNode($root, $user, $activeOnly, null, 0, $includeMilitari));
    }

    /**
     * Ottiene il sotto-albero a partire da un nodo.
     *
     * @param OrganizationalUnit $root Nodo radice
     * @param int|null $maxDepth Profondità massima (null per illimitata)
     * @param bool $activeOnly Solo unità attive
     * @return array
     */
    public function getSubtree(OrganizationalUnit $root, ?int $maxDepth = null, bool $activeOnly = true): array
    {
        return $this->buildTreeNode($root, null, $activeOnly, $maxDepth);
    }

    /**
     * Cerca unità per nome o codice.
     *
     * @param string $query Query di ricerca
     * @param int $limit Limite risultati
     * @return Collection
     */
    public function searchUnits(string $query, int $limit = 20): Collection
    {
        return OrganizationalUnit::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('code', 'like', "%{$query}%");
        })
            ->active()
            ->with('type')
            ->orderBy('depth')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Ottiene le statistiche aggregate per un'unità.
     *
     * @param OrganizationalUnit $unit
     * @return array
     */
    public function getUnitStats(OrganizationalUnit $unit): array
    {
        try {
            $descendantIds = $unit->descendants()->pluck('organizational_units.id')->push($unit->id)->values();

            return [
                'total_descendants' => $unit->descendants()->count(),
                'direct_children' => $unit->children()->count(),
                'total_militari' => UnitAssignment::whereIn('unit_id', $descendantIds)
                    ->forMilitari()
                    ->active()
                    ->count(),
                'total_users' => UnitAssignment::whereIn('unit_id', $descendantIds)
                    ->forUsers()
                    ->active()
                    ->count(),
                'depth' => $unit->depth,
                'is_leaf' => $unit->isLeaf(),
            ];
        } catch (\Throwable $e) {
            Log::warning('getUnitStats fallback per unità ' . $unit->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'total_descendants' => 0,
                'direct_children' => 0,
                'total_militari' => 0,
                'total_users' => 0,
                'depth' => $unit->depth ?? 0,
                'is_leaf' => true,
            ];
        }
    }

    // =========================================================================
    // METODI PRIVATI
    // =========================================================================

    /**
     * Risolve un tipo da ID, codice o istanza.
     */
    private function resolveType(mixed $type): ?OrganizationalUnitType
    {
        if ($type instanceof OrganizationalUnitType) {
            return $type;
        }

        if (is_int($type)) {
            return OrganizationalUnitType::find($type);
        }

        if (is_string($type)) {
            return OrganizationalUnitType::where('code', $type)->first();
        }

        return null;
    }

    /**
     * Ottiene il prossimo sort_order per i figli di un parent.
     */
    private function getNextSortOrder(?OrganizationalUnit $parent): int
    {
        return OrganizationalUnit::where('parent_id', $parent?->id)->max('sort_order') + 1;
    }

    /**
     * Calcola il path atteso per un'unità.
     */
    private function calculateExpectedPath(OrganizationalUnit $unit): string
    {
        if (!$unit->parent_id) {
            return '';
        }

        $parent = $unit->parent;
        if (!$parent) {
            return '';
        }

        return $parent->path 
            ? $parent->path . '.' . $parent->id 
            : (string) $parent->id;
    }

    /**
     * Calcola la profondità attesa per un'unità.
     */
    private function calculateExpectedDepth(OrganizationalUnit $unit): int
    {
        if (!$unit->parent_id) {
            return 0;
        }

        $parent = $unit->parent;
        return $parent ? $parent->depth + 1 : 0;
    }

    /**
     * Rileva se c'è un ciclo nella gerarchia.
     */
    private function detectCycle(OrganizationalUnit $unit, array $visited = []): bool
    {
        if (in_array($unit->id, $visited)) {
            return true;
        }

        if (!$unit->parent_id) {
            return false;
        }

        $visited[] = $unit->id;
        $parent = OrganizationalUnit::find($unit->parent_id);

        return $parent ? $this->detectCycle($parent, $visited) : false;
    }

    /**
     * Costruisce un nodo dell'albero con i suoi figli.
     * 
     * @param OrganizationalUnit $unit Unità corrente
     * @param User|null $user Utente
     * @param bool $activeOnly Solo unità attive
     * @param int|null $maxDepth Profondità massima
     * @param int $currentDepth Profondità corrente
     * @param bool $includeMilitari Includere militari come nodi foglia
     */
    private function buildTreeNode(
        OrganizationalUnit $unit, 
        ?User $user, 
        bool $activeOnly, 
        ?int $maxDepth = null,
        int $currentDepth = 0,
        bool $includeMilitari = false
    ): array {
        $data = $unit->toTreeArray(false);

        // Verifica se caricare i figli
        $loadChildren = $maxDepth === null || $currentDepth < $maxDepth;

        if ($loadChildren) {
            $childrenQuery = $activeOnly 
                ? $unit->activeChildren() 
                : $unit->children();

            $children = $childrenQuery->with('type')->get();

            $data['children'] = $children->map(
                fn($child) => $this->buildTreeNode($child, $user, $activeOnly, $maxDepth, $currentDepth + 1, $includeMilitari)
            )->values()->toArray();
            
            // Aggiungi militari come nodi foglia se richiesto
            if ($includeMilitari) {
                $militari = \App\Models\Militare::where('organizational_unit_id', $unit->id)
                    ->with(['grado'])
                    ->orderBy('cognome')
                    ->orderBy('nome')
                    ->get();
                
                foreach ($militari as $militare) {
                    $gradoNome = $militare->grado?->sigla ?? $militare->grado?->nome ?? '';
                    $data['children'][] = [
                        'id' => $unit->id, // ID dell'unità parent per riferimento
                        'uuid' => 'militare_' . $militare->id,
                        'name' => trim("{$gradoNome} {$militare->cognome} {$militare->nome}"),
                        'type' => 'militare',
                        'depth' => $currentDepth + 1,
                        'militare_id' => $militare->id,
                        'grado' => $gradoNome,
                        'cognome' => $militare->cognome,
                        'nome' => $militare->nome,
                        'unit_id' => $unit->id,
                        'unit_name' => $unit->name,
                        'children' => [],
                        'has_children' => false,
                    ];
                }
            }
        } else {
            $data['children'] = [];
            $data['has_more_children'] = $unit->children()->exists();
        }

        return $data;
    }

    // =========================================================================
    // SEED CONFIGURAZIONI PER NUOVE UNITÀ
    // =========================================================================

    /**
     * Copia le configurazioni da un'unità template a una nuova unità.
     * 
     * Copia:
     * - configurazione_campi_anagrafica (campi personalizzati anagrafica)
     * - configurazione_ruolini (stati presenza per impegni)
     * - codici_servizio_gerarchia (opzionale, solo se includeCptCodes = true)
     *
     * @param int $newUnitId ID della nuova unità da configurare
     * @param int $templateUnitId ID dell'unità template da copiare
     * @param bool $includeCptCodes Se copiare anche i codici CPT
     * @return array Riepilogo delle operazioni eseguite
     * @throws InvalidArgumentException
     */
    public function seedConfigurationsFromTemplate(
        int $newUnitId, 
        int $templateUnitId, 
        bool $includeCptCodes = false
    ): array {
        $newUnit = OrganizationalUnit::find($newUnitId);
        $templateUnit = OrganizationalUnit::find($templateUnitId);

        if (!$newUnit) {
            throw new InvalidArgumentException("Unità nuova con ID {$newUnitId} non trovata.");
        }

        if (!$templateUnit) {
            throw new InvalidArgumentException("Unità template con ID {$templateUnitId} non trovata.");
        }

        return DB::transaction(function () use ($newUnitId, $templateUnitId, $newUnit, $templateUnit, $includeCptCodes) {
            $results = [
                'campi_anagrafica' => 0,
                'ruolini' => 0,
                'codici_cpt' => 0,
                'skipped' => [],
            ];

            // 1. Copia configurazione_campi_anagrafica
            $campiTemplate = ConfigurazioneCampoAnagrafica::forUnit($templateUnitId)->get();
            foreach ($campiTemplate as $campo) {
                // Salta se esiste già
                $exists = ConfigurazioneCampoAnagrafica::where('organizational_unit_id', $newUnitId)
                    ->where('nome_campo', $campo->nome_campo)
                    ->exists();

                if ($exists) {
                    $results['skipped'][] = "campo_anagrafica:{$campo->nome_campo}";
                    continue;
                }

                ConfigurazioneCampoAnagrafica::create([
                    'organizational_unit_id' => $newUnitId,
                    'nome_campo' => $campo->nome_campo,
                    'etichetta' => $campo->etichetta,
                    'tipo_campo' => $campo->tipo_campo,
                    'opzioni' => $campo->opzioni,
                    'ordine' => $campo->ordine,
                    'attivo' => $campo->attivo,
                    'obbligatorio' => $campo->obbligatorio,
                    'is_system' => $campo->is_system,
                    'descrizione' => $campo->descrizione,
                ]);
                $results['campi_anagrafica']++;
            }

            // 2. Copia configurazione_ruolini
            $ruoliniTemplate = ConfigurazioneRuolino::forUnit($templateUnitId)->get();
            foreach ($ruoliniTemplate as $ruolino) {
                // Salta se esiste già
                $exists = ConfigurazioneRuolino::where('organizational_unit_id', $newUnitId)
                    ->where('tipo_servizio_id', $ruolino->tipo_servizio_id)
                    ->exists();

                if ($exists) {
                    $results['skipped'][] = "ruolino:{$ruolino->tipo_servizio_id}";
                    continue;
                }

                ConfigurazioneRuolino::create([
                    'organizational_unit_id' => $newUnitId,
                    'tipo_servizio_id' => $ruolino->tipo_servizio_id,
                    'stato_presenza' => $ruolino->stato_presenza,
                    'note' => $ruolino->note,
                ]);
                $results['ruolini']++;
            }

            // 3. Copia codici CPT (opzionale)
            if ($includeCptCodes) {
                $codiciTemplate = CodiciServizioGerarchia::where('organizational_unit_id', $templateUnitId)->get();
                foreach ($codiciTemplate as $codice) {
                    // Salta se esiste già (stesso codice nell'unità)
                    $exists = CodiciServizioGerarchia::where('organizational_unit_id', $newUnitId)
                        ->where('codice', $codice->codice)
                        ->exists();

                    if ($exists) {
                        $results['skipped'][] = "codice_cpt:{$codice->codice}";
                        continue;
                    }

                    CodiciServizioGerarchia::create([
                        'organizational_unit_id' => $newUnitId,
                        'codice' => $codice->codice,
                        'nome' => $codice->nome,
                        'macro_attivita' => $codice->macro_attivita,
                        'tipo_attivita' => $codice->tipo_attivita,
                        'colore' => $codice->colore,
                        'ordine' => $codice->ordine,
                        'attivo' => $codice->attivo,
                    ]);
                    $results['codici_cpt']++;
                }
            }

            Log::info('Configurazioni copiate da template', [
                'new_unit_id' => $newUnitId,
                'new_unit_name' => $newUnit->name,
                'template_unit_id' => $templateUnitId,
                'template_unit_name' => $templateUnit->name,
                'results' => $results,
            ]);

            return $results;
        });
    }

    /**
     * Ottiene l'ID dell'unità template di default per le nuove unità.
     * Per default cerca "Battaglione Leonessa" o la prima unità di tipo battaglione.
     *
     * @return int|null
     */
    public function getDefaultTemplateUnitId(): ?int
    {
        // Prima cerca Battaglione Leonessa
        $leonessa = OrganizationalUnit::where('name', 'like', '%Leonessa%')
            ->where('depth', 1)
            ->active()
            ->first();

        if ($leonessa) {
            return $leonessa->id;
        }

        // Fallback: primo battaglione attivo
        $firstBattalion = OrganizationalUnit::where('depth', 1)
            ->active()
            ->orderBy('id')
            ->first();

        return $firstBattalion?->id;
    }
}
