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
use App\Models\Militare;
use App\Models\Compagnia;
use App\Traits\BelongsToCompagnia;

/**
 * Modello per i plotoni militari
 * 
 * Questo modello rappresenta i plotoni nell'organigramma militare.
 * I plotoni appartengono a una compagnia e contengono militari.
 * Sono l'unità organizzativa di base per la gestione del personale.
 * 
 * NOTA: Segregazione automatica per compagnia tramite BelongsToCompagnia
 * 
 * @package App\Models
 * @version 1.1
 * 
 * @property int $id ID univoco del plotone
 * @property string $nome Nome del plotone
 * @property int $compagnia_id ID della compagnia di appartenenza
 * @property \Illuminate\Support\Carbon|null $created_at Data creazione
 * @property \Illuminate\Support\Carbon|null $updated_at Data ultimo aggiornamento
 * 
 * @property-read \App\Models\Compagnia $compagnia Compagnia di appartenenza
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Militare[] $militari Militari del plotone
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|Plotone perNome() Ordinati alfabeticamente per nome
 * @method static \Illuminate\Database\Eloquent\Builder|Plotone perCompagnia(int $compagniaId) Per compagnia specifica
 * @method static \Illuminate\Database\Eloquent\Builder|Plotone conMilitari() Con militari caricati
 */
class Plotone extends Model
{
    use HasFactory, BelongsToCompagnia;

    /**
     * Nome della tabella associata al modello
     * 
     * @var string
     */
    protected $table = 'plotoni';
    
    /**
     * Gli attributi che sono mass assignable
     * 
     * @var array<string>
     */
    protected $fillable = ['nome', 'compagnia_id'];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Relazione con la compagnia di appartenenza
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function compagnia()
    {
        return $this->belongsTo(Compagnia::class, 'compagnia_id');
    }

    /**
     * Relazione con i militari del plotone
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function militari()
    {
        return $this->hasMany(Militare::class, 'plotone_id');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per ordinare i plotoni alfabeticamente per nome
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerNome($query)
    {
        return $query->orderBy('nome');
    }

    /**
     * Scope per filtrare plotoni per compagnia
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $compagniaId ID della compagnia
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerCompagnia($query, $compagniaId)
    {
        return $query->where('compagnia_id', $compagniaId);
    }

    /**
     * Scope per caricare i plotoni con i militari
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConMilitari($query)
    {
        return $query->with(['militari' => function($query) {
            $query->with('grado')
                  ->join('gradi', 'militari.grado_id', '=', 'gradi.id')
                  ->orderByDesc('gradi.ordine')
                  ->orderBy('militari.cognome')
                  ->select('militari.*');
        }]);
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Ottiene il numero di militari nel plotone
     * 
     * @return int Numero di militari
     */
    public function getNumeroMilitari()
    {
        return $this->militari()->count();
    }

    /**
     * Verifica se il plotone ha militari assegnati
     * 
     * @return bool True se ha militari assegnati
     */
    public function hasMilitari()
    {
        return $this->getNumeroMilitari() > 0;
    }

    /**
     * Ottiene la rappresentazione testuale del plotone
     * 
     * @return string Nome del plotone
     */
    public function __toString()
    {
        return $this->nome;
    }

    /**
     * Ottiene il nome completo del plotone con la compagnia
     * 
     * @return string Nome completo (es. "1° Plotone - Alpha Company")
     */
    public function getNomeCompleto()
    {
        return $this->nome . ' - ' . $this->compagnia->nome;
    }

    /**
     * Ottiene statistiche del plotone
     * 
     * @return array<string, mixed> Statistiche del plotone
     */
    public function getStatistiche()
    {
        return [
            'nome' => $this->nome,
            'compagnia' => $this->compagnia->nome,
            'militari' => $this->getNumeroMilitari(),
            'gradi_presenti' => $this->militari()
                                    ->join('gradi', 'militari.grado_id', '=', 'gradi.id')
                                    ->distinct('gradi.nome')
                                    ->count('gradi.id')
        ];
    }

    /**
     * Ottiene i militari ordinati per grado
     * 
     * @return \Illuminate\Database\Eloquent\Collection Militari ordinati
     */
    public function getMilitariOrdinatiPerGrado()
    {
        return $this->militari()
                    ->with('grado')
                    ->join('gradi', 'militari.grado_id', '=', 'gradi.id')
                    ->orderByDesc('gradi.ordine')
                    ->orderBy('militari.cognome')
                    ->orderBy('militari.nome')
                    ->select('militari.*')
                    ->get();
    }

    /**
     * Verifica se il plotone è vuoto
     * 
     * @return bool True se è vuoto
     */
    public function isEmpty()
    {
        return !$this->hasMilitari();
    }

    /**
     * Ottiene il grado più alto nel plotone
     * 
     * @return \App\Models\Grado|null Grado più alto o null se vuoto
     */
    public function getGradoPiuAlto()
    {
        $militare = $this->militari()
                         ->with('grado')
                         ->join('gradi', 'militari.grado_id', '=', 'gradi.id')
                         ->orderByDesc('gradi.ordine')
                         ->first();
        
        return $militare ? $militare->grado : null;
    }

    /**
     * Calcola la forza effettiva del plotone (percentuale di presenza)
     * 
     * @return float Percentuale di presenza (0-100)
     */
    public function getForzaEffettiva()
    {
        $totale = $this->getNumeroMilitari();
        
        if ($totale === 0) {
            return 0;
        }
        
        $presenti = $this->militari()
                         ->whereHas('presenze', function($query) {
                             $query->where('data', now()->format('Y-m-d'))
                                   ->where('stato', 'Presente');
                         })
                         ->count();
        
        return round(($presenti / $totale) * 100, 1);
    }
}
