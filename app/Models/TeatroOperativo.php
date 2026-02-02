<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Traits\BelongsToOrganizationalUnit;

/**
 * Modello per i Teatri Operativi
 * 
 * Rappresenta un Teatro Operativo (es. Kosovo, Libano, etc.)
 * a cui possono essere assegnati i militari con stato bozza/confermato.
 * 
 * @property int $id
 * @property string $nome
 * @property string|null $codice
 * @property string|null $descrizione
 * @property \Carbon\Carbon|null $data_inizio
 * @property \Carbon\Carbon|null $data_fine
 * @property string $stato
 * @property string $colore_badge
 * @property int|null $compagnia_id
 * @property int|null $mounting_compagnia_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Militare[] $militari
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TeatroOperativoMilitare[] $assegnazioni
 */
class TeatroOperativo extends Model
{
    use HasFactory, BelongsToOrganizationalUnit;

    /**
     * Nome della tabella associata al modello
     */
    protected $table = 'teatri_operativi';

    /**
     * Attributi assegnabili in massa
     */
    protected $fillable = [
        'organizational_unit_id',
        'nome',
        'codice',
        'descrizione',
        'data_inizio',
        'data_fine',
        'stato',
        'colore_badge',
        'config_colonne',
        'compagnia_id',
        'mounting_compagnia_id'
    ];

    /**
     * Attributi che devono essere convertiti in tipi nativi
     */
    protected $casts = [
        'data_inizio' => 'date',
        'data_fine' => 'date',
        'config_colonne' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Militari assegnati al teatro operativo
     */
    public function militari()
    {
        return $this->belongsToMany(
            Militare::class,
            'teatro_operativo_militare',
            'teatro_operativo_id',
            'militare_id'
        )->withPivot(['stato', 'ruolo', 'note', 'data_assegnazione', 'data_fine_assegnazione'])
         ->withTimestamps();
    }

    /**
     * Militari confermati nel teatro operativo
     */
    public function militariConfermati()
    {
        return $this->militari()->wherePivot('stato', 'confermato');
    }

    /**
     * Militari in bozza nel teatro operativo
     */
    public function militariBozza()
    {
        return $this->militari()->wherePivot('stato', 'bozza');
    }

    /**
     * Assegnazioni dettagliate militare-teatro
     */
    public function assegnazioni()
    {
        return $this->hasMany(TeatroOperativoMilitare::class);
    }

    /**
     * Compagnia proprietaria
     */
    public function compagnia()
    {
        return $this->belongsTo(Compagnia::class);
    }

    /**
     * Compagnia di Mounting (dove i militari appariranno nel CPT/Anagrafica)
     */
    public function mountingCompagnia()
    {
        return $this->belongsTo(Compagnia::class, 'mounting_compagnia_id');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per teatri attivi
     */
    public function scopeAttivi($query)
    {
        return $query->where('stato', 'attivo');
    }

    /**
     * Scope per teatri in corso (con date)
     */
    public function scopeInCorso($query)
    {
        $oggi = Carbon::today();
        return $query->where('stato', 'attivo')
                    ->where(function($q) use ($oggi) {
                        $q->whereNull('data_inizio')
                          ->orWhere('data_inizio', '<=', $oggi);
                    })
                    ->where(function($q) use ($oggi) {
                        $q->whereNull('data_fine')
                          ->orWhere('data_fine', '>=', $oggi);
                    });
    }

    /**
     * Scope per ordinare per nome
     */
    public function scopePerNome($query)
    {
        return $query->orderBy('nome');
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Verifica se il teatro è attivo
     */
    public function isAttivo()
    {
        return $this->stato === 'attivo';
    }

    /**
     * Verifica se TUTTI i militari assegnati sono confermati
     * (questo rende il teatro "confermato")
     */
    public function isTuttiConfermati()
    {
        if ($this->militari()->count() === 0) {
            return false;
        }
        
        return $this->militariBozza()->count() === 0;
    }

    /**
     * Ottiene lo stato globale del teatro
     * 'confermato' se tutti i militari sono confermati
     * 'bozza' se almeno un militare è in bozza
     * 'vuoto' se non ci sono militari
     */
    public function getStatoGlobale()
    {
        $totale = $this->militari()->count();
        
        if ($totale === 0) {
            return 'vuoto';
        }
        
        return $this->isTuttiConfermati() ? 'confermato' : 'bozza';
    }

    /**
     * Ottiene il numero di militari assegnati
     */
    public function getNumeroMilitari()
    {
        return $this->militari()->count();
    }

    /**
     * Ottiene il numero di militari confermati
     */
    public function getNumeroConfermati()
    {
        return $this->militariConfermati()->count();
    }

    /**
     * Ottiene il numero di militari in bozza
     */
    public function getNumeroBozza()
    {
        return $this->militariBozza()->count();
    }

    /**
     * Ottiene la percentuale di conferma
     */
    public function getPercentualeConferma()
    {
        $totale = $this->getNumeroMilitari();
        if ($totale === 0) {
            return 0;
        }
        return round(($this->getNumeroConfermati() / $totale) * 100);
    }

    /**
     * Ottiene la durata del teatro in giorni
     */
    public function getDurataGiorni()
    {
        if (!$this->data_inizio || !$this->data_fine) {
            return null;
        }
        return $this->data_inizio->diffInDays($this->data_fine) + 1;
    }

    /**
     * Ottiene il periodo formattato
     */
    public function getPeriodoFormattato()
    {
        if (!$this->data_inizio && !$this->data_fine) {
            return 'Permanente';
        }

        $inizio = $this->data_inizio ? $this->data_inizio->format('d/m/Y') : 'Inizio indefinito';
        $fine = $this->data_fine ? $this->data_fine->format('d/m/Y') : 'Fine indefinita';

        return "{$inizio} - {$fine}";
    }

    /**
     * Ottiene la classe CSS per lo stato
     */
    public function getStatoCssClass()
    {
        return match($this->stato) {
            'attivo' => 'bg-success',
            'completato' => 'bg-secondary',
            'sospeso' => 'bg-warning',
            'pianificato' => 'bg-info',
            default => 'bg-light'
        };
    }

    /**
     * Ottiene la classe CSS per lo stato globale (tutti confermati vs bozze)
     */
    public function getStatoGlobaleCssClass()
    {
        return match($this->getStatoGlobale()) {
            'confermato' => 'stato-confermato',
            'bozza' => 'stato-bozza',
            default => 'stato-vuoto'
        };
    }

    /**
     * Rappresentazione testuale
     */
    public function __toString()
    {
        return $this->nome;
    }

    // ==========================================
    // RELAZIONI PRENOTAZIONI
    // ==========================================

    /**
     * Prenotazioni per questo teatro operativo
     */
    public function prenotazioni()
    {
        return $this->hasMany(PrenotazioneApprontamento::class, 'teatro_operativo_id');
    }

    // ==========================================
    // GESTIONE CONFIGURAZIONE COLONNE
    // ==========================================

    /**
     * Verifica se una colonna è visibile per questo teatro
     * Se non c'è configurazione, la colonna è visibile di default
     */
    public function isColonnaVisibile(string $colonna): bool
    {
        $config = $this->config_colonne;
        
        // Se non c'è configurazione, tutte le colonne sono visibili
        if (empty($config) || !isset($config[$colonna])) {
            return true;
        }
        
        return $config[$colonna]['visibile'] ?? true;
    }

    /**
     * Verifica se una colonna è richiesta per questo teatro
     * Se non c'è configurazione, la colonna è richiesta di default
     */
    public function isColonnaRichiesta(string $colonna): bool
    {
        $config = $this->config_colonne;
        
        // Se non c'è configurazione, tutte le colonne sono richieste
        if (empty($config) || !isset($config[$colonna])) {
            return true;
        }
        
        return $config[$colonna]['richiesta'] ?? true;
    }

    /**
     * Ottiene le colonne visibili per questo teatro
     */
    public function getColonneVisibili(array $tutteLeColonne): array
    {
        return array_filter($tutteLeColonne, function($config, $colonna) {
            return $this->isColonnaVisibile($colonna);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Ottiene le colonne richieste per questo teatro
     */
    public function getColonneRichieste(array $tutteLeColonne): array
    {
        return array_filter($tutteLeColonne, function($config, $colonna) {
            return $this->isColonnaVisibile($colonna) && $this->isColonnaRichiesta($colonna);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Imposta la configurazione per una colonna
     */
    public function setConfigColonna(string $colonna, bool $visibile = true, bool $richiesta = true): void
    {
        $config = $this->config_colonne ?? [];
        $config[$colonna] = [
            'visibile' => $visibile,
            'richiesta' => $richiesta
        ];
        $this->config_colonne = $config;
    }

    /**
     * Rimuove la configurazione per una colonna (torna ai default)
     */
    public function rimuoviConfigColonna(string $colonna): void
    {
        $config = $this->config_colonne ?? [];
        unset($config[$colonna]);
        $this->config_colonne = empty($config) ? null : $config;
    }

    /**
     * Resetta tutta la configurazione delle colonne
     */
    public function resetConfigColonne(): void
    {
        $this->config_colonne = null;
    }
}
