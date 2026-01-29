<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modello per la Closure Table delle unità organizzative.
 * 
 * Memorizza tutte le relazioni ancestor-descendant per query gerarchiche efficienti.
 * Per ogni nodo nell'albero, questa tabella contiene:
 * - Una riga per sé stesso (ancestor = descendant, depth = 0)
 * - Una riga per ogni antenato (depth = distanza dall'antenato)
 *
 * @property int $id
 * @property int $ancestor_id
 * @property int $descendant_id
 * @property int $depth
 */
class UnitClosure extends Model
{
    protected $table = 'unit_closure';

    public $timestamps = false;

    protected $fillable = [
        'ancestor_id',
        'descendant_id',
        'depth',
    ];

    protected $casts = [
        'depth' => 'integer',
    ];

    // =========================================================================
    // RELAZIONI
    // =========================================================================

    /**
     * Nodo antenato.
     */
    public function ancestor(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'ancestor_id');
    }

    /**
     * Nodo discendente.
     */
    public function descendant(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'descendant_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Filtra per nodo antenato.
     */
    public function scopeForAncestor(Builder $query, int $ancestorId): Builder
    {
        return $query->where('ancestor_id', $ancestorId);
    }

    /**
     * Filtra per nodo discendente.
     */
    public function scopeForDescendant(Builder $query, int $descendantId): Builder
    {
        return $query->where('descendant_id', $descendantId);
    }

    /**
     * Filtra per profondità specifica.
     */
    public function scopeAtDepth(Builder $query, int $depth): Builder
    {
        return $query->where('depth', $depth);
    }

    /**
     * Escludi self-reference (depth = 0).
     */
    public function scopeExcludeSelf(Builder $query): Builder
    {
        return $query->where('depth', '>', 0);
    }

    /**
     * Solo self-reference (depth = 0).
     */
    public function scopeOnlySelf(Builder $query): Builder
    {
        return $query->where('depth', 0);
    }

    /**
     * Figli diretti (depth = 1).
     */
    public function scopeDirectChildren(Builder $query): Builder
    {
        return $query->where('depth', 1);
    }

    // =========================================================================
    // METODI STATICI - GESTIONE CLOSURE
    // =========================================================================

    /**
     * Inserisce i record di closure per un nuovo nodo.
     * 
     * @param OrganizationalUnit $unit Il nuovo nodo
     */
    public static function insertForNode(OrganizationalUnit $unit): void
    {
        // Self-reference
        static::create([
            'ancestor_id' => $unit->id,
            'descendant_id' => $unit->id,
            'depth' => 0,
        ]);

        // Se ha un parent, copia tutti gli antenati del parent
        if ($unit->parent_id) {
            $ancestorRecords = static::forDescendant($unit->parent_id)->get();
            
            foreach ($ancestorRecords as $record) {
                static::create([
                    'ancestor_id' => $record->ancestor_id,
                    'descendant_id' => $unit->id,
                    'depth' => $record->depth + 1,
                ]);
            }
        }
    }

    /**
     * Rimuove i record di closure per un nodo (prima di eliminazione o spostamento).
     * 
     * @param OrganizationalUnit $unit Il nodo da rimuovere
     * @param bool $keepSelf Se true, mantiene il self-reference
     */
    public static function removeForNode(OrganizationalUnit $unit, bool $keepSelf = false): void
    {
        $query = static::where('descendant_id', $unit->id);
        
        if ($keepSelf) {
            $query->where('depth', '>', 0);
        }
        
        $query->delete();
    }

    /**
     * Aggiorna la closure table dopo lo spostamento di un nodo.
     * 
     * @param OrganizationalUnit $unit Il nodo spostato
     * @param OrganizationalUnit|null $newParent Il nuovo parent (null per root)
     */
    public static function updateAfterMove(OrganizationalUnit $unit, ?OrganizationalUnit $newParent): void
    {
        // Ottieni tutti i discendenti (incluso self)
        $descendantIds = static::forAncestor($unit->id)->pluck('descendant_id')->toArray();

        // Rimuovi i vecchi link degli antenati per il sotto-albero
        static::whereIn('descendant_id', $descendantIds)
            ->where('ancestor_id', '!=', function ($query) use ($descendantIds) {
                $query->select('ancestor_id')
                    ->from('unit_closure')
                    ->whereIn('ancestor_id', $descendantIds);
            })
            ->delete();

        // Se c'è un nuovo parent, aggiungi i nuovi link degli antenati
        if ($newParent) {
            $newAncestors = static::forDescendant($newParent->id)->get();
            
            foreach ($descendantIds as $descendantId) {
                $descendantDepthFromUnit = static::where('ancestor_id', $unit->id)
                    ->where('descendant_id', $descendantId)
                    ->value('depth') ?? 0;

                foreach ($newAncestors as $ancestorRecord) {
                    static::create([
                        'ancestor_id' => $ancestorRecord->ancestor_id,
                        'descendant_id' => $descendantId,
                        'depth' => $ancestorRecord->depth + 1 + $descendantDepthFromUnit,
                    ]);
                }
            }
        }
    }

    /**
     * Ricostruisce l'intera closure table.
     * Utile per riparare inconsistenze o dopo import massivi.
     */
    public static function rebuildAll(): void
    {
        // Svuota la tabella
        static::truncate();

        // Inserisci per tutti i nodi
        $units = OrganizationalUnit::withTrashed()->orderBy('depth')->get();

        foreach ($units as $unit) {
            static::insertForNode($unit);
        }
    }

    /**
     * Verifica l'integrità della closure table.
     * 
     * @return array Array di errori trovati (vuoto se tutto ok)
     */
    public static function checkIntegrity(): array
    {
        $errors = [];

        // Verifica che ogni nodo abbia il self-reference
        $missingSelfs = \DB::table('organizational_units as ou')
            ->leftJoin('unit_closure as uc', function ($join) {
                $join->on('ou.id', '=', 'uc.ancestor_id')
                    ->on('ou.id', '=', 'uc.descendant_id')
                    ->where('uc.depth', '=', 0);
            })
            ->whereNull('uc.id')
            ->whereNull('ou.deleted_at')
            ->pluck('ou.id')
            ->toArray();

        if (!empty($missingSelfs)) {
            $errors[] = "Nodi senza self-reference: " . implode(', ', $missingSelfs);
        }

        // Verifica che i depth siano corretti
        $wrongDepths = static::join('organizational_units as a', 'unit_closure.ancestor_id', '=', 'a.id')
            ->join('organizational_units as d', 'unit_closure.descendant_id', '=', 'd.id')
            ->whereRaw('unit_closure.depth != (d.depth - a.depth)')
            ->count();

        if ($wrongDepths > 0) {
            $errors[] = "Record con depth errato: {$wrongDepths}";
        }

        return $errors;
    }
}
