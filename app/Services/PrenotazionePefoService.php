<?php

namespace App\Services;

use App\Models\PrenotazionePefo;
use App\Models\Militare;
use App\Models\TipoServizio;
use App\Models\PianificazioneMensile;
use App\Models\PianificazioneGiornaliera;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service per la gestione delle prenotazioni PEFO
 * 
 * Gestisce la creazione, modifica, eliminazione e sincronizzazione
 * delle prenotazioni PEFO con il CPT.
 * 
 * Ogni prenotazione ha un tipo (agility/resistenza) e i militari
 * vengono confermati singolarmente o tutti insieme.
 */
class PrenotazionePefoService
{
    /**
     * Crea una nuova prenotazione PEFO
     *
     * @param array $data Deve contenere: tipo_prova, data_prenotazione
     * @return PrenotazionePefo
     */
    public function creaPrenotazione(array $data): PrenotazionePefo
    {
        return DB::transaction(function () use ($data) {
            $prenotazione = PrenotazionePefo::create([
                'tipo_prova' => $data['tipo_prova'],
                'data_prenotazione' => $data['data_prenotazione'],
                'stato' => PrenotazionePefo::STATO_ATTIVO,
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id()
            ]);

            // Auto-genera il nome
            $prenotazione->nome_prenotazione = $prenotazione->getNomeAutoGenerato();
            $prenotazione->save();

            Log::info('Prenotazione PEFO creata', [
                'prenotazione_id' => $prenotazione->id,
                'tipo' => $prenotazione->tipo_prova,
                'nome' => $prenotazione->getNomeAutoGenerato(),
                'data' => $prenotazione->data_prenotazione->format('Y-m-d'),
                'created_by' => auth()->id()
            ]);

            return $prenotazione;
        });
    }

    /**
     * Aggiunge militari a una prenotazione esistente
     * I militari vengono aggiunti con confermato = false (da confermare)
     *
     * @param PrenotazionePefo $prenotazione
     * @param array $militariIds
     * @return array Array con risultati dell'operazione
     */
    public function aggiungiMilitari(PrenotazionePefo $prenotazione, array $militariIds): array
    {
        return DB::transaction(function () use ($prenotazione, $militariIds) {
            $aggiunti = [];
            $giàPresenti = [];
            $errori = [];

            foreach ($militariIds as $militareId) {
                try {
                    // Verifica se il militare esiste
                    $militare = Militare::withoutGlobalScopes()->find($militareId);
                    if (!$militare) {
                        $errori[] = "Militare ID {$militareId} non trovato";
                        continue;
                    }

                    // Verifica se è già assegnato
                    if ($prenotazione->haMilitare($militareId)) {
                        $giàPresenti[] = $militareId;
                        continue;
                    }

                    // Aggiungi alla prenotazione con confermato = false
                    $prenotazione->militari()->attach($militareId, [
                        'confermato' => false,
                        'data_conferma' => null,
                        'confermato_da' => null
                    ]);
                    $aggiunti[] = $militareId;

                    // Sincronizza con CPT
                    $this->sincronizzaConCPT($prenotazione, $militareId);

                } catch (\Exception $e) {
                    $errori[] = "Errore per militare ID {$militareId}: " . $e->getMessage();
                    Log::error('Errore aggiunta militare a PEFO', [
                        'prenotazione_id' => $prenotazione->id,
                        'militare_id' => $militareId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Militari aggiunti a prenotazione PEFO', [
                'prenotazione_id' => $prenotazione->id,
                'tipo' => $prenotazione->tipo_prova,
                'aggiunti' => count($aggiunti),
                'già_presenti' => count($giàPresenti),
                'errori' => count($errori)
            ]);

            return [
                'success' => true,
                'aggiunti' => $aggiunti,
                'già_presenti' => $giàPresenti,
                'errori' => $errori
            ];
        });
    }

    /**
     * Conferma un singolo militare in una prenotazione
     * Aggiorna anche il campo data_agility o data_resistenza del militare
     *
     * @param PrenotazionePefo $prenotazione
     * @param int $militareId
     * @return bool
     */
    public function confermaMilitare(PrenotazionePefo $prenotazione, int $militareId): bool
    {
        return DB::transaction(function () use ($prenotazione, $militareId) {
            // Verifica che il militare sia nella prenotazione
            if (!$prenotazione->haMilitare($militareId)) {
                throw new \Exception("Il militare non è presente in questa prenotazione");
            }

            // Verifica che non sia già confermato
            if ($prenotazione->isMilitareConfermato($militareId)) {
                return true; // Già confermato, non serve fare nulla
            }

            // Aggiorna il pivot
            $prenotazione->militari()->updateExistingPivot($militareId, [
                'confermato' => true,
                'data_conferma' => Carbon::now(),
                'confermato_da' => auth()->id()
            ]);

            // Aggiorna il campo data_agility o data_resistenza del militare
            $militare = Militare::withoutGlobalScopes()->find($militareId);
            if ($militare) {
                $campo = $prenotazione->tipo_prova === PrenotazionePefo::TIPO_AGILITY 
                    ? 'data_agility' 
                    : 'data_resistenza';
                
                $militare->update([
                    $campo => $prenotazione->data_prenotazione
                ]);

                Log::info('Militare confermato in prenotazione PEFO', [
                    'prenotazione_id' => $prenotazione->id,
                    'militare_id' => $militareId,
                    'tipo_prova' => $prenotazione->tipo_prova,
                    'campo_aggiornato' => $campo,
                    'nuova_data' => $prenotazione->data_prenotazione->format('Y-m-d')
                ]);
            }

            return true;
        });
    }

    /**
     * Conferma tutti i militari non ancora confermati in una prenotazione
     *
     * @param PrenotazionePefo $prenotazione
     * @return int Numero di militari confermati
     */
    public function confermaAllMilitari(PrenotazionePefo $prenotazione): int
    {
        return DB::transaction(function () use ($prenotazione) {
            $militariDaConfermare = $prenotazione->getMilitariDaConfermare();
            $count = 0;

            foreach ($militariDaConfermare as $militare) {
                try {
                    $this->confermaMilitare($prenotazione, $militare->id);
                    $count++;
                } catch (\Exception $e) {
                    Log::error('Errore conferma militare in batch', [
                        'prenotazione_id' => $prenotazione->id,
                        'militare_id' => $militare->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Conferma batch militari PEFO', [
                'prenotazione_id' => $prenotazione->id,
                'militari_confermati' => $count
            ]);

            return $count;
        });
    }

    /**
     * Rimuove un militare da una prenotazione
     *
     * @param PrenotazionePefo $prenotazione
     * @param int $militareId
     * @return bool
     */
    public function rimuoviMilitare(PrenotazionePefo $prenotazione, int $militareId): bool
    {
        return DB::transaction(function () use ($prenotazione, $militareId) {
            // Rimuove dalla prenotazione
            $prenotazione->militari()->detach($militareId);

            // Rimuove dal CPT
            $this->rimuoviDaCPT($prenotazione, $militareId);

            Log::info('Militare rimosso da prenotazione PEFO', [
                'prenotazione_id' => $prenotazione->id,
                'militare_id' => $militareId
            ]);

            return true;
        });
    }

    /**
     * Annulla una prenotazione
     *
     * @param PrenotazionePefo $prenotazione
     * @return bool
     */
    public function annullaPrenotazione(PrenotazionePefo $prenotazione): bool
    {
        return DB::transaction(function () use ($prenotazione) {
            // Rimuove tutte le pianificazioni CPT collegate
            $this->rimuoviTutteDaCPT($prenotazione);

            // Annulla la prenotazione
            $prenotazione->annulla();

            Log::info('Prenotazione PEFO annullata', [
                'prenotazione_id' => $prenotazione->id
            ]);

            return true;
        });
    }

    /**
     * Elimina una prenotazione
     *
     * @param PrenotazionePefo $prenotazione
     * @return bool
     */
    public function eliminaPrenotazione(PrenotazionePefo $prenotazione): bool
    {
        return DB::transaction(function () use ($prenotazione) {
            // Rimuove tutte le pianificazioni CPT collegate
            $this->rimuoviTutteDaCPT($prenotazione);

            // Elimina la prenotazione (cascade elimina anche pivot)
            $prenotazione->delete();

            Log::info('Prenotazione PEFO eliminata', [
                'prenotazione_id' => $prenotazione->id
            ]);

            return true;
        });
    }

    /**
     * Sincronizza un militare con il CPT
     *
     * @param PrenotazionePefo $prenotazione
     * @param int $militareId
     * @return void
     */
    private function sincronizzaConCPT(PrenotazionePefo $prenotazione, int $militareId): void
    {
        try {
            $data = $prenotazione->data_prenotazione;

            // Cerca il tipo servizio PEFO (o AGILITY/RESISTENZA se esistono)
            $codice = 'PEFO'; // Default
            $tipoServizio = TipoServizio::where('codice', $codice)
                ->where('attivo', true)
                ->first();

            if (!$tipoServizio) {
                Log::warning('Tipo servizio PEFO non trovato');
                return;
            }

            // Ottieni o crea la pianificazione mensile
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

            // Ottieni l'unità organizzativa del militare
            $militare = Militare::withoutGlobalScopes()->find($militareId);
            $unitId = $militare?->organizational_unit_id;

            // Nota per il CPT con il tipo di prova
            $nota = ucfirst($prenotazione->tipo_prova) . " - " . $prenotazione->getDataFormattata();

            // Verifica se esiste già una pianificazione per questo giorno/militare
            $esisteGia = PianificazioneGiornaliera::where([
                'pianificazione_mensile_id' => $pianificazioneMensile->id,
                'militare_id' => $militareId,
                'giorno' => $data->day,
            ])->exists();

            if (!$esisteGia) {
                PianificazioneGiornaliera::create([
                    'pianificazione_mensile_id' => $pianificazioneMensile->id,
                    'militare_id' => $militareId,
                    'giorno' => $data->day,
                    'tipo_servizio_id' => $tipoServizio->id,
                    'organizational_unit_id' => $unitId,
                    'note' => $nota,
                    'prenotazione_pefo_id' => $prenotazione->id
                ]);

                Log::debug('Pianificazione CPT creata per PEFO', [
                    'prenotazione_id' => $prenotazione->id,
                    'tipo' => $prenotazione->tipo_prova,
                    'militare_id' => $militareId,
                    'data' => $data->format('Y-m-d')
                ]);
            } else {
                // Aggiorna la pianificazione esistente con il collegamento PEFO
                PianificazioneGiornaliera::where([
                    'pianificazione_mensile_id' => $pianificazioneMensile->id,
                    'militare_id' => $militareId,
                    'giorno' => $data->day,
                ])->update([
                    'tipo_servizio_id' => $tipoServizio->id,
                    'note' => $nota,
                    'prenotazione_pefo_id' => $prenotazione->id
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Errore sincronizzazione CPT per PEFO', [
                'prenotazione_id' => $prenotazione->id,
                'militare_id' => $militareId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Rimuove un militare dal CPT per una prenotazione
     *
     * @param PrenotazionePefo $prenotazione
     * @param int $militareId
     * @return void
     */
    private function rimuoviDaCPT(PrenotazionePefo $prenotazione, int $militareId): void
    {
        try {
            // Rimuove solo le pianificazioni collegate a questa prenotazione PEFO
            PianificazioneGiornaliera::where('prenotazione_pefo_id', $prenotazione->id)
                ->where('militare_id', $militareId)
                ->delete();

            Log::debug('Pianificazione CPT rimossa per PEFO', [
                'prenotazione_id' => $prenotazione->id,
                'militare_id' => $militareId
            ]);

        } catch (\Exception $e) {
            Log::error('Errore rimozione da CPT per PEFO', [
                'prenotazione_id' => $prenotazione->id,
                'militare_id' => $militareId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Rimuove tutte le pianificazioni CPT collegate a una prenotazione
     *
     * @param PrenotazionePefo $prenotazione
     * @return void
     */
    private function rimuoviTutteDaCPT(PrenotazionePefo $prenotazione): void
    {
        try {
            $count = PianificazioneGiornaliera::where('prenotazione_pefo_id', $prenotazione->id)->delete();

            Log::debug('Tutte le pianificazioni CPT rimosse per PEFO', [
                'prenotazione_id' => $prenotazione->id,
                'pianificazioni_rimosse' => $count
            ]);

        } catch (\Exception $e) {
            Log::error('Errore rimozione tutte da CPT per PEFO', [
                'prenotazione_id' => $prenotazione->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Ottiene le prenotazioni attive ordinate per data
     *
     * @param string|null $tipoProva Filtro per tipo prova (agility/resistenza)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPrenotazioniAttive(?string $tipoProva = null)
    {
        $query = PrenotazionePefo::with(['militari.grado', 'creatore'])
            ->attive()
            ->ordinatePerData('asc');

        if ($tipoProva) {
            $query->where('tipo_prova', $tipoProva);
        }

        return $query->get();
    }

    /**
     * Ottiene le prenotazioni future
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPrenotazioniFuture()
    {
        return PrenotazionePefo::with(['militari.grado', 'creatore'])
            ->attive()
            ->future()
            ->ordinatePerData('asc')
            ->get();
    }

    /**
     * Ottiene le statistiche delle prenotazioni
     *
     * @return array
     */
    public function getStatistiche(): array
    {
        return [
            'totale' => PrenotazionePefo::count(),
            'attive' => PrenotazionePefo::attive()->count(),
            'agility' => PrenotazionePefo::attive()->agility()->count(),
            'resistenza' => PrenotazionePefo::attive()->resistenza()->count(),
            'future' => PrenotazionePefo::future()->attive()->count(),
            'militari_totali' => DB::table('militari_prenotazioni_pefo')
                ->join('prenotazioni_pefo', 'militari_prenotazioni_pefo.prenotazione_pefo_id', '=', 'prenotazioni_pefo.id')
                ->where('prenotazioni_pefo.stato', PrenotazionePefo::STATO_ATTIVO)
                ->count(),
            'militari_confermati' => DB::table('militari_prenotazioni_pefo')
                ->join('prenotazioni_pefo', 'militari_prenotazioni_pefo.prenotazione_pefo_id', '=', 'prenotazioni_pefo.id')
                ->where('prenotazioni_pefo.stato', PrenotazionePefo::STATO_ATTIVO)
                ->where('militari_prenotazioni_pefo.confermato', true)
                ->count(),
            'militari_da_confermare' => DB::table('militari_prenotazioni_pefo')
                ->join('prenotazioni_pefo', 'militari_prenotazioni_pefo.prenotazione_pefo_id', '=', 'prenotazioni_pefo.id')
                ->where('prenotazioni_pefo.stato', PrenotazionePefo::STATO_ATTIVO)
                ->where('militari_prenotazioni_pefo.confermato', false)
                ->count()
        ];
    }
}
