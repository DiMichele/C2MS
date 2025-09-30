<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modello per i tipi di poligono
 * 
 * @property int $id
 * @property string $nome
 * @property string|null $descrizione
 * @property int $punteggio_minimo
 * @property int $punteggio_massimo
 * @property bool $attivo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Poligono[] $poligoni
 */
class TipoPoligono extends Model
{
    use HasFactory;

    protected $table = 'tipi_poligono';

    protected $fillable = [
        'nome',
        'descrizione',
        'punteggio_minimo',
        'punteggio_massimo',
        'attivo'
    ];

    protected $casts = [
        'attivo' => 'boolean',
        'punteggio_minimo' => 'integer',
        'punteggio_massimo' => 'integer'
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Poligoni di questo tipo
     */
    public function poligoni()
    {
        return $this->hasMany(Poligono::class, 'tipo_poligono_id');
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

    // ==========================================
    // METODI HELPER
    // ==========================================

    /**
     * Verifica se un punteggio è sufficiente per superare il poligono
     */
    public function isPunteggioSufficiente(int $punteggio): bool
    {
        return $punteggio >= $this->punteggio_minimo;
    }

    /**
     * Calcola la percentuale del punteggio
     */
    public function calcolaPercentuale(int $punteggio): float
    {
        if ($this->punteggio_massimo == 0) {
            return 0;
        }
        
        return round(($punteggio / $this->punteggio_massimo) * 100, 2);
    }

    /**
     * Ottiene il colore del badge in base al punteggio
     */
    public function getColoreBadge(int $punteggio): string
    {
        $percentuale = $this->calcolaPercentuale($punteggio);
        
        if ($percentuale >= 90) return 'success';
        if ($percentuale >= 80) return 'primary';
        if ($percentuale >= 70) return 'warning';
        if ($percentuale >= 60) return 'secondary';
        
        return 'danger';
    }
}
