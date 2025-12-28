<?php

namespace App\Observers;

use App\Models\TipoServizio;
use App\Models\ConfigurazioneRuolino;
use Illuminate\Support\Facades\Log;

/**
 * Observer per TipoServizio
 * 
 * Mantiene la sincronizzazione tra i codici CPT e le altre parti del sistema:
 * - Gestione Ruolini
 * - Ruolini
 * - CPT
 */
class TipoServizioObserver
{
    /**
     * Handle the TipoServizio "updated" event.
     * 
     * Quando un tipo servizio viene DISATTIVATO, verifica se ha configurazioni
     * nei ruolini e le rimuove automaticamente.
     */
    public function updated(TipoServizio $tipoServizio)
    {
        // Se il tipo servizio è stato disattivato
        if ($tipoServizio->isDirty('attivo') && !$tipoServizio->attivo) {
            Log::info('TipoServizio disattivato, rimuovo configurazioni ruolini', [
                'tipo_servizio_id' => $tipoServizio->id,
                'codice' => $tipoServizio->codice,
                'nome' => $tipoServizio->nome
            ]);
            
            // Rimuovi eventuali configurazioni ruolini associate
            ConfigurazioneRuolino::where('tipo_servizio_id', $tipoServizio->id)->delete();
            
            Log::info('Configurazioni ruolini rimosse per tipo servizio disattivato', [
                'tipo_servizio_id' => $tipoServizio->id
            ]);
        }
        
        // Se il tipo servizio è stato riattivato, non serve fare nulla
        // L'utente potrà riconfigurarlo da gestione ruolini se necessario
    }
    
    /**
     * Handle the TipoServizio "deleted" event.
     * 
     * Quando un tipo servizio viene eliminato, rimuove anche tutte le sue configurazioni.
     */
    public function deleted(TipoServizio $tipoServizio)
    {
        Log::info('TipoServizio eliminato, rimuovo configurazioni ruolini', [
            'tipo_servizio_id' => $tipoServizio->id,
            'codice' => $tipoServizio->codice,
            'nome' => $tipoServizio->nome
        ]);
        
        // Rimuovi tutte le configurazioni ruolini associate
        ConfigurazioneRuolino::where('tipo_servizio_id', $tipoServizio->id)->delete();
        
        Log::info('Configurazioni ruolini rimosse per tipo servizio eliminato', [
            'tipo_servizio_id' => $tipoServizio->id
        ]);
    }
}

