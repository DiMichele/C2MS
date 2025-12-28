<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PianificazioneMensile;
use App\Models\PianificazioneGiornaliera;
use App\Models\Militare;
use App\Models\TipoServizio;
use App\Models\CodiciServizioGerarchia;
use App\Models\ServizioTurno;
use App\Models\TurnoSettimanale;
use App\Models\AssegnazioneTurno;
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
        // Default: mese e anno correnti
        $now = Carbon::now();
        $mese = $request->get('mese', $now->month);
        $anno = $request->get('anno', $now->year);
        
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
            'patenti',
            'pianificazioniGiornaliere' => function($query) use ($pianificazioneMensile) {
                $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                      ->with(['tipoServizio', 'tipoServizio.codiceGerarchia']);
            }
        ]);

        // FILTRO PERMESSI: filtra per compagnia dell'utente se non è admin
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && !$user->hasRole('admin') && !$user->hasRole('amministratore')) {
            if ($user->compagnia_id) {
                $militariQuery->where('compagnia_id', $user->compagnia_id);
            }
        }

        // Applica filtri
        if ($request->filled('compagnia')) {
            $militariQuery->where('compagnia_id', $request->compagnia);
        }

        if ($request->filled('grado_id')) {
            $militariQuery->where('grado_id', $request->grado_id);
        }

        if ($request->filled('plotone_id')) {
            $militariQuery->where('plotone_id', $request->plotone_id);
        }

        if ($request->filled('patente')) {
            $militariQuery->whereHas('patenti', function($q) use ($request) {
                $q->where('categoria', $request->patente);
            });
        }

        // Filtro per compleanno
        if ($request->filled('compleanno')) {
            $oggi = now();
            
            switch ($request->compleanno) {
                case 'oggi':
                    $militariQuery->whereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$oggi->day, $oggi->month]);
                    break;
                    
                case 'ultimi_2':
                    // Compleanno negli ultimi 2 giorni (ieri e l'altro ieri)
                    $ieri = $oggi->copy()->subDay();
                    $altroIeri = $oggi->copy()->subDays(2);
                    
                    $militariQuery->where(function($q) use ($oggi, $ieri, $altroIeri) {
                        $q->whereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$oggi->day, $oggi->month])
                          ->orWhereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$ieri->day, $ieri->month])
                          ->orWhereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$altroIeri->day, $altroIeri->month]);
                    });
                    break;
                    
                case 'prossimi_2':
                    // Compleanno nei prossimi 2 giorni (domani e dopodomani)
                    $domani = $oggi->copy()->addDay();
                    $dopodomani = $oggi->copy()->addDays(2);
                    
                    $militariQuery->where(function($q) use ($oggi, $domani, $dopodomani) {
                        $q->whereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$oggi->day, $oggi->month])
                          ->orWhereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$domani->day, $domani->month])
                          ->orWhereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$dopodomani->day, $dopodomani->month]);
                    });
                    break;
            }
        }

        // FILTRO APPRONTAMENTI DISABILITATO - Tabella militare_approntamenti rimossa
        // if ($request->filled('approntamento_id')) {
        //     if ($request->approntamento_id === 'libero') {
        //         $militariQuery->doesntHave('approntamenti');
        //     } else {
        //         $militariQuery->whereHas('approntamenti', function($query) use ($request) {
        //             $query->where('approntamento_id', $request->approntamento_id);
        //         });
        //     }
        // }

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

        // Filtro stato_impegno per oggi (PRESENTI = libero, ASSENTI = impegnato)
        if ($request->filled('stato_impegno')) {
            $oggi = Carbon::today();
            
            if ($request->stato_impegno === 'libero') {
                // PRESENTI: Militari SENZA impegno per oggi
                $militariQuery->where(function($q) use ($pianificazioneMensile, $oggi) {
                    // O non hanno pianificazioni per oggi
                    $q->whereDoesntHave('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $oggi) {
                        $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                              ->where('giorno', $oggi->day);
                    })
                    // O hanno pianificazione ma senza tipo_servizio_id
                    ->orWhereHas('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $oggi) {
                        $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                              ->where('giorno', $oggi->day)
                              ->whereNull('tipo_servizio_id');
                    });
                });
            } elseif ($request->stato_impegno === 'impegnato') {
                // ASSENTI: Militari CON impegno per oggi
                $militariQuery->whereHas('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $oggi) {
                    $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                          ->where('giorno', $oggi->day)
                          ->whereNotNull('tipo_servizio_id');
                });
            }
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
        
        // Carica compagnie dal database
        $compagnie = \App\Models\Compagnia::orderBy('nome')->get();
        
        // Carica plotoni con la relazione alla compagnia
        $plotoni = \App\Models\Plotone::with('compagnia')->orderBy('nome')->get();
        
        $uffici = \App\Models\Polo::orderBy('nome')->get();
        // $approntamenti = \App\Models\Approntamento::orderBy('nome')->get(); // DISABILITATO
        $mansioni = \App\Models\Mansione::select('nome')->distinct()->orderBy('nome')->get();
        
        // Carica TUTTI i TipoServizio dal database (con ID NUMERICO corretto)
        $tipiServizioDb = TipoServizio::where('attivo', true)
            ->orderBy('categoria')
            ->orderBy('ordine')
            ->orderBy('nome')
            ->get();
        
        // Raggruppa per categorie (mantieni come Collection, NON array)
        $impegniPerCategoria = $tipiServizioDb->groupBy(function($tipo) {
            // Normalizza i nomi delle categorie per uniformità visiva
            $categoriaMap = [
                'servizio' => 'SERVIZIO',
                'permesso' => 'ASSENTE',
                'assenza' => 'ASSENTE',
                'formazione' => 'ADD./APP./CATTEDRE',
                'missione' => 'OPERAZIONE'
            ];
            return $categoriaMap[$tipo->categoria] ?? strtoupper($tipo->categoria);
        });
        
        // NOTA: Ora gli impegni vengono caricati REALMENTE dal database
        // Se vuoi aggiungere impieghi personalizzati, devono essere PRIMA inseriti nella tabella tipi_servizio
        
        // **BACKUP DEL VECCHIO CODICE**: Se necessario ripristinare i codici hardcoded, sono qui sotto (commentati)
        /*
        $impegniPerCategoria_HARDCODED = [
            'ASSENTE' => [
                (object)['id' => $tipiServizioDb['LS']->id ?? 'LS', 'codice' => 'LS', 'nome' => 'LICENZA STRAORD.', 'categoria' => 'ASSENTE'],
                (object)['id' => $tipiServizioDb['LO']->id ?? 'LO', 'codice' => 'LO', 'nome' => 'LICENZA ORD.', 'categoria' => 'ASSENTE'],
                (object)['id' => $tipiServizioDb['LM']->id ?? 'LM', 'codice' => 'LM', 'nome' => 'LICENZA DI MATERNITA\'', 'categoria' => 'ASSENTE'],
                (object)['id' => $tipiServizioDb['P']->id ?? 'P', 'codice' => 'P', 'nome' => 'PERMESSINO', 'categoria' => 'ASSENTE'],
                (object)['id' => $tipiServizioDb['TIR']->id ?? 'TIR', 'codice' => 'TIR', 'nome' => 'TIROCINIO', 'categoria' => 'ASSENTE'],
                (object)['id' => $tipiServizioDb['TRAS']->id ?? 'TRAS', 'codice' => 'TRAS', 'nome' => 'TRASFERITO', 'categoria' => 'ASSENTE']
            ],
            'PROVVEDIMENTI MEDICO SANITARI' => [
                (object)['id' => $tipiServizioDb['RMD']->id ?? 'RMD', 'codice' => 'RMD', 'nome' => 'RIPOSO MEDICO DOMICILIARE', 'categoria' => 'PROVVEDIMENTI MEDICO SANITARI'],
                (object)['id' => $tipiServizioDb['LC']->id ?? 'LC', 'codice' => 'LC', 'nome' => 'LICENZA DI CONVALESCENZA', 'categoria' => 'PROVVEDIMENTI MEDICO SANITARI'],
                (object)['id' => $tipiServizioDb['IS']->id ?? 'IS', 'codice' => 'IS', 'nome' => 'ISOLAMENTO/QUARANTENA', 'categoria' => 'PROVVEDIMENTI MEDICO SANITARI']
            ],
            'SERVIZIO' => [
                (object)['id' => $tipiServizioDb['S-G1']->id ?? 'S-G1', 'codice' => 'S-G1', 'nome' => 'GUARDIA D\'AVANZO LUNGA', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['S-G2']->id ?? 'S-G2', 'codice' => 'S-G2', 'nome' => 'GUARDIA D\'AVANZO CORTA', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['S-SA']->id ?? 'S-SA', 'codice' => 'S-SA', 'nome' => 'SORVEGLIANZA D\'AVANZO', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['S-CD1']->id ?? 'S-CD1', 'codice' => 'S-CD1', 'nome' => 'CONDUTTORE GUARDIA LUNGO', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['S-CD2']->id ?? 'S-CD2', 'codice' => 'S-CD2', 'nome' => 'CONDUTTORE PIAN DEL TERMINE CORTO', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['S-SG']->id ?? 'S-SG', 'codice' => 'S-SG', 'nome' => 'SOTTUFFICIALE DI GIORNATA', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['S-CG']->id ?? 'S-CG', 'codice' => 'S-CG', 'nome' => 'COMANDANTE DELLA GUARDIA', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['S-UI']->id ?? 'S-UI', 'codice' => 'S-UI', 'nome' => 'UFFICIALE DI ISPEZIONE', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['S-UP']->id ?? 'S-UP', 'codice' => 'S-UP', 'nome' => 'UFFICIALE DI PICCHETTO', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['S-AE']->id ?? 'S-AE', 'codice' => 'S-AE', 'nome' => 'AREE ESTERNE', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['S-ARM']->id ?? 'S-ARM', 'codice' => 'S-ARM', 'nome' => 'ARMIERE DI SERVIZIO', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['SI-GD']->id ?? 'SI-GD', 'codice' => 'SI-GD', 'nome' => 'SERVIZIO ISOLATO-GUARDIA DISTACCATA', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['SI']->id ?? 'SI', 'codice' => 'SI', 'nome' => 'SERVIZIO ISOLATO-CAPOMACCHINA/CAU', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['SI-VM']->id ?? 'SI-VM', 'codice' => 'SI-VM', 'nome' => 'VISITA MEDICA', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['S-PI']->id ?? 'S-PI', 'codice' => 'S-PI', 'nome' => 'PRONTO IMPIEGO', 'categoria' => 'SERVIZIO'],
                // SERVIZI TURNO SETTIMANALI
                (object)['id' => $tipiServizioDb['G-BTG']->id ?? 'G-BTG', 'codice' => 'G-BTG', 'nome' => 'GRADUATO DI BTG', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['NVA']->id ?? 'NVA', 'codice' => 'NVA', 'nome' => 'NUCLEO VIGILANZA ARMATA D\'AVANZO', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['CG']->id ?? 'CG', 'codice' => 'CG', 'nome' => 'CONDUTTORE GUARDIA', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['NS-DA']->id ?? 'NS-DA', 'codice' => 'NS-DA', 'nome' => 'NUCLEO SORV. D\' AVANZO 07:30 - 17:00', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['PDT']->id ?? 'PDT', 'codice' => 'PDT', 'nome' => 'VIGILANZA PIAN DEL TERMINE', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['AA']->id ?? 'AA', 'codice' => 'AA', 'nome' => 'ADDETTO ANTINCENDIO', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['VS-CETLI']->id ?? 'VS-CETLI', 'codice' => 'VS-CETLI', 'nome' => 'VIGILANZA SETTIMANALE CETLI', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['CORR']->id ?? 'CORR', 'codice' => 'CORR', 'nome' => 'CORRIERE', 'categoria' => 'SERVIZIO'],
                (object)['id' => $tipiServizioDb['NDI']->id ?? 'NDI', 'codice' => 'NDI', 'nome' => 'NUCLEO DIFESA IMMEDIATA', 'categoria' => 'SERVIZIO']
            ],
            'OPERAZIONE' => [
                (object)['id' => $tipiServizioDb['TO']->id ?? 'TO', 'codice' => 'TO', 'nome' => 'TEATRO OPERATIVO', 'categoria' => 'OPERAZIONE']
            ],
            'ADD./APP./CATTEDRE' => [
                (object)['id' => $tipiServizioDb['APS1']->id ?? 'APS1', 'codice' => 'APS1', 'nome' => 'PRELIEVI', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['APS2']->id ?? 'APS2', 'codice' => 'APS2', 'nome' => 'VACCINI', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['APS3']->id ?? 'APS3', 'codice' => 'APS3', 'nome' => 'ECG', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['APS4']->id ?? 'APS4', 'codice' => 'APS4', 'nome' => 'IDONEITA', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['AL-ELIX']->id ?? 'AL-ELIX', 'codice' => 'AL-ELIX', 'nome' => 'ELITRASPORTO', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['AL-MCM']->id ?? 'AL-MCM', 'codice' => 'AL-MCM', 'nome' => 'MCM', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['AL-BLS']->id ?? 'AL-BLS', 'codice' => 'AL-BLS', 'nome' => 'BLS', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['AL-CIED']->id ?? 'AL-CIED', 'codice' => 'AL-CIED', 'nome' => 'C-IED', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['AL-SM']->id ?? 'AL-SM', 'codice' => 'AL-SM', 'nome' => 'STRESS MANAGEMENT', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['AL-RM']->id ?? 'AL-RM', 'codice' => 'AL-RM', 'nome' => 'RAPPORTO MEDIA', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['AL-RSPP']->id ?? 'AL-RSPP', 'codice' => 'AL-RSPP', 'nome' => 'RSPP', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['AL-LEG']->id ?? 'AL-LEG', 'codice' => 'AL-LEG', 'nome' => 'ASPETTI LEGALI', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['AL-SEA']->id ?? 'AL-SEA', 'codice' => 'AL-SEA', 'nome' => 'SEXUAL EXPLOITATION AND ABUSE', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['AL-MI']->id ?? 'AL-MI', 'codice' => 'AL-MI', 'nome' => 'MALATTIE INFETTIVE', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['AL-PO']->id ?? 'AL-PO', 'codice' => 'AL-PO', 'nome' => 'PROPAGANDA OSTILE', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['AL-PI']->id ?? 'AL-PI', 'codice' => 'AL-PI', 'nome' => 'PUBBLICA INFORMAZIONE E COMUNICAZIONE', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['AP-M']->id ?? 'AP-M', 'codice' => 'AP-M', 'nome' => 'MANTENIMENTO', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['AP-A']->id ?? 'AP-A', 'codice' => 'AP-A', 'nome' => 'APPRONTAMENTO', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['AC-SW']->id ?? 'AC-SW', 'codice' => 'AC-SW', 'nome' => 'CORSO IN SMART WORKING', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['AC']->id ?? 'AC', 'codice' => 'AC', 'nome' => 'CORSO SERVIZIO ISOLATO', 'categoria' => 'ADD./APP./CATTEDRE'],
                (object)['id' => $tipiServizioDb['PEFO']->id ?? 'PEFO', 'codice' => 'PEFO', 'nome' => 'PEFO', 'categoria' => 'ADD./APP./CATTEDRE']
            ],
            'SUPP.CIS/EXE' => [
                (object)['id' => $tipiServizioDb['EXE']->id ?? 'EXE', 'codice' => 'EXE', 'nome' => 'ESERCITAZIONE', 'categoria' => 'SUPP.CIS/EXE']
            ]
        ];
        */ // Fine blocco commentato
        
        // Lista piatta per compatibilità
        $impegni = $tipiServizioDb;

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
            'compagnie',
            'plotoni', 
            'uffici',
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
        // Verifica permessi
        if (!auth()->user()->can('cpt.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per modificare il CPT'
            ], 403);
        }
        
        try {
            // Log per debug
            \Log::info('UpdateGiorno request', [
                'militare_id' => $militare->id,
                'request_data' => $request->all()
            ]);
        
        $request->validate([
            'pianificazione_mensile_id' => 'required|exists:pianificazioni_mensili,id',
            'giorno' => 'required|integer|min:1|max:31',
            'tipo_servizio_id' => 'nullable|integer|exists:tipi_servizio,id',
                'mese' => 'nullable|integer|min:1|max:12',
                'anno' => 'nullable|integer|min:2020|max:2030'
        ]);
        
        // Gestione esplicita del caso "nessun impegno"
            $tipoServizioId = $request->tipo_servizio_id ?: null;
            
            \Log::info('Update giorno - tipo_servizio_id ricevuto', [
                'militare_id' => $militare->id,
                'giorno' => $request->giorno,
                'tipo_servizio_id' => $tipoServizioId,
                'tipo' => gettype($tipoServizioId)
            ]);
            
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
        
        // SINCRONIZZAZIONE BIDIREZIONALE: CPT -> Turni
        $this->sincronizzaCptVersoTurni($militare, $anno, $mese, $request->giorno, $tipoServizioId);
        
            return response()->json([
                'success' => true,
                'pianificazione' => $pianificazione->load(['tipoServizio', 'tipoServizio.codiceGerarchia'])
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Errore updateGiorno', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nel salvataggio: ' . $e->getMessage()
            ], 200); // Cambio da 500 a 200 per garantire che il JSON venga processato
        }
    }

    public function updateGiorniRange(Request $request, Militare $militare)
    {
        // Verifica permessi
        if (!auth()->user()->can('cpt.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per modificare il CPT'
            ], 403);
        }
        
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
                // Il frontend invia l'ID, quindi convertiamo in numero
                $tipoServizioId = is_numeric($request->tipo_servizio_id) ? (int)$request->tipo_servizio_id : null;
                
                // Verifica che il tipo servizio esista
                if ($tipoServizioId) {
                    $tipoServizio = TipoServizio::find($tipoServizioId);
                    if (!$tipoServizio) {
                        $tipoServizioId = null;
                    }
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
                
                $pianificazioni[] = $pianificazione->load(['tipoServizio', 'tipoServizio.codiceGerarchia']);
                
                // SINCRONIZZAZIONE BIDIREZIONALE: CPT -> Turni
                $this->sincronizzaCptVersoTurni($militare, $giornoData['anno'], $giornoData['mese'], $giornoData['giorno'], $tipoServizioId);
            }
        
        return response()->json([
            'success' => true,
                'pianificazioni' => $pianificazioni
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Errore updateGiorniRange', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nel salvataggio: ' . $e->getMessage()
            ], 200); // Cambio da 500 a 200 per garantire che il JSON venga processato
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
            'patenti',
            'pianificazioniGiornaliere' => function($query) use ($pianificazioneMensile) {
                $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                      ->with(['tipoServizio', 'tipoServizio.codiceGerarchia']);
            }
        ]);

        // Applica filtri (stessi della vista index)
        if ($request->filled('compagnia')) {
            $militariQuery->where('compagnia', $request->compagnia);
        }

        if ($request->filled('grado_id')) {
            $militariQuery->where('grado_id', $request->grado_id);
        }

        if ($request->filled('plotone_id')) {
            $militariQuery->where('plotone_id', $request->plotone_id);
        }

        if ($request->filled('patente')) {
            $militariQuery->whereHas('patenti', function($q) use ($request) {
                $q->where('categoria', $request->patente);
            });
        }

        // FILTRO APPRONTAMENTI DISABILITATO - Tabella militare_approntamenti rimossa
        // if ($request->filled('approntamento_id')) {
        //     if ($request->approntamento_id === 'libero') {
        //         $militariQuery->doesntHave('approntamenti');
        //     } else {
        //         $militariQuery->whereHas('approntamenti', function($query) use ($request) {
        //             $query->where('approntamento_id', $request->approntamento_id);
        //         });
        //     }
        // }

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

        // Filtro stato_impegno per oggi (PRESENTI = libero, ASSENTI = impegnato)
        if ($request->filled('stato_impegno')) {
            $oggi = Carbon::today();
            
            if ($request->stato_impegno === 'libero') {
                // PRESENTI: Militari SENZA impegno per oggi
                $militariQuery->where(function($q) use ($pianificazioneMensile, $oggi) {
                    // O non hanno pianificazioni per oggi
                    $q->whereDoesntHave('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $oggi) {
                        $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                              ->where('giorno', $oggi->day);
                    })
                    // O hanno pianificazione ma senza tipo_servizio_id
                    ->orWhereHas('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $oggi) {
                        $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                              ->where('giorno', $oggi->day)
                              ->whereNull('tipo_servizio_id');
                    });
                });
            } elseif ($request->stato_impegno === 'impegnato') {
                // ASSENTI: Militari CON impegno per oggi
                $militariQuery->whereHas('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $oggi) {
                    $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                          ->where('giorno', $oggi->day)
                          ->whereNotNull('tipo_servizio_id');
                });
            }
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
        $headers = ['COMPAGNIA', 'GRADO', 'COGNOME', 'NOME', 'PLOTONE', 'PATENTE', 'APPRONTAMENTO'];
        
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
            $sheet->setCellValue('A' . $row, $militare->compagnia ? $militare->compagnia . 'a' : '');
            $sheet->setCellValue('B' . $row, $militare->grado->sigla ?? '');  // Usa sigla invece di nome
            $sheet->setCellValue('C' . $row, $militare->cognome);
            $sheet->setCellValue('D' . $row, $militare->nome);
            $sheet->setCellValue('E' . $row, str_replace(['° Plotone', 'Plotone'], ['°', ''], $militare->plotone->nome ?? ''));
            
            // Patenti - mostra tutte le patenti separate da spazio
            $patenti = $militare->patenti->pluck('categoria')->toArray();
            $sheet->setCellValue('F' . $row, !empty($patenti) ? implode(' ', $patenti) : '');
            
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
        
        // Imposta larghezze colonne (aumentate per evitare troncamenti)
        $sheet->getColumnDimension('A')->setWidth(12); // COMPAGNIA
        $sheet->getColumnDimension('B')->setWidth(20); // GRADO (sigla)
        $sheet->getColumnDimension('C')->setWidth(25); // COGNOME
        $sheet->getColumnDimension('D')->setWidth(20); // NOME
        $sheet->getColumnDimension('E')->setWidth(15); // PLOTONE
        $sheet->getColumnDimension('F')->setWidth(15); // PATENTE
        $sheet->getColumnDimension('G')->setWidth(25); // APPRONTAMENTO
        
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
    
    /**
     * Sincronizza un'assegnazione dal CPT verso i Turni
     * Se il tipo servizio CPT corrisponde a un servizio turno, crea/aggiorna l'assegnazione turno
     * 
     * @param Militare $militare
     * @param int $anno
     * @param int $mese
     * @param int $giorno
     * @param int|null $tipoServizioId
     * @return void
     */
    private function sincronizzaCptVersoTurni(Militare $militare, int $anno, int $mese, int $giorno, ?int $tipoServizioId)
    {
        try {
            \Log::info('========= INIZIO SINCRONIZZAZIONE CPT -> TURNI =========', [
                'militare_id' => $militare->id,
                'militare_nome' => $militare->cognome . ' ' . $militare->nome,
                'anno' => $anno,
                'mese' => $mese,
                'giorno' => $giorno,
                'tipo_servizio_id' => $tipoServizioId
            ]);
            
            $dataServizio = Carbon::createFromDate($anno, $mese, $giorno);
            
            // Se tipoServizioId è null, rimuovi l'eventuale assegnazione turno
            if (!$tipoServizioId) {
                AssegnazioneTurno::where('militare_id', $militare->id)
                    ->whereDate('data_servizio', $dataServizio->format('Y-m-d'))
                    ->delete();
                
                \Log::info('Rimossa assegnazione turno per CPT vuoto', [
                    'militare_id' => $militare->id,
                    'data' => $dataServizio->format('Y-m-d')
                ]);
                return;
            }
            
            // Trova il tipo servizio CPT
            $tipoServizio = TipoServizio::find($tipoServizioId);
            if (!$tipoServizio) {
                return;
            }
            
            // Cerca se esiste un servizio turno corrispondente (tramite sigla_cpt)
            $servizioTurno = ServizioTurno::where('sigla_cpt', $tipoServizio->codice)
                ->where('attivo', true)
                ->first();
            
            if (!$servizioTurno) {
                // Non è un servizio turno, non fare nulla
                \Log::info('Tipo servizio CPT non corrisponde a servizio turno', [
                    'codice_cpt' => $tipoServizio->codice
                ]);
                return;
            }
            
            // Trova o crea il turno settimanale per quella data
            $turno = TurnoSettimanale::createForDate($dataServizio);
            
            // Calcola il prossimo slot disponibile per questo servizio/data
            $maxSlot = AssegnazioneTurno::where('turno_settimanale_id', $turno->id)
                ->where('servizio_turno_id', $servizioTurno->id)
                ->whereDate('data_servizio', $dataServizio->format('Y-m-d'))
                ->max('posizione');
            
            $nuovoSlot = ($maxSlot ?? 0) + 1;
            
            // Verifica se il militare è già assegnato a questo servizio in questa data
            $assegnazioneEsistente = AssegnazioneTurno::where('militare_id', $militare->id)
                ->where('turno_settimanale_id', $turno->id)
                ->where('servizio_turno_id', $servizioTurno->id)
                ->whereDate('data_servizio', $dataServizio->format('Y-m-d'))
                ->first();
            
            if ($assegnazioneEsistente) {
                \Log::info('Assegnazione turno già esistente', [
                    'assegnazione_id' => $assegnazioneEsistente->id
                ]);
                return;
            }
            
            // Verifica se il militare ha già un altro servizio turno in quella data
            $altroServizio = AssegnazioneTurno::where('militare_id', $militare->id)
                ->whereDate('data_servizio', $dataServizio->format('Y-m-d'))
                ->where('servizio_turno_id', '!=', $servizioTurno->id)
                ->first();
            
            if ($altroServizio) {
                // Rimuovi il vecchio servizio turno
                $altroServizio->delete();
                \Log::info('Rimosso vecchio servizio turno per sincronizzazione CPT', [
                    'militare_id' => $militare->id,
                    'data' => $dataServizio->format('Y-m-d'),
                    'vecchio_servizio' => $altroServizio->servizio_turno_id
                ]);
            }
            
            // Crea la nuova assegnazione turno
            AssegnazioneTurno::create([
                'turno_settimanale_id' => $turno->id,
                'servizio_turno_id' => $servizioTurno->id,
                'militare_id' => $militare->id,
                'data_servizio' => $dataServizio,
                'giorno_settimana' => strtoupper($dataServizio->locale('it')->dayName),
                'posizione' => $nuovoSlot,
                'sincronizzato_cpt' => true,
            ]);
            
            \Log::info('Creata assegnazione turno da CPT', [
                'militare_id' => $militare->id,
                'servizio_turno_id' => $servizioTurno->id,
                'data' => $dataServizio->format('Y-m-d'),
                'slot' => $nuovoSlot
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Errore sincronizzazione CPT -> Turni', [
                'error' => $e->getMessage(),
                'militare_id' => $militare->id,
                'data' => $dataServizio->format('Y-m-d')
            ]);
            // Non propaghiamo l'errore per non bloccare il salvataggio CPT
        }
    }
}
