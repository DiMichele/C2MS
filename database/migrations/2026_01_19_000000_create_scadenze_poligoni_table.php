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
        if (!Schema::hasTable('scadenze_poligoni')) {
            Schema::create('scadenze_poligoni', function (Blueprint $table) {
                $table->id();
                $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
                $table->foreignId('tipo_poligono_id')->constrained('tipi_poligono')->onDelete('cascade');
                $table->date('data_conseguimento')->nullable();
                $table->timestamps();
                
                $table->unique(['militare_id', 'tipo_poligono_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scadenze_poligoni');
    }
};
