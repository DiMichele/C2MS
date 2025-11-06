<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Militare;
use App\Models\Presenza;
use App\Models\ScadenzaMilitare;
use App\Models\Plotone;
use App\Models\Polo;
use App\Models\Evento;
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
        
        // === CRITICITÀ E SCADENZE URGENTI ===
        $criticita = $this->getCriticita();
        
        // === PRESENZE ULTIMA SETTIMANA ===
        $presenzeSettimana = $this->getPresenzeSettimana();
        
        // === QUICK ACTIONS (scorciatoie con filtri) ===
        $quickActions = $this->getQuickActions();
        
        // === SITUAZIONE COMPAGNIA ===
        $situazioneCompagnia = $this->getSituazioneCompagnia();
        
        // === PROSSIMI EVENTI ===
        $prossimiEventi = $this->getProssimiEventi();
        
        return view('dashboard', compact(
            'kpis',
            'criticita',
            'presenzeSettimana',
            'quickActions',
            'situazioneCompagnia',
            'prossimiEventi'
        ));
    }
    
    /**
     * KPI principali
     */
    private function getKPIs()
    {
        return Cache::remember('dashboard.kpis', self::CACHE_DURATION, function () {
            $oggi = Carbon::today();
            
            return [
                // Forza effettiva
                'totale_militari' => Militare::count(),
                
                // Presenze oggi
                'presenti_oggi' => Militare::whereHas('presenzaOggi', function($q) {
                    $q->where('stato', 'Presente');
                })->count(),
                
                // Assenti oggi
                'assenti_oggi' => Militare::whereHas('presenzaOggi', function($q) {
                    $q->where('stato', 'Assente');
                })->count(),
                
                // Percentuale presenti
                'percentuale_presenti' => $this->calcolaPercentualePresenti(),
                
                // Scadenze critiche (scadute + in scadenza < 7gg)
                'scadenze_critiche' => $this->contaScadenzeCritiche(),
                
                // Militari in evento oggi
                'in_evento_oggi' => Evento::whereDate('data_inizio', '<=', $oggi)
                    ->whereDate('data_fine', '>=', $oggi)
                    ->withCount('militari')
                    ->get()
                    ->sum('militari_count'),
                
                // Pianificazioni mese corrente
                'pianificazioni_mese' => PianificazioneMensile::where('mese', $oggi->month)
                    ->where('anno', $oggi->year)
                    ->exists(),
            ];
        });
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
                'title' => 'Eventi Attivi',
                'icon' => 'fas fa-calendar-check',
                'color' => 'purple',
                'url' => route('eventi.index'),
                'count' => Evento::where('data_fine', '>=', $oggi)->count(),
            ],
        ];
    }
    
    /**
     * Situazione compagnia per plotone/polo
     */
    private function getSituazioneCompagnia()
    {
        return [
            'plotoni' => Plotone::withCount([
                'militari',
                'militari as presenti_count' => function($q) {
                    $q->whereHas('presenzaOggi', function($q2) {
                        $q2->where('stato', 'Presente');
                    });
                }
            ])
            ->orderBy('nome')
            ->get()
            ->map(function($plotone) {
                $percentuale = $plotone->militari_count > 0 
                    ? round(($plotone->presenti_count / $plotone->militari_count) * 100) 
                    : 0;
                return [
                    'nome' => $plotone->nome,
                    'totale' => $plotone->militari_count,
                    'presenti' => $plotone->presenti_count,
                    'percentuale' => $percentuale,
                ];
            }),
            
            'poli' => Polo::withCount([
                'militari',
                'militari as presenti_count' => function($q) {
                    $q->whereHas('presenzaOggi', function($q2) {
                        $q2->where('stato', 'Presente');
                    });
                }
            ])
            ->orderBy('nome')
            ->get()
            ->map(function($polo) {
                $percentuale = $polo->militari_count > 0 
                    ? round(($polo->presenti_count / $polo->militari_count) * 100) 
                    : 0;
                return [
                    'nome' => $polo->nome,
                    'totale' => $polo->militari_count,
                    'presenti' => $polo->presenti_count,
                    'percentuale' => $percentuale,
                ];
            }),
        ];
    }
    
    /**
     * Prossimi eventi
     */
    private function getProssimiEventi()
    {
        $oggi = Carbon::today();
        
        return Evento::where('data_fine', '>=', $oggi)
            ->with('militari')
            ->orderBy('data_inizio')
            ->limit(5)
            ->get()
            ->map(function($evento) use ($oggi) {
                $inCorso = $evento->data_inizio <= $oggi && $evento->data_fine >= $oggi;
                return [
                    'id' => $evento->id,
                    'titolo' => $evento->titolo,
                    'tipo' => $evento->tipo,
                    'data_inizio' => $evento->data_inizio,
                    'data_fine' => $evento->data_fine,
                    'militari_count' => $evento->militari->count(),
                    'in_corso' => $inCorso,
                    'giorni_inizio' => $inCorso ? 0 : $oggi->diffInDays($evento->data_inizio, false),
                ];
            });
    }
    
    // === METODI HELPER ===
    
    private function calcolaPercentualePresenti()
    {
        $presenti = Militare::whereHas('presenzaOggi', function($q) {
            $q->where('stato', 'Presente');
        })->count();
        
        $totale = Militare::count();
        
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

