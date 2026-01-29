<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Log Configuration
    |--------------------------------------------------------------------------
    |
    | Configurazione completa per il sistema di audit log SUGECO.
    | Ottimizzato per gestire centinaia di migliaia di operazioni
    | mantenendo performance e scalabilità nel tempo.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Retention Days
    |--------------------------------------------------------------------------
    |
    | Numero di giorni per cui mantenere i log nel database attivo.
    | I log più vecchi vengono archiviati e poi eliminati.
    |
    | Raccomandazioni per ambiente:
    | - Sviluppo: 30-90 giorni
    | - Produzione: 365-730 giorni
    | - Alta compliance: 1825 giorni (5 anni)
    |
    */
    'retention_days' => env('AUDIT_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Archive Before Delete
    |--------------------------------------------------------------------------
    |
    | Se true, i log vengono esportati in CSV prima di essere eliminati.
    | I file CSV vengono compressi e salvati nella directory specificata.
    |
    | IMPORTANTE: Per compliance a lungo termine, si raccomanda di
    | mantenere questa opzione attiva e fare backup regolari degli archivi.
    |
    */
    'archive_before_delete' => env('AUDIT_ARCHIVE_BEFORE_DELETE', true),

    /*
    |--------------------------------------------------------------------------
    | Archive Directory
    |--------------------------------------------------------------------------
    |
    | Directory dove vengono salvati i file CSV archiviati.
    | Path relativo a storage/app/
    |
    | I file vengono organizzati per anno/mese:
    | archives/audit-logs/2026/01/audit_logs_2026-01-01_2026-01-31.csv
    |
    */
    'archive_directory' => env('AUDIT_ARCHIVE_DIRECTORY', 'archives/audit-logs'),

    /*
    |--------------------------------------------------------------------------
    | Cleanup Schedule
    |--------------------------------------------------------------------------
    |
    | Frequenza della pulizia automatica dei log vecchi.
    | La pulizia viene eseguita dal Laravel Scheduler.
    |
    | Per attivare lo scheduler, aggiungere al crontab del server:
    | * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
    |
    */
    'cleanup_schedule' => [
        'frequency' => 'monthly', // monthly, weekly, daily
        'day' => 1,               // Giorno del mese (monthly) o settimana (weekly, 0=domenica)
        'time' => '02:00',        // Ora di esecuzione (HH:MM)
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization
    |--------------------------------------------------------------------------
    |
    | Configurazioni per ottimizzare le operazioni su grandi volumi di dati.
    | Questi valori bilanciano velocità e carico sul database.
    |
    */
    'performance' => [
        // Batch size per eliminazioni (più grande = più veloce, più memoria)
        'delete_batch_size' => env('AUDIT_DELETE_BATCH', 1000),
        
        // Batch size per esportazioni CSV
        'export_batch_size' => env('AUDIT_EXPORT_BATCH', 500),
        
        // Pausa tra batch in microsecondi (previene sovraccarico DB)
        'batch_delay' => env('AUDIT_BATCH_DELAY', 50000), // 50ms
        
        // Limite massimo record per export singolo
        'max_export_records' => env('AUDIT_MAX_EXPORT', 10000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Numero di log per pagina nell'interfaccia web.
    | Valori più bassi = pagina più veloce, meno dati caricati.
    |
    */
    'per_page' => env('AUDIT_PER_PAGE', 50),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configurazione cache per statistiche e dati frequenti.
    |
    */
    'cache' => [
        // TTL cache statistiche dashboard (secondi)
        'stats_ttl' => env('AUDIT_CACHE_STATS_TTL', 300), // 5 minuti
        
        // TTL cache liste utenti/compagnie per filtri (secondi)
        'filters_ttl' => env('AUDIT_CACHE_FILTERS_TTL', 600), // 10 minuti
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Configurazione comportamento del logging.
    |
    */
    'logging' => [
        // Previeni log duplicati nello stesso secondo
        'prevent_duplicates' => true,
        
        // Durata finestra anti-duplicati (secondi)
        'duplicate_window' => 5,
        
        // Log azioni di sistema (oltre a quelle utente)
        'log_system_actions' => false,
        
        // Campi da escludere sempre dal logging
        'excluded_fields' => [
            'updated_at',
            'created_at', 
            'remember_token',
            'password',
            'password_confirmation',
            'email_verified_at',
            '_token',
            '_method',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerting
    |--------------------------------------------------------------------------
    |
    | Configurazione per alert su eventi critici.
    | (Predisposto per future integrazioni)
    |
    */
    'alerting' => [
        // Soglia login falliti per alert (0 = disabilitato)
        'failed_login_threshold' => env('AUDIT_FAILED_LOGIN_ALERT', 5),
        
        // Finestra temporale per conteggio tentativi (minuti)
        'failed_login_window' => 15,
    ],
];
