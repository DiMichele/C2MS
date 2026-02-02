<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Modello per le Prenotazioni PEFO
 * 
 * Gestisce le prenotazioni per i corsi PEFO (Prove di Efficienza Fisica Operativa).
 * Ogni prenotazione ha un tipo (agility/resistenza) e una data.
 * I militari vengono aggiunti con stato "da confermare" e poi confermati singolarmente.
 * 
 * @property int $id
 * @property string $tipo_prova (agility, resistenza)
 * @property string|null $nome_prenotazione
 * @property \Carbon\Carbon $data_prenotazione
 * @property string $stato (attivo, annullato)
 * @property string|null $note
 * @property int|null $created_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Militare[] $militari
 * @property-read \App\Models\User|null $creatore
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PianificazioneGiornaliera[] $pianificazioniGiornaliere
 */
class PrenotazionePefo extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     */
    protected $table = 'prenotazioni_pefo';

    /**
     * Attributi assegnabili in massa
     */
    protected $fillable = [
        'tipo_prova',
        'nome_prenotazione',
        'data_prenotazione',
        'stato',
        'note',
        'created_by'
    ];

    /**
     * Attributi che devono essere convertiti in tipi nativi
     */
    protected $casts = [
        'data_prenotazione' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Tipi di prova possibili
     */
    const TIPO_AGILITY = 'agility';
    const TIPO_RESISTENZA = 'resistenza';

    /**
     * Stati possibili della prenotazione
     */
    const STATO_ATTIVO = 'attivo';
    const STATO_ANNULLATO = 'annullato';

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Militari assegnati a questa prenotazione
     * Include i campi pivot per la conferma singola
     */
    public function militari()
    {
        return $this->belongsToMany(Militare::class, 'militari_prenotazioni_pefo', 'prenotazione_pefo_id', 'militare_id')
            ->withPivot('confermato', 'data_conferma', 'confermato_da')
            ->withTimestamps();
    }

    /**
     * Utente che ha creato la prenotazione
     */
    public function creatore()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Pianificazioni giornaliere CPT collegate a questa prenotazione
     */
    public function pianificazioniGiornaliere()
    {
        return $this->hasMany(PianificazioneGiornaliera::class, 'prenotazione_pefo_id');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per prenotazioni attive (non annullate)
     */
    public function scopeAttive($query)
    {
        return $query->where('stato', self::STATO_ATTIVO);
    }

    /**
     * Scope per prenotazioni annullate
     */
    public function scopeAnnullate($query)
    {
        return $query->where('stato', self::STATO_ANNULLATO);
    }

    /**
     * Scope per tipo agility
     */
    public function scopeAgility($query)
    {
        return $query->where('tipo_prova', self::TIPO_AGILITY);
    }

    /**
     * Scope per tipo resistenza
     */
    public function scopeResistenza($query)
    {
        return $query->where('tipo_prova', self::TIPO_RESISTENZA);
    }

    /**
     * Scope per prenotazioni per una data specifica
     */
    public function scopePerData($query, $data)
    {
        return $query->whereDate('data_prenotazione', $data);
    }

    /**
     * Scope per prenotazioni future
     */
    public function scopeFuture($query)
    {
        return $query->where('data_prenotazione', '>=', Carbon::today());
    }

    /**
     * Scope per prenotazioni passate
     */
    public function scopePassate($query)
    {
        return $query->where('data_prenotazione', '<', Carbon::today());
    }

    /**
     * Scope ordinate per data
     */
    public function scopeOrdinatePerData($query, $direzione = 'asc')
    {
        return $query->orderBy('data_prenotazione', $direzione);
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Genera il nome automatico basato su tipo e data
     */
    public function getNomeAutoGenerato(): string
    {
        $tipo = ucfirst($this->tipo_prova);
        $data = $this->data_prenotazione->format('d/m/Y');
        return "{$tipo} - {$data}";
    }

    /**
     * Ottiene il nome da visualizzare (auto-generato o personalizzato)
     */
    public function getNomeVisualizzato(): string
    {
        return $this->nome_prenotazione ?: $this->getNomeAutoGenerato();
    }

    /**
     * Verifica se è tipo agility
     */
    public function isAgility(): bool
    {
        return $this->tipo_prova === self::TIPO_AGILITY;
    }

    /**
     * Verifica se è tipo resistenza
     */
    public function isResistenza(): bool
    {
        return $this->tipo_prova === self::TIPO_RESISTENZA;
    }

    /**
     * Verifica se la prenotazione è attiva
     */
    public function isAttiva(): bool
    {
        return $this->stato === self::STATO_ATTIVO;
    }

    /**
     * Verifica se la prenotazione è annullata
     */
    public function isAnnullata(): bool
    {
        return $this->stato === self::STATO_ANNULLATO;
    }

    /**
     * Verifica se la data è nel passato
     */
    public function isPassata(): bool
    {
        return $this->data_prenotazione->lt(Carbon::today());
    }

    /**
     * Verifica se la data è oggi
     */
    public function isOggi(): bool
    {
        return $this->data_prenotazione->isToday();
    }

    /**
     * Verifica se la data è nel futuro
     */
    public function isFutura(): bool
    {
        return $this->data_prenotazione->gt(Carbon::today());
    }

    /**
     * Annulla la prenotazione
     */
    public function annulla(): bool
    {
        $this->stato = self::STATO_ANNULLATO;
        return $this->save();
    }

    // ==========================================
    // METODI PER GESTIONE MILITARI
    // ==========================================

    /**
     * Ottiene il numero totale di militari assegnati
     */
    public function getNumeroMilitari(): int
    {
        return $this->militari()->count();
    }

    /**
     * Ottiene il numero di militari confermati
     */
    public function getNumeroMilitariConfermati(): int
    {
        return $this->militari()->wherePivot('confermato', true)->count();
    }

    /**
     * Ottiene il numero di militari da confermare
     */
    public function getNumeroMilitariDaConfermare(): int
    {
        return $this->militari()->wherePivot('confermato', false)->count();
    }

    /**
     * Ottiene i militari confermati
     */
    public function getMilitariConfermati()
    {
        return $this->militari()->wherePivot('confermato', true)->get();
    }

    /**
     * Ottiene i militari da confermare
     */
    public function getMilitariDaConfermare()
    {
        return $this->militari()->wherePivot('confermato', false)->get();
    }

    /**
     * Verifica se un militare è già assegnato a questa prenotazione
     */
    public function haMilitare(int $militareId): bool
    {
        return $this->militari()->where('militare_id', $militareId)->exists();
    }

    /**
     * Verifica se un militare è confermato
     */
    public function isMilitareConfermato(int $militareId): bool
    {
        return $this->militari()
            ->where('militare_id', $militareId)
            ->wherePivot('confermato', true)
            ->exists();
    }

    /**
     * Ottiene il label del tipo prova
     */
    public function getTipoProvaLabel(): string
    {
        return match($this->tipo_prova) {
            self::TIPO_AGILITY => 'Agility',
            self::TIPO_RESISTENZA => 'Resistenza',
            default => ucfirst($this->tipo_prova)
        };
    }

    /**
     * Ottiene la classe CSS per il colore dello stato
     */
    public function getStatoCssClass(): string
    {
        return match($this->stato) {
            self::STATO_ATTIVO => 'bg-success', // Verde
            self::STATO_ANNULLATO => 'bg-secondary', // Grigio
            default => 'bg-light'
        };
    }

    /**
     * Ottiene lo stile CSS per la cella/card
     */
    public function getCellStyle(): string
    {
        return match($this->stato) {
            self::STATO_ATTIVO => 'background-color: #d4edda; color: #155724;', // Verde chiaro
            self::STATO_ANNULLATO => 'background-color: #e2e3e5; color: #6c757d;', // Grigio chiaro
            default => ''
        };
    }

    /**
     * Ottiene il label dello stato
     */
    public function getStatoLabel(): string
    {
        return match($this->stato) {
            self::STATO_ATTIVO => 'Attivo',
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

    /**
     * Formatta la data completa con giorno settimana
     */
    public function getDataCompletaFormattata(): string
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

        $nomeGiorno = $this->data_prenotazione->format('l');
        $giorno = $giorni[$nomeGiorno] ?? $nomeGiorno;

        return $giorno . ' ' . $this->data_prenotazione->format('d/m/Y');
    }

    /**
     * Rappresentazione testuale
     */
    public function __toString(): string
    {
        return sprintf(
            "%s (%s)",
            $this->getNomeVisualizzato(),
            $this->getStatoLabel()
        );
    }
}
