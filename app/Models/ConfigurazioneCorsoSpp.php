<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model per la configurazione dei corsi SPP
 * 
 * Gestisce i corsi di formazione e accordo stato regione
 * con le relative durate di validità
 */
class ConfigurazioneCorsoSpp extends Model
{
    use HasFactory;

    /**
     * Nome della tabella
     */
    protected $table = 'configurazione_corsi_spp';

    /**
     * Attributi assegnabili in massa
     */
    protected $fillable = [
        'codice_corso',
        'nome_corso',
        'durata_anni',
        'tipo',
        'attivo',
        'ordine'
    ];

    /**
     * Attributi cast
     */
    protected $casts = [
        'durata_anni' => 'integer',
        'attivo' => 'boolean',
        'ordine' => 'integer',
    ];

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per corsi attivi
     */
    public function scopeAttivi($query)
    {
        return $query->where('attivo', true);
    }

    /**
     * Scope per tipo specifico
     */
    public function scopePerTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope ordinati per ordine
     */
    public function scopeOrdinati($query)
    {
        return $query->orderBy('ordine')->orderBy('nome_corso');
    }

    /**
     * Scope per codice specifico
     */
    public function scopePerCodice($query, $codice)
    {
        return $query->where('codice_corso', $codice);
    }

    // ==========================================
    // METODI STATICI
    // ==========================================

    /**
     * Ottiene tutti i corsi di formazione attivi
     */
    public static function corsiFormazione()
    {
        return static::attivi()
            ->perTipo('formazione')
            ->ordinati()
            ->get();
    }

    /**
     * Ottiene tutti i corsi accordo stato regione attivi
     */
    public static function corsiAccordoStatoRegione()
    {
        return static::attivi()
            ->perTipo('accordo_stato_regione')
            ->ordinati()
            ->get();
    }

    /**
     * Trova un corso per codice
     */
    public static function findByCodice($codice)
    {
        return static::where('codice_corso', $codice)->first();
    }

    /**
     * Ottiene la durata di un corso per codice
     */
    public static function getDurataPerCodice($codice)
    {
        $corso = static::findByCodice($codice);
        return $corso ? $corso->durata_anni : null;
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Verifica se il corso è attivo
     */
    public function isAttivo()
    {
        return $this->attivo;
    }

    /**
     * Attiva il corso
     */
    public function attiva()
    {
        $this->attivo = true;
        return $this->save();
    }

    /**
     * Disattiva il corso
     */
    public function disattiva()
    {
        $this->attivo = false;
        return $this->save();
    }

    /**
     * Ottiene il nome del tipo in italiano
     */
    public function getTipoNome()
    {
        return match($this->tipo) {
            'formazione' => 'Corsi di Formazione',
            'accordo_stato_regione' => 'Corsi Accordo Stato Regione',
            default => $this->tipo
        };
    }

    /**
     * Rappresentazione testuale
     */
    public function __toString()
    {
        return "{$this->nome_corso} ({$this->durata_anni} " . ($this->durata_anni === 1 ? 'anno' : 'anni') . ")";
    }
}
