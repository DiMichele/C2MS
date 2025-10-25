<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Grado;

class GradiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Elimina i gradi esistenti (se non ci sono dipendenze)
        if (Grado::count() > 0) {
            $this->command->info('⚠️  Grado già presenti nel database. Saltando creazione.');
            return;
        }

        $gradi = [
            // Ufficiali
            ['nome' => 'Generale', 'abbreviazione' => 'Gen.', 'ordine' => 100],
            ['nome' => 'Colonnello', 'abbreviazione' => 'Col.', 'ordine' => 90],
            ['nome' => 'Tenente Colonnello', 'abbreviazione' => 'T.Col.', 'ordine' => 85],
            ['nome' => 'Maggiore', 'abbreviazione' => 'Mag.', 'ordine' => 80],
            ['nome' => 'Capitano', 'abbreviazione' => 'Cap.', 'ordine' => 75],
            ['nome' => 'Tenente', 'abbreviazione' => 'Ten.', 'ordine' => 70],
            ['nome' => 'Sottotenente', 'abbreviazione' => 'S.Ten.', 'ordine' => 65],
            
            // Sottufficiali
            ['nome' => 'Maresciallo Maggiore', 'abbreviazione' => 'M.Mag.', 'ordine' => 60],
            ['nome' => 'Maresciallo Capo', 'abbreviazione' => 'M.Cap.', 'ordine' => 55],
            ['nome' => 'Maresciallo Ordinario', 'abbreviazione' => 'Mar.', 'ordine' => 50],
            ['nome' => 'Sergente Maggiore', 'abbreviazione' => 'S.Mag.', 'ordine' => 45],
            ['nome' => 'Sergente', 'abbreviazione' => 'Serg.', 'ordine' => 40],
            
            // Graduati
            ['nome' => 'Caporal Maggiore', 'abbreviazione' => 'C.Mag.', 'ordine' => 30],
            ['nome' => 'Caporale', 'abbreviazione' => 'Cap.', 'ordine' => 25],
            
            // Truppa
            ['nome' => 'Soldato', 'abbreviazione' => 'Sol.', 'ordine' => 10],
        ];

        foreach ($gradi as $grado) {
            Grado::create($grado);
        }

        $this->command->info('✅ Gradi militari creati con successo!');
    }
}
