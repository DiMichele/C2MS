<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per creare la tabella delle assegnazioni alle unità organizzative.
 * Questa tabella implementa una relazione polimorfica per assegnare
 * qualsiasi entità (Militare, User, etc.) a un'unità organizzativa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_assignments', function (Blueprint $table) {
            $table->id();
            
            // Unità organizzativa di assegnazione
            $table->foreignId('unit_id')
                ->constrained('organizational_units')
                ->cascadeOnDelete();
            
            // Relazione polimorfica (es. App\Models\Militare, App\Models\User)
            $table->morphs('assignable');
            
            // Ruolo nell'unità (es. "comandante", "vice_comandante", "membro", "responsabile")
            $table->string('role', 50)->default('membro');
            
            // È l'assegnazione primaria? (un'entità può avere più assegnazioni)
            $table->boolean('is_primary')->default(false);
            
            // Date di validità dell'assegnazione
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            // Note sull'assegnazione
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indici per query efficienti
            $table->index('unit_id', 'idx_assignments_unit');
            $table->index(['assignable_type', 'assignable_id'], 'idx_assignments_assignable');
            $table->index('role', 'idx_assignments_role');
            $table->index('is_primary', 'idx_assignments_primary');
            $table->index(['start_date', 'end_date'], 'idx_assignments_dates');
            
            // Indice composito per trovare rapidamente l'assegnazione primaria di un'entità
            $table->index(
                ['assignable_type', 'assignable_id', 'is_primary'],
                'idx_assignments_primary_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_assignments');
    }
};
