<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PianificazioneMensile;
use App\Models\PianificazioneGiornaliera;
use App\Models\Militare;
use App\Models\TipoServizio;
use App\Models\CodiciServizioGerarchia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Controller per la gestione della pianificazione mensile
 * 
 * Replica la vista del CPT Excel con tutti i militari e i loro impegni giornalieri
 */
class PianificazioneController extends Controller
{
    /**
     * Vista principale della pianificazione mensile (come CPT)
     */
    public function index(Request $request)
    {
        $mese = $request->get('mese', 10); // Default: Ottobre
        $anno = $request->get('anno', 2025); // Default: 2025
        
        // Trova la pianificazione mensile
        $pianificazioneMensile = PianificazioneMensile::where('mese', $mese)
            ->where('anno', $anno)
            ->first();
            
        if (!$pianificazioneMensile) {
            // Se non esiste, creala
            $pianificazioneMensile = PianificazioneMensile::create([
                'mese' => $mese,
                'anno' => $anno,
                'nome' => Carbon::createFromDate($anno, $mese, 1)->format('F Y'),
                'stato' => 'attiva', // Usa 'stato' invece di 'attiva'
                'data_creazione' => Carbon::createFromDate($anno, $mese, 1)->format('Y-m-d') // Date format invece di timestamp
            ]);
        }
        
        // Ottieni tutti i militari con le loro informazioni e applica filtri
        $militariQuery = Militare::with([
            'grado',
            'plotone',
            'polo',
            'mansione',
            'ruolo', 
            'approntamentoPrincipale',
            'approntamenti',
            'pianificazioniGiornaliere' => function($query) use ($pianificazioneMensile) {
                $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                      ->with(['tipoServizio', 'tipoServizio.codiceGerarchia']);
            }
        ]);

        // Applica filtri
        if ($request->filled('grado_id')) {
            $militariQuery->where('grado_id', $request->grado_id);
        }

        if ($request->filled('plotone_id')) {
            $militariQuery->where('plotone_id', $request->plotone_id);
        }

        if ($request->filled('ufficio_id')) {
            $militariQuery->where('polo_id', $request->ufficio_id);
        }

        if ($request->filled('mansione')) {
            $militariQuery->whereHas('mansione', function($q) use ($request) {
                $q->where('nome', 'like', '%' . $request->mansione . '%');
            });
        }

        if ($request->filled('approntamento_id')) {
            if ($request->approntamento_id === 'libero') {
                // Filtra militari senza approntamenti
                $militariQuery->doesntHave('approntamenti');
            } else {
                $militariQuery->whereHas('approntamenti', function($query) use ($request) {
                    $query->where('approntamento_id', $request->approntamento_id);
                });
            }
        }

        if ($request->filled('impegno')) {
            if ($request->impegno === 'libero') {
                // Filtra militari senza impegni nel mese corrente
                $militariQuery->whereDoesntHave('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile) {
                    $query->where('pianificazione_mensile_id', $pianificazioneMensile->id);
                });
            } else {
                $militariQuery->whereHas('pianificazioniGiornaliere', function($query) use ($request, $pianificazioneMensile) {
                    $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                          ->whereHas('tipoServizio', function($q) use ($request) {
                              $q->where('codice', $request->impegno);
                          });
                });
            }
        }

        if ($request->filled('giorno')) {
            $militariQuery->whereHas('pianificazioniGiornaliere', function($query) use ($request, $pianificazioneMensile) {
                $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                      ->where('giorno', $request->giorno);
            });
        }

        $militari = $militariQuery->orderByGradoENome()->get();
        
        // Calcola i giorni del mese
        $dataInizio = Carbon::createFromDate($anno, $mese, 1);
        $dataFine = $dataInizio->copy()->endOfMonth();
        $giorniMese = [];
        
        // Festività italiane con date corrette per gli anni 2025-2030
        $festivitaPerAnno = [
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
            2027 => [
                '01-01' => 'Capodanno',
                '01-06' => 'Epifania',
                '03-28' => 'Pasqua',
                '03-29' => 'Lunedì dell\'Angelo',
                '04-25' => 'Festa della Liberazione',
                '05-01' => 'Festa del Lavoro',
                '06-02' => 'Festa della Repubblica',
                '08-15' => 'Ferragosto',
                '11-01' => 'Ognissanti',
                '12-08' => 'Immacolata Concezione',
                '12-25' => 'Natale',
                '12-26' => 'Santo Stefano'
            ],
            2028 => [
                '01-01' => 'Capodanno',
                '01-06' => 'Epifania',
                '04-16' => 'Pasqua',
                '04-17' => 'Lunedì dell\'Angelo',
                '04-25' => 'Festa della Liberazione',
                '05-01' => 'Festa del Lavoro',
                '06-02' => 'Festa della Repubblica',
                '08-15' => 'Ferragosto',
                '11-01' => 'Ognissanti',
                '12-08' => 'Immacolata Concezione',
                '12-25' => 'Natale',
                '12-26' => 'Santo Stefano'
            ],
            2029 => [
                '01-01' => 'Capodanno',
                '01-06' => 'Epifania',
                '04-01' => 'Pasqua',
                '04-02' => 'Lunedì dell\'Angelo',
                '04-25' => 'Festa della Liberazione',
                '05-01' => 'Festa del Lavoro',
                '06-02' => 'Festa della Repubblica',
                '08-15' => 'Ferragosto',
                '11-01' => 'Ognissanti',
                '12-08' => 'Immacolata Concezione',
                '12-25' => 'Natale',
                '12-26' => 'Santo Stefano'
            ],
            2030 => [
                '01-01' => 'Capodanno',
                '01-06' => 'Epifania',
                '04-21' => 'Pasqua',
                '04-22' => 'Lunedì dell\'Angelo',
                '04-25' => 'Festa della Liberazione',
                '05-01' => 'Festa del Lavoro',
                '06-02' => 'Festa della Repubblica',
                '08-15' => 'Ferragosto',
                '11-01' => 'Ognissanti',
                '12-08' => 'Immacolata Concezione',
                '12-25' => 'Natale',
                '12-26' => 'Santo Stefano'
            ]
        ];
        
        $festivitaFisse = $festivitaPerAnno[$anno] ?? [];
        
        for ($giorno = 1; $giorno <= $dataFine->day; $giorno++) {
            $data = Carbon::createFromDate($anno, $mese, $giorno);
            
            // Nomi giorni in italiano
            $giorniItaliani = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];
            $nomeGiornoItaliano = $giorniItaliani[$data->dayOfWeek];
            
            // Controlla se è una festività
            $dataFormattata = $data->format('m-d');
            $isWeekend = $data->isWeekend();
            $isHoliday = isset($festivitaFisse[$dataFormattata]);
            
            $giorniMese[] = [
                'giorno' => $giorno,
                'data' => $data,
                'nome_giorno' => $nomeGiornoItaliano,
                'is_weekend' => $isWeekend,
                'is_holiday' => $isHoliday,
                'is_today' => $data->isToday(),
                'festivita_nome' => $isHoliday ? $festivitaFisse[$dataFormattata] : null
            ];
        }
        
        // Organizza i dati per la vista - MOSTRA TUTTI I MILITARI
        $militariConPianificazione = [];
        foreach ($militari as $militare) {
            $pianificazioniPerGiorno = [];
            
            // Carica le pianificazioni esistenti
            foreach ($militare->pianificazioniGiornaliere as $pianificazione) {
                $pianificazioniPerGiorno[$pianificazione->giorno] = $pianificazione;
            }
            
            // SEMPRE aggiungi il militare, anche se non ha pianificazioni
            $militariConPianificazione[] = [
                'militare' => $militare,
                'pianificazioni' => $pianificazioniPerGiorno
            ];
        }
        
        // Ottieni statistiche
        $statistiche = $this->calcolaStatistiche($pianificazioneMensile, $militari->count(), count($giorniMese));
        
        // Ottieni codici servizio per i filtri
        $codiciGerarchia = CodiciServizioGerarchia::orderBy('macro_attivita')
            ->orderBy('tipo_attivita')
            ->get()
            ->groupBy('macro_attivita');
        
        // Dati per i filtri
        $gradi = \App\Models\Grado::orderBy('ordine', 'asc')->get(); // Ordine 1 = COL (più alto), ordine 23 = SOL (più basso)
        $plotoni = \App\Models\Plotone::orderBy('nome')->get();
        $uffici = \App\Models\Polo::orderBy('nome')->get();
        $approntamenti = \App\Models\Approntamento::orderBy('nome')->get();
        $mansioni = \App\Models\Mansione::select('nome')->distinct()->orderBy('nome')->get();
        
        // Impegni (tipi servizio) organizzati per categorie - CODICI CORRETTI DALL'IMMAGINE
        $impegniPerCategoria = [
            'ASSENTE' => [
                (object)['id' => 'LS', 'codice' => 'LS', 'nome' => 'LICENZA STRAORD.', 'categoria' => 'ASSENTE'],
                (object)['id' => 'LO', 'codice' => 'LO', 'nome' => 'LICENZA ORD.', 'categoria' => 'ASSENTE'],
                (object)['id' => 'LM', 'codice' => 'LM', 'nome' => 'LICENZA DI MATERNITA\'', 'categoria' => 'ASSENTE'],
                (object)['id' => 'P', 'codice' => 'P', 'nome' => 'PERMESSINO', 'categoria' => 'ASSENTE'],
                (object)['id' => 'TIR', 'codice' => 'TIR', 'nome' => 'TIROCINIO', 'categoria' => 'ASSENTE'],
                (object)['id' => 'TRAS', 'codice' => 'TRAS', 'nome' => 'TRASFERITO', 'categoria' => 'ASSENTE']
            ],
            'PROVVEDIMENTI MEDICO SANITARI' => [
                (object)['id' => 'RMD', 'codice' => 'RMD', 'nome' => 'RIPOSO MEDICO DOMICILIARE', 'categoria' => 'PROVVEDIMENTI MEDICO SANITARI'],
                (object)['id' => 'LC', 'codice' => 'LC', 'nome' => 'LICENZA DI CONVALESCENZA', 'categoria' => 'PROVVEDIMENTI MEDICO SANITARI'],
                (object)['id' => 'IS', 'codice' => 'IS', 'nome' => 'ISOLAMENTO/QUARANTENA', 'categoria' => 'PROVVEDIMENTI MEDICO SANITARI']
            ],
            'SERVIZIO' => [
                (object)['id' => 'S-G1', 'codice' => 'S-G1', 'nome' => 'GUARDIA D\'AVANZO LUNGA', 'categoria' => 'SERVIZIO'],
                (object)['id' => 'S-G2', 'codice' => 'S-G2', 'nome' => 'GUARDIA D\'AVANZO CORTA', 'categoria' => 'SERVIZIO'],
                (object)['id' => 'S-SA', 'codice' => 'S-SA', 'nome' => 'SORVEGLIANZA D\'AVANZO', 'categoria' => 'SERVIZIO'],
                (object)['id' => 'S-CD1', 'codice' => 'S-CD1', 'nome' => 'CONDUTTORE GUARDIA LUNGO', 'categoria' => 'SERVIZIO'],
                (object)['id' => 'S-CD2', 'codice' => 'S-CD2', 'nome' => 'CONDUTTORE PIAN DEL TERMINE CORTO', 'categoria' => 'SERVIZIO'],
                (object)['id' => 'S-SG', 'codice' => 'S-SG', 'nome' => 'SOTTUFFICIALE DI GIORNATA', 'categoria' => 'SERVIZIO'],
                (object)['id' => 'S-CG', 'codice' => 'S-CG', 'nome' => 'COMANDANTE DELLA GUARDIA', 'categoria' => 'SERVIZIO'],
                (object)['id' => 'S-UI', 'codice' => 'S-UI', 'nome' => 'UFFICIALE DI ISPEZIONE', 'categoria' => 'SERVIZIO'],
                (object)['id' => 'S-UP', 'codice' => 'S-UP', 'nome' => 'UFFICIALE DI PICCHETTO', 'categoria' => 'SERVIZIO'],
                (object)['id' => 'S-AE', 'codice' => 'S-AE', 'nome' => 'AREE ESTERNE', 'categoria' => 'SERVIZIO'],
                (object)['id' => 'S-ARM', 'codice' => 'S-ARM', 'nome' => 'ARMIERE DI SERVIZIO', 'categoria' => 'SERVIZIO'],
                (object)['id' => 'SI-GD', 'codice' => 'SI-GD', 'nome' => 'SERVIZIO ISOLATO-GUARDIA DISTACCATA', 'categoria' => 'SERVIZIO'],
                (object)['id' => 'SI', 'codice' => 'SI', 'nome' => 'SERVIZIO ISOLATO-CAPOMACCHINA/CAU', 'categoria' => 'SERVIZIO'],
                (object)['id' => 'SI-VM', 'codice' => 'SI-VM', 'nome' => 'VISITA MEDICA', 'categoria' => 'SERVIZIO'],
                (object)['id' => 'S-PI', 'codice' => 'S-PI', 'nome' => 'PRONTO IMPIEGO', 'categoria' => 'SERVIZIO']
            ],
            'OPERAZIONE' => [
                (object)['id' => 'TO', 'codice' => 'TO', 'nome' => 'TEATRO OPERATIVO', 'categoria' => 'OPERAZIONE']
            ],
            'ADD./APP./CATTEDRE' => [
                (object)['id' => 'APS1', 'codice' => 'APS1', 'nome' => 'PRELIEVI', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'APS2', 'codice' => 'APS2', 'nome' => 'VACCINI', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'APS3', 'codice' => 'APS3', 'nome' => 'ECG', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'APS4', 'codice' => 'APS4', 'nome' => 'IDONEITA', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'AL-ELIX', 'codice' => 'AL-ELIX', 'nome' => 'ELITRASPORTO', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'AL-MCM', 'codice' => 'AL-MCM', 'nome' => 'MCM', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'AL-BLS', 'codice' => 'AL-BLS', 'nome' => 'BLS', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'AL-CIED', 'codice' => 'AL-CIED', 'nome' => 'C-IED', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'AL-SM', 'codice' => 'AL-SM', 'nome' => 'STRESS MANAGEMENT', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'AL-RM', 'codice' => 'AL-RM', 'nome' => 'RAPPORTO MEDIA', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'AL-RSPP', 'codice' => 'AL-RSPP', 'nome' => 'RSPP', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'AL-LEG', 'codice' => 'AL-LEG', 'nome' => 'ASPETTI LEGALI', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'AL-SEA', 'codice' => 'AL-SEA', 'nome' => 'SEXUAL EXPLOITATION AND ABUSE', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'AL-MI', 'codice' => 'AL-MI', 'nome' => 'MALATTIE INFETTIVE', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'AL-PO', 'codice' => 'AL-PO', 'nome' => 'PROPAGANDA OSTILE', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'AL-PI', 'codice' => 'AL-PI', 'nome' => 'PUBBLICA INFORMAZIONE E COMUNICAZIONE', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'AP-M', 'codice' => 'AP-M', 'nome' => 'MANTENIMENTO', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'AP-A', 'codice' => 'AP-A', 'nome' => 'APPRONTAMENTO', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'AC-SW', 'codice' => 'AC-SW', 'nome' => 'CORSO IN SMART WORKING', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'AC', 'codice' => 'AC', 'nome' => 'CORSO SERVIZIO ISOLATO', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => 'PEFO', 'codice' => 'PEFO', 'nome' => 'PEFO', 'categoria' => 'ADD./APP./CATTEDRE']
            ],
            'SUPP.CIS/EXE' => [
                (object)['id' => 'EXE', 'codice' => 'EXE', 'nome' => 'ESERCITAZIONE', 'categoria' => 'SUPP.CIS/EXE']
            ]
        ];
        
        // Lista piatta per compatibilità
        $impegni = collect();
        foreach ($impegniPerCategoria as $categoria => $impegniCategoria) {
            $impegni = $impegni->merge($impegniCategoria);
        }

        // Nomi dei mesi in italiano
        $mesiItaliani = [
            1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
            5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
            9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
        ];

        return view('pianificazione.index', compact(
            'pianificazioneMensile',
            'militariConPianificazione',
            'giorniMese',
            'statistiche',
            'codiciGerarchia',
            'mese',
            'anno',
            'gradi',
            'plotoni', 
            'uffici',
            'approntamenti',
            'mansioni',
            'impegni',
            'impegniPerCategoria',
            'mesiItaliani'
        ));
    }
    
    /**
     * Vista della pianificazione per un singolo militare
     */
    public function militare(Request $request, Militare $militare)
    {
        $mese = $request->get('mese', Carbon::now()->month);
        $anno = $request->get('anno', Carbon::now()->year);
        
        // Trova la pianificazione mensile
        $pianificazioneMensile = PianificazioneMensile::where('mese', $mese)
            ->where('anno', $anno)
            ->first();
            
        if (!$pianificazioneMensile) {
            return redirect()->route('pianificazione.index', compact('mese', 'anno'))
                ->with('warning', 'Pianificazione mensile non trovata. Creane una prima.');
        }
        
        // Ottieni le pianificazioni del militare
        $pianificazioni = PianificazioneGiornaliera::where('militare_id', $militare->id)
            ->where('pianificazione_mensile_id', $pianificazioneMensile->id)
            ->with(['tipoServizio', 'tipoServizio.codiceGerarchia'])
            ->orderBy('giorno')
            ->get()
            ->keyBy('giorno');
        
        // Calcola i giorni del mese
        $dataInizio = Carbon::createFromDate($anno, $mese, 1);
        $dataFine = $dataInizio->copy()->endOfMonth();
        $giorniMese = [];
        
        for ($giorno = 1; $giorno <= $dataFine->day; $giorno++) {
            $data = Carbon::createFromDate($anno, $mese, $giorno);
            $pianificazione = $pianificazioni->get($giorno);
            
            // Nomi giorni in italiano
            $giorniItaliani = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];
            $nomeGiornoItaliano = $giorniItaliani[$data->dayOfWeek];
            
            $giorniMese[] = [
                'giorno' => $giorno,
                'data' => $data,
                'nome_giorno' => $nomeGiornoItaliano,
                'is_weekend' => $data->isWeekend(),
                'is_today' => $data->isToday(),
                'pianificazione' => $pianificazione
            ];
        }
        
        // Carica dati aggiuntivi del militare
        $militare->load([
            'grado',
            'plotone',
            'approntamentoPrincipale',
            'ultimoPoligono.tipoPoligono',
            'valutazioni' => function($query) {
                $query->latest()->limit(5);
            }
        ]);
        
        // Statistiche del militare
        $statisticheMilitare = [
            'giorni_servizio' => $pianificazioni->where('tipoServizio.codiceGerarchia.impiego', 'PRESENTE_SERVIZIO')->count(),
            'giorni_disponibile' => $pianificazioni->where('tipoServizio.codiceGerarchia.impiego', 'DISPONIBILE')->count(),
            'giorni_assente' => $pianificazioni->where('tipoServizio.codiceGerarchia.impiego', 'NON_DISPONIBILE')->count(),
            'giorni_non_pianificati' => $dataFine->day - $pianificazioni->count()
        ];
        
        return view('pianificazione.militare', compact(
            'militare',
            'pianificazioneMensile',
            'giorniMese',
            'statisticheMilitare',
            'mese',
            'anno'
        ));
    }
    
    /**
     * Aggiorna la pianificazione di un giorno per un militare
     */
    public function updateGiorno(Request $request, Militare $militare)
    {
        try {
            // Log per debug
            \Log::info('UpdateGiorno request', [
                'militare_id' => $militare->id,
                'request_data' => $request->all()
            ]);
        
        $request->validate([
            'pianificazione_mensile_id' => 'required|exists:pianificazioni_mensili,id',
            'giorno' => 'required|integer|min:1|max:31',
                'tipo_servizio_id' => 'nullable|string',
                'mese' => 'nullable|integer|min:1|max:12',
                'anno' => 'nullable|integer|min:2020|max:2030'
        ]);
        
        // Gestione esplicita del caso "nessun impegno"
            $tipoServizioId = null;
            if ($request->tipo_servizio_id) {
                // Cerca il TipoServizio per codice invece che per ID
                $tipoServizio = TipoServizio::where('codice', $request->tipo_servizio_id)->first();
                \Log::info('TipoServizio search', [
                    'codice_ricercato' => $request->tipo_servizio_id,
                    'trovato' => $tipoServizio ? $tipoServizio->id : null
                ]);
                if ($tipoServizio) {
                    $tipoServizioId = $tipoServizio->id;
                }
            }
            
            // Determina mese e anno per questo giorno specifico
            $mese = $request->mese;
            $anno = $request->anno;
            
            // Se mese e anno non sono specificati, usa quelli della pianificazione mensile corrente
            if (!$mese || !$anno) {
                $pianificazioneMensileCorrente = PianificazioneMensile::find($request->pianificazione_mensile_id);
                $mese = $pianificazioneMensileCorrente->mese;
                $anno = $pianificazioneMensileCorrente->anno;
            }
            
            // Trova o crea la pianificazione mensile per questo mese/anno
            $pianificazioneMensile = PianificazioneMensile::firstOrCreate(
                [
                    'mese' => $mese,
                    'anno' => $anno
                ],
                [
                    'nome' => Carbon::createFromDate($anno, $mese, 1)->format('F Y'),
                    'stato' => 'attiva',
                    'data_creazione' => Carbon::createFromDate($anno, $mese, 1)->format('Y-m-d')
                ]
            );
        
        $pianificazione = PianificazioneGiornaliera::updateOrCreate(
            [
                'militare_id' => $militare->id,
                    'pianificazione_mensile_id' => $pianificazioneMensile->id,
                'giorno' => $request->giorno
            ],
            [
                'tipo_servizio_id' => $tipoServizioId
            ]
        );
        
        // Salva e ricarica i dati
        $pianificazione->save();
        $pianificazione->refresh();
        
            return response()->json([
                'success' => true,
                'pianificazione' => $pianificazione->load(['tipoServizio'])
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel salvataggio: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateGiorniRange(Request $request, Militare $militare)
    {
        try {
            $request->validate([
                'giorni' => 'required|array',
                'giorni.*.giorno' => 'required|integer|min:1|max:31',
                'giorni.*.mese' => 'required|integer|min:1|max:12',
                'giorni.*.anno' => 'required|integer|min:2020|max:2030',
                'tipo_servizio_id' => 'nullable|string'
            ]);
            
            // Gestione esplicita del caso "nessun impegno"
            $tipoServizioId = null;
            if ($request->tipo_servizio_id) {
                $tipoServizio = TipoServizio::where('codice', $request->tipo_servizio_id)->first();
                if ($tipoServizio) {
                    $tipoServizioId = $tipoServizio->id;
                }
            }
            
            $pianificazioni = [];
            
            foreach ($request->giorni as $giornoData) {
                // Trova o crea la pianificazione mensile per questo mese/anno
                $pianificazioneMensile = PianificazioneMensile::firstOrCreate(
                    [
                        'mese' => $giornoData['mese'],
                        'anno' => $giornoData['anno']
                    ],
                    [
                        'nome' => Carbon::createFromDate($giornoData['anno'], $giornoData['mese'], 1)->format('F Y'),
                        'stato' => 'attiva',
                        'data_creazione' => Carbon::createFromDate($giornoData['anno'], $giornoData['mese'], 1)->format('Y-m-d')
                    ]
                );
                
                $pianificazione = PianificazioneGiornaliera::updateOrCreate(
                    [
                        'militare_id' => $militare->id,
                        'pianificazione_mensile_id' => $pianificazioneMensile->id,
                        'giorno' => $giornoData['giorno']
                    ],
                    [
                        'tipo_servizio_id' => $tipoServizioId
                    ]
                );
                
                $pianificazioni[] = $pianificazione->load(['tipoServizio']);
            }
        
        return response()->json([
            'success' => true,
                'pianificazioni' => $pianificazioni
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Calcola le statistiche della pianificazione mensile
     */
    private function calcolaStatistiche(PianificazioneMensile $pianificazione, int $numeroMilitari, int $giorniMese): array
    {
        $totalePianificazioni = PianificazioneGiornaliera::where('pianificazione_mensile_id', $pianificazione->id)->count();
        $maxPianificazioni = $numeroMilitari * $giorniMese;
        
        $statistichePerImpiego = PianificazioneGiornaliera::where('pianificazione_mensile_id', $pianificazione->id)
            ->join('tipi_servizio', 'pianificazioni_giornaliere.tipo_servizio_id', '=', 'tipi_servizio.id')
            ->join('codici_servizio_gerarchia', 'tipi_servizio.codice_gerarchia_id', '=', 'codici_servizio_gerarchia.id')
            ->select('codici_servizio_gerarchia.impiego', DB::raw('COUNT(*) as totale'))
            ->groupBy('codici_servizio_gerarchia.impiego')
            ->get()
            ->pluck('totale', 'impiego')
            ->toArray();
        
        return [
            'totale_pianificazioni' => $totalePianificazioni,
            'percentuale_completamento' => $maxPianificazioni > 0 ? round(($totalePianificazioni / $maxPianificazioni) * 100, 1) : 0,
            'giorni_non_pianificati' => $maxPianificazioni - $totalePianificazioni,
            'per_impiego' => [
                'disponibile' => $statistichePerImpiego['DISPONIBILE'] ?? 0,
                'servizio' => $statistichePerImpiego['PRESENTE_SERVIZIO'] ?? 0,
                'non_disponibile' => $statistichePerImpiego['NON_DISPONIBILE'] ?? 0
            ]
        ];
    }

    /**
     * Esporta la pianificazione mensile in formato Excel (CPT.xlsx)
     */
    public function exportExcel(Request $request)
    {
        $mese = $request->get('mese', now()->month);
        $anno = $request->get('anno', now()->year);

        // Ottieni la pianificazione mensile
        $pianificazioneMensile = PianificazioneMensile::where('mese', $mese)
            ->where('anno', $anno)
            ->first();

        if (!$pianificazioneMensile) {
            return redirect()->back()->with('error', 'Pianificazione mensile non trovata');
        }

        // Ottieni tutti i militari con le loro pianificazioni e applica filtri
        $militariQuery = Militare::with([
            'grado',
            'plotone',
            'polo',
            'mansione',
            'ruolo',
            'approntamentoPrincipale',
            'approntamenti',
            'pianificazioniGiornaliere' => function($query) use ($pianificazioneMensile) {
                $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                      ->with(['tipoServizio', 'tipoServizio.codiceGerarchia']);
            }
        ]);

        // Applica filtri (stessi della vista index)
        if ($request->filled('grado_id')) {
            $militariQuery->where('grado_id', $request->grado_id);
        }

        if ($request->filled('plotone_id')) {
            $militariQuery->where('plotone_id', $request->plotone_id);
        }

        if ($request->filled('ufficio_id')) {
            $militariQuery->where('polo_id', $request->ufficio_id);
        }

        if ($request->filled('mansione')) {
            $militariQuery->whereHas('mansione', function($q) use ($request) {
                $q->where('nome', 'like', '%' . $request->mansione . '%');
            });
        }

        if ($request->filled('approntamento_id')) {
            if ($request->approntamento_id === 'libero') {
                // Filtra militari senza approntamenti
                $militariQuery->doesntHave('approntamenti');
            } else {
                $militariQuery->whereHas('approntamenti', function($query) use ($request) {
                    $query->where('approntamento_id', $request->approntamento_id);
                });
            }
        }

        if ($request->filled('impegno')) {
            if ($request->impegno === 'libero') {
                // Filtra militari senza impegni nel mese corrente
                $militariQuery->whereDoesntHave('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile) {
                    $query->where('pianificazione_mensile_id', $pianificazioneMensile->id);
                });
            } else {
                $militariQuery->whereHas('pianificazioniGiornaliere', function($query) use ($request, $pianificazioneMensile) {
                    $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                          ->whereHas('tipoServizio', function($q) use ($request) {
                              $q->where('codice', $request->impegno);
                          });
                });
            }
        }

        if ($request->filled('giorno')) {
            $militariQuery->whereHas('pianificazioniGiornaliere', function($query) use ($request, $pianificazioneMensile) {
                $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                      ->where('giorno', $request->giorno);
            });
        }

        $militari = $militariQuery->orderByGradoENome()->get();

        // Crea il file Excel usando PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Imposta header del foglio
        $nomiMesi = ['', 'GENNAIO', 'FEBBRAIO', 'MARZO', 'APRILE', 'MAGGIO', 'GIUGNO',
                    'LUGLIO', 'AGOSTO', 'SETTEMBRE', 'OTTOBRE', 'NOVEMBRE', 'DICEMBRE'];
        
        // Nome del foglio dinamico basato su mese e anno correnti
        $sheet->setTitle($nomiMesi[$mese] . ' ' . $anno);
        
        $sheet->setCellValue('A1', $nomiMesi[$mese] . ' ' . $anno);
        $sheet->mergeCells('A1:AJ1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        // Header delle colonne (riga 3)
        $headers = ['GRADO', 'COGNOME', 'NOME', 'PLOTONE', 'UFFICIO', 'MANSIONE', 'APPRONTAMENTO'];
        
        // Aggiungi giorni del mese
        $giorniMese = \Carbon\Carbon::createFromDate($anno, $mese, 1)->daysInMonth;
        for ($i = 1; $i <= $giorniMese; $i++) {
            $headers[] = $i;
        }

        // Scrivi gli header
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $sheet->getStyle($col . '3')->getFont()->setBold(true);
            $sheet->getStyle($col . '3')->getAlignment()->setHorizontal('center');
            $col++;
        }

        // Dati dei militari (inizia dalla riga 4)
        $row = 4;
        foreach ($militari as $militare) {
            $sheet->setCellValue('A' . $row, $militare->grado->nome ?? '');
            $sheet->setCellValue('B' . $row, $militare->cognome);
            $sheet->setCellValue('C' . $row, $militare->nome);
            $sheet->setCellValue('D' . $row, str_replace(['° Plotone', 'Plotone'], ['°', ''], $militare->plotone->nome ?? ''));
            $sheet->setCellValue('E' . $row, $militare->polo->nome ?? '');
            $sheet->setCellValue('F' . $row, $militare->mansione->nome ?? '');
            $sheet->setCellValue('G' . $row, $militare->approntamentoPrincipale->nome ?? '');

            // Aggiungi i dati di pianificazione per ogni giorno
            $pianificazioniPerGiorno = [];
            foreach ($militare->pianificazioniGiornaliere as $pianificazione) {
                $pianificazioniPerGiorno[$pianificazione->giorno] = $pianificazione;
            }

            $col = 'H'; // Inizia dalla colonna H per i giorni
            for ($giorno = 1; $giorno <= $giorniMese; $giorno++) {
                $codice = '';
                if (isset($pianificazioniPerGiorno[$giorno])) {
                    $tipoServizio = $pianificazioniPerGiorno[$giorno]->tipoServizio;
                    $codice = $tipoServizio->codice ?? '';
                }
                
                $sheet->setCellValue($col . $row, $codice);
                
                // Applica colori CPT se il codice esiste
                if ($codice && isset($pianificazioniPerGiorno[$giorno])) {
                    $this->applicaColoreCPT($sheet, $col . $row, $codice);
                }
                
                $col++;
            }
            
            $row++;
        }

        // Stile generale della tabella
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(7 + $giorniMese);
        $dataRange = 'A3:' . $lastColumn . ($row - 1);
        
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        // Imposta larghezze colonne più larghe
        $sheet->getColumnDimension('A')->setWidth(20); // GRADO
        $sheet->getColumnDimension('B')->setWidth(18); // COGNOME
        $sheet->getColumnDimension('C')->setWidth(15); // NOME
        $sheet->getColumnDimension('D')->setWidth(12); // PLOTONE
        $sheet->getColumnDimension('E')->setWidth(18); // UFFICIO
        $sheet->getColumnDimension('F')->setWidth(20); // MANSIONE
        $sheet->getColumnDimension('G')->setWidth(20); // APPRONTAMENTO
        
        // Giorni del mese - larghezza fissa
        for ($i = 0; $i < $giorniMese; $i++) {
            $colIndex = 8 + $i; // Inizia dalla colonna H (8)
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setWidth(8);
        }

        // Prepara il download
        $fileName = 'CPT_' . $nomiMesi[$mese] . '_' . $anno . '.xlsx';
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Headers per il download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    /**
     * Applica i colori CPT alla cella in base al codice (identici alla vista web)
     */
    private function applicaColoreCPT($sheet, $cellAddress, $codice)
    {
        $colore = null;
        $textColor = 'FF000000'; // Nero di default
        
        // Colori CPT esatti dalla vista
        switch ($codice) {
            // ASSENTE - Giallo
            case 'LS':
            case 'LO':
            case 'LM':
            case 'P':
            case 'TIR':
            case 'TRAS':
                $colore = 'FFFFFF00'; // #ffff00
                $textColor = 'FF000000'; // Nero
                break;
                
            // PROVVEDIMENTI MEDICO SANITARI - Rosso
            case 'RMD':
            case 'LC':
            case 'IS':
                $colore = 'FFFF0000'; // #ff0000
                $textColor = 'FFFFFFFF'; // Bianco
                break;
                
            // VERDE - Presente Servizio, Addestramento, Supporto
            case 'RIP':
            case 'S-G1':
            case 'S-G2':
            case 'S-SA':
            case 'S-CD1':
            case 'S-CD2':
            case 'S-CD3':
            case 'S-CD4':
            case 'S-SG':
            case 'S-CG':
            case 'S-UI':
            case 'S-UP':
            case 'S-AE':
            case 'S-ARM':
            case 'SI-GD':
            case 'SI':
            case 'S-VM':
            case 'S-PI':
            case 'G1':
            case 'G2':
            case 'CD2':
            case 'PDT1':
            case 'PDT2':
            case 'AE':
            case 'A-A':
            case 'CETLI':
            case 'LCC':
            case 'CENTURIA':
            case 'TIROCINIO':
            case 'APS1':
            case 'APS2':
            case 'APS3':
            case 'APS4':
            case 'AL-ELIX':
            case 'AL-MCM':
            case 'AL-BLS':
            case 'AL-CIED':
            case 'AL-SM':
            case 'AL-RM':
            case 'AL-RSPP':
            case 'AL-LEG':
            case 'AL-SEA':
            case 'AL-MI':
            case 'AL-PO':
            case 'AL-PI':
            case 'AP-M':
            case 'AP-A':
            case 'AC-SW':
            case 'AC':
            case 'PEFO':
            case 'EXE':
                $colore = 'FF00B050'; // #00b050
                $textColor = 'FFFFFFFF'; // Bianco
                break;
                
            // OPERAZIONE - Arancione
            case 'TO':
            case 'MCM':
            case 'KOSOVO':
                $colore = 'FFFFC000'; // #ffc000
                $textColor = 'FF000000'; // Nero
                break;
        }
        
        if ($colore) {
            $sheet->getStyle($cellAddress)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($colore);
            
            $sheet->getStyle($cellAddress)->getFont()->getColor()->setARGB($textColor);
            $sheet->getStyle($cellAddress)->getFont()->setBold(true);
            $sheet->getStyle($cellAddress)->getAlignment()->setHorizontal('center');
        }
    }

    /**
     * Calcola la data della Pasqua per un determinato anno
     */
    private function calcolaPasqua($anno)
    {
        // Algoritmo di Gauss per calcolare la Pasqua
        $a = $anno % 19;
        $b = intval($anno / 100);
        $c = $anno % 100;
        $d = intval($b / 4);
        $e = $b % 4;
        $f = intval(($b + 8) / 25);
        $g = intval(($b - $f + 1) / 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intval($c / 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intval(($a + 11 * $h + 22 * $l) / 451);
        $n = intval(($h + $l - 7 * $m + 114) / 31);
        $p = ($h + $l - 7 * $m + 114) % 31;
        
        return Carbon::createFromDate($anno, $n, $p + 1);
    }
}
