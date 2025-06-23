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
use Carbon\Carbon;

/**
 * Modello per le assenze militari
 * 
 * Questo modello rappresenta le assenze dei militari dal servizio,
 * inclusi permessi, congedi, recuperi compensativi e altre tipologie.
 * Include metodi per la gestione delle date e controlli di sovrapposizione.
 * 
 * @package App\Models
 * @version 1.0
 * 
 * @property int $id ID univoco dell'assenza
 * @property int $militare_id ID del militare associato
 * @property string $tipologia Tipologia dell'assenza
 * @property \Illuminate\Support\Carbon $data_inizio Data di inizio
 * @property \Illuminate\Support\Carbon $data_fine Data di fine
 * @property string|null $orario_inizio Orario di inizio (per recuperi compensativi)
 * @property string|null $orario_fine Orario di fine (per recuperi compensativi)
 * @property string $stato Stato dell'assenza (Richiesta Ricevuta, Approvata, ecc.)
 * @property \Illuminate\Support\Carbon|null $created_at Data creazione
 * @property \Illuminate\Support\Carbon|null $updated_at Data ultimo aggiornamento
 * 
 * @property-read \App\Models\Militare $militare Militare associato all'assenza
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|Assenza attiveOggi() Assenze attive oggi
 * @method static \Illuminate\Database\Eloquent\Builder|Assenza future() Assenze future
 * @method static \Illuminate\Database\Eloquent\Builder|Assenza passate() Assenze passate
 * @method static \Illuminate\Database\Eloquent\Builder|Assenza perStato(string $stato) Assenze per stato
 */
class Assenza extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     * 
     * @var string
     */
    protected $table = 'assenze';

    /**
     * Gli attributi che sono mass assignable
     * 
     * @var array<string>
     */
    protected $fillable = [
        'militare_id',
        'tipologia',
        'data_inizio',
        'data_fine',
        'orario_inizio',
        'orario_fine',
        'stato'
    ];
    
    /**
     * Gli attributi che dovrebbero essere cast
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'data_inizio' => 'date',
        'data_fine' => 'date',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================
    
    /**
     * Relazione con il militare associato
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function militare()
    {
        return $this->belongsTo(Militare::class, 'militare_id');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================
    
    /**
     * Scope per filtrare assenze attive oggi
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAttiveOggi($query)
    {
        $oggi = Carbon::today()->format('Y-m-d');
        
        return $query->where(function($q) use ($oggi) {
            $q->where('data_inizio', '<=', $oggi)
              ->where('data_fine', '>=', $oggi);
        });
    }
    
    /**
     * Scope per filtrare assenze future
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFuture($query)
    {
        $oggi = Carbon::today()->format('Y-m-d');
        
        return $query->where('data_inizio', '>', $oggi);
    }
    
    /**
     * Scope per filtrare assenze passate
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePassate($query)
    {
        $oggi = Carbon::today()->format('Y-m-d');
        
        return $query->where('data_fine', '<', $oggi);
    }
    
    /**
     * Scope per filtrare assenze per stato
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $stato Stato da filtrare
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerStato($query, $stato)
    {
        return $query->where('stato', $stato);
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================
    
    /**
     * Verifica se l'assenza è attiva oggi
     * 
     * @return bool True se l'assenza è attiva oggi
     */
    public function isAttivaOggi()
    {
        $oggi = Carbon::today();
        
        return Carbon::parse($this->data_inizio)->lte($oggi) && 
               Carbon::parse($this->data_fine)->gte($oggi);
    }
    
    /**
     * Ottiene la durata dell'assenza in giorni
     * 
     * @return int Numero di giorni di durata (inclusi estremi)
     */
    public function getDurata()
    {
        $inizio = Carbon::parse($this->data_inizio);
        $fine = Carbon::parse($this->data_fine);
        
        return $fine->diffInDays($inizio) + 1; // +1 perché include entrambi i giorni
    }
    
    /**
     * Verifica se l'assenza si sovrappone a un dato intervallo di date
     * 
     * @param string|\Carbon\Carbon $dataInizio Data di inizio da verificare
     * @param string|\Carbon\Carbon $dataFine Data di fine da verificare
     * @return bool True se c'è sovrapposizione
     */
    public function siSovrapponeA($dataInizio, $dataFine)
    {
        $inizio = Carbon::parse($dataInizio);
        $fine = Carbon::parse($dataFine);
        $assenzaInizio = Carbon::parse($this->data_inizio);
        $assenzaFine = Carbon::parse($this->data_fine);
        
        return ($assenzaInizio->lte($fine) && $assenzaFine->gte($inizio));
    }

    /**
     * Verifica se l'assenza è approvata
     * 
     * @return bool True se l'assenza è approvata
     */
    public function isApprovata()
    {
        return $this->stato === 'Approvata';
    }

    /**
     * Verifica se l'assenza è in attesa di approvazione
     * 
     * @return bool True se l'assenza è in attesa
     */
    public function isInAttesa()
    {
        return $this->stato === 'Richiesta Ricevuta';
    }

    /**
     * Verifica se l'assenza è un recupero compensativo
     * 
     * @return bool True se è un recupero compensativo
     */
    public function isRecuperoCompensativo()
    {
        return $this->tipologia === 'Recupero Compensativo';
    }

    /**
     * Ottiene una rappresentazione testuale dell'assenza
     * 
     * @return string Descrizione dell'assenza
     */
    public function getDescrizioneCompleta()
    {
        $base = "{$this->tipologia} ({$this->stato})";
        
        if ($this->isRecuperoCompensativo() && $this->orario_inizio && $this->orario_fine) {
            $base .= " - Orario: {$this->orario_inizio} - {$this->orario_fine}";
        }
        
        return $base;
    }

    /**
     * Ottiene il periodo dell'assenza formattato
     * 
     * @return string Periodo formattato (es. "01/01/2024 - 05/01/2024")
     */
    public function getPeriodoFormattato()
    {
        $inizio = $this->data_inizio->format('d/m/Y');
        $fine = $this->data_fine->format('d/m/Y');
        
        return $inizio === $fine ? $inizio : "{$inizio} - {$fine}";
    }

    /**
     * Ottiene la classe CSS per lo stato dell'assenza
     * 
     * @return string Classe CSS appropriata
     */
    public function getStatoCssClass()
    {
        return match($this->stato) {
            'Approvata' => 'badge-success',
            'Richiesta Ricevuta' => 'badge-warning',
            'Rifiutata' => 'badge-danger',
            default => 'badge-secondary'
        };
    }
}
