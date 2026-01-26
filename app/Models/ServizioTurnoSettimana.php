<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Gestisce il numero di posti per servizio specifico per ogni settimana.
 * Questo permette di modificare i posti senza influenzare le settimane passate.
 */
class ServizioTurnoSettimana extends Model
{
    use HasFactory;

    protected $table = 'servizio_turno_settimana';

    protected $fillable = [
        'turno_settimanale_id',
        'servizio_turno_id',
        'num_posti',
        'smontante_cpt',
        'attivo',
    ];

    protected $casts = [
        'num_posti' => 'integer',
        'smontante_cpt' => 'boolean',
        'attivo' => 'boolean',
    ];

    /**
     * Relazione con il turno settimanale
     */
    public function turnoSettimanale()
    {
        return $this->belongsTo(TurnoSettimanale::class, 'turno_settimanale_id');
    }

    /**
     * Relazione con il servizio turno
     */
    public function servizioTurno()
    {
        return $this->belongsTo(ServizioTurno::class, 'servizio_turno_id');
    }

    /**
     * Ottieni o crea le impostazioni per un servizio in una settimana specifica
     */
    public static function getOrCreate(int $turnoSettimanaleId, int $servizioTurnoId, array $defaults = [])
    {
        return self::firstOrCreate(
            [
                'turno_settimanale_id' => $turnoSettimanaleId,
                'servizio_turno_id' => $servizioTurnoId,
            ],
            array_merge([
                'num_posti' => 0,
                'smontante_cpt' => false,
                'attivo' => true,
            ], $defaults)
        );
    }

    /**
     * Ottieni i posti per un servizio in una settimana specifica
     * Se non esistono impostazioni specifiche, usa quelle globali del servizio
     */
    public static function getPostiPerSettimana(int $turnoSettimanaleId, int $servizioTurnoId): int
    {
        $config = self::where('turno_settimanale_id', $turnoSettimanaleId)
            ->where('servizio_turno_id', $servizioTurnoId)
            ->first();

        if ($config) {
            return $config->num_posti;
        }

        // Fallback ai posti globali del servizio
        $servizio = ServizioTurno::find($servizioTurnoId);
        return $servizio ? $servizio->num_posti : 0;
    }

    /**
     * Verifica se smontante è attivo per un servizio in una settimana
     */
    public static function isSmontanteAttivo(int $turnoSettimanaleId, int $servizioTurnoId): bool
    {
        $config = self::where('turno_settimanale_id', $turnoSettimanaleId)
            ->where('servizio_turno_id', $servizioTurnoId)
            ->first();

        if ($config) {
            return $config->smontante_cpt;
        }

        // Fallback al valore globale del servizio
        $servizio = ServizioTurno::find($servizioTurnoId);
        return $servizio ? $servizio->smontante_cpt : false;
    }
}
