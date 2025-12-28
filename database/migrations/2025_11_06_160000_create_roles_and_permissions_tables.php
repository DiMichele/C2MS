<?php

/**
|--------------------------------------------------------------------------
| SUGECO - Migration: Sistema Ruoli e Permessi
|--------------------------------------------------------------------------
| 
| Crea le tabelle necessarie per il sistema di gestione ruoli e permessi:
| - roles: Ruoli del sistema (es. amministratore, comandante, utente)
| - permissions: Permessi specifici (es. anagrafica.view, anagrafica.edit)
| - role_user: Pivot table per associare utenti a ruoli
| - permission_role: Pivot table per associare permessi a ruoli
| 
| @package SUGECO
| @author Michele Di Gennaro
| @version 1.0
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabella Ruoli
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nome del ruolo (es. amministratore)');
            $table->string('display_name')->comment('Nome visualizzato');
            $table->text('description')->nullable()->comment('Descrizione del ruolo');
            $table->unsignedBigInteger('compagnia_id')->nullable()->comment('Compagnia associata (null = tutti)');
            $table->timestamps();
            
            $table->foreign('compagnia_id')->references('id')->on('compagnie')->onDelete('set null');
        });

        // Tabella Permessi
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nome del permesso (es. anagrafica.view)');
            $table->string('display_name')->comment('Nome visualizzato');
            $table->text('description')->nullable()->comment('Descrizione del permesso');
            $table->string('category')->nullable()->comment('Categoria del permesso');
            $table->string('type')->default('read')->comment('Tipo: read, write, delete');
            $table->timestamps();
        });

        // Pivot: Utenti-Ruoli
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->unique(['role_id', 'user_id']);
        });

        // Pivot: Permessi-Ruoli
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            
            $table->unique(['permission_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};

