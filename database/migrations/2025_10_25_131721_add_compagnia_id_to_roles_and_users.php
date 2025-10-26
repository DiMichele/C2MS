<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('compagnia_id')->nullable()->after('name')->constrained('compagnie')->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('compagnia_id')->nullable()->after('email')->constrained('compagnie')->onDelete('set null');
            $table->string('role_type')->nullable()->after('compagnia_id'); // admin, rssp, comandante, ufficio_compagnia
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['compagnia_id']);
            $table->dropColumn(['compagnia_id', 'role_type']);
        });
        
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['compagnia_id']);
            $table->dropColumn('compagnia_id');
        });
    }
};
