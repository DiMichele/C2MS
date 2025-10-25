<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Militare;
use App\Models\ScadenzaMilitare;
use Carbon\Carbon;

class ScadenzeMilitariSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $militari = Militare::all();
        
        foreach ($militari as $militare) {
            // Crea scadenze con date casuali realistiche
            $scadenza = ScadenzaMilitare::create([
                'militare_id' => $militare->id,
                
                // PEFO - Port Efficiency (idoneità fisica)
                'pefo_data_conseguimento' => $this->getDataCasuale(12),
                
                // Idoneità mansione specifica
                'idoneita_mans_data_conseguimento' => $this->getDataCasuale(12),
                
                // Idoneità SMI (Servizio Militare Internazionale)
                'idoneita_smi_data_conseguimento' => rand(0, 100) < 70 ? $this->getDataCasuale(12) : null,
                
                // Corsi lavoratori 4h
                'lavoratore_4h_data_conseguimento' => $this->getDataCasuale(60),
                
                // Corsi lavoratori 8h
                'lavoratore_8h_data_conseguimento' => $this->getDataCasuale(60),
                
                // Corso preposti (solo per alcuni)
                'preposto_data_conseguimento' => rand(0, 100) < 40 ? $this->getDataCasuale(24) : null,
                
                // Corso dirigenti (solo per pochi)
                'dirigenti_data_conseguimento' => rand(0, 100) < 15 ? $this->getDataCasuale(24) : null,
                
                // Poligono approntamento
                'poligono_approntamento_data_conseguimento' => $this->getDataCasuale(6),
                
                // Poligono mantenimento
                'poligono_mantenimento_data_conseguimento' => $this->getDataCasuale(6),
            ]);
        }
        
        $this->command->info('✅ Scadenze militari create con successo!');
        $this->command->info('📊 Creati:');
        $this->command->info('   - ' . $militari->count() . ' record di scadenze per militari');
    }
    
    /**
     * Genera una data casuale per un certificato
     * 
     * @param int $validitaMesi Validità del certificato in mesi
     * @return Carbon
     */
    private function getDataCasuale($validitaMesi)
    {
        // Crea una distribuzione realistica:
        // 60% validi (da 1 a validitaMesi/2 mesi fa)
        // 25% in scadenza (da validitaMesi-2 a validitaMesi mesi fa)
        // 15% scaduti (da validitaMesi a validitaMesi+6 mesi fa)
        
        $rand = rand(1, 100);
        
        if ($rand <= 60) {
            // Valido - ottieni una data tra 1 e metà validità fa
            $mesiIndietro = rand(1, (int)($validitaMesi / 2));
        } elseif ($rand <= 85) {
            // In scadenza - ottieni una data vicina alla scadenza
            $mesiIndietro = rand((int)($validitaMesi - 2), $validitaMesi);
        } else {
            // Scaduto - ottieni una data oltre la scadenza
            $mesiIndietro = rand($validitaMesi, $validitaMesi + 6);
        }
        
        return Carbon::now()->subMonths($mesiIndietro)->subDays(rand(0, 30));
    }
}
