<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoPoligono;

class TipiPoligonoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipiPoligono = [
            [
                'nome' => 'Tiro di Precisione',
                'descrizione' => 'Prova di tiro di precisione con arma individuale',
                'punteggio_minimo' => 60,
                'punteggio_massimo' => 100,
                'attivo' => true
            ],
            [
                'nome' => 'Tiro Rapido',
                'descrizione' => 'Prova di tiro rapido con arma individuale',
                'punteggio_minimo' => 50,
                'punteggio_massimo' => 100,
                'attivo' => true
            ],
            [
                'nome' => 'Tiro Notturno',
                'descrizione' => 'Prova di tiro in condizioni di scarsa visibilità',
                'punteggio_minimo' => 40,
                'punteggio_massimo' => 100,
                'attivo' => true
            ],
            [
                'nome' => 'Tiro con Pistola',
                'descrizione' => 'Prova di tiro con pistola d\'ordinanza',
                'punteggio_minimo' => 45,
                'punteggio_massimo' => 100,
                'attivo' => true
            ],
            [
                'nome' => 'Tiro con Fucile',
                'descrizione' => 'Prova di tiro con fucile d\'assalto',
                'punteggio_minimo' => 55,
                'punteggio_massimo' => 100,
                'attivo' => true
            ],
            [
                'nome' => 'Tiro di Combattimento',
                'descrizione' => 'Prova di tiro in scenario di combattimento',
                'punteggio_minimo' => 65,
                'punteggio_massimo' => 100,
                'attivo' => true
            ],
            [
                'nome' => 'Qualificazione Tiratore Scelto',
                'descrizione' => 'Prova per qualifica di tiratore scelto',
                'punteggio_minimo' => 80,
                'punteggio_massimo' => 100,
                'attivo' => true
            ]
        ];

        foreach ($tipiPoligono as $tipo) {
            TipoPoligono::updateOrCreate(
                ['nome' => $tipo['nome']],
                $tipo
            );
        }

        $this->command->info('Creati ' . count($tipiPoligono) . ' tipi di poligono.');
    }
}
