<?php

namespace App\Observers;

use App\Models\MilitareApprontamento;
use App\Models\Militare;
use Illuminate\Support\Facades\Log;

/**
 * Observer per MilitareApprontamento.
 * 
 * NOTA: Il campo militari.approntamento_principale_id è stato rimosso.
 * Questo observer ora gestisce solo la logica del flag 'principale' nella tabella pivot.
 * 
 * @deprecated Considerare la migrazione a TeatroOperativoMilitare
 */
class MilitareApprontamentoObserver
{
    /**
     * Handle the MilitareApprontamento "saved" event.
     * Assicura che ci sia un solo approntamento principale per militare.
     */
    public function saved(MilitareApprontamento $assegnazione): void
    {
        if ($assegnazione->principale) {
            // Rimuovi il flag principale da altre assegnazioni dello stesso militare
            MilitareApprontamento::where('militare_id', $assegnazione->militare_id)
                ->where('id', '!=', $assegnazione->id)
                ->where('principale', true)
                ->update(['principale' => false]);
                
            Log::info('Approntamento principale impostato', [
                'militare_id' => $assegnazione->militare_id,
                'approntamento_id' => $assegnazione->approntamento_id
            ]);
        }
    }

    /**
     * Handle the MilitareApprontamento "deleted" event.
     */
    public function deleted(MilitareApprontamento $assegnazione): void
    {
        if ($assegnazione->principale) {
            Log::info('Approntamento principale eliminato', [
                'militare_id' => $assegnazione->militare_id,
                'approntamento_id' => $assegnazione->approntamento_id
            ]);
        }
    }
}
