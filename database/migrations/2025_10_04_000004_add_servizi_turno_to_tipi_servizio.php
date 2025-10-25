<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Aggiungi nuovi tipi servizio per i turni se non esistono già
        $nuoviServizi = [
            ['codice' => 'G1', 'nome' => 'Guardio 1', 'descrizione' => 'Servizio Guardio 1'],
            ['codice' => 'G2', 'nome' => 'Guardio 2', 'descrizione' => 'Servizio Guardio 2'],
            ['codice' => 'SG', 'nome' => 'Servizio Generale', 'descrizione' => 'Servizio Generale'],
            ['codice' => 'PDT1', 'nome' => 'Pian del Termine 1', 'descrizione' => 'Vigilanza Pian del Termine posizione 1'],
            ['codice' => 'PDT2', 'nome' => 'Pian del Termine 2', 'descrizione' => 'Vigilanza Pian del Termine posizione 2'],
            ['codice' => 'G-BTG', 'nome' => 'Graduato di Battaglione', 'descrizione' => 'Servizio Graduato di BTG'],
            ['codice' => 'NVA', 'nome' => 'Nucleo Vigilanza Armata', 'descrizione' => 'Nucleo Vigilanza Armata D\'Avanzo'],
            ['codice' => 'CG', 'nome' => 'Conduttore Guardia', 'descrizione' => 'Servizio Conduttore Guardia'],
            ['codice' => 'AA', 'nome' => 'Addetto Antincendio', 'descrizione' => 'Servizio Addetto Antincendio'],
            ['codice' => 'VS-CETLI', 'nome' => 'Vigilanza Settimanale CETLI', 'descrizione' => 'Vigilanza Settimanale CETLI'],
            ['codice' => 'CORR', 'nome' => 'Corriere', 'descrizione' => 'Servizio Corriere'],
            ['codice' => 'NDI', 'nome' => 'Nucleo Difesa Immediata', 'descrizione' => 'Nucleo Difesa Immediata'],
        ];

        foreach ($nuoviServizi as $servizio) {
            // Controlla se esiste già
            $exists = DB::table('tipi_servizio')->where('codice', $servizio['codice'])->exists();
            
            if (!$exists) {
                DB::table('tipi_servizio')->insert([
                    'codice' => $servizio['codice'],
                    'nome' => $servizio['nome'],
                    'descrizione' => $servizio['descrizione'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Opzionale: rimuovi i servizi aggiunti
        $codiciDaRimuovere = ['G1', 'G2', 'SG', 'PDT1', 'PDT2', 'G-BTG', 'NVA', 'CG', 'AA', 'VS-CETLI', 'CORR', 'NDI'];
        
        DB::table('tipi_servizio')->whereIn('codice', $codiciDaRimuovere)->delete();
    }
};

