<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ScadenzaCorsoSpp extends Model
{
    use HasFactory;

    protected $table = 'scadenze_corsi_spp';

    protected $fillable = [
        'militare_id',
        'configurazione_corso_spp_id',
        'data_conseguimento',
    ];

    protected $casts = [
        'data_conseguimento' => 'date',
    ];

    // Relationships
    public function militare()
    {
        return $this->belongsTo(Militare::class);
    }

    public function corso()
    {
        return $this->belongsTo(ConfigurazioneCorsoSpp::class, 'configurazione_corso_spp_id');
    }

    // Helper method per calcolare la scadenza
    public function calcolaScadenza()
    {
        if (!$this->data_conseguimento || !$this->corso) {
            return [
                'data_scadenza' => null,
                'stato' => 'mancante',
                'classe' => 'mancante',
                'giorni_rimanenti' => null,
                'data_conseguimento' => null,
                'durata' => null,
            ];
        }

        // Ricarica la relazione corso per assicurarsi di avere i dati più recenti
        $this->load('corso');
        
        $dataConseguimento = Carbon::parse($this->data_conseguimento);
        $durata = $this->corso->durata_anni;
        
        // Se durata = 0, significa "nessuna scadenza" => sempre valido
        if ($durata == 0) {
            return [
                'data_scadenza' => null,
                'stato' => 'valido',
                'classe' => 'valido',
                'giorni_rimanenti' => null,
                'data_conseguimento' => $dataConseguimento,
                'durata' => 0,
            ];
        }
        
        $dataScadenza = $dataConseguimento->copy()->addYears($durata);
        $giorniRimanenti = Carbon::now()->diffInDays($dataScadenza, false);

        // Determina lo stato
        if ($giorniRimanenti < 0) {
            $stato = 'scaduto';
            $classe = 'scaduto';
        } elseif ($giorniRimanenti <= 30) {
            $stato = 'in_scadenza';
            $classe = 'in_scadenza';
        } else {
            $stato = 'valido';
            $classe = 'valido';
        }

        return [
            'data_scadenza' => $dataScadenza,
            'stato' => $stato,
            'classe' => $classe,
            'giorni_rimanenti' => $giorniRimanenti,
            'data_conseguimento' => $dataConseguimento,
            'durata' => $durata,
        ];
    }
}
