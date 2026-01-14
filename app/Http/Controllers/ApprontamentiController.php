<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\ScadenzaApprontamento;
use App\Models\ScadenzaMilitare;
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
            ->with(['scadenzaApprontamento', 'scadenza', 'grado', 'ufficio', 'compagnia'])
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

        $colonne = ScadenzaApprontamento::getLabels();

        return view('approntamenti.index', compact('militari', 'filtri', 'canEdit', 'colonne'));
    }

    /**
     * Applica i filtri alla collection di militari
     */
    private function applicaFiltri($militari, array $filtri)
    {
        return $militari->filter(function ($militare) use ($filtri) {
            foreach ($filtri as $campo => $valore) {
                if (empty($valore) || $valore === 'tutti') {
                    continue;
                }

                // Determina la fonte dei dati
                $stato = $this->getStatoCampo($militare, $campo);

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
     * Ottiene lo stato di un campo considerando la fonte corretta
     */
    private function getStatoCampo($militare, string $campo): string
    {
        if (ScadenzaApprontamento::isColonnaCondivisa($campo)) {
            // Legge da scadenze_militari
            $scadenza = $militare->scadenza;
            if (!$scadenza) return 'non_presente';
            
            $campoSorgente = ScadenzaApprontamento::getCampoSorgente($campo);
            return $scadenza->verificaStato($campoSorgente);
        } else {
            // Legge da scadenze_approntamenti
            $scadenza = $militare->scadenzaApprontamento;
            if (!$scadenza) return 'non_presente';
            
            return $scadenza->verificaStato($campo);
        }
    }

    /**
     * Ottiene il valore formattato di un campo
     */
    public static function getValoreCampo($militare, string $campo): array
    {
        if (ScadenzaApprontamento::isColonnaCondivisa($campo)) {
            // Legge da scadenze_militari
            $scadenza = $militare->scadenza;
            $campoSorgente = ScadenzaApprontamento::getCampoSorgente($campo);
            
            if (!$scadenza) {
                return [
                    'valore' => '-',
                    'valore_raw' => '',
                    'stato' => 'non_presente',
                    'colore' => 'background-color: #f8f9fa; color: #6c757d;',
                    'scadenza' => '-',
                    'fonte' => 'scadenze_militari',
                    'readonly' => true, // Non modificabile da qui
                ];
            }
            
            $campoData = $campoSorgente . '_data_conseguimento';
            $dataConseguimento = $scadenza->$campoData;
            
            return [
                'valore' => $dataConseguimento ? Carbon::parse($dataConseguimento)->format('d/m/Y') : '-',
                'valore_raw' => $dataConseguimento ? Carbon::parse($dataConseguimento)->format('Y-m-d') : '',
                'stato' => $scadenza->verificaStato($campoSorgente),
                'colore' => $scadenza->getColore($campoSorgente),
                'scadenza' => $scadenza->formatScadenza($campoSorgente),
                'fonte' => 'scadenze_militari',
                'readonly' => true,
            ];
        } else {
            // Legge da scadenze_approntamenti
            $scadenza = $militare->scadenzaApprontamento;
            
            if (!$scadenza) {
                return [
                    'valore' => '-',
                    'valore_raw' => '',
                    'stato' => 'non_presente',
                    'colore' => 'background-color: #f8f9fa; color: #6c757d;',
                    'scadenza' => '-',
                    'fonte' => 'approntamenti',
                    'readonly' => false,
                ];
            }
            
            return [
                'valore' => $scadenza->getValoreFormattato($campo),
                'valore_raw' => $scadenza->$campo ?? '',
                'stato' => $scadenza->verificaStato($campo),
                'colore' => $scadenza->getColore($campo),
                'scadenza' => $scadenza->formatScadenza($campo),
                'fonte' => 'approntamenti',
                'readonly' => false,
            ];
        }
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

            // Verifica che il campo esista
            if (!array_key_exists($campo, ScadenzaApprontamento::COLONNE)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campo non valido'
                ], 400);
            }

            // Verifica se è una colonna condivisa (readonly)
            if (ScadenzaApprontamento::isColonnaCondivisa($campo)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Questo campo è gestito dalla pagina SPP/Idoneità. Modificalo da lì.'
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
            ->with(['scadenzaApprontamento', 'scadenza', 'grado', 'ufficio', 'compagnia'])
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
        foreach (ScadenzaApprontamento::getLabels() as $label) {
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

            // Grado, Cognome, Nome
            $sheet->setCellValue($col++ . $row, $militare->grado->sigla ?? '-');
            $sheet->setCellValue($col++ . $row, $militare->cognome);
            $sheet->setCellValue($col++ . $row, $militare->nome);

            // Colonne approntamento
            foreach (array_keys(ScadenzaApprontamento::COLONNE) as $campo) {
                $datiCampo = self::getValoreCampo($militare, $campo);
                $sheet->setCellValue($col . $row, $datiCampo['valore']);
                
                $bgColor = match($datiCampo['stato']) {
                    'scaduto' => 'FFCCCC',
                    'in_scadenza' => 'FFF3CD',
                    'valido' => 'D4EDDA',
                    'non_richiesto' => 'E2E3E5',
                    default => 'F8F9FA'
                };
                $sheet->getStyle($col . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($bgColor);
                
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
