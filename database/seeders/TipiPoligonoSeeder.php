<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoPoligono;
use Illuminate\Support\Str;

class TipiPoligonoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipiPoligono = [
            [
                'codice' => 'teatro_operativo',
                'nome' => 'Teatro Operativo',
                'descrizione' => 'Qualifica per teatro operativo',
                'punteggio_minimo' => 60,
                'punteggio_massimo' => 100,
                'durata_mesi' => 6,
                'ordine' => 1,
                'attivo' => true
            ],
            [
                'codice' => 'mantenimento_arma_lunga',
                'nome' => 'Mantenimento Arma Lunga',
                'descrizione' => 'Mantenimento qualifica con arma lunga (fucile)',
                'punteggio_minimo' => 55,
                'punteggio_massimo' => 100,
                'durata_mesi' => 6,
                'ordine' => 2,
                'attivo' => true
            ],
            [
                'codice' => 'mantenimento_arma_corta',
                'nome' => 'Mantenimento Arma Corta',
                'descrizione' => 'Mantenimento qualifica con arma corta (pistola)',
                'punteggio_minimo' => 50,
                'punteggio_massimo' => 100,
                'durata_mesi' => 6,
                'ordine' => 3,
                'attivo' => true
            ],
            [
                'codice' => 'tiro_precisione',
                'nome' => 'Tiro di Precisione',
                'descrizione' => 'Prova di tiro di precisione con arma individuale',
                'punteggio_minimo' => 60,
                'punteggio_massimo' => 100,
                'durata_mesi' => 12,
                'ordine' => 4,
                'attivo' => true
            ],
            [
                'codice' => 'tiro_rapido',
                'nome' => 'Tiro Rapido',
                'descrizione' => 'Prova di tiro rapido con arma individuale',
                'punteggio_minimo' => 50,
                'punteggio_massimo' => 100,
                'durata_mesi' => 12,
                'ordine' => 5,
                'attivo' => true
            ],
            [
                'codice' => 'tiro_notturno',
                'nome' => 'Tiro Notturno',
                'descrizione' => 'Prova di tiro in condizioni di scarsa visibilità',
                'punteggio_minimo' => 40,
                'punteggio_massimo' => 100,
                'durata_mesi' => 12,
                'ordine' => 6,
                'attivo' => true
            ],
            [
                'codice' => 'tiro_combattimento',
                'nome' => 'Tiro di Combattimento',
                'descrizione' => 'Prova di tiro in scenario di combattimento',
                'punteggio_minimo' => 65,
                'punteggio_massimo' => 100,
                'durata_mesi' => 12,
                'ordine' => 7,
                'attivo' => true
            ],
            [
                'codice' => 'qualificazione_tiratore_scelto',
                'nome' => 'Qualificazione Tiratore Scelto',
                'descrizione' => 'Prova per qualifica di tiratore scelto',
                'punteggio_minimo' => 80,
                'punteggio_massimo' => 100,
                'durata_mesi' => 12,
                'ordine' => 8,
                'attivo' => true
            ]
        ];

        foreach ($tipiPoligono as $tipo) {
            TipoPoligono::updateOrCreate(
                ['codice' => $tipo['codice']],
                $tipo
            );
        }

        $this->command->info('Creati/Aggiornati ' . count($tipiPoligono) . ' tipi di poligono.');
    }
}
