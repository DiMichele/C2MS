<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class ScadenzaApprontamento extends Model
{
    use HasFactory;

    protected $table = 'scadenze_approntamenti';

    protected $fillable = [
        'militare_id',
        'teatro_operativo_id',
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
     * Configurazione delle colonne per la visualizzazione (FALLBACK)
     * Usata solo se la tabella config_colonne_approntamenti non esiste
     * Le colonne marcate come 'fonte' => 'scadenze_militari' leggono dalla tabella condivisa
     */
    public const COLONNE_DEFAULT = [
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
        'lavoratore_4h' => ['label' => 'Lavoratore 4H (5 anni)', 'fonte' => 'scadenze_militari', 'campo_sorgente' => 'lavoratore_4h'],
        'lavoratore_8h' => ['label' => 'Lavoratore 8H (5 anni)', 'fonte' => 'scadenze_militari', 'campo_sorgente' => 'lavoratore_8h'],
        'preposto' => ['label' => 'Preposto (2 anni)', 'fonte' => 'scadenze_militari', 'campo_sorgente' => 'preposto'],
        'passaporti' => ['label' => 'PASSAPORTI', 'fonte' => 'approntamenti'],
    ];

    /**
     * Getter dinamico per COLONNE (compatibilità con codice esistente)
     * Legge dalla tabella config_colonne_approntamenti se esiste
     */
    public static function getColonne(): array
    {
        // Verifica se la tabella esiste
        if (Schema::hasTable('config_colonne_approntamenti')) {
            return ConfigColonnaApprontamento::getColonneAttive();
        }
        return self::COLONNE_DEFAULT;
    }

    /**
     * Property accessor per compatibilità con codice esistente che usa COLONNE
     */
    public const COLONNE = 'use_getColonne_method';

    /**
     * Colonne che provengono da scadenze_militari (dinamico)
     */
    public static function getColonneDaScadenzeMilitari(): array
    {
        if (Schema::hasTable('config_colonne_approntamenti')) {
            return ConfigColonnaApprontamento::getColonneCondivise();
        }
        return ['idoneita_to', 'bls', 'lavoratore_4h', 'lavoratore_8h', 'preposto'];
    }

    /**
     * Durate in mesi per le scadenze (dinamico dal DB)
     */
    public static function getDurataCampo(string $campo): ?int
    {
        if (Schema::hasTable('config_colonne_approntamenti')) {
            return ConfigColonnaApprontamento::getScadenzaMesi($campo);
        }
        return null;
    }

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
        return in_array($campo, self::getColonneDaScadenzeMilitari());
    }

    /**
     * Ottiene il nome del campo sorgente per colonne condivise
     */
    public static function getCampoSorgente(string $campo): string
    {
        if (Schema::hasTable('config_colonne_approntamenti')) {
            return ConfigColonnaApprontamento::getCampoSorgente($campo);
        }
        $config = self::COLONNE_DEFAULT[$campo] ?? null;
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
        
        $mesi = self::getDurataCampo($campo);
        
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
        if (Schema::hasTable('config_colonne_approntamenti')) {
            return ConfigColonnaApprontamento::getLabels();
        }
        
        $labels = [];
        foreach (self::COLONNE_DEFAULT as $campo => $config) {
            $labels[$campo] = $config['label'];
        }
        return $labels;
    }

    /**
     * Ottiene solo le colonne delle cattedre (escluso teatro_operativo e colonne condivise)
     */
    public static function getColonneCattedre(): array
    {
        $colonne = self::getColonne();
        return array_filter($colonne, function($config, $campo) {
            // Escludi le colonne condivise e teatro_operativo
            return ($config['fonte'] ?? '') === 'approntamenti' && $campo !== 'teatro_operativo';
        }, ARRAY_FILTER_USE_BOTH);
    }
}
