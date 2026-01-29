<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per creare la tabella delle unità organizzative.
 * Questa tabella implementa l'albero gerarchico usando il pattern Adjacency List
 * con Materialized Path per query efficienti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizational_units', function (Blueprint $table) {
            $table->id();
            
            // UUID per identificazione pubblica (API, URL)
            $table->uuid('uuid')->unique();
            
            // Tipo di unità
            $table->foreignId('type_id')
                ->constrained('organizational_unit_types')
                ->restrictOnDelete();
            
            // Parent per Adjacency List (null = root)
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('organizational_units')
                ->nullOnDelete();
            
            // Nome dell'unità
            $table->string('name', 150);
            
            // Codice univoco opzionale (es. "1CPT", "LEONESSA")
            $table->string('code', 50)->nullable();
            
            // Descrizione
            $table->text('description')->nullable();
            
            // Materialized Path (es. "1.5.12.34")
            // Contiene la catena di ID dalla root a questo nodo
            $table->string('path', 500)->default('');
            
            // Profondità nella gerarchia (0 = root)
            $table->tinyInteger('depth')->default(0);
            
            // Ordine tra siblings (figli dello stesso parent)
            $table->integer('sort_order')->default(0);
            
            // Configurazioni specifiche dell'unità (JSON)
            // Es: {"responsabile_id": 123, "email": "...", "telefono": "..."}
            $table->json('settings')->nullable();
            
            // Riferimento alla tabella legacy compagnie (per migrazione)
            $table->foreignId('legacy_compagnia_id')
                ->nullable()
                ->constrained('compagnie')
                ->nullOnDelete();
            
            // Riferimento alla tabella legacy plotoni (per migrazione)
            $table->unsignedBigInteger('legacy_plotone_id')->nullable();
            
            // Riferimento alla tabella legacy poli (per migrazione)
            $table->unsignedBigInteger('legacy_polo_id')->nullable();
            
            // Flag attivo/inattivo
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indici per performance
            $table->index('parent_id', 'idx_org_units_parent');
            $table->index('type_id', 'idx_org_units_type');
            $table->index('path', 'idx_org_units_path');
            $table->index('depth', 'idx_org_units_depth');
            $table->index('is_active', 'idx_org_units_active');
            $table->index('legacy_compagnia_id', 'idx_org_units_legacy_compagnia');
            $table->index('legacy_plotone_id', 'idx_org_units_legacy_plotone');
            $table->index(['parent_id', 'sort_order'], 'idx_org_units_parent_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizational_units');
    }
};
