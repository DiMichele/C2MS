<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per creare la Closure Table delle unità organizzative.
 * Questa tabella memorizza tutte le relazioni ancestor-descendant
 * per query gerarchiche efficienti (tutti gli antenati, tutti i discendenti).
 * 
 * Per ogni nodo, contiene:
 * - Una riga per sé stesso (ancestor = descendant, depth = 0)
 * - Una riga per ogni antenato (depth = distanza dall'antenato)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_closure', function (Blueprint $table) {
            $table->id();
            
            // Nodo antenato
            $table->foreignId('ancestor_id')
                ->constrained('organizational_units')
                ->cascadeOnDelete();
            
            // Nodo discendente
            $table->foreignId('descendant_id')
                ->constrained('organizational_units')
                ->cascadeOnDelete();
            
            // Distanza tra ancestor e descendant (0 = stesso nodo)
            $table->tinyInteger('depth')->default(0);
            
            // Constraint univoco per evitare duplicati
            $table->unique(['ancestor_id', 'descendant_id'], 'unit_closure_unique');
            
            // Indici per query efficienti
            $table->index('ancestor_id', 'idx_closure_ancestor');
            $table->index('descendant_id', 'idx_closure_descendant');
            $table->index('depth', 'idx_closure_depth');
            $table->index(['ancestor_id', 'depth'], 'idx_closure_ancestor_depth');
            $table->index(['descendant_id', 'depth'], 'idx_closure_descendant_depth');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_closure');
    }
};
