<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Modello per le scadenze idoneità dei militari
 * 
 * @property int $id
 * @property int $militare_id
 * @property int $tipo_idoneita_id
 * @property \Illuminate\Support\Carbon|null $data_conseguimento
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ScadenzaIdoneita extends Model
{
    use HasFactory;

    protected $table = 'scadenze_idoneita';

    protected $fillable = [
        'militare_id',
        'tipo_idoneita_id',
        'data_conseguimento'
    ];

    protected $casts = [
        'data_conseguimento' => 'date'
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Militare associato
     */
    public function militare()
    {
        return $this->belongsTo(Militare::class);
    }

    /**
     * Tipo di idoneità
     */
    public function tipoIdoneita()
    {
        return $this->belongsTo(TipoIdoneita::class, 'tipo_idoneita_id');
    }

    // ==========================================
    // ACCESSOR E MUTATOR
    // ==========================================

    /**
     * Calcola la data di scadenza
     */
    public function getDataScadenzaAttribute()
    {
        if (!$this->data_conseguimento || !$this->tipoIdoneita) {
            return null;
        }

        $durataMesi = $this->tipoIdoneita->durata_mesi;
        if ($durataMesi == 0) {
            return null; // Nessuna scadenza
        }

        return $this->data_conseguimento->copy()->addMonths($durataMesi);
    }

    /**
     * Verifica se è scaduto
     */
    public function getIsScadutoAttribute()
    {
        $dataScadenza = $this->data_scadenza;
        if (!$dataScadenza) {
            return false;
        }

        return $dataScadenza->isPast();
    }

    /**
     * Verifica se è in scadenza (entro 30 giorni)
     */
    public function getIsInScadenzaAttribute()
    {
        $dataScadenza = $this->data_scadenza;
        if (!$dataScadenza) {
            return false;
        }

        return $dataScadenza->isFuture() && $dataScadenza->diffInDays(now()) <= 30;
    }

    /**
     * Ottiene lo stato della scadenza
     */
    public function getStatoAttribute()
    {
        if (!$this->data_conseguimento) {
            return 'mancante';
        }

        if ($this->is_scaduto) {
            return 'scaduto';
        }

        if ($this->is_in_scadenza) {
            return 'in_scadenza';
        }

        return 'valido';
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scadenze scadute
     */
    public function scopeScaduti($query)
    {
        return $query->whereHas('tipoIdoneita', function ($q) {
            $q->where('durata_mesi', '>', 0);
        })->whereRaw("DATE_ADD(data_conseguimento, INTERVAL (SELECT durata_mesi FROM tipi_idoneita WHERE tipi_idoneita.id = scadenze_idoneita.tipo_idoneita_id) MONTH) < CURDATE()");
    }

    /**
     * Scadenze in scadenza (entro 30 giorni)
     */
    public function scopeInScadenza($query)
    {
        return $query->whereHas('tipoIdoneita', function ($q) {
            $q->where('durata_mesi', '>', 0);
        })->whereRaw("DATE_ADD(data_conseguimento, INTERVAL (SELECT durata_mesi FROM tipi_idoneita WHERE tipi_idoneita.id = scadenze_idoneita.tipo_idoneita_id) MONTH) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    }
}

