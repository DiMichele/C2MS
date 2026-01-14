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
        Schema::create('scadenze_approntamenti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            
            // Ogni campo può essere una data o 'NR' (Non Richiesto)
            // Usiamo string nullable per gestire entrambi i casi
            $table->string('teatro_operativo', 20)->nullable();
            $table->string('bls', 20)->nullable();
            $table->string('ultimo_poligono_approntamento', 20)->nullable();
            $table->string('poligono', 20)->nullable();
            $table->string('tipo_poligono_da_effettuare', 50)->nullable();
            $table->string('bam', 20)->nullable();
            $table->string('awareness_cied', 20)->nullable();
            $table->string('cied_pratico', 20)->nullable();
            $table->string('stress_management', 20)->nullable();
            $table->string('elitrasporto', 20)->nullable();
            $table->string('mcm', 20)->nullable();
            $table->string('uas', 20)->nullable();
            $table->string('ict', 20)->nullable();
            $table->string('rapporto_media', 20)->nullable();
            $table->string('abuso_alcol_droga', 20)->nullable();
            $table->string('training_covid', 20)->nullable();
            $table->string('rspp_4h', 20)->nullable();       // 5 anni
            $table->string('rspp_8h', 20)->nullable();       // 5 anni
            $table->string('rspp_preposti', 20)->nullable(); // 2 anni
            $table->string('passaporti', 20)->nullable();
            
            $table->timestamps();
            
            // Indice per ricerca veloce
            $table->unique('militare_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scadenze_approntamenti');
    }
};
