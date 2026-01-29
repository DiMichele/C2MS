<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Modello per le Prenotazioni Approntamenti
 * 
 * Gestisce le prenotazioni dei militari per specifiche cattedre
 * Permette di prenotare un militare per una cattedra e poi confermare la partecipazione
 * 
 * @property int $id
 * @property int $militare_id
 * @property int $teatro_operativo_id
 * @property string $cattedra
 * @property \Carbon\Carbon $data_prenotazione
 * @property string $stato (prenotato, confermato, annullato)
 * @property string|null $note
 * @property int|null $created_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \App\Models\Militare $militare
 * @property-read \App\Models\TeatroOperativo $teatroOperativo
 * @property-read \App\Models\User|null $creatore
 */
class PrenotazioneApprontamento extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     */
    protected $table = 'prenotazioni_approntamenti';

    /**
     * Attributi assegnabili in massa
     */
    protected $fillable = [
        'militare_id',
        'teatro_operativo_id',
        'cattedra',
        'data_prenotazione',
        'stato',
        'data_conferma',
        'confirmed_by',
        'note',
        'created_by'
    ];

    /**
     * Attributi che devono essere convertiti in tipi nativi
     */
    protected $casts = [
        'data_prenotazione' => 'date',
        'data_conferma' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Stati possibili della prenotazione
     */
    const STATO_PRENOTATO = 'prenotato';
    const STATO_CONFERMATO = 'confermato';
    const STATO_ANNULLATO = 'annullato';

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Militare della prenotazione
     */
    public function militare()
    {
        return $this->belongsTo(Militare::class);
    }

    /**
     * Teatro operativo della prenotazione
     */
    public function teatroOperativo()
    {
        return $this->belongsTo(TeatroOperativo::class);
    }

    /**
     * Utente che ha creato la prenotazione
     */
    public function creatore()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Utente che ha confermato la prenotazione
     */
    public function confermatoDa()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Attività Board collegata a questa prenotazione
     */
    public function boardActivity()
    {
        return $this->hasOne(BoardActivity::class, 'prenotazione_approntamento_id');
    }

    /**
     * Pianificazioni giornaliere CPT collegate a questa prenotazione
     */
    public function pianificazioniGiornaliere()
    {
        return $this->hasMany(PianificazioneGiornaliera::class, 'prenotazione_approntamento_id');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per prenotazioni attive (non annullate)
     */
    public function scopeAttive($query)
    {
        return $query->where('stato', '!=', self::STATO_ANNULLATO);
    }

    /**
     * Scope per prenotazioni in stato prenotato
     */
    public function scopePrenotate($query)
    {
        return $query->where('stato', self::STATO_PRENOTATO);
    }

    /**
     * Scope per prenotazioni confermate
     */
    public function scopeConfermate($query)
    {
        return $query->where('stato', self::STATO_CONFERMATO);
    }

    /**
     * Scope per prenotazioni di un teatro specifico
     */
    public function scopePerTeatro($query, $teatroId)
    {
        return $query->where('teatro_operativo_id', $teatroId);
    }

    /**
     * Scope per prenotazioni di una cattedra specifica
     */
    public function scopePerCattedra($query, $cattedra)
    {
        return $query->where('cattedra', $cattedra);
    }

    /**
     * Scope per prenotazioni per una data specifica
     */
    public function scopePerData($query, $data)
    {
        return $query->whereDate('data_prenotazione', $data);
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Verifica se la prenotazione è prenotata (in attesa)
     */
    public function isPrenotata(): bool
    {
        return $this->stato === self::STATO_PRENOTATO;
    }

    /**
     * Verifica se la prenotazione è confermata
     */
    public function isConfermata(): bool
    {
        return $this->stato === self::STATO_CONFERMATO;
    }

    /**
     * Verifica se la prenotazione è annullata
     */
    public function isAnnullata(): bool
    {
        return $this->stato === self::STATO_ANNULLATO;
    }

    /**
     * Conferma la prenotazione
     */
    public function conferma(): bool
    {
        $this->stato = self::STATO_CONFERMATO;
        return $this->save();
    }

    /**
     * Annulla la prenotazione
     */
    public function annulla(): bool
    {
        $this->stato = self::STATO_ANNULLATO;
        return $this->save();
    }

    /**
     * Ottiene la classe CSS per il colore dello stato
     */
    public function getStatoCssClass(): string
    {
        return match($this->stato) {
            self::STATO_PRENOTATO => 'bg-primary', // Blu
            self::STATO_CONFERMATO => 'bg-success', // Verde
            self::STATO_ANNULLATO => 'bg-secondary', // Grigio
            default => 'bg-light'
        };
    }

    /**
     * Ottiene lo stile CSS per la cella
     */
    public function getCellStyle(): string
    {
        return match($this->stato) {
            self::STATO_PRENOTATO => 'background-color: #cce5ff; color: #004085;', // Blu chiaro
            self::STATO_CONFERMATO => 'background-color: #d4edda; color: #155724;', // Verde chiaro
            default => ''
        };
    }

    /**
     * Ottiene il label dello stato
     */
    public function getStatoLabel(): string
    {
        return match($this->stato) {
            self::STATO_PRENOTATO => 'Prenotato',
            self::STATO_CONFERMATO => 'Confermato',
            self::STATO_ANNULLATO => 'Annullato',
            default => $this->stato
        };
    }

    /**
     * Formatta la data della prenotazione
     */
    public function getDataFormattata(): string
    {
        return $this->data_prenotazione->format('d/m/Y');
    }
}
