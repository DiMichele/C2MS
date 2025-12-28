<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValoreCampoAnagrafica extends Model
{
    use HasFactory;

    protected $table = 'valori_campi_anagrafica';

    protected $fillable = [
        'militare_id',
        'configurazione_campo_id',
        'valore',
    ];

    // Relazioni
    public function militare()
    {
        return $this->belongsTo(Militare::class);
    }

    public function configurazione()
    {
        return $this->belongsTo(ConfigurazioneCampoAnagrafica::class, 'configurazione_campo_id');
    }
}
