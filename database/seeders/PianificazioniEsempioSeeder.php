<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Militare;
use App\Models\PianificazioneMensile;
use App\Models\PianificazioneGiornaliera;
use App\Models\TipoServizio;
use Carbon\Carbon;

class PianificazioniEsempioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Trova la pianificazione mensile attuale
        $pianificazioneMensile = PianificazioneMensile::where('mese', Carbon::now()->month)
            ->where('anno', Carbon::now()->year)
            ->first();
            
        if (!$pianificazioneMensile) {
            $pianificazioneMensile = PianificazioneMensile::create([
                'mese' => Carbon::now()->month,
                'anno' => Carbon::now()->year,
                'nome' => Carbon::now()->format('F Y'),
                'attiva' => true
            ]);
        }

        // Ottieni alcuni militari casuali
        $militari = Militare::inRandomOrder()->limit(20)->get();
        
        // Codici tipici del CPT
        $codiciCPT = ['TO', 'lo', 'p', 'S-UI', 'KOSOVO', 'LCC', 'CENTURIA'];
        
        // Ottieni i tipi servizio esistenti
        $tipiServizio = TipoServizio::whereIn('codice', $codiciCPT)->get()->keyBy('codice');
        
        // Crea tipi servizio mancanti
        foreach ($codiciCPT as $codice) {
            if (!$tipiServizio->has($codice)) {
                $tipoServizio = TipoServizio::create([
                    'codice' => $codice,
                    'nome' => $this->getNomePerCodice($codice),
                    'descrizione' => $this->getDescrizionePerCodice($codice)
                ]);
                $tipiServizio[$codice] = $tipoServizio;
            }
        }

        $pianificazioniCreate = 0;
        
        // Crea pianificazioni per alcuni giorni
        foreach ($militari as $militare) {
            // Assegna codici per alcuni giorni del mese (non tutti)
            for ($giorno = 1; $giorno <= 10; $giorno++) {
                // 70% di probabilità di avere una pianificazione
                if (rand(1, 100) <= 70) {
                    $codice = $codiciCPT[array_rand($codiciCPT)];
                    
                    // Evita duplicati
                    $exists = PianificazioneGiornaliera::where('militare_id', $militare->id)
                        ->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                        ->where('giorno', $giorno)
                        ->exists();
                        
                    if (!$exists) {
                        PianificazioneGiornaliera::create([
                            'militare_id' => $militare->id,
                            'pianificazione_mensile_id' => $pianificazioneMensile->id,
                            'giorno' => $giorno,
                            'tipo_servizio_id' => $tipiServizio[$codice]->id
                        ]);
                        
                        $pianificazioniCreate++;
                    }
                }
            }
        }

        $this->command->info("Creati $pianificazioniCreate pianificazioni di esempio per " . $militari->count() . " militari.");
    }
    
    private function getNomePerCodice($codice)
    {
        return match($codice) {
            'TO' => 'Disponibile',
            'lo' => 'Licenza Ordinaria',
            'p' => 'Permesso',
            'S-UI' => 'Servizio Unità',
            'KOSOVO' => 'Missione Kosovo',
            'LCC' => 'Comando',
            'CENTURIA' => 'Addestramento Centuria',
            default => $codice
        };
    }
    
    private function getDescrizionePerCodice($codice)
    {
        return match($codice) {
            'TO' => 'Militare disponibile per servizio',
            'lo' => 'Licenza ordinaria',
            'p' => 'Permesso giornaliero',
            'S-UI' => 'Servizio presso l\'unità',
            'KOSOVO' => 'Missione internazionale Kosovo',
            'LCC' => 'Servizio di comando',
            'CENTURIA' => 'Addestramento Centuria',
            default => "Servizio $codice"
        };
    }
}
