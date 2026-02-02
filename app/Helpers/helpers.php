<?php

use App\Models\OrganizationalUnit;

// =========================================================================
// HELPER FUNZIONI UNITÀ ORGANIZZATIVA
// =========================================================================

if (! function_exists('activeUnit')) {
    /**
     * Ottiene l'unità organizzativa attualmente attiva per l'utente.
     * 
     * @return OrganizationalUnit|null
     */
    function activeUnit(): ?OrganizationalUnit
    {
        if (!app()->bound('active_unit')) {
            return null;
        }
        return app('active_unit');
    }
}

if (! function_exists('activeUnitId')) {
    /**
     * Ottiene l'ID dell'unità organizzativa attualmente attiva.
     * 
     * @return int|null
     */
    function activeUnitId(): ?int
    {
        if (!app()->bound('active_unit_id')) {
            return null;
        }
        return app('active_unit_id');
    }
}

if (! function_exists('isActiveUnit')) {
    /**
     * Verifica se un'unità è l'unità attiva corrente.
     * 
     * @param OrganizationalUnit|int $unit Unità o ID da verificare
     * @return bool
     */
    function isActiveUnit($unit): bool
    {
        $unitId = $unit instanceof OrganizationalUnit ? $unit->id : $unit;
        return activeUnitId() === $unitId;
    }
}

if (! function_exists('canEditInActiveUnit')) {
    /**
     * Verifica se un modello può essere modificato (appartiene all'unità attiva).
     * 
     * @param mixed $model Modello con organizational_unit_id
     * @return bool
     */
    function canEditInActiveUnit($model): bool
    {
        if (!$model || !isset($model->organizational_unit_id)) {
            return false;
        }
        
        return $model->organizational_unit_id === activeUnitId();
    }
}

// =========================================================================
// HELPER FUNZIONI CERTIFICATI
// =========================================================================

if (! function_exists('getCertificateInfo')) {
    function getCertificateInfo($cert, $isFemminile = false)
    {
        if (!$cert) {
            return [
                'class' => 'missing', 
                'text' => 'Mancante', 
                'color' => '#A0AEC0',
                'status' => 'missing',
                'daysRemaining' => '-',
                'ottenimento' => '-',
                'scadenza' => '-'
            ];
        }
        
        // Se non è presente data_scadenza, usiamo solo data_ottenimento (o restituiamo dati di default)
        if (!$cert->data_scadenza && !$cert->data_ottenimento) {
            return [
                'class' => 'missing',
                'text' => 'Mancante',
                'color' => '#A0AEC0',
                'status' => 'missing',
                'daysRemaining' => '-',
                'ottenimento' => '-',
                'scadenza' => '-'
            ];
        }
        
        // Per i certificati di questi tipi la durata è 1 anno
        $oneYearTypes = ['pefo', 'idoneita_smi', 'idoneita_mansione', 'idoneita'];
        if (in_array($cert->tipo, $oneYearTypes) && $cert->data_ottenimento && !$cert->data_scadenza) {
            $expirationDate = \Carbon\Carbon::parse($cert->data_ottenimento)->addYear();
        } else {
            $expirationDate = \Carbon\Carbon::parse($cert->data_scadenza);
        }
        
        $today = \Carbon\Carbon::today();
        $daysRemaining = $today->lte($expirationDate) ? $today->diffInDays($expirationDate, false) : -$today->diffInDays($expirationDate, false);
        
        if ($today->gt($expirationDate)) {
            $status = $isFemminile ? 'Scaduta' : 'Scaduto';
            $class = 'expired';
            $text = $isFemminile ? 'Scaduta' : 'Scaduto';
            $color = '#E53E3E'; // rosso
        } elseif ($daysRemaining <= 30) {
            $status = 'In scadenza';
            $class = 'expiring';
            $text = 'In scadenza';
            $color = '#DD6B20'; // arancione
        } else {
            $status = $isFemminile ? 'Valida' : 'Valido';
            $class = 'active';
            $text = $isFemminile ? 'Valida' : 'Valido';
            $color = '#38A169'; // verde
        }
        
        $ottenimento = $cert->data_ottenimento ? \Carbon\Carbon::parse($cert->data_ottenimento)->format('d/m/Y') : '-';
        $scadenza = $expirationDate->format('d/m/Y');
        
        return [
            'status' => $status,
            'class' => $class,
            'text' => $text,
            'color' => $color,
            'ottenimento' => $ottenimento,
            'scadenza' => $scadenza,
            'daysRemaining' => $daysRemaining
        ];
    }
}
