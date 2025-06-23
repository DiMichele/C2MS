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
        Schema::table('militare_valutazioni', function (Blueprint $table) {
            // Rimuovi il campo note generico se esiste
            $table->dropColumn('note');
            
            // Aggiungi i nuovi campi specifici
            $table->text('note_positive')->nullable()->comment('Note positive sulla valutazione');
            $table->text('note_negative')->nullable()->comment('Note negative o aree di miglioramento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('militare_valutazioni', function (Blueprint $table) {
            // Rimuovi i nuovi campi
            $table->dropColumn(['note_positive', 'note_negative']);
            
            // Ripristina il campo note generico
            $table->text('note')->nullable()->comment('Note aggiuntive sulla valutazione');
        });
    }
};
