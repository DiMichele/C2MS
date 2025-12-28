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
        // Disabilita i vincoli di foreign key temporaneamente
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Svuota la tabella servizi_turno
        DB::table('servizi_turno')->truncate();
        
        // Riabilita i vincoli di foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Inserisci i servizi corretti
        $servizi = [
            ['nome' => 'GRADUATO DI BTG', 'num_posti' => 1, 'attivo' => 1, 'ordine' => 1],
            ['nome' => 'NUCLEO VIGILANZA ARMATA D\'AVANZO', 'num_posti' => 1, 'attivo' => 1, 'ordine' => 2],
            ['nome' => 'CONDUTTORE GUARDIA', 'num_posti' => 1, 'attivo' => 1, 'ordine' => 3],
            ['nome' => 'NUCLEO SORV D AVANZO 07:30 - 17:00', 'num_posti' => 1, 'attivo' => 1, 'ordine' => 4],
            ['nome' => 'VIGILANZA PIAN DEL TERMINE', 'num_posti' => 1, 'attivo' => 1, 'ordine' => 5],
            ['nome' => 'ADDETTO ANTINCENDIO', 'num_posti' => 1, 'attivo' => 1, 'ordine' => 6],
            ['nome' => 'VIGILANZA SETTIMANALE CEILI', 'num_posti' => 1, 'attivo' => 1, 'ordine' => 7],
            ['nome' => 'CORRIERE', 'num_posti' => 1, 'attivo' => 1, 'ordine' => 8],
            ['nome' => 'NUCLEO DIFESA IMMEDIATA', 'num_posti' => 1, 'attivo' => 1, 'ordine' => 9],
        ];
        
        foreach ($servizi as $servizio) {
            DB::table('servizi_turno')->insert([
                'nome' => $servizio['nome'],
                'num_posti' => $servizio['num_posti'],
                'attivo' => $servizio['attivo'],
                'ordine' => $servizio['ordine'],
                'note' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nota: il rollback ripristinerà i servizi vecchi se erano stati salvati
        // In alternativa, puoi lasciare vuoto o ricreare manualmente
    }
};
