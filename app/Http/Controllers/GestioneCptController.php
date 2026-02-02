<?php

namespace App\Http\Controllers;

use App\Models\CodiciServizioGerarchia;
use App\Models\TipoServizio;
use App\Scopes\OrganizationalUnitScope;
use App\Services\ExcelStyleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Controller per la gestione dei codici CPT
 * Permette di creare, modificare ed eliminare i codici/categorie utilizzati nel CPT
 */
class GestioneCptController extends Controller
{
    /**
     * SINGLE SOURCE OF TRUTH: Definizione colonne tabella CPT
     * Usato sia nella vista che nell'export Excel per mantenere sincronizzazione automatica
     * 
     * @var array
     */
    public const COLONNE_TABELLA = [
        'codice' => [
            'header' => 'Codice',
            'width' => 15,
            'align' => 'center',
            'campo' => 'codice',
            'tipo' => 'badge_colorato', // Indica che questa colonna usa il colore_badge
        ],
        'descrizione' => [
            'header' => 'Descrizione',
            'width' => 45,
            'align' => 'left',
            'campo' => 'attivita_specifica',
            'tipo' => 'testo',
        ],
        'tipo_impiego' => [
            'header' => 'Tipo Impiego',
            'width' => 25,
            'align' => 'center',
            'campo' => 'impiego',
            'tipo' => 'impiego', // Formattazione speciale per tipo impiego
        ],
        'stato' => [
            'header' => 'Stato',
            'width' => 12,
            'align' => 'center',
            'campo' => 'attivo',
            'tipo' => 'stato', // Attivo/Inattivo
        ],
    ];

    /**
     * Mappa colori per tipo impiego (usato nell'export Excel)
     * Colori simili a quelli visualizzati nella pagina web
     * 
     * @var array
     */
    public const COLORI_IMPIEGO = [
        'DISPONIBILE' => ['bg' => 'd4edda', 'text' => '155724'],
        'INDISPONIBILE' => ['bg' => 'fff3cd', 'text' => '856404'],
        'NON_DISPONIBILE' => ['bg' => 'f8d7da', 'text' => '721c24'],
        'PRESENTE_SERVIZIO' => ['bg' => 'cce5ff', 'text' => '004085'],
        'DISPONIBILE_ESIGENZA' => ['bg' => 'd1ecf1', 'text' => '0c5460'],
    ];

    /**
     * Ottiene le colonne della tabella (per uso nelle viste)
     * 
     * @return array
     */
    public static function getColonneTabella(): array
    {
        return self::COLONNE_TABELLA;
    }

    /**
     * Visualizza l'elenco di tutti i codici CPT
     * 
     * I codici sono isolati per unità organizzativa:
     * - Ogni unità vede SOLO i propri codici (e quelli delle sottounità)
     * - Non esistono più codici "globali"
     * 
     * La vista è organizzata in accordion per tipo di impiego
     */
    public function index(Request $request)
    {
        // Carica tutti i codici ordinati per tipo impiego e poi per ordine
        // Senza paginazione - tabella scrollabile
        $codici = CodiciServizioGerarchia::orderBy('impiego')
            ->orderBy('ordine')
            ->orderBy('codice')
            ->get();

        // Raggruppa per tipo di impiego per la struttura accordion
        $codiciPerImpiego = $codici->groupBy('impiego');

        // Ottieni valori unici per i filtri (lo scope filtra automaticamente per unità attiva)
        $impieghi = CodiciServizioGerarchia::select('impiego')
            ->distinct()
            ->pluck('impiego');

        // Etichette leggibili per i tipi di impiego
        $tipiImpiego = CodiciServizioGerarchia::getTipiImpiego();

        return view('gestione-cpt.index', compact(
            'codici',
            'codiciPerImpiego',
            'impieghi',
            'tipiImpiego'
        ));
    }

    /**
     * Mostra il form per creare un nuovo codice CPT
     */
    public function create()
    {
        // Lo scope filtra automaticamente per unità attiva
        $macroAttivita = CodiciServizioGerarchia::select('macro_attivita')
            ->distinct()
            ->whereNotNull('macro_attivita')
            ->pluck('macro_attivita');

        $tipiAttivita = CodiciServizioGerarchia::select('tipo_attivita')
            ->distinct()
            ->whereNotNull('tipo_attivita')
            ->pluck('tipo_attivita');

        $impieghi = [
            'DISPONIBILE' => 'Disponibile',
            'INDISPONIBILE' => 'Indisponibile',
            'NON_DISPONIBILE' => 'Non Disponibile',
            'PRESENTE_SERVIZIO' => 'Presente in Servizio',
            'DISPONIBILE_ESIGENZA' => 'Disponibile su Esigenza'
        ];

        return view('gestione-cpt.create', compact('macroAttivita', 'tipiAttivita', 'impieghi'));
    }

    /**
     * Salva un nuovo codice CPT
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'codice' => 'required|string|max:20|unique:codici_servizio_gerarchia,codice',
            'macro_attivita' => 'required|string|max:100',
            'attivita_specifica' => 'required|string|max:200',
            'impiego' => 'required|in:DISPONIBILE,INDISPONIBILE,NON_DISPONIBILE,PRESENTE_SERVIZIO,DISPONIBILE_ESIGENZA',
            'colore_badge' => 'required|string|max:7',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        try {
            // Calcola automaticamente l'ordine come ultimo della categoria
            $maxOrdine = CodiciServizioGerarchia::where('macro_attivita', $request->macro_attivita)
                ->max('ordine') ?? 0;

            // Il codice è sempre associato all'unità attiva (non esistono più codici globali)
            $codice = CodiciServizioGerarchia::create([
                'organizational_unit_id' => activeUnitId(),
                'codice' => strtoupper($request->codice),
                'macro_attivita' => $request->macro_attivita,
                'tipo_attivita' => null,
                'attivita_specifica' => $request->attivita_specifica,
                'impiego' => $request->impiego,
                'descrizione_impiego' => null,
                'colore_badge' => $request->colore_badge,
                'attivo' => true,
                'ordine' => $maxOrdine + 1
            ]);

            // SINCRONIZZA con tipi_servizio per il CPT
            $this->sincronizzaTipoServizio($codice);

            DB::commit();
            
            return redirect()->route('codici-cpt.index')
                ->with('success', "Codice '{$codice->codice}' creato e sincronizzato con il CPT!");
        } catch (\Exception $e) {
            DB::rollBack();
            // Log dettagliato per debug, messaggio generico per l'utente
            \Illuminate\Support\Facades\Log::error('Errore creazione codice CPT', [
                'user_id' => auth()->id(),
                'input' => $request->except(['_token']),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withErrors(['error' => 'Si è verificato un errore durante la creazione del codice. Riprova o contatta l\'amministratore.'])
                ->withInput();
        }
    }

    /**
     * Mostra il form per modificare un codice CPT esistente
     */
    public function edit($codice)
    {
        // Lo scope filtra automaticamente per unità attiva
        $codice = CodiciServizioGerarchia::findOrFail($codice);
        
        $macroAttivita = CodiciServizioGerarchia::select('macro_attivita')
            ->distinct()
            ->whereNotNull('macro_attivita')
            ->pluck('macro_attivita');

        $tipiAttivita = CodiciServizioGerarchia::select('tipo_attivita')
            ->distinct()
            ->whereNotNull('tipo_attivita')
            ->pluck('tipo_attivita');

        $impieghi = [
            'DISPONIBILE' => 'Disponibile',
            'INDISPONIBILE' => 'Indisponibile',
            'NON_DISPONIBILE' => 'Non Disponibile',
            'PRESENTE_SERVIZIO' => 'Presente in Servizio',
            'DISPONIBILE_ESIGENZA' => 'Disponibile su Esigenza'
        ];

        return view('gestione-cpt.edit', compact('codice', 'macroAttivita', 'tipiAttivita', 'impieghi'));
    }

    /**
     * Aggiorna un codice CPT esistente
     */
    public function update(Request $request, $codice)
    {
        // Lo scope filtra automaticamente per unità attiva
        $codice = CodiciServizioGerarchia::findOrFail($codice);
        $validator = Validator::make($request->all(), [
            'codice' => 'required|string|max:20|unique:codici_servizio_gerarchia,codice,' . $codice->id,
            'macro_attivita' => 'required|string|max:100',
            'attivita_specifica' => 'required|string|max:200',
            'impiego' => 'required|in:DISPONIBILE,INDISPONIBILE,NON_DISPONIBILE,PRESENTE_SERVIZIO,DISPONIBILE_ESIGENZA',
            'colore_badge' => 'required|string|max:7',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Se cambia categoria, ricalcola l'ordine
        $ordine = $codice->ordine;
        if ($request->macro_attivita !== $codice->macro_attivita) {
            $maxOrdine = CodiciServizioGerarchia::where('macro_attivita', $request->macro_attivita)
                ->max('ordine') ?? 0;
            $ordine = $maxOrdine + 1;
        }

        DB::beginTransaction();
        try {
            $codice->update([
                'codice' => strtoupper($request->codice),
                'macro_attivita' => $request->macro_attivita,
                'attivita_specifica' => $request->attivita_specifica,
                'impiego' => $request->impiego,
                'colore_badge' => $request->colore_badge,
                'ordine' => $ordine,
                // Campi configurazione ruolini
                'conta_come_presente' => $request->boolean('conta_come_presente'),
                'esenzione_alzabandiera' => $request->boolean('esenzione_alzabandiera'),
                'disponibilita_limitata' => $request->boolean('disponibilita_limitata'),
            ]);

            // SINCRONIZZA con tipi_servizio
            $this->sincronizzaTipoServizio($codice);

            DB::commit();
            
            return redirect()->route('codici-cpt.index')
                ->with('success', "Codice '{$codice->codice}' aggiornato e sincronizzato con il CPT!");
        } catch (\Exception $e) {
            DB::rollBack();
            // Log dettagliato per debug, messaggio generico per l'utente
            \Illuminate\Support\Facades\Log::error('Errore aggiornamento codice CPT', [
                'user_id' => auth()->id(),
                'codice_id' => $codice->id,
                'input' => $request->except(['_token']),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withErrors(['error' => 'Si è verificato un errore durante l\'aggiornamento del codice. Riprova o contatta l\'amministratore.'])
                ->withInput();
        }
    }

    /**
     * Elimina un codice CPT
     */
    public function destroy(CodiciServizioGerarchia $codice)
    {
        DB::beginTransaction();
        try {
            $codiceTesto = $codice->codice;
            
            // Elimina anche da tipi_servizio se esiste (stesso codice e stessa unità)
            TipoServizio::withoutGlobalScope(OrganizationalUnitScope::class)
                ->where('codice', $codice->codice)
                ->where('organizational_unit_id', $codice->organizational_unit_id)
                ->delete();
            
            // Elimina da codici_servizio_gerarchia
            $codice->delete();

            DB::commit();
            
            return redirect()->route('codici-cpt.index')
                ->with('success', "Codice '{$codiceTesto}' eliminato e rimosso dal CPT!");
        } catch (\Exception $e) {
            DB::rollBack();
            // Log dettagliato per debug, messaggio generico per l'utente
            \Illuminate\Support\Facades\Log::error('Errore eliminazione codice CPT', [
                'user_id' => auth()->id(),
                'codice_id' => $codice->id,
                'codice' => $codiceTesto,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Si è verificato un errore durante l\'eliminazione del codice. Potrebbe essere in uso da altre tabelle.');
        }
    }

    /**
     * Aggiorna lo stato attivo/inattivo di un codice
     */
    public function toggleAttivo($codice)
    {
        // Lo scope filtra automaticamente per unità attiva
        $codice = CodiciServizioGerarchia::findOrFail($codice);
        DB::beginTransaction();
        try {
            $codice->update(['attivo' => !$codice->attivo]);

            // Sincronizza lo stato con tipi_servizio
            $this->sincronizzaTipoServizio($codice);

            DB::commit();
            
            $stato = $codice->attivo ? 'attivato' : 'disattivato';
            
            return redirect()->back()
                ->with('success', "Codice '{$codice->codice}' {$stato} e sincronizzato con il CPT!");
        } catch (\Exception $e) {
            DB::rollBack();
            // Log dettagliato per debug, messaggio generico per l'utente
            \Illuminate\Support\Facades\Log::error('Errore toggle stato codice CPT', [
                'user_id' => auth()->id(),
                'codice_id' => $codice->id,
                'codice' => $codice->codice,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Si è verificato un errore durante l\'aggiornamento dello stato. Riprova.');
        }
    }

    /**
     * Duplica un codice CPT esistente
     */
    public function duplicate($codice)
    {
        // Lo scope filtra automaticamente per unità attiva
        $codice = CodiciServizioGerarchia::findOrFail($codice);
        
        // Trova un codice univoco (nell'ambito dell'unità attiva)
        $nuovoCodice = $codice->codice . '_COPIA';
        $counter = 1;
        while (CodiciServizioGerarchia::where('codice', $nuovoCodice)->exists()) {
            $nuovoCodice = $codice->codice . '_COPIA' . $counter;
            $counter++;
        }

        $nuovo = $codice->replicate();
        $nuovo->codice = $nuovoCodice;
        $nuovo->attivo = false; // Disattivato per default
        $nuovo->save();

        return redirect()->route('codici-cpt.edit', $nuovo)
            ->with('success', "Codice duplicato con successo! Modifica i dettagli e attivalo quando pronto.");
    }

    /**
     * Esporta i codici in formato Excel con formattazione
     * Raggruppati per tipo di impiego con intestazioni di sezione
     * I badge mantengono i colori originali
     */
    public function export()
    {
        // Carica codici raggruppati per tipo di impiego
        $codici = CodiciServizioGerarchia::orderBy('impiego')
            ->orderBy('ordine')
            ->orderBy('codice')
            ->get();
        
        $codiciPerImpiego = $codici->groupBy('impiego');
        $tipiImpiego = CodiciServizioGerarchia::getTipiImpiego();
        
        // Usa ExcelStyleService per stili consistenti
        $excelService = new ExcelStyleService();
        $spreadsheet = $excelService->createSpreadsheet('Codici CPT');
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Codici CPT');
        
        // Imposta larghezze colonne
        $sheet->getColumnDimension('A')->setWidth(15); // Codice
        $sheet->getColumnDimension('B')->setWidth(50); // Descrizione
        $sheet->getColumnDimension('C')->setWidth(15); // Stato
        
        $row = 1;
        
        foreach ($codiciPerImpiego as $impiego => $codiciGruppo) {
            // --- INTESTAZIONE SEZIONE (Tipo Impiego) ---
            $titoloSezione = $tipiImpiego[$impiego] ?? str_replace('_', ' ', ucfirst(strtolower($impiego)));
            $colori = self::COLORI_IMPIEGO[$impiego] ?? ['bg' => '0A2342', 'text' => 'FFFFFF'];
            
            $sheet->setCellValue("A{$row}", $titoloSezione . ' (' . $codiciGruppo->count() . ' codici)');
            $sheet->mergeCells("A{$row}:C{$row}");
            
            // Stile intestazione sezione
            $sheet->getStyle("A{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $colori['bg']]
                ],
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => $colori['text']]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);
            $sheet->getRowDimension($row)->setRowHeight(28);
            $row++;
            
            // --- HEADER COLONNE ---
            $sheet->setCellValue("A{$row}", 'Codice');
            $sheet->setCellValue("B{$row}", 'Descrizione');
            $sheet->setCellValue("C{$row}", 'Stato');
            
            $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'e9ecef']
                ],
                'font' => [
                    'bold' => true,
                    'size' => 10,
                    'color' => ['rgb' => '495057']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);
            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;
            
            // --- DATI CODICI ---
            foreach ($codiciGruppo as $codice) {
                // Colonna Codice con colore badge originale
                $coloreHex = ltrim($codice->colore_badge, '#');
                $testoColore = $this->isColorChiaro($coloreHex) ? '000000' : 'FFFFFF';
                
                $sheet->setCellValue("A{$row}", $codice->codice);
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $coloreHex]
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => $testoColore]
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);
                
                // Colonna Descrizione
                $sheet->setCellValue("B{$row}", $codice->attivita_specifica);
                $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true);
                
                // Colonna Stato
                $sheet->setCellValue("C{$row}", $codice->attivo ? 'Attivo' : 'Inattivo');
                if ($codice->attivo) {
                    $excelService->applyBadgeStyle($sheet, "C{$row}", 'success');
                } else {
                    $sheet->getStyle("C{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '6c757d']
                        ],
                        'font' => [
                            'color' => ['rgb' => 'FFFFFF']
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER
                        ]
                    ]);
                }
                
                $sheet->getRowDimension($row)->setRowHeight(-1); // Auto-height
                $row++;
            }
            
            // Riga vuota tra sezioni
            $row++;
        }
        
        // Bordi per tutte le celle dati
        $sheet->getStyle("A1:C{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        // Freeze header (prima riga)
        $sheet->freezePane('A2');
        
        // Info generazione
        $excelService->addGenerationInfo($sheet, $row + 1);
        
        // Genera file
        $writer = new Xlsx($spreadsheet);
        $filename = 'Codici_CPT_' . date('Y-m-d') . '.xlsx';
        
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);
        
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Ottiene il valore formattato per una cella
     * 
     * @param CodiciServizioGerarchia $codice
     * @param array $config
     * @return string
     */
    private function getValoreCella(CodiciServizioGerarchia $codice, array $config): string
    {
        $campo = $config['campo'];
        $valore = $codice->$campo;
        
        switch ($config['tipo']) {
            case 'impiego':
                // Formatta l'impiego per esteso
                return str_replace('_', ' ', ucfirst(strtolower($valore)));
            
            case 'stato':
                return $valore ? 'Attivo' : 'Inattivo';
            
            default:
                return $valore ?? '';
        }
    }

    /**
     * Applica la formattazione specifica alla cella
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param string $cell
     * @param CodiciServizioGerarchia $codice
     * @param array $config
     * @param ExcelStyleService $excelService
     */
    private function applicaFormattazioneCella($sheet, string $cell, CodiciServizioGerarchia $codice, array $config, ExcelStyleService $excelService): void
    {
        // Allineamento base
        $align = match ($config['align']) {
            'center' => Alignment::HORIZONTAL_CENTER,
            'right' => Alignment::HORIZONTAL_RIGHT,
            default => Alignment::HORIZONTAL_LEFT,
        };
        
        $sheet->getStyle($cell)->getAlignment()->setHorizontal($align);
        $sheet->getStyle($cell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
        
        switch ($config['tipo']) {
            case 'badge_colorato':
                // Usa il colore_badge del codice come sfondo
                $coloreHex = ltrim($codice->colore_badge, '#');
                
                // Determina se usare testo chiaro o scuro in base al colore di sfondo
                $testoColore = $this->isColorChiaro($coloreHex) ? '000000' : 'FFFFFF';
                
                $sheet->getStyle($cell)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $coloreHex]
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => $testoColore]
                    ]
                ]);
                break;
            
            case 'impiego':
                // Colore in base al tipo di impiego
                $colori = self::COLORI_IMPIEGO[$codice->impiego] ?? ['bg' => 'FFFFFF', 'text' => '000000'];
                
                $sheet->getStyle($cell)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $colori['bg']]
                    ],
                    'font' => [
                        'color' => ['rgb' => $colori['text']]
                    ]
                ]);
                break;
            
            case 'stato':
                // Verde per attivo, grigio per inattivo
                if ($codice->attivo) {
                    $excelService->applyBadgeStyle($sheet, $cell, 'success');
                } else {
                    $sheet->getStyle($cell)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '6c757d']
                        ],
                        'font' => [
                            'color' => ['rgb' => 'FFFFFF']
                        ]
                    ]);
                }
                break;
        }
    }

    /**
     * Determina se un colore è chiaro (per decidere se usare testo nero o bianco)
     * 
     * @param string $hexColor Colore esadecimale senza #
     * @return bool True se il colore è chiaro
     */
    private function isColorChiaro(string $hexColor): bool
    {
        // Assicura che il colore sia in formato corretto
        $hexColor = str_pad($hexColor, 6, '0', STR_PAD_LEFT);
        
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));
        
        // Formula per luminosità percepita
        $luminosita = ($r * 299 + $g * 587 + $b * 114) / 1000;
        
        return $luminosita > 128;
    }

    /**
     * Aggiorna l'ordine dei codici tramite drag & drop
     */
    public function updateOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ordini' => 'required|array',
            'ordini.*' => 'required|integer|exists:codici_servizio_gerarchia,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Dati non validi'], 400);
        }

        // FIX: Usa transazione per aggiornamenti multipli atomici; aggiorna solo codici dell'unità attiva
        try {
            $visibleIds = CodiciServizioGerarchia::pluck('id')->toArray();
            DB::transaction(function() use ($request, $visibleIds) {
                foreach ($request->ordini as $ordine => $id) {
                    if (in_array((int) $id, $visibleIds, true)) {
                        CodiciServizioGerarchia::where('id', $id)
                            ->update(['ordine' => $ordine]);
                    }
                }
            });
            
            return response()->json(['success' => true, 'message' => 'Ordine aggiornato con successo!']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Errore aggiornamento ordine codici CPT', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Errore durante l\'aggiornamento dell\'ordine'], 500);
        }
    }

    /**
     * Sincronizza un codice CPT con la tabella tipi_servizio (usata dal CPT)
     * 
     * @param CodiciServizioGerarchia $codiceGerarchia
     * @return void
     */
    private function sincronizzaTipoServizio(CodiciServizioGerarchia $codiceGerarchia)
    {
        // Mappa macro_attivita -> categoria per tipi_servizio
        $mappaCategorie = [
            'ASSENTE' => 'assenza',
            'PROVVEDIMENTI MEDICO SANITARI' => 'assenza',
            'SERVIZIO' => 'servizio',
            'OPERAZIONE' => 'missione',
            'ADD./APP./CATTEDRE' => 'formazione',
            'SUPP.CIS/EXE' => 'servizio',
        ];

        $categoria = $mappaCategorie[$codiceGerarchia->macro_attivita] ?? 'servizio';

        // Crea o aggiorna il tipo servizio corrispondente (isolato per unità)
        // Cerchiamo per codice + organizational_unit_id per mantenere l'isolamento
        TipoServizio::withoutGlobalScope(OrganizationalUnitScope::class)
            ->updateOrCreate(
                [
                    'codice' => $codiceGerarchia->codice,
                    'organizational_unit_id' => $codiceGerarchia->organizational_unit_id,
                ],
                [
                    'nome' => $codiceGerarchia->attivita_specifica,
                    'descrizione' => $codiceGerarchia->descrizione_impiego ?? $codiceGerarchia->attivita_specifica,
                    'colore_badge' => $codiceGerarchia->colore_badge,
                    'categoria' => $categoria,
                    'attivo' => $codiceGerarchia->attivo,
                    'ordine' => $codiceGerarchia->ordine
                ]
            );
    }
}

