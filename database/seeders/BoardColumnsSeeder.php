<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BoardColumn;

class BoardColumnsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $columns = [
            [
                'name' => 'Servizi Isolati',
                'slug' => 'servizi-isolati',
                'color' => '#6c757d',
                'order' => 1,
                'is_active' => true
            ],
            [
                'name' => 'Esercitazioni',
                'slug' => 'esercitazioni',
                'color' => '#fd7e14',
                'order' => 2,
                'is_active' => true
            ],
            [
                'name' => 'Stand-by',
                'slug' => 'stand-by',
                'color' => '#ffc107',
                'order' => 3,
                'is_active' => true
            ],
            [
                'name' => 'Operazioni',
                'slug' => 'operazioni',
                'color' => '#dc3545',
                'order' => 4,
                'is_active' => true
            ],
            [
                'name' => 'Corsi',
                'slug' => 'corsi',
                'color' => '#0d6efd',
                'order' => 5,
                'is_active' => true
            ],
            [
                'name' => 'Cattedre',
                'slug' => 'cattedre',
                'color' => '#198754',
                'order' => 6,
                'is_active' => true
            ]
        ];

        foreach ($columns as $column) {
            BoardColumn::updateOrCreate(
                ['name' => $column['name']],
                $column
            );
        }

        $this->command->info('Creati ' . count($columns) . ' colonne del board.');
    }
}
