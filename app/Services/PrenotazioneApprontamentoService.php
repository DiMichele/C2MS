<?php

namespace App\Services;

use App\Models\PrenotazioneApprontamento;
use App\Models\BoardActivity;
use App\Models\BoardColumn;
use App\Models\PianificazioneGiornaliera;
use App\Models\PianificazioneMensile;
use App\Models\TipoServizio;
use App\Models\Militare;
use App\Models\TeatroOperativo;
use App\Models\ScadenzaApprontamento;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service per la gestione centralizzata delle Prenotazioni Approntamenti
 * 
 * Garantisce la consistenza dei dati tra:
 * - prenotazioni_approntamenti (fonte primaria)
 * - board_activities (Board Attività)
 * - pianificazioni_giornaliere (CPT)
 * 
 * Tutte le operazioni CRUD devono passare da questo service per mantenere
 * la sincronizzazione bidirezionale.
 */
class PrenotazioneApprontamentoService
{
    /**
     * Crea una nuova prenotazione e sincronizza con Board e CPT
     *
     * @param int $teatroId
     * @param string $cattedra
     * @param Carbon $data
     * @param array $militariIds
     * @param int|null $createdBy
     * @param string|null $note
     * @return array ['success' => bool, 'prenotazioni' => array, 'message' => string]
     */
    public function creaPrenotazione(
        int $teatroId,
        string $cattedra,
        Carbon $data,
        array $militariIds,
        ?int $createdBy = null,
        ?string $note = null
    ): array {
        $labels = ScadenzaApprontamento::getLabels();
        $cattedraLabel = $labels[$cattedra] ?? $cattedra;
        
        DB::beginTransaction();
        
        try {
            $prenotazioniCreate = [];
            $giàPrenotati = 0;

            foreach ($militariIds as $militareId) {
                // Verifica che non esista già una prenotazione attiva
                $esistente = PrenotazioneApprontamento::where([
                    'militare_id' => $militareId,
                    'teatro_operativo_id' => $teatroId,
                    'cattedra' => $cattedra,
                    'data_prenotazione' => $data->format('Y-m-d'),
                ])->whereIn('stato', ['prenotato', 'confermato'])->exists();

                if ($esistente) {
                    $giàPrenotati++;
                    continue;
                }

                // Crea la prenotazione
                $prenotazione = PrenotazioneApprontamento::create([
                    'militare_id' => $militareId,
                    'teatro_operativo_id' => $teatroId,
                    'cattedra' => $cattedra,
                    'data_prenotazione' => $data->format('Y-m-d'),
                    'stato' => 'prenotato',
                    'note' => $note,
                    'created_by' => $createdBy ?? auth()->id()
                ]);

                $prenotazioniCreate[] = $prenotazione;
            }

            if (!empty($prenotazioniCreate)) {
                $prenotazioneIds = collect($prenotazioniCreate)->pluck('id')->toArray();
                $militariPrenotati = collect($prenotazioniCreate)->pluck('militare_id')->toArray();
                
                // Sincronizza con CPT
                $this->sincronizzaConCPT($cattedraLabel, $data, $militariPrenotati, $prenotazioniCreate);
                
                // Sincronizza con Board
                $this->sincronizzaConBoard($cattedraLabel, $data, $militariPrenotati, $teatroId, $prenotazioniCreate);
            }

            DB::commit();

            $message = count($prenotazioniCreate) > 0 
                ? "Prenotazioni create: " . count($prenotazioniCreate) 
                : "Nessuna nuova prenotazione";
            
            if ($giàPrenotati > 0) {
                $message .= " ({$giàPrenotati} già prenotati)";
            }

            return [
                'success' => true,
                'prenotazioni' => $prenotazioniCreate,
                'count' => count($prenotazioniCreate),
                'message' => $message
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PrenotazioneApprontamentoService::creaPrenotazione', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'prenotazioni' => [],
                'count' => 0,
                'message' => 'Errore durante la creazione: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Modifica una prenotazione esistente e propaga le modifiche
     *
     * @param PrenotazioneApprontamento $prenotazione
     * @param array $data ['data_prenotazione' => Carbon, 'note' => string, etc.]
     * @return array
     */
    public function modificaPrenotazione(PrenotazioneApprontamento $prenotazione, array $data): array
    {
        DB::beginTransaction();
        
        try {
            $oldData = $prenotazione->data_prenotazione;
            $labels = ScadenzaApprontamento::getLabels();
            $cattedraLabel = $labels[$prenotazione->cattedra] ?? $prenotazione->cattedra;
            
            // Se cambia la data, aggiorna anche CPT e Board
            $dataChanged = isset($data['data_prenotazione']) && 
                           $data['data_prenotazione'] != $oldData->format('Y-m-d');
            
            // Aggiorna la prenotazione
            $prenotazione->update($data);
            
            if ($dataChanged) {
                $newData = Carbon::parse($data['data_prenotazione']);
                
                // Aggiorna CPT
                $this->aggiornaDataCPT($prenotazione, $oldData, $newData, $cattedraLabel);
                
                // Aggiorna Board
                $this->aggiornaDataBoard($prenotazione, $newData);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Prenotazione aggiornata con successo'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PrenotazioneApprontamentoService::modificaPrenotazione', [
                'prenotazione_id' => $prenotazione->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Errore durante la modifica: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Elimina una prenotazione e tutte le entità collegate (cascata)
     *
     * @param PrenotazioneApprontamento $prenotazione
     * @return array
     */
    public function eliminaPrenotazione(PrenotazioneApprontamento $prenotazione): array
    {
        DB::beginTransaction();
        
        try {
            $labels = ScadenzaApprontamento::getLabels();
            $cattedraLabel = $labels[$prenotazione->cattedra] ?? $prenotazione->cattedra;
            
            // Rimuovi da CPT
            $this->rimuoviDaCPT($prenotazione);
            
            // Rimuovi da Board (o scollega il militare)
            $this->rimuoviDaBoard($prenotazione);
            
            // Elimina la prenotazione
            $prenotazione->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Prenotazione eliminata con successo'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PrenotazioneApprontamentoService::eliminaPrenotazione', [
                'prenotazione_id' => $prenotazione->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Conferma una prenotazione e aggiorna le scadenze
     *
     * @param PrenotazioneApprontamento $prenotazione
     * @param string|null $dataEffettiva
     * @param int|null $confirmedBy
     * @return array
     */
    public function confermaPrenotazione(
        PrenotazioneApprontamento $prenotazione, 
        ?string $dataEffettiva = null,
        ?int $confirmedBy = null
    ): array {
        DB::beginTransaction();
        
        try {
            // Usa data effettiva o data prenotazione
            $dataEffettiva = $dataEffettiva ?: $prenotazione->data_prenotazione->format('d/m/Y');
            
            // Aggiorna stato prenotazione
            $prenotazione->stato = 'confermato';
            $prenotazione->data_conferma = now();
            $prenotazione->confirmed_by = $confirmedBy ?? auth()->id();
            $prenotazione->save();
            
            // Aggiorna la scadenza del militare
            $this->aggiornaScadenzaMilitare($prenotazione, $dataEffettiva);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Prenotazione confermata con successo'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PrenotazioneApprontamentoService::confermaPrenotazione', [
                'prenotazione_id' => $prenotazione->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Errore durante la conferma: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sincronizza quando viene modificata un'attività dalla Board
     * Propaga le modifiche alle prenotazioni collegate
     *
     * @param BoardActivity $activity
     * @param array $changes ['start_date' => Carbon, 'end_date' => Carbon, 'removed_militari' => array]
     * @return void
     */
    public function sincronizzaDaBoard(BoardActivity $activity, array $changes): void
    {
        if (!$activity->prenotazione_approntamento_id) {
            // L'attività non è collegata a prenotazioni, verifica se ci sono prenotazioni collegate
            $prenotazioni = PrenotazioneApprontamento::whereHas('boardActivity', function($q) use ($activity) {
                $q->where('id', $activity->id);
            })->get();
            
            if ($prenotazioni->isEmpty()) {
                return;
            }
        }

        try {
            // Se cambia la data
            if (isset($changes['start_date'])) {
                $prenotazioni = PrenotazioneApprontamento::where('prenotazione_approntamento_id', $activity->prenotazione_approntamento_id)
                    ->orWhereHas('boardActivity', function($q) use ($activity) {
                        $q->where('id', $activity->id);
                    })
                    ->get();

                foreach ($prenotazioni as $prenotazione) {
                    if ($prenotazione->stato === 'prenotato') {
                        $prenotazione->data_prenotazione = $changes['start_date'];
                        $prenotazione->save();
                    }
                }
            }

            // Se vengono rimossi militari
            if (isset($changes['removed_militari']) && !empty($changes['removed_militari'])) {
                foreach ($changes['removed_militari'] as $militareId) {
                    $prenotazione = PrenotazioneApprontamento::where('militare_id', $militareId)
                        ->whereHas('boardActivity', function($q) use ($activity) {
                            $q->where('id', $activity->id);
                        })
                        ->first();

                    if ($prenotazione && $prenotazione->stato === 'prenotato') {
                        $this->eliminaPrenotazione($prenotazione);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('PrenotazioneApprontamentoService::sincronizzaDaBoard', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sincronizza quando viene modificata una pianificazione dal CPT
     * Propaga le modifiche alle prenotazioni collegate
     *
     * @param PianificazioneGiornaliera $pianificazione
     * @param string $action 'update' | 'delete'
     * @return void
     */
    public function sincronizzaDaCPT(PianificazioneGiornaliera $pianificazione, string $action): void
    {
        if (!$pianificazione->prenotazione_approntamento_id) {
            return;
        }

        try {
            $prenotazione = PrenotazioneApprontamento::find($pianificazione->prenotazione_approntamento_id);
            
            if (!$prenotazione || $prenotazione->stato !== 'prenotato') {
                return;
            }

            if ($action === 'delete') {
                // Se rimuovono dal CPT, elimina la prenotazione
                $this->eliminaPrenotazione($prenotazione);
            }

        } catch (\Exception $e) {
            Log::error('PrenotazioneApprontamentoService::sincronizzaDaCPT', [
                'pianificazione_id' => $pianificazione->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Elimina tutte le prenotazioni collegate a un'attività Board (cascata)
     *
     * @param BoardActivity $activity
     * @return int Numero di prenotazioni eliminate
     */
    public function eliminaDaBoard(BoardActivity $activity): int
    {
        $count = 0;
        
        // Trova tutte le prenotazioni collegate
        $prenotazioni = PrenotazioneApprontamento::where(function($q) use ($activity) {
            $q->whereHas('boardActivity', function($subQ) use ($activity) {
                $subQ->where('id', $activity->id);
            });
        })->get();

        foreach ($prenotazioni as $prenotazione) {
            $result = $this->eliminaPrenotazione($prenotazione);
            if ($result['success']) {
                $count++;
            }
        }

        return $count;
    }

    // ==========================================
    // METODI PRIVATI DI SINCRONIZZAZIONE
    // ==========================================

    /**
     * Sincronizza la prenotazione con il CPT
     */
    private function sincronizzaConCPT(string $cattedraLabel, Carbon $data, array $militariIds, array $prenotazioni): void
    {
        try {
            $tipoServizio = TipoServizio::where('codice', 'Cattedra')
                ->where('attivo', true)
                ->first();
            
            if (!$tipoServizio) {
                $tipoServizio = TipoServizio::where('codice', 'CATT')
                    ->where('attivo', true)
                    ->first();
            }
            
            if (!$tipoServizio) {
                Log::warning('Tipo servizio Cattedra non trovato', ['cattedra' => $cattedraLabel]);
                return;
            }

            $pianificazioneMensile = PianificazioneMensile::firstOrCreate(
                [
                    'mese' => $data->month,
                    'anno' => $data->year,
                ],
                [
                    'nome' => $data->translatedFormat('F Y'),
                    'stato' => 'attiva',
                    'data_creazione' => $data->format('Y-m-d'),
                ]
            );

            foreach ($prenotazioni as $prenotazione) {
                $esisteGia = PianificazioneGiornaliera::where([
                    'pianificazione_mensile_id' => $pianificazioneMensile->id,
                    'militare_id' => $prenotazione->militare_id,
                    'giorno' => $data->day,
                ])->exists();

                if (!$esisteGia) {
                    PianificazioneGiornaliera::create([
                        'pianificazione_mensile_id' => $pianificazioneMensile->id,
                        'militare_id' => $prenotazione->militare_id,
                        'giorno' => $data->day,
                        'tipo_servizio_id' => $tipoServizio->id,
                        'note' => "Cattedra: {$cattedraLabel}",
                        'prenotazione_approntamento_id' => $prenotazione->id
                    ]);
                } else {
                    // Aggiorna con il collegamento
                    PianificazioneGiornaliera::where([
                        'pianificazione_mensile_id' => $pianificazioneMensile->id,
                        'militare_id' => $prenotazione->militare_id,
                        'giorno' => $data->day,
                    ])->update(['prenotazione_approntamento_id' => $prenotazione->id]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Errore sincronizzazione CPT', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sincronizza la prenotazione con la Board Attività
     */
    private function sincronizzaConBoard(string $cattedraLabel, Carbon $data, array $militariIds, int $teatroId, array $prenotazioni): void
    {
        try {
            $columnaCattedre = BoardColumn::where('slug', 'cattedre')->first();
            
            if (!$columnaCattedre) {
                $columnaCattedre = BoardColumn::where('name', 'like', '%attedre%')->first();
            }
            
            if (!$columnaCattedre) {
                Log::warning('Colonna Cattedre non trovata nella Board');
                return;
            }

            $titolo = "{$cattedraLabel} - Approntamento";
            
            // Cerca attività esistente per questa cattedra e data
            $attivitaEsistente = BoardActivity::where('title', $titolo)
                ->where('start_date', $data->format('Y-m-d'))
                ->first();

            if ($attivitaEsistente) {
                // Aggiungi militari all'attività esistente
                $militariAttuali = $attivitaEsistente->militari()->pluck('militare_id')->toArray();
                $nuoviMilitari = array_diff($militariIds, $militariAttuali);
                
                if (!empty($nuoviMilitari)) {
                    $attivitaEsistente->militari()->attach($nuoviMilitari);
                }
                
                // Collega le prenotazioni all'attività
                foreach ($prenotazioni as $prenotazione) {
                    if (in_array($prenotazione->militare_id, $nuoviMilitari) || in_array($prenotazione->militare_id, $militariAttuali)) {
                        // L'attività Board è condivisa, collegala
                        $attivitaEsistente->prenotazione_approntamento_id = $prenotazione->id;
                        $attivitaEsistente->save();
                    }
                }
            } else {
                // Crea nuova attività
                $teatro = TeatroOperativo::find($teatroId);
                $primoMilitare = Militare::find($militariIds[0]);
                $compagniaMountingId = $primoMilitare?->compagnia_id ?? 1;

                $activity = BoardActivity::create([
                    'title' => $titolo,
                    'description' => "Cattedra approntamento per " . ($teatro->nome ?? 'Teatro Operativo'),
                    'start_date' => $data->format('Y-m-d'),
                    'end_date' => $data->format('Y-m-d'),
                    'column_id' => $columnaCattedre->id,
                    'compagnia_id' => $compagniaMountingId,
                    'compagnia_mounting_id' => $compagniaMountingId,
                    'sigla_cpt_suggerita' => 'Cattedra',
                    'created_by' => auth()->id() ?? 1,
                    'order' => BoardActivity::where('column_id', $columnaCattedre->id)->max('order') + 1,
                    'prenotazione_approntamento_id' => $prenotazioni[0]->id ?? null
                ]);

                $activity->militari()->attach($militariIds);
            }

        } catch (\Exception $e) {
            Log::error('Errore sincronizzazione Board', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Rimuove la pianificazione CPT collegata alla prenotazione
     */
    private function rimuoviDaCPT(PrenotazioneApprontamento $prenotazione): void
    {
        try {
            // Rimuovi per ID prenotazione
            PianificazioneGiornaliera::where('prenotazione_approntamento_id', $prenotazione->id)
                ->delete();

            // Rimuovi anche per matching (backup)
            $data = $prenotazione->data_prenotazione;
            $pianificazioneMensile = PianificazioneMensile::where('anno', $data->year)
                ->where('mese', $data->month)
                ->first();

            if ($pianificazioneMensile) {
                $labels = ScadenzaApprontamento::getLabels();
                $cattedraLabel = $labels[$prenotazione->cattedra] ?? $prenotazione->cattedra;
                
                PianificazioneGiornaliera::where([
                    'pianificazione_mensile_id' => $pianificazioneMensile->id,
                    'militare_id' => $prenotazione->militare_id,
                    'giorno' => $data->day,
                ])->where('note', 'like', "%{$cattedraLabel}%")
                  ->delete();
            }

        } catch (\Exception $e) {
            Log::error('Errore rimozione da CPT', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Rimuove/scollega il militare dalla Board Activity
     */
    private function rimuoviDaBoard(PrenotazioneApprontamento $prenotazione): void
    {
        try {
            // Trova l'attività collegata
            $activity = BoardActivity::where('prenotazione_approntamento_id', $prenotazione->id)->first();
            
            if (!$activity) {
                // Cerca per matching
                $labels = ScadenzaApprontamento::getLabels();
                $cattedraLabel = $labels[$prenotazione->cattedra] ?? $prenotazione->cattedra;
                $titolo = "{$cattedraLabel} - Approntamento";
                
                $activity = BoardActivity::where('title', $titolo)
                    ->where('start_date', $prenotazione->data_prenotazione->format('Y-m-d'))
                    ->first();
            }

            if ($activity) {
                // Scollega il militare
                $activity->militari()->detach($prenotazione->militare_id);
                
                // Se non ci sono più militari, elimina l'attività
                if ($activity->militari()->count() === 0) {
                    $activity->delete();
                }
            }

        } catch (\Exception $e) {
            Log::error('Errore rimozione da Board', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Aggiorna la data nel CPT quando cambia la prenotazione
     */
    private function aggiornaDataCPT(PrenotazioneApprontamento $prenotazione, Carbon $oldData, Carbon $newData, string $cattedraLabel): void
    {
        try {
            // Trova e rimuovi la vecchia pianificazione
            $oldPianificazioneMensile = PianificazioneMensile::where('anno', $oldData->year)
                ->where('mese', $oldData->month)
                ->first();

            if ($oldPianificazioneMensile) {
                PianificazioneGiornaliera::where([
                    'pianificazione_mensile_id' => $oldPianificazioneMensile->id,
                    'militare_id' => $prenotazione->militare_id,
                    'giorno' => $oldData->day,
                    'prenotazione_approntamento_id' => $prenotazione->id
                ])->delete();
            }

            // Crea la nuova pianificazione
            $this->sincronizzaConCPT($cattedraLabel, $newData, [$prenotazione->militare_id], [$prenotazione]);

        } catch (\Exception $e) {
            Log::error('Errore aggiornamento data CPT', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Aggiorna la data nella Board quando cambia la prenotazione
     */
    private function aggiornaDataBoard(PrenotazioneApprontamento $prenotazione, Carbon $newData): void
    {
        try {
            $activity = BoardActivity::where('prenotazione_approntamento_id', $prenotazione->id)->first();
            
            if ($activity) {
                $activity->start_date = $newData->format('Y-m-d');
                $activity->end_date = $newData->format('Y-m-d');
                $activity->save();
            }

        } catch (\Exception $e) {
            Log::error('Errore aggiornamento data Board', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Aggiorna la scadenza del militare quando viene confermata la prenotazione
     */
    private function aggiornaScadenzaMilitare(PrenotazioneApprontamento $prenotazione, string $dataEffettiva): void
    {
        try {
            $militare = Militare::find($prenotazione->militare_id);
            if (!$militare) return;

            $cattedra = $prenotazione->cattedra;
            
            // Aggiorna la scadenza approntamento
            $scadenza = ScadenzaApprontamento::firstOrCreate(
                ['militare_id' => $militare->id],
                ['teatro_operativo' => null]
            );

            // Se è una colonna condivisa, aggiorna scadenze_militari
            if (ScadenzaApprontamento::isColonnaCondivisa($cattedra)) {
                $campoSorgente = ScadenzaApprontamento::getCampoSorgente($cattedra);
                $scadenzaMilitare = \App\Models\ScadenzaMilitare::firstOrCreate(['militare_id' => $militare->id]);
                $scadenzaMilitare->$campoSorgente = $dataEffettiva;
                $scadenzaMilitare->save();
            } else {
                $scadenza->$cattedra = $dataEffettiva;
                $scadenza->save();
            }

        } catch (\Exception $e) {
            Log::error('Errore aggiornamento scadenza militare', ['error' => $e->getMessage()]);
        }
    }
}
