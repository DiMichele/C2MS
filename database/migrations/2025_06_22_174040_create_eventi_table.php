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
        Schema::create('eventi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->string('tipologia', 100);
            $table->string('nome', 255);
            $table->date('data_inizio');
            $table->date('data_fine');
            $table->string('localita', 255)->nullable();
            $table->text('note')->nullable();
            $table->enum('stato', ['programmato', 'in_corso', 'completato', 'annullato'])->default('programmato');
            $table->timestamps();
            
            // Indici per performance
            $table->index(['militare_id', 'data_inizio', 'data_fine']);
            $table->index(['tipologia']);
            $table->index(['stato']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eventi');
    }
};
