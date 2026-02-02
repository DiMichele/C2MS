<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per creare le tabelle di gestione permessi per unità organizzativa.
 * 
 * Crea due tabelle:
 * 1. role_visible_units - Unità visibili per ogni ruolo (semplice visibilità)
 * 2. role_unit_permissions - Permessi specifici per combinazione ruolo-unità-permesso
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabella per unità visibili per ruolo (semplice visibilità)
        Schema::create('role_visible_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('organizational_unit_id');
            $table->timestamps();
            
            $table->foreign('organizational_unit_id')
                  ->references('id')
                  ->on('organizational_units')
                  ->cascadeOnDelete();
            
            $table->unique(['role_id', 'organizational_unit_id'], 'role_visible_units_unique');
            
            // Indici per performance
            $table->index('role_id');
            $table->index('organizational_unit_id');
        });

        // Tabella per permessi specifici per ruolo-unità
        Schema::create('role_unit_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('organizational_unit_id');
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->enum('access_level', ['view', 'edit', 'admin'])->default('view');
            $table->timestamps();
            
            $table->foreign('organizational_unit_id')
                  ->references('id')
                  ->on('organizational_units')
                  ->cascadeOnDelete();
            
            // Indice univoco per evitare duplicati
            $table->unique(
                ['role_id', 'organizational_unit_id', 'permission_id'],
                'role_unit_permission_unique'
            );
            
            // Indici per performance
            $table->index('role_id');
            $table->index('organizational_unit_id');
            $table->index('permission_id');
            $table->index(['role_id', 'organizational_unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_unit_permissions');
        Schema::dropIfExists('role_visible_units');
    }
};
