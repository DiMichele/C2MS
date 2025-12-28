<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modello per i tipi di idoneità sanitaria
 * 
 * @property int $id
 * @property string $codice
 * @property string $nome
 * @property string|null $descrizione
 * @property int $durata_mesi
 * @property bool $attivo
 * @property int $ordine
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class TipoIdoneita extends Model
{
    use HasFactory;

    protected $table = 'tipi_idoneita';

    protected $fillable = [
        'codice',
        'nome',
        'descrizione',
        'durata_mesi',
        'attivo',
        'ordine'
    ];

    protected $casts = [
        'attivo' => 'boolean',
        'durata_mesi' => 'integer',
        'ordine' => 'integer'
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Scadenze idoneità di questo tipo
     */
    public function scadenzeIdoneita()
    {
        return $this->hasMany(ScadenzaIdoneita::class, 'tipo_idoneita_id');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Solo tipi di idoneità attivi
     */
    public function scopeAttivi($query)
    {
        return $query->where('attivo', true);
    }

    /**
     * Ordinati per nome
     */
    public function scopePerNome($query)
    {
        return $query->orderBy('nome');
    }

    /**
     * Ordinati per ordine e nome
     */
    public function scopeOrdinati($query)
    {
        return $query->orderBy('ordine')->orderBy('nome');
    }

    /**
     * Filtra per codice
     */
    public function scopePerCodice($query, $codice)
    {
        return $query->where('codice', $codice);
    }
}

