<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoServizio;
use App\Models\PianificazioneMensile;
use App\Models\PianificazioneGiornaliera;
use App\Models\Militare;
use Carbon\Carbon;

class PianificazioniSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Controlla se esistono già pianificazioni giornaliere
        if (PianificazioneGiornaliera::count() > 0) {
            $this->command->info('⚠️  Pianificazioni giornaliere già presenti nel database. Saltando creazione.');
            return;
        }

        // Crea pianificazioni mensili per i prossimi 3 mesi
        $militari = Militare::with(['grado', 'plotone', 'polo'])->get();
        $tipiServizio = TipoServizio::all();
        
        for ($mese = 0; $mese < 3; $mese++) {
            $dataMese = Carbon::now()->addMonths($mese);
            $anno = $dataMese->year;
            $meseNum = $dataMese->month;
            
            // Controlla se esiste già una pianificazione per questo mese/anno
            $pianificazioneEsistente = PianificazioneMensile::where('anno', $anno)
                ->where('mese', $meseNum)
                ->first();
            
            if ($pianificazioneEsistente) {
                $pianificazioneMensile = $pianificazioneEsistente;
                $this->command->info("Pianificazione per {$dataMese->format('F Y')} già esistente, uso quella esistente.");
            } else {
                $pianificazioneMensile = PianificazioneMensile::create([
                    'mese' => $meseNum,
                    'anno' => $anno,
                    'nome' => "Pianificazione {$dataMese->format('F Y')}",
                    'stato' => 'attiva',
                    'descrizione' => "Pianificazione mensile per {$dataMese->format('F Y')}",
                    'data_creazione' => now(),
                ]);
            }

            // Crea pianificazioni giornaliere per ogni giorno del mese
            $giorniNelMese = $dataMese->daysInMonth;
            
            for ($giorno = 1; $giorno <= $giorniNelMese; $giorno++) {
                $dataGiorno = Carbon::create($anno, $meseNum, $giorno);
                
                // Skip weekend per alcuni servizi
                if ($dataGiorno->isWeekend()) {
                    continue;
                }

                // Assegna impegni casuali ai militari
                $militariConImpegni = $militari->random(rand(3, 8)); // 3-8 militari con impegni al giorno
                
                foreach ($militariConImpegni as $militare) {
                    $tipoServizio = $tipiServizio->random();
                    
                    PianificazioneGiornaliera::create([
                        'militare_id' => $militare->id,
                        'pianificazione_mensile_id' => $pianificazioneMensile->id,
                        'tipo_servizio_id' => $tipoServizio->id,
                        'giorno' => $giorno,
                        'note' => $this->getNotaImpegnoRandom($tipoServizio->codice, $militare)
                    ]);
                }
            }
        }

        $this->command->info('✅ Pianificazioni create con successo!');
        $this->command->info('📊 Creati:');
        $this->command->info('   - 3 pianificazioni mensili');
        $this->command->info('   - Pianificazioni giornaliere con impegni casuali');
    }

    /**
     * Genera note casuali per gli impegni
     */
    private function getNotaImpegnoRandom($codiceServizio, $militare)
    {
        $notePerServizio = [
            'G-BTG' => 'Servizio di comando e coordinamento',
            'NVA' => 'Vigilanza armata in zona avanzata',
            'CG' => 'Condotta servizio di guardia',
            'NS-DA' => 'Sorveglianza zona avanzata',
            'PDT' => 'Vigilanza Pian del Termine',
            'AA' => 'Servizio antincendio e sicurezza',
            'VS-CETLI' => 'Vigilanza settimanale CETLI',
            'CORR' => 'Servizio corriere e trasporti',
            'NDI' => 'Nucleo difesa immediata',
            'FERIE' => 'Periodo di ferie autorizzate',
            'MALATTIA' => 'Assenza per motivi di salute',
            'CORSO' => 'Partecipazione corso di formazione',
            'MISSIONE' => 'Missione operativa esterna',
        ];

        $note = $notePerServizio[$codiceServizio] ?? 'Impegno programmato';
        
        // Aggiungi dettagli specifici per il militare
        if ($militare->grado) {
            $note .= " - {$militare->grado->nome} {$militare->cognome}";
        }
        
        return $note;
    }
}
