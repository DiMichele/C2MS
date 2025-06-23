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
        Schema::create('activity_militare', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('board_activities')->onDelete('cascade');
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->string('role')->nullable(); // ruolo del militare nell'attività
            $table->text('notes')->nullable(); // note specifiche per questo militare in questa attività
            $table->timestamps();
            
            // Indice unico per evitare duplicati
            $table->unique(['activity_id', 'militare_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_militare');
    }
};
