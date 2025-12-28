<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migrazione per unificare i poli (uffici)
 * 
 * I poli sono entità uniche condivise tra le compagnie.
 * Questa migrazione:
 * 1. Identifica i poli unici per nome
 * 2. Aggiorna i militari per usare i poli unificati
 * 3. Elimina i poli duplicati
 * 4. Rimuove il campo compagnia_id dalla tabella poli
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Ottieni tutti i poli raggruppati per nome
        $poliPerNome = DB::table('poli')
            ->select('nome', DB::raw('MIN(id) as id_principale'), DB::raw('GROUP_CONCAT(id) as tutti_ids'))
            ->groupBy('nome')
            ->get();
        
        // 2. Per ogni gruppo di poli con lo stesso nome, aggiorna i militari
        foreach ($poliPerNome as $gruppo) {
            $idPrincipale = $gruppo->id_principale;
            $tuttiIds = explode(',', $gruppo->tutti_ids);
            
            // Aggiorna i militari che usano poli duplicati
            foreach ($tuttiIds as $poloId) {
                if ($poloId != $idPrincipale) {
                    DB::table('militari')
                        ->where('polo_id', $poloId)
                        ->update(['polo_id' => $idPrincipale]);
                }
            }
        }
        
        // 3. Elimina i poli duplicati (mantieni solo il primo per ogni nome)
        $poliDaMantenere = DB::table('poli')
            ->select(DB::raw('MIN(id) as id'))
            ->groupBy('nome')
            ->pluck('id')
            ->toArray();
        
        DB::table('poli')
            ->whereNotIn('id', $poliDaMantenere)
            ->delete();
        
        // 4. Rimuovi il campo compagnia_id dalla tabella poli
        Schema::table('poli', function (Blueprint $table) {
            // Rimuovi la foreign key se esiste
            try {
                $table->dropForeign(['compagnia_id']);
            } catch (\Exception $e) {
                // FK potrebbe non esistere
            }
            
            // Rimuovi la colonna
            if (Schema::hasColumn('poli', 'compagnia_id')) {
                $table->dropColumn('compagnia_id');
            }
        });
    }

    public function down(): void
    {
        // Ricrea il campo compagnia_id
        Schema::table('poli', function (Blueprint $table) {
            $table->unsignedBigInteger('compagnia_id')->nullable()->after('nome');
        });
        
        // NOTA: I dati dei poli duplicati non possono essere ripristinati
    }
};
