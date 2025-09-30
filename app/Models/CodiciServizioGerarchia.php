<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modello per la gerarchia dei codici servizio
 * 
 * Rappresenta la struttura gerarchica dei codici servizio
 * come definita nella pagina CODICI del file Excel CPT.xlsx
 * 
 * @property int $id
 * @property string $codice
 * @property string|null $macro_attivita
 * @property string|null $tipo_attivita
 * @property string $attivita_specifica
 * @property string $impiego
 * @property string|null $descrizione_impiego
 * @property string $colore_badge
 * @property bool $attivo
 * @property int $ordine
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TipoServizio[] $tipiServizio
 */
class CodiciServizioGerarchia extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     */
    protected $table = 'codici_servizio_gerarchia';

    /**
     * Attributi assegnabili in massa
     */
    protected $fillable = [
        'codice',
        'macro_attivita',
        'tipo_attivita',
        'attivita_specifica',
        'impiego',
        'descrizione_impiego',
        'colore_badge',
        'attivo',
        'ordine'
    ];

    /**
     * Attributi che devono essere convertiti in tipi nativi
     */
    protected $casts = [
        'attivo' => 'boolean',
        'ordine' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Tipi di servizio collegati a questo codice
     */
    public function tipiServizio()
    {
        return $this->hasMany(TipoServizio::class, 'codice_gerarchia_id');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per codici attivi
     */
    public function scopeAttivi($query)
    {
        return $query->where('attivo', true);
    }

    /**
     * Scope per macro attività specifica
     */
    public function scopePerMacroAttivita($query, $macroAttivita)
    {
        return $query->where('macro_attivita', $macroAttivita);
    }

    /**
     * Scope per tipo attività specifica
     */
    public function scopePerTipoAttivita($query, $tipoAttivita)
    {
        return $query->where('tipo_attivita', $tipoAttivita);
    }

    /**
     * Scope per impiego specifico
     */
    public function scopePerImpiego($query, $impiego)
    {
        return $query->where('impiego', $impiego);
    }

    /**
     * Scope ordinati per ordine di visualizzazione
     */
    public function scopeOrdinati($query)
    {
        return $query->orderBy('ordine')->orderBy('codice');
    }

    // ==========================================
    // METODI STATICI
    // ==========================================

    /**
     * Ottiene tutti i tipi di impiego disponibili
     */
    public static function getTipiImpiego()
    {
        return [
            'DISPONIBILE' => 'Disponibile',
            'INDISPONIBILE' => 'Indisponibile ma richiamabile',
            'NON_DISPONIBILE' => 'Non disponibile',
            'PRESENTE_SERVIZIO' => 'Presente ed in servizio',
            'DISPONIBILE_ESIGENZA' => 'Disponibile su esigenza'
        ];
    }

    /**
     * Ottiene la struttura gerarchica completa
     */
    public static function getStrutturaGerarchica()
    {
        return static::attivi()
                     ->orderBy('macro_attivita')
                     ->orderBy('tipo_attivita')
                     ->orderBy('ordine')
                     ->get()
                     ->groupBy(['macro_attivita', 'tipo_attivita']);
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Verifica se il codice è attivo
     */
    public function isAttivo()
    {
        return $this->attivo;
    }

    /**
     * Ottiene il nome dell'impiego
     */
    public function getNomeImpiego()
    {
        $tipi = static::getTipiImpiego();
        return $tipi[$this->impiego] ?? $this->impiego;
    }

    /**
     * Verifica se è un codice di disponibilità
     */
    public function isDisponibile()
    {
        return $this->impiego === 'DISPONIBILE';
    }

    /**
     * Verifica se è un codice di indisponibilità
     */
    public function isIndisponibile()
    {
        return in_array($this->impiego, ['INDISPONIBILE', 'NON_DISPONIBILE']);
    }

    /**
     * Verifica se è un codice di servizio
     */
    public function isServizio()
    {
        return $this->impiego === 'PRESENTE_SERVIZIO';
    }

    /**
     * Ottiene la classe CSS per l'impiego
     */
    public function getImpiegoCssClass()
    {
        return match($this->impiego) {
            'DISPONIBILE' => 'badge-success',
            'INDISPONIBILE' => 'badge-warning',
            'NON_DISPONIBILE' => 'badge-danger',
            'PRESENTE_SERVIZIO' => 'badge-primary',
            'DISPONIBILE_ESIGENZA' => 'badge-info',
            default => 'badge-secondary'
        };
    }

    /**
     * Ottiene l'icona per l'impiego
     */
    public function getImpiegoIcon()
    {
        return match($this->impiego) {
            'DISPONIBILE' => 'fas fa-check-circle',
            'INDISPONIBILE' => 'fas fa-clock',
            'NON_DISPONIBILE' => 'fas fa-times-circle',
            'PRESENTE_SERVIZIO' => 'fas fa-user-tie',
            'DISPONIBILE_ESIGENZA' => 'fas fa-exclamation-circle',
            default => 'fas fa-question-circle'
        };
    }

    /**
     * Ottiene la descrizione completa del codice
     */
    public function getDescrizioneCompleta()
    {
        $parti = array_filter([
            $this->macro_attivita,
            $this->tipo_attivita,
            $this->attivita_specifica
        ]);
        
        return implode(' - ', $parti);
    }

    /**
     * Rappresentazione testuale
     */
    public function __toString()
    {
        return "{$this->codice}: {$this->attivita_specifica}";
    }
}
