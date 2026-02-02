<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServizioTurno;
use App\Models\OrganizationalUnit;

class ServiziTurnoSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Ottieni tutte le unità organizzative attive di livello Battaglione (depth=1)
        $units = OrganizationalUnit::where('depth', 1)->where('is_active', true)->get();
        
        // Se non ci sono unità, usa la prima disponibile o fallback a null
        if ($units->isEmpty()) {
            $units = OrganizationalUnit::where('is_active', true)->get();
        }
        
        $servizi = [
            ['nome' => 'Guardia', 'num_posti' => 2, 'ordine' => 1],
            ['nome' => 'Piantone', 'num_posti' => 1, 'ordine' => 2],
            ['nome' => 'Servizio Comando', 'num_posti' => 1, 'ordine' => 3],
            ['nome' => 'Autista', 'num_posti' => 1, 'ordine' => 4],
            ['nome' => 'Reperibilità', 'num_posti' => 2, 'ordine' => 5],
        ];

        $totalCreati = 0;
        
        // Crea servizi per ogni unità organizzativa
        foreach ($units as $unit) {
            foreach ($servizi as $servizio) {
                ServizioTurno::withoutGlobalScopes()->updateOrCreate(
                    [
                        'nome' => $servizio['nome'],
                        'organizational_unit_id' => $unit->id,
                    ],
                    [
                        'num_posti' => $servizio['num_posti'],
                        'ordine' => $servizio['ordine'],
                        'attivo' => true,
                    ]
                );
                $totalCreati++;
            }
        }

        $this->command->info('✅ Creati/aggiornati ' . $totalCreati . ' servizi turno per ' . $units->count() . ' unità organizzative');
    }
}
