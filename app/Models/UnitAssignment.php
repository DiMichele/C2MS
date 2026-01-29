<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Modello per le assegnazioni alle unità organizzative.
 * 
 * Implementa una relazione polimorfica per assegnare
 * qualsiasi entità (Militare, User, etc.) a un'unità organizzativa.
 *
 * @property int $id
 * @property int $unit_id
 * @property string $assignable_type
 * @property int $assignable_id
 * @property string $role
 * @property bool $is_primary
 * @property \Carbon\Carbon|null $start_date
 * @property \Carbon\Carbon|null $end_date
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class UnitAssignment extends Model
{
    protected $table = 'unit_assignments';

    protected $fillable = [
        'unit_id',
        'assignable_type',
        'assignable_id',
        'role',
        'is_primary',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $attributes = [
        'role' => 'membro',
        'is_primary' => false,
    ];

    // =========================================================================
    // COSTANTI - RUOLI
    // =========================================================================

    public const ROLE_COMANDANTE = 'comandante';
    public const ROLE_VICE_COMANDANTE = 'vice_comandante';
    public const ROLE_RESPONSABILE = 'responsabile';
    public const ROLE_MEMBRO = 'membro';
    public const ROLE_ADDETTO = 'addetto';
    public const ROLE_COLLABORATORE = 'collaboratore';

    /**
     * Ottiene tutti i ruoli disponibili.
     */
    public static function getAvailableRoles(): array
    {
        return [
            self::ROLE_COMANDANTE => 'Comandante',
            self::ROLE_VICE_COMANDANTE => 'Vice Comandante',
            self::ROLE_RESPONSABILE => 'Responsabile',
            self::ROLE_MEMBRO => 'Membro',
            self::ROLE_ADDETTO => 'Addetto',
            self::ROLE_COLLABORATORE => 'Collaboratore',
        ];
    }

    // =========================================================================
    // RELAZIONI
    // =========================================================================

    /**
     * Unità organizzativa.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'unit_id');
    }

    /**
     * Entità assegnata (polimorfica).
     */
    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Solo assegnazioni primarie.
     */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    /**
     * Solo assegnazioni attive (date valide).
     */
    public function scopeActive(Builder $query, ?Carbon $date = null): Builder
    {
        $date = $date ?? Carbon::today();

        return $query
            ->where(function ($q) use ($date) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            });
    }

    /**
     * Filtra per ruolo.
     */
    public function scopeWithRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    /**
     * Filtra per tipo di entità assegnabile.
     */
    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('assignable_type', $type);
    }

    /**
     * Filtra per Militare.
     */
    public function scopeForMilitari(Builder $query): Builder
    {
        return $query->forType(Militare::class);
    }

    /**
     * Filtra per User.
     */
    public function scopeForUsers(Builder $query): Builder
    {
        return $query->forType(User::class);
    }

    // =========================================================================
    // METODI
    // =========================================================================

    /**
     * Verifica se l'assegnazione è attiva.
     */
    public function isActive(?Carbon $date = null): bool
    {
        $date = $date ?? Carbon::today();

        $afterStart = $this->start_date === null || $this->start_date <= $date;
        $beforeEnd = $this->end_date === null || $this->end_date >= $date;

        return $afterStart && $beforeEnd;
    }

    /**
     * Verifica se è un ruolo di comando.
     */
    public function isCommandRole(): bool
    {
        return in_array($this->role, [
            self::ROLE_COMANDANTE,
            self::ROLE_VICE_COMANDANTE,
            self::ROLE_RESPONSABILE,
        ], true);
    }

    /**
     * Ottiene il nome visualizzabile del ruolo.
     */
    public function getRoleName(): string
    {
        return self::getAvailableRoles()[$this->role] ?? ucfirst($this->role);
    }

    /**
     * Imposta questa come assegnazione primaria,
     * rimuovendo il flag primario dalle altre assegnazioni della stessa entità.
     */
    public function makePrimary(): bool
    {
        // Rimuovi primary da altre assegnazioni della stessa entità
        static::where('assignable_type', $this->assignable_type)
            ->where('assignable_id', $this->assignable_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->is_primary = true;
        return $this->save();
    }
}
