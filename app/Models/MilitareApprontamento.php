<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Modello per la relazione militare-approntamento
 * 
 * Rappresenta l'assegnazione di un militare a un approntamento
 * con dettagli specifici come ruolo, date e note.
 * 
 * @property int $id
 * @property int $militare_id
 * @property int $approntamento_id
 * @property string|null $ruolo
 * @property \Carbon\Carbon $data_assegnazione
 * @property \Carbon\Carbon|null $data_fine_assegnazione
 * @property bool $principale
 * @property string|null $note
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \App\Models\Militare $militare
 * @property-read \App\Models\Approntamento $approntamento
 */
class MilitareApprontamento extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     */
    protected $table = 'militare_approntamenti';

    /**
     * Attributi assegnabili in massa
     */
    protected $fillable = [
        'militare_id',
        'approntamento_id',
        'ruolo',
        'data_assegnazione',
        'data_fine_assegnazione',
        'principale',
        'note'
    ];

    /**
     * Attributi che devono essere convertiti in tipi nativi
     */
    protected $casts = [
        'data_assegnazione' => 'date',
        'data_fine_assegnazione' => 'date',
        'principale' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Militare assegnato
     */
    public function militare()
    {
        return $this->belongsTo(Militare::class);
    }

    /**
     * Approntamento di assegnazione
     */
    public function approntamento()
    {
        return $this->belongsTo(Approntamento::class);
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per assegnazioni attive
     */
    public function scopeAttive($query)
    {
        return $query->whereNull('data_fine_assegnazione')
                    ->orWhere('data_fine_assegnazione', '>', Carbon::today());
    }

    /**
     * Scope per assegnazioni concluse
     */
    public function scopeConcluse($query)
    {
        return $query->whereNotNull('data_fine_assegnazione')
                    ->where('data_fine_assegnazione', '<=', Carbon::today());
    }

    /**
     * Scope per assegnazioni principali
     */
    public function scopePrincipali($query)
    {
        return $query->where('principale', true);
    }

    /**
     * Scope per militare specifico
     */
    public function scopePerMilitare($query, $militareId)
    {
        return $query->where('militare_id', $militareId);
    }

    /**
     * Scope per approntamento specifico
     */
    public function scopePerApprontamento($query, $approntamentoId)
    {
        return $query->where('approntamento_id', $approntamentoId);
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Verifica se l'assegnazione è attualmente attiva
     */
    public function isAttiva()
    {
        return !$this->data_fine_assegnazione || 
               $this->data_fine_assegnazione->gt(Carbon::today());
    }

    /**
     * Verifica se l'assegnazione è conclusa
     */
    public function isConclusa()
    {
        return $this->data_fine_assegnazione && 
               $this->data_fine_assegnazione->lte(Carbon::today());
    }

    /**
     * Verifica se è l'assegnazione principale del militare
     */
    public function isPrincipale()
    {
        return $this->principale;
    }

    /**
     * Ottiene la durata dell'assegnazione in giorni
     */
    public function getDurataGiorni()
    {
        $fine = $this->data_fine_assegnazione ?: Carbon::today();
        return $this->data_assegnazione->diffInDays($fine) + 1;
    }

    /**
     * Ottiene il periodo di assegnazione formattato
     */
    public function getPeriodoFormattato()
    {
        $inizio = $this->data_assegnazione->format('d/m/Y');
        $fine = $this->data_fine_assegnazione ? 
                $this->data_fine_assegnazione->format('d/m/Y') : 'In corso';

        return "{$inizio} - {$fine}";
    }

    /**
     * Ottiene lo stato dell'assegnazione
     */
    public function getStato()
    {
        if ($this->isConclusa()) {
            return 'conclusa';
        }

        return 'attiva';
    }

    /**
     * Ottiene la classe CSS per lo stato
     */
    public function getStatoCssClass()
    {
        return match($this->getStato()) {
            'attiva' => 'badge-success',
            'conclusa' => 'badge-secondary',
            default => 'badge-light'
        };
    }

    /**
     * Termina l'assegnazione
     */
    public function termina($dataFine = null)
    {
        $this->data_fine_assegnazione = $dataFine ?: Carbon::today();
        return $this->save();
    }

    /**
     * Riattiva l'assegnazione (rimuove data fine)
     */
    public function riattiva()
    {
        $this->data_fine_assegnazione = null;
        return $this->save();
    }

    /**
     * Imposta come assegnazione principale
     */
    public function impostaPrincipale()
    {
        // Prima rimuovi il flag principale da altre assegnazioni dello stesso militare
        static::where('militare_id', $this->militare_id)
              ->where('id', '!=', $this->id)
              ->update(['principale' => false]);

        $this->principale = true;
        return $this->save();
    }
}
