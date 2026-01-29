<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('config_colonne_approntamenti', function (Blueprint $table) {
            $table->id();
            $table->string('campo_db', 50)->unique()->comment('Nome del campo nel database scadenze_approntamenti');
            $table->string('label', 100)->comment('Nome visualizzato nella UI');
            $table->integer('scadenza_mesi')->nullable()->comment('Durata in mesi (null = nessuna scadenza)');
            $table->string('fonte', 50)->default('approntamenti')->comment('Fonte dati: approntamenti o scadenze_militari');
            $table->string('campo_sorgente', 50)->nullable()->comment('Campo sorgente se fonte diversa');
            $table->boolean('attivo')->default(true)->comment('Se la colonna è visibile');
            $table->integer('ordine')->default(0)->comment('Ordine di visualizzazione');
            $table->timestamps();
        });

        // Popola con le colonne predefinite esistenti
        $colonneDefault = [
            // Colonne condivise con SPP (Corsi di Formazione)
            ['campo_db' => 'idoneita_to', 'label' => 'Idoneità T.O.', 'scadenza_mesi' => null, 'fonte' => 'scadenze_militari', 'campo_sorgente' => 'idoneita_to', 'ordine' => 1],
            ['campo_db' => 'bls', 'label' => 'BLS', 'scadenza_mesi' => 24, 'fonte' => 'scadenze_militari', 'campo_sorgente' => 'blsd', 'ordine' => 2],
            ['campo_db' => 'ultimo_poligono_approntamento', 'label' => 'Ultimo Poligono Approntamento', 'scadenza_mesi' => null, 'fonte' => 'approntamenti', 'campo_sorgente' => null, 'ordine' => 3],
            ['campo_db' => 'poligono', 'label' => 'POLIGONO', 'scadenza_mesi' => null, 'fonte' => 'approntamenti', 'campo_sorgente' => null, 'ordine' => 4],
            ['campo_db' => 'tipo_poligono_da_effettuare', 'label' => 'Tipo Poligono da Effettuare', 'scadenza_mesi' => null, 'fonte' => 'approntamenti', 'campo_sorgente' => null, 'ordine' => 5],
            ['campo_db' => 'bam', 'label' => 'B.A.M.', 'scadenza_mesi' => null, 'fonte' => 'approntamenti', 'campo_sorgente' => null, 'ordine' => 6],
            ['campo_db' => 'awareness_cied', 'label' => 'AWARENESS C-IED', 'scadenza_mesi' => null, 'fonte' => 'approntamenti', 'campo_sorgente' => null, 'ordine' => 7],
            ['campo_db' => 'cied_pratico', 'label' => 'C-IED PRATICO', 'scadenza_mesi' => null, 'fonte' => 'approntamenti', 'campo_sorgente' => null, 'ordine' => 8],
            ['campo_db' => 'stress_management', 'label' => 'STRESS MANAGEMENT', 'scadenza_mesi' => null, 'fonte' => 'approntamenti', 'campo_sorgente' => null, 'ordine' => 9],
            ['campo_db' => 'elitrasporto', 'label' => 'ELITRASPORTO', 'scadenza_mesi' => null, 'fonte' => 'approntamenti', 'campo_sorgente' => null, 'ordine' => 10],
            ['campo_db' => 'mcm', 'label' => 'MCM', 'scadenza_mesi' => null, 'fonte' => 'approntamenti', 'campo_sorgente' => null, 'ordine' => 11],
            ['campo_db' => 'uas', 'label' => 'UAS', 'scadenza_mesi' => null, 'fonte' => 'approntamenti', 'campo_sorgente' => null, 'ordine' => 12],
            ['campo_db' => 'ict', 'label' => 'ICT', 'scadenza_mesi' => null, 'fonte' => 'approntamenti', 'campo_sorgente' => null, 'ordine' => 13],
            ['campo_db' => 'rapporto_media', 'label' => 'RAPPORTO CON I MEDIA', 'scadenza_mesi' => null, 'fonte' => 'approntamenti', 'campo_sorgente' => null, 'ordine' => 14],
            ['campo_db' => 'abuso_alcol_droga', 'label' => 'ABUSO ALCOL e DROGA', 'scadenza_mesi' => null, 'fonte' => 'approntamenti', 'campo_sorgente' => null, 'ordine' => 15],
            ['campo_db' => 'training_covid', 'label' => 'TRAINING ON COVID (A cura DSS)', 'scadenza_mesi' => null, 'fonte' => 'approntamenti', 'campo_sorgente' => null, 'ordine' => 16],
            // Colonne condivise con SPP
            ['campo_db' => 'lavoratore_4h', 'label' => 'Lavoratore 4H (5 anni)', 'scadenza_mesi' => 60, 'fonte' => 'scadenze_militari', 'campo_sorgente' => 'lavoratore_4h', 'ordine' => 17],
            ['campo_db' => 'lavoratore_8h', 'label' => 'Lavoratore 8H (5 anni)', 'scadenza_mesi' => 60, 'fonte' => 'scadenze_militari', 'campo_sorgente' => 'lavoratore_8h', 'ordine' => 18],
            ['campo_db' => 'preposto', 'label' => 'Preposto (5 anni)', 'scadenza_mesi' => 60, 'fonte' => 'scadenze_militari', 'campo_sorgente' => 'preposto', 'ordine' => 19],
        ];

        $now = now();
        foreach ($colonneDefault as $colonna) {
            DB::table('config_colonne_approntamenti')->insert(array_merge($colonna, [
                'attivo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_colonne_approntamenti');
    }
};
