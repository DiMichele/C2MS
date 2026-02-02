<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;

/**
 * Service per la gestione dei log di audit.
 * 
 * Questo service fornisce metodi semplici per registrare
 * le azioni degli utenti nel sistema.
 * 
 * Ottimizzato per:
 * - Gestire grandi volumi di log
 * - Filtrare campi non significativi
 * - Comprimere dati ridondanti
 * - Prevenire flood di log duplicati
 * 
 * Esempio di utilizzo:
 * 
 *   AuditService::log('create', 'Creato nuovo militare', $militare);
 *   AuditService::logLogin($user);
 *   AuditService::logUpdate($militare, $oldValues, $newValues);
 */
class AuditService
{
    /**
     * Campi da escludere automaticamente dal logging (non significativi).
     */
    private const EXCLUDED_FIELDS = [
        'updated_at',
        'created_at',
        'remember_token',
        'password',
        'password_confirmation',
        'email_verified_at',
        '_token',
        '_method',
    ];
    
    /**
     * Cache temporanea per prevenire log duplicati (stesso secondo).
     */
    private static array $recentLogs = [];
    
    /**
     * Registra un'azione generica nel log.
     *
     * @param string $action Tipo di azione (vedi AuditLog::ACTION_LABELS)
     * @param string $description Descrizione leggibile dell'azione
     * @param Model|null $entity Entità coinvolta (opzionale)
     * @param array $metadata Dati aggiuntivi (opzionale)
     * @param string $status Stato dell'operazione (success, failed, warning)
     */
    public static function log(
        string $action,
        string $description,
        ?Model $entity = null,
        array $metadata = [],
        string $status = 'success'
    ): ?AuditLog {
        try {
            // Prevenzione duplicati (stesso utente, stessa azione, stessa entità, stesso secondo)
            $logKey = self::generateLogKey($action, $entity);
            if (self::isDuplicateLog($logKey)) {
                return null;
            }
            
            $user = Auth::user();
            $request = Request::instance();

            // Contesto unità organizzativa
            $activeUnit = activeUnit();
            
            $data = [
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? $user?->username ?? 'Sistema',
                'action' => $action,
                'description' => self::truncateDescription($description),
                'status' => $status,
                'ip_address' => $request->ip(),
                'user_agent' => self::truncateUserAgent($request->userAgent()),
                'url' => self::truncateUrl($request->fullUrl()),
                'method' => $request->method(),
                'compagnia_id' => $user?->compagnia_id,
                // Multi-tenancy: traccia unità attiva
                'active_unit_id' => $activeUnit?->id,
                'active_unit_name' => $activeUnit?->name,
                'metadata' => !empty($metadata) ? self::sanitizeMetadata($metadata) : null,
            ];

            // Se c'è un'entità, aggiungi i suoi dati
            if ($entity) {
                $data['entity_type'] = self::getEntityType($entity);
                $data['entity_id'] = $entity->getKey();
                $data['entity_name'] = self::getEntityName($entity);
                
                // Traccia l'unità del record modificato (affected)
                $affectedUnitId = self::getEntityUnitId($entity);
                $affectedUnitName = self::getEntityUnitName($entity);
                if ($affectedUnitId) {
                    $data['affected_unit_id'] = $affectedUnitId;
                    $data['affected_unit_name'] = $affectedUnitName;
                }
            }

            return AuditLog::create($data);
            
        } catch (\Exception $e) {
            // Non far fallire l'operazione principale se il logging fallisce
            Log::warning('AuditService: Errore durante il logging', [
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Registra un accesso riuscito.
     */
    public static function logLogin(User $user): ?AuditLog
    {
        return self::log(
            'login',
            "Accesso al sistema da parte di {$user->name}",
            $user
        );
    }

    /**
     * Registra un tentativo di accesso fallito.
     */
    public static function logLoginFailed(string $username, string $reason = 'Credenziali non valide'): ?AuditLog
    {
        try {
            $request = Request::instance();

            return AuditLog::create([
                'user_id' => null,
                'user_name' => $username,
                'action' => 'login_failed',
                'description' => "Tentativo di accesso fallito per '{$username}': {$reason}",
                'status' => 'failed',
                'ip_address' => $request->ip(),
                'user_agent' => self::truncateUserAgent($request->userAgent()),
                'url' => self::truncateUrl($request->fullUrl()),
                'method' => $request->method(),
                'metadata' => ['username' => $username, 'reason' => $reason],
            ]);
        } catch (\Exception $e) {
            Log::warning('AuditService: Errore durante il logging login fallito', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Registra un logout.
     */
    public static function logLogout(?User $user = null): ?AuditLog
    {
        $user = $user ?? Auth::user();
        
        $userName = $user ? $user->name : 'Utente sconosciuto';
        
        return self::log(
            'logout',
            "Uscita dal sistema di {$userName}",
            $user
        );
    }

    /**
     * Registra la creazione di un'entità.
     */
    public static function logCreate(Model $entity, ?string $description = null): ?AuditLog
    {
        $entityName = self::getEntityName($entity);
        $entityLabel = self::getEntityLabel($entity);
        
        // Sanitizza i valori dell'entità per il logging
        $entityValues = self::sanitizeValues(
            self::filterExcludedFields($entity->toArray())
        );
        
        return self::log(
            'create',
            $description ?? "Creato {$entityLabel}: {$entityName}",
            $entity,
            ['new_values' => $entityValues]
        );
    }

    /**
     * Registra la modifica di un'entità.
     */
    public static function logUpdate(
        Model $entity,
        array $oldValues = [],
        array $newValues = [],
        ?string $description = null
    ): ?AuditLog {
        $entityName = self::getEntityName($entity);
        $entityLabel = self::getEntityLabel($entity);
        
        // Filtra campi non significativi
        $oldValues = self::filterExcludedFields($oldValues);
        $newValues = self::filterExcludedFields($newValues);
        
        // Filtra solo i campi effettivamente modificati
        $changes = self::getChangedFields($oldValues, $newValues);
        
        // Se non ci sono modifiche significative, non loggare
        if (empty($changes)) {
            return null;
        }
        
        $log = self::log(
            'update',
            $description ?? "Modificato {$entityLabel}: {$entityName}",
            $entity,
            ['changed_fields' => array_keys($changes)]
        );

        if ($log) {
            // Sanitizza e salva i valori vecchi e nuovi
            $log->old_values = self::sanitizeValues($oldValues);
            $log->new_values = self::sanitizeValues($newValues);
            $log->save();
        }

        return $log;
    }

    /**
     * Registra l'eliminazione di un'entità.
     */
    public static function logDelete(Model $entity, ?string $description = null): ?AuditLog
    {
        $entityName = self::getEntityName($entity);
        $entityLabel = self::getEntityLabel($entity);
        
        // Sanitizza i dati dell'entità eliminata
        $deletedData = self::sanitizeValues(
            self::filterExcludedFields($entity->toArray())
        );
        
        return self::log(
            'delete',
            $description ?? "Eliminato {$entityLabel}: {$entityName}",
            $entity,
            ['deleted_data' => $deletedData]
        );
    }

    /**
     * Registra un cambio di password.
     */
    public static function logPasswordChange(User $user): ?AuditLog
    {
        return self::log(
            'password_change',
            "Password modificata per l'utente {$user->name}",
            $user
        );
    }

    /**
     * Registra una modifica ai permessi.
     */
    public static function logPermissionChange(
        User $user,
        array $oldPermissions,
        array $newPermissions
    ): ?AuditLog {
        $log = self::log(
            'permission_change',
            "Permessi modificati per l'utente {$user->name}",
            $user
        );

        if ($log) {
            $log->old_values = ['permissions' => $oldPermissions];
            $log->new_values = ['permissions' => $newPermissions];
            $log->save();
        }

        return $log;
    }

    /**
     * Registra un'esportazione di dati.
     */
    public static function logExport(string $type, int $count, ?string $description = null): ?AuditLog
    {
        return self::log(
            'export',
            $description ?? "Esportati {$count} record di tipo '{$type}'",
            null,
            ['export_type' => $type, 'record_count' => $count]
        );
    }

    /**
     * Registra un'importazione di dati.
     */
    public static function logImport(string $type, int $count, ?string $description = null): ?AuditLog
    {
        return self::log(
            'import',
            $description ?? "Importati {$count} record di tipo '{$type}'",
            null,
            ['import_type' => $type, 'record_count' => $count]
        );
    }

    /**
     * Registra la visualizzazione di un'entità sensibile.
     */
    public static function logView(Model $entity, ?string $description = null): ?AuditLog
    {
        $entityName = self::getEntityName($entity);
        $entityLabel = self::getEntityLabel($entity);
        
        return self::log(
            'view',
            $description ?? "Visualizzato {$entityLabel}: {$entityName}",
            $entity
        );
    }

    // =========================================================================
    // METODI HELPER
    // =========================================================================

    /**
     * Ottiene il tipo di entità dal Model.
     */
    protected static function getEntityType(Model $entity): string
    {
        $class = class_basename($entity);
        
        $mapping = [
            'Militare' => 'militare',
            'User' => 'user',
            'Compagnia' => 'compagnia',
            'ScadenzaMilitare' => 'scadenza',
            'ServizioTurno' => 'servizio',
            'AssegnazioneTurno' => 'turno',
            'BoardActivity' => 'attivita',
            'ConfigurazioneRuolino' => 'ruolino',
            'Role' => 'permesso',
            'Permission' => 'permesso',
        ];

        return $mapping[$class] ?? strtolower($class);
    }

    /**
     * Ottiene il nome leggibile dell'entità.
     */
    protected static function getEntityName(Model $entity): string
    {
        // Prova diversi attributi comuni
        $nameFields = ['name', 'nome', 'title', 'titolo', 'cognome', 'username', 'codice'];
        
        foreach ($nameFields as $field) {
            if (isset($entity->$field) && !empty($entity->$field)) {
                // Per i militari, usa cognome + nome
                if ($field === 'cognome' && isset($entity->nome)) {
                    return "{$entity->cognome} {$entity->nome}";
                }
                return $entity->$field;
            }
        }

        // Fallback: usa l'ID
        return "ID: {$entity->getKey()}";
    }

    /**
     * Ottiene l'etichetta leggibile del tipo di entità.
     */
    protected static function getEntityLabel(Model $entity): string
    {
        $type = self::getEntityType($entity);
        return AuditLog::ENTITY_LABELS[$type] ?? ucfirst($type);
    }

    /**
     * Ottiene l'ID dell'unità organizzativa dell'entità (se disponibile).
     */
    protected static function getEntityUnitId(Model $entity): ?int
    {
        // Prima verifica organizational_unit_id diretto
        if (isset($entity->organizational_unit_id)) {
            return $entity->organizational_unit_id;
        }

        // Poi verifica relazione organizationalUnit
        if (method_exists($entity, 'organizationalUnit')) {
            $unit = $entity->organizationalUnit;
            return $unit?->id;
        }

        return null;
    }

    /**
     * Ottiene il nome dell'unità organizzativa dell'entità (se disponibile).
     */
    protected static function getEntityUnitName(Model $entity): ?string
    {
        // Prima verifica se ha relazione caricata
        if (method_exists($entity, 'organizationalUnit')) {
            $unit = $entity->organizationalUnit;
            return $unit?->name;
        }

        // Altrimenti carica dall'ID
        if (isset($entity->organizational_unit_id) && $entity->organizational_unit_id) {
            $unit = \App\Models\OrganizationalUnit::find($entity->organizational_unit_id);
            return $unit?->name;
        }

        return null;
    }

    /**
     * Trova i campi che sono stati modificati.
     */
    protected static function getChangedFields(array $old, array $new): array
    {
        $changes = [];
        
        foreach ($new as $key => $value) {
            // Salta campi esclusi
            if (in_array($key, self::EXCLUDED_FIELDS)) {
                continue;
            }
            
            if (!array_key_exists($key, $old) || $old[$key] !== $value) {
                $changes[$key] = [
                    'old' => $old[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        return $changes;
    }

    /**
     * Pulisce i log più vecchi di X giorni.
     * Da chiamare periodicamente (es. con un comando Artisan schedulato).
     */
    public static function cleanOldLogs(int $days = 365): int
    {
        return AuditLog::where('created_at', '<', now()->subDays($days))->delete();
    }
    
    // =========================================================================
    // METODI HELPER PER OTTIMIZZAZIONE
    // =========================================================================
    
    /**
     * Genera una chiave univoca per prevenire log duplicati.
     */
    protected static function generateLogKey(string $action, ?Model $entity): string
    {
        $userId = Auth::id() ?? 'guest';
        $entityKey = $entity ? (self::getEntityType($entity) . '_' . $entity->getKey()) : 'none';
        $timestamp = now()->format('Y-m-d_H:i:s');
        
        return "{$userId}_{$action}_{$entityKey}_{$timestamp}";
    }
    
    /**
     * Verifica se un log è duplicato (stesso utente, stessa azione, stesso secondo).
     */
    protected static function isDuplicateLog(string $logKey): bool
    {
        // Pulisci cache vecchia (oltre 5 secondi)
        $now = time();
        foreach (self::$recentLogs as $key => $timestamp) {
            if ($now - $timestamp > 5) {
                unset(self::$recentLogs[$key]);
            }
        }
        
        // Controlla se esiste già
        if (isset(self::$recentLogs[$logKey])) {
            return true;
        }
        
        // Aggiungi alla cache
        self::$recentLogs[$logKey] = $now;
        return false;
    }
    
    /**
     * Filtra i campi da escludere dai valori loggati.
     */
    public static function filterExcludedFields(array $values): array
    {
        return array_diff_key($values, array_flip(self::EXCLUDED_FIELDS));
    }
    
    /**
     * Sanitizza i metadati per evitare dati troppo grandi.
     */
    protected static function sanitizeMetadata(array $metadata): array
    {
        $sanitized = [];
        
        foreach ($metadata as $key => $value) {
            // Salta campi esclusi
            if (in_array($key, self::EXCLUDED_FIELDS)) {
                continue;
            }
            
            // Limita stringhe molto lunghe
            if (is_string($value) && strlen($value) > 1000) {
                $sanitized[$key] = substr($value, 0, 1000) . '... [troncato]';
            } 
            // Limita array molto grandi
            elseif (is_array($value) && count($value) > 50) {
                $sanitized[$key] = array_slice($value, 0, 50);
                $sanitized[$key . '_truncated'] = true;
            }
            else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Tronca la descrizione solo se supera 10000 caratteri (per evitare valori eccessivamente lunghi).
     * Ora supporta descrizioni complete e dettagliate.
     */
    protected static function truncateDescription(string $description): string
    {
        return strlen($description) > 10000 
            ? substr($description, 0, 9997) . '...' 
            : $description;
    }
    
    /**
     * Tronca l'user agent.
     */
    protected static function truncateUserAgent(?string $userAgent): ?string
    {
        if (!$userAgent) return null;
        return strlen($userAgent) > 255 ? substr($userAgent, 0, 255) : $userAgent;
    }
    
    /**
     * Tronca l'URL.
     */
    protected static function truncateUrl(?string $url): ?string
    {
        if (!$url) return null;
        return strlen($url) > 255 ? substr($url, 0, 255) : $url;
    }
    
    /**
     * Sanitizza i valori old/new per il logging.
     * Rimuove campi non significativi e limita dimensioni.
     */
    public static function sanitizeValues(array $values): array
    {
        $sanitized = [];
        
        foreach ($values as $key => $value) {
            // Salta campi esclusi
            if (in_array($key, self::EXCLUDED_FIELDS)) {
                continue;
            }
            
            // Salta valori null o vuoti
            if ($value === null || $value === '') {
                continue;
            }
            
            // Limita stringhe molto lunghe
            if (is_string($value) && strlen($value) > 500) {
                $sanitized[$key] = substr($value, 0, 500) . '...';
            }
            // Converti date in formato leggibile
            elseif ($value instanceof \DateTimeInterface) {
                $sanitized[$key] = $value->format('d/m/Y H:i:s');
            }
            else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
}
