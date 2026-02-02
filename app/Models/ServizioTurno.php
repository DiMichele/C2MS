<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OrganizationalUnit;
use App\Traits\BelongsToOrganizationalUnit;

class ServizioTurno extends Model
{
    use HasFactory, BelongsToOrganizationalUnit;

    protected $table = 'servizi_turno';

    protected $fillable = [
        'nome',
        'organizational_unit_id', // Nuova gerarchia organizzativa
        'codice',
        'sigla_cpt',
        'smontante_cpt',
        'descrizione',
        'num_posti',
        'tipo',
        'orario_inizio',
        'orario_fine',
        'ordine',
        'attivo',
    ];

    protected $casts = [
        'attivo' => 'boolean',
        'smontante_cpt' => 'boolean',
        'num_posti' => 'integer',
        'ordine' => 'integer',
        'orario_inizio' => 'datetime:H:i',
        'orario_fine' => 'datetime:H:i',
    ];

    /**
     * Assegnazioni per questo servizio
     */
    public function assegnazioni()
    {
        return $this->hasMany(AssegnazioneTurno::class, 'servizio_turno_id');
    }

    /**
     * Unità organizzativa (nuova gerarchia)
     */
    public function organizationalUnit()
    {
        return $this->belongsTo(OrganizationalUnit::class, 'organizational_unit_id');
    }

    /**
     * Scope per servizi attivi
     */
    public function scopeAttivi($query)
    {
        return $query->where('attivo', true);
    }

    /**
     * Scope per ordinamento
     */
    public function scopeOrdinati($query)
    {
        return $query->orderBy('ordine');
    }

    /**
     * Verifica se il servizio è multi-posto
     */
    public function isMultiposto()
    {
        return $this->tipo === 'multiplo' || $this->num_posti > 1;
    }

    /**
     * Ottieni il tipo servizio corrispondente per il CPT
     */
    public function tipoServizioCpt()
    {
        if ($this->sigla_cpt) {
            return TipoServizio::where('codice', $this->sigla_cpt)->first();
        }
        return null;
    }
}

