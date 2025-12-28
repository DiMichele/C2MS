<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Modello per gli approntamenti/missioni militari
 * 
 * Rappresenta le missioni, operazioni e approntamenti a cui possono
 * essere assegnati i militari (es. KOSOVO, CJ CBRN, CENTURIA, etc.)
 * 
 * @property int $id
 * @property string $nome
 * @property string|null $codice
 * @property string|null $descrizione
 * @property \Carbon\Carbon|null $data_inizio
 * @property \Carbon\Carbon|null $data_fine
 * @property string $stato
 * @property string $colore_badge
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Militare[] $militari
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MilitareApprontamento[] $militareApprontamenti
 */
class Approntamento extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     */
    protected $table = 'approntamenti';

    /**
     * Attributi assegnabili in massa
     */
    protected $fillable = [
        'nome',
        'codice',
        'descrizione',
        'data_inizio',
        'data_fine',
        'stato',
        'colore_badge'
    ];

    /**
     * Attributi che devono essere convertiti in tipi nativi
     */
    protected $casts = [
        'data_inizio' => 'date',
        'data_fine' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Militari assegnati come approntamento principale
     */
    public function militariPrincipali()
    {
        return $this->hasMany(Militare::class, 'approntamento_principale_id');
    }


    /**
     * Assegnazioni dettagliate militare-approntamento
     */
    public function militareApprontamenti()
    {
        return $this->hasMany(MilitareApprontamento::class);
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per approntamenti attivi
     */
    public function scopeAttivi($query)
    {
        return $query->where('stato', 'attivo');
    }

    /**
     * Scope per approntamenti in corso
     */
    public function scopeInCorso($query)
    {
        $oggi = Carbon::today();
        return $query->where('stato', 'attivo')
                    ->where(function($q) use ($oggi) {
                        $q->whereNull('data_inizio')
                          ->orWhere('data_inizio', '<=', $oggi);
                    })
                    ->where(function($q) use ($oggi) {
                        $q->whereNull('data_fine')
                          ->orWhere('data_fine', '>=', $oggi);
                    });
    }

    /**
     * Scope per ordinare per nome
     */
    public function scopePerNome($query)
    {
        return $query->orderBy('nome');
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Verifica se l'approntamento è attualmente attivo
     */
    public function isAttivo()
    {
        return $this->stato === 'attivo';
    }

    /**
     * Verifica se l'approntamento è in corso
     */
    public function isInCorso()
    {
        if ($this->stato !== 'attivo') {
            return false;
        }

        $oggi = Carbon::today();
        
        $inizioOk = !$this->data_inizio || $this->data_inizio->lte($oggi);
        $fineOk = !$this->data_fine || $this->data_fine->gte($oggi);
        
        return $inizioOk && $fineOk;
    }

    /**
     * Ottiene il numero di militari assegnati
     */
    public function getNumeroMilitari()
    {
        return $this->militari()->count();
    }

    /**
     * Ottiene il numero di militari con questo come approntamento principale
     */
    public function getNumeroMilitariPrincipali()
    {
        return $this->militariPrincipali()->count();
    }

    /**
     * Ottiene la durata dell'approntamento in giorni
     */
    public function getDurataGiorni()
    {
        if (!$this->data_inizio || !$this->data_fine) {
            return null;
        }

        return $this->data_inizio->diffInDays($this->data_fine) + 1;
    }

    /**
     * Ottiene il periodo formattato
     */
    public function getPeriodoFormattato()
    {
        if (!$this->data_inizio && !$this->data_fine) {
            return 'Permanente';
        }

        $inizio = $this->data_inizio ? $this->data_inizio->format('d/m/Y') : 'Inizio indefinito';
        $fine = $this->data_fine ? $this->data_fine->format('d/m/Y') : 'Fine indefinita';

        return "{$inizio} - {$fine}";
    }

    /**
     * Ottiene la classe CSS per lo stato
     */
    public function getStatoCssClass()
    {
        return match($this->stato) {
            'attivo' => 'badge-success',
            'completato' => 'badge-secondary',
            'sospeso' => 'badge-warning',
            'pianificato' => 'badge-info',
            default => 'badge-light'
        };
    }

    /**
     * Ottiene l'icona per lo stato
     */
    public function getStatoIcon()
    {
        return match($this->stato) {
            'attivo' => 'fas fa-play',
            'completato' => 'fas fa-check',
            'sospeso' => 'fas fa-pause',
            'pianificato' => 'fas fa-calendar',
            default => 'fas fa-question'
        };
    }

    /**
     * Assegna un militare all'approntamento
     */
    public function assegnaMilitare($militareId, $ruolo = null, $principale = false, $dataAssegnazione = null)
    {
        $dataAssegnazione = $dataAssegnazione ?: Carbon::today();

        return $this->militari()->attach($militareId, [
            'ruolo' => $ruolo,
            'data_assegnazione' => $dataAssegnazione,
            'principale' => $principale,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Rimuove un militare dall'approntamento
     */
    public function rimuoviMilitare($militareId, $dataFine = null)
    {
        $dataFine = $dataFine ?: Carbon::today();

        // Aggiorna la data di fine invece di eliminare il record
        return $this->militari()->updateExistingPivot($militareId, [
            'data_fine_assegnazione' => $dataFine,
            'updated_at' => now()
        ]);
    }

    /**
     * Rappresentazione testuale
     */
    public function __toString()
    {
        return $this->nome;
    }
}
