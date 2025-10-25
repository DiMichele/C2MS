<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            // Struttura organizzativa
            GradiSeeder::class,
            CompagniePlotoniSeeder::class,
            MansioniRuoliSeeder::class,
            
            // Militari e dati completi
            MilitariCompletiSeeder::class,
            
            // Servizi e impegni
            ServiziTurnoSeeder::class,
            ImpegniServiziSeeder::class,
            
            // Utenti e board
            UsersSeeder::class,
            BoardColumnSeeder::class,
            BoardActivitiesSeeder::class,
        ]);
    }
}
