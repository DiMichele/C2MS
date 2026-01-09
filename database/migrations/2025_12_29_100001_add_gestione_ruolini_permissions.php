<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Aggiunge i permessi per la gestione ruolini per compagnia.
     */
    public function up(): void
    {
        $permissions = [
            [
                'name' => 'gestione_ruolini.view',
                'display_name' => 'Visualizza Gestione Ruolini',
                'description' => 'Permette di visualizzare le configurazioni ruolini della propria compagnia',
            ],
            [
                'name' => 'gestione_ruolini.edit',
                'display_name' => 'Modifica Gestione Ruolini',
                'description' => 'Permette di modificare le configurazioni ruolini della propria compagnia (quali servizi = presenza/assenza)',
            ],
        ];

        foreach ($permissions as $permission) {
            $exists = DB::table('permissions')
                ->where('name', $permission['name'])
                ->exists();

            if (!$exists) {
                DB::table('permissions')->insert([
                    'name' => $permission['name'],
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('name', ['gestione_ruolini.view', 'gestione_ruolini.edit'])
            ->delete();
    }
};

