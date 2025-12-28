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
            // Aggiungi campi per il calcolo del codice fiscale
            if (!Schema::hasColumn('militari', 'sesso')) {
                $table->enum('sesso', ['M', 'F'])->nullable()->after('data_nascita');
            }
            if (!Schema::hasColumn('militari', 'luogo_nascita')) {
                $table->string('luogo_nascita', 100)->nullable()->after('sesso');
            }
            if (!Schema::hasColumn('militari', 'provincia_nascita')) {
                $table->string('provincia_nascita', 2)->nullable()->after('luogo_nascita');
            }
            if (!Schema::hasColumn('militari', 'codice_comune')) {
                $table->string('codice_comune', 4)->nullable()->after('provincia_nascita');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('militari', function (Blueprint $table) {
            $table->dropColumn(['sesso', 'luogo_nascita', 'provincia_nascita', 'codice_comune']);
        });
    }
};

