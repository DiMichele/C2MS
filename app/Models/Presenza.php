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
use Carbon\Carbon;

/**
 * Modello per le presenze dei militari
 * 
 * Questo modello rappresenta le presenze giornaliere dei militari,
 * tracciando lo stato di presenza per ogni giorno lavorativo.
 * 
 * @package App\Models
 * @version 1.0
 * 
 * @property int $id ID univoco della presenza
 * @property int $militare_id ID del militare
 * @property \Illuminate\Support\Carbon $data Data della presenza
 * @property string $stato Stato della presenza (Presente, Assente, ecc.)
 * @property \Illuminate\Support\Carbon|null $created_at Data creazione
 * @property \Illuminate\Support\Carbon|null $updated_at Data ultimo aggiornamento
 * 
 * @property-read \App\Models\Militare $militare Militare associato
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|Presenza oggi() Presenze di oggi
 * @method static \Illuminate\Database\Eloquent\Builder|Presenza perData(\Carbon\Carbon|string $data) Presenze per data specifica
 * @method static \Illuminate\Database\Eloquent\Builder|Presenza perStato(string $stato) Presenze per stato
 * @method static \Illuminate\Database\Eloquent\Builder|Presenza perMilitare(int $militareId) Presenze per militare
 * @method static \Illuminate\Database\Eloquent\Builder|Presenza presenti() Solo presenti
 * @method static \Illuminate\Database\Eloquent\Builder|Presenza assenti() Solo assenti
 */
class Presenza extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     * 
     * @var string
     */
    protected $table = 'presenze';
    
    /**
     * Gli attributi che sono mass assignable
     * 
     * @var array<string>
     */
    protected $fillable = [
        'militare_id', 
        'data', 
        'stato'
    ];
    
    /**
     * Gli attributi che dovrebbero essere cast
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'date',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Relazione con il militare
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
     * Scope per filtrare presenze di oggi
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOggi($query)
    {
        return $query->where('data', Carbon::today()->format('Y-m-d'));
    }
    
    /**
     * Scope per filtrare presenze per data specifica
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Carbon\Carbon|string $data Data da filtrare
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerData($query, $data)
    {
        return $query->where('data', Carbon::parse($data)->format('Y-m-d'));
    }
    
    /**
     * Scope per filtrare presenze per stato
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $stato Stato da filtrare
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerStato($query, $stato)
    {
        return $query->where('stato', $stato);
    }
    
    /**
     * Scope per filtrare presenze per militare
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $militareId ID del militare
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerMilitare($query, $militareId)
    {
        return $query->where('militare_id', $militareId);
    }
    
    /**
     * Scope per ottenere solo i presenti
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePresenti($query)
    {
        return $query->where('stato', 'Presente');
    }
    
    /**
     * Scope per ottenere solo gli assenti
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAssenti($query)
    {
        return $query->where('stato', '!=', 'Presente');
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================
    
    /**
     * Verifica se è un record di presenza per oggi
     * 
     * @return bool True se la presenza è per oggi
     */
    public function isOggi()
    {
        return Carbon::parse($this->data)->isToday();
    }

    /**
     * Verifica se il militare è presente
     * 
     * @return bool True se è presente
     */
    public function isPresente()
    {
        return $this->stato === 'Presente';
    }

    /**
     * Verifica se il militare è assente
     * 
     * @return bool True se è assente
     */
    public function isAssente()
    {
        return $this->stato !== 'Presente';
    }

    /**
     * Ottiene la classe CSS per lo stato della presenza
     * 
     * @return string Classe CSS appropriata
     */
    public function getStatoCssClass()
    {
        return match($this->stato) {
            'Presente' => 'badge-success',
            'Assente' => 'badge-danger',
            'Permesso' => 'badge-warning',
            'Malattia' => 'badge-info',
            'Congedo' => 'badge-secondary',
            default => 'badge-light'
        };
    }

    /**
     * Ottiene l'icona appropriata per lo stato
     * 
     * @return string Classe icona FontAwesome
     */
    public function getStatoIcon()
    {
        return match($this->stato) {
            'Presente' => 'fas fa-check',
            'Assente' => 'fas fa-times',
            'Permesso' => 'fas fa-clock',
            'Malattia' => 'fas fa-user-injured',
            'Congedo' => 'fas fa-calendar-alt',
            default => 'fas fa-question'
        };
    }

    /**
     * Ottiene la data formattata per la visualizzazione
     * 
     * @return string Data formattata (es. "01/01/2024")
     */
    public function getDataFormattata()
    {
        return $this->data->format('d/m/Y');
    }

    /**
     * Ottiene il giorno della settimana in italiano
     * 
     * @return string Nome del giorno in italiano
     */
    public function getGiornoSettimana()
    {
        $giorni = [
            'Monday' => 'Lunedì',
            'Tuesday' => 'Martedì',
            'Wednesday' => 'Mercoledì',
            'Thursday' => 'Giovedì',
            'Friday' => 'Venerdì',
            'Saturday' => 'Sabato',
            'Sunday' => 'Domenica'
        ];
        
        return $giorni[$this->data->format('l')] ?? $this->data->format('l');
    }
}
