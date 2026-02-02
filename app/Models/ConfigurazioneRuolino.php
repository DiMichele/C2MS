<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganizationalUnit;
use App\Models\OrganizationalUnit;

/**
 * Model per la configurazione dei ruolini PER UNITÀ ORGANIZZATIVA
 * 
 * Gestisce la configurazione di quali impegni CPT rendono un militare
 * presente o assente nei ruolini giornalieri.
 * 
 * OGNI UNITÀ ORGANIZZATIVA può avere le proprie regole.
 * 
 * MIGRAZIONE MULTI-TENANCY: Questo modello ora usa organizational_unit_id invece di compagnia_id.
 * I metodi legacy basati su compagnia_id sono mantenuti per retrocompatibilità ma deprecati.
 * 
 * @property int $id
 * @property int|null $compagnia_id @deprecated Usare organizational_unit_id
 * @property int|null $organizational_unit_id
 * @property int $tipo_servizio_id
 * @property string $stato_presenza presente|assente|default
 * @property string|null $note
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read Compagnia $compagnia @deprecated
 * @property-read OrganizationalUnit $organizationalUnit
 * @property-read TipoServizio $tipoServizio
 */
class ConfigurazioneRuolino extends Model
{
    use HasFactory, BelongsToOrganizationalUnit;

    protected $table = 'configurazione_ruolini';

    protected $fillable = [
        'compagnia_id',
        'organizational_unit_id', // Nuova gerarchia organizzativa
        'tipo_servizio_id',
        'stato_presenza',
        'note'
    ];

    protected $casts = [
        'compagnia_id' => 'integer',
        'tipo_servizio_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Compagnia proprietaria della configurazione
     * @deprecated Usare organizationalUnit() invece
     */
    public function compagnia()
    {
        return $this->belongsTo(Compagnia::class);
    }

    /**
     * Unità organizzativa proprietaria della configurazione
     */
    public function organizationalUnit()
    {
        return $this->belongsTo(OrganizationalUnit::class, 'organizational_unit_id');
    }

    /**
     * Tipo di servizio associato
     */
    public function tipoServizio()
    {
        return $this->belongsTo(TipoServizio::class, 'tipo_servizio_id');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per compagnia specifica
     * @deprecated Usare scopeForUnit() invece
     */
    public function scopeForCompagnia($query, int $compagniaId)
    {
        return $query->where('compagnia_id', $compagniaId);
    }
    
    /**
     * Scope per unità organizzativa specifica
     */
    public function scopeForUnit($query, int $unitId)
    {
        return $query->where('organizational_unit_id', $unitId);
    }

    /**
     * Scope per tipo servizio specifico
     */
    public function scopeForTipoServizio($query, int $tipoServizioId)
    {
        return $query->where('tipo_servizio_id', $tipoServizioId);
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Verifica se questo impegno rende il militare presente
     */
    public function isPresente(): bool
    {
        return $this->stato_presenza === 'presente';
    }

    /**
     * Verifica se questo impegno rende il militare assente
     */
    public function isAssente(): bool
    {
        return $this->stato_presenza === 'assente';
    }

    /**
     * Verifica se usa la logica di default
     */
    public function isDefault(): bool
    {
        return $this->stato_presenza === 'default';
    }

    // ==========================================
    // METODI STATICI CON ORGANIZATIONAL UNIT (NUOVI)
    // ==========================================

    /**
     * Ottiene la configurazione per un tipo di servizio e unità organizzativa specifici
     * 
     * @param int $tipoServizioId ID del tipo servizio
     * @param int|null $unitId ID dell'unità organizzativa (null = unità attiva)
     * @return ConfigurazioneRuolino|null
     */
    public static function getByTipoServizioIdAndUnit(int $tipoServizioId, ?int $unitId = null)
    {
        if ($unitId === null) {
            $unitId = activeUnitId();
        }

        if (!$unitId) {
            return null;
        }

        return static::withoutGlobalScopes()
            ->forUnit($unitId)
            ->forTipoServizio($tipoServizioId)
            ->first();
    }

    /**
     * Ottiene lo stato di presenza per un tipo di servizio e unità
     * Ritorna: 'presente', 'assente', o 'default'
     * 
     * @param int $tipoServizioId
     * @param int|null $unitId
     * @return string
     */
    public static function getStatoPresenzaForTipoServizioAndUnit(int $tipoServizioId, ?int $unitId = null): string
    {
        $config = static::getByTipoServizioIdAndUnit($tipoServizioId, $unitId);
        return $config ? $config->stato_presenza : 'default';
    }

    /**
     * Ottiene tutte le configurazioni per un'unità organizzativa
     * 
     * @param int|null $unitId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllForUnit(?int $unitId = null)
    {
        if ($unitId === null) {
            $unitId = activeUnitId();
        }

        if (!$unitId) {
            return collect();
        }

        return static::withoutGlobalScopes()
            ->forUnit($unitId)
            ->with('tipoServizio')
            ->get();
    }

    /**
     * Ottiene le configurazioni raggruppate per stato
     * 
     * @param int|null $unitId
     * @return array ['presente' => [...], 'assente' => [...]]
     */
    public static function getGroupedByStatoForUnit(?int $unitId = null): array
    {
        $configs = static::getAllForUnit($unitId);

        return [
            'presente' => $configs->where('stato_presenza', 'presente')->pluck('tipo_servizio_id')->toArray(),
            'assente' => $configs->where('stato_presenza', 'assente')->pluck('tipo_servizio_id')->toArray(),
        ];
    }

    // ==========================================
    // METODI STATICI LEGACY (DEPRECATED)
    // ==========================================

    /**
     * Ottiene la configurazione per un tipo di servizio e compagnia specifici
     * @deprecated Usare getByTipoServizioIdAndUnit() invece
     * 
     * @param int $tipoServizioId ID del tipo servizio
     * @param int|null $compagniaId ID della compagnia (null = utente corrente)
     * @return ConfigurazioneRuolino|null
     */
    public static function getByTipoServizioIdAndCompagnia(int $tipoServizioId, ?int $compagniaId = null)
    {
        if ($compagniaId === null) {
            $user = auth()->user();
            $compagniaId = $user?->compagnia_id;
        }

        if (!$compagniaId) {
            return null;
        }

        return static::withoutGlobalScopes()
            ->forCompagnia($compagniaId)
            ->forTipoServizio($tipoServizioId)
            ->first();
    }

    /**
     * @deprecated Usa getByTipoServizioIdAndUnit() invece
     */
    public static function getByTipoServizioId($tipoServizioId)
    {
        // Prima prova con organizational_unit_id, poi fallback su compagnia_id
        $config = static::getByTipoServizioIdAndUnit($tipoServizioId);
        if ($config) {
            return $config;
        }
        return static::getByTipoServizioIdAndCompagnia($tipoServizioId);
    }

    /**
     * @deprecated Usare getStatoPresenzaForTipoServizioAndUnit() invece
     */
    public static function getStatoPresenzaForTipoServizioAndCompagnia(int $tipoServizioId, ?int $compagniaId = null): string
    {
        $config = static::getByTipoServizioIdAndCompagnia($tipoServizioId, $compagniaId);
        return $config ? $config->stato_presenza : 'default';
    }

    /**
     * @deprecated Usa getStatoPresenzaForTipoServizioAndUnit() invece
     */
    public static function getStatoPresenzaForTipoServizio($tipoServizioId)
    {
        // Prima prova con organizational_unit_id, poi fallback su compagnia_id
        return static::getStatoPresenzaForTipoServizioAndUnit($tipoServizioId)
            ?? static::getStatoPresenzaForTipoServizioAndCompagnia($tipoServizioId);
    }

    /**
     * @deprecated Usare getAllForUnit() invece
     */
    public static function getAllForCompagnia(?int $compagniaId = null)
    {
        if ($compagniaId === null) {
            $user = auth()->user();
            $compagniaId = $user?->compagnia_id;
        }

        if (!$compagniaId) {
            return collect();
        }

        return static::withoutGlobalScopes()
            ->forCompagnia($compagniaId)
            ->with('tipoServizio')
            ->get();
    }

    /**
     * @deprecated Usare getGroupedByStatoForUnit() invece
     */
    public static function getGroupedByStatoForCompagnia(?int $compagniaId = null): array
    {
        $configs = static::getAllForCompagnia($compagniaId);

        return [
            'presente' => $configs->where('stato_presenza', 'presente')->pluck('tipo_servizio_id')->toArray(),
            'assente' => $configs->where('stato_presenza', 'assente')->pluck('tipo_servizio_id')->toArray(),
        ];
    }
}
