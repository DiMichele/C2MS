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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Services\ExcelStyleService;
use App\Services\AuditService;
use App\Services\PrenotazioneApprontamentoService;
use App\Models\PrenotazioneApprontamento;
use Illuminate\Validation\ValidationException;

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
        // ARCHITETTURA MULTI-TENANCY: OrganizationalUnitScope filtra automaticamente
        // per activeUnitId() (incluso per admin). CompagniaScope è mantenuto per legacy.
        //
        // Lo scope withVisibilityFlags() aggiunge is_owner e is_acquired calcolati
        // direttamente in SQL per evitare N+1 nelle liste.
        $militariQuery = Militare::withVisibilityFlags()
            ->with([
                'grado',
                'plotone',
                'polo',
                'mansione',
                'ruolo', 
                'scadenzaApprontamento',
                'teatriOperativi',
                'patenti',
                'organizationalUnit', // Multi-tenancy
                'pianificazioniGiornaliere' => function($query) use ($pianificazioneMensile) {
                    $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                          ->with(['tipoServizio', 'tipoServizio.codiceGerarchia']);
                }
            ]);

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

        if ($request->filled('ufficio_id')) {
            $militariQuery->where('polo_id', $request->ufficio_id);
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

        // Filtro Teatro Operativo (usa la tabella teatro_operativo_militare)
        if ($request->filled('approntamento_id')) {
            if ($request->approntamento_id === 'libero') {
                // Militari NON assegnati a nessun teatro operativo
                $militariQuery->whereDoesntHave('teatriOperativi');
            } else {
                // Militari assegnati a un teatro operativo specifico
                // Usa una subquery diretta per maggiore affidabilità
                $militariQuery->whereIn('id', function($query) use ($request) {
                    $query->select('militare_id')
                          ->from('teatro_operativo_militare')
                          ->where('teatro_operativo_id', $request->approntamento_id);
                });
            }
        }

        // Filtro combinato impegno + giorno
        if ($request->filled('impegno') && $request->filled('giorno')) {
            // Se sono specificati ENTRAMBI, filtra per impegno in quel giorno specifico
            $giornoFiltro = $request->giorno;
            if ($request->impegno === 'libero') {
                // Militari senza impegno nel giorno specificato
                $militariQuery->where(function($q) use ($pianificazioneMensile, $giornoFiltro) {
                    $q->whereDoesntHave('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $giornoFiltro) {
                        $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                              ->where('giorno', $giornoFiltro);
                    })
                    ->orWhereHas('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $giornoFiltro) {
                        $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                              ->where('giorno', $giornoFiltro)
                              ->whereNull('tipo_servizio_id');
                    });
                });
            } else {
                // Militari con l'impegno specifico nel giorno specificato
                $militariQuery->whereHas('pianificazioniGiornaliere', function($query) use ($request, $pianificazioneMensile, $giornoFiltro) {
                    $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                          ->where('giorno', $giornoFiltro)
                          ->whereHas('tipoServizio', function($q) use ($request) {
                              $q->where('codice', $request->impegno);
                          });
                });
            }
        } elseif ($request->filled('impegno')) {
            // Solo filtro impegno (qualsiasi giorno del mese)
            if ($request->impegno === 'libero') {
                // Filtra militari senza impegni nel mese corrente
                $militariQuery->whereDoesntHave('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile) {
                    $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                          ->whereNotNull('tipo_servizio_id');
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
        // NOTA: Se specificato solo 'giorno' senza 'impegno', mostra tutti i militari
        // (il giorno viene usato solo in combinazione con impegno)

        // Filtro disponibile per oggi (SI = libero o presente, NO = assente)
        if ($request->filled('disponibile')) {
            $oggi = Carbon::today();
            
            if ($request->disponibile === 'si') {
                // DISPONIBILE (Si): Militari liberi o con impegno che li rende presenti
                $militariQuery->where(function($q) use ($pianificazioneMensile, $oggi) {
                    // Non hanno pianificazioni per oggi
                    $q->whereDoesntHave('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $oggi) {
                        $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                              ->where('giorno', $oggi->day);
                    })
                    // O hanno pianificazione ma senza tipo_servizio_id
                    ->orWhereHas('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $oggi) {
                        $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                              ->where('giorno', $oggi->day)
                              ->whereNull('tipo_servizio_id');
                    })
                    // O hanno impegno che li rende presenti (PRESENTE_SERVIZIO o DISPONIBILE)
                    ->orWhereHas('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $oggi) {
                        $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                              ->where('giorno', $oggi->day)
                              ->whereNotNull('tipo_servizio_id')
                              ->whereHas('tipoServizio.codiceGerarchia', function($q) {
                                  $q->whereIn('impiego', ['PRESENTE_SERVIZIO', 'DISPONIBILE']);
                              });
                    });
                });
            } elseif ($request->disponibile === 'no') {
                // NON DISPONIBILE (No): Militari con impegno che li rende assenti
                $militariQuery->whereHas('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $oggi) {
                    $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                          ->where('giorno', $oggi->day)
                          ->whereNotNull('tipo_servizio_id')
                          ->whereHas('tipoServizio.codiceGerarchia', function($q) {
                              $q->where('impiego', 'NON_DISPONIBILE');
                          });
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
        $gradi = \App\Models\Grado::orderBy('ordine', 'desc')->get(); // Ordine 90 = COL (più alto), ordine 10 = SOL (più basso)
        
        // Carica compagnie dal database
        $compagnie = \App\Models\Compagnia::orderBy('nome')->get();
        
        // Carica plotoni con la relazione alla compagnia
        $plotoni = \App\Models\Plotone::with('compagnia')->orderBy('nome')->get();
        
        $uffici = \App\Models\Polo::orderBy('nome')->get();
        $approntamenti = \App\Models\TeatroOperativo::attivi()->orderBy('nome')->get();
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
            'scadenzaApprontamento',
            'teatriOperativi',
            'scadenzePoligoni.tipoPoligono',
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
        // Verifica permessi generali
        if (!auth()->user()->can('cpt.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per modificare il CPT'
            ], 403);
        }
        
        try {
            // VERIFICA PERMESSI via Policy (single source of truth)
            // updateCpt verifica che sia owner E abbia permesso cpt.edit
            $this->authorize('updateCpt', $militare);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Non puoi modificare il CPT di questo militare. I militari acquisiti sono in sola lettura.'
            ], 403);
        }
        
        try {
        
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
        
        // Verifica se c'è una pianificazione esistente con prenotazione collegata
        $pianificazioneEsistente = PianificazioneGiornaliera::where([
            'militare_id' => $militare->id,
            'pianificazione_mensile_id' => $pianificazioneMensile->id,
            'giorno' => $request->giorno
        ])->first();
        
        // Se si sta rimuovendo il servizio e c'era una prenotazione collegata, elimina la prenotazione
        if (!$tipoServizioId && $pianificazioneEsistente && $pianificazioneEsistente->prenotazione_approntamento_id) {
            $prenotazione = PrenotazioneApprontamento::find($pianificazioneEsistente->prenotazione_approntamento_id);
            if ($prenotazione && $prenotazione->stato === 'prenotato') {
                $prenotazioneService = app(PrenotazioneApprontamentoService::class);
                $prenotazioneService->eliminaPrenotazione($prenotazione);
                \Log::info('Eliminata prenotazione collegata da CPT', [
                    'prenotazione_id' => $prenotazione->id,
                    'militare_id' => $militare->id
                ]);
            }
        }

        $unitId = activeUnitId();
        // withoutGlobalScope: trova il record per (militare, mese, giorno) anche se organizational_unit_id era null
        $pianificazione = PianificazioneGiornaliera::withoutGlobalScope(OrganizationalUnitScope::class)
            ->updateOrCreate(
                [
                    'militare_id' => $militare->id,
                    'pianificazione_mensile_id' => $pianificazioneMensile->id,
                    'giorno' => $request->giorno
                ],
                [
                    'tipo_servizio_id' => $tipoServizioId,
                    'organizational_unit_id' => $unitId,
                ]
            );
        
        $pianificazione->refresh();
        
        // SINCRONIZZAZIONE BIDIREZIONALE: CPT -> Turni
        $this->sincronizzaCptVersoTurni($militare, $anno, $mese, $request->giorno, $tipoServizioId);
        
            try {
                AuditService::log(
                    'update',
                    "Aggiornato CPT giorno {$request->giorno}/{$mese}/{$anno} per {$militare->cognome} {$militare->nome}",
                    $militare,
                    ['giorno' => $request->giorno, 'mese' => $mese, 'anno' => $anno, 'tipo_servizio_id' => $tipoServizioId]
                );
            } catch (\Throwable $auditE) {
                \Log::warning('Audit log skip in updateGiorno', ['error' => $auditE->getMessage()]);
            }
        
            return response()->json([
                'success' => true,
                'pianificazione' => $pianificazione->load(['tipoServizio', 'tipoServizio.codiceGerarchia'])
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first() ?? 'Dati non validi.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Errore updateGiorno', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nel salvataggio: ' . $e->getMessage()
            ], 200);
        }
    }

    public function updateGiorniRange(Request $request, Militare $militare)
    {
        // Verifica permessi generali
        if (!auth()->user()->can('cpt.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per modificare il CPT'
            ], 403);
        }
        
        try {
            // VERIFICA PERMESSI via Policy (single source of truth)
            $this->authorize('updateCpt', $militare);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Non puoi modificare il CPT di questo militare. I militari acquisiti sono in sola lettura.'
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
            $unitId = activeUnitId();
            
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
                
                $pianificazione = PianificazioneGiornaliera::withoutGlobalScope(OrganizationalUnitScope::class)
                    ->updateOrCreate(
                        [
                            'militare_id' => $militare->id,
                            'pianificazione_mensile_id' => $pianificazioneMensile->id,
                            'giorno' => $giornoData['giorno']
                        ],
                        [
                            'tipo_servizio_id' => $tipoServizioId,
                            'organizational_unit_id' => $unitId,
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
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first() ?? 'Dati non validi.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Errore updateGiorniRange', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nel salvataggio: ' . $e->getMessage()
            ], 200);
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
            'scadenzaApprontamento',
            'teatriOperativi',
            'patenti',
            'pianificazioniGiornaliere' => function($query) use ($pianificazioneMensile) {
                $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                      ->with(['tipoServizio', 'tipoServizio.codiceGerarchia']);
            }
        ]);

        // Applica filtri (IDENTICI alla vista index per coerenza export)
        if ($request->filled('compagnia')) {
            $militariQuery->where('compagnia_id', $request->compagnia);
        }

        if ($request->filled('grado_id')) {
            $militariQuery->where('grado_id', $request->grado_id);
        }

        if ($request->filled('plotone_id')) {
            $militariQuery->where('plotone_id', $request->plotone_id);
        }

        if ($request->filled('ufficio_id')) {
            $militariQuery->where('polo_id', $request->ufficio_id);
        }

        if ($request->filled('patente')) {
            $militariQuery->whereHas('patenti', function($q) use ($request) {
                $q->where('categoria', $request->patente);
            });
        }

        // Filtro compleanno (identico alla vista index)
        if ($request->filled('compleanno')) {
            $oggi = now();
            
            switch ($request->compleanno) {
                case 'oggi':
                    $militariQuery->whereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$oggi->day, $oggi->month]);
                    break;
                    
                case 'ultimi_2':
                    // Ultimi 2 giorni incluso oggi
                    $militariQuery->where(function($q) use ($oggi) {
                        for ($i = 0; $i <= 2; $i++) {
                            $giorno = $oggi->copy()->subDays($i);
                            $q->orWhereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$giorno->day, $giorno->month]);
                        }
                    });
                    break;
                    
                case 'prossimi_2':
                    // Prossimi 2 giorni incluso oggi
                    $militariQuery->where(function($q) use ($oggi) {
                        for ($i = 0; $i <= 2; $i++) {
                            $giorno = $oggi->copy()->addDays($i);
                            $q->orWhereRaw('DAY(data_nascita) = ? AND MONTH(data_nascita) = ?', [$giorno->day, $giorno->month]);
                        }
                    });
                    break;
            }
        }

        // Filtro Teatro Operativo (usa la tabella teatro_operativo_militare)
        if ($request->filled('approntamento_id')) {
            if ($request->approntamento_id === 'libero') {
                // Militari NON assegnati a nessun teatro operativo
                $militariQuery->whereDoesntHave('teatriOperativi');
            } else {
                // Militari assegnati a un teatro operativo specifico
                // Usa una subquery diretta per maggiore affidabilità
                $militariQuery->whereIn('id', function($query) use ($request) {
                    $query->select('militare_id')
                          ->from('teatro_operativo_militare')
                          ->where('teatro_operativo_id', $request->approntamento_id);
                });
            }
        }

        // Filtro combinato impegno + giorno (stesso della vista index)
        if ($request->filled('impegno') && $request->filled('giorno')) {
            $giornoFiltro = $request->giorno;
            if ($request->impegno === 'libero') {
                $militariQuery->where(function($q) use ($pianificazioneMensile, $giornoFiltro) {
                    $q->whereDoesntHave('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $giornoFiltro) {
                        $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                              ->where('giorno', $giornoFiltro);
                    })
                    ->orWhereHas('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $giornoFiltro) {
                        $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                              ->where('giorno', $giornoFiltro)
                              ->whereNull('tipo_servizio_id');
                    });
                });
            } else {
                $militariQuery->whereHas('pianificazioniGiornaliere', function($query) use ($request, $pianificazioneMensile, $giornoFiltro) {
                    $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                          ->where('giorno', $giornoFiltro)
                          ->whereHas('tipoServizio', function($q) use ($request) {
                              $q->where('codice', $request->impegno);
                          });
                });
            }
        } elseif ($request->filled('impegno')) {
            if ($request->impegno === 'libero') {
                $militariQuery->whereDoesntHave('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile) {
                    $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                          ->whereNotNull('tipo_servizio_id');
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

        // Filtro stato_impegno per oggi
        // Filtro disponibile per oggi (SI = libero o presente, NO = assente)
        if ($request->filled('disponibile')) {
            $oggi = Carbon::today();
            
            if ($request->disponibile === 'si') {
                $militariQuery->where(function($q) use ($pianificazioneMensile, $oggi) {
                    $q->whereDoesntHave('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $oggi) {
                        $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                              ->where('giorno', $oggi->day);
                    })
                    ->orWhereHas('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $oggi) {
                        $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                              ->where('giorno', $oggi->day)
                              ->whereNull('tipo_servizio_id');
                    })
                    ->orWhereHas('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $oggi) {
                        $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                              ->where('giorno', $oggi->day)
                              ->whereNotNull('tipo_servizio_id')
                              ->whereHas('tipoServizio.codiceGerarchia', function($q) {
                                  $q->whereIn('impiego', ['PRESENTE_SERVIZIO', 'DISPONIBILE']);
                              });
                    });
                });
            } elseif ($request->disponibile === 'no') {
                $militariQuery->whereHas('pianificazioniGiornaliere', function($query) use ($pianificazioneMensile, $oggi) {
                    $query->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                          ->where('giorno', $oggi->day)
                          ->whereNotNull('tipo_servizio_id')
                          ->whereHas('tipoServizio.codiceGerarchia', function($q) {
                              $q->where('impiego', 'NON_DISPONIBILE');
                          });
                });
            }
        }

        $militari = $militariQuery->orderByGradoENome()->get();

        // Usa il servizio per stili Excel
        $excelService = new ExcelStyleService();
        
        // Crea il file Excel usando PhpSpreadsheet
        $spreadsheet = $excelService->createSpreadsheet('CPT - Controllo Presenze Turni');
        $sheet = $spreadsheet->getActiveSheet();
        
        // Nomi dei mesi
        $nomiMesi = ['', 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
                    'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
        
        // Nome del foglio dinamico basato su mese e anno correnti
        $sheet->setTitle($nomiMesi[$mese] . ' ' . $anno);
        
        // Calcola giorni del mese
        $giorniMese = Carbon::createFromDate($anno, $mese, 1)->daysInMonth;
        
        // SINGLE SOURCE OF TRUTH: Colonne fisse identiche alla vista web
        // Se modifichi qui, aggiorna anche resources/views/pianificazione/index.blade.php
        $colonneBase = [
            'compagnia' => ['header' => 'Compagnia', 'width' => 12],
            'grado' => ['header' => 'Grado', 'width' => 14],
            'cognome' => ['header' => 'Cognome', 'width' => 18],
            'nome' => ['header' => 'Nome', 'width' => 16],
            'plotone' => ['header' => 'Plotone', 'width' => 14],
            'ufficio' => ['header' => 'Ufficio', 'width' => 20],
            'patente' => ['header' => 'Patente', 'width' => 12],
            'teatro_operativo' => ['header' => 'Teatro Operativo', 'width' => 18],
        ];
        
        $numColonneBase = count($colonneBase);
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($numColonneBase + $giorniMese);
        
        // Titolo principale (riga 1) - Solo mese e anno
        $titolo = $nomiMesi[$mese] . ' ' . $anno;
        $excelService->applyTitleStyle($sheet, 'A1:' . $lastColumn . '1', $titolo);

        // Header delle colonne (riga 2) - Identici alla pagina web
        $headers = array_column($colonneBase, 'header');
        
        // Aggiungi giorni del mese con giorno della settimana (come nella pagina web)
        $giorniCompletiItaliani = ['DOMENICA', 'LUNEDI', 'MARTEDI', 'MERCOLEDI', 'GIOVEDI', 'VENERDI', 'SABATO'];
        for ($i = 1; $i <= $giorniMese; $i++) {
            $dataGiorno = Carbon::createFromDate($anno, $mese, $i);
            $giornoSettimana = $giorniCompletiItaliani[$dataGiorno->dayOfWeek];
            $headers[] = $giornoSettimana . "\n" . str_pad($i, 2, '0', STR_PAD_LEFT) . '/' . str_pad($mese, 2, '0', STR_PAD_LEFT);
        }

        // Scrivi gli header
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '2', $header);
            $col++;
        }
        $excelService->applyHeaderStyle($sheet, 'A2:' . $lastColumn . '2');
        $sheet->getRowDimension('2')->setRowHeight(35);
        // Wrap text per header con newline (giorni)
        $sheet->getStyle('A2:' . $lastColumn . '2')->getAlignment()->setWrapText(true);
        
        // Colora le colonne weekend/festività nell'header
        $festivitaPerAnno = $this->getFestivitaPerAnno($anno);
        for ($giorno = 1; $giorno <= $giorniMese; $giorno++) {
            $dataGiorno = Carbon::createFromDate($anno, $mese, $giorno);
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($numColonneBase + $giorno);
            
            $dataFormattata = $dataGiorno->format('m-d');
            $isWeekend = $dataGiorno->isWeekend();
            $isHoliday = isset($festivitaPerAnno[$dataFormattata]);
            
            if ($isWeekend || $isHoliday) {
                $sheet->getStyle($colLetter . '2')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DC3545']
                    ]
                ]);
            }
        }

        // Dati dei militari (inizia dalla riga 3)
        $row = 3;
        foreach ($militari as $militare) {
            // Compagnia - nome per esteso
            $compagniaValue = '';
            if ($militare->compagnia_id) {
                $comp = $militare->compagnia;
                $compagniaValue = $comp ? ($comp->numero ?? $comp->nome) : '';
            }
            
            // Colonne base (identiche alla pagina web)
            $sheet->setCellValue('A' . $row, $compagniaValue);
            $sheet->setCellValue('B' . $row, $militare->grado->nome ?? ($militare->grado->sigla ?? '')); // Nome grado per esteso
            $sheet->setCellValue('C' . $row, $militare->cognome); // Come nella pagina web, non in maiuscolo
            $sheet->setCellValue('D' . $row, $militare->nome); // Come nella pagina web, non in maiuscolo
            $sheet->setCellValue('E' . $row, $militare->plotone->nome ?? '-'); // Nome plotone per esteso
            $sheet->setCellValue('F' . $row, $militare->polo->nome ?? '-'); // Ufficio (era mancante)
            
            // Patenti - mostra tutte le patenti separate da spazio
            $patenti = $militare->patenti->pluck('categoria')->toArray();
            $sheet->setCellValue('G' . $row, !empty($patenti) ? implode(' ', $patenti) : '-');
            
            // Teatro Operativo (era "APPRONT.")
            $sheet->setCellValue('H' . $row, $militare->scadenzaApprontamento->teatro_operativo ?? ($militare->getTeatroOperativoCodice() ?? '-'));

            // Aggiungi i dati di pianificazione per ogni giorno
            $pianificazioniPerGiorno = [];
            foreach ($militare->pianificazioniGiornaliere as $pianificazione) {
                $pianificazioniPerGiorno[$pianificazione->giorno] = $pianificazione;
            }

            $colIndex = $numColonneBase + 1; // Inizia dopo le colonne base
            for ($giorno = 1; $giorno <= $giorniMese; $giorno++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $codice = '';
                $pianificazione = null;
                
                if (isset($pianificazioniPerGiorno[$giorno])) {
                    $pianificazione = $pianificazioniPerGiorno[$giorno];
                    $tipoServizio = $pianificazione->tipoServizio;
                    $codice = $tipoServizio->codice ?? '';
                }
                
                $sheet->setCellValue($colLetter . $row, $codice);
                
                // Applica colori CPT dal database se il codice esiste
                if ($codice && $pianificazione) {
                    $this->applicaColoreCPTDaDatabase($sheet, $colLetter . $row, $pianificazione);
                }
                
                $colIndex++;
            }
            
            $row++;
        }

        // Stile generale dei dati
        $dataRange = 'A3:' . $lastColumn . ($row - 1);
        if ($row > 3) {
            $excelService->applyDataStyle($sheet, $dataRange);
        }
        
        // AUTO-SIZE: Larghezza colonne automatica in base al contenuto più lungo
        // Colonne base (Compagnia, Grado, Cognome, Nome, Plotone, Ufficio, Patente, Teatro Operativo)
        $colLetter = 'A';
        foreach ($colonneBase as $config) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            $colLetter++;
        }
        
        // Giorni del mese - larghezza sufficiente per "MERCOLEDI" (il più lungo) + data
        for ($i = 0; $i < $giorniMese; $i++) {
            $colIndex = $numColonneBase + 1 + $i;
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setWidth(14); // Larghezza per contenere "MERCOLEDI" + margine
        }
        
        // Freeze header
        $excelService->freezeHeader($sheet, 2);
        
        // Data generazione
        $excelService->addGenerationInfo($sheet, $row + 1);

        // Prepara il download
        $fileName = 'CPT_' . $nomiMesi[$mese] . '_' . $anno . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'cpt_');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
    
    /**
     * Ottiene le festività per un anno specifico
     */
    private function getFestivitaPerAnno(int $anno): array
    {
        $festivitaPerAnno = [
            2025 => [
                '01-01' => 'Capodanno', '01-06' => 'Epifania', '04-20' => 'Pasqua',
                '04-21' => 'Lunedì dell\'Angelo', '04-25' => 'Festa della Liberazione',
                '05-01' => 'Festa del Lavoro', '06-02' => 'Festa della Repubblica',
                '08-15' => 'Ferragosto', '11-01' => 'Ognissanti',
                '12-08' => 'Immacolata Concezione', '12-25' => 'Natale', '12-26' => 'Santo Stefano'
            ],
            2026 => [
                '01-01' => 'Capodanno', '01-06' => 'Epifania', '04-05' => 'Pasqua',
                '04-06' => 'Lunedì dell\'Angelo', '04-25' => 'Festa della Liberazione',
                '05-01' => 'Festa del Lavoro', '06-02' => 'Festa della Repubblica',
                '08-15' => 'Ferragosto', '11-01' => 'Ognissanti',
                '12-08' => 'Immacolata Concezione', '12-25' => 'Natale', '12-26' => 'Santo Stefano'
            ]
        ];
        
        return $festivitaPerAnno[$anno] ?? [];
    }

    /**
     * Applica i colori CPT alla cella leggendo dal database (SINGLE SOURCE OF TRUTH)
     * Usa lo stesso ordine di priorità della vista web:
     * 1. colore_badge dalla codiceGerarchia
     * 2. colore_badge dal tipoServizio
     * 3. Mappa fallback hardcoded
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param string $cellAddress
     * @param \App\Models\PianificazioneGiornaliera $pianificazione
     */
    private function applicaColoreCPTDaDatabase($sheet, string $cellAddress, $pianificazione): void
    {
        $tipoServizio = $pianificazione->tipoServizio;
        if (!$tipoServizio) {
            return;
        }
        
        $codice = $tipoServizio->codice ?? '';
        
        // Priorità colori: gerarchia -> tipoServizio -> fallback
        $coloreBadge = null;
        
        // 1. Colore dalla gerarchia (codici_servizio_gerarchia)
        $gerarchia = $tipoServizio->codiceGerarchia;
        if ($gerarchia && !empty($gerarchia->colore_badge)) {
            $coloreBadge = $gerarchia->colore_badge;
        }
        
        // 2. Colore dal tipo servizio
        if (!$coloreBadge && !empty($tipoServizio->colore_badge)) {
            $coloreBadge = $tipoServizio->colore_badge;
        }
        
        // 3. Fallback hardcoded (identico alla vista web)
        if (!$coloreBadge) {
            $coloreBadge = $this->getColoreFallback($codice);
        }
        
        // Se non c'è colore, usa verde default
        if (!$coloreBadge) {
            $coloreBadge = '#00b050';
        }
        
        // Applica il colore
        $hexColor = ltrim($coloreBadge, '#');
        $textColor = $this->isColorChiaro($hexColor) ? '000000' : 'FFFFFF';
        
        $sheet->getStyle($cellAddress)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($hexColor);
        
        $sheet->getStyle($cellAddress)->getFont()->getColor()->setRGB($textColor);
        $sheet->getStyle($cellAddress)->getFont()->setBold(true);
        $sheet->getStyle($cellAddress)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
    
    /**
     * Mappa colori fallback per codici CPT (identica alla vista web)
     * Usata solo se il colore non è presente nel database
     * 
     * @param string $codice
     * @return string|null
     */
    private function getColoreFallback(string $codice): ?string
    {
        $mappaColori = [
            // ASSENTE - Giallo
            'LS' => '#ffff00', 'LO' => '#ffff00', 'LM' => '#ffff00',
            'P' => '#ffff00', 'TIR' => '#ffff00', 'TRAS' => '#ffff00',
            
            // PROVVEDIMENTI MEDICO SANITARI - Rosso
            'RMD' => '#ff0000', 'LC' => '#ff0000', 'IS' => '#ff0000',
            
            // SERVIZIO/PRESENTE - Verde
            'RIP' => '#00b050', 'S.I.' => '#00b050', 'SI' => '#00b050',
            'S-G1' => '#00b050', 'S-G2' => '#00b050', 'S-SA' => '#00b050',
            'S-CD1' => '#00b050', 'S-CD2' => '#00b050', 'S-CD3' => '#00b050', 'S-CD4' => '#00b050',
            'S-SG' => '#00b050', 'S-CG' => '#00b050', 'S-UI' => '#00b050', 'S-UP' => '#00b050',
            'S-AE' => '#00b050', 'S-ARM' => '#00b050', 'SI-GD' => '#00b050',
            'S-VM' => '#00b050', 'S-PI' => '#00b050',
            'G1' => '#00b050', 'G2' => '#00b050', 'CD2' => '#00b050',
            'PDT1' => '#00b050', 'PDT2' => '#00b050',
            'AE' => '#00b050', 'A-A' => '#00b050',
            'CETLI' => '#00b050', 'LCC' => '#00b050', 'CENTURIA' => '#00b050',
            'TIROCINIO' => '#00b050',
            'G-BTG' => '#00b050', 'NVA' => '#00b050', 'CG' => '#00b050',
            'NS-DA' => '#00b050', 'PDT' => '#00b050', 'AA' => '#00b050',
            'VS-CETLI' => '#00b050', 'CORR' => '#00b050', 'NDI' => '#00b050',
            'Cattedra' => '#00b050', 'CATTEDRA' => '#00b050', 'cattedra' => '#00b050',
            'APS1' => '#00b050', 'APS2' => '#00b050', 'APS3' => '#00b050', 'APS4' => '#00b050',
            'AL-ELIX' => '#00b050', 'AL-MCM' => '#00b050', 'AL-BLS' => '#00b050',
            'AL-CIED' => '#00b050', 'AL-SM' => '#00b050', 'AL-RM' => '#00b050',
            'AL-RSPP' => '#00b050', 'AL-LEG' => '#00b050', 'AL-SEA' => '#00b050',
            'AL-MI' => '#00b050', 'AL-PO' => '#00b050', 'AL-PI' => '#00b050',
            'AP-M' => '#00b050', 'AP-A' => '#00b050', 'AC-SW' => '#00b050',
            'AC' => '#00b050', 'PEFO' => '#00b050', 'EXE' => '#00b050',
            'SMO' => '#00b050', 'smo' => '#00b050',
            
            // OPERAZIONE/T.O. - Arancione
            'TO' => '#ffc000', 'T.O.' => '#ffc000', 'MCM' => '#ffc000', 'KOSOVO' => '#ffc000',
        ];
        
        return $mappaColori[$codice] ?? null;
    }
    
    /**
     * Determina se un colore è chiaro (per decidere se usare testo nero o bianco)
     * 
     * @param string $hexColor Colore esadecimale senza #
     * @return bool True se il colore è chiaro
     */
    private function isColorChiaro(string $hexColor): bool
    {
        $hexColor = str_pad($hexColor, 6, '0', STR_PAD_LEFT);
        
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));
        
        // Formula per luminosità percepita (stessa della vista web)
        $luminosita = ($r * 299 + $g * 587 + $b * 114) / 1000;
        
        return $luminosita > 128;
    }
    
    /**
     * DEPRECATO: Usa applicaColoreCPTDaDatabase invece
     * Mantenuto per retrocompatibilità
     */
    private function applicaColoreCPT($sheet, $cellAddress, $codice)
    {
        $colore = $this->getColoreFallback($codice);
        
        if ($colore) {
            $hexColor = ltrim($colore, '#');
            $textColor = $this->isColorChiaro($hexColor) ? '000000' : 'FFFFFF';
            
            $sheet->getStyle($cellAddress)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($hexColor);
            
            $sheet->getStyle($cellAddress)->getFont()->getColor()->setRGB($textColor);
            $sheet->getStyle($cellAddress)->getFont()->setBold(true);
            $sheet->getStyle($cellAddress)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
