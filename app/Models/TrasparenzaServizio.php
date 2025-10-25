<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrasparenzaServizio extends Model
{
    use HasFactory;

    protected $table = 'trasparenza_servizi';

    protected $fillable = [
        'militare_id',
        'anno',
        'mese',
        'giorno',
        'codice_servizio',
    ];

    protected $casts = [
        'anno' => 'integer',
        'mese' => 'integer',
        'giorno' => 'integer',
    ];

    /**
     * Relazione con Militare
     */
    public function militare()
    {
        return $this->belongsTo(Militare::class);
    }

    /**
     * Codici servizio disponibili
     */
    public static function codiciServizio()
    {
        return [
            'S-SI' => 'Servizio Interno',
            'S-UP' => 'Ufficiale di Picchetto',
            'S-CG' => 'Conduttore Guardia',
            'SG' => 'Sorveglianza Generale',
            'G1' => 'Guardia 1',
            'G2' => 'Guardia 2',
            'PDT1' => 'Pian del Termine 1',
            'PDT2' => 'Pian del Termine 2',
            'S-CD1' => 'Servizio CD 1',
            'S-CD2' => 'Servizio CD 2',
            'SA' => 'Servizio Armato',
            'S-UI' => 'Ufficiale Informazioni',
            'S-SG' => 'Sottufficiale Guardia',
        ];
    }

    /**
     * Nomi mesi
     */
    public static function nomiMesi()
    {
        return [
            1 => 'Gennaio',
            2 => 'Febbraio',
            3 => 'Marzo',
            4 => 'Aprile',
            5 => 'Maggio',
            6 => 'Giugno',
            7 => 'Luglio',
            8 => 'Agosto',
            9 => 'Settembre',
            10 => 'Ottobre',
            11 => 'Novembre',
            12 => 'Dicembre',
        ];
    }
}
