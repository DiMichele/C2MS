<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compagnia_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compagnia_id')
                  ->constrained('compagnie')
                  ->onDelete('cascade')
                  ->unique();
            
            $table->json('ruolini_config')->nullable();
            $table->json('general_config')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('compagnia_id', 'idx_compagnia_settings_compagnia');
        });
        
        $compagnie = \DB::table('compagnie')->pluck('id');
        foreach ($compagnie as $compagniaId) {
            \DB::table('compagnia_settings')->insert([
                'compagnia_id' => $compagniaId,
                'ruolini_config' => json_encode([
                    'assenza_servizi_ids' => [],
                    'presenza_servizi_ids' => [],
                    'default_stato' => 'presente'
                ]),
                'general_config' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compagnia_settings');
    }
};
