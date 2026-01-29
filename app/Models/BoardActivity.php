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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\OrganizationalUnit;

/**
 * Modello per le attività della bacheca (board)
 * 
 * Questo modello rappresenta le attività organizzate nella bacheca di gestione,
 * con supporto per drag & drop, allegati e assegnazione di militari.
 * 
 * @package App\Models
 * @version 1.0
 * 
 * @property int $id ID univoco dell'attività
 * @property string $title Titolo dell'attività
 * @property string|null $description Descrizione dell'attività
 * @property \Illuminate\Support\Carbon $start_date Data di inizio
 * @property \Illuminate\Support\Carbon|null $end_date Data di fine
 * @property int $column_id ID della colonna di appartenenza
 * @property int $created_by ID dell'utente creatore
 * @property string|null $status Stato dell'attività
 * @property int $order Ordine di visualizzazione nella colonna
 * @property \Illuminate\Support\Carbon|null $created_at Data creazione
 * @property \Illuminate\Support\Carbon|null $updated_at Data ultimo aggiornamento
 * 
 * @property-read \App\Models\BoardColumn $column Colonna di appartenenza
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Militare[] $militari Militari assegnati
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ActivityAttachment[] $attachments Allegati
 * @property-read \App\Models\User $creator Utente creatore
 */
class BoardActivity extends Model
{
    use HasFactory;

    /**
     * Gli attributi che sono mass assignable
     * 
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'column_id',
        'created_by',
        'compagnia_id',
        'compagnia_mounting_id',
        'organizational_unit_id', // Nuova gerarchia organizzativa
        'sigla_cpt_suggerita',
        'status',
        'order',
        'prenotazione_approntamento_id'
    ];

    /**
     * Gli attributi che dovrebbero essere cast
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime'
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Relazione con la colonna di appartenenza
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function column()
    {
        return $this->belongsTo(BoardColumn::class);
    }

    /**
     * Unità organizzativa (nuova gerarchia)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organizationalUnit()
    {
        return $this->belongsTo(OrganizationalUnit::class, 'organizational_unit_id');
    }

    /**
     * Relazione many-to-many con i militari assegnati
     * 
     * I militari sono ordinati per grado (decrescente) e poi alfabeticamente.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function militari()
    {
        return $this->belongsToMany(Militare::class, 'activity_militare', 'activity_id', 'militare_id')
                    ->join('gradi', 'militari.grado_id', '=', 'gradi.id')
                    ->orderByDesc('gradi.ordine')
                    ->orderBy('militari.cognome')
                    ->orderBy('militari.nome')
                    ->select('militari.*');
    }

    /**
     * Relazione con gli allegati dell'attività
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attachments()
    {
        return $this->hasMany(ActivityAttachment::class, 'activity_id');
    }

    /**
     * Relazione con l'utente creatore dell'attività
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relazione con la compagnia di appartenenza
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function compagnia()
    {
        return $this->belongsTo(Compagnia::class);
    }

    /**
     * Relazione con la compagnia mounting (organizzatrice)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function compagniaMounting()
    {
        return $this->belongsTo(Compagnia::class, 'compagnia_mounting_id');
    }

    /**
     * Relazione con la prenotazione approntamento collegata
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function prenotazioneApprontamento()
    {
        return $this->belongsTo(PrenotazioneApprontamento::class);
    }

    /**
     * Verifica se l'attività è collegata a una prenotazione approntamento
     * 
     * @return bool
     */
    public function isCollegataAPrenotazione(): bool
    {
        return $this->prenotazione_approntamento_id !== null;
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Verifica se l'attività è attiva oggi
     * 
     * @return bool True se l'attività è attiva oggi
     */
    public function isAttivaOggi()
    {
        $oggi = now();
        
        return $this->start_date->lte($oggi) && 
               ($this->end_date === null || $this->end_date->gte($oggi));
    }

    /**
     * Ottiene la durata dell'attività in giorni
     * 
     * @return int|null Numero di giorni di durata o null se non ha fine
     */
    public function getDurata()
    {
        if (!$this->end_date) {
            return null;
        }
        
        return $this->end_date->diffInDays($this->start_date) + 1;
    }

    /**
     * Ottiene il periodo dell'attività formattato
     * 
     * @return string Periodo formattato
     */
    public function getPeriodoFormattato()
    {
        $inizio = $this->start_date->format('d/m/Y');
        
        if (!$this->end_date) {
            return "Dal {$inizio}";
        }
        
        $fine = $this->end_date->format('d/m/Y');
        
        return $inizio === $fine ? $inizio : "{$inizio} - {$fine}";
    }

    /**
     * Ottiene il numero di militari assegnati
     * 
     * @return int Numero di militari
     */
    public function getNumeroMilitari()
    {
        return $this->militari()->count();
    }

    /**
     * Ottiene il numero di allegati
     * 
     * @return int Numero di allegati
     */
    public function getNumeroAllegati()
    {
        return $this->attachments()->count();
    }

    /**
     * Verifica se l'attività ha militari assegnati
     * 
     * @return bool True se ha militari assegnati
     */
    public function hasMilitariAssegnati()
    {
        return $this->getNumeroMilitari() > 0;
    }

    /**
     * Verifica se l'attività ha allegati
     * 
     * @return bool True se ha allegati
     */
    public function hasAllegati()
    {
        return $this->getNumeroAllegati() > 0;
    }

    /**
     * Ottiene la classe CSS per lo stato dell'attività
     * 
     * @return string Classe CSS appropriata
     */
    public function getStatoCssClass()
    {
        return match($this->status) {
            'completed' => 'badge-success',
            'in_progress' => 'badge-primary',
            'pending' => 'badge-warning',
            'cancelled' => 'badge-danger',
            default => 'badge-secondary'
        };
    }
} 
