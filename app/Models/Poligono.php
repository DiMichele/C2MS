<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Modello per i poligoni effettuati
 * 
 * @property int $id
 * @property int $militare_id
 * @property int $tipo_poligono_id
 * @property \Carbon\Carbon $data_poligono
 * @property int|null $punteggio
 * @property string $esito
 * @property string|null $note
 * @property string|null $istruttore
 * @property string|null $arma_utilizzata
 * @property int|null $colpi_sparati
 * @property int|null $colpi_a_segno
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \App\Models\Militare $militare
 * @property-read \App\Models\TipoPoligono $tipoPoligono
 */
class Poligono extends Model
{
    use HasFactory;

    protected $table = 'poligoni';

    protected $fillable = [
        'militare_id',
        'tipo_poligono_id',
        'data_poligono',
        'punteggio',
        'esito',
        'note',
        'istruttore',
        'arma_utilizzata',
        'colpi_sparati',
        'colpi_a_segno'
    ];

    protected $casts = [
        'data_poligono' => 'date',
        'punteggio' => 'integer',
        'colpi_sparati' => 'integer',
        'colpi_a_segno' => 'integer'
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Militare che ha effettuato il poligono
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
    // QUERY SCOPES
    // ==========================================

    /**
     * Poligoni superati
     */
    public function scopeSuperati($query)
    {
        return $query->where('esito', 'SUPERATO');
    }

    /**
     * Poligoni non superati
     */
    public function scopeNonSuperati($query)
    {
        return $query->where('esito', 'NON_SUPERATO');
    }

    /**
     * Poligoni da valutare
     */
    public function scopeDaValutare($query)
    {
        return $query->where('esito', 'DA_VALUTARE');
    }

    /**
     * Poligoni recenti (ultimi 30 giorni)
     */
    public function scopeRecenti($query)
    {
        return $query->where('data_poligono', '>=', Carbon::now()->subDays(30));
    }

    /**
     * Poligoni per anno
     */
    public function scopePerAnno($query, int $anno)
    {
        return $query->whereYear('data_poligono', $anno);
    }

    /**
     * Ordinati per data (più recenti prima)
     */
    public function scopePerData($query)
    {
        return $query->orderByDesc('data_poligono');
    }

    // ==========================================
    // METODI HELPER
    // ==========================================

    /**
     * Verifica se il poligono è stato superato
     */
    public function isSuperato(): bool
    {
        return $this->esito === 'SUPERATO';
    }

    /**
     * Calcola la precisione (colpi a segno / colpi sparati)
     */
    public function calcolaPrecisione(): float
    {
        if (!$this->colpi_sparati || $this->colpi_sparati == 0) {
            return 0;
        }
        
        return round(($this->colpi_a_segno / $this->colpi_sparati) * 100, 2);
    }

    /**
     * Ottiene il colore del badge per l'esito
     */
    public function getColoreBadgeEsito(): string
    {
        return match($this->esito) {
            'SUPERATO' => 'success',
            'NON_SUPERATO' => 'danger',
            'DA_VALUTARE' => 'warning',
            default => 'secondary'
        };
    }

    /**
     * Ottiene il testo dell'esito
     */
    public function getTestoEsito(): string
    {
        return match($this->esito) {
            'SUPERATO' => 'Superato',
            'NON_SUPERATO' => 'Non Superato',
            'DA_VALUTARE' => 'Da Valutare',
            default => 'Sconosciuto'
        };
    }

    /**
     * Ottiene il numero di giorni trascorsi dalla data del poligono
     */
    public function getGiorniTrascorsi(): int
    {
        return $this->data_poligono->diffInDays(Carbon::now());
    }

    /**
     * Verifica se il poligono è scaduto (più di 1 anno)
     */
    public function isScaduto(): bool
    {
        return $this->data_poligono->addYear()->isPast();
    }

    // ==========================================
    // EVENTI DEL MODELLO
    // ==========================================

    protected static function boot()
    {
        parent::boot();

        // Dopo aver salvato un poligono, aggiorna l'ultimo poligono del militare
        static::saved(function ($poligono) {
            $militare = $poligono->militare;
            
            // Trova l'ultimo poligono di questo militare
            $ultimoPoligono = Poligono::where('militare_id', $militare->id)
                ->orderByDesc('data_poligono')
                ->orderByDesc('id')
                ->first();
            
            if ($ultimoPoligono) {
                $militare->update([
                    'ultimo_poligono_id' => $ultimoPoligono->id,
                    'data_ultimo_poligono' => $ultimoPoligono->data_poligono
                ]);
            }
        });

        // Dopo aver eliminato un poligono, aggiorna l'ultimo poligono del militare
        static::deleted(function ($poligono) {
            $militare = $poligono->militare;
            
            // Trova il nuovo ultimo poligono di questo militare
            $ultimoPoligono = Poligono::where('militare_id', $militare->id)
                ->orderByDesc('data_poligono')
                ->orderByDesc('id')
                ->first();
            
            $militare->update([
                'ultimo_poligono_id' => $ultimoPoligono?->id,
                'data_ultimo_poligono' => $ultimoPoligono?->data_poligono
            ]);
        });
    }
}
