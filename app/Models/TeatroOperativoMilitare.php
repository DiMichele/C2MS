<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Modello per la relazione Teatro Operativo - Militare (Pivot)
 * 
 * Rappresenta l'assegnazione di un militare a un Teatro Operativo
 * con stato bozza/confermato, ruolo e note.
 * 
 * @property int $id
 * @property int $teatro_operativo_id
 * @property int $militare_id
 * @property string $stato
 * @property string|null $ruolo
 * @property string|null $note
 * @property \Carbon\Carbon|null $data_assegnazione
 * @property \Carbon\Carbon|null $data_fine_assegnazione
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \App\Models\TeatroOperativo $teatroOperativo
 * @property-read \App\Models\Militare $militare
 */
class TeatroOperativoMilitare extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     */
    protected $table = 'teatro_operativo_militare';

    /**
     * Attributi assegnabili in massa
     */
    protected $fillable = [
        'teatro_operativo_id',
        'militare_id',
        'stato',
        'ruolo',
        'note',
        'data_assegnazione',
        'data_fine_assegnazione'
    ];

    /**
     * Attributi che devono essere convertiti in tipi nativi
     */
    protected $casts = [
        'data_assegnazione' => 'date',
        'data_fine_assegnazione' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Teatro Operativo di assegnazione
     */
    public function teatroOperativo()
    {
        return $this->belongsTo(TeatroOperativo::class);
    }

    /**
     * Militare assegnato
     */
    public function militare()
    {
        return $this->belongsTo(Militare::class);
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per assegnazioni confermate
     */
    public function scopeConfermati($query)
    {
        return $query->where('stato', 'confermato');
    }

    /**
     * Scope per assegnazioni in bozza
     */
    public function scopeBozza($query)
    {
        return $query->where('stato', 'bozza');
    }

    /**
     * Scope per assegnazioni attive (senza data fine o data fine futura)
     */
    public function scopeAttive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('data_fine_assegnazione')
              ->orWhere('data_fine_assegnazione', '>=', Carbon::today());
        });
    }

    /**
     * Scope per militare specifico
     */
    public function scopePerMilitare($query, $militareId)
    {
        return $query->where('militare_id', $militareId);
    }

    /**
     * Scope per teatro specifico
     */
    public function scopePerTeatro($query, $teatroId)
    {
        return $query->where('teatro_operativo_id', $teatroId);
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Verifica se l'assegnazione è confermata
     */
    public function isConfermato()
    {
        return $this->stato === 'confermato';
    }

    /**
     * Verifica se l'assegnazione è in bozza
     */
    public function isBozza()
    {
        return $this->stato === 'bozza';
    }

    /**
     * Conferma l'assegnazione
     */
    public function conferma()
    {
        $this->stato = 'confermato';
        return $this->save();
    }

    /**
     * Riporta in bozza l'assegnazione
     */
    public function riportaInBozza()
    {
        $this->stato = 'bozza';
        return $this->save();
    }

    /**
     * Ottiene la classe CSS per lo stato
     */
    public function getStatoCssClass()
    {
        return match($this->stato) {
            'confermato' => 'stato-confermato',
            'bozza' => 'stato-bozza',
            default => 'stato-indefinito'
        };
    }

    /**
     * Ottiene la label per lo stato
     */
    public function getStatoLabel()
    {
        return match($this->stato) {
            'confermato' => 'Confermato',
            'bozza' => 'In Bozza',
            default => 'N/D'
        };
    }
}
