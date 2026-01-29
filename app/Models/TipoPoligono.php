<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modello per i tipi di poligono
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
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ScadenzaPoligono[] $scadenzePoligoni
 */
class TipoPoligono extends Model
{
    use HasFactory;

    protected $table = 'tipi_poligono';

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
     * Scadenze poligoni di questo tipo
     */
    public function scadenzePoligoni()
    {
        return $this->hasMany(\App\Models\ScadenzaPoligono::class, 'tipo_poligono_id');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Solo tipi di poligono attivi
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
