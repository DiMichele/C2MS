<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompagnia;

/**
 * Model per la configurazione dei ruolini PER COMPAGNIA
 * 
 * Gestisce la configurazione di quali impegni CPT rendono un militare
 * presente o assente nei ruolini giornalieri.
 * 
 * OGNI COMPAGNIA può avere le proprie regole.
 * 
 * @property int $id
 * @property int $compagnia_id
 * @property int $tipo_servizio_id
 * @property string $stato_presenza presente|assente|default
 * @property string|null $note
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read Compagnia $compagnia
 * @property-read TipoServizio $tipoServizio
 */
class ConfigurazioneRuolino extends Model
{
    use HasFactory, BelongsToCompagnia;

    protected $table = 'configurazione_ruolini';

    protected $fillable = [
        'compagnia_id',
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
     * (sovrascrive il trait per chiarezza)
     */
    public function compagnia()
    {
        return $this->belongsTo(Compagnia::class);
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
     */
    public function scopeForCompagnia($query, int $compagniaId)
    {
        return $query->where('compagnia_id', $compagniaId);
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
    // METODI STATICI CON COMPAGNIA
    // ==========================================

    /**
     * Ottiene la configurazione per un tipo di servizio e compagnia specifici
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
     * @deprecated Usa getByTipoServizioIdAndCompagnia() invece
     * Mantenuto per retrocompatibilità - usa la compagnia dell'utente corrente
     */
    public static function getByTipoServizioId($tipoServizioId)
    {
        return static::getByTipoServizioIdAndCompagnia($tipoServizioId);
    }

    /**
     * Ottiene lo stato di presenza per un tipo di servizio e compagnia
     * Ritorna: 'presente', 'assente', o 'default'
     * 
     * @param int $tipoServizioId
     * @param int|null $compagniaId
     * @return string
     */
    public static function getStatoPresenzaForTipoServizioAndCompagnia(int $tipoServizioId, ?int $compagniaId = null): string
    {
        $config = static::getByTipoServizioIdAndCompagnia($tipoServizioId, $compagniaId);
        return $config ? $config->stato_presenza : 'default';
    }

    /**
     * @deprecated Usa getStatoPresenzaForTipoServizioAndCompagnia() invece
     */
    public static function getStatoPresenzaForTipoServizio($tipoServizioId)
    {
        return static::getStatoPresenzaForTipoServizioAndCompagnia($tipoServizioId);
    }

    /**
     * Ottiene tutte le configurazioni per una compagnia
     * 
     * @param int|null $compagniaId
     * @return \Illuminate\Database\Eloquent\Collection
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
     * Ottiene le configurazioni raggruppate per stato
     * 
     * @param int|null $compagniaId
     * @return array ['presente' => [...], 'assente' => [...]]
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
