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
            // Rinomina la colonna statuti in istituti
            if (Schema::hasColumn('militari', 'statuti')) {
                $table->renameColumn('statuti', 'istituti');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('militari', function (Blueprint $table) {
            if (Schema::hasColumn('militari', 'istituti')) {
                $table->renameColumn('istituti', 'statuti');
            }
        });
    }
};

