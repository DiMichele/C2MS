<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per creare la tabella dei tipi di unità organizzative.
 * Questa tabella definisce i tipi di nodi che possono esistere nella gerarchia
 * (es. reggimento, battaglione, compagnia, plotone, ufficio, sezione).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizational_unit_types', function (Blueprint $table) {
            $table->id();
            
            // Codice univoco del tipo (es. "reggimento", "battaglione", "compagnia")
            $table->string('code', 50)->unique();
            
            // Nome visualizzato
            $table->string('name', 100);
            
            // Descrizione del tipo
            $table->text('description')->nullable();
            
            // Icona Font Awesome (es. "fa-building", "fa-users")
            $table->string('icon', 50)->default('fa-building');
            
            // Colore per la visualizzazione (hex o nome CSS)
            $table->string('color', 30)->default('#0A2342');
            
            // Livello di profondità suggerito nella gerarchia (0 = root)
            $table->tinyInteger('default_depth_level')->default(0);
            
            // Array JSON di codici tipo che questo nodo può contenere
            // Es: ["battaglione", "ufficio", "sezione"] per reggimento
            // Se null, può contenere qualsiasi tipo
            $table->json('can_contain_types')->nullable();
            
            // Impostazioni aggiuntive specifiche del tipo (JSON)
            $table->json('settings')->nullable();
            
            // Ordine di visualizzazione nel menu/dropdown
            $table->integer('sort_order')->default(0);
            
            // Flag attivo/inattivo
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indici
            $table->index('code');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizational_unit_types');
    }
};
