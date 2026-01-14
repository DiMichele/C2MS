<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\ScadenzaApprontamento;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ApprontamentiController extends Controller
{
    /**
     * Mostra la pagina degli approntamenti
     */
    public function index(Request $request)
    {
        // Il Global Scope filtra già automaticamente i militari visibili
        $query = Militare::withVisibilityFlags()
            ->with(['scadenzaApprontamento', 'grado', 'ufficio', 'compagnia'])
            ->orderBy('cognome')
            ->orderBy('nome');

        $militari = $query->get();

        // Applica i filtri se presenti
        $filtri = $request->only(array_keys(ScadenzaApprontamento::COLONNE));

        if (!empty(array_filter($filtri))) {
            $militari = $this->applicaFiltri($militari, $filtri);
        }

        // Verifica se l'utente può modificare le scadenze
        $canEdit = auth()->user()->hasPermission('approntamenti.edit') 
                || auth()->user()->hasPermission('admin.access');

        $colonne = ScadenzaApprontamento::COLONNE;

        return view('approntamenti.index', compact('militari', 'filtri', 'canEdit', 'colonne'));
    }

    /**
     * Applica i filtri alla collection di militari
     */
    private function applicaFiltri($militari, array $filtri)
    {
        return $militari->filter(function ($militare) use ($filtri) {
            $scadenza = $militare->scadenzaApprontamento;

            foreach ($filtri as $campo => $valore) {
                if (empty($valore) || $valore === 'tutti') {
                    continue;
                }

                if (!$scadenza) {
                    if ($valore === 'scaduti' || $valore === 'non_presenti') {
                        continue; // Include i militari senza record
                    }
                    return false;
                }

                $stato = $scadenza->verificaStato($campo);

                switch ($valore) {
                    case 'validi':
                        if ($stato !== 'valido') return false;
                        break;
                    case 'in_scadenza':
                        if ($stato !== 'in_scadenza') return false;
                        break;
                    case 'scaduti':
                        if ($stato !== 'scaduto' && $stato !== 'non_presente') return false;
                        break;
                    case 'non_richiesti':
                        if ($stato !== 'non_richiesto') return false;
                        break;
                }
            }

            return true;
        });
    }

    /**
     * Aggiorna una singola cella
     */
    public function updateSingola(Request $request, $militareId)
    {
        // Verifica permessi
        if (!auth()->user()->hasPermission('approntamenti.edit') && !auth()->user()->hasPermission('admin.access')) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per modificare gli approntamenti'
            ], 403);
        }

        try {
            $militare = Militare::findOrFail($militareId);
            $campo = $request->input('campo');
            $valore = $request->input('valore');

            // Valida il campo
            if (!array_key_exists($campo, ScadenzaApprontamento::COLONNE)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campo non valido'
                ], 400);
            }

            // Gestisce il valore: converte stringa vuota in null, 'NR' per Non Richiesto
            $valoreDaSalvare = null;
            if (!empty($valore)) {
                if (strtoupper($valore) === 'NR' || strtolower($valore) === 'non richiesto') {
                    $valoreDaSalvare = 'NR';
                } else {
                    // Prova a validare come data
                    try {
                        $data = Carbon::createFromFormat('d/m/Y', $valore);
                        $valoreDaSalvare = $data->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Prova altri formati
                        try {
                            $data = Carbon::parse($valore);
                            $valoreDaSalvare = $data->format('Y-m-d');
                        } catch (\Exception $e2) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Formato data non valido. Usa dd/mm/yyyy o "NR" per Non Richiesto'
                            ], 400);
                        }
                    }
                }
            }

            // Crea o aggiorna il record
            $scadenza = ScadenzaApprontamento::updateOrCreate(
                ['militare_id' => $militareId],
                [$campo => $valoreDaSalvare]
            );

            // Calcola i dati aggiornati
            $valoreFormattato = $scadenza->getValoreFormattato($campo);
            $stato = $scadenza->verificaStato($campo);

            return response()->json([
                'success' => true,
                'message' => 'Valore aggiornato con successo',
                'valore' => $valoreFormattato,
                'stato' => $stato,
                'colore' => $scadenza->getColore($campo),
                'scadenza' => $scadenza->formatScadenza($campo)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pagina di gestione approntamenti (admin)
     */
    public function gestione()
    {
        if (!auth()->user()->hasPermission('admin.access')) {
            abort(403, 'Non hai i permessi per accedere a questa pagina');
        }

        $colonne = ScadenzaApprontamento::COLONNE;
        
        return view('approntamenti.gestione', compact('colonne'));
    }

    /**
     * Export Excel
     */
    public function exportExcel(Request $request)
    {
        $query = Militare::withVisibilityFlags()
            ->with(['scadenzaApprontamento', 'grado', 'ufficio', 'compagnia'])
            ->orderBy('cognome')
            ->orderBy('nome');

        $militari = $query->get();

        // Applica filtri se presenti
        $filtri = $request->only(array_keys(ScadenzaApprontamento::COLONNE));
        if (!empty(array_filter($filtri))) {
            $militari = $this->applicaFiltri($militari, $filtri);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Approntamenti');

        // Header
        $headers = ['Grado', 'Cognome', 'Nome'];
        foreach (ScadenzaApprontamento::COLONNE as $label) {
            $headers[] = $label;
        }

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0A2342']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ]);
            $col++;
        }

        // Dati
        $row = 2;
        foreach ($militari as $militare) {
            $col = 'A';
            $scadenza = $militare->scadenzaApprontamento;

            // Grado, Cognome, Nome
            $sheet->setCellValue($col++ . $row, $militare->grado->sigla ?? '-');
            $sheet->setCellValue($col++ . $row, $militare->cognome);
            $sheet->setCellValue($col++ . $row, $militare->nome);

            // Colonne approntamento
            foreach (array_keys(ScadenzaApprontamento::COLONNE) as $campo) {
                $valore = $scadenza ? $scadenza->getValoreFormattato($campo) : '-';
                $sheet->setCellValue($col . $row, $valore);
                
                if ($scadenza) {
                    $stato = $scadenza->verificaStato($campo);
                    $bgColor = match($stato) {
                        'scaduto' => 'FFCCCC',
                        'in_scadenza' => 'FFF3CD',
                        'valido' => 'D4EDDA',
                        'non_richiesto' => 'E2E3E5',
                        default => 'F8F9FA'
                    };
                    $sheet->getStyle($col . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB($bgColor);
                }
                $col++;
            }
            $row++;
        }

        // Auto-size colonne
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        $writer = new Xlsx($spreadsheet);
        $filename = 'Approntamenti_' . date('Y-m-d_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
}
