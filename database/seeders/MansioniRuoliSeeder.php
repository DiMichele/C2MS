<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mansione;
use App\Models\Ruolo;
use App\Models\Militare;
use App\Models\OrganizationalUnit;

class MansioniRuoliSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ottieni tutte le unità organizzative attive di livello Battaglione (depth=1)
        $units = OrganizationalUnit::where('depth', 1)->where('is_active', true)->get();
        
        // Se non ci sono unità, usa la prima disponibile
        if ($units->isEmpty()) {
            $units = OrganizationalUnit::where('is_active', true)->get();
        }
        
        // Crea mansioni se non esistono
        $mansioni = [
            'Comandante di Plotone',
            'Sottocomandante',
            'Capogruppo',
            'Operatore Radio',
            'Autista',
            'Meccanico',
            'Armiere',
            'Sanitario',
            'Logistico',
            'Tiratore Scelto',
            'Specialista IT',
            'Cuciniere',
            'Magazziniere',
            'Addetto Sicurezza',
            'Militare Semplice'
        ];

        $mansioniCreate = 0;
        // Crea mansioni per ogni unità organizzativa
        foreach ($units as $unit) {
            foreach ($mansioni as $nome) {
                Mansione::withoutGlobalScopes()->firstOrCreate(
                    [
                        'nome' => $nome,
                        'organizational_unit_id' => $unit->id,
                    ]
                );
                $mansioniCreate++;
            }
        }

        // Crea ruoli se non esistono
        $ruoli = [
            'Comandante',
            'Sottocomandante', 
            'Capo Servizio',
            'Operatore',
            'Specialista',
            'Addetto',
            'Militare di Truppa'
        ];

        foreach ($ruoli as $nome) {
            Ruolo::firstOrCreate(
                ['nome' => $nome],
                ['descrizione' => "Ruolo: $nome"]
            );
        }

        $this->command->info('Creati mansioni per ' . $units->count() . ' unità organizzative e ' . count($ruoli) . ' ruoli.');

        // Assegna mansioni e ruoli casuali ai militari
        $mansioniIds = Mansione::pluck('id')->toArray();
        $ruoliIds = Ruolo::pluck('id')->toArray();
        
        $militari = Militare::whereNull('mansione_id')->orWhereNull('ruolo_id')->get();
        
        foreach ($militari as $militare) {
            // Assegna mansione basata sul grado
            $gradoNome = $militare->grado->nome ?? '';
            $mansioneId = null;
            $ruoloId = null;

            // Logica per assegnare mansioni appropriate al grado
            if (str_contains($gradoNome, 'Ten.') || str_contains($gradoNome, 'Cap.')) {
                $mansioneId = Mansione::where('nome', 'Comandante di Plotone')->first()?->id;
                $ruoloId = Ruolo::where('nome', 'Comandante')->first()?->id;
            } elseif (str_contains($gradoNome, 'Serg.')) {
                $mansioneId = Mansione::where('nome', 'Sottocomandante')->first()?->id;
                $ruoloId = Ruolo::where('nome', 'Sottocomandante')->first()?->id;
            } elseif (str_contains($gradoNome, 'Grd.') || str_contains($gradoNome, 'C.le')) {
                $mansioneId = Mansione::where('nome', 'Capogruppo')->first()?->id;
                $ruoloId = Ruolo::where('nome', 'Capo Servizio')->first()?->id;
            } else {
                // Assegna mansione casuale per gli altri
                $mansioneId = $mansioniIds[array_rand($mansioniIds)];
                $ruoloId = $ruoliIds[array_rand($ruoliIds)];
            }

            $militare->update([
                'mansione_id' => $mansioneId,
                'ruolo_id' => $ruoloId
            ]);
        }

        $this->command->info('Assegnati mansioni e ruoli a ' . $militari->count() . ' militari.');
    }
}
