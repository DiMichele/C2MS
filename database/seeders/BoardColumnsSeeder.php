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
                'name' => 'Da Fare',
                'slug' => 'todo',
                'color' => '#6c757d',
                'order' => 1,
                'is_active' => true
            ],
            [
                'name' => 'In Corso',
                'slug' => 'progress',
                'color' => '#0d6efd',
                'order' => 2,
                'is_active' => true
            ],
            [
                'name' => 'In Revisione',
                'slug' => 'review',
                'color' => '#ffc107',
                'order' => 3,
                'is_active' => true
            ],
            [
                'name' => 'Completato',
                'slug' => 'done',
                'color' => '#198754',
                'order' => 4,
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
