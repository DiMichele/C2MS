<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Aggiunge foreign keys mancanti per migliorare l'integrità referenziale.
     * Le colonne string esistenti vengono mantenute per retrocompatibilità.
     */
    public function up(): void
    {
        // 1. Aggiungi teatro_operativo_id a scadenze_approntamenti
        if (Schema::hasTable('scadenze_approntamenti') && !Schema::hasColumn('scadenze_approntamenti', 'teatro_operativo_id')) {
            Schema::table('scadenze_approntamenti', function (Blueprint $table) {
                $table->unsignedBigInteger('teatro_operativo_id')->nullable()->after('militare_id');
                
                $table->foreign('teatro_operativo_id')
                      ->references('id')
                      ->on('teatri_operativi')
                      ->nullOnDelete();
                
                $table->index('teatro_operativo_id');
            });

            // Tenta di migrare i dati esistenti dalla colonna string
            $this->migraTeatroOperativo();
        }

        // 2. Aggiungi ruolo_id a militare_approntamenti (il campo ruolo è string)
        if (Schema::hasTable('militare_approntamenti') && !Schema::hasColumn('militare_approntamenti', 'ruolo_id')) {
            Schema::table('militare_approntamenti', function (Blueprint $table) {
                $table->unsignedBigInteger('ruolo_id')->nullable()->after('approntamento_id');
                
                $table->foreign('ruolo_id')
                      ->references('id')
                      ->on('ruoli')
                      ->nullOnDelete();
                
                $table->index('ruolo_id');
            });
        }

        // 3. Aggiungi ruolo_id a teatro_operativo_militare
        if (Schema::hasTable('teatro_operativo_militare') && !Schema::hasColumn('teatro_operativo_militare', 'ruolo_id')) {
            Schema::table('teatro_operativo_militare', function (Blueprint $table) {
                $table->unsignedBigInteger('ruolo_id')->nullable()->after('teatro_operativo_id');
                
                $table->foreign('ruolo_id')
                      ->references('id')
                      ->on('ruoli')
                      ->nullOnDelete();
                
                $table->index('ruolo_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('scadenze_approntamenti') && Schema::hasColumn('scadenze_approntamenti', 'teatro_operativo_id')) {
            Schema::table('scadenze_approntamenti', function (Blueprint $table) {
                $table->dropForeign(['teatro_operativo_id']);
                $table->dropIndex(['teatro_operativo_id']);
                $table->dropColumn('teatro_operativo_id');
            });
        }

        if (Schema::hasTable('militare_approntamenti') && Schema::hasColumn('militare_approntamenti', 'ruolo_id')) {
            Schema::table('militare_approntamenti', function (Blueprint $table) {
                $table->dropForeign(['ruolo_id']);
                $table->dropIndex(['ruolo_id']);
                $table->dropColumn('ruolo_id');
            });
        }

        if (Schema::hasTable('teatro_operativo_militare') && Schema::hasColumn('teatro_operativo_militare', 'ruolo_id')) {
            Schema::table('teatro_operativo_militare', function (Blueprint $table) {
                $table->dropForeign(['ruolo_id']);
                $table->dropIndex(['ruolo_id']);
                $table->dropColumn('ruolo_id');
            });
        }
    }

    /**
     * Migra i dati dalla colonna string teatro_operativo alla FK
     */
    private function migraTeatroOperativo(): void
    {
        try {
            // Ottieni tutti i teatri operativi
            $teatri = DB::table('teatri_operativi')->pluck('id', 'nome')->toArray();
            
            if (empty($teatri)) {
                return;
            }

            // Aggiorna le scadenze con il teatro_operativo_id corretto
            foreach ($teatri as $nome => $id) {
                DB::table('scadenze_approntamenti')
                    ->where('teatro_operativo', $nome)
                    ->whereNull('teatro_operativo_id')
                    ->update(['teatro_operativo_id' => $id]);
            }

        } catch (\Exception $e) {
            // Se fallisce la migrazione dati, continua comunque
            // I dati verranno migrati manualmente se necessario
            \Log::warning('Migrazione teatro_operativo fallita: ' . $e->getMessage());
        }
    }
};
