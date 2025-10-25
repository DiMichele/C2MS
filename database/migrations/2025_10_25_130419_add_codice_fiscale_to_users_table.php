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
        Schema::table('users', function (Blueprint $table) {
            $table->string('codice_fiscale', 16)->unique()->nullable()->after('email');
            $table->boolean('must_change_password')->default(true)->after('password');
            $table->timestamp('last_password_change')->nullable()->after('must_change_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['codice_fiscale', 'must_change_password', 'last_password_change']);
        });
    }
};
