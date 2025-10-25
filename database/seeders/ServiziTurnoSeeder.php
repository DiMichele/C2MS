<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiziTurnoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Prima disattiva tutti i vecchi servizi con "Posto X"
        DB::table('servizi_turno')
            ->where(function($query) {
                $query->where('codice', 'like', '%-1')
                      ->orWhere('codice', 'like', '%-2')
                      ->orWhere('codice', 'like', '%-3')
                      ->orWhere('codice', 'like', '%1') // PDT1, AA1, ecc.
                      ->orWhere('codice', 'like', '%2') // PDT2, AA2, ecc.
                      ->orWhere('codice', 'like', '%3'); // PDT3, AA3, ecc.
            })
            ->where('codice', '!=', 'G-BTG') // Non disattivare i servizi principali
            ->where('codice', '!=', 'NVA')
            ->where('codice', '!=', 'CG')
            ->where('codice', '!=', 'NS-DA')
            ->where('codice', '!=', 'PDT')
            ->where('codice', '!=', 'AA')
            ->where('codice', '!=', 'VS-CETLI')
            ->where('codice', '!=', 'CORR')
            ->where('codice', '!=', 'NDI')
            ->update(['attivo' => false]);
        
        $servizi = [
            [
                'nome' => 'GRADUATO DI BTG',
                'codice' => 'G-BTG',
                'sigla_cpt' => 'G-BTG', // Verde nel CPT
                'descrizione' => 'Graduato di Battaglione',
                'num_posti' => 3,
                'tipo' => 'multiplo',
                'orario_inizio' => null,
                'orario_fine' => null,
                'ordine' => 1,
                'attivo' => true,
            ],
            [
                'nome' => 'NUCLEO VIGILANZA ARMATA D\'AVANZO',
                'codice' => 'NVA',
                'sigla_cpt' => 'NVA', // Verde nel CPT
                'descrizione' => 'Nucleo Vigilanza Armata D\'Avanzo',
                'num_posti' => 3,
                'tipo' => 'multiplo',
                'orario_inizio' => null,
                'orario_fine' => null,
                'ordine' => 2,
                'attivo' => true,
            ],
            [
                'nome' => 'CONDUTTORE GUARDIA',
                'codice' => 'CG',
                'sigla_cpt' => 'CG', // Verde nel CPT
                'descrizione' => 'Servizio Conduttore Guardia',
                'num_posti' => 3,
                'tipo' => 'multiplo',
                'orario_inizio' => null,
                'orario_fine' => null,
                'ordine' => 3,
                'attivo' => true,
            ],
            [
                'nome' => 'NUCLEO SORV. D\'AVANZO 07:30 - 17:00',
                'codice' => 'NS-DA',
                'sigla_cpt' => 'NS-DA', // Verde nel CPT
                'descrizione' => 'Nucleo Sorveglianza D\'Avanzo (07:30-17:00)',
                'num_posti' => 3,
                'tipo' => 'multiplo',
                'orario_inizio' => '07:30:00',
                'orario_fine' => '17:00:00',
                'ordine' => 4,
                'attivo' => true,
            ],
            [
                'nome' => 'VIGILANZA PIAN DEL TERMINE',
                'codice' => 'PDT',
                'sigla_cpt' => 'PDT', // Verde nel CPT
                'descrizione' => 'Vigilanza Pian del Termine',
                'num_posti' => 2,
                'tipo' => 'multiplo',
                'orario_inizio' => null,
                'orario_fine' => null,
                'ordine' => 5,
                'attivo' => true,
            ],
            [
                'nome' => 'ADDETTO ANTINCENDIO',
                'codice' => 'AA',
                'sigla_cpt' => 'AA', // Verde nel CPT
                'descrizione' => 'Addetto Antincendio',
                'num_posti' => 2,
                'tipo' => 'multiplo',
                'orario_inizio' => null,
                'orario_fine' => null,
                'ordine' => 6,
                'attivo' => true,
            ],
            [
                'nome' => 'VIGILANZA SETTIMANALE CETLI',
                'codice' => 'VS-CETLI',
                'sigla_cpt' => 'VS-CETLI', // Verde nel CPT
                'descrizione' => 'Vigilanza Settimanale CETLI',
                'num_posti' => 2,
                'tipo' => 'multiplo',
                'orario_inizio' => null,
                'orario_fine' => null,
                'ordine' => 7,
                'attivo' => true,
            ],
            [
                'nome' => 'CORRIERE',
                'codice' => 'CORR',
                'sigla_cpt' => 'CORR', // Verde nel CPT
                'descrizione' => 'Servizio Corriere',
                'num_posti' => 2,
                'tipo' => 'multiplo',
                'orario_inizio' => null,
                'orario_fine' => null,
                'ordine' => 8,
                'attivo' => true,
            ],
            [
                'nome' => 'NUCLEO DIFESA IMMEDIATA',
                'codice' => 'NDI',
                'sigla_cpt' => 'NDI', // Verde nel CPT
                'descrizione' => 'Nucleo Difesa Immediata',
                'num_posti' => 2,
                'tipo' => 'multiplo',
                'orario_inizio' => null,
                'orario_fine' => null,
                'ordine' => 9,
                'attivo' => true,
            ],
        ];

        foreach ($servizi as $servizio) {
            DB::table('servizi_turno')->updateOrInsert(
                ['codice' => $servizio['codice']],
                array_merge($servizio, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
        
        $this->command->info('Servizi turno popolati con successo!');
    }
}

