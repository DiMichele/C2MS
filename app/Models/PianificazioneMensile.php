<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\OrganizationalUnit;

/**
 * Modello per le pianificazioni mensili
 * 
 * Rappresenta un calendario mensile di pianificazione dei servizi
 * (es. "Settembre 2025" dal file Excel)
 * 
 * @property int $id
 * @property int $anno
 * @property int $mese
 * @property string $nome
 * @property string|null $descrizione
 * @property string $stato
 * @property \Carbon\Carbon $data_creazione
 * @property int|null $creato_da
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \App\Models\User|null $creatore
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PianificazioneGiornaliera[] $pianificazioniGiornaliere
 */
class PianificazioneMensile extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     */
    protected $table = 'pianificazioni_mensili';

    /**
     * Attributi assegnabili in massa
     */
    protected $fillable = [
        'anno',
        'mese',
        'nome',
        'descrizione',
        'stato',
        'data_creazione',
        'creato_da',
        'organizational_unit_id', // Nuova gerarchia organizzativa
    ];

    /**
     * Attributi che devono essere convertiti in tipi nativi
     */
    protected $casts = [
        'anno' => 'integer',
        'mese' => 'integer',
        'data_creazione' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Unità organizzativa (nuova gerarchia)
     */
    public function organizationalUnit()
    {
        return $this->belongsTo(OrganizationalUnit::class, 'organizational_unit_id');
    }

    /**
     * Utente che ha creato la pianificazione
     */
    public function creatore()
    {
        return $this->belongsTo(User::class, 'creato_da');
    }

    /**
     * Pianificazioni giornaliere associate
     */
    public function pianificazioniGiornaliere()
    {
        return $this->hasMany(PianificazioneGiornaliera::class);
    }

    /**
     * Pianificazioni per un giorno specifico
     */
    public function pianificazioniGiorno($giorno)
    {
        return $this->pianificazioniGiornaliere()
                    ->where('giorno', $giorno)
                    ->with(['militare', 'tipoServizio']);
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per anno specifico
     */
    public function scopePerAnno($query, $anno)
    {
        return $query->where('anno', $anno);
    }

    /**
     * Scope per mese specifico
     */
    public function scopePerMese($query, $mese)
    {
        return $query->where('mese', $mese);
    }

    /**
     * Scope per stato specifico
     */
    public function scopePerStato($query, $stato)
    {
        return $query->where('stato', $stato);
    }

    /**
     * Scope per pianificazioni attive
     */
    public function scopeAttive($query)
    {
        return $query->where('stato', 'attiva');
    }

    /**
     * Scope ordinate per data (anno, mese)
     */
    public function scopeOrdinatePerData($query)
    {
        return $query->orderBy('anno', 'desc')->orderBy('mese', 'desc');
    }

    // ==========================================
    // METODI STATICI
    // ==========================================

    /**
     * Ottiene tutti gli stati disponibili
     */
    public static function getStatiDisponibili()
    {
        return [
            'bozza' => 'Bozza',
            'attiva' => 'Attiva',
            'completata' => 'Completata',
            'archiviata' => 'Archiviata'
        ];
    }

    /**
     * Trova pianificazione per anno e mese
     */
    public static function findByAnnoMese($anno, $mese)
    {
        return static::where('anno', $anno)
                     ->where('mese', $mese)
                     ->first();
    }

    /**
     * Crea una nuova pianificazione mensile
     */
    public static function creaNuova($anno, $mese, $nome = null, $creatoId = null)
    {
        $nome = $nome ?: static::getNomeMeseAnno($anno, $mese);
        
        return static::create([
            'anno' => $anno,
            'mese' => $mese,
            'nome' => $nome,
            'stato' => 'bozza',
            'data_creazione' => Carbon::today(),
            'creato_da' => $creatoId
        ]);
    }

    /**
     * Ottiene il nome del mese in italiano
     */
    public static function getNomeMese($mese)
    {
        $nomiMesi = [
            1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
            5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
            9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
        ];

        return $nomiMesi[$mese] ?? "Mese {$mese}";
    }

    /**
     * Ottiene nome formattato "Mese Anno"
     */
    public static function getNomeMeseAnno($anno, $mese)
    {
        return static::getNomeMese($mese) . ' ' . $anno;
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Ottiene il nome del mese per questa istanza
     */
    public function getNomeMeseIstanza()
    {
        return static::getNomeMese($this->mese);
    }

    /**
     * Ottiene nome formattato "Mese Anno" per questa istanza
     */
    public function getNomeMeseAnnoIstanza()
    {
        return static::getNomeMeseAnno($this->anno, $this->mese);
    }

    /**
     * Ottiene il numero di giorni nel mese
     */
    public function getNumeroGiorni()
    {
        return Carbon::create($this->anno, $this->mese, 1)->daysInMonth;
    }

    /**
     * Ottiene la data di inizio del mese
     */
    public function getDataInizio()
    {
        return Carbon::create($this->anno, $this->mese, 1);
    }

    /**
     * Ottiene la data di fine del mese
     */
    public function getDataFine()
    {
        return Carbon::create($this->anno, $this->mese, 1)->endOfMonth();
    }

    /**
     * Verifica se è il mese corrente
     */
    public function isMeseCorrente()
    {
        $oggi = Carbon::today();
        return $this->anno === $oggi->year && $this->mese === $oggi->month;
    }

    /**
     * Verifica se è nel passato
     */
    public function isPassato()
    {
        return $this->getDataFine()->lt(Carbon::today());
    }

    /**
     * Verifica se è nel futuro
     */
    public function isFuturo()
    {
        return $this->getDataInizio()->gt(Carbon::today());
    }

    /**
     * Ottiene lo stato
     */
    public function getStato()
    {
        return $this->stato;
    }

    /**
     * Ottiene il nome dello stato
     */
    public function getNomeStato()
    {
        $stati = static::getStatiDisponibili();
        return $stati[$this->stato] ?? $this->stato;
    }

    /**
     * Ottiene la classe CSS per lo stato
     */
    public function getStatoCssClass()
    {
        return match($this->stato) {
            'bozza' => 'badge-secondary',
            'attiva' => 'badge-primary',
            'completata' => 'badge-success',
            'archiviata' => 'badge-dark',
            default => 'badge-light'
        };
    }

    /**
     * Verifica se è modificabile
     */
    public function isModificabile()
    {
        return in_array($this->stato, ['bozza', 'attiva']);
    }

    /**
     * Attiva la pianificazione
     */
    public function attiva()
    {
        $this->stato = 'attiva';
        return $this->save();
    }

    /**
     * Completa la pianificazione
     */
    public function completa()
    {
        $this->stato = 'completata';
        return $this->save();
    }

    /**
     * Archivia la pianificazione
     */
    public function archivia()
    {
        $this->stato = 'archiviata';
        return $this->save();
    }

    /**
     * Ottiene le statistiche della pianificazione
     */
    public function getStatistiche()
    {
        $pianificazioni = $this->pianificazioniGiornaliere;
        
        return [
            'totale_pianificazioni' => $pianificazioni->count(),
            'militari_coinvolti' => $pianificazioni->pluck('militare_id')->unique()->count(),
            'giorni_pianificati' => $pianificazioni->pluck('giorno')->unique()->count(),
            'tipi_servizio_utilizzati' => $pianificazioni->pluck('tipo_servizio_id')->unique()->count()
        ];
    }

    /**
     * Copia pianificazioni da un altro mese
     */
    public function copiaDa(PianificazioneMensile $altraPianificazione)
    {
        $altrePianificazioni = $altraPianificazione->pianificazioniGiornaliere()
                                                  ->with(['militare', 'tipoServizio'])
                                                  ->get();

        foreach ($altrePianificazioni as $pianificazione) {
            // Verifica che il giorno esista nel mese di destinazione
            if ($pianificazione->giorno <= $this->getNumeroGiorni()) {
                PianificazioneGiornaliera::create([
                    'pianificazione_mensile_id' => $this->id,
                    'militare_id' => $pianificazione->militare_id,
                    'giorno' => $pianificazione->giorno,
                    'tipo_servizio_id' => $pianificazione->tipo_servizio_id,
                    'note' => $pianificazione->note
                ]);
            }
        }

        return true;
    }

    /**
     * Rappresentazione testuale
     */
    public function __toString()
    {
        return $this->getNomeMeseAnno();
    }
}
