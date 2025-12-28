<?php

namespace App\Http\Controllers;

use App\Models\TrasparenzaServizio;
use App\Models\Militare;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrasparenzaController extends Controller
{
    /**
     * Visualizza la tabella Trasparenza Servizi (SOLA LETTURA)
     * Dati provenienti da CPT e Turni Settimanali
     */
    public function index(Request $request)
    {
        $anno = $request->input('anno', Carbon::now()->year);
        $mese = $request->input('mese', Carbon::now()->month);
        
        // Calcola i totali annuali per ciascun militare
        $totaliAnnuali = $this->calcolaTotaliAnnuali($anno);
        
        // Festività italiane per anno
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
        ];
        
        $festivitaFisse = $festivitaPerAnno[$anno] ?? [];
        
        // Ottieni militari ordinati per compagnia, grado e cognome
        $militari = Militare::with(['grado', 'compagnia'])
            ->orderBy('compagnia_id')
            ->orderByGradoENome()
            ->get();
        
        // Calcola numero di giorni nel mese
        $giorniNelMese = Carbon::create($anno, $mese)->daysInMonth;
        
        // CARICA SOLO LE ASSEGNAZIONI TURNO (Turni Settimanali), non il CPT completo
        $dataInizio = Carbon::create($anno, $mese, 1)->startOfDay();
        $dataFine = Carbon::create($anno, $mese, $giorniNelMese)->endOfDay();
        
        $assegnazioniTurno = \App\Models\AssegnazioneTurno::whereBetween('data_servizio', [$dataInizio, $dataFine])
            ->with('servizioTurno')
            ->get()
            ->groupBy('militare_id');
        
        // Prepara i dati per la vista e mappa dei nomi servizi
        $datiMilitari = [];
        $mappaNomiServizi = [];
        
        foreach ($militari as $militare) {
            $assegnazioniMilitare = $assegnazioniTurno->get($militare->id, collect());
            
            $giorni = [];
            $giorniDettaglio = [];
            for ($g = 1; $g <= $giorniNelMese; $g++) {
                // Trova l'assegnazione per questo giorno
                $data = Carbon::create($anno, $mese, $g);
                $assegnazione = $assegnazioniMilitare->first(function($a) use ($data) {
                    return Carbon::parse($a->data_servizio)->isSameDay($data);
                });
                
                $codice = $assegnazione ? $assegnazione->servizioTurno?->codice : null;
                $nomeCompleto = $assegnazione ? $assegnazione->servizioTurno?->nome : null;
                
                $giorni[$g] = $codice;
                $giorniDettaglio[$g] = [
                    'codice' => $codice,
                    'nome' => $nomeCompleto
                ];
                
                // Aggiungi alla mappa se non esiste già
                if ($codice && $nomeCompleto && !isset($mappaNomiServizi[$codice])) {
                    $mappaNomiServizi[$codice] = $nomeCompleto;
                }
            }
            
            // Calcola totali
            $totali = $this->calcolaTotali($giorni, $anno, $mese);
            
            $datiMilitari[] = [
                'militare' => $militare,
                'giorni' => $giorni,
                'giorniDettaglio' => $giorniDettaglio,
                'totali' => $totali,
            ];
        }
        
        $nomiMesi = TrasparenzaServizio::nomiMesi();
        
        return view('trasparenza.index', compact(
            'datiMilitari',
            'anno',
            'mese',
            'giorniNelMese',
            'nomiMesi',
            'mappaNomiServizi',
            'festivitaFisse',
            'totaliAnnuali'
        ));
    }
    
    /**
     * Calcola i totali annuali per tutti i militari
     */
    private function calcolaTotaliAnnuali($anno)
    {
        $totali = [];
        
        // Per ogni mese dell'anno, calcola i servizi
        for ($mese = 1; $mese <= 12; $mese++) {
            $giorniNelMese = Carbon::create($anno, $mese)->daysInMonth;
            $dataInizio = Carbon::create($anno, $mese, 1)->startOfDay();
            $dataFine = Carbon::create($anno, $mese, $giorniNelMese)->endOfDay();
            
            // Carica le assegnazioni turno per questo mese
            $assegnazioniTurno = \App\Models\AssegnazioneTurno::whereBetween('data_servizio', [$dataInizio, $dataFine])
                ->with('servizioTurno')
                ->get();
            
            foreach ($assegnazioniTurno as $assegnazione) {
                $militareId = $assegnazione->militare_id;
                if (!isset($totali[$militareId])) {
                    $totali[$militareId] = [
                        'feriali' => 0,
                        'festivi' => 0,
                        'superfestivi' => 0,
                        'totale' => 0
                    ];
                }
                
                // Determina il tipo di giorno
                $data = Carbon::parse($assegnazione->data_servizio);
                $tipoGiorno = $this->determinaTipoGiorno($data);
                
                $totali[$militareId]['totale']++;
                if ($tipoGiorno === 'festivo') {
                    $totali[$militareId]['festivi']++;
                } elseif ($tipoGiorno === 'superfestivo') {
                    $totali[$militareId]['superfestivi']++;
                } else {
                    $totali[$militareId]['feriali']++;
                }
            }
        }
        
        return $totali;
    }


    /**
     * Calcola i totali per un militare
     */
    private function calcolaTotali($giorni, $anno, $mese)
    {
        $totali = [
            'feriali' => 0,
            'festivi' => 0,
            'superfestivi' => 0,
            'totale' => 0,
        ];
        
        foreach ($giorni as $giorno => $codice) {
            if ($codice && $codice !== '') {
                $totali['totale']++;
                
                // Determina il tipo di giorno
                $data = Carbon::create($anno, $mese, $giorno);
                $tipoGiorno = $this->determinaTipoGiorno($data);
                
                if ($tipoGiorno === 'festivo') {
                    $totali['festivi']++;
                } elseif ($tipoGiorno === 'superfestivo') {
                    $totali['superfestivi']++;
                } else {
                    $totali['feriali']++;
                }
            }
        }
        
        return $totali;
    }

    /**
     * Determina se un giorno è feriale, festivo o superfestivo
     */
    private function determinaTipoGiorno(Carbon $data)
    {
        // Superfestivi: Natale, Capodanno, Pasqua, Pasquetta, Ferragosto
        $superfestivi = [
            '01-01', // Capodanno
            '12-25', // Natale
            '12-26', // Santo Stefano
            '08-15', // Ferragosto
        ];
        
        $dataStr = $data->format('m-d');
        
        if (in_array($dataStr, $superfestivi)) {
            return 'superfestivo';
        }
        
        // Domenica o festivi nazionali
        $festivi = [
            '01-06', // Epifania
            '04-25', // Liberazione
            '05-01', // Festa del lavoro
            '06-02', // Festa della Repubblica
            '11-01', // Ognissanti
            '12-08', // Immacolata
        ];
        
        if ($data->dayOfWeek === 0 || in_array($dataStr, $festivi)) {
            return 'festivo';
        }
        
        return 'feriale';
    }

    /**
     * Export Excel
     */
    public function exportExcel(Request $request)
    {
        $anno = $request->input('anno', Carbon::now()->year);
        $mese = $request->input('mese', Carbon::now()->month);
        
        // Festività italiane per anno
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
        ];
        
        $festivitaFisse = $festivitaPerAnno[$anno] ?? [];
        
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Ottieni dati
            $militari = Militare::with(['grado', 'compagnia'])
                ->orderBy('compagnia_id')
                ->orderByGradoENome()
                ->get();
            
            $giorniNelMese = Carbon::create($anno, $mese)->daysInMonth;
            
            // Ottieni la pianificazione mensile attiva per anno/mese
            $pianificazioneMensile = \App\Models\PianificazioneMensile::where('anno', $anno)
                ->where('mese', $mese)
                ->where('stato', 'attiva')
                ->first();
            
            // CARICA SOLO LE ASSEGNAZIONI TURNO (Turni Settimanali), non il CPT completo
            $dataInizio = Carbon::create($anno, $mese, 1)->startOfDay();
            $dataFine = Carbon::create($anno, $mese, $giorniNelMese)->endOfDay();
            
            $assegnazioniTurno = \App\Models\AssegnazioneTurno::whereBetween('data_servizio', [$dataInizio, $dataFine])
                ->with('servizioTurno')
                ->get()
                ->groupBy('militare_id');
            
            // Stile header
            $headerStyle = [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0a2342']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];
            
            // Titolo
            $sheet->setCellValue('A1', 'TRASPARENZA SERVIZI ' . $anno);
            $sheet->mergeCells('A1:AU1');
            $sheet->getStyle('A1')->applyFromArray($headerStyle);
            
            // Mese
            $nomeMese = strtoupper(TrasparenzaServizio::nomiMesi()[$mese]);
            $sheet->setCellValue('D2', $nomeMese);
            $colonnaTotale = chr(68 + $giorniNelMese); // D + giorniNelMese
            $sheet->mergeCells('D2:' . chr(67 + $giorniNelMese) . '2');
            $sheet->getStyle('D2')->applyFromArray($headerStyle);
            
            // Header colonne fisse
            $sheet->setCellValue('A3', 'N.');
            $sheet->setCellValue('B3', 'COMPAGNIA');
            $sheet->setCellValue('C3', 'GRADO');
            $sheet->setCellValue('D3', 'COGNOME');
            $sheet->setCellValue('E3', 'NOME');
            $sheet->getStyle('A3:E3')->applyFromArray($headerStyle);
            
            // Header giorni
            $col = 'F';
            for ($g = 1; $g <= $giorniNelMese; $g++) {
                $sheet->setCellValue($col . '3', $g);
                $sheet->getStyle($col . '3')->applyFromArray($headerStyle);
                $col++;
            }
            
            // Header totali
            $sheet->setCellValue($col . '3', 'FERIALI');
            $sheet->getStyle($col . '3')->applyFromArray($headerStyle);
            $col++;
            $sheet->setCellValue($col . '3', 'FESTIVI');
            $sheet->getStyle($col . '3')->applyFromArray($headerStyle);
            $col++;
            $sheet->setCellValue($col . '3', 'SUPERFESTIVI');
            $sheet->getStyle($col . '3')->applyFromArray($headerStyle);
            
            // Dati militari            
            $row = 4;
            $num = 1;
            foreach ($militari as $militare) {
                $assegnazioniMilitare = $assegnazioniTurno->get($militare->id, collect());
                
                $sheet->setCellValue('A' . $row, $num++);
                $sheet->setCellValue('B' . $row, $militare->compagnia->nome ?? '');
                $sheet->setCellValue('C' . $row, $militare->grado->sigla ?? '');
                $sheet->setCellValue('D' . $row, strtoupper($militare->cognome));
                $sheet->setCellValue('E' . $row, strtoupper($militare->nome));
                
                // Giorni
                $col = 'F';
                $giorni = [];
                for ($g = 1; $g <= $giorniNelMese; $g++) {
                    // Trova l'assegnazione per questo giorno
                    $data = Carbon::create($anno, $mese, $g);
                    $assegnazione = $assegnazioniMilitare->first(function($a) use ($data) {
                        return Carbon::parse($a->data_servizio)->isSameDay($data);
                    });
                    
                    $codice = $assegnazione ? $assegnazione->servizioTurno?->codice : null;
                    $giorni[$g] = $codice;
                    $sheet->setCellValue($col . $row, $codice ?? '');
                    $sheet->getStyle($col . $row)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                    $col++;
                }
                
                // Totali
                $totali = $this->calcolaTotali($giorni, $anno, $mese);
                
                $sheet->setCellValue($col . $row, $totali['feriali']);
                $sheet->getStyle($col . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $col++;
                
                $sheet->setCellValue($col . $row, $totali['festivi']);
                $sheet->getStyle($col . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $col++;
                
                $sheet->setCellValue($col . $row, $totali['superfestivi']);
                $sheet->getStyle($col . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                
                // Bordi
                $sheet->getStyle('A' . $row . ':' . $col . $row)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                
                $row++;
            }
            
            // Larghezze colonne
            $sheet->getColumnDimension('A')->setWidth(8);   // N.
            $sheet->getColumnDimension('B')->setWidth(20);  // Compagnia
            $sheet->getColumnDimension('C')->setWidth(15);  // Grado
            $sheet->getColumnDimension('D')->setWidth(20);  // Cognome
            $sheet->getColumnDimension('E')->setWidth(20);  // Nome
            
            // Giorni e totali
            for ($c = 'F'; $c <= $col; $c++) {
                $sheet->getColumnDimension($c)->setWidth(12);
            }
            
            // Salva
            $filename = 'Trasparenza_Servizi_' . $nomeMese . '_' . $anno . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'trasparenza_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);
            
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Errore export Excel trasparenza', [
                'error' => $e->getMessage(),
                'anno' => $anno,
                'mese' => $mese,
            ]);
            
            return redirect()->back()->with('error', 'Errore durante l\'esportazione Excel.');
        }
    }
}
