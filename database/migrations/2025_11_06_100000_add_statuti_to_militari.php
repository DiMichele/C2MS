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
            // Aggiungo colonna JSON per statuti multipli
            $table->json('statuti')->nullable()->after('note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('militari', function (Blueprint $table) {
            $table->dropColumn('statuti');
        });
    }
};

