<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Modello per le patenti dei militari
 * 
 * Rappresenta le patenti di guida possedute dai militari
 * con categorie, scadenze e abilitazioni specifiche.
 * 
 * @property int $id
 * @property int $militare_id
 * @property string $categoria
 * @property string|null $tipo
 * @property \Carbon\Carbon $data_ottenimento
 * @property \Carbon\Carbon $data_scadenza
 * @property string|null $numero_patente
 * @property string|null $note
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \App\Models\Militare $militare
 */
class PatenteMilitare extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     */
    protected $table = 'patenti_militari';

    /**
     * Attributi assegnabili in massa
     */
    protected $fillable = [
        'militare_id',
        'categoria',
        'tipo',
        'data_ottenimento',
        'data_scadenza',
        'numero_patente',
        'note'
    ];

    /**
     * Attributi che devono essere convertiti in tipi nativi
     */
    protected $casts = [
        'data_ottenimento' => 'date',
        'data_scadenza' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Militare proprietario della patente
     */
    public function militare()
    {
        return $this->belongsTo(Militare::class);
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per patenti valide
     */
    public function scopeValide($query)
    {
        return $query->where('data_scadenza', '>', Carbon::today());
    }

    /**
     * Scope per patenti scadute
     */
    public function scopeScadute($query)
    {
        return $query->where('data_scadenza', '<=', Carbon::today());
    }

    /**
     * Scope per patenti in scadenza
     */
    public function scopeInScadenza($query, $giorni = 30)
    {
        $oggi = Carbon::today();
        return $query->where('data_scadenza', '>', $oggi)
                    ->where('data_scadenza', '<=', $oggi->copy()->addDays($giorni));
    }

    /**
     * Scope per categoria specifica
     */
    public function scopePerCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Scope per militare specifico
     */
    public function scopePerMilitare($query, $militareId)
    {
        return $query->where('militare_id', $militareId);
    }

    // ==========================================
    // METODI STATICI
    // ==========================================

    /**
     * Ottiene tutte le categorie di patenti disponibili
     */
    public static function getCategorieDisponibili()
    {
        return [
            'A' => 'Motocicli',
            'B' => 'Autovetture',
            'C' => 'Autocarri',
            'D' => 'Autobus',
            'E' => 'Rimorchi',
            '1' => 'Ciclomotori (Patentino)',
            '2' => 'Trattori agricoli',
            '3' => 'Macchine operatrici',
            '4' => 'Mezzi speciali',
            '5' => 'Macchine movimento terra',
            '6' => 'Gru mobili'
        ];
    }

    /**
     * Ottiene i tipi di abilitazione disponibili
     */
    public static function getTipiAbilitazione()
    {
        return [
            'ABIL' => 'Abilitazione generica',
            'PROF' => 'Abilitazione professionale',
            'SPEC' => 'Abilitazione speciale',
            'MIL' => 'Abilitazione militare'
        ];
    }

    /**
     * Parsing delle patenti dal formato Excel (es. "2-3-6A ABIL")
     */
    public static function parseFromExcel($stringaPatenti)
    {
        if (empty($stringaPatenti) || $stringaPatenti === '//') {
            return [];
        }

        $patenti = [];
        $parti = explode(' ', $stringaPatenti);
        
        if (count($parti) >= 2) {
            $categorie = explode('-', $parti[0]);
            $tipo = $parti[1] ?? 'ABIL';
            
            foreach ($categorie as $categoria) {
                if (!empty($categoria)) {
                    $patenti[] = [
                        'categoria' => strtoupper(trim($categoria)),
                        'tipo' => strtoupper(trim($tipo))
                    ];
                }
            }
        }

        return $patenti;
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Verifica se la patente è valida
     */
    public function isValida()
    {
        return $this->data_scadenza->gt(Carbon::today());
    }

    /**
     * Verifica se la patente è scaduta
     */
    public function isScaduta()
    {
        return $this->data_scadenza->lte(Carbon::today());
    }

    /**
     * Verifica se la patente è in scadenza
     */
    public function isInScadenza($giorni = 30)
    {
        $oggi = Carbon::today();
        return $this->data_scadenza->gt($oggi) && 
               $this->data_scadenza->lte($oggi->copy()->addDays($giorni));
    }

    /**
     * Ottiene i giorni rimanenti alla scadenza
     */
    public function getGiorniRimanenti()
    {
        return Carbon::today()->diffInDays($this->data_scadenza, false);
    }

    /**
     * Ottiene lo stato della patente
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
     * Ottiene la classe CSS per lo stato
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

    /**
     * Ottiene il nome completo della categoria
     */
    public function getNomeCategoria()
    {
        $categorie = static::getCategorieDisponibili();
        return $categorie[$this->categoria] ?? $this->categoria;
    }

    /**
     * Ottiene il nome completo del tipo
     */
    public function getNomeTipo()
    {
        if (!$this->tipo) {
            return '';
        }

        $tipi = static::getTipiAbilitazione();
        return $tipi[$this->tipo] ?? $this->tipo;
    }

    /**
     * Ottiene la descrizione completa della patente
     */
    public function getDescrizioneCompleta()
    {
        $descrizione = "Patente {$this->categoria}";
        
        if ($this->tipo) {
            $descrizione .= " ({$this->getNomeTipo()})";
        }

        return $descrizione;
    }

    /**
     * Verifica se è una patente professionale
     */
    public function isProfessionale()
    {
        return in_array($this->categoria, ['C', 'D', 'E']) || 
               $this->tipo === 'PROF';
    }

    /**
     * Verifica se è un'abilitazione speciale
     */
    public function isAbilitazioneSpeciale()
    {
        return in_array($this->categoria, ['2', '3', '4', '5', '6']) || 
               $this->tipo === 'SPEC';
    }

    /**
     * Rappresentazione testuale
     */
    public function __toString()
    {
        return $this->getDescrizioneCompleta();
    }
}
