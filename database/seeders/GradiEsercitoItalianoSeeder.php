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
            ['nome' => 'Generale di Corpo d\'Armata', 'abbreviazione' => 'Gen. C.A.', 'ordine' => 110],
            ['nome' => 'Generale di Divisione', 'abbreviazione' => 'Gen. D.', 'ordine' => 105],
            ['nome' => 'Generale di Brigata', 'abbreviazione' => 'Gen. B.', 'ordine' => 100],
            
            // UFFICIALI SUPERIORI
            ['nome' => 'Colonnello', 'abbreviazione' => 'Col.', 'ordine' => 90],
            ['nome' => 'Tenente Colonnello', 'abbreviazione' => 'Ten. Col.', 'ordine' => 85],
            ['nome' => 'Maggiore', 'abbreviazione' => 'Magg.', 'ordine' => 80],
            
            // UFFICIALI INFERIORI
            ['nome' => 'Capitano', 'abbreviazione' => 'Cap.', 'ordine' => 75],
            ['nome' => 'Tenente', 'abbreviazione' => 'Ten.', 'ordine' => 70],
            ['nome' => 'Sottotenente', 'abbreviazione' => 'S. Ten.', 'ordine' => 65],
            
            // SOTTUFFICIALI - LUOGOTENENTI
            ['nome' => 'Primo Luogotenente', 'abbreviazione' => '1° Lgt.', 'ordine' => 62],
            ['nome' => 'Luogotenente', 'abbreviazione' => 'Lgt.', 'ordine' => 61],
            
            // SOTTUFFICIALI - MARESCIALLI
            ['nome' => 'Primo Maresciallo', 'abbreviazione' => '1° Mar.', 'ordine' => 60],
            ['nome' => 'Maresciallo Capo', 'abbreviazione' => 'Mar. Ca.', 'ordine' => 59],
            ['nome' => 'Maresciallo Ordinario', 'abbreviazione' => 'Mar. Ord.', 'ordine' => 58],
            ['nome' => 'Maresciallo', 'abbreviazione' => 'Mar.', 'ordine' => 57],
            
            // SOTTUFFICIALI - SERGENTI
            ['nome' => 'Sergente Maggiore Aiutante', 'abbreviazione' => 'Serg. Magg. Aiut.', 'ordine' => 53],
            ['nome' => 'Sergente Maggiore Capo', 'abbreviazione' => 'Serg. Magg. Capo', 'ordine' => 52],
            ['nome' => 'Sergente Maggiore', 'abbreviazione' => 'Serg. Magg.', 'ordine' => 51],
            ['nome' => 'Sergente', 'abbreviazione' => 'Serg.', 'ordine' => 50],
            
            // GRADUATI
            ['nome' => 'Graduato Aiutante', 'abbreviazione' => 'Grad. Aiut.', 'ordine' => 45],
            ['nome' => 'Primo Graduato', 'abbreviazione' => '1° Grad.', 'ordine' => 44],
            ['nome' => 'Graduato Capo', 'abbreviazione' => 'Grad. Capo', 'ordine' => 43],
            ['nome' => 'Graduato Scelto', 'abbreviazione' => 'Grad. Sc.', 'ordine' => 42],
            ['nome' => 'Graduato', 'abbreviazione' => 'Grad.', 'ordine' => 41],
            
            // MILITARI DI TRUPPA
            ['nome' => 'Caporal Maggiore', 'abbreviazione' => 'C.le Magg.', 'ordine' => 30],
            ['nome' => 'Caporale', 'abbreviazione' => 'C.le', 'ordine' => 20],
            ['nome' => 'Soldato', 'abbreviazione' => 'Sol.', 'ordine' => 10],
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

