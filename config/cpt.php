<?php

/**
 * SUGECO: Configurazione CPT (Controllo Presenza Truppe)
 * 
 * Questo file centralizza tutti i codici relativi al CPT.
 * NON duplicare questi valori in altri punti del codice!
 * 
 * Uso: config('cpt.codici_assenza')
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Codici che indicano ASSENZA
    |--------------------------------------------------------------------------
    |
    | Questi codici nel CPT indicano che il militare è assente.
    | Usati per: filtri presenza, dashboard, calcolo ruolini.
    |
    */
    'codici_assenza' => [
        'LIC',      // Licenza
        'MAL',      // Malattia
        'RIP',      // Riposo
        'CONGEDO',  // Congedo
        'PERM',     // Permesso
        'LICENZA',  // Licenza (variante)
        'MALATTIA', // Malattia (variante)
        'RIPOSO',   // Riposo (variante)
    ],

    /*
    |--------------------------------------------------------------------------
    | Codici che indicano PRESENZA con servizio speciale
    |--------------------------------------------------------------------------
    |
    | Questi codici indicano che il militare è presente ma in servizio speciale.
    |
    */
    'codici_servizio' => [
        'SERV',     // Servizio
        'PIAN',     // Piantonamento
        'GUARD',    // Guardia
    ],

    /*
    |--------------------------------------------------------------------------
    | Comportamento Default per Weekend
    |--------------------------------------------------------------------------
    |
    | Sabato e Domenica: il militare è considerato assente di default
    | a meno che non abbia un servizio programmato nel CPT.
    |
    */
    'weekend_default_assente' => true,

    /*
    |--------------------------------------------------------------------------
    | Comportamento Default per Giorni Feriali
    |--------------------------------------------------------------------------
    |
    | Lunedì-Venerdì: il militare è considerato presente di default
    | a meno che non abbia un codice di assenza nel CPT.
    |
    */
    'feriali_default_presente' => true,
];
