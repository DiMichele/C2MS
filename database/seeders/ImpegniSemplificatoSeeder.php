<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoServizio;
use App\Models\PianificazioneMensile;
use App\Models\PianificazioneGiornaliera;
use App\Models\Militare;
use Carbon\Carbon;

class ImpegniSemplificatoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Controlla se esistono già tipi di servizio
        if (TipoServizio::count() > 0) {
            $this->command->info('⚠️  Tipi di servizio già presenti nel database. Saltando creazione.');
            return;
        }

        // Crea tipi di servizio per il CPT
        $tipiServizio = [
            ['codice' => 'G-BTG', 'nome' => 'Graduato di Battaglione', 'colore' => 'verde'],
            ['codice' => 'NVA', 'nome' => 'Nucleo Vigilanza Armata', 'colore' => 'verde'],
            ['codice' => 'CG', 'nome' => 'Conduttore Guardia', 'colore' => 'verde'],
            ['codice' => 'NS-DA', 'nome' => 'Nucleo Sorveglianza D\'Avanzo', 'colore' => 'verde'],
            ['codice' => 'PDT', 'nome' => 'Vigilanza Pian del Termine', 'colore' => 'verde'],
            ['codice' => 'AA', 'nome' => 'Addetto Antincendio', 'colore' => 'verde'],
            ['codice' => 'VS-CETLI', 'nome' => 'Vigilanza Settimanale CETLI', 'colore' => 'verde'],
            ['codice' => 'CORR', 'nome' => 'Corriere', 'colore' => 'verde'],
            ['codice' => 'NDI', 'nome' => 'Nucleo Difesa Immediata', 'colore' => 'verde'],
            ['codice' => 'FERIE', 'nome' => 'Ferie', 'colore' => 'rosso'],
            ['codice' => 'MALATTIA', 'nome' => 'Malattia', 'colore' => 'giallo'],
            ['codice' => 'CORSO', 'nome' => 'Corso di Formazione', 'colore' => 'blu'],
            ['codice' => 'MISSIONE', 'nome' => 'Missione', 'colore' => 'arancione'],
        ];

        foreach ($tipiServizio as $tipo) {
            TipoServizio::create($tipo);
        }

        // Crea pianificazioni mensili per i prossimi 3 mesi
        $militari = Militare::with(['grado', 'plotone', 'polo'])->get();
        
        for ($mese = 0; $mese < 3; $mese++) {
            $dataMese = Carbon::now()->addMonths($mese);
            $anno = $dataMese->year;
            $meseNum = $dataMese->month;
            
            $pianificazioneMensile = PianificazioneMensile::create([
                'mese' => $meseNum,
                'anno' => $anno,
                'nome' => "Pianificazione {$dataMese->format('F Y')}",
                'attiva' => true,
            ]);

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
                    $tipoServizio = TipoServizio::inRandomOrder()->first();
                    
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

        $this->command->info('✅ Impegni e servizi creati con successo!');
        $this->command->info('📊 Creati:');
        $this->command->info('   - ' . count($tipiServizio) . ' tipi di servizio per CPT');
        $this->command->info('   - Pianificazioni mensili per 3 mesi');
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
