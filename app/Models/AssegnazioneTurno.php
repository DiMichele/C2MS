<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssegnazioneTurno extends Model
{
    use HasFactory;

    protected $table = 'assegnazioni_turno';

    protected $fillable = [
        'turno_settimanale_id',
        'servizio_turno_id',
        'militare_id',
        'data_servizio',
        'giorno_settimana',
        'posizione',
        'note',
        'sincronizzato_cpt',
    ];

    protected $casts = [
        'data_servizio' => 'date',
        'sincronizzato_cpt' => 'boolean',
        'posizione' => 'integer',
    ];

    /**
     * Turno settimanale associato
     */
    public function turnoSettimanale()
    {
        return $this->belongsTo(TurnoSettimanale::class, 'turno_settimanale_id');
    }

    /**
     * Servizio turno associato
     */
    public function servizioTurno()
    {
        return $this->belongsTo(ServizioTurno::class, 'servizio_turno_id');
    }

    /**
     * Militare assegnato
     */
    public function militare()
    {
        return $this->belongsTo(Militare::class, 'militare_id');
    }

    /**
     * Scope per assegnazioni non sincronizzate
     */
    public function scopeNonSincronizzate($query)
    {
        return $query->where('sincronizzato_cpt', false);
    }

    /**
     * Scope per data specifica
     */
    public function scopePerData($query, $data)
    {
        return $query->where('data_servizio', $data);
    }

    /**
     * Scope per militare
     */
    public function scopePerMilitare($query, $militareId)
    {
        return $query->where('militare_id', $militareId);
    }

    /**
     * Marca come sincronizzato
     */
    public function marcaSincronizzato()
    {
        $this->update(['sincronizzato_cpt' => true]);
    }
}

