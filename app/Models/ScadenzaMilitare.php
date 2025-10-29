<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ScadenzaMilitare extends Model
{
    use HasFactory;

    protected $table = 'scadenze_militari';

    protected $fillable = [
        'militare_id',
        'pefo_data_conseguimento',
        'idoneita_mans_data_conseguimento',
        'idoneita_smi_data_conseguimento',
        'lavoratore_4h_data_conseguimento',
        'lavoratore_8h_data_conseguimento',
        'preposto_data_conseguimento',
        'dirigenti_data_conseguimento',
        'antincendio_data_conseguimento',
        'blsd_data_conseguimento',
        'primo_soccorso_aziendale_data_conseguimento',
        'ecg_data_conseguimento',
        'prelievi_data_conseguimento',
        'tiri_approntamento_data_conseguimento',
        'mantenimento_arma_lunga_data_conseguimento',
        'mantenimento_arma_corta_data_conseguimento',
        'poligono_approntamento_data_conseguimento',
        'poligono_mantenimento_data_conseguimento',
    ];

    protected $casts = [
        'pefo_data_conseguimento' => 'date',
        'idoneita_mans_data_conseguimento' => 'date',
        'idoneita_smi_data_conseguimento' => 'date',
        'lavoratore_4h_data_conseguimento' => 'date',
        'lavoratore_8h_data_conseguimento' => 'date',
        'preposto_data_conseguimento' => 'date',
        'dirigenti_data_conseguimento' => 'date',
        'antincendio_data_conseguimento' => 'date',
        'blsd_data_conseguimento' => 'date',
        'primo_soccorso_aziendale_data_conseguimento' => 'date',
        'ecg_data_conseguimento' => 'date',
        'prelievi_data_conseguimento' => 'date',
        'tiri_approntamento_data_conseguimento' => 'date',
        'mantenimento_arma_lunga_data_conseguimento' => 'date',
        'mantenimento_arma_corta_data_conseguimento' => 'date',
        'poligono_approntamento_data_conseguimento' => 'date',
        'poligono_mantenimento_data_conseguimento' => 'date',
    ];

    // Relazione con Militare
    public function militare()
    {
        return $this->belongsTo(Militare::class);
    }

    /**
     * Configurazione durate (in mesi)
     */
    private const DURATE = [
        'pefo' => 12,
        'idoneita_mans' => 12,
        'idoneita_smi' => 12,
        'lavoratore_4h' => 60,
        'lavoratore_8h' => 60,
        'preposto' => 24,
        'dirigenti' => 24,
        'poligono_approntamento' => 6,
        'poligono_mantenimento' => 6,
    ];

    /**
     * Calcola la data di scadenza per un certificato
     */
    public function calcolaScadenza(string $tipo): ?Carbon
    {
        $campo = $tipo . '_data_conseguimento';
        $dataConseguimento = $this->$campo;

        if (!$dataConseguimento) {
            return null;
        }

        $mesi = self::DURATE[$tipo] ?? 12;
        return Carbon::parse($dataConseguimento)->addMonths($mesi);
    }

    /**
     * Verifica lo stato di un certificato
     * @return string 'valido'|'in_scadenza'|'scaduto'|'non_presente'
     */
    public function verificaStato(string $tipo): string
    {
        $scadenza = $this->calcolaScadenza($tipo);

        if (!$scadenza) {
            return 'non_presente';
        }

        $oggi = Carbon::now();
        $trentaGiorni = Carbon::now()->addDays(30);

        if ($scadenza < $oggi) {
            return 'scaduto';
        } elseif ($scadenza <= $trentaGiorni) {
            return 'in_scadenza';
        } else {
            return 'valido';
        }
    }

    /**
     * Ottiene il colore CSS per un certificato
     */
    public function getColore(string $tipo): string
    {
        $stato = $this->verificaStato($tipo);

        return match($stato) {
            'scaduto' => 'background-color: #ffcccc; color: #cc0000;',
            'in_scadenza' => 'background-color: #fff3cd; color: #856404;',
            'valido' => 'background-color: #d4edda; color: #155724;',
            'non_presente' => 'background-color: #e9ecef; color: #6c757d;',
        };
    }

    /**
     * Formatta la data di scadenza per la visualizzazione
     */
    public function formatScadenza(string $tipo): string
    {
        $scadenza = $this->calcolaScadenza($tipo);
        
        if (!$scadenza) {
            return '-';
        }

        return $scadenza->format('d/m/Y');
    }

    /**
     * Ottiene tutte le scadenze con i loro stati
     */
    public function getTutteScadenze(): array
    {
        $risultati = [];
        
        foreach (self::DURATE as $tipo => $mesi) {
            $risultati[$tipo] = [
                'data_conseguimento' => $this->{$tipo . '_data_conseguimento'} 
                    ? Carbon::parse($this->{$tipo . '_data_conseguimento'})->format('d/m/Y') 
                    : '-',
                'data_scadenza' => $this->formatScadenza($tipo),
                'stato' => $this->verificaStato($tipo),
                'colore' => $this->getColore($tipo),
            ];
        }

        return $risultati;
    }
}

