<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Pulisce il campo compagnia nella tabella militari
     * rimuovendo testi aggiuntivi e lasciando solo il numero
     */
    public function up(): void
    {
        // Pulisci il campo 'nome' nella tabella compagnie
        // Rimuovi testo extra e lascia solo numeri (es. "124 Compagniaa" -> "124")
        DB::statement("UPDATE compagnie SET nome = REGEXP_REPLACE(nome, '[^0-9]', '') WHERE nome IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non è possibile ripristinare i valori originali
    }
};
