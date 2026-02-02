<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\OrganizationalUnit;
use App\Traits\BelongsToOrganizationalUnit;

/**
 * Modello per le pianificazioni giornaliere
 * 
 * Rappresenta l'assegnazione di un tipo di servizio a un militare
 * per un giorno specifico di un mese pianificato.
 * 
 * @property int $id
 * @property int $pianificazione_mensile_id
 * @property int $militare_id
 * @property int $giorno
 * @property int|null $tipo_servizio_id
 * @property string|null $note
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \App\Models\PianificazioneMensile $pianificazioneMensile
 * @property-read \App\Models\Militare $militare
 * @property-read \App\Models\TipoServizio|null $tipoServizio
 */
class PianificazioneGiornaliera extends Model
{
    use HasFactory, BelongsToOrganizationalUnit;

    /**
     * Nome della tabella associata al modello
     */
    protected $table = 'pianificazioni_giornaliere';

    /**
     * Attributi assegnabili in massa
     */
    protected $fillable = [
        'pianificazione_mensile_id',
        'militare_id',
        'giorno',
        'tipo_servizio_id',
        'organizational_unit_id', // Nuova gerarchia organizzativa
        'note',
        'prenotazione_approntamento_id',
        'prenotazione_pefo_id'
    ];

    /**
     * Attributi che devono essere convertiti in tipi nativi
     */
    protected $casts = [
        'giorno' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Pianificazione mensile di appartenenza
     */
    public function pianificazioneMensile()
    {
        return $this->belongsTo(PianificazioneMensile::class);
    }

    /**
     * Unità organizzativa (nuova gerarchia)
     */
    public function organizationalUnit()
    {
        return $this->belongsTo(OrganizationalUnit::class, 'organizational_unit_id');
    }

    /**
     * Militare assegnato
     */
    public function militare()
    {
        return $this->belongsTo(Militare::class);
    }

    /**
     * Tipo di servizio assegnato
     */
    public function tipoServizio()
    {
        return $this->belongsTo(TipoServizio::class);
    }

    /**
     * Prenotazione approntamento collegata
     */
    public function prenotazioneApprontamento()
    {
        return $this->belongsTo(PrenotazioneApprontamento::class);
    }

    /**
     * Prenotazione PEFO collegata
     */
    public function prenotazionePefo()
    {
        return $this->belongsTo(PrenotazionePefo::class);
    }

    /**
     * Verifica se è collegata a una prenotazione approntamento
     */
    public function isCollegataAPrenotazione(): bool
    {
        return $this->prenotazione_approntamento_id !== null;
    }

    /**
     * Verifica se è collegata a una prenotazione PEFO
     */
    public function isCollegataAPrenotazionePefo(): bool
    {
        return $this->prenotazione_pefo_id !== null;
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per pianificazione mensile specifica
     */
    public function scopePerPianificazione($query, $pianificazioneId)
    {
        return $query->where('pianificazione_mensile_id', $pianificazioneId);
    }

    /**
     * Scope per militare specifico
     */
    public function scopePerMilitare($query, $militareId)
    {
        return $query->where('militare_id', $militareId);
    }

    /**
     * Scope per giorno specifico
     */
    public function scopePerGiorno($query, $giorno)
    {
        return $query->where('giorno', $giorno);
    }

    /**
     * Scope per tipo servizio specifico
     */
    public function scopePerTipoServizio($query, $tipoServizioId)
    {
        return $query->where('tipo_servizio_id', $tipoServizioId);
    }

    /**
     * Scope per anno e mese specifici
     */
    public function scopePerAnnoMese($query, $anno, $mese)
    {
        return $query->whereHas('pianificazioneMensile', function($q) use ($anno, $mese) {
            $q->where('anno', $anno)->where('mese', $mese);
        });
    }

    /**
     * Scope ordinate per giorno
     */
    public function scopeOrdinatePerGiorno($query)
    {
        return $query->orderBy('giorno');
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Ottiene la data completa (anno-mese-giorno)
     */
    public function getDataCompleta()
    {
        return Carbon::create(
            $this->pianificazioneMensile->anno,
            $this->pianificazioneMensile->mese,
            $this->giorno
        );
    }

    /**
     * Ottiene la data formattata per la visualizzazione
     */
    public function getDataFormattata()
    {
        return $this->getDataCompleta()->format('d/m/Y');
    }

    /**
     * Ottiene il giorno della settimana
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

        $nomeGiorno = $this->getDataCompleta()->format('l');
        return $giorni[$nomeGiorno] ?? $nomeGiorno;
    }

    /**
     * Verifica se è un giorno nel passato
     */
    public function isPassato()
    {
        return $this->getDataCompleta()->lt(Carbon::today());
    }

    /**
     * Verifica se è oggi
     */
    public function isOggi()
    {
        return $this->getDataCompleta()->isToday();
    }

    /**
     * Verifica se è nel futuro
     */
    public function isFuturo()
    {
        return $this->getDataCompleta()->gt(Carbon::today());
    }

    /**
     * Verifica se è un weekend
     */
    public function isWeekend()
    {
        return $this->getDataCompleta()->isWeekend();
    }

    /**
     * Ottiene il codice del tipo di servizio
     */
    public function getCodiceServizio()
    {
        return $this->tipoServizio ? $this->tipoServizio->codice : '';
    }

    /**
     * Ottiene il nome del tipo di servizio
     */
    public function getNomeServizio()
    {
        return $this->tipoServizio ? $this->tipoServizio->nome : 'Non assegnato';
    }

    /**
     * Ottiene il colore del badge per il tipo di servizio
     */
    public function getColoreServizio()
    {
        return $this->tipoServizio ? $this->tipoServizio->colore_badge : '#6c757d';
    }

    /**
     * Verifica se ha un servizio assegnato
     */
    public function hasServizioAssegnato()
    {
        return $this->tipo_servizio_id !== null;
    }

    /**
     * Verifica se è modificabile
     */
    public function isModificabile()
    {
        return $this->pianificazioneMensile->isModificabile();
    }

    /**
     * Crea o aggiorna una pianificazione giornaliera
     */
    public static function createOrUpdate($pianificazioneMensileId, $militareId, $giorno, $tipoServizioId = null, $note = null)
    {
        return static::updateOrCreate(
            [
                'pianificazione_mensile_id' => $pianificazioneMensileId,
                'militare_id' => $militareId,
                'giorno' => $giorno
            ],
            [
                'tipo_servizio_id' => $tipoServizioId,
                'note' => $note
            ]
        );
    }

    /**
     * Rimuove l'assegnazione del servizio
     */
    public function rimuoviServizio()
    {
        $this->tipo_servizio_id = null;
        $this->note = null;
        return $this->save();
    }

    /**
     * Copia l'assegnazione a un altro giorno
     */
    public function copiaA($nuovoGiorno, $nuovaPianificazioneId = null)
    {
        $nuovaPianificazioneId = $nuovaPianificazioneId ?: $this->pianificazione_mensile_id;

        return static::create([
            'pianificazione_mensile_id' => $nuovaPianificazioneId,
            'militare_id' => $this->militare_id,
            'giorno' => $nuovoGiorno,
            'tipo_servizio_id' => $this->tipo_servizio_id,
            'note' => $this->note
        ]);
    }

    /**
     * Converte in presenza effettiva
     */
    public function convertiInPresenza()
    {
        if (!$this->hasServizioAssegnato()) {
            return null;
        }

        // Determina lo stato della presenza basato sul tipo di servizio
        $stato = 'Presente';
        if ($this->tipoServizio && $this->tipoServizio->categoria === 'assenza') {
            $stato = 'Assente';
        } elseif ($this->tipoServizio && $this->tipoServizio->categoria === 'permesso') {
            $stato = 'Permesso';
        }

        return Presenza::updateOrCreate(
            [
                'militare_id' => $this->militare_id,
                'data' => $this->getDataCompleta()
            ],
            [
                'stato' => $stato,
                'tipo_servizio_id' => $this->tipo_servizio_id,
                'note_servizio' => $this->note
            ]
        );
    }

    /**
     * Rappresentazione testuale
     */
    public function __toString()
    {
        return sprintf(
            "%s - %s (%d/%d/%d)",
            $this->militare->getNomeCompleto(false),
            $this->getNomeServizio(),
            $this->giorno,
            $this->pianificazioneMensile->mese,
            $this->pianificazioneMensile->anno
        );
    }
}
