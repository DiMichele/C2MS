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
        Schema::create('militare_valutazioni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->foreignId('valutatore_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('precisione_lavoro')->unsigned()->default(0)->comment('Punteggio 1-5 per precisione nel lavoro');
            $table->tinyInteger('affidabilita')->unsigned()->default(0)->comment('Punteggio 1-5 per affidabilità');
            $table->tinyInteger('capacita_tecnica')->unsigned()->default(0)->comment('Punteggio 1-5 per capacità tecnica');
            $table->tinyInteger('collaborazione')->unsigned()->default(0)->comment('Punteggio 1-5 per collaborazione');
            $table->tinyInteger('iniziativa')->unsigned()->default(0)->comment('Punteggio 1-5 per iniziativa');
            $table->text('note')->nullable()->comment('Note aggiuntive sulla valutazione');
            $table->timestamps();
            
            // Indice unico per evitare valutazioni duplicate dallo stesso valutatore
            $table->unique(['militare_id', 'valutatore_id'], 'unique_militare_valutatore');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('militare_valutazioni');
    }
};
