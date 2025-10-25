<?php

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
        Schema::create('turni_settimanali', function (Blueprint $table) {
            $table->id();
            $table->date('data_inizio');                                    // Data inizio settimana (giovedì)
            $table->date('data_fine');                                      // Data fine settimana (mercoledì)
            $table->integer('anno');
            $table->integer('numero_settimana');                            // Settimana dell'anno
            $table->enum('stato', ['bozza', 'pubblicato', 'archiviato'])->default('bozza');
            $table->text('note')->nullable();
            $table->timestamps();
            
            $table->index('data_inizio');
            $table->index(['anno', 'numero_settimana']);
            $table->unique(['anno', 'numero_settimana']);                  // Una sola settimana per anno/numero
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turni_settimanali');
    }
};

