<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * SUGECO: Migration per Indici Segregazione per Compagnia
 * 
 * Questa migration aggiunge indici ottimizzati per le query di segregazione
 * dei dati per compagnia, migliorando significativamente le performance
 * delle query che determinano visibilità owner/acquired.
 * 
 * INDICI AGGIUNTI:
 * - militari(compagnia_id) - filtro base per owner
 * - activity_militare(militare_id, activity_id) - lookup acquisiti
 * - board_activities(compagnia_id) - filtro attività per compagnia
 * 
 * PERFORMANCE: Questi indici sono critici per la performance del sistema
 * con dataset >500 militari e molte attività cross-compagnia.
 * 
 * @version 1.0
 * @author Michele Di Gennaro
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Indice su militari.compagnia_id (se non esiste già)
        if (!$this->indexExists('militari', 'idx_militari_compagnia_id')) {
            Schema::table('militari', function (Blueprint $table) {
                $table->index('compagnia_id', 'idx_militari_compagnia_id');
            });
        }
        
        // Indice composito su activity_militare per lookup efficienti
        // Questo indice supporta sia la ricerca per militare_id che per la coppia (militare_id, activity_id)
        if (Schema::hasTable('activity_militare')) {
            if (!$this->indexExists('activity_militare', 'idx_activity_militare_lookup')) {
                Schema::table('activity_militare', function (Blueprint $table) {
                    $table->index(['militare_id', 'activity_id'], 'idx_activity_militare_lookup');
                });
            }
            
            // Indice inverso per ricerche per activity_id
            if (!$this->indexExists('activity_militare', 'idx_activity_militare_activity')) {
                Schema::table('activity_militare', function (Blueprint $table) {
                    $table->index('activity_id', 'idx_activity_militare_activity');
                });
            }
        }
        
        // Indice su board_activities.compagnia_id (se non esiste già)
        if (Schema::hasTable('board_activities')) {
            if (!$this->indexExists('board_activities', 'idx_board_activities_compagnia')) {
                Schema::table('board_activities', function (Blueprint $table) {
                    $table->index('compagnia_id', 'idx_board_activities_compagnia');
                });
            }
        }
        
        // Indice su pianificazioni_giornaliere per filtro per militare
        if (Schema::hasTable('pianificazioni_giornaliere')) {
            if (!$this->indexExists('pianificazioni_giornaliere', 'idx_pianificazioni_giornaliere_militare')) {
                Schema::table('pianificazioni_giornaliere', function (Blueprint $table) {
                    $table->index('militare_id', 'idx_pianificazioni_giornaliere_militare');
                });
            }
        }
        
        // Indice su plotoni.compagnia_id per filtro plotoni per compagnia
        if (Schema::hasTable('plotoni')) {
            if (!$this->indexExists('plotoni', 'idx_plotoni_compagnia')) {
                Schema::table('plotoni', function (Blueprint $table) {
                    $table->index('compagnia_id', 'idx_plotoni_compagnia');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi indici in ordine inverso
        if (Schema::hasTable('plotoni') && $this->indexExists('plotoni', 'idx_plotoni_compagnia')) {
            Schema::table('plotoni', function (Blueprint $table) {
                $table->dropIndex('idx_plotoni_compagnia');
            });
        }
        
        if (Schema::hasTable('pianificazioni_giornaliere') && $this->indexExists('pianificazioni_giornaliere', 'idx_pianificazioni_giornaliere_militare')) {
            Schema::table('pianificazioni_giornaliere', function (Blueprint $table) {
                $table->dropIndex('idx_pianificazioni_giornaliere_militare');
            });
        }
        
        if (Schema::hasTable('board_activities') && $this->indexExists('board_activities', 'idx_board_activities_compagnia')) {
            Schema::table('board_activities', function (Blueprint $table) {
                $table->dropIndex('idx_board_activities_compagnia');
            });
        }
        
        if (Schema::hasTable('activity_militare')) {
            if ($this->indexExists('activity_militare', 'idx_activity_militare_activity')) {
                Schema::table('activity_militare', function (Blueprint $table) {
                    $table->dropIndex('idx_activity_militare_activity');
                });
            }
            
            if ($this->indexExists('activity_militare', 'idx_activity_militare_lookup')) {
                Schema::table('activity_militare', function (Blueprint $table) {
                    $table->dropIndex('idx_activity_militare_lookup');
                });
            }
        }
        
        if ($this->indexExists('militari', 'idx_militari_compagnia_id')) {
            Schema::table('militari', function (Blueprint $table) {
                $table->dropIndex('idx_militari_compagnia_id');
            });
        }
    }
    
    /**
     * Verifica se un indice esiste su una tabella
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};

