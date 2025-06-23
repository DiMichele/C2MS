<?php

namespace Database\Seeders;

use App\Models\BoardColumn;
use Illuminate\Database\Seeder;

class BoardColumnSeeder extends Seeder
{
    public function run()
    {
        // Se ci sono già colonne, non ne creiamo altre
        if (BoardColumn::count() > 0) {
            $this->command->info('Le colonne della board esistono già. Salto la creazione.');
            return;
        }
        
        $columns = [
            ['name' => 'In Scadenza', 'slug' => 'in-scadenza', 'order' => 1],
            ['name' => 'Pianificate', 'slug' => 'pianificate', 'order' => 2],
            ['name' => 'Fuori Porta', 'slug' => 'fuori-porta', 'order' => 3],
            ['name' => 'Urgenti', 'slug' => 'urgenti', 'order' => 4],
        ];

        foreach ($columns as $column) {
            BoardColumn::create($column);
        }
    }
} 
