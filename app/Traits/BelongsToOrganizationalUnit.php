<?php

namespace App\Traits;

use App\Models\OrganizationalUnit;
use App\Models\UnitAssignment;
use App\Scopes\OrganizationalUnitScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

/**
 * Trait per i modelli che appartengono alla gerarchia organizzativa.
 * 
 * Questo trait:
 * - Applica automaticamente OrganizationalUnitScope per il filtraggio
 * - Fornisce relazioni con le unità organizzative
 * - Auto-assegna l'unità durante la creazione
 * - Fornisce metodi helper per la gestione delle assegnazioni
 * 
 * Modalità di utilizzo:
 * 1. Per modelli con colonna organizational_unit_id (appartenenza diretta)
 * 2. Per modelli con relazione polimorfica via unit_assignments (assegnazioni multiple)
 */
trait BelongsToOrganizationalUnit
{
    /**
     * Boot del trait: registra lo scope e gli eventi.
     */
    public static function bootBelongsToOrganizationalUnit(): void
    {
        // Applica lo scope globale per il filtraggio automatico
        static::addGlobalScope(new OrganizationalUnitScope());

        // Auto-assegna l'unità durante la creazione
        static::creating(function ($model) {
            $model->autoAssignUnit();
        });
    }

    // =========================================================================
    // RELAZIONI
    // =========================================================================

    /**
     * Relazione diretta con un'unità organizzativa (se il modello ha organizational_unit_id).
     */
    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'organizational_unit_id');
    }

    /**
     * Relazione polimorfica con le unità organizzative (via unit_assignments).
     */
    public function organizationalUnits(): MorphToMany
    {
        return $this->morphToMany(
            OrganizationalUnit::class,
            'assignable',
            'unit_assignments',
            'assignable_id',
            'unit_id'
        )
            ->withPivot(['role', 'is_primary', 'start_date', 'end_date', 'notes'])
            ->withTimestamps();
    }

    /**
     * Tutte le assegnazioni alle unità.
     */
    public function unitAssignments(): MorphMany
    {
        return $this->morphMany(UnitAssignment::class, 'assignable');
    }

    /**
     * Unità primaria (via assegnazioni).
     */
    public function primaryUnit(): ?OrganizationalUnit
    {
        $assignment = $this->unitAssignments()
            ->where('is_primary', true)
            ->active()
            ->first();

        return $assignment?->unit;
    }

    // =========================================================================
    // METODI DI ASSEGNAZIONE
    // =========================================================================

    /**
     * Assegna il modello a un'unità organizzativa.
     *
     * @param OrganizationalUnit|int $unit Unità o ID
     * @param string $role Ruolo nell'unità
     * @param bool $isPrimary È l'assegnazione primaria?
     * @param array $options Opzioni aggiuntive (start_date, end_date, notes)
     * @return UnitAssignment
     */
    public function assignToUnit(
        OrganizationalUnit|int $unit,
        string $role = 'membro',
        bool $isPrimary = false,
        array $options = []
    ): UnitAssignment {
        $unitId = $unit instanceof OrganizationalUnit ? $unit->id : $unit;

        // Se deve essere primario, rimuovi il flag dalle altre assegnazioni
        if ($isPrimary) {
            $this->unitAssignments()->update(['is_primary' => false]);
        }

        // Verifica se esiste già un'assegnazione a questa unità
        $existing = $this->unitAssignments()
            ->where('unit_id', $unitId)
            ->first();

        if ($existing) {
            // Aggiorna l'assegnazione esistente
            $existing->update([
                'role' => $role,
                'is_primary' => $isPrimary,
                'start_date' => $options['start_date'] ?? $existing->start_date,
                'end_date' => $options['end_date'] ?? $existing->end_date,
                'notes' => $options['notes'] ?? $existing->notes,
            ]);
            return $existing;
        }

        // Crea nuova assegnazione
        return $this->unitAssignments()->create([
            'unit_id' => $unitId,
            'role' => $role,
            'is_primary' => $isPrimary,
            'start_date' => $options['start_date'] ?? null,
            'end_date' => $options['end_date'] ?? null,
            'notes' => $options['notes'] ?? null,
        ]);
    }

    /**
     * Rimuove l'assegnazione da un'unità.
     *
     * @param OrganizationalUnit|int $unit Unità o ID
     * @return bool
     */
    public function removeFromUnit(OrganizationalUnit|int $unit): bool
    {
        $unitId = $unit instanceof OrganizationalUnit ? $unit->id : $unit;

        return $this->unitAssignments()
            ->where('unit_id', $unitId)
            ->delete() > 0;
    }

    /**
     * Verifica se il modello è assegnato a un'unità.
     *
     * @param OrganizationalUnit|int $unit Unità o ID
     * @return bool
     */
    public function isAssignedTo(OrganizationalUnit|int $unit): bool
    {
        $unitId = $unit instanceof OrganizationalUnit ? $unit->id : $unit;

        return $this->unitAssignments()
            ->where('unit_id', $unitId)
            ->active()
            ->exists();
    }

    /**
     * Verifica se il modello è assegnato a un'unità o ai suoi discendenti.
     *
     * @param OrganizationalUnit|int $unit Unità o ID
     * @return bool
     */
    public function isUnderUnit(OrganizationalUnit|int $unit): bool
    {
        $unit = $unit instanceof OrganizationalUnit 
            ? $unit 
            : OrganizationalUnit::find($unit);

        if (!$unit) {
            return false;
        }

        // Ottieni tutti gli ID dei discendenti (incluso self)
        $descendantIds = $unit->descendants()->pluck('id')->push($unit->id)->toArray();

        return $this->unitAssignments()
            ->whereIn('unit_id', $descendantIds)
            ->active()
            ->exists();
    }

    // =========================================================================
    // METODI DI VISIBILITÀ
    // =========================================================================

    /**
     * Verifica se il modello è visibile all'utente corrente.
     *
     * @param \App\Models\User|null $user
     * @return bool
     */
    public function isVisibleTo($user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        // Admin vedono tutto
        if (method_exists($user, 'hasRole') && 
            ($user->hasRole('admin') || $user->hasRole('amministratore'))) {
            return true;
        }

        if (method_exists($user, 'hasGlobalVisibility') && $user->hasGlobalVisibility()) {
            return true;
        }

        // Verifica se l'utente ha accesso alle unità del modello
        $modelUnitIds = $this->unitAssignments()
            ->active()
            ->pluck('unit_id')
            ->toArray();

        if (empty($modelUnitIds)) {
            // Se il modello non ha assegnazioni, usa la logica legacy
            return $this->isVisibleToLegacy($user);
        }

        // Verifica se le unità del modello sono tra quelle visibili all'utente
        $userVisibleUnitIds = $this->getUserVisibleUnitIds($user);

        return !empty(array_intersect($modelUnitIds, $userVisibleUnitIds));
    }

    /**
     * Verifica se il modello è modificabile dall'utente.
     *
     * @param \App\Models\User|null $user
     * @return bool
     */
    public function isEditableBy($user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        // Admin possono modificare tutto
        if (method_exists($user, 'hasRole') && 
            ($user->hasRole('admin') || $user->hasRole('amministratore'))) {
            return true;
        }

        // Per ora, modificabile equivale a visibile
        // In futuro si può aggiungere logica più granulare
        return $this->isVisibleTo($user);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope per filtrare per unità specifica.
     */
    public function scopeInUnit($query, OrganizationalUnit|int $unit)
    {
        $unitId = $unit instanceof OrganizationalUnit ? $unit->id : $unit;

        return $query->whereHas('unitAssignments', function ($q) use ($unitId) {
            $q->where('unit_id', $unitId)->active();
        });
    }

    /**
     * Scope per filtrare per unità e discendenti.
     */
    public function scopeUnderUnit($query, OrganizationalUnit|int $unit)
    {
        $unit = $unit instanceof OrganizationalUnit 
            ? $unit 
            : OrganizationalUnit::find($unit);

        if (!$unit) {
            return $query->whereRaw('1 = 0');
        }

        $descendantIds = $unit->descendants()->pluck('id')->push($unit->id)->toArray();

        return $query->whereHas('unitAssignments', function ($q) use ($descendantIds) {
            $q->whereIn('unit_id', $descendantIds)->active();
        });
    }

    /**
     * Scope per bypassare l'OrganizationalUnitScope (solo per admin).
     */
    public function scopeWithoutUnitScope($query)
    {
        return $query->withoutGlobalScope(OrganizationalUnitScope::class);
    }

    // =========================================================================
    // METODI PRIVATI
    // =========================================================================

    /**
     * Auto-assegna l'unità durante la creazione.
     */
    protected function autoAssignUnit(): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        // Se il modello ha organizational_unit_id e non è impostato
        if (in_array('organizational_unit_id', $this->fillable) && empty($this->organizational_unit_id)) {
            // Trova l'unità primaria dell'utente
            $userPrimaryUnit = UnitAssignment::where('assignable_type', get_class($user))
                ->where('assignable_id', $user->id)
                ->where('is_primary', true)
                ->active()
                ->first();

            if ($userPrimaryUnit) {
                $this->organizational_unit_id = $userPrimaryUnit->unit_id;
            }
        }
    }

    /**
     * Ottiene gli ID delle unità visibili all'utente.
     */
    protected function getUserVisibleUnitIds($user): array
    {
        // Implementazione semplificata - in produzione usare cache
        $directUnitIds = UnitAssignment::where('assignable_type', get_class($user))
            ->where('assignable_id', $user->id)
            ->active()
            ->pluck('unit_id')
            ->toArray();

        if (empty($directUnitIds)) {
            return [];
        }

        // Espandi per includere discendenti
        return \DB::table('unit_closure')
            ->whereIn('ancestor_id', $directUnitIds)
            ->pluck('descendant_id')
            ->unique()
            ->toArray();
    }

    /**
     * Verifica visibilità con logica legacy (compagnia_id).
     */
    protected function isVisibleToLegacy($user): bool
    {
        // Se il modello ha compagnia_id
        if (isset($this->compagnia_id) && method_exists($user, 'canAccessCompagnia')) {
            return $user->canAccessCompagnia($this->compagnia_id);
        }

        return false;
    }
}
