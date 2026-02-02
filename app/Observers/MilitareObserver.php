<?php

namespace App\Observers;

use App\Models\Militare;
use App\Models\OrganizationalUnit;
use App\Services\AuditService;
use Illuminate\Support\Facades\Log;

/**
 * Observer per il modello Militare.
 * 
 * Gestisce:
 * - Sincronizzazione bidirezionale tra organizational_unit_id e campi legacy (compagnia_id, plotone_id)
 * - Logging trasferimenti tra unità organizzative
 * - Validazioni pre-salvataggio
 */
class MilitareObserver
{
    /**
     * Flag statico per prevenire loop infiniti durante la sincronizzazione.
     */
    private static bool $isSyncing = false;

    /**
     * Handle the Militare "updating" event.
     * Chiamato PRIMA che le modifiche siano salvate.
     */
    public function updating(Militare $militare): void
    {
        // Previeni loop infiniti: se stiamo già sincronizzando, esci
        if (self::$isSyncing) {
            return;
        }

        // ============================================
        // SINCRONIZZAZIONE BIDIREZIONALE
        // ============================================
        
        self::$isSyncing = true;
        
        try {
            // CASO A: organizational_unit_id cambia → aggiorna campi legacy
            if ($militare->isDirty('organizational_unit_id') && !$militare->isDirty(['compagnia_id', 'plotone_id'])) {
                $this->syncLegacyFieldsFromUnit($militare);
            }
            
            // CASO B: compagnia_id o plotone_id cambiano → aggiorna organizational_unit_id
            elseif ($militare->isDirty(['compagnia_id', 'plotone_id']) && !$militare->isDirty('organizational_unit_id')) {
                $this->syncUnitFromLegacyFields($militare);
            }
        } finally {
            self::$isSyncing = false;
        }

        // ============================================
        // LOGGING TRASFERIMENTO
        // ============================================
        
        if ($militare->isDirty('organizational_unit_id')) {
            $oldUnitId = $militare->getOriginal('organizational_unit_id');
            $newUnitId = $militare->organizational_unit_id;

            // Non loggare se entrambi sono null o uguali
            if ($oldUnitId === $newUnitId) {
                return;
            }

            // Ottieni i nomi delle unità per un log leggibile
            $oldUnitName = $oldUnitId 
                ? (OrganizationalUnit::find($oldUnitId)?->name ?? "ID: {$oldUnitId}") 
                : 'Nessuna';
            $newUnitName = $newUnitId 
                ? (OrganizationalUnit::find($newUnitId)?->name ?? "ID: {$newUnitId}") 
                : 'Nessuna';

            // Log del trasferimento con contesto completo
            Log::info('Trasferimento militare tra unità organizzative', [
                'militare_id' => $militare->id,
                'militare_nome' => "{$militare->cognome} {$militare->nome}",
                'old_unit_id' => $oldUnitId,
                'old_unit_name' => $oldUnitName,
                'new_unit_id' => $newUnitId,
                'new_unit_name' => $newUnitName,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()?->name ?? 'Sistema',
            ]);
        }
    }

    /**
     * Sincronizza i campi legacy (compagnia_id, plotone_id) dall'organizational_unit_id.
     * Chiamato quando l'utente modifica l'unità organizzativa (es. dall'organigramma).
     */
    private function syncLegacyFieldsFromUnit(Militare $militare): void
    {
        if (!$militare->organizational_unit_id) {
            // Se l'unità è null, resetta anche i campi legacy
            $militare->compagnia_id = null;
            $militare->plotone_id = null;
            return;
        }

        $unit = OrganizationalUnit::with(['type', 'parent.type', 'parent.parent.type'])->find($militare->organizational_unit_id);
        
        if (!$unit) {
            return;
        }

        $typeCode = $unit->type?->code;

        // Se è un plotone → imposta plotone_id e compagnia_id dalla gerarchia
        if ($typeCode === 'plotone') {
            $militare->plotone_id = $unit->legacy_plotone_id;
            $compagniaId = $this->findLegacyCompagniaId($unit->parent);
            $militare->compagnia_id = $compagniaId;
        }
        // Se è una compagnia → imposta compagnia_id, resetta plotone
        elseif ($typeCode === 'compagnia') {
            $militare->compagnia_id = $unit->legacy_compagnia_id;
            $militare->plotone_id = null;
        }
        // Se è un ufficio → cerca compagnia nel parent, resetta plotone
        elseif ($typeCode === 'ufficio') {
            $compagniaId = $this->findLegacyCompagniaId($unit->parent);
            $militare->compagnia_id = $compagniaId;
            $militare->plotone_id = null;
        }
        // Altri tipi (battaglione, reggimento, sezione) → resetta entrambi
        else {
            $compagniaId = $this->findLegacyCompagniaId($unit);
            $militare->compagnia_id = $compagniaId;
            $militare->plotone_id = null;
        }

        Log::debug('Sincronizzati campi legacy da organizational_unit_id', [
            'militare_id' => $militare->id,
            'organizational_unit_id' => $militare->organizational_unit_id,
            'compagnia_id' => $militare->compagnia_id,
            'plotone_id' => $militare->plotone_id,
        ]);
    }

    /**
     * Sincronizza organizational_unit_id dai campi legacy (compagnia_id, plotone_id).
     * Chiamato quando l'utente modifica compagnia/plotone (es. dall'anagrafica).
     */
    private function syncUnitFromLegacyFields(Militare $militare): void
    {
        // Se plotone_id è impostato, cerca OrganizationalUnit corrispondente
        if ($militare->plotone_id) {
            $orgUnit = OrganizationalUnit::where('legacy_plotone_id', $militare->plotone_id)->first();
            if ($orgUnit) {
                $militare->organizational_unit_id = $orgUnit->id;
                Log::debug('Sincronizzato organizational_unit_id da plotone_id', [
                    'militare_id' => $militare->id,
                    'plotone_id' => $militare->plotone_id,
                    'organizational_unit_id' => $orgUnit->id,
                ]);
                return;
            }
        }

        // Altrimenti, se compagnia_id è impostata, cerca OrganizationalUnit corrispondente
        if ($militare->compagnia_id) {
            $orgUnit = OrganizationalUnit::where('legacy_compagnia_id', $militare->compagnia_id)->first();
            if ($orgUnit) {
                $militare->organizational_unit_id = $orgUnit->id;
                Log::debug('Sincronizzato organizational_unit_id da compagnia_id', [
                    'militare_id' => $militare->id,
                    'compagnia_id' => $militare->compagnia_id,
                    'organizational_unit_id' => $orgUnit->id,
                ]);
                return;
            }
        }

        // Se entrambi sono null, resetta organizational_unit_id
        if (!$militare->plotone_id && !$militare->compagnia_id) {
            $militare->organizational_unit_id = null;
        }
    }

    /**
     * Risale la gerarchia per trovare il legacy_compagnia_id.
     */
    private function findLegacyCompagniaId(?OrganizationalUnit $unit): ?int
    {
        if (!$unit) {
            return null;
        }

        // Se questa unità ha un legacy_compagnia_id, restituiscilo
        if ($unit->legacy_compagnia_id) {
            return $unit->legacy_compagnia_id;
        }

        // Altrimenti, risali al parent
        if ($unit->parent_id) {
            $parent = $unit->relationLoaded('parent') 
                ? $unit->parent 
                : OrganizationalUnit::find($unit->parent_id);
            return $this->findLegacyCompagniaId($parent);
        }

        return null;
    }

    /**
     * Handle the Militare "updated" event.
     * Chiamato DOPO che le modifiche sono state salvate.
     */
    public function updated(Militare $militare): void
    {
        // Rileva trasferimento completato
        if ($militare->wasChanged('organizational_unit_id')) {
            $oldUnitId = $militare->getOriginal('organizational_unit_id');
            $newUnitId = $militare->organizational_unit_id;

            // Non loggare se entrambi sono null o uguali
            if ($oldUnitId === $newUnitId) {
                return;
            }

            // Ottieni i nomi delle unità
            $oldUnitName = $oldUnitId 
                ? (OrganizationalUnit::find($oldUnitId)?->name ?? "ID: {$oldUnitId}") 
                : 'Nessuna';
            $newUnitName = $newUnitId 
                ? (OrganizationalUnit::find($newUnitId)?->name ?? "ID: {$newUnitId}") 
                : 'Nessuna';

            // Registra nel sistema di audit
            try {
                $description = "Militare {$militare->cognome} {$militare->nome} trasferito da '{$oldUnitName}' a '{$newUnitName}'. " .
                    "NOTA: I dati storici (CPT, attività board, turni) restano nell'unità di origine.";

                AuditService::log('transfer', $militare, $description, [
                    'old_unit_id' => $oldUnitId,
                    'old_unit_name' => $oldUnitName,
                    'new_unit_id' => $newUnitId,
                    'new_unit_name' => $newUnitName,
                    'transfer_type' => 'organizational_unit',
                ]);
            } catch (\Throwable $e) {
                // Non bloccare l'operazione se l'audit fallisce
                Log::warning('Errore logging audit trasferimento militare', [
                    'militare_id' => $militare->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the Militare "created" event.
     */
    public function created(Militare $militare): void
    {
        // Log creazione con unità assegnata
        if ($militare->organizational_unit_id) {
            $unitName = OrganizationalUnit::find($militare->organizational_unit_id)?->name ?? "ID: {$militare->organizational_unit_id}";
            
            Log::info('Nuovo militare creato con unità organizzativa', [
                'militare_id' => $militare->id,
                'militare_nome' => "{$militare->cognome} {$militare->nome}",
                'unit_id' => $militare->organizational_unit_id,
                'unit_name' => $unitName,
            ]);
        }
    }
}
