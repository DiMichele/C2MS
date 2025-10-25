<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TurnoSettimanale extends Model
{
    use HasFactory;

    protected $table = 'turni_settimanali';

    protected $fillable = [
        'data_inizio',
        'data_fine',
        'anno',
        'numero_settimana',
        'stato',
        'note',
    ];

    protected $casts = [
        'data_inizio' => 'date',
        'data_fine' => 'date',
        'anno' => 'integer',
        'numero_settimana' => 'integer',
    ];

    /**
     * Assegnazioni per questo turno
     */
    public function assegnazioni()
    {
        return $this->hasMany(AssegnazioneTurno::class, 'turno_settimanale_id');
    }

    /**
     * Crea un turno per una data specifica (trova il giovedì della settimana)
     */
    public static function createForDate(Carbon $date)
    {
        // Trova il giovedì della settimana
        $dataInizio = $date->copy()->startOfWeek(Carbon::THURSDAY);
        $dataFine = $dataInizio->copy()->addDays(6); // Mercoledì

        return self::firstOrCreate(
            [
                'anno' => $dataInizio->year,
                'numero_settimana' => $dataInizio->weekOfYear,
            ],
            [
                'data_inizio' => $dataInizio,
                'data_fine' => $dataFine,
                'stato' => 'bozza',
            ]
        );
    }

    /**
     * Ottieni array dei giorni della settimana
     */
    public function getGiorniSettimana()
    {
        $giorni = [];
        $nomiGiorni = ['GIOVEDI', 'VENERDI', 'SABATO', 'DOMENICA', 'LUNEDI', 'MARTEDI', 'MERCOLEDI'];
        
        for ($i = 0; $i < 7; $i++) {
            $data = $this->data_inizio->copy()->addDays($i);
            $giorni[] = [
                'data' => $data,
                'data_formatted' => $data->format('d/m/Y'),
                'giorno_settimana' => $nomiGiorni[$i],
                'giorno_num' => $data->day,
                'is_weekend' => in_array($data->dayOfWeek, [0, 6]), // Domenica, Sabato
            ];
        }
        
        return $giorni;
    }

    /**
     * Scope per turni attivi/bozza
     */
    public function scopeBozza($query)
    {
        return $query->where('stato', 'bozza');
    }

    public function scopePubblicati($query)
    {
        return $query->where('stato', 'pubblicato');
    }

    /**
     * Pubblica il turno
     */
    public function pubblica()
    {
        $this->update(['stato' => 'pubblicato']);
    }

    /**
     * Archivia il turno
     */
    public function archivia()
    {
        $this->update(['stato' => 'archiviato']);
    }
}

