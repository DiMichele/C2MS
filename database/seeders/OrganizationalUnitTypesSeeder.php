<?php

namespace Database\Seeders;

use App\Models\OrganizationalUnitType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder per i tipi di unità organizzative.
 * 
 * Crea i tipi predefiniti per la struttura gerarchica:
 * - Reggimento (root)
 * - Battaglione
 * - Compagnia
 * - Plotone
 * - Ufficio
 * - Sezione
 * - Infermeria
 */
class OrganizationalUnitTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => 'reggimento',
                'name' => 'Reggimento',
                'description' => 'Unità di comando principale, nodo root della gerarchia.',
                'icon' => 'fa-landmark',
                'color' => '#0A1E38',
                'default_depth_level' => 0,
                'can_contain_types' => ['battaglione', 'ufficio', 'sezione', 'infermeria', 'ccsl'],
                'settings' => [
                    'is_command_unit' => true,
                    'requires_commander' => true,
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'battaglione',
                'name' => 'Battaglione',
                'description' => 'Unità tattica intermedia composta da più compagnie.',
                'icon' => 'fa-shield-alt',
                'color' => '#1A3A5F',
                'default_depth_level' => 1,
                'can_contain_types' => ['compagnia', 'ufficio', 'sezione'],
                'settings' => [
                    'is_command_unit' => true,
                    'requires_commander' => true,
                ],
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'compagnia',
                'name' => 'Compagnia',
                'description' => 'Unità operativa base composta da plotoni.',
                'icon' => 'fa-users-cog',
                'color' => '#2E5A8F',
                'default_depth_level' => 2,
                'can_contain_types' => ['plotone', 'ufficio'],
                'settings' => [
                    'is_command_unit' => true,
                    'requires_commander' => true,
                    'has_ruolini' => true,
                ],
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'plotone',
                'name' => 'Plotone',
                'description' => 'Unità operativa minore sotto il comando di una compagnia.',
                'icon' => 'fa-users',
                'color' => '#4A7AB0',
                'default_depth_level' => 3,
                'can_contain_types' => ['ufficio', 'squadra'],
                'settings' => [
                    'is_operational_unit' => true,
                ],
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'code' => 'ufficio',
                'name' => 'Ufficio',
                'description' => 'Unità amministrativa o di supporto.',
                'icon' => 'fa-building',
                'color' => '#6B8CBF',
                'default_depth_level' => 2,
                'can_contain_types' => ['sezione'],
                'settings' => [
                    'is_administrative_unit' => true,
                ],
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'sezione',
                'name' => 'Sezione',
                'description' => 'Sottounità specializzata o di supporto.',
                'icon' => 'fa-sitemap',
                'color' => '#8BA4CC',
                'default_depth_level' => 3,
                'can_contain_types' => [],
                'settings' => [
                    'is_support_unit' => true,
                ],
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'code' => 'infermeria',
                'name' => 'Infermeria',
                'description' => 'Servizio medico sanitario.',
                'icon' => 'fa-medkit',
                'color' => '#C62828',
                'default_depth_level' => 1,
                'can_contain_types' => ['sezione'],
                'settings' => [
                    'is_medical_unit' => true,
                ],
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'code' => 'ccsl',
                'name' => 'CCSL',
                'description' => 'Centro Comando e Supporto Logistico.',
                'icon' => 'fa-warehouse',
                'color' => '#FF8F00',
                'default_depth_level' => 1,
                'can_contain_types' => ['ufficio', 'sezione'],
                'settings' => [
                    'is_support_unit' => true,
                    'is_logistics' => true,
                ],
                'sort_order' => 8,
                'is_active' => true,
            ],
            [
                'code' => 'squadra',
                'name' => 'Squadra',
                'description' => 'Unità operativa minima.',
                'icon' => 'fa-user-friends',
                'color' => '#9EAFD1',
                'default_depth_level' => 4,
                'can_contain_types' => [],
                'settings' => [
                    'is_operational_unit' => true,
                    'is_leaf_unit' => true,
                ],
                'sort_order' => 9,
                'is_active' => true,
            ],
        ];

        foreach ($types as $typeData) {
            OrganizationalUnitType::updateOrCreate(
                ['code' => $typeData['code']],
                $typeData
            );
        }

        $this->command->info('✓ Tipi di unità organizzative creati/aggiornati: ' . count($types));
    }
}
