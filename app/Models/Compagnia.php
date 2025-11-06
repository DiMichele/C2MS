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
use App\Models\Plotone;
use App\Models\Polo;

/**
 * Modello per le compagnie militari
 * 
 * Questo modello rappresenta le compagnie nell'organigramma militare.
 * Le compagnie contengono plotoni e poli, e sono il livello intermedio
 * dell'organizzazione gerarchica.
 * 
 * @package App\Models
 * @version 1.0
 * 
 * @property int $id ID univoco della compagnia
 * @property string $nome Nome della compagnia
 * @property \Illuminate\Support\Carbon|null $created_at Data creazione
 * @property \Illuminate\Support\Carbon|null $updated_at Data ultimo aggiornamento
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Plotone[] $plotoni Plotoni della compagnia
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Polo[] $poli Poli della compagnia
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Militare[] $militari Tutti i militari della compagnia
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|Compagnia perNome() Ordinate alfabeticamente per nome
 * @method static \Illuminate\Database\Eloquent\Builder|Compagnia conPlotoni() Con plotoni caricati
 * @method static \Illuminate\Database\Eloquent\Builder|Compagnia conPoli() Con poli caricati
 */
class Compagnia extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     * 
     * @var string
     */
    protected $table = 'compagnie';
    
    /**
     * Gli attributi che sono mass assignable
     * 
     * @var array<string>
     */
    protected $fillable = ['nome'];
    
    /**
     * Accessor per ottenere il numero della compagnia dal nome
     * Estrae il numero (es. da "124^ Compagnia" ottiene "124")
     * 
     * @return string|null
     */
    public function getNumeroAttribute()
    {
        if (preg_match('/^(\d+)/', $this->nome, $matches)) {
            return $matches[1];
        }
        return null;
    }

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Relazione con i plotoni della compagnia
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function plotoni()
    {
        return $this->hasMany(Plotone::class, 'compagnia_id');
    }

    /**
     * Relazione con i poli della compagnia
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function poli()
    {
        return $this->hasMany(Polo::class, 'compagnia_id');
    }

    /**
     * Relazione diretta con i militari della compagnia
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function militari()
    {
        return $this->hasMany(Militare::class, 'compagnia_id');
    }

    /**
     * Relazione con tutti i militari della compagnia (attraverso plotoni)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function militariPerPlotone()
    {
        return $this->hasManyThrough(
            Militare::class,
            Plotone::class,
            'compagnia_id', // Foreign key on plotoni table
            'plotone_id',   // Foreign key on militari table
            'id',           // Local key on compagnie table
            'id'            // Local key on plotoni table
        );
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per ordinare le compagnie alfabeticamente per nome
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerNome($query)
    {
        return $query->orderBy('nome');
    }

    /**
     * Scope per caricare le compagnie con i plotoni
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConPlotoni($query)
    {
        return $query->with('plotoni');
    }

    /**
     * Scope per caricare le compagnie con i poli
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConPoli($query)
    {
        return $query->with('poli');
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Ottiene il numero di plotoni nella compagnia
     * 
     * @return int Numero di plotoni
     */
    public function getNumeroPlotoni()
    {
        return $this->plotoni()->count();
    }

    /**
     * Ottiene il numero di poli nella compagnia
     * 
     * @return int Numero di poli
     */
    public function getNumeroPoli()
    {
        return $this->poli()->count();
    }

    /**
     * Ottiene il numero totale di militari nella compagnia
     * 
     * @return int Numero di militari
     */
    public function getNumeroMilitari()
    {
        return $this->militari()->count();
    }

    /**
     * Verifica se la compagnia ha plotoni
     * 
     * @return bool True se ha plotoni
     */
    public function hasPlotoni()
    {
        return $this->getNumeroPlotoni() > 0;
    }

    /**
     * Verifica se la compagnia ha poli
     * 
     * @return bool True se ha poli
     */
    public function hasPoli()
    {
        return $this->getNumeroPoli() > 0;
    }

    /**
     * Verifica se la compagnia ha militari
     * 
     * @return bool True se ha militari
     */
    public function hasMilitari()
    {
        return $this->getNumeroMilitari() > 0;
    }

    /**
     * Ottiene la rappresentazione testuale della compagnia
     * 
     * @return string Nome della compagnia
     */
    public function __toString()
    {
        return $this->nome;
    }

    /**
     * Ottiene statistiche complete della compagnia
     * 
     * @return array<string, mixed> Statistiche della compagnia
     */
    public function getStatistiche()
    {
        return [
            'nome' => $this->nome,
            'plotoni' => $this->getNumeroPlotoni(),
            'poli' => $this->getNumeroPoli(),
            'militari' => $this->getNumeroMilitari(),
            'plotoni_con_militari' => $this->plotoni()->whereHas('militari')->count(),
            'poli_con_militari' => $this->poli()->whereHas('militari')->count()
        ];
    }

    /**
     * Ottiene tutti i militari raggruppati per plotone
     * 
     * @return \Illuminate\Database\Eloquent\Collection Militari raggruppati
     */
    public function getMilitariPerPlotone()
    {
        return $this->plotoni()
                    ->with(['militari' => function($query) {
                        $query->with('grado')
                              ->join('gradi', 'militari.grado_id', '=', 'gradi.id')
                              ->orderByDesc('gradi.ordine')
                              ->orderBy('militari.cognome')
                              ->select('militari.*');
                    }])
                    ->get();
    }

    /**
     * Verifica se la compagnia è vuota (senza plotoni né poli)
     * 
     * @return bool True se è vuota
     */
    public function isEmpty()
    {
        return !$this->hasPlotoni() && !$this->hasPoli();
    }

    /**
     * Ottiene il tipo di organizzazione prevalente
     * 
     * @return string Tipo di organizzazione
     */
    public function getTipoOrganizzazione()
    {
        $plotoni = $this->getNumeroPlotoni();
        $poli = $this->getNumeroPoli();
        
        if ($plotoni > $poli) return 'Plotoni';
        if ($poli > $plotoni) return 'Poli';
        if ($plotoni > 0 && $poli > 0) return 'Mista';
        
        return 'Vuota';
    }
}
