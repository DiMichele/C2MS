<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\PianificazioneGiornaliera;
use App\Models\PianificazioneMensile;
use App\Models\Polo;
use App\Models\Plotone;
use App\Models\Compagnia;
use App\Models\AssegnazioneTurno;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller per la gestione della disponibilità del personale
 * 
 * Funzionalità:
 * - Vista impegni per singolo militare (lista + calendario)
 * - Panoramica disponibilità per polo/giornata
 */
class DisponibilitaController extends Controller
{
    /**
     * Mostra la panoramica della disponibilità per polo e giornata
     */
    public function index(Request $request)
    {
        $anno = $request->input('anno', Carbon::now()->year);
        $mese = $request->input('mese', Carbon::now()->month);
        $poloId = $request->input('polo_id');
        $compagniaId = $request->input('compagnia_id');
        $plotoneId = $request->input('plotone_id');
        
        // Ottieni tutti i poli (uffici)
        $poli = Polo::orderBy('nome')->get();
        
        // Ottieni tutte le compagnie
        $compagnie = Compagnia::orderBy('nome')->get();
        
        // Ottieni tutti i plotoni
        $plotoni = Plotone::with('compagnia')->orderBy('nome')->get();
        
        // Calcola i giorni del mese
        $giorniNelMese = Carbon::create($anno, $mese)->daysInMonth;
        $giorniMese = [];
        
        // Festività
        $festivitaPerAnno = $this->getFestivita();
        $festivitaFisse = $festivitaPerAnno[$anno] ?? [];
        
        for ($giorno = 1; $giorno <= $giorniNelMese; $giorno++) {
            $data = Carbon::createFromDate($anno, $mese, $giorno);
            $giorniItaliani = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];
            
            $dataFormattata = $data->format('m-d');
            $isWeekend = $data->isWeekend();
            $isHoliday = isset($festivitaFisse[$dataFormattata]);
            
            $giorniMese[] = [
                'giorno' => $giorno,
                'data' => $data,
                'nome_giorno' => $giorniItaliani[$data->dayOfWeek],
                'is_weekend' => $isWeekend,
                'is_holiday' => $isHoliday,
                'is_today' => $data->isToday(),
            ];
        }
        
        // Ottieni la pianificazione mensile
        $pianificazioneMensile = PianificazioneMensile::where('mese', $mese)
            ->where('anno', $anno)
            ->first();
        
        // Ottieni TUTTI i militari (senza filtri server-side, il filtraggio avviene lato client)
        $militari = Militare::with(['polo', 'grado', 'compagnia', 'plotone'])
            ->orderBy('polo_id')
            ->orderByGradoENome()
            ->get();
        
        // Calcola la disponibilità per ogni polo e giorno
        $disponibilitaPerPolo = [];
        
        foreach ($poli as $polo) {
            $militariPolo = $militari->where('polo_id', $polo->id);
            $totaleMilitariPolo = $militariPolo->count();
            
            $disponibilitaPerPolo[$polo->id] = [
                'polo' => $polo,
                'totale_militari' => $totaleMilitariPolo,
                'giorni' => []
            ];
            
            foreach ($giorniMese as $giorno) {
                // Conta quanti militari sono impegnati in questo giorno
                $impegnati = 0;
                
                if ($pianificazioneMensile) {
                    $impegnati = PianificazioneGiornaliera::where('pianificazione_mensile_id', $pianificazioneMensile->id)
                        ->where('giorno', $giorno['giorno'])
                        ->whereIn('militare_id', $militariPolo->pluck('id'))
                        ->whereNotNull('tipo_servizio_id')
                        ->count();
                }
                
                // Conta anche le assegnazioni turno
                $dataGiorno = Carbon::create($anno, $mese, $giorno['giorno']);
                $impegnatiTurno = AssegnazioneTurno::whereDate('data_servizio', $dataGiorno)
                    ->whereIn('militare_id', $militariPolo->pluck('id'))
                    ->count();
                
                $impegnatiTotale = max($impegnati, $impegnatiTurno);
                $liberi = $totaleMilitariPolo - $impegnatiTotale;
                
                $disponibilitaPerPolo[$polo->id]['giorni'][$giorno['giorno']] = [
                    'liberi' => max(0, $liberi),
                    'impegnati' => $impegnatiTotale,
                    'totale' => $totaleMilitariPolo,
                    'percentuale_liberi' => $totaleMilitariPolo > 0 ? round(($liberi / $totaleMilitariPolo) * 100) : 0
                ];
            }
        }
        
        $nomiMesi = [
            1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
            5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
            9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
        ];
        
        // Prepara dati militari per il filtraggio lato client
        $militariData = [];
        
        // Ottieni tutti gli impegni CPT del mese
        $impegniCpt = [];
        if ($pianificazioneMensile) {
            $impegniCptQuery = PianificazioneGiornaliera::where('pianificazione_mensile_id', $pianificazioneMensile->id)
                ->whereNotNull('tipo_servizio_id')
                ->get();
            
            foreach ($impegniCptQuery as $impegno) {
                $key = $impegno->militare_id . '_' . $impegno->giorno;
                $impegniCpt[$key] = true;
            }
        }
        
        // Ottieni tutti gli impegni turno del mese
        $impegniTurno = [];
        $dataInizio = Carbon::create($anno, $mese, 1)->startOfDay();
        $dataFine = Carbon::create($anno, $mese, $giorniNelMese)->endOfDay();
        
        $turniQuery = AssegnazioneTurno::whereBetween('data_servizio', [$dataInizio, $dataFine])->get();
        foreach ($turniQuery as $turno) {
            $giorno = Carbon::parse($turno->data_servizio)->day;
            $key = $turno->militare_id . '_' . $giorno;
            $impegniTurno[$key] = true;
        }
        
        foreach ($militari as $militare) {
            $impegniGiorni = [];
            for ($g = 1; $g <= $giorniNelMese; $g++) {
                $keyCpt = $militare->id . '_' . $g;
                $keyTurno = $militare->id . '_' . $g;
                $impegniGiorni[$g] = isset($impegniCpt[$keyCpt]) || isset($impegniTurno[$keyTurno]);
            }
            
            $militariData[] = [
                'id' => $militare->id,
                'polo_id' => $militare->polo_id,
                'compagnia_id' => $militare->compagnia_id,
                'plotone_id' => $militare->plotone_id,
                'impegni' => $impegniGiorni
            ];
        }
        
        return view('disponibilita.index', compact(
            'anno',
            'mese',
            'poli',
            'poloId',
            'compagnie',
            'compagniaId',
            'plotoni',
            'plotoneId',
            'giorniMese',
            'disponibilitaPerPolo',
            'nomiMesi',
            'militariData'
        ));
    }
    
    /**
     * Mostra gli impegni di un singolo militare (calendario + lista)
     */
    public function militare(Request $request, Militare $militare)
    {
        $anno = $request->input('anno', Carbon::now()->year);
        $mese = $request->input('mese', Carbon::now()->month);
        
        // Carica le relazioni del militare
        $militare->load(['grado', 'polo', 'compagnia', 'plotone']);
        
        // Calcola i giorni del mese
        $giorniNelMese = Carbon::create($anno, $mese)->daysInMonth;
        
        // Festività
        $festivitaPerAnno = $this->getFestivita();
        $festivitaFisse = $festivitaPerAnno[$anno] ?? [];
        
        // Ottieni la pianificazione mensile
        $pianificazioneMensile = PianificazioneMensile::where('mese', $mese)
            ->where('anno', $anno)
            ->first();
        
        // Ottieni le pianificazioni del militare (raggruppa per giorno per gestire impegni multipli)
        $pianificazioni = [];
        if ($pianificazioneMensile) {
            $pianificazioniQuery = PianificazioneGiornaliera::where('militare_id', $militare->id)
                ->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                ->with(['tipoServizio', 'tipoServizio.codiceGerarchia'])
                ->get();
            
            // Raggruppa per giorno (può avere più impegni nello stesso giorno)
            foreach ($pianificazioniQuery as $p) {
                if (!isset($pianificazioni[$p->giorno])) {
                    $pianificazioni[$p->giorno] = [];
                }
                $pianificazioni[$p->giorno][] = $p;
            }
        }
        
        // Ottieni le assegnazioni turno del militare per questo mese
        $dataInizio = Carbon::create($anno, $mese, 1)->startOfDay();
        $dataFine = Carbon::create($anno, $mese, $giorniNelMese)->endOfDay();
        
        $assegnazioniTurnoQuery = AssegnazioneTurno::where('militare_id', $militare->id)
            ->whereBetween('data_servizio', [$dataInizio, $dataFine])
            ->with('servizioTurno')
            ->get();
        
        // Raggruppa per giorno
        $assegnazioniTurno = [];
        foreach ($assegnazioniTurnoQuery as $a) {
            $day = Carbon::parse($a->data_servizio)->day;
            if (!isset($assegnazioniTurno[$day])) {
                $assegnazioniTurno[$day] = [];
            }
            $assegnazioniTurno[$day][] = $a;
        }
        
        // Mappa colori per tipologie di attività
        $coloriAttivita = [
            'T.O.' => '#dc3545',      // Rosso - Teatro Operativo
            'EXE' => '#ffc107',        // Giallo - Esercitazioni
            'EX' => '#ffc107',         // Giallo - Esercitazioni
            'CAT' => '#28a745',        // Verde - Cattedre
            'CATT' => '#28a745',       // Verde - Cattedre  
            'CRS' => '#007bff',        // Blu - Corsi
            'CORSO' => '#007bff',      // Blu - Corsi
            'SI' => '#6c757d',         // Grigio - Servizi Isolati
            'STB' => '#fd7e14',        // Arancione - Stand-by
            'OP' => '#dc3545',         // Rosso - Operazioni
            'LIC' => '#17a2b8',        // Ciano - Licenza
            'MAL' => '#e83e8c',        // Rosa - Malattia
            'RIP' => '#6f42c1',        // Viola - Riposo
        ];
        
        // Prepara i dati del calendario
        $calendarioMese = [];
        $listaImpegni = [];
        
        for ($giorno = 1; $giorno <= $giorniNelMese; $giorno++) {
            $data = Carbon::createFromDate($anno, $mese, $giorno);
            $giorniItaliani = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
            
            $dataFormattata = $data->format('m-d');
            $isWeekend = $data->isWeekend();
            $isHoliday = isset($festivitaFisse[$dataFormattata]);
            
            // Array per impegni multipli
            $impegniGiorno = [];
            
            // Controlla CPT (può avere più impegni)
            if (isset($pianificazioni[$giorno])) {
                foreach ($pianificazioni[$giorno] as $p) {
                    if ($p->tipoServizio) {
                        $codice = $p->tipoServizio->codice;
                        // Cerca colore dalla mappa, poi dalla gerarchia, poi default
                        $colore = $coloriAttivita[strtoupper($codice)] 
                            ?? $p->tipoServizio->codiceGerarchia->colore_badge 
                            ?? '#6c757d';
                        
                        $impegniGiorno[] = [
                            'codice' => $codice,
                            'descrizione' => $p->tipoServizio->nome,
                            'colore' => $colore,
                            'fonte' => 'CPT'
                        ];
                    }
                }
            }
            
            // Controlla Turni (può avere più turni)
            if (isset($assegnazioniTurno[$giorno])) {
                foreach ($assegnazioniTurno[$giorno] as $a) {
                    if ($a->servizioTurno) {
                        $codice = $a->servizioTurno->codice;
                        $colore = $coloriAttivita[strtoupper($codice)] ?? '#0d6efd';
                        
                        $impegniGiorno[] = [
                            'codice' => $codice,
                            'descrizione' => $a->servizioTurno->nome,
                            'colore' => $colore,
                            'fonte' => 'Turno'
                        ];
                    }
                }
            }
            
            $calendarioMese[$giorno] = [
                'giorno' => $giorno,
                'data' => $data,
                'data_formattata' => $data->format('d/m/Y'),
                'nome_giorno' => $giorniItaliani[$data->dayOfWeek],
                'nome_giorno_breve' => substr($giorniItaliani[$data->dayOfWeek], 0, 3),
                'is_weekend' => $isWeekend,
                'is_holiday' => $isHoliday,
                'is_today' => $data->isToday(),
                'impegni' => $impegniGiorno,
                'ha_impegno' => count($impegniGiorno) > 0,
                // Per retrocompatibilità, mantieni anche i campi singoli (primo impegno)
                'codice' => $impegniGiorno[0]['codice'] ?? null,
                'descrizione' => $impegniGiorno[0]['descrizione'] ?? null,
                'colore' => $impegniGiorno[0]['colore'] ?? null,
                'fonte' => $impegniGiorno[0]['fonte'] ?? null,
            ];
            
            // Aggiungi alla lista ogni impegno
            foreach ($impegniGiorno as $impegno) {
                $listaImpegni[] = array_merge([
                    'giorno' => $giorno,
                    'data' => $data,
                    'data_formattata' => $data->format('d/m/Y'),
                    'nome_giorno' => $giorniItaliani[$data->dayOfWeek],
                ], $impegno);
            }
        }
        
        // Statistiche mensili
        $statistiche = [
            'totale_impegni' => count($listaImpegni),
            'giorni_liberi' => $giorniNelMese - count($listaImpegni),
            'percentuale_impegno' => round((count($listaImpegni) / $giorniNelMese) * 100)
        ];
        
        $nomiMesi = [
            1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
            5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
            9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
        ];
        
        return view('disponibilita.militare', compact(
            'militare',
            'anno',
            'mese',
            'calendarioMese',
            'listaImpegni',
            'statistiche',
            'nomiMesi'
        ));
    }
    
    /**
     * API: Ottieni militari liberi e impegnati per un giorno specifico
     */
    public function getMilitariLiberi(Request $request)
    {
        $data = Carbon::parse($request->input('data', now()->format('Y-m-d')));
        $poloId = $request->input('polo_id');
        
        $anno = $data->year;
        $mese = $data->month;
        $giorno = $data->day;
        
        // Ottieni la pianificazione mensile
        $pianificazioneMensile = PianificazioneMensile::where('mese', $mese)
            ->where('anno', $anno)
            ->first();
        
        // Ottieni tutti i militari (filtrati per polo se specificato)
        $militariQuery = Militare::with(['grado', 'polo', 'compagnia']);
        
        if ($poloId) {
            $militariQuery->where('polo_id', $poloId);
        }
        
        $militari = $militariQuery->orderByGradoENome()->get();
        
        // Trova i militari impegnati da CPT con dettagli
        $impegniCpt = collect();
        if ($pianificazioneMensile) {
            $impegniCpt = PianificazioneGiornaliera::where('pianificazione_mensile_id', $pianificazioneMensile->id)
                ->where('giorno', $giorno)
                ->whereNotNull('tipo_servizio_id')
                ->with('tipoServizio')
                ->get()
                ->keyBy('militare_id');
        }
        
        // Trova i militari impegnati da Turni con dettagli
        $impegniTurno = AssegnazioneTurno::whereDate('data_servizio', $data)
            ->with('servizioTurno')
            ->get()
            ->keyBy('militare_id');
        
        // Costruisci lista impegnati con motivo
        $militariImpegnatiIds = $impegniCpt->keys()->merge($impegniTurno->keys())->unique();
        
        // Filtra i militari liberi e impegnati
        $militariLiberi = $militari->whereNotIn('id', $militariImpegnatiIds);
        $militariImpegnatiList = $militari->whereIn('id', $militariImpegnatiIds);
        
        return response()->json([
            'success' => true,
            'data' => $data->format('d/m/Y'),
            'totale' => $militari->count(),
            'liberi' => $militariLiberi->count(),
            'impegnati' => $militariImpegnatiList->count(),
            'militari_liberi' => $militariLiberi->map(function($m) {
                return [
                    'id' => $m->id,
                    'nome_completo' => ($m->grado->sigla ?? '') . ' ' . $m->cognome . ' ' . $m->nome,
                    'grado' => $m->grado->sigla ?? '',
                    'polo' => $m->polo->nome ?? 'N/A',
                    'compagnia' => $m->compagnia->nome ?? 'N/A'
                ];
            })->values(),
            'militari_impegnati' => $militariImpegnatiList->map(function($m) use ($impegniCpt, $impegniTurno) {
                // Determina il motivo dell'impegno
                $motivo = 'Impegnato';
                $codice = '';
                $fonte = '';
                
                if ($impegniCpt->has($m->id)) {
                    $impegno = $impegniCpt[$m->id];
                    $codice = $impegno->tipoServizio->codice ?? '';
                    $motivo = $impegno->tipoServizio->nome ?? 'CPT';
                    $fonte = 'CPT';
                } elseif ($impegniTurno->has($m->id)) {
                    $impegno = $impegniTurno[$m->id];
                    $codice = $impegno->servizioTurno->codice ?? '';
                    $motivo = $impegno->servizioTurno->nome ?? 'Turno';
                    $fonte = 'Turno';
                }
                
                return [
                    'id' => $m->id,
                    'nome_completo' => ($m->grado->sigla ?? '') . ' ' . $m->cognome . ' ' . $m->nome,
                    'grado' => $m->grado->sigla ?? '',
                    'polo' => $m->polo->nome ?? 'N/A',
                    'compagnia' => $m->compagnia->nome ?? 'N/A',
                    'codice' => $codice,
                    'motivo' => $motivo,
                    'fonte' => $fonte
                ];
            })->values()
        ]);
    }
    
    /**
     * Ritorna le festività per anno
     */
    private function getFestivita()
    {
        return [
            2025 => [
                '01-01' => 'Capodanno',
                '01-06' => 'Epifania', 
                '04-20' => 'Pasqua',
                '04-21' => 'Lunedì dell\'Angelo',
                '04-25' => 'Festa della Liberazione',
                '05-01' => 'Festa del Lavoro',
                '06-02' => 'Festa della Repubblica',
                '08-15' => 'Ferragosto',
                '11-01' => 'Ognissanti',
                '12-08' => 'Immacolata Concezione',
                '12-25' => 'Natale',
                '12-26' => 'Santo Stefano'
            ],
            2026 => [
                '01-01' => 'Capodanno',
                '01-06' => 'Epifania',
                '04-05' => 'Pasqua',
                '04-06' => 'Lunedì dell\'Angelo',
                '04-25' => 'Festa della Liberazione',
                '05-01' => 'Festa del Lavoro',
                '06-02' => 'Festa della Repubblica',
                '08-15' => 'Ferragosto',
                '11-01' => 'Ognissanti',
                '12-08' => 'Immacolata Concezione',
                '12-25' => 'Natale',
                '12-26' => 'Santo Stefano'
            ],
        ];
    }
}

