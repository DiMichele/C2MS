<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model per i log di audit del sistema.
 * 
 * Traccia tutte le azioni degli utenti per garantire
 * trasparenza e sicurezza nelle operazioni.
 */
class AuditLog extends Model
{
    /**
     * I campi che possono essere assegnati in massa.
     */
    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'description',
        'entity_type',
        'entity_id',
        'entity_name',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'compagnia_id',
        // Multi-tenancy: contesto unità organizzativa
        'active_unit_id',
        'active_unit_name',
        'affected_unit_id',
        'affected_unit_name',
        'status',
    ];

    /**
     * I campi che devono essere convertiti in tipi nativi.
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Etichette leggibili per le azioni.
     */
    public const ACTION_LABELS = [
        'login' => 'Accesso',
        'logout' => 'Uscita',
        'login_failed' => 'Accesso fallito',
        'create' => 'Creazione',
        'update' => 'Modifica',
        'delete' => 'Eliminazione',
        'view' => 'Visualizzazione',
        'export' => 'Esportazione',
        'import' => 'Importazione',
        'password_change' => 'Cambio password',
        'permission_change' => 'Modifica permessi',
        'other' => 'Altra azione',
    ];

    /**
     * Icone per le azioni (Font Awesome).
     */
    public const ACTION_ICONS = [
        'login' => 'fa-sign-in-alt',
        'logout' => 'fa-sign-out-alt',
        'login_failed' => 'fa-exclamation-triangle',
        'create' => 'fa-plus-circle',
        'update' => 'fa-edit',
        'delete' => 'fa-trash-alt',
        'view' => 'fa-eye',
        'export' => 'fa-file-export',
        'import' => 'fa-file-import',
        'password_change' => 'fa-key',
        'permission_change' => 'fa-user-shield',
        'other' => 'fa-cog',
    ];

    /**
     * Colori per le azioni (Bootstrap).
     */
    public const ACTION_COLORS = [
        'login' => 'success',
        'logout' => 'secondary',
        'login_failed' => 'danger',
        'create' => 'primary',
        'update' => 'info',
        'delete' => 'danger',
        'view' => 'secondary',
        'export' => 'warning',
        'import' => 'warning',
        'password_change' => 'warning',
        'permission_change' => 'purple',
        'other' => 'secondary',
    ];

    /**
     * Etichette per i tipi di entità.
     */
    public const ENTITY_LABELS = [
        'militare' => 'Militare',
        'user' => 'Utente',
        'compagnia' => 'Compagnia',
        'certificato' => 'Certificato',
        'scadenza' => 'Scadenza',
        'servizio' => 'Servizio',
        'turno' => 'Turno',
        'attivita' => 'Attività',
        'ruolino' => 'Ruolino',
        'permesso' => 'Permesso',
        'configurazione' => 'Configurazione',
        'sistema' => 'Sistema',
    ];

    // =========================================================================
    // RELAZIONI
    // =========================================================================

    /**
     * L'utente che ha eseguito l'azione.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * La compagnia dell'utente.
     */
    public function compagnia(): BelongsTo
    {
        return $this->belongsTo(Compagnia::class);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Ottiene l'etichetta leggibile dell'azione.
     */
    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }

    /**
     * Ottiene l'icona dell'azione.
     */
    public function getActionIconAttribute(): string
    {
        return self::ACTION_ICONS[$this->action] ?? 'fa-circle';
    }

    /**
     * Ottiene il colore dell'azione.
     */
    public function getActionColorAttribute(): string
    {
        return self::ACTION_COLORS[$this->action] ?? 'secondary';
    }

    /**
     * Ottiene l'etichetta del tipo di entità.
     */
    public function getEntityLabelAttribute(): string
    {
        return self::ENTITY_LABELS[$this->entity_type] ?? $this->entity_type ?? '-';
    }

    /**
     * Formatta la data in modo leggibile.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i:s');
    }

    /**
     * Formatta la data in modo relativo (es. "2 ore fa").
     */
    public function getRelativeDateAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Filtra per utente.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Filtra per tipo di azione.
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Filtra per tipo di entità.
     */
    public function scopeByEntityType($query, $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Filtra per compagnia.
     */
    public function scopeByCompagnia($query, $compagniaId)
    {
        return $query->where('compagnia_id', $compagniaId);
    }

    /**
     * Filtra per intervallo di date.
     */
    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Solo gli ultimi N giorni.
     */
    public function scopeLastDays($query, $days)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Solo accessi (login/logout).
     */
    public function scopeAccessLogs($query)
    {
        return $query->whereIn('action', ['login', 'logout', 'login_failed']);
    }

    /**
     * Solo modifiche ai dati.
     */
    public function scopeDataChanges($query)
    {
        return $query->whereIn('action', ['create', 'update', 'delete']);
    }
}
