<?php

/**
 * SUGECO: Sistema Unico di Gestione e Controllo
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
 * Modello per gli eventi militari
 * 
 * Questo modello rappresenta gli eventi a cui partecipano i militari,
 * come missioni, addestramenti, servizi speciali, ecc.
 * Include metodi per la gestione delle date e controlli di sovrapposizione.
 * 
 * @package App\Models
 * @version 1.0
 * 
 * @property int $id ID univoco dell'evento
 * @property int $militare_id ID del militare associato
 * @property string $tipologia Tipologia dell'evento
 * @property string $nome Nome/descrizione dell'evento
 * @property \Illuminate\Support\Carbon $data_inizio Data di inizio
 * @property \Illuminate\Support\Carbon $data_fine Data di fine
 * @property string $localita Località dell'evento
 * @property \Illuminate\Support\Carbon|null $created_at Data creazione
 * @property \Illuminate\Support\Carbon|null $updated_at Data ultimo aggiornamento
 * 
 * @property-read \App\Models\Militare $militare Militare associato all'evento
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|Evento attiviOggi() Eventi attivi oggi
 * @method static \Illuminate\Database\Eloquent\Builder|Evento futuri() Eventi futuri
 * @method static \Illuminate\Database\Eloquent\Builder|Evento passati() Eventi passati
 * @method static \Illuminate\Database\Eloquent\Builder|Evento perTipologia(string $tipologia) Eventi per tipologia
 */
class Evento extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     * 
     * @var string
     */
    protected $table = 'eventi';

    /**
     * Gli attributi che sono mass assignable
     * 
     * @var array<string>
     */
    protected $fillable = [
        'militare_id',
        'tipologia',
        'nome',
        'data_inizio',
        'data_fine',
        'localita',
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
        return $this->belongsTo(Militare::class);
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================
    
    /**
     * Scope per filtrare eventi attivi oggi
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAttiviOggi($query)
    {
        $oggi = Carbon::today()->format('Y-m-d');
        
        return $query->where(function($q) use ($oggi) {
            $q->where('data_inizio', '<=', $oggi)
              ->where('data_fine', '>=', $oggi);
        });
    }
    
    /**
     * Scope per filtrare eventi futuri
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFuturi($query)
    {
        $oggi = Carbon::today()->format('Y-m-d');
        
        return $query->where('data_inizio', '>', $oggi);
    }
    
    /**
     * Scope per filtrare eventi passati
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePassati($query)
    {
        $oggi = Carbon::today()->format('Y-m-d');
        
        return $query->where('data_fine', '<', $oggi);
    }
    
    /**
     * Scope per filtrare eventi per tipologia
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $tipologia Tipologia da filtrare
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerTipologia($query, $tipologia)
    {
        return $query->where('tipologia', $tipologia);
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================
    
    /**
     * Verifica se l'evento è attivo oggi
     * 
     * @return bool True se l'evento è attivo oggi
     */
    public function isAttivoOggi()
    {
        $oggi = Carbon::today();
        
        return Carbon::parse($this->data_inizio)->lte($oggi) && 
               Carbon::parse($this->data_fine)->gte($oggi);
    }
    
    /**
     * Ottiene la durata dell'evento in giorni
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
     * Verifica se l'evento si sovrappone a un dato intervallo di date
     * 
     * @param string|\Carbon\Carbon $dataInizio Data di inizio da verificare
     * @param string|\Carbon\Carbon $dataFine Data di fine da verificare
     * @return bool True se c'è sovrapposizione
     */
    public function siSovrapponeA($dataInizio, $dataFine)
    {
        $inizio = Carbon::parse($dataInizio);
        $fine = Carbon::parse($dataFine);
        $eventoInizio = Carbon::parse($this->data_inizio);
        $eventoFine = Carbon::parse($this->data_fine);
        
        return ($eventoInizio->lte($fine) && $eventoFine->gte($inizio));
    }

    /**
     * Ottiene una rappresentazione testuale dell'evento
     * 
     * @return string Descrizione dell'evento
     */
    public function getDescrizioneCompleta()
    {
        return "{$this->tipologia}: {$this->nome} ({$this->localita})";
    }

    /**
     * Ottiene il periodo dell'evento formattato
     * 
     * @return string Periodo formattato (es. "01/01/2024 - 05/01/2024")
     */
    public function getPeriodoFormattato()
    {
        $inizio = $this->data_inizio->format('d/m/Y');
        $fine = $this->data_fine->format('d/m/Y');
        
        return $inizio === $fine ? $inizio : "{$inizio} - {$fine}";
    }
}
