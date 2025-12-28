<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConfigurazioneCorsoSpp;

class ConfigurazioneCorsiSppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $corsi = [
            // Corsi di Formazione SPP
            [
                'codice_corso' => 'lavoratore_4h',
                'nome_corso' => 'Lavoratore 4h',
                'durata_anni' => 4,
                'tipo' => 'formazione',
                'attivo' => true,
                'ordine' => 10
            ],
            [
                'codice_corso' => 'lavoratore_8h',
                'nome_corso' => 'Lavoratore 8h',
                'durata_anni' => 4,
                'tipo' => 'formazione',
                'attivo' => true,
                'ordine' => 20
            ],
            [
                'codice_corso' => 'preposto',
                'nome_corso' => 'Preposto',
                'durata_anni' => 2,
                'tipo' => 'formazione',
                'attivo' => true,
                'ordine' => 30
            ],
            [
                'codice_corso' => 'dirigente',
                'nome_corso' => 'Dirigente',
                'durata_anni' => 4,
                'tipo' => 'formazione',
                'attivo' => true,
                'ordine' => 40
            ],
            [
                'codice_corso' => 'antincendio',
                'nome_corso' => 'Antincendio',
                'durata_anni' => 1,
                'tipo' => 'formazione',
                'attivo' => true,
                'ordine' => 50
            ],
            [
                'codice_corso' => 'blsd',
                'nome_corso' => 'BLSD',
                'durata_anni' => 2,
                'tipo' => 'formazione',
                'attivo' => true,
                'ordine' => 60
            ],
            [
                'codice_corso' => 'primo_soccorso_aziendale',
                'nome_corso' => 'Primo Soccorso Aziendale',
                'durata_anni' => 2,
                'tipo' => 'formazione',
                'attivo' => true,
                'ordine' => 70
            ],
            
            // Corsi Accordo Stato Regione
            [
                'codice_corso' => 'abilitazione_trattori',
                'nome_corso' => 'Abilitazione Trattori',
                'durata_anni' => 5,
                'tipo' => 'accordo_stato_regione',
                'attivo' => true,
                'ordine' => 10
            ],
            [
                'codice_corso' => 'abilitazione_mmt',
                'nome_corso' => 'Abilitazione MMT',
                'durata_anni' => 5,
                'tipo' => 'accordo_stato_regione',
                'attivo' => true,
                'ordine' => 20
            ],
            [
                'codice_corso' => 'abilitazione_ple',
                'nome_corso' => 'Abilitazione PLE',
                'durata_anni' => 5,
                'tipo' => 'accordo_stato_regione',
                'attivo' => true,
                'ordine' => 30
            ],
            [
                'codice_corso' => 'abilitazione_gru',
                'nome_corso' => 'Abilitazione Gru',
                'durata_anni' => 5,
                'tipo' => 'accordo_stato_regione',
                'attivo' => true,
                'ordine' => 40
            ],
            [
                'codice_corso' => 'abilitazione_muletto',
                'nome_corso' => 'Abilitazione Muletto',
                'durata_anni' => 5,
                'tipo' => 'accordo_stato_regione',
                'attivo' => true,
                'ordine' => 50
            ],
        ];

        foreach ($corsi as $corso) {
            ConfigurazioneCorsoSpp::updateOrCreate(
                ['codice_corso' => $corso['codice_corso']],
                $corso
            );
        }

        $this->command->info('✅ Configurazione corsi SPP completata!');
        $this->command->info('   • ' . count($corsi) . ' corsi creati/aggiornati');
    }
}
