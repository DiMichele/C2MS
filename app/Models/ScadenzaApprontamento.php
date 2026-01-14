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
        'passaporti',
    ];

    /**
     * Configurazione delle colonne per la visualizzazione
     * Le colonne marcate come 'fonte' => 'scadenze_militari' leggono dalla tabella condivisa
     */
    public const COLONNE = [
        'teatro_operativo' => ['label' => 'Teatro Operativo', 'fonte' => 'approntamenti'],
        // Colonne condivise con SPP (Corsi di Formazione)
        'idoneita_to' => ['label' => 'Idoneità T.O.', 'fonte' => 'scadenze_militari', 'campo_sorgente' => 'idoneita_to'],
        'bls' => ['label' => 'BLS', 'fonte' => 'scadenze_militari', 'campo_sorgente' => 'blsd'],
        'ultimo_poligono_approntamento' => ['label' => 'Ultimo Poligono Approntamento', 'fonte' => 'approntamenti'],
        'poligono' => ['label' => 'POLIGONO', 'fonte' => 'approntamenti'],
        'tipo_poligono_da_effettuare' => ['label' => 'Tipo Poligono da Effettuare', 'fonte' => 'approntamenti'],
        'bam' => ['label' => 'B.A.M.', 'fonte' => 'approntamenti'],
        'awareness_cied' => ['label' => 'AWARENESS C-IED', 'fonte' => 'approntamenti'],
        'cied_pratico' => ['label' => 'C-IED PRATICO', 'fonte' => 'approntamenti'],
        'stress_management' => ['label' => 'STRESS MANAGEMENT', 'fonte' => 'approntamenti'],
        'elitrasporto' => ['label' => 'ELITRASPORTO', 'fonte' => 'approntamenti'],
        'mcm' => ['label' => 'MCM', 'fonte' => 'approntamenti'],
        'uas' => ['label' => 'UAS', 'fonte' => 'approntamenti'],
        'ict' => ['label' => 'ICT', 'fonte' => 'approntamenti'],
        'rapporto_media' => ['label' => 'RAPPORTO CON I MEDIA', 'fonte' => 'approntamenti'],
        'abuso_alcol_droga' => ['label' => 'ABUSO ALCOL e DROGA', 'fonte' => 'approntamenti'],
        'training_covid' => ['label' => 'TRAINING ON COVID (A cura DSS)', 'fonte' => 'approntamenti'],
        // Colonne condivise con SPP
        'lavoratore_4h' => ['label' => 'Lavoratore 4H (5 anni)', 'fonte' => 'scadenze_militari', 'campo_sorgente' => 'lavoratore_4h'],
        'lavoratore_8h' => ['label' => 'Lavoratore 8H (5 anni)', 'fonte' => 'scadenze_militari', 'campo_sorgente' => 'lavoratore_8h'],
        'preposto' => ['label' => 'Preposto (2 anni)', 'fonte' => 'scadenze_militari', 'campo_sorgente' => 'preposto'],
        'passaporti' => ['label' => 'PASSAPORTI', 'fonte' => 'approntamenti'],
    ];

    /**
     * Colonne che provengono da scadenze_militari
     */
    public const COLONNE_DA_SCADENZE_MILITARI = [
        'idoneita_to', 'bls', 'lavoratore_4h', 'lavoratore_8h', 'preposto'
    ];

    /**
     * Durate in mesi per le scadenze proprie (se applicabile)
     */
    private const DURATE = [
        // Le durate per colonne condivise sono gestite da ScadenzaMilitare
    ];

    // Relazione con Militare
    public function militare()
    {
        return $this->belongsTo(Militare::class);
    }

    /**
     * Verifica se una colonna è condivisa con scadenze_militari
     */
    public static function isColonnaCondivisa(string $campo): bool
    {
        return in_array($campo, self::COLONNE_DA_SCADENZE_MILITARI);
    }

    /**
     * Ottiene il nome del campo sorgente per colonne condivise
     */
    public static function getCampoSorgente(string $campo): string
    {
        $config = self::COLONNE[$campo] ?? null;
        return $config['campo_sorgente'] ?? $campo;
    }

    /**
     * Verifica se un valore è "Non Richiesto"
     */
    public function isNonRichiesto(string $campo): bool
    {
        $valore = $this->$campo ?? null;
        return $valore === 'NR' || $valore === 'Non richiesto';
    }

    /**
     * Ottiene il valore formattato per la visualizzazione
     */
    public function getValoreFormattato(string $campo): string
    {
        $valore = $this->$campo ?? null;
        
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
        $valore = $this->$campo ?? null;
        
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
        $valore = $this->$campo ?? null;
        
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
     * Ottiene le label semplificate per la view
     */
    public static function getLabels(): array
    {
        $labels = [];
        foreach (self::COLONNE as $campo => $config) {
            $labels[$campo] = $config['label'];
        }
        return $labels;
    }
}
