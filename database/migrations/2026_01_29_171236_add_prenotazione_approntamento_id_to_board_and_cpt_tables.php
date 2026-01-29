<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Aggiunge foreign keys per collegare board_activities e pianificazioni_giornaliere
     * alle prenotazioni_approntamenti, permettendo sincronizzazione bidirezionale.
     */
    public function up(): void
    {
        // Aggiungi FK a board_activities
        Schema::table('board_activities', function (Blueprint $table) {
            $table->unsignedBigInteger('prenotazione_approntamento_id')->nullable()->after('id');
            
            $table->foreign('prenotazione_approntamento_id')
                  ->references('id')
                  ->on('prenotazioni_approntamenti')
                  ->nullOnDelete();
            
            $table->index('prenotazione_approntamento_id');
        });

        // Aggiungi FK a pianificazioni_giornaliere
        Schema::table('pianificazioni_giornaliere', function (Blueprint $table) {
            $table->unsignedBigInteger('prenotazione_approntamento_id')->nullable()->after('tipo_servizio_id');
            
            $table->foreign('prenotazione_approntamento_id')
                  ->references('id')
                  ->on('prenotazioni_approntamenti')
                  ->nullOnDelete();
            
            $table->index('prenotazione_approntamento_id');
        });

        // Tenta di collegare record esistenti basandosi su pattern noti
        $this->linkExistingRecords();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('board_activities', function (Blueprint $table) {
            $table->dropForeign(['prenotazione_approntamento_id']);
            $table->dropIndex(['prenotazione_approntamento_id']);
            $table->dropColumn('prenotazione_approntamento_id');
        });

        Schema::table('pianificazioni_giornaliere', function (Blueprint $table) {
            $table->dropForeign(['prenotazione_approntamento_id']);
            $table->dropIndex(['prenotazione_approntamento_id']);
            $table->dropColumn('prenotazione_approntamento_id');
        });
    }

    /**
     * Tenta di collegare i record esistenti basandosi su:
     * - Stesso militare_id
     * - Stessa data
     * - Nota/titolo contenente riferimento alla cattedra
     */
    private function linkExistingRecords(): void
    {
        // Collega pianificazioni_giornaliere esistenti
        // Le pianificazioni create da prenotazioni hanno nota "Cattedra: [nome]"
        $prenotazioni = DB::table('prenotazioni_approntamenti')
            ->where('stato', '!=', 'annullato')
            ->get();

        foreach ($prenotazioni as $prenotazione) {
            $dataPrenotazione = $prenotazione->data_prenotazione;
            $militareId = $prenotazione->militare_id;
            
            // Trova la pianificazione mensile per questa data
            $anno = date('Y', strtotime($dataPrenotazione));
            $mese = date('n', strtotime($dataPrenotazione));
            $giorno = date('j', strtotime($dataPrenotazione));
            
            $pianificazioneMensile = DB::table('pianificazioni_mensili')
                ->where('anno', $anno)
                ->where('mese', $mese)
                ->first();
            
            if ($pianificazioneMensile) {
                // Aggiorna pianificazioni_giornaliere che corrispondono
                DB::table('pianificazioni_giornaliere')
                    ->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                    ->where('militare_id', $militareId)
                    ->where('giorno', $giorno)
                    ->whereNull('prenotazione_approntamento_id')
                    ->update(['prenotazione_approntamento_id' => $prenotazione->id]);
            }

            // Collega board_activities esistenti
            // Le attività create da prenotazioni hanno titolo "[Cattedra] - Approntamento"
            DB::table('board_activities')
                ->where('start_date', $dataPrenotazione)
                ->where('title', 'like', '%- Approntamento')
                ->whereNull('prenotazione_approntamento_id')
                ->whereExists(function ($query) use ($militareId) {
                    $query->select(DB::raw(1))
                          ->from('activity_militare')
                          ->whereColumn('activity_militare.activity_id', 'board_activities.id')
                          ->where('activity_militare.militare_id', $militareId);
                })
                ->update(['prenotazione_approntamento_id' => $prenotazione->id]);
        }
    }
};
