<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge organizational_unit_id alla tabella users.
 * 
 * Questa colonna permette di associare direttamente un utente alla sua
 * unità organizzativa primaria, facilitando il filtro dei dati e 
 * l'identificazione della "home unit" dell'utente.
 * 
 * L'utente può comunque essere assegnato a più unità tramite
 * la tabella unit_assignments (relazione polimorfica).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organizational_unit_id')
                ->nullable()
                ->after('compagnia_id')
                ->constrained('organizational_units')
                ->nullOnDelete();
            
            $table->index('organizational_unit_id', 'users_org_unit_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organizational_unit_id']);
            $table->dropIndex('users_org_unit_idx');
            $table->dropColumn('organizational_unit_id');
        });
    }
};
