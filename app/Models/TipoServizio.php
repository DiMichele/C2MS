<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Modello per i tipi di servizio
 * 
 * Rappresenta i diversi tipi di servizio che possono essere assegnati
 * ai militari nei calendari giornalieri (TO, lo, S-UI, p, etc.)
 * 
 * NOTA: Questo modello NON usa BelongsToOrganizationalUnit standard
 * perché i tipi servizio sono configurazioni ereditate dalla gerarchia.
 * Un'unità subordinata deve vedere i tipi della propria macro-entità.
 * 
 * @property int $id
 * @property string $codice
 * @property string $nome
 * @property string|null $descrizione
 * @property string $colore_badge
 * @property string $categoria
 * @property bool $attivo
 * @property int $ordine
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Presenza[] $presenze
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PianificazioneGiornaliera[] $pianificazioni
 */
class TipoServizio extends Model
{
    use HasFactory;
    
    /**
     * Boot del modello: applica scope personalizzato per ereditarietà configurazioni
     */
    protected static function booted(): void
    {
        static::addGlobalScope('inheritedConfig', function ($builder) {
            $activeUnitId = activeUnitId();
            
            if (!$activeUnitId) {
                return;
            }
            
            // Per le configurazioni, mostra sia i tipi dell'unità attiva 
            // che quelli degli ANCESTOR (macro-entità parent)
            $unitIds = self::getUnitWithAncestors($activeUnitId);
            
            $builder->whereIn('organizational_unit_id', $unitIds);
        });
    }
    
    /**
     * Ottiene l'unità attiva + tutti i suoi ancestor (per ereditarietà configurazioni)
     */
    protected static function getUnitWithAncestors(int $unitId): array
    {
        try {
            // Usa la closure table per ottenere tutti gli ancestor
            $ancestorIds = DB::table('unit_closure')
                ->where('descendant_id', $unitId)
                ->pluck('ancestor_id')
                ->toArray();
            
            // Assicurati che l'unità stessa sia inclusa
            return array_unique(array_merge([$unitId], $ancestorIds));
        } catch (\Exception $e) {
            return [$unitId];
        }
    }
    
    /**
     * Relazione con l'unità organizzativa
     */
    public function organizationalUnit()
    {
        return $this->belongsTo(OrganizationalUnit::class, 'organizational_unit_id');
    }

    /**
     * Nome della tabella associata al modello
     */
    protected $table = 'tipi_servizio';

    /**
     * Attributi assegnabili in massa
     */
    protected $fillable = [
        'organizational_unit_id',
        'codice',
        'nome',
        'descrizione',
        'colore_badge',
        'categoria',
        'attivo',
        'ordine'
    ];

    /**
     * Attributi che devono essere convertiti in tipi nativi
     */
    protected $casts = [
        'attivo' => 'boolean',
        'ordine' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Presenze associate a questo tipo di servizio
     */
    public function presenze()
    {
        return $this->hasMany(Presenza::class, 'tipo_servizio_id');
    }

    /**
     * Pianificazioni giornaliere associate
     */
    public function pianificazioni()
    {
        return $this->hasMany(PianificazioneGiornaliera::class, 'tipo_servizio_id');
    }

    /**
     * Codice gerarchia associato
     */
    public function codiceGerarchia()
    {
        return $this->belongsTo(CodiciServizioGerarchia::class, 'codice_gerarchia_id');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per tipi di servizio attivi
     */
    public function scopeAttivi($query)
    {
        return $query->where('attivo', true);
    }

    /**
     * Scope per categoria specifica
     */
    public function scopePerCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Scope ordinati per ordine di visualizzazione
     */
    public function scopeOrdinati($query)
    {
        return $query->orderBy('ordine')->orderBy('nome');
    }

    /**
     * Scope per codice specifico
     */
    public function scopePerCodice($query, $codice)
    {
        return $query->where('codice', $codice);
    }

    // ==========================================
    // METODI STATICI
    // ==========================================

    /**
     * Ottiene tutte le categorie disponibili
     */
    public static function getCategorieDisponibili()
    {
        return [
            'servizio' => 'Servizio',
            'permesso' => 'Permesso',
            'assenza' => 'Assenza',
            'formazione' => 'Formazione',
            'missione' => 'Missione'
        ];
    }

    /**
     * Trova un tipo di servizio per codice
     */
    public static function findByCodice($codice)
    {
        return static::where('codice', $codice)->first();
    }

    /**
     * Ottiene i tipi di servizio per categoria
     */
    public static function perCategoria($categoria)
    {
        return static::where('categoria', $categoria)
                     ->attivi()
                     ->ordinati()
                     ->get();
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Verifica se il tipo di servizio è attivo
     */
    public function isAttivo()
    {
        return $this->attivo;
    }

    /**
     * Ottiene il nome della categoria
     */
    public function getNomeCategoria()
    {
        $categorie = static::getCategorieDisponibili();
        return $categorie[$this->categoria] ?? $this->categoria;
    }

    /**
     * Verifica se è un tipo di servizio
     */
    public function isServizio()
    {
        return $this->categoria === 'servizio';
    }

    /**
     * Verifica se è un permesso
     */
    public function isPermesso()
    {
        return $this->categoria === 'permesso';
    }

    /**
     * Verifica se è un'assenza
     */
    public function isAssenza()
    {
        return $this->categoria === 'assenza';
    }

    /**
     * Verifica se è formazione
     */
    public function isFormazione()
    {
        return $this->categoria === 'formazione';
    }

    /**
     * Verifica se è una missione
     */
    public function isMissione()
    {
        return $this->categoria === 'missione';
    }

    /**
     * Ottiene la classe CSS per la categoria
     */
    public function getCategoriaCssClass()
    {
        return match($this->categoria) {
            'servizio' => 'badge-primary',
            'permesso' => 'badge-info',
            'assenza' => 'badge-warning',
            'formazione' => 'badge-success',
            'missione' => 'badge-danger',
            default => 'badge-secondary'
        };
    }

    /**
     * Ottiene l'icona per la categoria
     */
    public function getCategoriaIcon()
    {
        return match($this->categoria) {
            'servizio' => 'fas fa-briefcase',
            'permesso' => 'fas fa-clock',
            'assenza' => 'fas fa-user-times',
            'formazione' => 'fas fa-graduation-cap',
            'missione' => 'fas fa-plane',
            default => 'fas fa-question'
        };
    }

    /**
     * Ottiene il numero di utilizzi nelle presenze
     */
    public function getNumeroUtilizzi()
    {
        return $this->presenze()->count();
    }

    /**
     * Ottiene il numero di pianificazioni
     */
    public function getNumeroPianificazioni()
    {
        return $this->pianificazioni()->count();
    }

    /**
     * Attiva il tipo di servizio
     */
    public function attiva()
    {
        $this->attivo = true;
        return $this->save();
    }

    /**
     * Disattiva il tipo di servizio
     */
    public function disattiva()
    {
        $this->attivo = false;
        return $this->save();
    }

    /**
     * Rappresentazione testuale
     */
    public function __toString()
    {
        return "{$this->codice} - {$this->nome}";
    }
}
