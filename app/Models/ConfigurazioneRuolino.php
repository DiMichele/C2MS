<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model per la configurazione dei ruolini
 * 
 * Gestisce la configurazione di quali impegni CPT rendono un militare
 * presente o assente nei ruolini giornalieri
 */
class ConfigurazioneRuolino extends Model
{
    use HasFactory;

    protected $table = 'configurazione_ruolini';

    protected $fillable = [
        'tipo_servizio_id',
        'stato_presenza',
        'note'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Tipo di servizio associato
     */
    public function tipoServizio()
    {
        return $this->belongsTo(TipoServizio::class, 'tipo_servizio_id');
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

    /**
     * Ottiene la configurazione per un tipo di servizio specifico
     */
    public static function getByTipoServizioId($tipoServizioId)
    {
        return static::where('tipo_servizio_id', $tipoServizioId)->first();
    }

    /**
     * Ottiene lo stato di presenza per un tipo di servizio
     * Ritorna: 'presente', 'assente', o 'default'
     */
    public static function getStatoPresenzaForTipoServizio($tipoServizioId)
    {
        $config = static::getByTipoServizioId($tipoServizioId);
        return $config ? $config->stato_presenza : 'default';
    }
}

