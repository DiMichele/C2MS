<?php

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
