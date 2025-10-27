<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder per i gradi ufficiali dell'Esercito Italiano
 * 
 * Fonte: https://it.wikipedia.org/wiki/Gradi_e_qualifiche_dell%27Esercito_Italiano
 */
class GradiEsercitoItalianoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Non facciamo truncate per evitare problemi con foreign keys
        // Invece faremo update dei gradi esistenti e insert dei nuovi
        
        $gradi = [
            // UFFICIALI GENERALI
            ['nome' => 'Generale', 'abbreviazione' => 'Gen.', 'ordine' => 110],
            ['nome' => 'Generale di Corpo d\'Armata', 'abbreviazione' => 'Gen. C.A.', 'ordine' => 105],
            ['nome' => 'Generale di Divisione', 'abbreviazione' => 'Gen. D.', 'ordine' => 100],
            ['nome' => 'Generale di Brigata', 'abbreviazione' => 'Gen. B.', 'ordine' => 95],
            
            // UFFICIALI SUPERIORI
            ['nome' => 'Colonnello', 'abbreviazione' => 'Col.', 'ordine' => 90],
            ['nome' => 'Tenente Colonnello', 'abbreviazione' => 'Ten. Col.', 'ordine' => 85],
            ['nome' => 'Maggiore', 'abbreviazione' => 'Magg.', 'ordine' => 80],
            
            // UFFICIALI INFERIORI
            ['nome' => 'Capitano', 'abbreviazione' => 'Cap.', 'ordine' => 75],
            ['nome' => 'Tenente', 'abbreviazione' => 'Ten.', 'ordine' => 70],
            ['nome' => 'Sottotenente', 'abbreviazione' => 'S.Ten.', 'ordine' => 65],
            
            // SOTTUFFICIALI - MARESCIALLI
            ['nome' => 'Primo Luogotenente', 'abbreviazione' => '1° Lgt.', 'ordine' => 60],
            ['nome' => 'Luogotenente', 'abbreviazione' => 'Lgt.', 'ordine' => 59],
            ['nome' => 'Maresciallo Maggiore', 'abbreviazione' => 'Mar. Magg.', 'ordine' => 58],
            ['nome' => 'Maresciallo Ordinario', 'abbreviazione' => 'Mar. Ord.', 'ordine' => 57],
            ['nome' => 'Maresciallo Capo', 'abbreviazione' => 'Mar. Ca.', 'ordine' => 56],
            ['nome' => 'Maresciallo', 'abbreviazione' => 'Mar.', 'ordine' => 55],
            
            // SOTTUFFICIALI - SERGENTI
            ['nome' => 'Sergente Maggiore Capo', 'abbreviazione' => 'Serg. Magg. Ca.', 'ordine' => 50],
            ['nome' => 'Sergente Maggiore', 'abbreviazione' => 'Serg. Magg.', 'ordine' => 49],
            ['nome' => 'Sergente', 'abbreviazione' => 'Serg.', 'ordine' => 48],
            
            // GRADUATI
            ['nome' => 'Caporal Maggiore Scelto', 'abbreviazione' => 'Cap. Magg. Sc.', 'ordine' => 40],
            ['nome' => 'Caporal Maggiore Capo', 'abbreviazione' => 'Cap. Magg. Ca.', 'ordine' => 39],
            ['nome' => 'Caporal Maggiore', 'abbreviazione' => 'Cap. Magg.', 'ordine' => 38],
            ['nome' => 'Caporale', 'abbreviazione' => 'Cpl.', 'ordine' => 37],
            
            // MILITARI DI TRUPPA
            ['nome' => 'Primo Caporal', 'abbreviazione' => '1° Cpl.', 'ordine' => 20],
            ['nome' => 'Soldato', 'abbreviazione' => 'Sold.', 'ordine' => 10],
        ];

        foreach ($gradi as $grado) {
            DB::table('gradi')->updateOrInsert(
                ['nome' => $grado['nome']], // Condizione di ricerca
                [
                    'abbreviazione' => $grado['abbreviazione'],
                    'ordine' => $grado['ordine'],
                    'updated_at' => now(),
                ]
            );
        }

        // Aggiungi created_at per i nuovi record
        DB::table('gradi')
            ->whereNull('created_at')
            ->update(['created_at' => now()]);

        $count = DB::table('gradi')->count();
        $this->command->info("✓ Gradi dell'Esercito Italiano aggiornati! Totale: {$count} gradi");
    }
}

