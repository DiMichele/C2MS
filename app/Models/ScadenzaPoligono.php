<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompagnia;
use Carbon\Carbon;

/**
 * Modello per le scadenze dei poligoni
 * 
 * @property int $id
 * @property int $militare_id
 * @property int $tipo_poligono_id
 * @property \Illuminate\Support\Carbon|null $data_conseguimento
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \App\Models\Militare $militare
 * @property-read \App\Models\TipoPoligono $tipoPoligono
 */
class ScadenzaPoligono extends Model
{
    use HasFactory, BelongsToCompagnia;

    protected $table = 'scadenze_poligoni';

    protected $fillable = [
        'militare_id',
        'tipo_poligono_id',
        'data_conseguimento',
    ];

    protected $casts = [
        'data_conseguimento' => 'date',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Militare a cui appartiene la scadenza
     */
    public function militare()
    {
        return $this->belongsTo(Militare::class, 'militare_id');
    }

    /**
     * Tipo di poligono
     */
    public function tipoPoligono()
    {
        return $this->belongsTo(TipoPoligono::class, 'tipo_poligono_id');
    }

    // ==========================================
    // ACCESSOR
    // ==========================================

    /**
     * Calcola la data di scadenza in base alla durata del tipo poligono
     */
    public function getDataScadenzaAttribute(): ?Carbon
    {
        if (!$this->data_conseguimento || !$this->tipoPoligono) {
            return null;
        }

        $durataMesi = $this->tipoPoligono->durata_mesi ?? 6;
        
        if ($durataMesi == 0) {
            return null; // Nessuna scadenza
        }

        return $this->data_conseguimento->copy()->addMonths($durataMesi);
    }

    /**
     * Calcola lo stato della scadenza
     */
    public function getStatoAttribute(): string
    {
        if (!$this->data_conseguimento) {
            return 'mancante';
        }

        $dataScadenza = $this->data_scadenza;
        
        if (!$dataScadenza) {
            return 'valido'; // Nessuna scadenza = sempre valido
        }

        $oggi = Carbon::now();
        $giorniRimanenti = $oggi->diffInDays($dataScadenza, false);

        if ($giorniRimanenti < 0) {
            return 'scaduto';
        } elseif ($giorniRimanenti <= 30) {
            return 'in_scadenza';
        }

        return 'valido';
    }

    /**
     * Calcola i giorni rimanenti alla scadenza
     */
    public function getGiorniRimanentiAttribute(): ?int
    {
        $dataScadenza = $this->data_scadenza;
        
        if (!$dataScadenza) {
            return null;
        }

        return (int) Carbon::now()->diffInDays($dataScadenza, false);
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scadenze di un militare specifico
     */
    public function scopePerMilitare($query, $militareId)
    {
        return $query->where('militare_id', $militareId);
    }

    /**
     * Scadenze di un tipo specifico
     */
    public function scopePerTipo($query, $tipoPoligonoId)
    {
        return $query->where('tipo_poligono_id', $tipoPoligonoId);
    }

    /**
     * Scadenze scadute
     */
    public function scopeScadute($query)
    {
        return $query->whereHas('tipoPoligono', function ($q) {
            $q->where('durata_mesi', '>', 0);
        })->whereRaw('DATE_ADD(data_conseguimento, INTERVAL (SELECT durata_mesi FROM tipi_poligono WHERE id = scadenze_poligoni.tipo_poligono_id) MONTH) < NOW()');
    }

    /**
     * Scadenze in scadenza (entro 30 giorni)
     */
    public function scopeInScadenza($query)
    {
        return $query->whereHas('tipoPoligono', function ($q) {
            $q->where('durata_mesi', '>', 0);
        })->whereRaw('DATE_ADD(data_conseguimento, INTERVAL (SELECT durata_mesi FROM tipi_poligono WHERE id = scadenze_poligoni.tipo_poligono_id) MONTH) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)');
    }

    // ==========================================
    // METODI PER BELONGSTOCOMPAGNIA TRAIT
    // ==========================================

    /**
     * Nome della relazione con la compagnia
     */
    public function compagniaRelation()
    {
        return 'militare.compagnia';
    }
}
