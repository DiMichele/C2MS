<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Militare;
use App\Models\ScadenzaMilitare;
use App\Models\Plotone;
use App\Models\Polo;
use App\Models\BoardActivity;
use App\Models\PianificazioneMensile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Controller v2.0 - Ottimizzato per produzione
 * 
 * Dashboard completa con:
 * - KPI principali in tempo reale
 * - Criticità e scadenze urgenti
 * - Scorciatoie verso funzioni principali
 * - Statistiche e grafici situazione compagnia
 */
class DashboardController extends Controller
{
    private const CACHE_DURATION = 600; // 10 minuti per dati semi-statici
    private const CACHE_DURATION_DYNAMIC = 60; // 1 minuto per dati dinamici
    
    /**
     * Dashboard principale con visione completa della situazione
     */
    public function index()
    {
        // === KPI PRINCIPALI ===
        $kpis = $this->getKPIs();
        
        // === SCADENZE REALI DAL DATABASE ===
        $scadenzeRspp = $this->getScadenzeRspp();
        $scadenzeIdoneita = $this->getScadenzeIdoneita();
        $scadenzePoligoni = $this->getScadenzePoligoni();
        
        // === ATTIVITÀ BOARD IN CORSO ===
        $attivitaOggi = $this->getAttivitaBoardOggi();
        
        // === COMPLEANNI DI OGGI ===
        $compleanniOggi = $this->getCompleanniOggi();
        
        return view('dashboard', compact(
            'kpis',
            'scadenzeRspp',
            'scadenzeIdoneita',
            'scadenzePoligoni',
            'attivitaOggi',
            'compleanniOggi'
        ));
    }
    
    /**
     * KPI principali
     */
    private function getKPIs()
    {
        return Cache::remember('dashboard.kpis', self::CACHE_DURATION_DYNAMIC, function () {
            $oggi = Carbon::today();
            $totaleMilitari = Militare::count();
            
            // Calcola presenti/assenti dal CPT
            $presenzaDaCPT = $this->calcolaPresenzeAssenzeDaCPT($oggi);
            
            return [
                // Forza effettiva
                'totale_militari' => $totaleMilitari,
                
                // Presenze oggi dal CPT
                'presenti_oggi' => $presenzaDaCPT['presenti'],
                
                // Assenti oggi dal CPT
                'assenti_oggi' => $presenzaDaCPT['assenti'],
                
                // Percentuale presenti
                'percentuale_presenti' => $totaleMilitari > 0 
                    ? round(($presenzaDaCPT['presenti'] / $totaleMilitari) * 100) 
                    : 0,
                
                // Scadenze critiche (scadute + in scadenza < 7gg)
                'scadenze_critiche' => $this->contaScadenzeCritiche(),
                
                // Attività in corso oggi (Board)
                'in_evento_oggi' => BoardActivity::whereDate('start_date', '<=', $oggi)
                    ->whereDate('end_date', '>=', $oggi)
                    ->count(),
                
                // Pianificazioni mese corrente
                'pianificazioni_mese' => PianificazioneMensile::where('mese', $oggi->month)
                    ->where('anno', $oggi->year)
                    ->exists(),
            ];
        });
    }
    
    /**
     * Calcola presenti e assenti dal CPT (pianificazioni giornaliere)
     * Logica: 
     * - ASSENTI = militari con tipo_servizio_id nel CPT oggi
     * - PRESENTI = tutti gli altri (senza servizio o non nel CPT)
     */
    private function calcolaPresenzeAssenzeDaCPT(Carbon $data)
    {
        $mese = $data->month;
        $anno = $data->year;
        $giorno = $data->day;
        
        // Trova le pianificazioni mensili per questo mese/anno
        $pianificazioniMensili = PianificazioneMensile::where('mese', $mese)
            ->where('anno', $anno)
            ->pluck('id');
        
        if ($pianificazioniMensili->isEmpty()) {
            // Se non ci sono pianificazioni, tutti sono considerati presenti
            return [
                'presenti' => Militare::count(),
                'assenti' => 0,
            ];
        }
        
        // Conta i militari con impegno (tipo_servizio_id != null)
        $assenti = DB::table('pianificazioni_giornaliere')
            ->whereIn('pianificazione_mensile_id', $pianificazioniMensili)
            ->where('giorno', $giorno)
            ->whereNotNull('tipo_servizio_id')
            ->distinct('militare_id')
            ->count('militare_id');
        
        // Tutti gli altri sono presenti
        $totaleMilitari = Militare::count();
        $presenti = $totaleMilitari - $assenti;
        
        return [
            'presenti' => $presenti,
            'assenti' => $assenti,
        ];
    }
    
    /**
     * Criticità e situazioni urgenti
     */
    private function getCriticita()
    {
        $oggi = Carbon::today();
        $tra7giorni = Carbon::today()->addDays(7);
        $tra30giorni = Carbon::today()->addDays(30);
        
        // Scadenze RSPP critiche
        $scadenzeRspp = ScadenzaMilitare::with('militare.grado', 'militare.compagnia')
            ->where(function($q) use ($oggi, $tra30giorni) {
                // Lavoratore 4h
                $q->whereNotNull('lavoratore_4h_data_conseguimento')
                  ->whereRaw('DATE_ADD(lavoratore_4h_data_conseguimento, INTERVAL 4 YEAR) <= ?', [$tra30giorni])
                  ->orWhereNotNull('lavoratore_8h_data_conseguimento')
                  ->whereRaw('DATE_ADD(lavoratore_8h_data_conseguimento, INTERVAL 4 YEAR) <= ?', [$tra30giorni])
                  ->orWhereNotNull('preposto_data_conseguimento')
                  ->whereRaw('DATE_ADD(preposto_data_conseguimento, INTERVAL 2 YEAR) <= ?', [$tra30giorni])
                  ->orWhereNotNull('blsd_data_conseguimento')
                  ->whereRaw('DATE_ADD(blsd_data_conseguimento, INTERVAL 2 YEAR) <= ?', [$tra30giorni]);
            })
            ->limit(10)
            ->get()
            ->map(function($scadenza) use ($oggi) {
                return $this->elaboraScadenzaRspp($scadenza, $oggi);
            })
            ->filter()
            ->sortBy('giorni_alla_scadenza')
            ->take(5);
        
        // Scadenze Idoneità critiche
        $scadenzeIdoneita = ScadenzaMilitare::with('militare.grado', 'militare.compagnia')
            ->where(function($q) use ($oggi, $tra30giorni) {
                $q->whereNotNull('idoneita_mans_data_conseguimento')
                  ->whereRaw('DATE_ADD(idoneita_mans_data_conseguimento, INTERVAL 1 YEAR) <= ?', [$tra30giorni])
                  ->orWhereNotNull('idoneita_smi_data_conseguimento')
                  ->whereRaw('DATE_ADD(idoneita_smi_data_conseguimento, INTERVAL 1 YEAR) <= ?', [$tra30giorni]);
            })
            ->limit(10)
            ->get()
            ->map(function($scadenza) use ($oggi) {
                return $this->elaboraScadenzaIdoneita($scadenza, $oggi);
            })
            ->filter()
            ->sortBy('giorni_alla_scadenza')
            ->take(5);
        
        // Scadenze Poligoni critiche
        $scadenzePoligoni = ScadenzaMilitare::with('militare.grado', 'militare.compagnia')
            ->where(function($q) use ($oggi, $tra30giorni) {
                $q->whereNotNull('tiri_approntamento_data_conseguimento')
                  ->whereRaw('DATE_ADD(tiri_approntamento_data_conseguimento, INTERVAL 6 MONTH) <= ?', [$tra30giorni])
                  ->orWhereNotNull('mantenimento_arma_lunga_data_conseguimento')
                  ->whereRaw('DATE_ADD(mantenimento_arma_lunga_data_conseguimento, INTERVAL 6 MONTH) <= ?', [$tra30giorni]);
            })
            ->limit(10)
            ->get()
            ->map(function($scadenza) use ($oggi) {
                return $this->elaboraScadenzaPoligoni($scadenza, $oggi);
            })
            ->filter()
            ->sortBy('giorni_alla_scadenza')
            ->take(5);
        
        return [
            'rspp' => $scadenzeRspp,
            'idoneita' => $scadenzeIdoneita,
            'poligoni' => $scadenzePoligoni,
            'totale_critiche' => $scadenzeRspp->count() + $scadenzeIdoneita->count() + $scadenzePoligoni->count(),
        ];
    }
    
    /**
     * Presenze ultima settimana
     */
    private function getPresenzeSettimana()
    {
        $oggi = Carbon::today();
        $settimanaFa = Carbon::today()->subDays(6);
        
        $presenze = [];
        for ($i = 0; $i < 7; $i++) {
            $data = $settimanaFa->copy()->addDays($i);
            $presenze[] = [
                'data' => $data->format('Y-m-d'),
                'giorno' => $data->locale('it')->isoFormat('ddd'),
                'presenti' => Presenza::whereDate('data', $data)
                    ->where('stato', 'Presente')
                    ->count(),
                'assenti' => Presenza::whereDate('data', $data)
                    ->where('stato', 'Assente')
                    ->count(),
            ];
        }
        
        return $presenze;
    }
    
    /**
     * Quick Actions con link filtrati
     */
    private function getQuickActions()
    {
        $oggi = Carbon::today();
        
        return [
            [
                'title' => 'Scadenze RSPP Critiche',
                'icon' => 'fas fa-hard-hat',
                'color' => 'danger',
                'url' => route('scadenze.rspp'),
                'count' => $this->contaScadenzeCritiche('rspp'),
            ],
            [
                'title' => 'Idoneità in Scadenza',
                'icon' => 'fas fa-heartbeat',
                'color' => 'warning',
                'url' => route('scadenze.idoneita'),
                'count' => $this->contaScadenzeCritiche('idoneita'),
            ],
            [
                'title' => 'Poligoni da Effettuare',
                'icon' => 'fas fa-bullseye',
                'color' => 'info',
                'url' => route('scadenze.poligoni'),
                'count' => $this->contaScadenzeCritiche('poligoni'),
            ],
            [
                'title' => 'Anagrafica Completa',
                'icon' => 'fas fa-users',
                'color' => 'primary',
                'url' => route('anagrafica.index'),
                'count' => Militare::count(),
            ],
            [
                'title' => 'Pianificazione CPT',
                'icon' => 'fas fa-calendar-alt',
                'color' => 'success',
                'url' => route('pianificazione.index', ['mese' => $oggi->month, 'anno' => $oggi->year]),
                'count' => null,
            ],
            [
                'title' => 'Attività Board',
                'icon' => 'fas fa-clipboard-list',
                'color' => 'purple',
                'url' => route('board.index'),
                'count' => BoardActivity::where('end_date', '>=', $oggi)->count(),
            ],
        ];
    }
    
    /**
     * Situazione compagnia per plotone/polo
     * Le presenze sono calcolate usando il metodo isPresente() basato sul CPT
     */
    private function getSituazioneCompagnia()
    {
        return [
            'plotoni' => Plotone::with('militari')
                ->orderBy('nome')
                ->get()
                ->map(function($plotone) {
                    $totale = $plotone->militari->count();
                    $presenti = $plotone->militari->filter(fn($m) => $m->isPresente())->count();
                    $percentuale = $totale > 0 ? round(($presenti / $totale) * 100) : 0;
                    return [
                        'nome' => $plotone->nome,
                        'totale' => $totale,
                        'presenti' => $presenti,
                        'percentuale' => $percentuale,
                    ];
                }),
            
            'poli' => Polo::with('militari')
                ->orderBy('nome')
                ->get()
                ->map(function($polo) {
                    $totale = $polo->militari->count();
                    $presenti = $polo->militari->filter(fn($m) => $m->isPresente())->count();
                    $percentuale = $totale > 0 ? round(($presenti / $totale) * 100) : 0;
                    return [
                        'nome' => $polo->nome,
                        'totale' => $totale,
                        'presenti' => $presenti,
                        'percentuale' => $percentuale,
                    ];
                }),
        ];
    }
    
    /**
     * Compleanni di oggi
     */
    private function getCompleanniOggi()
    {
        $oggi = Carbon::today();
        
        return Militare::with(['grado', 'compagnia'])
            ->whereMonth('data_nascita', $oggi->month)
            ->whereDay('data_nascita', $oggi->day)
            ->orderBy('cognome')
            ->orderBy('nome')
            ->get()
            ->map(function($militare) use ($oggi) {
                $dataNascita = Carbon::parse($militare->data_nascita);
                $eta = $dataNascita->diffInYears($oggi);
                
                return [
                    'id' => $militare->id,
                    'nome_completo' => ($militare->grado->sigla ?? '') . ' ' . $militare->cognome . ' ' . $militare->nome,
                    'compagnia' => $militare->compagnia->nome ?? '',
                    'eta' => $eta,
                ];
            });
    }
    
    /**
     * Attività dal Board in corso oggi
     */
    private function getAttivitaBoardOggi()
    {
        $oggi = Carbon::today();
        
        return \App\Models\BoardActivity::whereDate('start_date', '<=', $oggi)
            ->whereDate('end_date', '>=', $oggi)
            ->with(['militari.grado', 'column'])
            ->orderBy('start_date')
            ->get()
            ->map(function($activity) use ($oggi) {
                $militari = $activity->militari->map(function($m) {
                    return ($m->grado->sigla ?? '') . ' ' . $m->cognome . ' ' . $m->nome;
                })->join(', ');
                
                // La categoria è il nome della colonna della board
                // (Servizi Isolati, Esercitazioni, Stand-by, Operazioni, Corsi, Cattedre)
                $categoria = $activity->column->name ?? 'Attività';
                
                return [
                    'id' => $activity->id,
                    'titolo' => $activity->title,
                    'descrizione' => $activity->description,
                    'priorita' => $activity->priority,
                    'categoria' => $categoria,
                    'stato' => $activity->column->name ?? 'N/A',
                    'data_inizio' => $activity->start_date,
                    'data_scadenza' => $activity->end_date,
                    'militari' => $militari ?: 'Nessuno assegnato',
                    'in_corso' => true,
                ];
            });
    }
    
    /**
     * Calcola scadenze RSPP reali dal database
     */
    private function getScadenzeRspp()
    {
        $oggi = Carbon::today();
        $tra30giorni = $oggi->copy()->addDays(30);
        
        $scadenze = ScadenzaMilitare::with('militare')->get();
        
        $validi = 0;
        $inScadenza = 0;
        $scaduti = 0;
        
        foreach ($scadenze as $scadenza) {
            // Lavoratore 4h
            if ($scadenza->lavoratore_4h_data_conseguimento) {
                $dataScadenza = Carbon::parse($scadenza->lavoratore_4h_data_conseguimento)->addYears(4);
                if ($dataScadenza < $oggi) $scaduti++;
                elseif ($dataScadenza <= $tra30giorni) $inScadenza++;
                else $validi++;
            }
            
            // Lavoratore 8h
            if ($scadenza->lavoratore_8h_data_conseguimento) {
                $dataScadenza = Carbon::parse($scadenza->lavoratore_8h_data_conseguimento)->addYears(4);
                if ($dataScadenza < $oggi) $scaduti++;
                elseif ($dataScadenza <= $tra30giorni) $inScadenza++;
                else $validi++;
            }
            
            // Preposto
            if ($scadenza->preposto_data_conseguimento) {
                $dataScadenza = Carbon::parse($scadenza->preposto_data_conseguimento)->addYears(2);
                if ($dataScadenza < $oggi) $scaduti++;
                elseif ($dataScadenza <= $tra30giorni) $inScadenza++;
                else $validi++;
            }
            
            // BLSD
            if ($scadenza->blsd_data_conseguimento) {
                $dataScadenza = Carbon::parse($scadenza->blsd_data_conseguimento)->addYears(2);
                if ($dataScadenza < $oggi) $scaduti++;
                elseif ($dataScadenza <= $tra30giorni) $inScadenza++;
                else $validi++;
            }
        }
        
        return [
            'validi' => $validi,
            'in_scadenza' => $inScadenza,
            'scaduti' => $scaduti,
        ];
    }
    
    /**
     * Calcola scadenze Idoneità reali dal database
     */
    private function getScadenzeIdoneita()
    {
        $oggi = Carbon::today();
        $tra30giorni = $oggi->copy()->addDays(30);
        
        $scadenze = ScadenzaMilitare::with('militare')->get();
        
        $validi = 0;
        $inScadenza = 0;
        $scaduti = 0;
        
        foreach ($scadenze as $scadenza) {
            // Idoneità Mansione
            if ($scadenza->idoneita_mans_data_conseguimento) {
                $dataScadenza = Carbon::parse($scadenza->idoneita_mans_data_conseguimento)->addYear();
                if ($dataScadenza < $oggi) $scaduti++;
                elseif ($dataScadenza <= $tra30giorni) $inScadenza++;
                else $validi++;
            }
            
            // Idoneità SMI
            if ($scadenza->idoneita_smi_data_conseguimento) {
                $dataScadenza = Carbon::parse($scadenza->idoneita_smi_data_conseguimento)->addYear();
                if ($dataScadenza < $oggi) $scaduti++;
                elseif ($dataScadenza <= $tra30giorni) $inScadenza++;
                else $validi++;
            }
            
            // ECG
            if ($scadenza->ecg_data_conseguimento) {
                $dataScadenza = Carbon::parse($scadenza->ecg_data_conseguimento)->addYear();
                if ($dataScadenza < $oggi) $scaduti++;
                elseif ($dataScadenza <= $tra30giorni) $inScadenza++;
                else $validi++;
            }
        }
        
        return [
            'validi' => $validi,
            'in_scadenza' => $inScadenza,
            'scaduti' => $scaduti,
        ];
    }
    
    /**
     * Calcola scadenze Poligoni reali dal database
     */
    private function getScadenzePoligoni()
    {
        $oggi = Carbon::today();
        $tra30giorni = $oggi->copy()->addDays(30);
        
        $scadenze = ScadenzaMilitare::with('militare')->get();
        
        $validi = 0;
        $inScadenza = 0;
        $scaduti = 0;
        
        foreach ($scadenze as $scadenza) {
            // Tiri Approntamento
            if ($scadenza->tiri_approntamento_data_conseguimento) {
                $dataScadenza = Carbon::parse($scadenza->tiri_approntamento_data_conseguimento)->addMonths(6);
                if ($dataScadenza < $oggi) $scaduti++;
                elseif ($dataScadenza <= $tra30giorni) $inScadenza++;
                else $validi++;
            }
            
            // Mantenimento Arma Lunga
            if ($scadenza->mantenimento_arma_lunga_data_conseguimento) {
                $dataScadenza = Carbon::parse($scadenza->mantenimento_arma_lunga_data_conseguimento)->addMonths(6);
                if ($dataScadenza < $oggi) $scaduti++;
                elseif ($dataScadenza <= $tra30giorni) $inScadenza++;
                else $validi++;
            }
            
            // Mantenimento Arma Corta
            if ($scadenza->mantenimento_arma_corta_data_conseguimento) {
                $dataScadenza = Carbon::parse($scadenza->mantenimento_arma_corta_data_conseguimento)->addMonths(6);
                if ($dataScadenza < $oggi) $scaduti++;
                elseif ($dataScadenza <= $tra30giorni) $inScadenza++;
                else $validi++;
            }
        }
        
        return [
            'validi' => $validi,
            'in_scadenza' => $inScadenza,
            'scaduti' => $scaduti,
        ];
    }
    
    // === METODI HELPER ===
    
    private function calcolaPercentualePresenti()
    {
        $militari = Militare::all();
        $presenti = $militari->filter(fn($m) => $m->isPresente())->count();
        $totale = $militari->count();
        
        return $totale > 0 ? round(($presenti / $totale) * 100) : 0;
    }
    
    private function contaScadenzeCritiche($tipo = null)
    {
        $oggi = Carbon::today();
        $tra30giorni = Carbon::today()->addDays(30);
        
        $query = ScadenzaMilitare::query();
        
        if ($tipo === 'rspp') {
            $query->where(function($q) use ($oggi, $tra30giorni) {
                $q->whereRaw('DATE_ADD(lavoratore_4h_data_conseguimento, INTERVAL 4 YEAR) <= ?', [$tra30giorni])
                  ->orWhereRaw('DATE_ADD(lavoratore_8h_data_conseguimento, INTERVAL 4 YEAR) <= ?', [$tra30giorni])
                  ->orWhereRaw('DATE_ADD(preposto_data_conseguimento, INTERVAL 2 YEAR) <= ?', [$tra30giorni])
                  ->orWhereRaw('DATE_ADD(blsd_data_conseguimento, INTERVAL 2 YEAR) <= ?', [$tra30giorni]);
            });
        } elseif ($tipo === 'idoneita') {
            $query->where(function($q) use ($oggi, $tra30giorni) {
                $q->whereRaw('DATE_ADD(idoneita_mans_data_conseguimento, INTERVAL 1 YEAR) <= ?', [$tra30giorni])
                  ->orWhereRaw('DATE_ADD(idoneita_smi_data_conseguimento, INTERVAL 1 YEAR) <= ?', [$tra30giorni]);
            });
        } elseif ($tipo === 'poligoni') {
            $query->where(function($q) use ($oggi, $tra30giorni) {
                $q->whereRaw('DATE_ADD(tiri_approntamento_data_conseguimento, INTERVAL 6 MONTH) <= ?', [$tra30giorni])
                  ->orWhereRaw('DATE_ADD(mantenimento_arma_lunga_data_conseguimento, INTERVAL 6 MONTH) <= ?', [$tra30giorni]);
            });
        }
        
        return $query->count();
    }
    
    private function elaboraScadenzaRspp($scadenza, $oggi)
    {
        $militare = $scadenza->militare;
        $criticita = [];
        
        // Lavoratore 4h
        if ($scadenza->lavoratore_4h_data_conseguimento) {
            $dataScadenza = Carbon::parse($scadenza->lavoratore_4h_data_conseguimento)->addYears(4);
            $giorni = $oggi->diffInDays($dataScadenza, false);
            if ($giorni <= 30) {
                $criticita[] = [
                    'tipo' => 'Lavoratore 4h',
                    'giorni_scadenza' => $giorni,
                    'data_scadenza' => $dataScadenza->format('d/m/Y'),
                    'stato' => $giorni < 0 ? 'scaduto' : ($giorni <= 7 ? 'critico' : 'attenzione'),
                ];
            }
        }
        
        // Lavoratore 8h
        if ($scadenza->lavoratore_8h_data_conseguimento) {
            $dataScadenza = Carbon::parse($scadenza->lavoratore_8h_data_conseguimento)->addYears(4);
            $giorni = $oggi->diffInDays($dataScadenza, false);
            if ($giorni <= 30) {
                $criticita[] = [
                    'tipo' => 'Lavoratore 8h',
                    'giorni_scadenza' => $giorni,
                    'data_scadenza' => $dataScadenza->format('d/m/Y'),
                    'stato' => $giorni < 0 ? 'scaduto' : ($giorni <= 7 ? 'critico' : 'attenzione'),
                ];
            }
        }
        
        // Preposto
        if ($scadenza->preposto_data_conseguimento) {
            $dataScadenza = Carbon::parse($scadenza->preposto_data_conseguimento)->addYears(2);
            $giorni = $oggi->diffInDays($dataScadenza, false);
            if ($giorni <= 30) {
                $criticita[] = [
                    'tipo' => 'Preposto',
                    'giorni_scadenza' => $giorni,
                    'data_scadenza' => $dataScadenza->format('d/m/Y'),
                    'stato' => $giorni < 0 ? 'scaduto' : ($giorni <= 7 ? 'critico' : 'attenzione'),
                ];
            }
        }
        
        // BLSD
        if ($scadenza->blsd_data_conseguimento) {
            $dataScadenza = Carbon::parse($scadenza->blsd_data_conseguimento)->addYears(2);
            $giorni = $oggi->diffInDays($dataScadenza, false);
            if ($giorni <= 30) {
                $criticita[] = [
                    'tipo' => 'BLSD',
                    'giorni_scadenza' => $giorni,
                    'data_scadenza' => $dataScadenza->format('d/m/Y'),
                    'stato' => $giorni < 0 ? 'scaduto' : ($giorni <= 7 ? 'critico' : 'attenzione'),
                ];
            }
        }
        
        if (empty($criticita)) return null;
        
        // Prendi la più critica
        usort($criticita, function($a, $b) {
            return $a['giorni_scadenza'] <=> $b['giorni_scadenza'];
        });
        
        $piuCritica = $criticita[0];
        
        return [
            'militare_id' => $militare->id,
            'militare_nome' => $militare->grado->sigla . ' ' . $militare->cognome . ' ' . $militare->nome,
            'compagnia' => $militare->compagnia ? $militare->compagnia->nome : '',
            'tipo_scadenza' => $piuCritica['tipo'],
            'data_scadenza' => $piuCritica['data_scadenza'],
            'giorni_alla_scadenza' => $piuCritica['giorni_scadenza'],
            'stato' => $piuCritica['stato'],
            'url' => route('scadenze.rspp'),
        ];
    }
    
    private function elaboraScadenzaIdoneita($scadenza, $oggi)
    {
        $militare = $scadenza->militare;
        $criticita = [];
        
        // Idoneità Mansione
        if ($scadenza->idoneita_mans_data_conseguimento) {
            $dataScadenza = Carbon::parse($scadenza->idoneita_mans_data_conseguimento)->addYear();
            $giorni = $oggi->diffInDays($dataScadenza, false);
            if ($giorni <= 30) {
                $criticita[] = [
                    'tipo' => 'Idoneità Mansione',
                    'giorni_scadenza' => $giorni,
                    'data_scadenza' => $dataScadenza->format('d/m/Y'),
                    'stato' => $giorni < 0 ? 'scaduto' : ($giorni <= 7 ? 'critico' : 'attenzione'),
                ];
            }
        }
        
        // Idoneità SMI
        if ($scadenza->idoneita_smi_data_conseguimento) {
            $dataScadenza = Carbon::parse($scadenza->idoneita_smi_data_conseguimento)->addYear();
            $giorni = $oggi->diffInDays($dataScadenza, false);
            if ($giorni <= 30) {
                $criticita[] = [
                    'tipo' => 'Idoneità SMI',
                    'giorni_scadenza' => $giorni,
                    'data_scadenza' => $dataScadenza->format('d/m/Y'),
                    'stato' => $giorni < 0 ? 'scaduto' : ($giorni <= 7 ? 'critico' : 'attenzione'),
                ];
            }
        }
        
        if (empty($criticita)) return null;
        
        usort($criticita, function($a, $b) {
            return $a['giorni_scadenza'] <=> $b['giorni_scadenza'];
        });
        
        $piuCritica = $criticita[0];
        
        return [
            'militare_id' => $militare->id,
            'militare_nome' => $militare->grado->sigla . ' ' . $militare->cognome . ' ' . $militare->nome,
            'compagnia' => $militare->compagnia ? $militare->compagnia->nome : '',
            'tipo_scadenza' => $piuCritica['tipo'],
            'data_scadenza' => $piuCritica['data_scadenza'],
            'giorni_alla_scadenza' => $piuCritica['giorni_scadenza'],
            'stato' => $piuCritica['stato'],
            'url' => route('scadenze.idoneita'),
        ];
    }
    
    private function elaboraScadenzaPoligoni($scadenza, $oggi)
    {
        $militare = $scadenza->militare;
        $criticita = [];
        
        // Tiri Approntamento
        if ($scadenza->tiri_approntamento_data_conseguimento) {
            $dataScadenza = Carbon::parse($scadenza->tiri_approntamento_data_conseguimento)->addMonths(6);
            $giorni = $oggi->diffInDays($dataScadenza, false);
            if ($giorni <= 30) {
                $criticita[] = [
                    'tipo' => 'Tiri Approntamento',
                    'giorni_scadenza' => $giorni,
                    'data_scadenza' => $dataScadenza->format('d/m/Y'),
                    'stato' => $giorni < 0 ? 'scaduto' : ($giorni <= 7 ? 'critico' : 'attenzione'),
                ];
            }
        }
        
        // Mantenimento Arma Lunga
        if ($scadenza->mantenimento_arma_lunga_data_conseguimento) {
            $dataScadenza = Carbon::parse($scadenza->mantenimento_arma_lunga_data_conseguimento)->addMonths(6);
            $giorni = $oggi->diffInDays($dataScadenza, false);
            if ($giorni <= 30) {
                $criticita[] = [
                    'tipo' => 'Mantenimento A.L.',
                    'giorni_scadenza' => $giorni,
                    'data_scadenza' => $dataScadenza->format('d/m/Y'),
                    'stato' => $giorni < 0 ? 'scaduto' : ($giorni <= 7 ? 'critico' : 'attenzione'),
                ];
            }
        }
        
        // Mantenimento Arma Corta
        if ($scadenza->mantenimento_arma_corta_data_conseguimento) {
            $dataScadenza = Carbon::parse($scadenza->mantenimento_arma_corta_data_conseguimento)->addMonths(6);
            $giorni = $oggi->diffInDays($dataScadenza, false);
            if ($giorni <= 30) {
                $criticita[] = [
                    'tipo' => 'Mantenimento A.C.',
                    'giorni_scadenza' => $giorni,
                    'data_scadenza' => $dataScadenza->format('d/m/Y'),
                    'stato' => $giorni < 0 ? 'scaduto' : ($giorni <= 7 ? 'critico' : 'attenzione'),
                ];
            }
        }
        
        if (empty($criticita)) return null;
        
        usort($criticita, function($a, $b) {
            return $a['giorni_scadenza'] <=> $b['giorni_scadenza'];
        });
        
        $piuCritica = $criticita[0];
        
        return [
            'militare_id' => $militare->id,
            'militare_nome' => $militare->grado->sigla . ' ' . $militare->cognome . ' ' . $militare->nome,
            'compagnia' => $militare->compagnia ? $militare->compagnia->nome : '',
            'tipo_scadenza' => $piuCritica['tipo'],
            'data_scadenza' => $piuCritica['data_scadenza'],
            'giorni_alla_scadenza' => $piuCritica['giorni_scadenza'],
            'stato' => $piuCritica['stato'],
            'url' => route('scadenze.poligoni'),
        ];
    }
    
    /**
     * Invalida cache dashboard
     */
    public function refreshCache()
    {
        Cache::tags(['dashboard'])->flush();
        
        return redirect()->route('dashboard')
            ->with('success', 'Cache dashboard aggiornata!');
    }
}

