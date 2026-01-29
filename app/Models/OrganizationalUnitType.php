<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modello per i tipi di unità organizzative.
 * 
 * Definisce i tipi di nodi che possono esistere nella gerarchia
 * (es. reggimento, battaglione, compagnia, plotone, ufficio, sezione).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $icon
 * @property string $color
 * @property int $default_depth_level
 * @property array|null $can_contain_types
 * @property array|null $settings
 * @property int $sort_order
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class OrganizationalUnitType extends Model
{
    protected $table = 'organizational_unit_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'color',
        'default_depth_level',
        'can_contain_types',
        'settings',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'can_contain_types' => 'array',
        'settings' => 'array',
        'default_depth_level' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'icon' => 'fa-building',
        'color' => '#0A2342',
        'default_depth_level' => 0,
        'sort_order' => 0,
        'is_active' => true,
    ];

    // =========================================================================
    // RELAZIONI
    // =========================================================================

    /**
     * Unità organizzative di questo tipo.
     */
    public function units(): HasMany
    {
        return $this->hasMany(OrganizationalUnit::class, 'type_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Solo tipi attivi.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Ordinati per sort_order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Filtra per codice.
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    /**
     * Filtra per livello di profondità suggerito.
     */
    public function scopeByDepthLevel(Builder $query, int $level): Builder
    {
        return $query->where('default_depth_level', $level);
    }

    // =========================================================================
    // METODI
    // =========================================================================

    /**
     * Verifica se questo tipo può contenere un altro tipo.
     */
    public function canContain(string|OrganizationalUnitType $type): bool
    {
        // Se can_contain_types è null, può contenere qualsiasi tipo
        if ($this->can_contain_types === null) {
            return true;
        }

        $typeCode = $type instanceof OrganizationalUnitType ? $type->code : $type;
        
        return in_array($typeCode, $this->can_contain_types, true);
    }

    /**
     * Ottiene i tipi che possono essere contenuti.
     */
    public function getContainableTypes(): array
    {
        if ($this->can_contain_types === null) {
            return static::active()->pluck('code')->toArray();
        }

        return $this->can_contain_types;
    }

    /**
     * Ottiene un'impostazione specifica.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Imposta un valore nelle impostazioni.
     */
    public function setSetting(string $key, mixed $value): self
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        
        return $this;
    }

    // =========================================================================
    // COSTANTI - TIPI PREDEFINITI
    // =========================================================================

    public const TYPE_REGGIMENTO = 'reggimento';
    public const TYPE_BATTAGLIONE = 'battaglione';
    public const TYPE_COMPAGNIA = 'compagnia';
    public const TYPE_PLOTONE = 'plotone';
    public const TYPE_UFFICIO = 'ufficio';
    public const TYPE_SEZIONE = 'sezione';
    public const TYPE_INFERMERIA = 'infermeria';

    /**
     * Ottiene il tipo reggimento.
     */
    public static function reggimento(): ?self
    {
        return static::byCode(self::TYPE_REGGIMENTO)->first();
    }

    /**
     * Ottiene il tipo battaglione.
     */
    public static function battaglione(): ?self
    {
        return static::byCode(self::TYPE_BATTAGLIONE)->first();
    }

    /**
     * Ottiene il tipo compagnia.
     */
    public static function compagnia(): ?self
    {
        return static::byCode(self::TYPE_COMPAGNIA)->first();
    }

    /**
     * Ottiene il tipo plotone.
     */
    public static function plotone(): ?self
    {
        return static::byCode(self::TYPE_PLOTONE)->first();
    }

    /**
     * Ottiene il tipo ufficio.
     */
    public static function ufficio(): ?self
    {
        return static::byCode(self::TYPE_UFFICIO)->first();
    }
}
