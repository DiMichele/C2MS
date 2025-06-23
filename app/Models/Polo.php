<?php

/**
 * C2MS: Gestione e Controllo Digitale a Supporto del Comando
 * 
 * Questo file fa parte del sistema C2MS per la gestione militare digitale.
 * 
 * @package    C2MS
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

/**
 * Modello per i poli militari
 * 
 * Questo modello rappresenta i poli nell'organigramma militare.
 * I poli appartengono a una compagnia e contengono militari.
 * Sono un'alternativa ai plotoni per l'organizzazione del personale.
 * 
 * @package App\Models
 * @version 1.0
 * 
 * @property int $id ID univoco del polo
 * @property string $nome Nome del polo
 * @property int $compagnia_id ID della compagnia di appartenenza
 * @property \Illuminate\Support\Carbon|null $created_at Data creazione
 * @property \Illuminate\Support\Carbon|null $updated_at Data ultimo aggiornamento
 * 
 * @property-read \App\Models\Compagnia $compagnia Compagnia di appartenenza
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Militare[] $militari Militari del polo
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|Polo perNome() Ordinati alfabeticamente per nome
 * @method static \Illuminate\Database\Eloquent\Builder|Polo perCompagnia(int $compagniaId) Per compagnia specifica
 * @method static \Illuminate\Database\Eloquent\Builder|Polo conMilitari() Con militari caricati
 */
class Polo extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     * 
     * @var string
     */
    protected $table = 'poli';
    
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
     * Relazione con i militari del polo
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function militari()
    {
        return $this->hasMany(Militare::class, 'polo_id');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per ordinare i poli alfabeticamente per nome
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerNome($query)
    {
        return $query->orderBy('nome');
    }

    /**
     * Scope per filtrare poli per compagnia
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
     * Scope per caricare i poli con i militari
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
     * Ottiene il numero di militari nel polo
     * 
     * @return int Numero di militari
     */
    public function getNumeroMilitari()
    {
        return $this->militari()->count();
    }

    /**
     * Verifica se il polo ha militari assegnati
     * 
     * @return bool True se ha militari assegnati
     */
    public function hasMilitari()
    {
        return $this->getNumeroMilitari() > 0;
    }

    /**
     * Ottiene la rappresentazione testuale del polo
     * 
     * @return string Nome del polo
     */
    public function __toString()
    {
        return $this->nome;
    }

    /**
     * Ottiene il nome completo del polo con la compagnia
     * 
     * @return string Nome completo (es. "Polo Logistico - Alpha Company")
     */
    public function getNomeCompleto()
    {
        return $this->nome . ' - ' . $this->compagnia->nome;
    }

    /**
     * Ottiene statistiche del polo
     * 
     * @return array<string, mixed> Statistiche del polo
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
     * Verifica se il polo è vuoto
     * 
     * @return bool True se è vuoto
     */
    public function isEmpty()
    {
        return !$this->hasMilitari();
    }

    /**
     * Ottiene il grado più alto nel polo
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
     * Calcola la forza effettiva del polo (percentuale di presenza)
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

    /**
     * Ottiene la specializzazione del polo
     * 
     * @return string Tipo di specializzazione basato sul nome
     */
    public function getSpecializzazione()
    {
        $nome = strtolower($this->nome);
        
        if (str_contains($nome, 'logistic') || str_contains($nome, 'logistico')) {
            return 'Logistica';
        }
        
        if (str_contains($nome, 'tecnic') || str_contains($nome, 'technical')) {
            return 'Tecnico';
        }
        
        if (str_contains($nome, 'amministrat') || str_contains($nome, 'admin')) {
            return 'Amministrativo';
        }
        
        if (str_contains($nome, 'operativ') || str_contains($nome, 'operational')) {
            return 'Operativo';
        }
        
        return 'Generico';
    }

    /**
     * Ottiene la classe CSS per il tipo di polo
     * 
     * @return string Classe CSS appropriata
     */
    public function getPoloCssClass()
    {
        $specializzazione = $this->getSpecializzazione();
        
        return match($specializzazione) {
            'Logistica' => 'badge-info',
            'Tecnico' => 'badge-warning',
            'Amministrativo' => 'badge-secondary',
            'Operativo' => 'badge-primary',
            default => 'badge-light'
        };
    }
}
