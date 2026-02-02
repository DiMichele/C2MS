<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Modello per le unità organizzative.
 * 
 * Implementa una struttura ad albero gerarchica usando:
 * - Adjacency List (parent_id) per relazioni dirette
 * - Materialized Path (path) per query su sotto-alberi
 * - Closure Table (unit_closure) per query su antenati/discendenti
 *
 * @property int $id
 * @property string $uuid
 * @property int $type_id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property string $path
 * @property int $depth
 * @property int $sort_order
 * @property array|null $settings
 * @property int|null $legacy_compagnia_id
 * @property int|null $legacy_plotone_id
 * @property int|null $legacy_polo_id
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class OrganizationalUnit extends Model
{
    use SoftDeletes;

    protected $table = 'organizational_units';

    protected $fillable = [
        'uuid',
        'type_id',
        'parent_id',
        'name',
        'code',
        'description',
        'path',
        'depth',
        'sort_order',
        'settings',
        'legacy_compagnia_id',
        'legacy_plotone_id',
        'legacy_polo_id',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'depth' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'path' => '',
        'depth' => 0,
        'sort_order' => 0,
        'is_active' => true,
    ];

    // =========================================================================
    // BOOT
    // =========================================================================

    protected static function boot()
    {
        parent::boot();

        // Genera UUID automaticamente
        static::creating(function (OrganizationalUnit $unit) {
            if (empty($unit->uuid)) {
                $unit->uuid = (string) Str::uuid();
            }
        });
    }

    // =========================================================================
    // RELAZIONI - STRUTTURA ALBERO
    // =========================================================================

    /**
     * Tipo di unità.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnitType::class, 'type_id');
    }

    /**
     * Unità padre (Adjacency List).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'parent_id');
    }

    /**
     * Unità figlie dirette (Adjacency List).
     */
    public function children(): HasMany
    {
        return $this->hasMany(OrganizationalUnit::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Unità figlie attive.
     */
    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    /**
     * Tutti gli antenati (via Closure Table).
     */
    public function ancestors(): BelongsToMany
    {
        return $this->belongsToMany(
            OrganizationalUnit::class,
            'unit_closure',
            'descendant_id',
            'ancestor_id'
        )
            ->withPivot('depth')
            ->where('unit_closure.depth', '>', 0) // Escludi self
            ->orderByDesc('unit_closure.depth');
    }

    /**
     * Tutti i discendenti (via Closure Table).
     */
    public function descendants(): BelongsToMany
    {
        return $this->belongsToMany(
            OrganizationalUnit::class,
            'unit_closure',
            'ancestor_id',
            'descendant_id'
        )
            ->withPivot('depth')
            ->where('unit_closure.depth', '>', 0) // Escludi self
            ->orderBy('unit_closure.depth');
    }

    /**
     * Discendenti diretti (figli) via Closure Table.
     */
    public function directDescendants(): BelongsToMany
    {
        return $this->descendants()->wherePivot('depth', 1);
    }

    // =========================================================================
    // RELAZIONI - LEGACY
    // =========================================================================

    /**
     * Compagnia legacy (per migrazione).
     */
    public function legacyCompagnia(): BelongsTo
    {
        return $this->belongsTo(Compagnia::class, 'legacy_compagnia_id');
    }

    /**
     * Plotone legacy (per migrazione).
     */
    public function legacyPlotone(): BelongsTo
    {
        return $this->belongsTo(Plotone::class, 'legacy_plotone_id');
    }

    /**
     * Polo legacy (per migrazione).
     */
    public function legacyPolo(): BelongsTo
    {
        return $this->belongsTo(Polo::class, 'legacy_polo_id');
    }

    // =========================================================================
    // RELAZIONI - ASSEGNAZIONI POLIMORFICHE
    // =========================================================================

    /**
     * Tutte le assegnazioni a questa unità.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(UnitAssignment::class, 'unit_id');
    }

    /**
     * Militari assegnati a questa unità.
     */
    public function militari(): MorphToMany
    {
        return $this->morphedByMany(Militare::class, 'assignable', 'unit_assignments', 'unit_id')
            ->withPivot(['role', 'is_primary', 'start_date', 'end_date', 'notes'])
            ->withTimestamps();
    }

    /**
     * Utenti assegnati a questa unità.
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'assignable', 'unit_assignments', 'unit_id')
            ->withPivot(['role', 'is_primary', 'start_date', 'end_date', 'notes'])
            ->withTimestamps();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Solo unità attive.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Solo root (senza parent).
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Solo foglie (senza figli).
     */
    public function scopeLeaves(Builder $query): Builder
    {
        return $query->whereDoesntHave('children');
    }

    /**
     * Filtra per tipo.
     */
    public function scopeOfType(Builder $query, string|int|OrganizationalUnitType $type): Builder
    {
        if ($type instanceof OrganizationalUnitType) {
            return $query->where('type_id', $type->id);
        }

        if (is_string($type)) {
            return $query->whereHas('type', fn($q) => $q->where('code', $type));
        }

        return $query->where('type_id', $type);
    }

    /**
     * Filtra per profondità.
     */
    public function scopeAtDepth(Builder $query, int $depth): Builder
    {
        return $query->where('depth', $depth);
    }

    /**
     * Filtra per path (discendenti di un nodo).
     */
    public function scopeUnderPath(Builder $query, string $path): Builder
    {
        return $query->where('path', 'like', $path . '.%');
    }

    /**
     * Ordina per posizione nell'albero.
     */
    public function scopeTreeOrder(Builder $query): Builder
    {
        return $query->orderBy('path')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Filtra per compagnia legacy.
     */
    public function scopeForLegacyCompagnia(Builder $query, int $compagniaId): Builder
    {
        return $query->where('legacy_compagnia_id', $compagniaId);
    }

    // =========================================================================
    // METODI - VERIFICA POSIZIONE
    // =========================================================================

    /**
     * È un nodo root?
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * È un nodo foglia (senza figli)?
     */
    public function isLeaf(): bool
    {
        return $this->children()->doesntExist();
    }

    /**
     * È antenato di un altro nodo?
     */
    public function isAncestorOf(OrganizationalUnit $unit): bool
    {
        return $unit->ancestors()->where('organizational_units.id', $this->id)->exists();
    }

    /**
     * È discendente di un altro nodo?
     */
    public function isDescendantOf(OrganizationalUnit $unit): bool
    {
        return $this->ancestors()->where('organizational_units.id', $unit->id)->exists();
    }

    /**
     * È fratello di un altro nodo (stesso parent)?
     */
    public function isSiblingOf(OrganizationalUnit $unit): bool
    {
        return $this->parent_id === $unit->parent_id && $this->id !== $unit->id;
    }

    // =========================================================================
    // METODI - NAVIGAZIONE ALBERO
    // =========================================================================

    /**
     * Ottiene il percorso come array di ID.
     */
    public function getPathIds(): array
    {
        if (empty($this->path)) {
            return [$this->id];
        }

        $ids = array_map('intval', explode('.', $this->path));
        $ids[] = $this->id;
        
        return $ids;
    }

    /**
     * Ottiene tutti gli antenati come Collection (dalla root a questo nodo).
     */
    public function getAncestorsAndSelf(): Collection
    {
        return $this->ancestors()
            ->orderBy('unit_closure.depth', 'desc')
            ->get()
            ->push($this);
    }

    /**
     * Ottiene il breadcrumb (path con nomi).
     */
    public function getBreadcrumb(): Collection
    {
        return $this->getAncestorsAndSelf()->map(fn($unit) => [
            'id' => $unit->id,
            'uuid' => $unit->uuid,
            'name' => $unit->name,
            'type' => $unit->type?->name ?? null,
        ]);
    }

    /**
     * Ottiene la root dell'albero.
     */
    public function getRoot(): OrganizationalUnit
    {
        if ($this->isRoot()) {
            return $this;
        }

        return $this->ancestors()
            ->orderByDesc('unit_closure.depth')
            ->first() ?? $this;
    }

    /**
     * Ottiene tutti i fratelli (stesso parent, escluso self).
     */
    public function getSiblings(): Collection
    {
        return static::where('parent_id', $this->parent_id)
            ->where('id', '!=', $this->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Ottiene il numero totale di discendenti.
     */
    public function getDescendantCount(): int
    {
        return $this->descendants()->count();
    }

    /**
     * Ottiene gli ID di tutti i discendenti.
     * 
     * @param bool $includeSelf Se true, include anche l'ID di questa unità
     * @return array
     */
    public function getDescendantIds(bool $includeSelf = false): array
    {
        $ids = $this->descendants()->pluck('organizational_units.id')->toArray();
        
        if ($includeSelf) {
            array_unshift($ids, $this->id);
        }
        
        return $ids;
    }

    /**
     * Ottiene gli ID di tutti gli antenati.
     * 
     * @param bool $includeSelf Se true, include anche l'ID di questa unità
     * @return array
     */
    public function getAncestorIds(bool $includeSelf = false): array
    {
        $ids = $this->ancestors()->pluck('organizational_units.id')->toArray();
        
        if ($includeSelf) {
            $ids[] = $this->id;
        }
        
        return $ids;
    }

    // =========================================================================
    // METODI - MODIFICA ALBERO
    // =========================================================================

    /**
     * Calcola e imposta path e depth dal parent.
     */
    public function updatePathAndDepth(): self
    {
        if ($this->parent_id === null) {
            $this->path = '';
            $this->depth = 0;
        } else {
            $parent = $this->parent()->first();
            if ($parent) {
                $this->path = $parent->path
                    ? $parent->path . '.' . $parent->id
                    : (string) $parent->id;
                $this->depth = $parent->depth + 1;
            }
        }

        return $this;
    }

    /**
     * Verifica se può essere spostato sotto un nuovo parent.
     */
    public function canMoveTo(?OrganizationalUnit $newParent): bool
    {
        // Può sempre andare a root
        if ($newParent === null) {
            return true;
        }

        // Non può essere parent di se stesso
        if ($newParent->id === $this->id) {
            return false;
        }

        // Non può essere spostato sotto un proprio discendente (creerebbe ciclo)
        if ($newParent->isDescendantOf($this)) {
            return false;
        }

        // Verifica vincoli di tipo (null-safe: tipo può essere assente)
        if ($this->type && $newParent->type) {
            if (!$newParent->type?->canContain($this->type)) {
                return false;
            }
        }

        return true;
    }

    // =========================================================================
    // METODI - SETTINGS
    // =========================================================================

    /**
     * Ottiene un'impostazione specifica.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Imposta un valore nelle impostazioni.
     */
    public function setSetting(string $key, mixed $value): self
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        
        return $this;
    }

    // =========================================================================
    // METODI - CONVERSIONE
    // =========================================================================

    /**
     * Converte in array per visualizzazione tree.
     */
    public function toTreeArray(bool $includeChildren = false, int $maxDepth = 10): array
    {
        $data = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type ? [
                'code' => $this->type?->code,
                'name' => $this->type?->name,
                'icon' => $this->type?->icon,
                'color' => $this->type?->color,
            ] : null,
            'depth' => $this->depth,
            'is_active' => $this->is_active,
            'has_children' => !$this->isLeaf(),
        ];

        if ($includeChildren && $maxDepth > 0) {
            $data['children'] = $this->activeChildren
                ->map(fn($child) => $child->toTreeArray(true, $maxDepth - 1))
                ->values()
                ->toArray();
        }

        return $data;
    }

    // =========================================================================
    // METODI STATICI
    // =========================================================================

    /**
     * Trova per UUID.
     */
    public static function findByUuid(string $uuid): ?self
    {
        return static::where('uuid', $uuid)->first();
    }

    /**
     * Ottiene tutte le root attive.
     */
    public static function getRoots(): Collection
    {
        return static::roots()
            ->active()
            ->with('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Costruisce l'albero completo partendo dalle root.
     */
    public static function getFullTree(): Collection
    {
        return static::getRoots()->map(fn($root) => $root->toTreeArray(true));
    }
}
