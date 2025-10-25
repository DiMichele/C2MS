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
        Schema::create('scadenze_militari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            
            // Certificati con durata 1 anno
            $table->date('pefo_data_conseguimento')->nullable();
            $table->date('idoneita_mans_data_conseguimento')->nullable();
            $table->date('idoneita_smi_data_conseguimento')->nullable();
            
            // Certificati lavoratori con durata 5 anni
            $table->date('lavoratore_4h_data_conseguimento')->nullable();
            $table->date('lavoratore_8h_data_conseguimento')->nullable();
            
            // Corsi con durata 2 anni
            $table->date('preposto_data_conseguimento')->nullable();
            $table->date('dirigenti_data_conseguimento')->nullable();
            
            // Poligoni con durata 6 mesi
            $table->date('poligono_approntamento_data_conseguimento')->nullable();
            $table->date('poligono_mantenimento_data_conseguimento')->nullable();
            
            $table->timestamps();
            
            // Index per ricerche veloci
            $table->index('militare_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scadenze_militari');
    }
};

