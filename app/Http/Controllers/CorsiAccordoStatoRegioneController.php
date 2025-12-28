<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\ScadenzaMilitare;
use App\Models\ConfigurazioneCorsoSpp;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use Illuminate\Support\Facades\Log;

class CorsiAccordoStatoRegioneController extends Controller
{
    /**
     * Visualizza la pagina Corsi Accordo Stato Regione
     */
    public function index(Request $request)
    {
        // Recupera tutti i corsi attivi di tipo 'accordo_stato_regione' ordinati
        $corsi = ConfigurazioneCorsoSpp::attivi()
            ->perTipo('accordo_stato_regione')
            ->ordinati()
            ->get();
        
        // Ottieni tutti i militari con le loro scadenze SPP
        $militari = Militare::with(['scadenzeCorsiSpp.corso'])
            ->orderByGradoENome()
            ->get();
        
        // Calcola le scadenze per ogni militare
        $data = $militari->map(function ($militare) use ($corsi) {
            $row = [
                'militare' => $militare,
            ];
            
            // Per ogni corso, recupera la scadenza del militare
            foreach ($corsi as $corso) {
                $scadenzaCorso = $militare->scadenzeCorsiSpp->firstWhere('configurazione_corso_spp_id', $corso->id);
                
                if ($scadenzaCorso) {
                    $row[$corso->codice_corso] = $scadenzaCorso->calcolaScadenza();
                } else {
                    // Nessuna scadenza registrata per questo corso
                    $row[$corso->codice_corso] = [
                        'data_scadenza' => null,
                        'stato' => 'mancante',
                        'classe' => 'mancante',
                        'giorni_rimanenti' => null,
                        'data_conseguimento' => null,
                        'durata' => $corso->durata_anni,
                    ];
                }
            }
            
            return $row;
        });
        
        return view('scadenze.corsi-accordo-stato-regione', compact('data', 'corsi'));
    }
    
    /**
     * Aggiorna una singola scadenza via AJAX
     */
    public function updateSingola(Request $request, Militare $militare)
    {
        $request->validate([
            'corso_id' => 'required|exists:configurazione_corsi_spp,id',
            'data' => 'nullable|date',
        ]);
        
        $corsoId = $request->corso_id;
        $data = $request->data;
        
        // Trova o crea la scadenza per questo militare e corso
        $scadenzaCorso = \App\Models\ScadenzaCorsoSpp::updateOrCreate(
            [
                'militare_id' => $militare->id,
                'configurazione_corso_spp_id' => $corsoId,
            ],
            [
                'data_conseguimento' => $data,
            ]
        );
        
        // Calcola e restituisci la nuova scadenza
        $scadenzaCalcolata = $scadenzaCorso->calcolaScadenza();
        
        return response()->json([
            'success' => true,
            'scadenza' => $scadenzaCalcolata,
        ]);
    }
    
    /**
     * Calcola la scadenza e lo stato
     */
    private function calcolaScadenza($dataConseguimento, $durata)
    {
        if (!$dataConseguimento) {
            return [
                'data_conseguimento' => null,
                'data_scadenza' => null,
                'stato' => 'mancante',
                'giorni_rimanenti' => null,
            ];
        }
        
        $data = Carbon::parse($dataConseguimento);
        $scadenza = $data->copy()->addYears($durata);
        $oggi = Carbon::now();
        $giorniRimanenti = $oggi->diffInDays($scadenza, false);
        
        // Determina lo stato
        if ($giorniRimanenti < 0) {
            $stato = 'scaduto';
        } elseif ($giorniRimanenti <= 30) {
            $stato = 'in_scadenza';
        } else {
            $stato = 'valido';
        }
        
        return [
            'data_conseguimento' => $data,
            'data_scadenza' => $scadenza,
            'stato' => $stato,
            'giorni_rimanenti' => abs($giorniRimanenti),
        ];
    }
    
    /**
     * Esporta i dati Corsi Accordo Stato Regione in Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Ottieni tutti i militari con le loro scadenze
            $militari = Militare::with(['grado', 'compagnia', 'scadenza'])
                ->orderByGradoENome()
                ->get();
            
            // Stile header
            $headerStyle = [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0a2342']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];
            
            // Titolo
            $sheet->setCellValue('A1', 'CORSI ACCORDO STATO REGIONE');
            $sheet->mergeCells('A1:P1');
            $sheet->getStyle('A1')->applyFromArray($headerStyle);
            $sheet->getRowDimension('1')->setRowHeight(25);
            
            // Header colonne
            $headers = [
                'N.', 'COMPAGNIA', 'GRADO', 'COGNOME', 'NOME',
                'TRATTORI CONS.', 'TRATTORI SCAD.',
                'MMT CONS.', 'MMT SCAD.',
                'MULETTO CONS.', 'MULETTO SCAD.',
                'PLE CONS.', 'PLE SCAD.',
                'MOTOSEGA CONS.', 'MOTOSEGA SCAD.',
                'FUNI/CATENE CONS.', 'FUNI/CATENE SCAD.',
                'RLS CONS.', 'RLS SCAD.'
            ];
            
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '2', $header);
                $sheet->getStyle($col . '2')->applyFromArray($headerStyle);
                $sheet->getStyle($col . '2')->getAlignment()->setWrapText(true);
                $col++;
            }
            $sheet->getRowDimension('2')->setRowHeight(40);
            
            // Dati militari
            $row = 3;
            $num = 1;
            
            foreach ($militari as $militare) {
                $scadenza = $militare->scadenza;
                
                $sheet->setCellValue('A' . $row, $num++);
                $sheet->setCellValue('B' . $row, $militare->compagnia->nome ?? '');
                $sheet->setCellValue('C' . $row, $militare->grado->sigla ?? '');
                $sheet->setCellValue('D' . $row, strtoupper($militare->cognome));
                $sheet->setCellValue('E' . $row, strtoupper($militare->nome));
                
                // Abilitazione Trattori
                $this->addScadenzaToSheet($sheet, $row, 'F', $scadenza?->abilitazione_trattori_data_conseguimento, 5);
                
                // Abilitazione MMT
                $this->addScadenzaToSheet($sheet, $row, 'H', $scadenza?->abilitazione_mmt_data_conseguimento, 5);
                
                // Abilitazione Muletto
                $this->addScadenzaToSheet($sheet, $row, 'J', $scadenza?->abilitazione_muletto_data_conseguimento, 5);
                
                // Abilitazione PLE
                $this->addScadenzaToSheet($sheet, $row, 'L', $scadenza?->abilitazione_ple_data_conseguimento, 5);
                
                // Corso Motosega
                $this->addScadenzaToSheet($sheet, $row, 'N', $scadenza?->corso_motosega_data_conseguimento, 5);
                
                // Addetti Funi e Catene
                $this->addScadenzaToSheet($sheet, $row, 'P', $scadenza?->addetti_funi_catene_data_conseguimento, 5);
                
                // Corso RLS
                $this->addScadenzaToSheet($sheet, $row, 'R', $scadenza?->corso_rls_data_conseguimento, 5);
                
                // Bordi
                $sheet->getStyle('A' . $row . ':S' . $row)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                
                $row++;
            }
            
            // Larghezze colonne
            $sheet->getColumnDimension('A')->setWidth(6);   // N.
            $sheet->getColumnDimension('B')->setWidth(25);  // Compagnia
            $sheet->getColumnDimension('C')->setWidth(12);  // Grado
            $sheet->getColumnDimension('D')->setWidth(20);  // Cognome
            $sheet->getColumnDimension('E')->setWidth(20);  // Nome
            
            // Date colonne
            for ($i = ord('F'); $i <= ord('S'); $i++) {
                $sheet->getColumnDimension(chr($i))->setWidth(18);
            }
            
            // Salva
            $filename = 'Corsi_Accordo_Stato_Regione_' . date('Y-m-d') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'corsi_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);
            
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Errore export Excel Corsi Accordo Stato Regione', [
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()->with('error', 'Errore durante l\'esportazione Excel.');
        }
    }
    
    /**
     * Aggiunge una scadenza al foglio Excel con colori
     */
    private function addScadenzaToSheet($sheet, $row, $colCons, $dataConseguimento, $durata)
    {
        $colScad = chr(ord($colCons) + 1);
        
        if (!$dataConseguimento) {
            $sheet->setCellValue($colCons . $row, '');
            $sheet->setCellValue($colScad . $row, '');
            // Sfondo grigio per mancante
            $sheet->getStyle($colCons . $row . ':' . $colScad . $row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CCCCCC']],
            ]);
            return;
        }
        
        $data = Carbon::parse($dataConseguimento);
        $scadenza = $data->copy()->addYears($durata);
        
        $sheet->setCellValue($colCons . $row, $data->format('d/m/Y'));
        $sheet->setCellValue($colScad . $row, $scadenza->format('d/m/Y'));
        
        // Calcola stato
        $oggi = Carbon::now();
        $giorniRimanenti = $oggi->diffInDays($scadenza, false);
        
        $coloreFondo = 'FFFFFF'; // Default bianco
        if ($giorniRimanenti < 0) {
            $coloreFondo = 'FF6B6B'; // Rosso - scaduto
        } elseif ($giorniRimanenti <= 30) {
            $coloreFondo = 'FFD93D'; // Giallo - in scadenza
        } else {
            $coloreFondo = '6BCF7F'; // Verde - valido
        }
        
        $sheet->getStyle($colScad . $row)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $coloreFondo]],
        ]);
        
        // Allineamento
        $sheet->getStyle($colCons . $row . ':' . $colScad . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }
}
