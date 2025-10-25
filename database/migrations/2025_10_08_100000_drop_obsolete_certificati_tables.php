<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Rimuove le tabelle obsolete per certificati e idoneità.
     * Queste funzionalità sono state consolidate nella nuova tabella scadenze_militari.
     */
    public function up(): void
    {
        // Elimina le tabelle obsolete
        Schema::dropIfExists('idoneita');
        Schema::dropIfExists('certificati_lavoratori');
        Schema::dropIfExists('certificati');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ricrea le tabelle in caso di rollback
        // Certificati
        Schema::create('certificati', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->string('tipo', 100);
            $table->date('data_ottenimento');
            $table->date('data_scadenza');
            $table->string('file_path')->nullable();
            $table->string('durata')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Certificati Lavoratori
        Schema::create('certificati_lavoratori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->string('tipo', 100);
            $table->date('data_ottenimento');
            $table->date('data_scadenza');
            $table->string('file_path')->nullable();
            $table->text('note')->nullable();
            $table->boolean('in_scadenza')->default(false);
            $table->timestamps();
            
            $table->index(['militare_id', 'tipo', 'data_scadenza']);
        });

        // Idoneità
        Schema::create('idoneita', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->string('tipo', 100);
            $table->date('data_ottenimento');
            $table->date('data_scadenza');
            $table->string('file_path')->nullable();
            $table->text('note')->nullable();
            $table->boolean('in_scadenza')->default(false);
            $table->timestamps();
            
            $table->index(['militare_id', 'tipo', 'data_scadenza']);
        });
    }
};

