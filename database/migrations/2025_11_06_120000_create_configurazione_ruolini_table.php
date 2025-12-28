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
        Schema::create('configurazione_ruolini', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_servizio_id')->constrained('tipi_servizio')->onDelete('cascade');
            $table->enum('stato_presenza', ['presente', 'assente', 'default'])->default('default')
                  ->comment('presente=conta come presente, assente=conta come assente, default=usa logica standard');
            $table->text('note')->nullable();
            $table->timestamps();
            
            // Indice unico per tipo_servizio_id
            $table->unique('tipo_servizio_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurazione_ruolini');
    }
};

