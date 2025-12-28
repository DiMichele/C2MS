<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScadenzaMilitare;
use App\Models\ScadenzaCorsoSpp;
use App\Models\ConfigurazioneCorsoSpp;
use Illuminate\Support\Facades\DB;

class MigraScadenzeSpp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spp:migra-scadenze';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra le scadenze SPP dalla tabella scadenze_militari alla nuova tabella scadenze_corsi_spp';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Inizio migrazione scadenze SPP...');
        
        // Mapping tra campi vecchi e codici corsi
        $mapping = [
            'lavoratore_4h_data_conseguimento' => 'lavoratore_4h',
            'lavoratore_8h_data_conseguimento' => 'lavoratore_8h',
            'preposto_data_conseguimento' => 'preposto',
            'dirigenti_data_conseguimento' => 'dirigente',
            'antincendio_data_conseguimento' => 'antincendio',
            'blsd_data_conseguimento' => 'blsd',
            'primo_soccorso_aziendale_data_conseguimento' => 'primo_soccorso_aziendale',
        ];

        $scadenze = ScadenzaMilitare::all();
        $totaleMigrate = 0;

        DB::beginTransaction();
        
        try {
            foreach ($scadenze as $scadenza) {
                foreach ($mapping as $campo => $codiceCorso) {
                    $corso = ConfigurazioneCorsoSpp::where('codice_corso', $codiceCorso)->first();
                    
                    if (!$corso) {
                        $this->warn("⚠️  Corso '{$codiceCorso}' non trovato in configurazione_corsi_spp");
                        continue;
                    }
                    
                    $dataConseguimento = $scadenza->$campo;
                    
                    if ($dataConseguimento) {
                        ScadenzaCorsoSpp::updateOrCreate(
                            [
                                'militare_id' => $scadenza->militare_id,
                                'configurazione_corso_spp_id' => $corso->id,
                            ],
                            [
                                'data_conseguimento' => $dataConseguimento,
                            ]
                        );
                        $totaleMigrate++;
                    }
                }
            }
            
            DB::commit();
            $this->info("✅ Migrazione completata! {$totaleMigrate} scadenze migrate con successo.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Errore durante la migrazione: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
