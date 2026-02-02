<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * I 15 campi di sistema che non devono essere eliminabili.
     */
    private array $campiSistema = [
        'compagnia',
        'grado',
        'cognome',
        'nome',
        'plotone',
        'ufficio',
        'incarico',
        'patenti',
        'nos',
        'anzianita',
        'data_nascita',
        'email_istituzionale',
        'telefono',
        'codice_fiscale',
        'istituti',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Aggiungi colonna is_system
        Schema::table('configurazione_campi_anagrafica', function (Blueprint $table) {
            if (!Schema::hasColumn('configurazione_campi_anagrafica', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('obbligatorio');
            }
        });

        // Imposta is_system = true per i 15 campi sistema
        DB::table('configurazione_campi_anagrafica')
            ->whereIn('nome_campo', $this->campiSistema)
            ->update(['is_system' => true]);

        // Log del numero di campi aggiornati
        $count = DB::table('configurazione_campi_anagrafica')
            ->where('is_system', true)
            ->count();

        if (app()->runningInConsole()) {
            echo "Aggiornati {$count} campi sistema con is_system = true\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurazione_campi_anagrafica', function (Blueprint $table) {
            if (Schema::hasColumn('configurazione_campi_anagrafica', 'is_system')) {
                $table->dropColumn('is_system');
            }
        });
    }
};
