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
        Schema::table('scadenze_militari', function (Blueprint $table) {
            if (!Schema::hasColumn('scadenze_militari', 'idoneita_to_data_conseguimento')) {
                $table->date('idoneita_to_data_conseguimento')->nullable()->after('idoneita_smi_data_conseguimento');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scadenze_militari', function (Blueprint $table) {
            if (Schema::hasColumn('scadenze_militari', 'idoneita_to_data_conseguimento')) {
                $table->dropColumn('idoneita_to_data_conseguimento');
            }
        });
    }
};
