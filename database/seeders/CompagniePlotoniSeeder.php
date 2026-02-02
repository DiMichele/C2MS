<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Compagnia;
use App\Models\Plotone;
use App\Models\Polo;
use App\Models\OrganizationalUnit;

class CompagniePlotoniSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Controlla se esistono già dati
        if (Compagnia::count() > 0) {
            $this->command->info('⚠️  Compagnie già presenti nel database. Saltando creazione.');
            return;
        }

        // Crea la 124^ Compagnia
        $compagnia = Compagnia::create([
            'nome' => '124^ Compagnia Trasmissioni'
        ]);

        // Ottieni l'unità organizzativa corrispondente (o la prima disponibile)
        $orgUnit = OrganizationalUnit::where('legacy_compagnia_id', $compagnia->id)->first()
            ?? OrganizationalUnit::where('depth', 1)->where('is_active', true)->first()
            ?? OrganizationalUnit::where('is_active', true)->first();
        
        $orgUnitId = $orgUnit?->id;

        // Crea i plotoni
        $plotoni = [
            '1° Plotone Operativo',
            '2° Plotone Supporto',
            '3° Plotone Tecnico',
            '4° Plotone Logistico'
        ];

        $plotoniCreati = [];
        foreach ($plotoni as $nomePlotone) {
            $plotoniCreati[] = Plotone::withoutGlobalScopes()->create([
                'nome' => $nomePlotone,
                'compagnia_id' => $compagnia->id,
                'organizational_unit_id' => $orgUnitId,
            ]);
        }

        // Crea i poli specialistici
        $poli = [
            'Polo Comando',
            'Polo Informatico',
            'Polo Radio',
            'Polo Satellitare',
            'Polo MGE',
            'Polo Logistico',
            'Polo Sanitario',
            'Polo Armeria',
            'Polo Fureria',
            'Polo SIGE'
        ];

        foreach ($poli as $nomePolo) {
            Polo::withoutGlobalScopes()->create([
                'nome' => $nomePolo,
                'compagnia_id' => $compagnia->id,
                'organizational_unit_id' => $orgUnitId,
            ]);
        }

        $this->command->info('✅ Compagnia, plotoni e poli creati con successo!');
        $this->command->info('   - 1 Compagnia: 124^ Compagnia Trasmissioni');
        $this->command->info('   - 4 Plotoni operativi');
        $this->command->info('   - 10 Poli specialistici');
        if ($orgUnit) {
            $this->command->info('   - Associati a unità organizzativa: ' . $orgUnit->name);
        }
    }
}
