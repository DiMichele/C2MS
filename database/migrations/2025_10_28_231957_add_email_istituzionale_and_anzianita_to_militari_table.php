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
        Schema::table('militari', function (Blueprint $table) {
            // Aggiungi email_istituzionale dopo email
            $table->string('email_istituzionale', 255)->nullable()->after('email');
            
            // Aggiungi anzianita (data di anzianità di grado) dopo data_nascita
            $table->date('anzianita')->nullable()->after('data_nascita');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('militari', function (Blueprint $table) {
            $table->dropColumn(['email_istituzionale', 'anzianita']);
        });
    }
};
