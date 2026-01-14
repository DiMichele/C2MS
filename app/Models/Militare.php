<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Traits\BelongsToCompagnia;
use App\Models\User;

/**
 * SUGECO: Sistema Unico di Gestione e Controllo
 * Modello Militare
 * 
 * Rappresenta un militare con le sue informazioni personali, grado, assegnazioni
 * e relazioni con certificati, idoneità, presenze e valutazioni.
 * 
 * NOTA: Questo modello usa il trait BelongsToCompagnia per la segregazione
 * automatica dei dati per compagnia. Tutte le query saranno filtrate
 * automaticamente in base alla compagnia dell'utente autenticato.
 * 
 * @version 1.1
 * @author Michele Di Gennaro
 * 
 * @property int $id
 * @property int|null $grado_id
 * @property int|null $compagnia_id
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
 * @property-read \App\Models\Compagnia|null $compagnia
 * @property-read \App\Models\Ruolo|null $ruoloCertificati
 * @property-read \App\Models\Mansione|null $mansione
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ScadenzaIdoneita[] $scadenzeIdoneita
 */
class Militare extends Model
{
    use HasFactory, BelongsToCompagnia;

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
        'compagnia_id',
        'ruolo_id',
        'mansione_id',
        'approntamento_principale_id',
        'data_nascita',
        'sesso',
        'luogo_nascita',
        'provincia_nascita',
        'codice_comune',
        'anzianita',
        'codice_fiscale',
        'email',
        'telefono',
        'certificati_note',
        'idoneita_note', 
        'note',
        'istituti',
        'foto_path',
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
        'data_nascita' => 'date',
        'anzianita' => 'date',
        'istituti' => 'array',
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
     * Scope per aggiungere i flag di visibilità (is_owner, is_acquired) alla query.
     * 
     * PERFORMANCE: Questo scope calcola i flag direttamente in SQL, evitando N+1.
     * DEVE essere usato in TUTTE le liste di militari.
     * 
     * Comportamento:
     * - Admin (o user senza compagnia): is_owner=1, is_acquired=0 (vede tutto come owner)
     * - Non-admin: is_owner calcolato, is_acquired calcolato con EXISTS
     * 
     * Uso:
     *   Militare::withVisibilityFlags()->with([...])->get();
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\User|null $user Utente (default: auth()->user())
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithVisibilityFlags($query, $user = null)
    {
        $user = $user ?? auth()->user();
        
        // Se non c'è utente, non aggiungere flag (il Global Scope bloccherà comunque)
        if (!$user) {
            return $query->selectRaw('militari.*, 0 as is_owner, 0 as is_acquired');
        }
        
        $isAdmin = $user->hasRole('admin') || $user->hasRole('amministratore') 
                   || $user->hasPermission('view_all_companies');
        $userCompagniaId = $user->compagnia_id;
        
        // Admin o utente senza compagnia: vede tutto come owner
        if ($isAdmin || !$userCompagniaId) {
            return $query->selectRaw('militari.*, 1 as is_owner, 0 as is_acquired');
        }
        
        // Non-admin con compagnia: calcola flag
        return $query->selectRaw(
            'militari.*, 
             (militari.compagnia_id = ?) as is_owner,
             (militari.compagnia_id != ? AND EXISTS (
                 SELECT 1 FROM activity_militare am
                 JOIN board_activities ba ON am.activity_id = ba.id
                 WHERE am.militare_id = militari.id
                 AND ba.compagnia_id = ?
             )) as is_acquired',
            [$userCompagniaId, $userCompagniaId, $userCompagniaId]
        );
    }
    
    /**
     * Scope per ordinare i militari per grado, anzianità e nome
     * 
     * Ordine di visualizzazione:
     * 1. Grado (dal più alto al più basso: COL → SOL)
     * 2. Anzianità (a parità di grado, il più anziano viene prima)
     * 3. Cognome
     * 4. Nome
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByGradoENome($query)
    {
        return $query->leftJoin('gradi', 'militari.grado_id', '=', 'gradi.id')
                    // Chi ha il grado viene prima (0), chi non ha grado viene dopo (1)
                    ->orderByRaw('CASE WHEN militari.grado_id IS NULL THEN 1 ELSE 0 END')
                    // Tra chi ha il grado, ordina per ordine DECRESCENTE (ordine 90 = COL più alto, ordine 10 = SOL più basso)
                    ->orderBy('gradi.ordine', 'desc')
                    // A parità di grado, ordina per anzianità ASCENDENTE (il più vecchio prima)
                    // NULL viene considerato come ultimo (senza anzianità)
                    ->orderByRaw('CASE WHEN militari.anzianita IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('militari.anzianita', 'asc')
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

    /**
     * @deprecated Usa il Global Scope CompagniaScope che filtra automaticamente.
     * Questo scope è mantenuto solo per retrocompatibilità ma NON deve essere usato
     * perché taglia fuori i militari "acquired".
     * 
     * Il Global Scope già filtra per: owner OR acquired
     * Questo scope filtrava solo per: owner (sbagliato!)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCurrentUser($query)
    {
        // DEPRECATO: Il Global Scope CompagniaScope filtra già automaticamente
        // Restituisce la query senza modifiche (il Global Scope fa il lavoro)
        return $query;
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
     * Relazione con l'ufficio del militare (alias di polo per compatibilità)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ufficio()
    {
        return $this->belongsTo(Ufficio::class, 'polo_id');
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
     * Relazione con l'approntamento principale
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approntamentoPrincipale()
    {
        return $this->belongsTo(Approntamento::class, 'approntamento_principale_id');
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

    /**
     * Relazione con le scadenze approntamenti
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function scadenzaApprontamento()
    {
        return $this->hasOne(ScadenzaApprontamento::class, 'militare_id');
    }

    /**
     * Relazione con le scadenze idoneità sanitarie
     */
    public function scadenzeIdoneita()
    {
        return $this->hasMany(ScadenzaIdoneita::class, 'militare_id');
    }

    /**
     * Relazione con le scadenze corsi SPP (dinamica)
     */
    public function scadenzeCorsiSpp()
    {
        return $this->hasMany(ScadenzaCorsoSpp::class, 'militare_id');
    }

    /**
     * Relazione con i campi custom dell'anagrafica
     */
    public function campiCustom()
    {
        return $this->hasMany(ValoreCampoAnagrafica::class, 'militare_id');
    }

    /**
     * Helper per ottenere il valore di un campo custom o di sistema
     */
    public function getValoreCampoCustom($nome_campo)
    {
        $configurazione = ConfigurazioneCampoAnagrafica::where('nome_campo', $nome_campo)
            ->where('attivo', true)
            ->first();
        if (!$configurazione) {
            return null;
        }
        
        // Per i campi di sistema, restituisci il valore direttamente dal modello
        $campiSistema = [
            'compagnia' => 'compagnia_id',
            'grado' => 'grado_id',
            'cognome' => 'cognome',
            'nome' => 'nome',
            'plotone' => 'plotone_id',
            'ufficio' => 'polo_id',
            'incarico' => 'mansione_id',
            'nos' => 'nos_status',
            'anzianita' => 'anzianita',
            'data_nascita' => 'data_nascita',
            'email_istituzionale' => 'email_istituzionale',
            'telefono' => 'telefono',
            'codice_fiscale' => 'codice_fiscale',
        ];
        
        if (isset($campiSistema[$nome_campo])) {
            $campoDB = $campiSistema[$nome_campo];
            $valore = $this->$campoDB;
            
            // Formatta le date
            if ($nome_campo == 'anzianita' || $nome_campo == 'data_nascita') {
                return $valore ? $valore->format('Y-m-d') : '';
            }
            
            return $valore;
        }
        
        // Per i campi custom, usa la relazione
        return $this->campiCustom()->where('configurazione_campo_id', $configurazione->id)->first()?->valore;
    }

    /**
     * Helper per settare il valore di un campo custom o di sistema
     */
    public function setValoreCampoCustom($nome_campo, $valore)
    {
        $configurazione = ConfigurazioneCampoAnagrafica::where('nome_campo', $nome_campo)
            ->where('attivo', true)
            ->first();
        if (!$configurazione) {
            return false;
        }
        
        // Per i campi di sistema, aggiorna direttamente il modello
        $campiSistema = [
            'compagnia' => 'compagnia_id',
            'grado' => 'grado_id',
            'cognome' => 'cognome',
            'nome' => 'nome',
            'plotone' => 'plotone_id',
            'ufficio' => 'polo_id',
            'incarico' => 'mansione_id',
            'nos' => 'nos_status',
            'anzianita' => 'anzianita',
            'data_nascita' => 'data_nascita',
            'email_istituzionale' => 'email_istituzionale',
            'telefono' => 'telefono',
            'codice_fiscale' => 'codice_fiscale',
        ];
        
        if (isset($campiSistema[$nome_campo])) {
            $campoDB = $campiSistema[$nome_campo];
            $this->$campoDB = $valore;
            $this->save();
            return true;
        }
        
        // Per i campi custom, usa la relazione
        ValoreCampoAnagrafica::updateOrCreate(
            [
                'militare_id' => $this->id,
                'configurazione_campo_id' => $configurazione->id
            ],
            ['valore' => $valore]
        );
        
        return true;
    }

    // Relazione assegnazioniTurno rimossa - tabella non esistente

    /**
     * Verifica se il militare è disponibile per una data specifica
     * Controlla sia il CPT che i turni già assegnati
     * 
     * @param string $data Data nel formato Y-m-d
     * @param int|null $excludeActivityId ID dell'attività da escludere dal controllo (utile per modifiche)
     * @return array ['disponibile' => bool, 'motivo' => string|null, 'conflitto' => object|null]
     */
    public function isDisponibile($data, $excludeActivityId = null)
    {
        // Controlla se ha un impegno nel CPT
        $dataObj = \Carbon\Carbon::parse($data);
        $query = $this->pianificazioniGiornaliere()
            ->whereYear('created_at', $dataObj->year)
            ->whereMonth('created_at', $dataObj->month)
            ->whereHas('pianificazioneMensile', function($q) use ($dataObj) {
                $q->where('mese', $dataObj->month)
                  ->where('anno', $dataObj->year);
            })
            ->where('giorno', $dataObj->day)
            ->with('tipoServizio');
        
        // Se dobbiamo escludere un'attività, escludiamo le sue pianificazioni
        if ($excludeActivityId) {
            $activity = \App\Models\BoardActivity::with('column')->find($excludeActivityId);
            if ($activity && $activity->column) {
                $notaToExclude = "{$activity->column->name}: {$activity->title}";
                $query->where('note', '!=', $notaToExclude);
            }
        }
        
        $impegnoCpt = $query->first();

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

    // ============================================
    // METODI DI UTILITÀ
    // ============================================

    /**
     * Verifica se il militare è presente oggi (basato sul CPT)
     * 
     * Logica:
     * - Weekend (sab/dom): assente di default, presente solo se ha servizio nel CPT
     * - Giorni feriali (lun-ven): presente di default, assente solo se ha codici di assenza
     * 
     * @return bool
     */
    public function isPresente()
    {
        $oggi = Carbon::today();
        $isWeekend = $oggi->isWeekend(); // Sabato o Domenica
        
        // Cerca pianificazione nel CPT
        $pianificazione = PianificazioneGiornaliera::where('militare_id', $this->id)
            ->whereHas('pianificazioneMensile', function($q) use ($oggi) {
                $q->where('mese', $oggi->month)->where('anno', $oggi->year);
            })
            ->where('giorno', $oggi->day)
            ->with('tipoServizio')
            ->first();
        
        // Codici che indicano assenza - centralizzati in config/cpt.php
        $codiciAssenza = config('cpt.codici_assenza', ['LIC', 'MAL', 'RIP', 'CONGEDO', 'PERM']);
        
        if ($isWeekend) {
            // Weekend: assente di default
            // Presente solo se ha un servizio programmato (che non sia assenza)
            if (!$pianificazione || !$pianificazione->tipoServizio) {
                return false; // Nessun servizio nel weekend = assente
            }
            // Ha un servizio, controlla che non sia un codice di assenza
            return !in_array(strtoupper($pianificazione->tipoServizio->codice), $codiciAssenza);
        } else {
            // Giorni feriali: presente di default
            if (!$pianificazione || !$pianificazione->tipoServizio) {
                return true; // Nessuna pianificazione in settimana = presente
            }
            // Controlla se ha codici di assenza
            return !in_array(strtoupper($pianificazione->tipoServizio->codice), $codiciAssenza);
        }
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

    // ============================================
    // METODI PER GESTIONE OWNER/ACQUIRED
    // ============================================
    
    /**
     * Verifica se il militare è "owner" per l'utente corrente.
     * Owner = appartiene alla stessa compagnia dell'utente
     * 
     * @return bool
     */
    public function isOwnerFor(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        
        if (!$user || !$user->compagnia_id) {
            return false;
        }
        
        return $this->compagnia_id === $user->compagnia_id;
    }
    
    /**
     * Verifica se il militare è "acquisito" dalla compagnia dell'utente.
     * Acquisito = partecipa ad attività della compagnia ma appartiene ad altra compagnia
     * 
     * NOTA TEMPORALE: L'acquisizione è valida solo finché l'attività esiste.
     * Se l'attività viene cancellata, il militare non è più visibile per quella compagnia.
     * Questo è garantito dalla JOIN con board_activities (se l'attività non esiste,
     * la join non restituisce risultati).
     * 
     * @return bool
     */
    public function isAcquiredBy(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        
        if (!$user || !$user->compagnia_id) {
            return false;
        }
        
        // Se è owner, non è acquisito
        if ($this->isOwnerFor($user)) {
            return false;
        }
        
        // Verifica partecipazione ad attività ESISTENTI della compagnia dell'utente
        // La JOIN garantisce che l'attività esista ancora (non soft-deleted)
        return \DB::table('activity_militare')
            ->join('board_activities', 'activity_militare.activity_id', '=', 'board_activities.id')
            ->where('activity_militare.militare_id', $this->id)
            ->where('board_activities.compagnia_id', $user->compagnia_id)
            ->exists();
    }
    
    /**
     * Verifica se il militare è modificabile dall'utente corrente.
     * Solo gli owner possono modificare.
     * 
     * @return bool
     */
    public function isEditableBy(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return false;
        }
        
        // Admin possono sempre modificare
        if ($user->isAdmin()) {
            return true;
        }
        
        // Solo gli owner possono modificare
        return $this->isOwnerFor($user);
    }
    
    /**
     * Verifica se il militare è in sola lettura per l'utente.
     * 
     * @return bool
     */
    public function isReadOnlyFor(?User $user = null): bool
    {
        return !$this->isEditableBy($user);
    }
    
    /**
     * Ottiene il tipo di relazione con l'utente corrente.
     * 
     * @return string 'owner', 'acquired', o 'none'
     */
    public function getRelationType(?User $user = null): string
    {
        $user = $user ?? auth()->user();
        
        if ($this->isOwnerFor($user)) {
            return 'owner';
        }
        
        if ($this->isAcquiredBy($user)) {
            return 'acquired';
        }
        
        return 'none';
    }
    
    /**
     * Relazione con le attività in cui il militare è assegnato
     */
    public function activities()
    {
        return $this->belongsToMany(
            \App\Models\BoardActivity::class, 
            'activity_militare', 
            'militare_id', 
            'activity_id'
        );
    }
    
    /**
     * Ottiene le attività della compagnia specificata in cui il militare è coinvolto
     */
    public function activitiesForCompagnia(int $compagniaId)
    {
        return $this->activities()->where('board_activities.compagnia_id', $compagniaId);
    }
}
