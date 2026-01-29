<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per creare la tabella dei permessi per ruolo su unità organizzative.
 * Questa tabella permette di assegnare permessi specifici a ruoli
 * su specifiche unità della gerarchia, con supporto per l'ereditarietà.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_role_permissions', function (Blueprint $table) {
            $table->id();
            
            // Ruolo a cui si applica il permesso
            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();
            
            // Unità organizzativa su cui si applica
            // Se null, il permesso si applica a tutte le unità
            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('organizational_units')
                ->cascadeOnDelete();
            
            // Permesso specifico
            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->cascadeOnDelete();
            
            // Se true, il permesso si propaga ai nodi figli
            $table->boolean('inherit_to_children')->default(true);
            
            // Tipo di accesso: 'grant' per concedere, 'deny' per negare esplicitamente
            $table->enum('access_type', ['grant', 'deny'])->default('grant');
            
            $table->timestamps();
            
            // Constraint univoco per evitare duplicati
            $table->unique(
                ['role_id', 'unit_id', 'permission_id'],
                'unit_role_perm_unique'
            );
            
            // Indici per query efficienti
            $table->index('role_id', 'idx_unit_role_perm_role');
            $table->index('unit_id', 'idx_unit_role_perm_unit');
            $table->index('permission_id', 'idx_unit_role_perm_permission');
            $table->index('inherit_to_children', 'idx_unit_role_perm_inherit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_role_permissions');
    }
};
