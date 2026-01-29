<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Aggiunge i permessi .edit mancanti per le pagine che hanno solo .view
     */
    public function up(): void
    {
        $missingEditPermissions = [
            // Dashboard - aggiunta modifica (es. configurazione widget)
            [
                'name' => 'dashboard.edit',
                'display_name' => 'Modifica Dashboard',
                'description' => 'Permette di modificare la configurazione della dashboard',
                'category' => 'dashboard',
                'type' => 'write',
            ],
            // Organigramma
            [
                'name' => 'organigramma.edit',
                'display_name' => 'Modifica Organigramma',
                'description' => 'Permette di modificare l\'organigramma',
                'category' => 'personale',
                'type' => 'write',
            ],
            // Ruolini
            [
                'name' => 'ruolini.edit',
                'display_name' => 'Modifica Ruolini',
                'description' => 'Permette di modificare i ruolini',
                'category' => 'personale',
                'type' => 'write',
            ],
            // Servizi
            [
                'name' => 'servizi.edit',
                'display_name' => 'Modifica Servizi',
                'description' => 'Permette di modificare i servizi',
                'category' => 'servizi',
                'type' => 'write',
            ],
            // Trasparenza
            [
                'name' => 'trasparenza.edit',
                'display_name' => 'Modifica Trasparenza',
                'description' => 'Permette di modificare i dati di trasparenza',
                'category' => 'servizi',
                'type' => 'write',
            ],
            // Disponibilità
            [
                'name' => 'disponibilita.edit',
                'display_name' => 'Modifica Disponibilità',
                'description' => 'Permette di modificare la disponibilità del personale',
                'category' => 'personale',
                'type' => 'write',
            ],
        ];

        foreach ($missingEditPermissions as $permission) {
            $exists = DB::table('permissions')->where('name', $permission['name'])->exists();
            
            if (!$exists) {
                DB::table('permissions')->insert(array_merge($permission, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')->whereIn('name', [
            'dashboard.edit',
            'organigramma.edit',
            'ruolini.edit',
            'servizi.edit',
            'trasparenza.edit',
            'disponibilita.edit',
        ])->delete();
    }
};
