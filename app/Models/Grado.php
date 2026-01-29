<?php

/**
 * SUGECO: Sistema Unico di Gestione e Controllo
 * 
 * Questo file fa parte del sistema SUGECO per la gestione militare digitale.
 * 
 * @package    SUGECO
 * @subpackage Models
 * @version    2.1.0
 * @author     Michele Di Gennaro
 * @copyright 2025 Michele Di Gennaro
 * @license    Proprietary
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modello per i gradi militari
 * 
 * Questo modello rappresenta i gradi della gerarchia militare.
 * I gradi hanno un ordine di importanza per la visualizzazione
 * e l'organizzazione dei militari.
 * 
 * @package App\Models
 * @version 1.0
 * 
 * @property int $id ID univoco del grado
 * @property string $nome Nome del grado (es. "Generale", "Colonnello", ecc.)
 * @property int $ordine Ordine di importanza (più alto = più importante)
 * @property \Illuminate\Support\Carbon|null $created_at Data creazione
 * @property \Illuminate\Support\Carbon|null $updated_at Data ultimo aggiornamento
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Militare[] $militari Militari con questo grado
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|Grado perOrdine() Ordinati per importanza (decrescente)
 * @method static \Illuminate\Database\Eloquent\Builder|Grado perNome() Ordinati alfabeticamente per nome
 */
class Grado extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     * 
     * @var string
     */
    protected $table = 'gradi';
    
    /**
     * Gli attributi che sono mass assignable
     * 
     * @var array<string>
     */
    protected $fillable = ['nome', 'abbreviazione', 'categoria', 'ordine', 'sigla_categoria'];
    
    /**
     * Accessor per retrocompatibilità: ritorna abbreviazione quando si chiede sigla
     * 
     * @return string|null
     */
    public function getSiglaAttribute()
    {
        return $this->abbreviazione;
    }

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Relazione con i militari che hanno questo grado
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function militari()
    {
        return $this->hasMany(Militare::class, 'grado_id');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per ordinare i gradi per importanza (decrescente)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerOrdine($query)
    {
        return $query->orderByDesc('ordine');
    }

    /**
     * Scope per ordinare i gradi alfabeticamente per nome
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerNome($query)
    {
        return $query->orderBy('nome');
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Ottiene il numero di militari con questo grado
     * 
     * @return int Numero di militari
     */
    public function getNumeroMilitari()
    {
        return $this->militari()->count();
    }

    /**
     * Verifica se il grado ha militari assegnati
     * 
     * @return bool True se ha militari assegnati
     */
    public function hasMilitari()
    {
        return $this->getNumeroMilitari() > 0;
    }

    /**
     * Ottiene la rappresentazione testuale del grado
     * 
     * @return string Nome del grado
     */
    public function __toString()
    {
        return $this->nome;
    }

    /**
     * Verifica se questo grado è superiore a un altro
     * 
     * @param \App\Models\Grado $altroGrado Grado da confrontare
     * @return bool True se questo grado è superiore
     */
    public function isSuperioreA(Grado $altroGrado)
    {
        return $this->ordine > $altroGrado->ordine;
    }

    /**
     * Verifica se questo grado è inferiore a un altro
     * 
     * @param \App\Models\Grado $altroGrado Grado da confrontare
     * @return bool True se questo grado è inferiore
     */
    public function isInferioreA(Grado $altroGrado)
    {
        return $this->ordine < $altroGrado->ordine;
    }

    /**
     * Ottiene il livello di importanza del grado
     * 
     * @return string Livello di importanza
     */
    public function getLivelloImportanza()
    {
        if ($this->ordine >= 90) return 'Generale';
        if ($this->ordine >= 80) return 'Superiore';
        if ($this->ordine >= 70) return 'Intermedio';
        if ($this->ordine >= 60) return 'Subalterno';
        
        return 'Base';
    }

    /**
     * Ottiene la classe CSS per il colore del grado
     * 
     * @return string Classe CSS appropriata
     */
    public function getGradoCssClass()
    {
        $livello = $this->getLivelloImportanza();
        
        return match($livello) {
            'Generale' => 'badge-danger',
            'Superiore' => 'badge-warning',
            'Intermedio' => 'badge-primary',
            'Subalterno' => 'badge-info',
            default => 'badge-secondary'
        };
    }
}
