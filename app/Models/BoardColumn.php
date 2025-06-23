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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modello per le colonne della bacheca
 * 
 * Questo modello rappresenta le colonne della bacheca kanban per l'organizzazione
 * delle attività. Ogni colonna può contenere multiple attività ordinate.
 * 
 * @package App\Models
 * @version 1.0
 * 
 * @property int $id ID univoco della colonna
 * @property string $name Nome della colonna
 * @property string $slug Slug univoco per URL
 * @property int $order Ordine di visualizzazione
 * @property \Illuminate\Support\Carbon|null $created_at Data creazione
 * @property \Illuminate\Support\Carbon|null $updated_at Data ultimo aggiornamento
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\BoardActivity[] $activities Attività della colonna
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|BoardColumn perOrdine() Ordinate per ordine di visualizzazione
 * @method static \Illuminate\Database\Eloquent\Builder|BoardColumn conAttivita() Con attività caricate
 */
class BoardColumn extends Model
{
    use HasFactory;

    /**
     * Gli attributi che sono mass assignable
     * 
     * @var array<string>
     */
    protected $fillable = ['name', 'slug', 'order'];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Relazione con le attività della colonna
     * 
     * Le attività sono ordinate per il campo 'order'
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activities()
    {
        return $this->hasMany(BoardActivity::class, 'column_id')->orderBy('order');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per ordinare le colonne per ordine di visualizzazione
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerOrdine($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Scope per caricare le colonne con le attività
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConAttivita($query)
    {
        return $query->with(['activities' => function($query) {
            $query->orderBy('order');
        }]);
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Ottiene il numero di attività nella colonna
     * 
     * @return int Numero di attività
     */
    public function getNumeroAttivita()
    {
        return $this->activities()->count();
    }

    /**
     * Verifica se la colonna ha attività
     * 
     * @return bool True se ha attività
     */
    public function hasAttivita()
    {
        return $this->getNumeroAttivita() > 0;
    }

    /**
     * Ottiene la rappresentazione testuale della colonna
     * 
     * @return string Nome della colonna
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Ottiene il prossimo ordine disponibile per una nuova attività
     * 
     * @return int Prossimo numero di ordine
     */
    public function getNextActivityOrder()
    {
        $maxOrder = $this->activities()->max('order');
        
        return $maxOrder ? $maxOrder + 1 : 1;
    }

    /**
     * Ottiene la classe CSS per lo stile della colonna
     * 
     * @return string Classe CSS appropriata
     */
    public function getColumnCssClass()
    {
        $slug = strtolower($this->slug);
        
        return match(true) {
            str_contains($slug, 'todo') || str_contains($slug, 'backlog') => 'column-todo',
            str_contains($slug, 'progress') || str_contains($slug, 'doing') => 'column-progress',
            str_contains($slug, 'review') || str_contains($slug, 'testing') => 'column-review',
            str_contains($slug, 'done') || str_contains($slug, 'completed') => 'column-done',
            default => 'column-default'
        };
    }

    /**
     * Ottiene l'icona appropriata per il tipo di colonna
     * 
     * @return string Classe icona FontAwesome
     */
    public function getColumnIcon()
    {
        $slug = strtolower($this->slug);
        
        return match(true) {
            str_contains($slug, 'todo') || str_contains($slug, 'backlog') => 'fas fa-list',
            str_contains($slug, 'progress') || str_contains($slug, 'doing') => 'fas fa-spinner',
            str_contains($slug, 'review') || str_contains($slug, 'testing') => 'fas fa-search',
            str_contains($slug, 'done') || str_contains($slug, 'completed') => 'fas fa-check',
            default => 'fas fa-columns'
        };
    }

    /**
     * Verifica se è una colonna di stato finale
     * 
     * @return bool True se è una colonna finale
     */
    public function isFinalColumn()
    {
        $slug = strtolower($this->slug);
        
        return str_contains($slug, 'done') || 
               str_contains($slug, 'completed') || 
               str_contains($slug, 'archived');
    }

    /**
     * Verifica se è una colonna di inizio
     * 
     * @return bool True se è una colonna di inizio
     */
    public function isStartColumn()
    {
        $slug = strtolower($this->slug);
        
        return str_contains($slug, 'todo') || 
               str_contains($slug, 'backlog') || 
               str_contains($slug, 'new');
    }

    /**
     * Ottiene statistiche della colonna
     * 
     * @return array<string, mixed> Statistiche della colonna
     */
    public function getStatistiche()
    {
        return [
            'nome' => $this->name,
            'attivita_totali' => $this->getNumeroAttivita(),
            'attivita_attive' => $this->activities()->where('status', '!=', 'completed')->count(),
            'attivita_completate' => $this->activities()->where('status', 'completed')->count(),
            'ordine' => $this->order
        ];
    }
} 
