<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modello per le impostazioni generali della compagnia
 * 
 * Gestisce le impostazioni configurabili per ogni compagnia,
 * incluse regole ruolini e future estensioni.
 * 
 * @property int $id
 * @property int $compagnia_id
 * @property array|null $settings Impostazioni JSON flessibili
 * @property array|null $ruolini_cache Cache delle regole ruolini
 * @property \Carbon\Carbon|null $cache_updated_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read Compagnia $compagnia
 */
class CompagniaSetting extends Model
{
    use HasFactory;

    protected $table = 'compagnia_settings';

    protected $fillable = [
        'compagnia_id',
        'settings',
        'ruolini_cache',
        'cache_updated_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'ruolini_cache' => 'array',
        'cache_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Compagnia proprietaria delle impostazioni
     */
    public function compagnia()
    {
        return $this->belongsTo(Compagnia::class);
    }

    // ==========================================
    // METODI DI ACCESSO SETTINGS
    // ==========================================

    /**
     * Ottiene un valore dalle impostazioni
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Imposta un valore nelle impostazioni
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }

    /**
     * Rimuove un valore dalle impostazioni
     */
    public function removeSetting(string $key): void
    {
        $settings = $this->settings ?? [];
        unset($settings[$key]);
        $this->settings = $settings;
    }

    // ==========================================
    // METODI SPECIFICI RUOLINI
    // ==========================================

    /**
     * Ottiene le impostazioni ruolini
     */
    public function getRuoliniSettings(): array
    {
        return $this->getSetting('ruolini', [
            'default_stato' => 'assente',
            'assenza_note' => null,
        ]);
    }

    /**
     * Imposta le configurazioni ruolini
     */
    public function setRuoliniSettings(array $settings): void
    {
        $this->setSetting('ruolini', $settings);
    }

    /**
     * Ottiene lo stato di default (presente/assente) quando non specificato
     */
    public function getDefaultStato(): string
    {
        return $this->getSetting('ruolini.default_stato', 'assente');
    }

    /**
     * Imposta lo stato di default
     */
    public function setDefaultStato(string $stato): void
    {
        $this->setSetting('ruolini.default_stato', $stato);
    }

    // ==========================================
    // GESTIONE CACHE
    // ==========================================

    /**
     * Aggiorna la cache dei ruolini
     */
    public function updateRuoliniCache(array $cache): void
    {
        $this->ruolini_cache = $cache;
        $this->cache_updated_at = now();
        $this->save();
    }

    /**
     * Invalida la cache
     */
    public function invalidateCache(): void
    {
        $this->ruolini_cache = null;
        $this->cache_updated_at = null;
        $this->save();
    }

    /**
     * Verifica se la cache è valida
     */
    public function isCacheValid(): bool
    {
        if (!$this->ruolini_cache || !$this->cache_updated_at) {
            return false;
        }

        // Cache valida per 1 ora
        return $this->cache_updated_at->gt(now()->subHour());
    }

    // ==========================================
    // METODI STATICI
    // ==========================================

    /**
     * Ottiene o crea le impostazioni per una compagnia
     */
    public static function getOrCreateForCompagnia(int $compagniaId): self
    {
        return static::firstOrCreate(
            ['compagnia_id' => $compagniaId],
            [
                'settings' => [
                    'ruolini' => [
                        'default_stato' => 'assente',
                    ],
                ],
            ]
        );
    }

    /**
     * Ottiene le impostazioni per la compagnia dell'utente autenticato
     */
    public static function getForCurrentUser(): ?self
    {
        $user = auth()->user();
        
        if (!$user || !$user->compagnia_id) {
            return null;
        }

        return static::getOrCreateForCompagnia($user->compagnia_id);
    }
}

