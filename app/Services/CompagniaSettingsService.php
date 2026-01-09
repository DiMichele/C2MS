<?php

namespace App\Services;

use App\Models\CompagniaSetting;
use App\Models\ConfigurazioneRuolino;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SUGECO: Service per la gestione delle impostazioni di compagnia
 * 
 * Questo service è il "Single Source of Truth" per:
 * - Regole ruolini (quali servizi contano come assenza/presenza)
 * - Stato di default (presente/assente)
 * - Future estensioni di configurazione per compagnia
 * 
 * ARCHITETTURA:
 * - Context-aware: può essere istanziato per User o per Compagnia
 * - Cache-enabled: usa Cache::remember per evitare stampede
 * - Audit-logged: traccia tutte le modifiche
 * 
 * UTILIZZO:
 *   // In controller web:
 *   $service = CompagniaSettingsService::forCurrentUser();
 *   
 *   // In job/command:
 *   $service = CompagniaSettingsService::forCompagnia($compagniaId);
 *   $service = CompagniaSettingsService::forUser($user);
 * 
 * @package App\Services
 * @version 2.0
 * @author Michele Di Gennaro
 */
class CompagniaSettingsService
{
    private const CACHE_KEY_RUOLINI = 'compagnia_ruolini_rules_';
    private const CACHE_TTL = 3600; // 1 ora

    protected ?int $compagniaId = null;
    protected ?User $user = null;
    protected ?array $ruoliniRulesCache = null;

    /**
     * Costruttore privato - usa i factory methods
     */
    public function __construct(?int $compagniaId = null, ?User $user = null)
    {
        $this->compagniaId = $compagniaId;
        $this->user = $user;
    }

    // ==========================================
    // FACTORY METHODS
    // ==========================================

    /**
     * Crea un'istanza per l'utente corrente autenticato
     */
    public static function forCurrentUser(): self
    {
        $user = auth()->user();
        
        if (!$user) {
            return new self(null, null);
        }

        return new self($user->compagnia_id, $user);
    }

    /**
     * Crea un'istanza per un utente specifico
     */
    public static function forUser(User $user): self
    {
        return new self($user->compagnia_id, $user);
    }

    /**
     * Crea un'istanza per una compagnia specifica (per job/command)
     */
    public static function forCompagnia(int $compagniaId): self
    {
        return new self($compagniaId, null);
    }

    // ==========================================
    // GETTERS
    // ==========================================

    /**
     * Ottiene l'ID della compagnia corrente
     */
    public function getCompagniaId(): ?int
    {
        return $this->compagniaId;
    }

    /**
     * Verifica se il service ha un contesto valido
     */
    public function hasValidContext(): bool
    {
        return $this->compagniaId !== null;
    }

    // ==========================================
    // REGOLE RUOLINI
    // ==========================================

    /**
     * Ottiene tutte le regole ruolini per la compagnia corrente
     * 
     * Usa Cache::remember per prevenire cache stampede.
     * 
     * @return array [
     *   'default_stato' => 'presente'|'assente',
     *   'servizio_states' => [tipo_servizio_id => 'presente'|'assente', ...],
     * ]
     */
    public function getRuoliniRules(): array
    {
        if (!$this->hasValidContext()) {
            return $this->getDefaultRules();
        }

        // Usa cache in memoria se già caricato
        if ($this->ruoliniRulesCache !== null) {
            return $this->ruoliniRulesCache;
        }

        $cacheKey = self::CACHE_KEY_RUOLINI . $this->compagniaId;

        // Cache::remember previene cache stampede
        $this->ruoliniRulesCache = Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return $this->loadRuoliniRulesFromDb();
        });

        return $this->ruoliniRulesCache;
    }

    /**
     * Carica le regole dal database
     */
    protected function loadRuoliniRulesFromDb(): array
    {
        // Carica le impostazioni generali
        $settings = CompagniaSetting::where('compagnia_id', $this->compagniaId)->first();
        $defaultStato = $settings?->getDefaultStato() ?? 'assente';

        // Carica tutte le configurazioni specifiche per servizio
        // in una mappa per lookup O(1)
        $configurazioni = ConfigurazioneRuolino::where('compagnia_id', $this->compagniaId)
            ->get()
            ->keyBy('tipo_servizio_id')
            ->map(fn($config) => $config->stato_presenza)
            ->toArray();

        return [
            'default_stato' => $defaultStato,
            'servizio_states' => $configurazioni,
        ];
    }

    /**
     * Restituisce regole di default (nessuna compagnia)
     */
    protected function getDefaultRules(): array
    {
        return [
            'default_stato' => 'assente',
            'servizio_states' => [],
        ];
    }

    /**
     * Verifica se un tipo servizio conta come assente
     * 
     * @param int $tipoServizioId
     * @return bool
     */
    public function isAssente(int $tipoServizioId): bool
    {
        $rules = $this->getRuoliniRules();
        
        // Se esiste configurazione specifica, usala
        if (isset($rules['servizio_states'][$tipoServizioId])) {
            return $rules['servizio_states'][$tipoServizioId] === 'assente';
        }

        // Altrimenti usa il default
        return $rules['default_stato'] === 'assente';
    }

    /**
     * Verifica se un tipo servizio conta come presente
     * 
     * @param int $tipoServizioId
     * @return bool
     */
    public function isPresente(int $tipoServizioId): bool
    {
        $rules = $this->getRuoliniRules();
        
        // Se esiste configurazione specifica, usala
        if (isset($rules['servizio_states'][$tipoServizioId])) {
            return $rules['servizio_states'][$tipoServizioId] === 'presente';
        }

        // Altrimenti usa il default
        return $rules['default_stato'] === 'presente';
    }

    /**
     * Ottiene lo stato per un tipo servizio specifico
     * 
     * @param int $tipoServizioId
     * @return string 'presente'|'assente'
     */
    public function getStatoForServizio(int $tipoServizioId): string
    {
        $rules = $this->getRuoliniRules();
        
        return $rules['servizio_states'][$tipoServizioId] 
            ?? $rules['default_stato'];
    }

    /**
     * Ottiene lo stato di default della compagnia
     */
    public function getDefaultStato(): string
    {
        $rules = $this->getRuoliniRules();
        return $rules['default_stato'];
    }

    // ==========================================
    // AGGIORNAMENTO CONFIGURAZIONI
    // ==========================================

    /**
     * Aggiorna la configurazione per un tipo servizio
     * 
     * Se stato_presenza è 'default', rimuove l'override (usa default).
     * 
     * @param int $tipoServizioId
     * @param string $statoPresenza 'presente'|'assente'|'default'
     * @param string|null $note
     */
    public function updateServizioConfig(int $tipoServizioId, string $statoPresenza, ?string $note = null): void
    {
        if (!$this->hasValidContext()) {
            throw new \RuntimeException('CompagniaSettingsService: nessun contesto compagnia valido');
        }

        // Ottieni stato precedente per audit
        $oldConfig = ConfigurazioneRuolino::where('compagnia_id', $this->compagniaId)
            ->where('tipo_servizio_id', $tipoServizioId)
            ->first();
        $oldStato = $oldConfig?->stato_presenza ?? 'default';

        // Se 'default', elimina l'override
        if ($statoPresenza === 'default') {
            $this->removeServizioConfigInternal($tipoServizioId, $oldStato);
            return;
        }

        // Altrimenti crea/aggiorna
        DB::transaction(function () use ($tipoServizioId, $statoPresenza, $note) {
            ConfigurazioneRuolino::updateOrCreate(
                [
                    'compagnia_id' => $this->compagniaId,
                    'tipo_servizio_id' => $tipoServizioId,
                ],
                [
                    'stato_presenza' => $statoPresenza,
                    'note' => $note,
                ]
            );
        });

        // Invalida cache DOPO commit
        $this->invalidateCache();

        // Audit log
        $this->logAudit('update_servizio_config', [
            'tipo_servizio_id' => $tipoServizioId,
            'old_stato' => $oldStato,
            'new_stato' => $statoPresenza,
            'note' => $note,
        ]);
    }

    /**
     * Rimuove la configurazione override per un tipo servizio
     */
    protected function removeServizioConfigInternal(int $tipoServizioId, string $oldStato): void
    {
        DB::transaction(function () use ($tipoServizioId) {
            ConfigurazioneRuolino::where('compagnia_id', $this->compagniaId)
                ->where('tipo_servizio_id', $tipoServizioId)
                ->delete();
        });

        // Invalida cache DOPO commit
        $this->invalidateCache();

        // Audit log
        $this->logAudit('remove_servizio_config', [
            'tipo_servizio_id' => $tipoServizioId,
            'old_stato' => $oldStato,
            'new_stato' => 'default',
        ]);
    }

    /**
     * Rimuove la configurazione override per un tipo servizio (public)
     */
    public function removeServizioConfig(int $tipoServizioId): void
    {
        $oldConfig = ConfigurazioneRuolino::where('compagnia_id', $this->compagniaId)
            ->where('tipo_servizio_id', $tipoServizioId)
            ->first();
        $oldStato = $oldConfig?->stato_presenza ?? 'default';

        $this->removeServizioConfigInternal($tipoServizioId, $oldStato);
    }

    /**
     * Aggiorna lo stato di default della compagnia
     */
    public function updateDefaultStato(string $stato): void
    {
        if (!$this->hasValidContext()) {
            throw new \RuntimeException('CompagniaSettingsService: nessun contesto compagnia valido');
        }

        if (!in_array($stato, ['presente', 'assente'])) {
            throw new \InvalidArgumentException('Stato deve essere "presente" o "assente"');
        }

        $settings = CompagniaSetting::getOrCreateForCompagnia($this->compagniaId);
        $oldStato = $settings->getDefaultStato();

        DB::transaction(function () use ($settings, $stato) {
            $settings->setDefaultStato($stato);
            $settings->save();
        });

        // Invalida cache DOPO commit
        $this->invalidateCache();

        // Audit log
        $this->logAudit('update_default_stato', [
            'old_stato' => $oldStato,
            'new_stato' => $stato,
        ]);
    }

    /**
     * Aggiorna multiple configurazioni in batch
     */
    public function updateBatch(array $configurazioni): void
    {
        if (!$this->hasValidContext()) {
            throw new \RuntimeException('CompagniaSettingsService: nessun contesto compagnia valido');
        }

        DB::transaction(function () use ($configurazioni) {
            foreach ($configurazioni as $config) {
                $tipoServizioId = $config['tipo_servizio_id'];
                $statoPresenza = $config['stato_presenza'];
                $note = $config['note'] ?? null;

                if ($statoPresenza === 'default') {
                    // Rimuovi override
                    ConfigurazioneRuolino::where('compagnia_id', $this->compagniaId)
                        ->where('tipo_servizio_id', $tipoServizioId)
                        ->delete();
                } else {
                    // Crea/aggiorna
                    ConfigurazioneRuolino::updateOrCreate(
                        [
                            'compagnia_id' => $this->compagniaId,
                            'tipo_servizio_id' => $tipoServizioId,
                        ],
                        [
                            'stato_presenza' => $statoPresenza,
                            'note' => $note,
                        ]
                    );
                }
            }
        });

        // Invalida cache DOPO commit
        $this->invalidateCache();

        // Audit log
        $this->logAudit('update_batch', [
            'count' => count($configurazioni),
            'configurazioni' => $configurazioni,
        ]);
    }

    // ==========================================
    // CACHE MANAGEMENT
    // ==========================================

    /**
     * Invalida la cache delle regole ruolini
     */
    public function invalidateCache(): void
    {
        if ($this->compagniaId) {
            $cacheKey = self::CACHE_KEY_RUOLINI . $this->compagniaId;
            Cache::forget($cacheKey);
        }

        // Reset cache in memoria
        $this->ruoliniRulesCache = null;
    }

    /**
     * Invalida la cache per una compagnia specifica (static)
     */
    public static function invalidateCacheForCompagnia(int $compagniaId): void
    {
        $cacheKey = self::CACHE_KEY_RUOLINI . $compagniaId;
        Cache::forget($cacheKey);
    }

    // ==========================================
    // AUDIT LOGGING
    // ==========================================

    /**
     * Registra un'azione di audit
     */
    protected function logAudit(string $action, array $data = []): void
    {
        $auditData = array_merge([
            'action' => $action,
            'compagnia_id' => $this->compagniaId,
            'user_id' => $this->user?->id,
            'user_name' => $this->user?->name,
            'timestamp' => now()->toIso8601String(),
        ], $data);

        Log::info("CompagniaSettings Audit: {$action}", $auditData);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Ottiene tutte le configurazioni ruolini come collection
     */
    public function getAllConfigurations(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->hasValidContext()) {
            return collect();
        }

        return ConfigurazioneRuolino::where('compagnia_id', $this->compagniaId)
            ->with('tipoServizio')
            ->get();
    }

    /**
     * Ottiene le impostazioni della compagnia
     */
    public function getSettings(): ?CompagniaSetting
    {
        if (!$this->hasValidContext()) {
            return null;
        }

        return CompagniaSetting::getOrCreateForCompagnia($this->compagniaId);
    }
}
