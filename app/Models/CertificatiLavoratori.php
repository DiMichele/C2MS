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
use App\Traits\CertificatoTrait;
use Carbon\Carbon;

/**
 * Modello per i certificati dei lavoratori
 * 
 * Questo modello rappresenta i certificati di formazione dei lavoratori
 * (formazione generale, specifica, preposti, dirigenti).
 * Utilizza il trait CertificatoTrait per funzionalità comuni con le idoneità.
 * Include gestione automatica delle scadenze basata sul tipo di corso.
 * 
 * @package App\Models
 * @version 1.0
 * 
 * @property int $id ID univoco del certificato
 * @property int $militare_id ID del militare
 * @property string $tipo Tipo di certificato (corsi_lavoratori_4h, corsi_lavoratori_8h, ecc.)
 * @property \Illuminate\Support\Carbon $data_ottenimento Data di ottenimento
 * @property \Illuminate\Support\Carbon $data_scadenza Data di scadenza
 * @property string|null $file_path Percorso del file allegato
 * @property string|null $note Note aggiuntive
 * @property \Illuminate\Support\Carbon|null $created_at Data creazione
 * @property \Illuminate\Support\Carbon|null $updated_at Data ultimo aggiornamento
 * 
 * @property-read \App\Models\Militare $militare Militare associato
 * @property-read string $nome_tipo Nome formattato del tipo di certificato
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|CertificatiLavoratori validi() Certificati validi
 * @method static \Illuminate\Database\Eloquent\Builder|CertificatiLavoratori scaduti() Certificati scaduti
 * @method static \Illuminate\Database\Eloquent\Builder|CertificatiLavoratori inScadenza(int $giorni = 30) Certificati in scadenza
 */
class CertificatiLavoratori extends Model
{
    use HasFactory, CertificatoTrait;

    /**
     * Nome della tabella associata al modello
     * 
     * @var string
     */
    protected $table = 'certificati_lavoratori';

    /**
     * Gli attributi che sono mass assignable
     * 
     * @var array<string>
     */
    protected $fillable = [
        'militare_id',
        'tipo',
        'data_ottenimento',
        'data_scadenza',
        'file_path',
        'note'
    ];

    /**
     * Gli attributi che dovrebbero essere cast
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'data_ottenimento' => 'date',
        'data_scadenza' => 'date'
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
    // ATTRIBUTI CALCOLATI
    // ==========================================
    
    /**
     * Restituisce il nome formattato del tipo di certificato
     *
     * @return string Nome formattato del tipo
     */
    public function getNomeTipo()
    {
        $tipi = [
            'corsi_lavoratori_4h' => 'Corso Formazione Generale (4h)',
            'corsi_lavoratori_8h' => 'Corso Formazione Specifica (8h)',
            'corsi_lavoratori_preposti' => 'Corso Preposti',
            'corsi_lavoratori_dirigenti' => 'Corso Dirigenti'
        ];
        
        return $tipi[$this->tipo] ?? $this->tipo;
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================
    
    /**
     * Scope per filtrare certificati validi
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValidi($query)
    {
        return $query->where('data_scadenza', '>=', now());
    }
    
    /**
     * Scope per filtrare certificati scaduti
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScaduti($query)
    {
        return $query->where('data_scadenza', '<', now());
    }
    
    /**
     * Scope per filtrare certificati in scadenza
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $giorni Numero di giorni per considerare "in scadenza"
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInScadenza($query, $giorni = 30)
    {
        $now = now();
        return $query->where('data_scadenza', '>=', $now)
                    ->where('data_scadenza', '<=', $now->copy()->addDays($giorni));
    }

    // ==========================================
    // METODI STATICI
    // ==========================================
    
    /**
     * Determina automaticamente la data di scadenza in base al tipo
     * 
     * @param \Carbon\Carbon|string $dataOttenimento Data di ottenimento
     * @param string $tipo Tipo di certificato
     * @return \Carbon\Carbon Data di scadenza calcolata
     */
    public static function getScadenzaAutomatica($dataOttenimento, $tipo)
    {
        $dataOttenimento = Carbon::parse($dataOttenimento);
        
        if (str_contains($tipo, 'corsi_lavoratori')) {
            return $dataOttenimento->copy()->addYears(5);
        }
        
        return $dataOttenimento->copy()->addYear();
    }

    /**
     * Ottiene tutti i tipi di certificato disponibili
     * 
     * @return array<string, string> Array associativo tipo => nome
     */
    public static function getTipiDisponibili()
    {
        return [
            'corsi_lavoratori_4h' => 'Corso Formazione Generale (4h)',
            'corsi_lavoratori_8h' => 'Corso Formazione Specifica (8h)',
            'corsi_lavoratori_preposti' => 'Corso Preposti',
            'corsi_lavoratori_dirigenti' => 'Corso Dirigenti'
        ];
    }

    /**
     * Ottiene la durata standard di validità per tipo di certificato
     * 
     * @param string $tipo Tipo di certificato
     * @return int Anni di validità
     */
    public static function getDurataValidita($tipo)
    {
        if (str_contains($tipo, 'corsi_lavoratori')) {
            return 5; // I corsi lavoratori durano 5 anni
        }
        
        return 1; // Altri certificati durano 1 anno
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Verifica se il certificato è valido
     * 
     * @return bool True se il certificato è ancora valido
     */
    public function isValido()
    {
        return $this->data_scadenza >= now();
    }

    /**
     * Verifica se il certificato è scaduto
     * 
     * @return bool True se il certificato è scaduto
     */
    public function isScaduto()
    {
        return $this->data_scadenza < now();
    }

    /**
     * Verifica se il certificato è in scadenza
     * 
     * @param int $giorni Numero di giorni per considerare "in scadenza"
     * @return bool True se il certificato è in scadenza
     */
    public function isInScadenza($giorni = 30)
    {
        $now = now();
        return $this->data_scadenza >= $now && 
               $this->data_scadenza <= $now->copy()->addDays($giorni);
    }

    /**
     * Ottiene i giorni rimanenti alla scadenza
     * 
     * @return int Giorni rimanenti (negativo se scaduto)
     */
    public function getGiorniRimanenti()
    {
        return now()->diffInDays($this->data_scadenza, false);
    }

    /**
     * Ottiene lo stato del certificato (valido, in_scadenza, scaduto)
     * 
     * @return string Stato del certificato
     */
    public function getStato()
    {
        if ($this->isScaduto()) {
            return 'scaduto';
        }
        
        if ($this->isInScadenza()) {
            return 'in_scadenza';
        }
        
        return 'valido';
    }

    /**
     * Ottiene la classe CSS per lo stato del certificato
     * 
     * @return string Classe CSS appropriata
     */
    public function getStatoCssClass()
    {
        return match($this->getStato()) {
            'valido' => 'badge-success',
            'in_scadenza' => 'badge-warning',
            'scaduto' => 'badge-danger',
            default => 'badge-secondary'
        };
    }

    /**
     * Verifica se è un corso per lavoratori (durata 5 anni)
     * 
     * @return bool True se è un corso per lavoratori
     */
    public function isCorsoLavoratori()
    {
        return str_contains($this->tipo, 'corsi_lavoratori');
    }

    /**
     * Ottiene la categoria del certificato
     * 
     * @return string Categoria del certificato
     */
    public function getCategoria()
    {
        if ($this->isCorsoLavoratori()) {
            return 'Formazione Lavoratori';
        }
        
        return 'Altro';
    }

    // ==========================================
    // METODI TRAIT OVERRIDE
    // ==========================================
    
    /**
     * Ritorna il nome dello stato appropriato per i certificati
     * 
     * Override del metodo del trait per mantenere la nomenclatura standard
     *
     * @param string $stato Stato da formattare
     * @return string Stato formattato
     */
    protected function getStatoNaming($stato)
    {
        return $stato;
    }
}
