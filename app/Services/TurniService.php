<?php

namespace App\Services;

use App\Models\TurnoSettimanale;
use App\Models\AssegnazioneTurno;
use App\Models\ServizioTurno;
use App\Models\Militare;
use App\Models\PianificazioneMensile;
use App\Models\PianificazioneGiornaliera;
use App\Models\TipoServizio;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TurniService
{
    private const SMONTANTE_CODICE = 'SMO';
    /**
     * Ottieni o crea il turno per una settimana specifica
     * 
     * @param Carbon $data Data qualsiasi della settimana
     * @return TurnoSettimanale
     */
    public function getTurnoSettimana(Carbon $data)
    {
        return TurnoSettimanale::createForDate($data);
    }

    /**
     * Ottieni i dati completi per la vista settimanale
     * 
     * @param Carbon $data
     * @return array
     */
    public function getDatiSettimana(Carbon $data)
    {
        $turno = $this->getTurnoSettimana($data);
        $serviziTurno = ServizioTurno::attivi()->ordinati()->get();
        $giorniSettimana = $turno->getGiorniSettimana();

        // Carica tutte le assegnazioni della settimana
        $assegnazioni = $turno->assegnazioni()
            ->with(['militare.grado', 'servizioTurno'])
            ->get()
            ->groupBy(function($item) {
                return $item->servizio_turno_id . '_' . $item->data_servizio->format('Y-m-d');
            });

        // Organizza i dati per la vista
        $matriceTurni = [];
        foreach ($serviziTurno as $servizio) {
            $matriceTurni[$servizio->id] = [
                'servizio' => $servizio,
                'assegnazioni' => []
            ];
            
            foreach ($giorniSettimana as $giorno) {
                $key = $servizio->id . '_' . $giorno['data']->format('Y-m-d');
                $matriceTurni[$servizio->id]['assegnazioni'][$giorno['data']->format('Y-m-d')] = 
                    $assegnazioni->get($key, collect());
            }
        }

        // Ottieni tutti i militari disponibili (ordinati per grado)
        $militari = Militare::with(['grado', 'plotone'])
            ->orderByGradoENome()
            ->get();

        return [
            'turno' => $turno,
            'serviziTurno' => $serviziTurno,
            'giorniSettimana' => $giorniSettimana,
            'matriceTurni' => $matriceTurni,
            'militari' => $militari,
        ];
    }

    /**
     * Assegna un militare a un servizio per una data specifica
     * 
     * @param int $turnoId
     * @param int $servizioId
     * @param int $militareId
     * @param string $data (Y-m-d)
     * @param bool $forzaSovrascrizione Se true, sovrascrive conflitti CPT
     * @return array ['success' => bool, 'message' => string, 'warning' => string|null, 'assegnazione' => AssegnazioneTurno|null]
     */
    public function assegnaMilitare($turnoId, $servizioId, $militareId, $data, $forzaSovrascrizione = false)
    {
        DB::beginTransaction();
        
        try {
            $turno = TurnoSettimanale::findOrFail($turnoId);
            $servizio = ServizioTurno::findOrFail($servizioId);
            $militare = Militare::findOrFail($militareId);
            $dataObj = Carbon::parse($data);

            // Verifica che la data sia nella settimana del turno
            if ($dataObj->lt($turno->data_inizio) || $dataObj->gt($turno->data_fine)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'La data non è valida per questo turno settimanale.',
                    'warning' => null,
                    'assegnazione' => null
                ];
            }

            // Verifica disponibilità militare
            $disponibilita = $militare->isDisponibile($data);
            
            if (!$disponibilita['disponibile'] && !$forzaSovrascrizione) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => $disponibilita['motivo'],
                    'warning' => 'conflitto',
                    'conflitto' => $disponibilita,
                    'assegnazione' => null
                ];
            }

            // VERIFICA CRITICA: Militare può avere SOLO UN turno per data
            $altreAssegnazioni = AssegnazioneTurno::where('militare_id', $militareId)
                ->where('data_servizio', $data)
                ->with('servizioTurno')
                ->get();

            if ($altreAssegnazioni->isNotEmpty()) {
                // Se è lo stesso servizio
                $giàStessoServizio = $altreAssegnazioni->where('servizio_turno_id', $servizioId)->first();
                if ($giàStessoServizio) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => 'Questo militare è già assegnato a questo servizio per questa data.',
                        'warning' => null,
                        'assegnazione' => null
                    ];
                }
                
                // Se è un altro servizio, NON permettere senza forzatura
                $altroServizio = $altreAssegnazioni->first();
                if (!$forzaSovrascrizione) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => 'Già assegnato al servizio: ' . $altroServizio->servizioTurno->nome,
                        'warning' => 'conflitto',
                        'conflitto' => [
                            'disponibile' => false,
                            'motivo' => 'Il militare è già assegnato al servizio: ' . $altroServizio->servizioTurno->nome . ' per questa data.'
                        ],
                        'assegnazione' => null
                    ];
                }
                
                // Se forziamo, rimuoviamo le altre assegnazioni per questa data
                foreach ($altreAssegnazioni as $vecchiaAssegnazione) {
                    $this->rimuoviDaCPT($vecchiaAssegnazione);
                    if ($vecchiaAssegnazione->servizioTurno?->smontante_cpt) {
                        $this->rimuoviSmontanteDaCPT($vecchiaAssegnazione);
                    }
                    $vecchiaAssegnazione->delete();
                }
            }

            // Calcola posizione e verifica limite posti
            $assegnazioniEsistenti = AssegnazioneTurno::where('turno_settimanale_id', $turnoId)
                ->where('servizio_turno_id', $servizioId)
                ->where('data_servizio', $data)
                ->count();

            $maxPosti = max((int) $servizio->num_posti, 1);
            if ($assegnazioniEsistenti >= $maxPosti) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => "Posti esauriti per il servizio \"{$servizio->nome}\" in questa data.",
                    'warning' => null,
                    'assegnazione' => null
                ];
            }

            // Determina il giorno della settimana
            $giorniSettimana = ['GIOVEDI', 'VENERDI', 'SABATO', 'DOMENICA', 'LUNEDI', 'MARTEDI', 'MERCOLEDI'];
            $indiceDayOfWeek = $dataObj->dayOfWeek;
            // Aggiusta: giovedì = 4 in PHP, ma è il giorno 0 della nostra settimana
            $indiceSettimana = ($indiceDayOfWeek + 3) % 7;
            $giornoSettimana = $giorniSettimana[$indiceSettimana];

            // Crea l'assegnazione
            $assegnazione = AssegnazioneTurno::create([
                'turno_settimanale_id' => $turnoId,
                'servizio_turno_id' => $servizioId,
                'militare_id' => $militareId,
                'data_servizio' => $data,
                'giorno_settimana' => $giornoSettimana,
                'posizione' => $assegnazioniEsistenti + 1,
                'sincronizzato_cpt' => false,
            ]);

            // Sincronizza con CPT
            $warningMessage = null;
            if ($forzaSovrascrizione && !$disponibilita['disponibile']) {
                $warningMessage = 'ATTENZIONE: Sovrascritto conflitto CPT: ' . $disponibilita['motivo'];
            }

            $sincronizzato = $this->sincronizzaConCPT($assegnazione);
            
            if (!$sincronizzato) {
                $warningMessage = $warningMessage ? $warningMessage . ' - CPT non sincronizzato.' : 'CPT non sincronizzato automaticamente.';
            }

            if ($servizio->smontante_cpt) {
                $smontanteOk = $this->sincronizzaSmontante($assegnazione);
                if (!$smontanteOk) {
                    $warningMessage = $warningMessage
                        ? $warningMessage . ' - Smontante non sincronizzato.'
                        : 'Smontante non sincronizzato.';
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Militare assegnato con successo al servizio.',
                'warning' => $warningMessage,
                'assegnazione' => $assegnazione->load(['militare.grado', 'servizioTurno'])
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore assegnazione turno', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Errore durante l\'assegnazione: ' . $e->getMessage(),
                'warning' => null,
                'assegnazione' => null
            ];
        }
    }

    /**
     * Rimuovi un'assegnazione
     * 
     * @param int $assegnazioneId
     * @return array
     */
    public function rimuoviAssegnazione($assegnazioneId)
    {
        DB::beginTransaction();
        
        try {
            $assegnazione = AssegnazioneTurno::with('servizioTurno')->findOrFail($assegnazioneId);

            // Rimuovi anche dal CPT se era sincronizzato
            if ($assegnazione->sincronizzato_cpt) {
                $this->rimuoviDaCPT($assegnazione);
            }

            if ($assegnazione->servizioTurno?->smontante_cpt) {
                $this->rimuoviSmontanteDaCPT($assegnazione);
            }

            $assegnazione->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Assegnazione rimossa con successo.'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore rimozione assegnazione turno', [
                'error' => $e->getMessage(),
                'assegnazione_id' => $assegnazioneId
            ]);
            
            return [
                'success' => false,
                'message' => 'Errore durante la rimozione: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Copia le assegnazioni dalla settimana precedente
     * 
     * @param int $turnoId
     * @return array
     */
    public function copiaSettimanaPrecedente($turnoId)
    {
        DB::beginTransaction();
        
        try {
            $turno = TurnoSettimanale::findOrFail($turnoId);
            
            // Trova il turno della settimana precedente
            $turnoPrecedente = TurnoSettimanale::where('anno', $turno->anno)
                ->where('numero_settimana', $turno->numero_settimana - 1)
                ->first();

            if (!$turnoPrecedente) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Nessuna settimana precedente trovata.'
                ];
            }

            // Ottieni tutte le assegnazioni della settimana precedente
            $assegnazioniPrecedenti = $turnoPrecedente->assegnazioni;

            if ($assegnazioniPrecedenti->isEmpty()) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'La settimana precedente non ha assegnazioni.'
                ];
            }

            $copiate = 0;
            $giorniDiff = $turno->data_inizio->diffInDays($turnoPrecedente->data_inizio);

            foreach ($assegnazioniPrecedenti as $vecchia) {
                // Calcola la nuova data (stessa posizione nella settimana)
                $nuovaData = Carbon::parse($vecchia->data_servizio)->addDays($giorniDiff);

                // Verifica se non esiste già un'assegnazione
                $esiste = AssegnazioneTurno::where('turno_settimanale_id', $turnoId)
                    ->where('servizio_turno_id', $vecchia->servizio_turno_id)
                    ->where('militare_id', $vecchia->militare_id)
                    ->where('data_servizio', $nuovaData)
                    ->exists();

                if (!$esiste) {
                    AssegnazioneTurno::create([
                        'turno_settimanale_id' => $turnoId,
                        'servizio_turno_id' => $vecchia->servizio_turno_id,
                        'militare_id' => $vecchia->militare_id,
                        'data_servizio' => $nuovaData,
                        'giorno_settimana' => $vecchia->giorno_settimana,
                        'posizione' => $vecchia->posizione,
                        'sincronizzato_cpt' => false,
                    ]);
                    $copiate++;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Copiate $copiate assegnazioni dalla settimana precedente."
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore copia settimana precedente', [
                'error' => $e->getMessage(),
                'turno_id' => $turnoId
            ]);
            
            return [
                'success' => false,
                'message' => 'Errore durante la copia: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sincronizza un'assegnazione con il CPT
     * 
     * @param AssegnazioneTurno $assegnazione
     * @return bool
     */
    protected function sincronizzaConCPT(AssegnazioneTurno $assegnazione)
    {
        try {
            $servizio = $assegnazione->servizioTurno;
            
            // Se non ha una sigla CPT, non sincronizzare
            if (!$servizio->sigla_cpt) {
                return false;
            }

            // Trova il tipo servizio corrispondente
            $tipoServizio = TipoServizio::where('codice', $servizio->sigla_cpt)->first();
            
            if (!$tipoServizio) {
                Log::warning('Tipo servizio CPT non trovato', [
                    'sigla_cpt' => $servizio->sigla_cpt,
                    'assegnazione_id' => $assegnazione->id
                ]);
                return false;
            }

            $dataServizio = Carbon::parse($assegnazione->data_servizio);
            
            // Trova o crea la pianificazione mensile
            $pianificazioneMensile = PianificazioneMensile::firstOrCreate(
                [
                    'mese' => $dataServizio->month,
                    'anno' => $dataServizio->year,
                ],
                [
                    'nome' => $dataServizio->format('F Y'),
                    'stato' => 'attiva',
                    'data_creazione' => $dataServizio->format('Y-m-d'),
                ]
            );

            // Crea o aggiorna la pianificazione giornaliera
            PianificazioneGiornaliera::updateOrCreate(
                [
                    'pianificazione_mensile_id' => $pianificazioneMensile->id,
                    'militare_id' => $assegnazione->militare_id,
                    'giorno' => $dataServizio->day,
                ],
                [
                    'tipo_servizio_id' => $tipoServizio->id,
                ]
            );

            // Marca come sincronizzato
            $assegnazione->marcaSincronizzato();

            return true;

        } catch (\Exception $e) {
            Log::error('Errore sincronizzazione CPT', [
                'error' => $e->getMessage(),
                'assegnazione_id' => $assegnazione->id
            ]);
            return false;
        }
    }

    /**
     * Rimuovi un'assegnazione dal CPT
     * 
     * @param AssegnazioneTurno $assegnazione
     * @return bool
     */
    protected function rimuoviDaCPT(AssegnazioneTurno $assegnazione)
    {
        try {
            $dataServizio = Carbon::parse($assegnazione->data_servizio);
            
            // Trova la pianificazione mensile
            $pianificazioneMensile = PianificazioneMensile::where('mese', $dataServizio->month)
                ->where('anno', $dataServizio->year)
                ->first();

            if (!$pianificazioneMensile) {
                return false;
            }

            // Rimuovi la pianificazione giornaliera
            PianificazioneGiornaliera::where('pianificazione_mensile_id', $pianificazioneMensile->id)
                ->where('militare_id', $assegnazione->militare_id)
                ->where('giorno', $dataServizio->day)
                ->delete();

            return true;

        } catch (\Exception $e) {
            Log::error('Errore rimozione da CPT', [
                'error' => $e->getMessage(),
                'assegnazione_id' => $assegnazione->id
            ]);
            return false;
        }
    }

    /**
     * Sincronizza lo smontante nel CPT (giorno successivo)
     */
    protected function sincronizzaSmontante(AssegnazioneTurno $assegnazione): bool
    {
        try {
            $tipoSmontante = TipoServizio::where('codice', self::SMONTANTE_CODICE)->first();
            if (!$tipoSmontante) {
                Log::warning('Codice smontante CPT non trovato', [
                    'codice' => self::SMONTANTE_CODICE,
                    'assegnazione_id' => $assegnazione->id
                ]);
                return false;
            }

            $dataSmontante = Carbon::parse($assegnazione->data_servizio)->addDay();
            $pianificazioneMensile = PianificazioneMensile::firstOrCreate(
                [
                    'mese' => $dataSmontante->month,
                    'anno' => $dataSmontante->year,
                ],
                [
                    'nome' => $dataSmontante->format('F Y'),
                    'stato' => 'attiva',
                    'data_creazione' => $dataSmontante->format('Y-m-d'),
                ]
            );

            PianificazioneGiornaliera::updateOrCreate(
                [
                    'pianificazione_mensile_id' => $pianificazioneMensile->id,
                    'militare_id' => $assegnazione->militare_id,
                    'giorno' => $dataSmontante->day,
                ],
                [
                    'tipo_servizio_id' => $tipoSmontante->id,
                ]
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Errore sincronizzazione smontante CPT', [
                'error' => $e->getMessage(),
                'assegnazione_id' => $assegnazione->id
            ]);
            return false;
        }
    }

    /**
     * Rimuovi lo smontante dal CPT (giorno successivo)
     */
    protected function rimuoviSmontanteDaCPT(AssegnazioneTurno $assegnazione): bool
    {
        try {
            $tipoSmontante = TipoServizio::where('codice', self::SMONTANTE_CODICE)->first();
            if (!$tipoSmontante) {
                return false;
            }

            $dataSmontante = Carbon::parse($assegnazione->data_servizio)->addDay();
            $pianificazioneMensile = PianificazioneMensile::where('mese', $dataSmontante->month)
                ->where('anno', $dataSmontante->year)
                ->first();

            if (!$pianificazioneMensile) {
                return false;
            }

            PianificazioneGiornaliera::where('pianificazione_mensile_id', $pianificazioneMensile->id)
                ->where('militare_id', $assegnazione->militare_id)
                ->where('giorno', $dataSmontante->day)
                ->where('tipo_servizio_id', $tipoSmontante->id)
                ->delete();

            return true;
        } catch (\Exception $e) {
            Log::error('Errore rimozione smontante CPT', [
                'error' => $e->getMessage(),
                'assegnazione_id' => $assegnazione->id
            ]);
            return false;
        }
    }

    /**
     * Sincronizza tutte le assegnazioni non sincronizzate di un turno
     * 
     * @param int $turnoId
     * @return array ['sincronizzate' => int, 'fallite' => int]
     */
    public function sincronizzaTutteAssegnazioni($turnoId)
    {
        $assegnazioni = AssegnazioneTurno::where('turno_settimanale_id', $turnoId)
            ->nonSincronizzate()
            ->get();

        $sincronizzate = 0;
        $fallite = 0;

        foreach ($assegnazioni as $assegnazione) {
            if ($this->sincronizzaConCPT($assegnazione)) {
                $sincronizzate++;
            } else {
                $fallite++;
            }
        }

        return [
            'sincronizzate' => $sincronizzate,
            'fallite' => $fallite
        ];
    }
}

