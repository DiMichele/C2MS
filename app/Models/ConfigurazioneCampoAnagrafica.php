<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigurazioneCampoAnagrafica extends Model
{
    use HasFactory;

    protected $table = 'configurazione_campi_anagrafica';

    protected $fillable = [
        'nome_campo',
        'etichetta',
        'tipo_campo',
        'opzioni',
        'ordine',
        'attivo',
        'obbligatorio',
        'descrizione',
    ];

    protected $casts = [
        'opzioni' => 'array',
        'attivo' => 'boolean',
        'obbligatorio' => 'boolean',
        'ordine' => 'integer',
    ];

    // Scopes
    public function scopeAttivi($query)
    {
        return $query->where('attivo', true);
    }

    public function scopeOrdinati($query)
    {
        return $query->orderBy('ordine')->orderBy('etichetta');
    }

    // Relazioni
    public function valori()
    {
        return $this->hasMany(ValoreCampoAnagrafica::class, 'configurazione_campo_id');
    }

    // Helper per ottenere il valore per un militare specifico
    public function getValorePerMilitare($militare_id)
    {
        return $this->valori()->where('militare_id', $militare_id)->first()?->valore;
    }
}
