<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration per rimuovere campi legacy e ridondanti dalla tabella militari.
 * 
 * Campi rimossi:
 * - ruolo (string) - ridondante con ruolo_id (FK)
 * - compagnia (enum) - ridondante con compagnia_id (FK)
 * - certificati_note - non utilizzato (esiste già note)
 * - idoneita_note - non utilizzato (esiste già note)
 * - nos_scadenza - non utilizzato
 * - nos_note - non utilizzato
 * - compagnia_nos - non utilizzato
 * - data_ultimo_poligono - denormalizzato (calcolabile da scadenze_poligoni)
 * - approntamento_principale_id - tabella approntamenti eliminata
 * 
 * Modifiche:
 * - email_istituzionale rinominato in email (elimina il vecchio email se esiste)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Prima di tutto, se esistono entrambi i campi email, migrare i dati
        if (Schema::hasColumn('militari', 'email') && Schema::hasColumn('militari', 'email_istituzionale')) {
            // Copia i dati da email_istituzionale a email se email_istituzionale ha valore
            DB::statement("
                UPDATE militari 
                SET email = email_istituzionale 
                WHERE email_istituzionale IS NOT NULL 
                AND email_istituzionale != ''
            ");
        }

        Schema::table('militari', function (Blueprint $table) {
            // Rimuovi campi legacy uno alla volta con controlli di esistenza
            
            // 1. Rimuovi ruolo (string) - usiamo ruolo_id
            if (Schema::hasColumn('militari', 'ruolo')) {
                $table->dropColumn('ruolo');
            }
            
            // 2. Rimuovi compagnia (enum) - usiamo compagnia_id
            if (Schema::hasColumn('militari', 'compagnia')) {
                $table->dropColumn('compagnia');
            }
            
            // 3. Rimuovi certificati_note - non usato
            if (Schema::hasColumn('militari', 'certificati_note')) {
                $table->dropColumn('certificati_note');
            }
            
            // 4. Rimuovi idoneita_note - non usato
            if (Schema::hasColumn('militari', 'idoneita_note')) {
                $table->dropColumn('idoneita_note');
            }
            
            // 5. Rimuovi nos_scadenza - non usato
            if (Schema::hasColumn('militari', 'nos_scadenza')) {
                $table->dropColumn('nos_scadenza');
            }
            
            // 6. Rimuovi nos_note - non usato
            if (Schema::hasColumn('militari', 'nos_note')) {
                $table->dropColumn('nos_note');
            }
            
            // 7. Rimuovi compagnia_nos - non usato
            if (Schema::hasColumn('militari', 'compagnia_nos')) {
                $table->dropColumn('compagnia_nos');
            }
            
            // 8. Rimuovi data_ultimo_poligono - denormalizzato
            if (Schema::hasColumn('militari', 'data_ultimo_poligono')) {
                // Prima rimuovi l'indice se esiste
                try {
                    $table->dropIndex(['data_ultimo_poligono']);
                } catch (\Exception $e) {
                    // Indice potrebbe non esistere
                }
                $table->dropColumn('data_ultimo_poligono');
            }
            
            // 9. Rimuovi email_istituzionale (i dati sono già migrati in email)
            if (Schema::hasColumn('militari', 'email_istituzionale')) {
                $table->dropColumn('email_istituzionale');
            }
        });
        
        // Rimuovi approntamento_principale_id in una chiamata separata per gestire la FK
        if (Schema::hasColumn('militari', 'approntamento_principale_id')) {
            Schema::table('militari', function (Blueprint $table) {
                // Prima rimuovi la FK se esiste
                try {
                    $table->dropForeign(['approntamento_principale_id']);
                } catch (\Exception $e) {
                    // FK potrebbe non esistere
                }
                $table->dropColumn('approntamento_principale_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('militari', function (Blueprint $table) {
            // Ricrea i campi rimossi
            
            if (!Schema::hasColumn('militari', 'ruolo')) {
                $table->string('ruolo', 50)->default('Lavoratore')->after('mansione_id');
            }
            
            if (!Schema::hasColumn('militari', 'compagnia')) {
                $table->enum('compagnia', ['110', '124', '127'])->nullable()->after('polo_id');
            }
            
            if (!Schema::hasColumn('militari', 'certificati_note')) {
                $table->text('certificati_note')->nullable()->after('note');
            }
            
            if (!Schema::hasColumn('militari', 'idoneita_note')) {
                $table->text('idoneita_note')->nullable()->after('certificati_note');
            }
            
            if (!Schema::hasColumn('militari', 'nos_scadenza')) {
                $table->date('nos_scadenza')->nullable()->after('nos_status');
            }
            
            if (!Schema::hasColumn('militari', 'nos_note')) {
                $table->text('nos_note')->nullable()->after('nos_scadenza');
            }
            
            if (!Schema::hasColumn('militari', 'compagnia_nos')) {
                $table->string('compagnia_nos', 50)->nullable()->after('nos_note');
            }
            
            if (!Schema::hasColumn('militari', 'data_ultimo_poligono')) {
                $table->date('data_ultimo_poligono')->nullable();
                $table->index('data_ultimo_poligono');
            }
            
            if (!Schema::hasColumn('militari', 'email_istituzionale')) {
                $table->string('email_istituzionale', 255)->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('militari', 'approntamento_principale_id')) {
                $table->foreignId('approntamento_principale_id')->nullable()
                      ->constrained('approntamenti')->onDelete('set null');
            }
        });
    }
};
