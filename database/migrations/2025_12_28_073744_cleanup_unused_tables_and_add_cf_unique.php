<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migrazione per:
 * 1. Rimuovere tabelle non utilizzate
 * 2. Rendere il codice_fiscale obbligatorio e chiave univoca per i militari
 */
return new class extends Migration
{
    /**
     * Tabelle da eliminare (vuote e non utilizzate)
     */
    private array $tablesToDrop = [
        'certificati',
        'certificati_lavoratori', 
        'cpt_dashboard_views',
        'eventi',
        'idoneita',
        'incarichi',
        'militare_valutazioni',
        'notas',
        'poligoni',
        'presenze',
        'scadenze_poligoni',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Prima rimuovi la FK da militari.ultimo_poligono_id verso poligoni
        Schema::table('militari', function (Blueprint $table) {
            // Rimuovi la colonna ultimo_poligono_id se esiste
            if (Schema::hasColumn('militari', 'ultimo_poligono_id')) {
                // Prima rimuovi la FK se esiste
                try {
                    $table->dropForeign(['ultimo_poligono_id']);
                } catch (\Exception $e) {
                    // FK potrebbe non esistere, ignora
                }
                $table->dropColumn('ultimo_poligono_id');
            }
        });

        // 2. Elimina le tabelle non utilizzate
        foreach ($this->tablesToDrop as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::dropIfExists($tableName);
            }
        }

        // 3. Gestisci i militari senza codice fiscale (assegna CF temporaneo)
        $militariSenzaCF = DB::table('militari')
            ->where(function($query) {
                $query->whereNull('codice_fiscale')
                      ->orWhere('codice_fiscale', '');
            })
            ->get();

        foreach ($militariSenzaCF as $militare) {
            // Genera un CF temporaneo basato sull'ID
            $cfTemporaneo = 'TEMP' . str_pad($militare->id, 12, '0', STR_PAD_LEFT);
            DB::table('militari')
                ->where('id', $militare->id)
                ->update(['codice_fiscale' => $cfTemporaneo]);
        }

        // 4. Rendi codice_fiscale NOT NULL e UNIQUE
        Schema::table('militari', function (Blueprint $table) {
            // Prima modifica la colonna per renderla NOT NULL
            $table->string('codice_fiscale', 16)->nullable(false)->change();
        });

        // Aggiungi l'indice UNIQUE separatamente
        Schema::table('militari', function (Blueprint $table) {
            $table->unique('codice_fiscale', 'militari_codice_fiscale_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi il vincolo UNIQUE
        Schema::table('militari', function (Blueprint $table) {
            $table->dropUnique('militari_codice_fiscale_unique');
        });

        // Rendi codice_fiscale nullable di nuovo
        Schema::table('militari', function (Blueprint $table) {
            $table->string('codice_fiscale', 16)->nullable()->change();
        });

        // Ricrea le tabelle eliminate (struttura base)
        // NOTA: I dati non possono essere recuperati
        
        if (!Schema::hasTable('certificati')) {
            Schema::create('certificati', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('certificati_lavoratori')) {
            Schema::create('certificati_lavoratori', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cpt_dashboard_views')) {
            Schema::create('cpt_dashboard_views', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('eventi')) {
            Schema::create('eventi', function (Blueprint $table) {
                $table->id();
                $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
                $table->string('tipologia');
                $table->string('nome');
                $table->date('data_inizio');
                $table->date('data_fine');
                $table->string('localita');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('idoneita')) {
            Schema::create('idoneita', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('incarichi')) {
            Schema::create('incarichi', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('militare_valutazioni')) {
            Schema::create('militare_valutazioni', function (Blueprint $table) {
                $table->id();
                $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
                $table->foreignId('valutatore_id')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('notas')) {
            Schema::create('notas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->text('contenuto');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('poligoni')) {
            Schema::create('poligoni', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('presenze')) {
            Schema::create('presenze', function (Blueprint $table) {
                $table->id();
                $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
                $table->date('data');
                $table->string('stato');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('scadenze_poligoni')) {
            Schema::create('scadenze_poligoni', function (Blueprint $table) {
                $table->id();
                $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
                $table->foreignId('tipo_poligono_id')->constrained('tipi_poligono')->onDelete('cascade');
                $table->date('data_conseguimento')->nullable();
                $table->timestamps();
            });
        }

        // Ricrea la colonna ultimo_poligono_id nella tabella militari
        Schema::table('militari', function (Blueprint $table) {
            $table->unsignedBigInteger('ultimo_poligono_id')->nullable()->after('ruolo_id');
        });
    }
};
