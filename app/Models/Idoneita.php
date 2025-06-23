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
 * Modello per le idoneità militari
 * 
 * Questo modello rappresenta le idoneità dei militari (PEFO, Idoneità Mansione, SMI).
 * Utilizza il trait CertificatoTrait per funzionalità comuni con i certificati.
 * Include gestione automatica delle scadenze e stati.
 * 
 * @package App\Models
 * @version 1.0
 * 
 * @property int $id ID univoco dell'idoneità
 * @property int $militare_id ID del militare
 * @property string $tipo Tipo di idoneità (idoneita_mansione, idoneita_smi, idoneita)
 * @property \Illuminate\Support\Carbon $data_ottenimento Data di ottenimento
 * @property \Illuminate\Support\Carbon $data_scadenza Data di scadenza
 * @property string|null $file_path Percorso del file allegato
 * @property string|null $note Note aggiuntive
 * @property \Illuminate\Support\Carbon|null $created_at Data creazione
 * @property \Illuminate\Support\Carbon|null $updated_at Data ultimo aggiornamento
 * 
 * @property-read \App\Models\Militare $militare Militare associato
 * @property-read string $nome_tipo Nome formattato del tipo di idoneità
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|Idoneita valide() Idoneità valide
 * @method static \Illuminate\Database\Eloquent\Builder|Idoneita scadute() Idoneità scadute
 * @method static \Illuminate\Database\Eloquent\Builder|Idoneita inScadenza(int $giorni = 30) Idoneità in scadenza
 */
class Idoneita extends Model
{
    use HasFactory, CertificatoTrait;

    /**
     * Nome della tabella associata al modello
     * 
     * @var string
     */
    protected $table = 'idoneita';

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
     * Restituisce il nome formattato del tipo di idoneità
     *
     * @return string Nome formattato del tipo
     */
    public function getNomeTipo()
    {
        $tipi = [
            'idoneita_mansione' => 'Idoneità Mansione',
            'idoneita_smi' => 'Idoneità SMI',
            'idoneita' => 'PEFO'
        ];
        
        return $tipi[$this->tipo] ?? $this->tipo;
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================
    
    /**
     * Scope per filtrare idoneità valide
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValide($query)
    {
        return $query->where('data_scadenza', '>=', now());
    }
    
    /**
     * Scope per filtrare idoneità scadute
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScadute($query)
    {
        return $query->where('data_scadenza', '<', now());
    }
    
    /**
     * Scope per filtrare idoneità in scadenza
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
     * Determina automaticamente la data di scadenza
     *
     * @param \Carbon\Carbon|string $dataOttenimento Data di ottenimento
     * @return \Carbon\Carbon Data di scadenza calcolata
     */
    public static function getScadenzaAutomatica($dataOttenimento)
    {
        return Carbon::parse($dataOttenimento)->addYear();
    }

    /**
     * Ottiene tutti i tipi di idoneità disponibili
     * 
     * @return array<string, string> Array associativo tipo => nome
     */
    public static function getTipiDisponibili()
    {
        return [
            'idoneita' => 'PEFO',
            'idoneita_mansione' => 'Idoneità Mansione',
            'idoneita_smi' => 'Idoneità SMI'
        ];
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Verifica se l'idoneità è valida
     * 
     * @return bool True se l'idoneità è ancora valida
     */
    public function isValida()
    {
        return $this->data_scadenza >= now();
    }

    /**
     * Verifica se l'idoneità è scaduta
     * 
     * @return bool True se l'idoneità è scaduta
     */
    public function isScaduta()
    {
        return $this->data_scadenza < now();
    }

    /**
     * Verifica se l'idoneità è in scadenza
     * 
     * @param int $giorni Numero di giorni per considerare "in scadenza"
     * @return bool True se l'idoneità è in scadenza
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
     * @return int Giorni rimanenti (negativo se scaduta)
     */
    public function getGiorniRimanenti()
    {
        return now()->diffInDays($this->data_scadenza, false);
    }

    /**
     * Ottiene lo stato dell'idoneità (valida, in_scadenza, scaduta)
     * 
     * @return string Stato dell'idoneità
     */
    public function getStato()
    {
        if ($this->isScaduta()) {
            return 'scaduta';
        }
        
        if ($this->isInScadenza()) {
            return 'in_scadenza';
        }
        
        return 'valida';
    }

    /**
     * Ottiene la classe CSS per lo stato dell'idoneità
     * 
     * @return string Classe CSS appropriata
     */
    public function getStatoCssClass()
    {
        return match($this->getStato()) {
            'valida' => 'badge-success',
            'in_scadenza' => 'badge-warning',
            'scaduta' => 'badge-danger',
            default => 'badge-secondary'
        };
    }

    // ==========================================
    // METODI TRAIT OVERRIDE
    // ==========================================
    
    /**
     * Ritorna il nome dello stato appropriato per le idoneità
     * 
     * Override del metodo del trait per adattare la nomenclatura
     *
     * @param string $stato Stato da formattare
     * @return string Stato formattato
     */
    protected function getStatoNaming($stato)
    {
        $mappatura = [
            'valido' => 'valida',
            'in_scadenza' => 'in_scadenza',
            'scaduto' => 'scaduta'
        ];
        
        return $mappatura[$stato] ?? $stato;
    }
}
