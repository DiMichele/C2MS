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
        Schema::create('notas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('militare_id');
            $table->unsignedBigInteger('user_id');
            $table->text('contenuto')->nullable();
            $table->timestamps();
            
            // Chiavi esterne corrette
            $table->foreign('militare_id')->references('id')->on('militari')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indice unico per evitare note duplicate per lo stesso militare/utente
            $table->unique(['militare_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};
