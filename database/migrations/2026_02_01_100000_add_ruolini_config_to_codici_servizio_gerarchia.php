<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge i campi di configurazione ruolini alla tabella codici_servizio_gerarchia
 * 
 * Questi campi integrano le funzionalità di Gestione Ruolini direttamente
 * nei codici CPT, eliminando la necessità di una pagina separata.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('codici_servizio_gerarchia', function (Blueprint $table) {
            // Esenzione alzabandiera: se true, il militare con questo codice
            // è esonerato dalla cerimonia dell'alzabandiera
            $table->boolean('esenzione_alzabandiera')->default(false)->after('attivo');
            
            // Disponibilità limitata: se true, il militare è disponibile
            // solo per determinate esigenze (non completamente libero)
            $table->boolean('disponibilita_limitata')->default(false)->after('esenzione_alzabandiera');
            
            // Determina presenza nel ruolino: se true, il militare conta come presente
            // Default basato sul tipo di impiego: PRESENTE_SERVIZIO e DISPONIBILE = presente
            $table->boolean('conta_come_presente')->default(false)->after('disponibilita_limitata');
        });

        // Imposta valori di default sensati basati sul tipo di impiego
        DB::table('codici_servizio_gerarchia')
            ->whereIn('impiego', ['PRESENTE_SERVIZIO', 'DISPONIBILE'])
            ->update(['conta_come_presente' => true]);
    }

    public function down(): void
    {
        Schema::table('codici_servizio_gerarchia', function (Blueprint $table) {
            $table->dropColumn(['esenzione_alzabandiera', 'disponibilita_limitata', 'conta_come_presente']);
        });
    }
};
