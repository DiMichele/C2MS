<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ScadenzaApprontamento extends Model
{
    use HasFactory;

    protected $table = 'scadenze_approntamenti';

    protected $fillable = [
        'militare_id',
        'teatro_operativo',
        'bls',
        'ultimo_poligono_approntamento',
        'poligono',
        'tipo_poligono_da_effettuare',
        'bam',
        'awareness_cied',
        'cied_pratico',
        'stress_management',
        'elitrasporto',
        'mcm',
        'uas',
        'ict',
        'rapporto_media',
        'abuso_alcol_droga',
        'training_covid',
        'rspp_4h',
        'rspp_8h',
        'rspp_preposti',
        'passaporti',
    ];

    /**
     * Configurazione delle colonne per la visualizzazione
     */
    public const COLONNE = [
        'teatro_operativo' => 'Teatro Operativo',
        'bls' => 'BLS',
        'ultimo_poligono_approntamento' => 'Ultimo Poligono Approntamento',
        'poligono' => 'POLIGONO',
        'tipo_poligono_da_effettuare' => 'Tipo Poligono da Effettuare',
        'bam' => 'B.A.M.',
        'awareness_cied' => 'AWARENESS C-IED',
        'cied_pratico' => 'C-IED PRATICO',
        'stress_management' => 'STRESS MANAGEMENT',
        'elitrasporto' => 'ELITRASPORTO',
        'mcm' => 'MCM',
        'uas' => 'UAS',
        'ict' => 'ICT',
        'rapporto_media' => 'RAPPORTO CON I MEDIA',
        'abuso_alcol_droga' => 'ABUSO ALCOL e DROGA',
        'training_covid' => 'TRAINING ON COVID (A cura DSS)',
        'rspp_4h' => 'RSPP 4H (5 anni)',
        'rspp_8h' => 'RSPP 8H (5 anni)',
        'rspp_preposti' => 'RSPP PREPOSTI (2 anni)',
        'passaporti' => 'PASSAPORTI',
    ];

    /**
     * Durate in mesi per le scadenze (se applicabile)
     */
    private const DURATE = [
        'rspp_4h' => 60,        // 5 anni
        'rspp_8h' => 60,        // 5 anni
        'rspp_preposti' => 24,  // 2 anni
        'bls' => 24,            // 2 anni tipicamente
    ];

    // Relazione con Militare
    public function militare()
    {
        return $this->belongsTo(Militare::class);
    }

    /**
     * Verifica se un valore è "Non Richiesto"
     */
    public function isNonRichiesto(string $campo): bool
    {
        $valore = $this->$campo;
        return $valore === 'NR' || $valore === 'Non richiesto';
    }

    /**
     * Ottiene il valore formattato per la visualizzazione
     */
    public function getValoreFormattato(string $campo): string
    {
        $valore = $this->$campo;
        
        if (empty($valore)) {
            return '-';
        }
        
        if ($this->isNonRichiesto($campo)) {
            return 'Non richiesto';
        }
        
        // Prova a parsare come data
        try {
            $data = Carbon::parse($valore);
            return $data->format('d/m/Y');
        } catch (\Exception $e) {
            return $valore;
        }
    }

    /**
     * Calcola la data di scadenza per un campo (se ha una durata definita)
     */
    public function calcolaScadenza(string $campo): ?Carbon
    {
        $valore = $this->$campo;
        
        if (empty($valore) || $this->isNonRichiesto($campo)) {
            return null;
        }
        
        $mesi = self::DURATE[$campo] ?? null;
        
        if (!$mesi) {
            return null;
        }
        
        try {
            $dataConseguimento = Carbon::parse($valore);
            return $dataConseguimento->addMonths($mesi);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Verifica lo stato di un campo
     * @return string 'valido'|'in_scadenza'|'scaduto'|'non_presente'|'non_richiesto'
     */
    public function verificaStato(string $campo): string
    {
        $valore = $this->$campo;
        
        if (empty($valore)) {
            return 'non_presente';
        }
        
        if ($this->isNonRichiesto($campo)) {
            return 'non_richiesto';
        }
        
        // Se non ha durata definita, è sempre valido se ha una data
        $scadenza = $this->calcolaScadenza($campo);
        
        if (!$scadenza) {
            // Nessuna scadenza, solo verifica che sia una data valida
            try {
                Carbon::parse($valore);
                return 'valido';
            } catch (\Exception $e) {
                return 'non_presente';
            }
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
     * Ottiene il colore CSS per un campo
     */
    public function getColore(string $campo): string
    {
        $stato = $this->verificaStato($campo);

        return match($stato) {
            'scaduto' => 'background-color: #ffcccc; color: #cc0000;',
            'in_scadenza' => 'background-color: #fff3cd; color: #856404;',
            'valido' => 'background-color: #d4edda; color: #155724;',
            'non_richiesto' => 'background-color: #e2e3e5; color: #6c757d;',
            'non_presente' => 'background-color: #f8f9fa; color: #6c757d;',
        };
    }

    /**
     * Formatta la data di scadenza per la visualizzazione
     */
    public function formatScadenza(string $campo): string
    {
        $scadenza = $this->calcolaScadenza($campo);
        
        if (!$scadenza) {
            return '-';
        }

        return $scadenza->format('d/m/Y');
    }

    /**
     * Ottiene tutti i dati formattati per la visualizzazione
     */
    public function getTuttiDati(): array
    {
        $risultati = [];
        
        foreach (self::COLONNE as $campo => $label) {
            $risultati[$campo] = [
                'label' => $label,
                'valore' => $this->getValoreFormattato($campo),
                'stato' => $this->verificaStato($campo),
                'colore' => $this->getColore($campo),
                'scadenza' => $this->formatScadenza($campo),
            ];
        }

        return $risultati;
    }
}
