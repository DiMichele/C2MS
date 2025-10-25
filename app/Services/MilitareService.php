<?php

namespace App\Services;

use App\Models\Militare;
use App\Models\Grado;
use App\Models\Plotone;
use App\Models\Polo;
use App\Models\Ruolo;
use App\Models\Mansione;
use App\Models\MilitareValutazione;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * C2MS: Gestione e Controllo Digitale a Supporto del Comando
 * 
 * Service per la gestione dei militari e delle loro informazioni.
 * Centralizza la logica di business per operazioni sui militari, valutazioni,
 * filtri, ricerche e gestione file.
 * 
 * @package C2MS
 * @subpackage Services
 * @version 1.0
 * @since 1.0
 * @author Michele Di Gennaro
 * @copyright 2025 C2MS
 */
class MilitareService
{
    /**
     * Applica filtri alla query dei militari
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyFilters($query, Request $request)
    {
        // Filtro per grado
        if ($request->filled('grado_id')) {
            $query->where('grado_id', $request->input('grado_id'));
        }
        
        // Filtro per plotone
        if ($request->filled('plotone_id')) {
            $query->where('plotone_id', $request->input('plotone_id'));
        }
        
        // Filtro per polo
        if ($request->filled('polo_id')) {
            $query->where('polo_id', $request->input('polo_id'));
        }
        
        // Filtro per ruolo
        if ($request->filled('ruolo_id')) {
            $query->where('ruolo_id', $request->input('ruolo_id'));
        }
        
        // Filtro per mansione
        if ($request->filled('mansione_id')) {
            $query->where('mansione_id', $request->input('mansione_id'));
        }
        
        // Filtro per presenza
        if ($request->filled('presenza')) {
            $presenza = $request->input('presenza');
            $today = now()->format('Y-m-d');
            
            if ($presenza === 'Presente') {
                $query->whereHas('presenzaOggi', function($q) {
                    $q->where('stato', 'Presente');
                });
            } elseif ($presenza === 'Assente') {
                $query->whereDoesntHave('presenzaOggi', function($q) {
                    $q->where('stato', 'Presente');
                });
            }
        }
        
        return $query;
    }

    /**
     * Esegue ricerca di militari
     * 
     * @param string $query Termine di ricerca
     * @param int $limit Limite risultati
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search($query, $limit = 10)
    {
        return Militare::with(['grado', 'plotone', 'polo'])
            ->where(function($q) use ($query) {
                // Cerca nelle iniziali di nome e cognome
                $q->where('militari.nome', 'LIKE', "{$query}%")
                  ->orWhere('militari.cognome', 'LIKE', "{$query}%")
                  // Cerca anche se la query corrisponde alle iniziali nome+cognome (es. "MR" per Mario Rossi)
                  ->orWhereRaw("CONCAT(LEFT(militari.nome, 1), LEFT(militari.cognome, 1)) LIKE ?", ["{$query}%"])
                  // Cerca anche se la query corrisponde alle iniziali cognome+nome (es. "RM" per Rossi Mario)
                  ->orWhereRaw("CONCAT(LEFT(militari.cognome, 1), LEFT(militari.nome, 1)) LIKE ?", ["{$query}%"]);
            })
            ->orderByGradoENome()
            ->limit($limit)
            ->get();
    }

    /**
     * Salva una valutazione per un militare
     * 
     * @param Militare $militare
     * @param array $data Dati della valutazione
     * @return MilitareValutazione
     */
    public function saveValutazione(Militare $militare, array $data)
    {
        // Cerca valutazione esistente o crea nuova
        $valutazione = MilitareValutazione::firstOrNew([
            'militare_id' => $militare->id
        ]);
        
        // Aggiorna i campi
        $valutazione->fill($data);
        $valutazione->save();
        
        // Invalida cache
        $this->invalidateCache($militare->id);
        
        return $valutazione;
    }

    /**
     * Aggiorna un singolo campo di valutazione
     * 
     * @param Militare $militare
     * @param string $field Nome del campo
     * @param mixed $value Valore del campo
     * @return bool
     */
    public function updateValutazioneField(Militare $militare, $field, $value)
    {
        $valutazione = MilitareValutazione::firstOrNew([
            'militare_id' => $militare->id
        ]);
        
        $valutazione->{$field} = $value;
        $saved = $valutazione->save();
        
        if ($saved) {
            $this->invalidateCache($militare->id);
        }
        
        return $saved;
    }

    /**
     * Aggiorna le note di un militare
     * 
     * @param Militare $militare
     * @param string $notes Note da salvare
     * @return bool
     */
    public function updateNotes(Militare $militare, $notes)
    {
        $updated = $militare->update(['note' => $notes]);
        
        if ($updated) {
            $this->invalidateCache($militare->id);
        }
        
        return $updated;
    }

    /**
     * Gestisce l'upload della foto di un militare
     * 
     * @param Militare $militare
     * @param \Illuminate\Http\UploadedFile $file
     * @return array
     */
    public function uploadFoto(Militare $militare, $file)
    {
        \Log::info("Upload foto per militare ID: {$militare->id}, file: {$file->getClientOriginalName()}");
        
        // Elimina la foto precedente se esiste
        $this->deleteFoto($militare);
        
        // Utilizza il percorso personalizzato del militare
        $fileName = 'foto_profilo.' . $file->getClientOriginalExtension();
        $folderPath = $militare->getFolderPath();
        
        \Log::info("Percorso cartella: {$folderPath}, nome file: {$fileName}");
        
        // Assicurati che la cartella esista
        Storage::disk('public')->makeDirectory($folderPath);
        
        $path = $file->storeAs($folderPath, $fileName, 'public');
        
        \Log::info("File salvato in: {$path}");
        
        // Aggiorna il record del militare
        $militare->update(['foto_path' => $path]);
        
        \Log::info("Database aggiornato, foto_path: {$path}");
        
        // Invalida cache
        $this->invalidateCache($militare->id);
        
        return [
            'path' => $path,
            'url' => Storage::disk('public')->url($path)
        ];
    }

    /**
     * Elimina la foto di un militare
     * 
     * @param Militare $militare
     * @return bool
     */
    public function deleteFoto(Militare $militare)
    {
        if ($militare->foto_path && Storage::disk('public')->exists($militare->foto_path)) {
            $deleted = Storage::disk('public')->delete($militare->foto_path);
            
            if ($deleted) {
                $militare->update(['foto_path' => null]);
                $this->invalidateCache($militare->id);
            }
            
            return $deleted;
        }
        
        return true;
    }

    /**
     * Ottiene l'URL della foto di un militare
     * 
     * @param Militare $militare
     * @return string|null
     */
    public function getFotoUrl(Militare $militare)
    {
        if ($militare->foto_path && Storage::disk('public')->exists($militare->foto_path)) {
            return Storage::disk('public')->url($militare->foto_path);
        }
        
        return null;
    }

    /**
     * Ottiene la foto di un militare
     * 
     * @param int $militareId
     * @return \Illuminate\Http\Response
     */
    public function getFoto($militareId)
    {
        $militare = Militare::findOrFail($militareId);
        
        \Log::info("Richiesta foto per militare ID: {$militareId}, foto_path: {$militare->foto_path}");
        
        if ($militare->foto_path && Storage::disk('public')->exists($militare->foto_path)) {
            $path = Storage::disk('public')->path($militare->foto_path);
            $mimeType = Storage::disk('public')->mimeType($militare->foto_path);
            
            \Log::info("Servendo foto: {$path}, mime: {$mimeType}");
            
            return response()->file($path, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        }
        
        \Log::info("Foto non trovata per militare ID: {$militareId}, restituendo 404");
        
        // Se non c'è una foto, restituisci 404 per permettere al JavaScript di gestire il caso
        return response('', 404);
    }

    /**
     * Prepara i dati API per un militare
     * 
     * @param Militare $militare
     * @return array
     */
    public function getApiData(Militare $militare)
    {
        $militare->load([
            'grado',
            'plotone', 
            'polo',
            'ruoloCertificati',
            'mansione',
            'certificatiLavoratori',
            'idoneita'
        ]);
        
        return [
            'id' => $militare->id,
            'nome_completo' => $militare->getNomeCompleto(),
            'grado' => $militare->grado?->nome,
            'plotone' => $militare->plotone?->nome,
            'polo' => $militare->polo?->nome,
            'ruolo' => $militare->ruoloCertificati?->nome,
            'mansione' => $militare->mansione?->nome,
            'note' => $militare->note,
            'foto_url' => $this->getFotoUrl($militare),
            'media_valutazioni' => $militare->media_valutazioni,
            'created_at' => $militare->created_at,
            'updated_at' => $militare->updated_at
        ];
    }

    /**
     * Elimina un militare e tutti i suoi dati associati
     * 
     * @param Militare|int $militare
     * @return bool
     * @throws \Exception Se l'eliminazione fallisce
     */
    public function deleteMilitare($militare)
    {
        try {
            // Se è un ID, trova il militare
            if (is_numeric($militare)) {
                $militare = Militare::findOrFail($militare);
            }
            
            DB::beginTransaction();
            
            try {
                // Prima di tutto, rimuoviamo eventuali riferimenti circolari
                // Se questo militare è referenziato come ultimo_poligono_id in altri militari
                DB::table('militari')
                    ->where('ultimo_poligono_id', function($query) use ($militare) {
                        $query->select('id')
                              ->from('poligoni')
                              ->where('militare_id', $militare->id);
                    })
                    ->update(['ultimo_poligono_id' => null]);
                
                // Se questo militare è referenziato come approntamento_principale_id
                DB::table('militari')
                    ->where('approntamento_principale_id', function($query) use ($militare) {
                        $query->select('id')
                              ->from('approntamenti')
                              ->whereExists(function($subquery) use ($militare) {
                                  $subquery->select(DB::raw(1))
                                           ->from('militare_approntamenti')
                                           ->whereColumn('militare_approntamenti.approntamento_id', 'approntamenti.id')
                                           ->where('militare_approntamenti.militare_id', $militare->id);
                              });
                    })
                    ->update(['approntamento_principale_id' => null]);
                
                // Ora elimina la foto se esiste
                $this->deleteFoto($militare);
                
                // Elimina i poligoni PRIMA (perché militari.ultimo_poligono_id li referenzia)
                DB::table('poligoni')->where('militare_id', $militare->id)->delete();
                
                // Elimina i certificati associati (hanno ON DELETE CASCADE)
                // Ma per sicurezza li eliminiamo esplicitamente
                DB::table('certificati_lavoratori')->where('militare_id', $militare->id)->delete();
                DB::table('idoneita')->where('militare_id', $militare->id)->delete();
                
                // Elimina le valutazioni
                DB::table('militare_valutazioni')->where('militare_id', $militare->id)->delete();
                
                // Elimina le presenze (esiste nel DB)
                DB::table('presenze')->where('militare_id', $militare->id)->delete();
                
                // NOTA: La tabella 'assenze' NON ESISTE nel database, quindi non la eliminiamo
                
                // Elimina gli eventi
                DB::table('eventi')->where('militare_id', $militare->id)->delete();
                
                // Elimina le pianificazioni giornaliere (CPT) - nome corretto della tabella
                DB::table('pianificazioni_giornaliere')->where('militare_id', $militare->id)->delete();
                
                // Elimina le patenti
                DB::table('patenti_militari')->where('militare_id', $militare->id)->delete();
                
                // Elimina le note
                DB::table('notas')->where('militare_id', $militare->id)->delete();
                
                // Elimina eventuali relazioni con approntamenti (tabella pivot)
                DB::table('militare_approntamenti')->where('militare_id', $militare->id)->delete();
                
                // Elimina eventuali associazioni con attività del board
                DB::table('activity_militare')->where('militare_id', $militare->id)->delete();
                
                // Elimina il militare
                $deleted = $militare->delete();
                
                if (!$deleted) {
                    throw new \Exception('L\'eliminazione del militare ha fallito senza eccezione');
                }
                
                DB::commit();
                
                $this->invalidateCache($militare->id);
                
                return true;
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Errore durante l\'eliminazione del militare', [
                'militare_id' => is_object($militare) ? $militare->id : $militare,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception('Impossibile eliminare il militare: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Ottiene le opzioni per i form
     * 
     * @return array
     */
    public function getFormOptions()
    {
        return [
            'gradi' => Grado::orderBy('ordine')->get(),
            'plotoni' => Plotone::orderBy('nome')->get(),
            'poli' => Polo::orderBy('nome')->get(),
            'ruoli' => Ruolo::orderBy('nome')->get(),
            'mansioni' => Mansione::orderBy('nome')->get()
        ];
    }

    /**
     * Valida i dati per la creazione/aggiornamento di un militare
     * 
     * @param array $data
     * @param Militare|null $militare Per aggiornamenti
     * @return array Regole di validazione
     */
    public function getValidationRules($militare = null)
    {
        return [
            'nome' => 'required|string|max:255',
            'cognome' => 'required|string|max:255',
            'grado_id' => 'required|exists:gradi,id',
            'plotone_id' => 'nullable|exists:plotoni,id',
            'polo_id' => 'nullable|exists:poli,id',
            'ruolo_id' => 'nullable|exists:ruoli,id',
            'mansione_id' => 'nullable|exists:mansioni,id',
            'anzianita' => 'nullable|string|max:255',
            'email_istituzionale' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'note' => 'nullable|string',
            'certificati_note' => 'nullable|string',
            'idoneita_note' => 'nullable|string'
        ];
    }

    /**
     * Invalida la cache per un militare specifico
     * 
     * @param int $militareId
     */
    protected function invalidateCache($militareId)
    {
        $cacheKeys = [
            "militare.{$militareId}",
            "militare.{$militareId}.certificati",
            "militare.{$militareId}.idoneita",
            "militare.{$militareId}.valutazioni",
            'militari.index',
            'dashboard.militari'
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Invalida tutta la cache dei militari
     */
    public function invalidateAllCache()
    {
        $patterns = [
            'militare.*',
            'militari.*',
            'dashboard.militari'
        ];
        
        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Ottiene i dettagli completi di un militare
     * 
     * @param Militare|int $militare
     * @return array
     */
    public function getMilitareDetails($militare)
    {
        if (is_numeric($militare)) {
            $militare = Militare::findOrFail($militare);
        }
        
        $militare->load([
            'grado', 
            'plotone', 
            'polo', 
            'mansione', 
            'ruolo',
            'certificatiLavoratori',
            'idoneita',
            'valutazioni',
            'assenze',
            'eventi'
        ]);
        
        return $militare;
    }

    /**
     * Ottiene militari filtrati con paginazione
     * 
     * @param Request $request
     * @param int $perPage
     * @return array
     */
    public function getFilteredMilitari(Request $request, $perPage = 20)
    {
        $query = Militare::with(['grado', 'plotone', 'polo', 'mansione', 'ruolo', 'presenzaOggi', 'patenti']);
        
        // Filtri
        if ($request->filled('grado_id')) {
            $query->where('grado_id', $request->grado_id);
        }
        
        if ($request->filled('plotone_id')) {
            $query->where('plotone_id', $request->plotone_id);
        }
        
        if ($request->filled('polo_id')) {
            $query->where('polo_id', $request->polo_id);
        }
        
        if ($request->filled('compagnia')) {
            $query->where('compagnia', $request->compagnia);
        }
        
        if ($request->filled('nos_status')) {
            $query->where('nos_status', $request->nos_status);
        }
        
        if ($request->filled('mansione_id')) {
            $query->where('mansione_id', $request->mansione_id);
        }
        
        // Filtro per compleanno
        if ($request->filled('compleanno')) {
            $oggi = now();
            
            switch ($request->compleanno) {
                case 'oggi':
                    $query->whereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$oggi->day, $oggi->month]);
                    break;
                    
                case 'ultimi_2':
                    // Compleanno negli ultimi 2 giorni (ieri e l'altro ieri)
                    $ieri = $oggi->copy()->subDay();
                    $altroIeri = $oggi->copy()->subDays(2);
                    
                    $query->where(function($q) use ($oggi, $ieri, $altroIeri) {
                        $q->whereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$oggi->day, $oggi->month])
                          ->orWhereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$ieri->day, $ieri->month])
                          ->orWhereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$altroIeri->day, $altroIeri->month]);
                    });
                    break;
                    
                case 'prossimi_2':
                    // Compleanno nei prossimi 2 giorni (domani e dopodomani)
                    $domani = $oggi->copy()->addDay();
                    $dopodomani = $oggi->copy()->addDays(2);
                    
                    $query->where(function($q) use ($oggi, $domani, $dopodomani) {
                        $q->whereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$oggi->day, $oggi->month])
                          ->orWhereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$domani->day, $domani->month])
                          ->orWhereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$dopodomani->day, $dopodomani->month]);
                    });
                    break;
            }
        }
        
        if ($request->filled('ruolo_id')) {
            $query->where('ruolo_id', $request->ruolo_id);
        }
        
        // Filtro per email istituzionale
        if ($request->filled('email_istituzionale')) {
            if ($request->email_istituzionale === 'registrata') {
                $query->whereNotNull('email_istituzionale')->where('email_istituzionale', '!=', '');
            } elseif ($request->email_istituzionale === 'non_registrata') {
                $query->where(function($q) {
                    $q->whereNull('email_istituzionale')->orWhere('email_istituzionale', '');
                });
            }
        }
        
        // Filtro per telefono
        if ($request->filled('telefono')) {
            if ($request->telefono === 'registrato') {
                $query->whereNotNull('telefono')->where('telefono', '!=', '');
            } elseif ($request->telefono === 'non_registrato') {
                $query->where(function($q) {
                    $q->whereNull('telefono')->orWhere('telefono', '');
                });
            }
        }
        
        if ($request->filled('presenza')) {
            if ($request->presenza === 'Presente') {
                $query->whereHas('presenzaOggi', function($q) {
                    $q->where('stato', 'Presente');
                });
            } elseif ($request->presenza === 'Assente') {
                $query->whereDoesntHave('presenzaOggi')
                      ->orWhereHas('presenzaOggi', function($q) {
                          $q->where('stato', 'Assente');
                      });
            }
        }
        
        $militari = $query->orderByGradoENome()->get();
        
        // Dati per i filtri
        $gradi = Grado::orderBy('ordine')->get();
        $plotoni = Plotone::orderBy('nome')->get();
        $poli = Polo::orderBy('nome')->get();
        $mansioni = Mansione::orderBy('nome')->get();
        $ruoli = Ruolo::orderBy('nome')->get();
        
        // Calcola filtri attivi
        $activeFilters = [];
        $filterFields = ['compagnia', 'plotone_id', 'grado_id', 'polo_id', 'mansione_id', 'nos_status', 'ruolo_id', 'email_istituzionale', 'telefono'];
        
        foreach ($filterFields as $field) {
            if ($request->filled($field)) {
                $activeFilters[] = $field;
            }
        }
        
        $hasActiveFilters = count($activeFilters) > 0;
        
        return [
            'militari' => $militari,
            'gradi' => $gradi,
            'plotoni' => $plotoni,
            'poli' => $poli,
            'mansioni' => $mansioni,
            'ruoli' => $ruoli,
            'filtri' => $request->all(),
            'activeFilters' => $activeFilters,
            'hasActiveFilters' => $hasActiveFilters
        ];
    }

    /**
     * Aggiorna un militare via AJAX
     * 
     * @param int $militareId
     * @param array $data
     * @return array
     */
    public function updateMilitareAjax($militareId, $data)
    {
        try {
            $militare = Militare::findOrFail($militareId);
            
            // Filtra solo i campi permessi per l'aggiornamento
            $allowedFields = [
                'note', 
                'certificati_note', 
                'idoneita_note',
                'nome',
                'cognome',
                'grado_id',
                'plotone_id',
                'polo_id',
                'ruolo_id',
                'mansione_id'
            ];
            
            $updateData = array_intersect_key($data, array_flip($allowedFields));
            
            if (empty($updateData)) {
                return [
                    'success' => false,
                    'message' => 'Nessun campo valido da aggiornare'
                ];
            }
            
            $militare->update($updateData);
            
            // Invalida cache
            $this->invalidateCache($militareId);
            
            return [
                'success' => true,
                'message' => 'Dati aggiornati con successo',
                'updated_fields' => array_keys($updateData)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ottiene i dati per i form di creazione/modifica
     * 
     * @param Militare|null $militare
     * @return array
     */
    public function getFormData($militare = null)
    {
        $formOptions = $this->getFormOptions();
        
        return [
            'militare' => $militare,
            'gradi' => $formOptions['gradi'],
            'plotoni' => $formOptions['plotoni'],
            'poli' => $formOptions['poli'],
            'ruoli' => $formOptions['ruoli'],
            'mansioni' => $formOptions['mansioni']
        ];
    }

    /**
     * Crea un nuovo militare
     * 
     * @param array $data
     * @return Militare
     */
    public function createMilitare($data)
    {
        // Valida i dati
        $rules = $this->getValidationRules();
        $validator = validator($data, $rules);
        
        if ($validator->fails()) {
            throw new \Exception('Dati non validi: ' . implode(', ', $validator->errors()->all()));
        }
        
        // Crea il militare
        $militare = Militare::create($data);
        
        // Invalida cache
        $this->invalidateAllCache();
        
        return $militare;
    }

    /**
     * Aggiorna un militare esistente
     * 
     * @param int $militareId
     * @param array $data
     * @return Militare
     */
    public function updateMilitare($militareId, $data)
    {
        $militare = Militare::findOrFail($militareId);
        
        // Valida i dati
        $rules = $this->getValidationRules($militare);
        $validator = validator($data, $rules);
        
        if ($validator->fails()) {
            throw new \Exception('Dati non validi: ' . implode(', ', $validator->errors()->all()));
        }
        
        // Aggiorna il militare
        $militare->update($data);
        
        // Invalida cache
        $this->invalidateCache($militareId);
        
        return $militare;
    }

    /**
     * Cerca militari per query
     * 
     * @param string $query
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchMilitari($query)
    {
        return $this->search($query, 50); // Usa il metodo search esistente con limite più alto
    }
} 
