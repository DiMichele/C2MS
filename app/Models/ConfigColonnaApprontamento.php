<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ConfigColonnaApprontamento extends Model
{
    use HasFactory;

    protected $table = 'config_colonne_approntamenti';

    protected $fillable = [
        'campo_db',
        'label',
        'scadenza_mesi',
        'fonte',
        'campo_sorgente',
        'attivo',
        'ordine',
    ];

    protected $casts = [
        'scadenza_mesi' => 'integer',
        'attivo' => 'boolean',
        'ordine' => 'integer',
    ];

    /**
     * Cache key per le colonne
     */
    private const CACHE_KEY = 'config_colonne_approntamenti';
    private const CACHE_TTL = 3600; // 1 ora

    /**
     * Ottiene tutte le colonne attive ordinate
     */
    public static function getColonneAttive(): array
    {
        return Cache::remember(self::CACHE_KEY . '_attive', self::CACHE_TTL, function () {
            return self::where('attivo', true)
                ->orderBy('ordine')
                ->get()
                ->mapWithKeys(function ($colonna) {
                    return [
                        $colonna->campo_db => [
                            'label' => $colonna->label,
                            'fonte' => $colonna->fonte,
                            'campo_sorgente' => $colonna->campo_sorgente,
                            'scadenza_mesi' => $colonna->scadenza_mesi,
                        ]
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Ottiene le labels delle colonne attive
     */
    public static function getLabels(): array
    {
        return Cache::remember(self::CACHE_KEY . '_labels', self::CACHE_TTL, function () {
            return self::where('attivo', true)
                ->orderBy('ordine')
                ->pluck('label', 'campo_db')
                ->toArray();
        });
    }

    /**
     * Ottiene le colonne che provengono da scadenze_militari (condivise)
     */
    public static function getColonneCondivise(): array
    {
        return Cache::remember(self::CACHE_KEY . '_condivise', self::CACHE_TTL, function () {
            return self::where('attivo', true)
                ->where('fonte', 'scadenze_militari')
                ->pluck('campo_db')
                ->toArray();
        });
    }

    /**
     * Verifica se una colonna è condivisa con scadenze_militari
     */
    public static function isColonnaCondivisa(string $campo): bool
    {
        return in_array($campo, self::getColonneCondivise());
    }

    /**
     * Ottiene il campo sorgente per una colonna condivisa
     */
    public static function getCampoSorgente(string $campo): string
    {
        $colonna = self::where('campo_db', $campo)->first();
        return $colonna?->campo_sorgente ?? $campo;
    }

    /**
     * Ottiene la scadenza in mesi per una colonna
     */
    public static function getScadenzaMesi(string $campo): ?int
    {
        $colonna = self::where('campo_db', $campo)->first();
        return $colonna?->scadenza_mesi;
    }

    /**
     * Pulisce la cache delle colonne
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY . '_attive');
        Cache::forget(self::CACHE_KEY . '_labels');
        Cache::forget(self::CACHE_KEY . '_condivise');
    }

    /**
     * Boot del model - pulisce cache su modifiche
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            self::clearCache();
        });

        static::deleted(function () {
            self::clearCache();
        });
    }

    /**
     * Ottiene il prossimo ordine disponibile
     */
    public static function getNextOrdine(): int
    {
        return (self::max('ordine') ?? 0) + 1;
    }

    /**
     * Verifica se il campo_db esiste nella tabella scadenze_approntamenti
     */
    public static function campoEsisteInTabella(string $campo): bool
    {
        // Lista dei campi fisici nella tabella scadenze_approntamenti
        $campiEsistenti = [
            'teatro_operativo', 'bls', 'ultimo_poligono_approntamento', 'poligono',
            'tipo_poligono_da_effettuare', 'bam', 'awareness_cied', 'cied_pratico',
            'stress_management', 'elitrasporto', 'mcm', 'uas', 'ict',
            'rapporto_media', 'abuso_alcol_droga', 'training_covid',
            'rspp_4h', 'rspp_8h', 'rspp_preposti', 'passaporti',
            // Campi da scadenze_militari (non fisici in scadenze_approntamenti)
            'idoneita_to', 'lavoratore_4h', 'lavoratore_8h', 'preposto',
        ];

        return in_array($campo, $campiEsistenti);
    }

    /**
     * Formatta la scadenza per la visualizzazione
     */
    public function getScadenzaFormattataAttribute(): string
    {
        if (!$this->scadenza_mesi) {
            return '-';
        }

        if ($this->scadenza_mesi >= 12 && $this->scadenza_mesi % 12 === 0) {
            $anni = $this->scadenza_mesi / 12;
            return $anni . ' ' . ($anni === 1 ? 'anno' : 'anni');
        }

        return $this->scadenza_mesi . ' mesi';
    }
}
