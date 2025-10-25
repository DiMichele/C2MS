<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * C2MS: Gestione e Controllo Digitale a Supporto del Comando
 * Modello Militare
 * 
 * Rappresenta un militare con le sue informazioni personali, grado, assegnazioni
 * e relazioni con certificati, idoneità, presenze e valutazioni.
 * 
 * @version 1.0
 * @author Michele Di Gennaro
 * 
 * @property int $id
 * @property int|null $grado_id
 * @property string $cognome
 * @property string $nome
 * @property int|null $plotone_id
 * @property int|null $polo_id
 * @property int|null $ruolo_id
 * @property int|null $mansione_id
 * @property string|null $certificati_note
 * @property string|null $idoneita_note
 * @property string|null $note
 * @property string|null $foto_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \App\Models\Grado|null $grado
 * @property-read \App\Models\Plotone|null $plotone
 * @property-read \App\Models\Polo|null $polo
 * @property-read \App\Models\Ruolo|null $ruoloCertificati
 * @property-read \App\Models\Mansione|null $mansione
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Presenza[] $presenze
 * @property-read \App\Models\Presenza|null $presenzaOggi
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Evento[] $eventi
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MilitareValutazione[] $valutazioni
 * @property-read float $media_valutazioni
 */
class Militare extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     * 
     * @var string
     */
    protected $table = 'militari';

    /**
     * Attributi assegnabili in massa
     * 
     * @var array<string>
     */
    protected $fillable = [
        'grado_id',
        'categoria',
        'numero_matricola',
        'cognome',
        'nome',
        'plotone_id',
        'polo_id',
        'compagnia',
        'ruolo_id',
        'mansione_id',
        'approntamento_principale_id',
        'anzianita',
        'email_istituzionale',
        'telefono',
        'certificati_note',
        'idoneita_note', 
        'note',
        'ultimo_poligono_id',
        'data_ultimo_poligono',
        'nos_status',
        'nos_scadenza',
        'nos_note',
        'compagnia_nos'
    ];

    /**
     * Attributi che devono essere convertiti in tipi nativi
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Eventi del modello
     */
    protected static function booted()
    {
        static::created(function ($militare) {
            $militare->createMilitareDirectories();
        });

        static::updated(function ($militare) {
            // Se il nome o cognome sono cambiati, rinomina le cartelle
            if ($militare->isDirty(['nome', 'cognome'])) {
                $militare->renameMilitareDirectories();
            }
        });

        static::deleting(function ($militare) {
            $militare->deleteMilitareDirectories();
        });
    }
    
    // ============================================
    // QUERY SCOPES
    // ============================================
    
    /**
     * Scope per ordinare i militari per grado e nome
     * 
     * Ordina prima per ordine del grado (decrescente, grado più alto prima),
     * poi per cognome e nome in ordine alfabetico.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByGradoENome($query)
    {
        return $query->leftJoin('gradi', 'militari.grado_id', '=', 'gradi.id')
                    // Chi ha il grado viene prima (0), chi non ha grado viene dopo (1)
                    ->orderByRaw('CASE WHEN militari.grado_id IS NULL THEN 1 ELSE 0 END')
                    // Tra chi ha il grado, ordina per ordine CRESCENTE (ordine 1 = COL più alto, ordine 23 = SOL più basso)
                    ->orderBy('gradi.ordine', 'asc')
                    ->orderBy('militari.cognome')
                    ->orderBy('militari.nome')
                    ->select('militari.*');
    }
    
    /**
     * Scope per filtrare militari presenti in una data specifica
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $data Data di riferimento (default: oggi)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePresenti($query, $data = null)
    {
        $data = $data ?: Carbon::today()->format('Y-m-d');
        
        return $query->whereHas('presenze', function($q) use ($data) {
            $q->where('data', $data)
              ->where('stato', 'Presente');
        });
    }
    
    /**
     * Scope per filtrare militari assenti in una data specifica
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $data Data di riferimento (default: oggi)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAssenti($query, $data = null)
    {
        $data = $data ?: Carbon::today()->format('Y-m-d');
        
        return $query->whereDoesntHave('presenze', function($q) use ($data) {
            $q->where('data', $data)
              ->where('stato', 'Presente');
        });
    }
    
    /**
     * Scope per filtrare militari coinvolti in eventi in un periodo
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $dataInizio Data di inizio periodo
     * @param string|null $dataFine Data di fine periodo (default: stessa data di inizio)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInEvento($query, $dataInizio, $dataFine = null)
    {
        $dataFine = $dataFine ?: $dataInizio;
        
        return $query->whereHas('eventi', function($q) use ($dataInizio, $dataFine) {
            $q->where(function($sq) use ($dataInizio, $dataFine) {
                $sq->whereBetween('data_inizio', [$dataInizio, $dataFine])
                  ->orWhereBetween('data_fine', [$dataInizio, $dataFine])
                  ->orWhere(function($ssq) use ($dataInizio, $dataFine) {
                      $ssq->where('data_inizio', '<=', $dataInizio)
                          ->where('data_fine', '>=', $dataFine);
                  });
            });
        });
    }
    
    // ============================================
    // RELAZIONI ELOQUENT
    // ============================================
    
    /**
     * Relazione con il grado del militare
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }

    /**
     * Relazione con il plotone di assegnazione
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plotone()
    {
        return $this->belongsTo(Plotone::class, 'plotone_id');
    }

    /**
     * Relazione con il polo del militare
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function polo()
    {
        return $this->belongsTo(Polo::class, 'polo_id');
    }

    /**
     * Relazione con la compagnia del militare
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function compagnia()
    {
        return $this->belongsTo(Compagnia::class, 'compagnia_id');
    }

    /**
     * Relazione con il ruolo del militare
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ruolo()
    {
        return $this->belongsTo(Ruolo::class, 'ruolo_id');
    }

    /**
     * Relazione con la presenza odierna del militare
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function presenzaOggi()
    {
        return $this->hasOne(Presenza::class, 'militare_id')
                    ->where('data', Carbon::today()->format('Y-m-d'));
    }
    
    /**
     * Relazione con tutte le presenze del militare
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function presenze()
    {
        return $this->hasMany(Presenza::class, 'militare_id');
    }

    /**
     * Relazione con il ruolo per i certificati
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ruoloCertificati()
    {
        return $this->belongsTo(Ruolo::class, 'ruolo_id');
    }

    /**
     * Relazione con la mansione del militare
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mansione()
    {
        return $this->belongsTo(Mansione::class, 'mansione_id');
    }

    
    /**
     * Relazione con le note del militare
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function note()
    {
        return $this->hasMany(Nota::class, 'militare_id');
    }
    
    /**
     * Relazione con gli eventi del militare
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function eventi()
    {
        return $this->hasMany(Evento::class, 'militare_id');
    }

    /**
     * Relazione con le valutazioni del militare
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function valutazioni()
    {
        return $this->hasMany(MilitareValutazione::class);
    }

    /**
     * Relazione con l'approntamento principale
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approntamentoPrincipale()
    {
        return $this->belongsTo(Approntamento::class, 'approntamento_principale_id');
    }

    /**
     * Relazione many-to-many con gli approntamenti
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function approntamenti()
    {
        return $this->belongsToMany(Approntamento::class, 'militare_approntamenti')
                    ->withPivot(['ruolo', 'data_assegnazione', 'data_fine_assegnazione', 'principale', 'note'])
                    ->withTimestamps();
    }

    /**
     * Relazione con le assegnazioni agli approntamenti
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function militareApprontamenti()
    {
        return $this->hasMany(MilitareApprontamento::class);
    }

    /**
     * Relazione con le patenti possedute
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function patenti()
    {
        return $this->hasMany(PatenteMilitare::class);
    }

    /**
     * Relazione con le scadenze
     */
    public function scadenza()
    {
        return $this->hasOne(ScadenzaMilitare::class, 'militare_id');
    }

    // Relazione assegnazioniTurno rimossa - tabella non esistente

    /**
     * Verifica se il militare è disponibile per una data specifica
     * Controlla sia il CPT che i turni già assegnati
     * 
     * @param string $data Data nel formato Y-m-d
     * @return array ['disponibile' => bool, 'motivo' => string|null, 'conflitto' => object|null]
     */
    public function isDisponibile($data)
    {
        // Controlla se ha un impegno nel CPT
        $dataObj = \Carbon\Carbon::parse($data);
        $impegnoCpt = $this->pianificazioniGiornaliere()
            ->whereYear('created_at', $dataObj->year)
            ->whereMonth('created_at', $dataObj->month)
            ->whereHas('pianificazioneMensile', function($q) use ($dataObj) {
                $q->where('mese', $dataObj->month)
                  ->where('anno', $dataObj->year);
            })
            ->where('giorno', $dataObj->day)
            ->with('tipoServizio')
            ->first();

        if ($impegnoCpt && $impegnoCpt->tipoServizio) {
            return [
                'disponibile' => false,
                'motivo' => 'Impegnato nel CPT: ' . $impegnoCpt->tipoServizio->codice,
                'conflitto' => $impegnoCpt,
                'tipo' => 'cpt'
            ];
        }

        return [
            'disponibile' => true,
            'motivo' => null,
            'conflitto' => null,
            'tipo' => null
        ];
    }

    /**
     * Relazione con le pianificazioni giornaliere
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pianificazioniGiornaliere()
    {
        return $this->hasMany(PianificazioneGiornaliera::class);
    }

    /**
     * Relazione con i poligoni effettuati
     */
    public function poligoni()
    {
        return $this->hasMany(Poligono::class, 'militare_id');
    }

    /**
     * Relazione con l'ultimo poligono effettuato
     */
    public function ultimoPoligono()
    {
        return $this->belongsTo(Poligono::class, 'ultimo_poligono_id');
    }

    // ============================================
    // ATTRIBUTI CALCOLATI
    // ============================================

    /**
     * Calcola la media delle valutazioni del militare
     * 
     * @return float Media delle valutazioni (0 se non ci sono valutazioni)
     */
    public function getMediaValutazioniAttribute()
    {
        $valutazione = MilitareValutazione::where('militare_id', $this->id)->first();
        if (!$valutazione) {
            return 0;
        }

        $campi = ['precisione_lavoro', 'affidabilita', 'capacita_tecnica', 'collaborazione', 'iniziativa'];
        $totale = 0;
        $count = 0;

        foreach ($campi as $campo) {
            if ($valutazione->$campo > 0) {
                $totale += $valutazione->$campo;
                $count++;
            }
        }

        return $count > 0 ? round($totale / $count, 2) : 0;
    }

    // ============================================
    // METODI DI UTILITÀ
    // ============================================

    /**
     * Verifica se il militare è presente oggi
     * 
     * @return bool
     */
    public function isPresente()
    {
        $presenzaOggi = $this->presenzaOggi;
        return $presenzaOggi && $presenzaOggi->stato === 'Presente';
    }


    /**
     * Ottiene il nome completo del militare
     * 
     * @param bool $includeGrado Se includere il grado nel nome
     * @return string Nome completo formattato
     */
    public function getNomeCompleto($includeGrado = true)
    {
        $nome = trim($this->cognome . ' ' . $this->nome);
        
        if ($includeGrado && $this->grado) {
            return $this->grado->nome . ' ' . $nome;
        }
        
        return $nome;
    }

    /**
     * Verifica se il militare ha eventi in un periodo specifico
     * 
     * @param string $dataInizio Data di inizio periodo
     * @param string $dataFine Data di fine periodo
     * @return bool
     */
    public function hasEventoInDate($dataInizio, $dataFine)
    {
        return $this->eventi()
            ->where(function($query) use ($dataInizio, $dataFine) {
                $query->whereBetween('data_inizio', [$dataInizio, $dataFine])
                      ->orWhereBetween('data_fine', [$dataInizio, $dataFine])
                      ->orWhere(function($q) use ($dataInizio, $dataFine) {
                          $q->where('data_inizio', '<=', $dataInizio)
                            ->where('data_fine', '>=', $dataFine);
                      });
            })
            ->exists();
    }


    // ============================================
    // GESTIONE CARTELLE MILITARE
    // ============================================

    /**
     * Ottiene il nome della cartella del militare
     * 
     * @return string
     */
    public function getFolderName()
    {
        return $this->cognome . '_' . $this->nome . '_' . $this->id;
    }

    /**
     * Ottiene il percorso della cartella del militare
     * 
     * @return string
     */
    public function getFolderPath()
    {
        return 'militari/' . $this->getFolderName();
    }

    /**
     * Crea le cartelle per il militare
     * 
     * @return void
     */
    public function createMilitareDirectories()
    {
        $folderPath = $this->getFolderPath();
        
        // Crea la cartella principale del militare
        \Storage::disk('public')->makeDirectory($folderPath);
        
        // Crea le sottocartelle per i certificati
        \Storage::disk('public')->makeDirectory($folderPath . '/certificati_lavoratori');
        \Storage::disk('public')->makeDirectory($folderPath . '/idoneita');
        
        \Log::info('Cartelle create per militare: ' . $this->getNomeCompleto(), [
            'militare_id' => $this->id,
            'folder_path' => $folderPath
        ]);
    }

    /**
     * Rinomina le cartelle del militare (quando cambia nome/cognome)
     * 
     * @return void
     */
    public function renameMilitareDirectories()
    {
        $oldName = $this->getOriginal('cognome') . '_' . $this->getOriginal('nome') . '_' . $this->id;
        $oldPath = 'militari/' . $oldName;
        $newPath = $this->getFolderPath();
        
        if (\Storage::disk('public')->exists($oldPath)) {
            \Storage::disk('public')->move($oldPath, $newPath);
            
            \Log::info('Cartelle rinominate per militare: ' . $this->getNomeCompleto(), [
                'militare_id' => $this->id,
                'old_path' => $oldPath,
                'new_path' => $newPath
            ]);
        }
    }

    /**
     * Elimina le cartelle del militare
     * 
     * @return void
     */
    public function deleteMilitareDirectories()
    {
        $folderPath = $this->getFolderPath();
        
        if (\Storage::disk('public')->exists($folderPath)) {
            \Storage::disk('public')->deleteDirectory($folderPath);
            
            \Log::info('Cartelle eliminate per militare: ' . $this->getNomeCompleto(), [
                'militare_id' => $this->id,
                'folder_path' => $folderPath
            ]);
        }
    }


    /**
     * Ottiene il percorso per salvare la foto profilo
     * 
     * @return string
     */
    public function getPhotoPath()
    {
        return $this->getFolderPath() . '/foto_profilo.jpg';
    }
}
