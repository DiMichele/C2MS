<?php

/**
 * SUGECO: Sistema Unico di Gestione e Controllo
 * 
 * Questo file fa parte del sistema SUGECO per la gestione militare digitale.
 * 
 * @package    SUGECO
 * @subpackage Controllers
 * @version    2.1.0
 * @author     Michele Di Gennaro
 * @copyright 2025 Michele Di Gennaro
 * @license    Proprietary
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Militare;
use App\Models\Grado;
use App\Models\Plotone;
use App\Models\Polo;
use App\Models\Ruolo;
use App\Models\Mansione;
use App\Models\RuoloCertificati;
use App\Services\MilitareService;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Controller per la gestione dei militari
 * 
 * Questo controller gestisce tutte le operazioni CRUD sui militari,
 * delegando la logica business al MilitareService per mantenere
 * il controller snello e focalizzato sulla gestione delle richieste HTTP.
 * 
 * @package App\Http\Controllers
 * @version 1.0
 */
class MilitareController extends Controller
{
    /**
     * Servizio per la gestione dei militari
     * 
     * @var MilitareService
     */
    protected $militareService;

    /**
     * Costruttore del controller
     * 
     * @param MilitareService $militareService Servizio per la gestione dei militari
     */
    public function __construct(MilitareService $militareService)
    {
        $this->militareService = $militareService;
    }

    /**
     * Mostra l'elenco dei militari con filtri e paginazione
     * 
     * @param Request $request Richiesta HTTP con eventuali filtri
     * @return \Illuminate\View\View Vista con l'elenco dei militari
     */
    public function index(Request $request)
    {
        try {
            // Delega al service la logica di ricerca e filtri
            $result = $this->militareService->getFilteredMilitari($request);
            
            return view('militare.index', $result);
            
        } catch (\Exception $e) {
            Log::error('Errore nel caricamento dell\'elenco militari', [
                'error' => $e->getMessage(),
                'filters' => $request->all()
            ]);
            
            return redirect()->route('dashboard')
                ->withErrors(['error' => 'Errore nel caricamento dell\'elenco militari']);
        }
    }

    /**
     * Mostra i dettagli di un militare specifico
     * 
     * @param Militare $militare Modello del militare da visualizzare
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse Vista dettagli o redirect in caso di errore
     */
    public function show(Militare $militare)
    {
        try {
            // Carica le relazioni necessarie (solo quelle che esistono)
            $militare->load([
                'grado', 
                'plotone', 
                'polo', 
                'mansione', 
                'ruolo',
                'scadenza',
                'valutazioni'
                // 'assenze', // Tabella non esiste
                // 'eventi'   // Controllare se esiste
            ]);
            
            // Ottieni la valutazione del militare (se esiste)
            $valutazioneUtente = $militare->valutazioni->first();
            
            return view('militare.show', compact('militare', 'valutazioneUtente'));
            
        } catch (\Exception $e) {
            Log::error('Errore nel caricamento del militare', [
                'militare_id' => $militare->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('anagrafica.index')
                ->withErrors(['error' => 'Errore nel caricamento del militare: ' . $e->getMessage()]);
        }
    }

    /**
     * Mostra il form per creare un nuovo militare
     * 
     * @return \Illuminate\View\View Vista del form di creazione
     */
    public function create()
    {
        $data = $this->militareService->getFormData();
        
        return view('militare.form', $data);
    }

    /**
     * Memorizza un nuovo militare nel database
     * 
     * @param Request $request Richiesta HTTP con i dati del militare
     * @return \Illuminate\Http\RedirectResponse Redirect con messaggio di successo o errore
     */
    public function store(Request $request)
    {
        try {
            $this->militareService->createMilitare($request->all());
            
            return redirect()->route('anagrafica.index')
                ->with('success', 'Militare creato con successo!');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Errori di validazione - mostra messaggi user-friendly
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
                
        } catch (\Illuminate\Database\QueryException $e) {
            // Errori database - traduci in messaggi comprensibili
            Log::error('Errore database nella creazione del militare', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            
            $message = $this->translateDatabaseError($e);
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $message]);
                
        } catch (\Exception $e) {
            Log::error('Errore nella creazione del militare', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Si è verificato un errore durante la creazione del militare. Riprova.']);
        }
    }
    
    /**
     * Traduce gli errori del database in messaggi user-friendly
     */
    private function translateDatabaseError(\Illuminate\Database\QueryException $e): string
    {
        $message = $e->getMessage();
        
        // Codice fiscale nullo o duplicato
        if (str_contains($message, 'codice_fiscale') && str_contains($message, 'cannot be null')) {
            return 'Il codice fiscale è obbligatorio. Compila tutti i campi anagrafici per calcolarlo automaticamente.';
        }
        
        if (str_contains($message, 'codice_fiscale') && str_contains($message, 'Duplicate entry')) {
            return 'Questo codice fiscale è già presente nel sistema. Verifica i dati inseriti.';
        }
        
        // Vincoli di chiave esterna
        if (str_contains($message, 'foreign key constraint')) {
            return 'Uno dei valori selezionati non è valido. Verifica i campi del form.';
        }
        
        // Errore generico
        return 'Si è verificato un errore durante il salvataggio. Verifica i dati e riprova.';
    }

    /**
     * Mostra il form per modificare un militare esistente
     * 
     * @param Militare $militare Modello del militare da modificare
     * @return \Illuminate\View\View Vista del form di modifica
     */
    public function edit(Militare $militare)
    {
        $data = $this->militareService->getFormData($militare);
        
        return view('militare.form', $data);
    }

    /**
     * Aggiorna un militare esistente
     * 
     * @param Request $request Richiesta HTTP con i dati aggiornati
     * @param int $id ID del militare da aggiornare
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse Risposta JSON per AJAX o redirect per form
     */
    public function update(Request $request, $id)
    {
        try {
            // Trova il militare (senza scope per verificare esistenza)
            $militare = Militare::withoutGlobalScopes()->findOrFail($id);
            
            // VERIFICA PERMESSI: Solo owner possono modificare
            if (!$militare->isEditableBy(auth()->user())) {
                Log::warning('Tentativo di modifica militare non autorizzato', [
                    'user_id' => auth()->id(),
                    'user_compagnia' => auth()->user()->compagnia_id,
                    'militare_id' => $id,
                    'militare_compagnia' => $militare->compagnia_id,
                    'relation_type' => $militare->getRelationType(auth()->user())
                ]);
                
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Non hai i permessi per modificare questo militare. I militari acquisiti sono in sola lettura.'
                    ], 403);
                }
                
                return redirect()->back()
                    ->with('error', 'Non hai i permessi per modificare questo militare. I militari acquisiti sono in sola lettura.');
            }
            
            if ($request->ajax() || $request->wantsJson()) {
                $result = $this->militareService->updateMilitareAjax($id, $request->all());
                return response()->json($result);
            }
            
            $this->militareService->updateMilitare($id, $request->all());
            
            return redirect()->route('anagrafica.show', $id)
                ->with('success', 'Militare aggiornato con successo!');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => implode(', ', collect($e->errors())->flatten()->all())
                ], 422);
            }
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
                
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Errore database nell\'aggiornamento del militare', [
                'militare_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            $message = $this->translateDatabaseError($e);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 500);
            }
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $message]);
                
        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiornamento del militare', [
                'militare_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Si è verificato un errore durante l\'aggiornamento.'
                ], 500);
            }
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Si è verificato un errore durante l\'aggiornamento. Riprova.']);
        }
    }

    /**
     * Elimina un militare dal database
     * 
     * @param Militare $militare Il militare da eliminare (model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect con messaggio di successo o errore
     */
    public function destroy(Militare $militare)
    {
        try {
            Log::info('Tentativo di eliminazione militare', [
                'militare_id' => $militare->id,
                'militare_nome' => $militare->cognome . ' ' . $militare->nome
            ]);
            
            $result = $this->militareService->deleteMilitare($militare);
            
            if (!$result) {
                Log::error('Eliminazione militare fallita - servizio ha ritornato false', [
                    'militare_id' => $militare->id
                ]);
                
                return redirect()->route('anagrafica.index')
                    ->withErrors(['error' => 'Impossibile eliminare il militare. Controlla i log per i dettagli.']);
            }
            
            Log::info('Militare eliminato con successo', ['militare_id' => $militare->id]);
            
            return redirect()->route('anagrafica.index')
                ->with('success', 'Militare eliminato con successo.');
                
        } catch (\Exception $e) {
            Log::error('Errore nell\'eliminazione del militare', [
                'militare_id' => $militare->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('anagrafica.index')
                ->withErrors(['error' => 'Errore durante l\'eliminazione: ' . $e->getMessage()]);
        }
    }

    /**
     * Ricerca militari tramite AJAX
     * 
     * @param Request $request Richiesta HTTP con i parametri di ricerca
     * @return \Illuminate\Http\JsonResponse Risposta JSON con i risultati della ricerca
     */
    public function search(Request $request)
    {
        try {
            $results = $this->militareService->searchMilitari($request->get('query', ''));
            
            return response()->json([
                'success' => true,
                'data' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore nella ricerca militari', [
                'query' => $request->get('query'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nella ricerca: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aggiorna le note di un militare
     * 
     * @param Request $request Richiesta HTTP con le nuove note
     * @param Militare $militare Modello del militare
     * @return \Illuminate\Http\JsonResponse Risposta JSON con il risultato
     */
    public function updateNotes(Request $request, Militare $militare)
    {
        try {
            // VERIFICA PERMESSI: Solo owner possono modificare
            if (!$militare->isEditableBy(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non hai i permessi per modificare questo militare.'
                ], 403);
            }
            
            $this->militareService->updateNotes($militare, $request->get('note'));
            
            return response()->json([
                'success' => true,
                'message' => 'Note aggiornate con successo!'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiornamento delle note', [
                'militare_id' => $militare->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memorizza una nuova valutazione per un militare
     * 
     * @param Request $request Richiesta HTTP con i dati della valutazione
     * @param Militare $militare Modello del militare
     * @return \Illuminate\Http\RedirectResponse Redirect con messaggio di successo o errore
     */
    public function storeValutazione(Request $request, Militare $militare)
    {
        try {
            $this->militareService->saveValutazione($militare, $request->all());
            
            return redirect()->route('anagrafica.show', $militare->id)
                ->with('success', 'Valutazione salvata con successo!');
                
        } catch (\Exception $e) {
            Log::error('Errore nel salvataggio della valutazione', [
                'militare_id' => $militare->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Errore nel salvataggio: ' . $e->getMessage()]);
        }
    }

    /**
     * Aggiorna un singolo campo della valutazione tramite AJAX
     * 
     * @param Request $request Richiesta HTTP con il campo e valore
     * @param Militare $militare Modello del militare
     * @return \Illuminate\Http\JsonResponse Risposta JSON con il risultato
     */
    public function updateValutazioneField(Request $request, Militare $militare)
    {
        try {
            $result = $this->militareService->updateValutazioneField(
                $militare,
                $request->get('field'),
                $request->get('value')
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Campo aggiornato con successo!'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiornamento del campo valutazione', [
                'militare_id' => $militare->id,
                'field' => $request->get('field'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ottiene la foto del militare
     * 
     * @param int $id ID del militare
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse Immagine o risposta di errore
     */
    public function getFoto($id)
    {
        try {
            return $this->militareService->getFoto($id);
            
        } catch (\Exception $e) {
            Log::error('Errore nel recupero della foto', [
                'militare_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            // Restituisce un'immagine SVG di errore invece di JSON
            $svg = '<svg width="150" height="150" xmlns="http://www.w3.org/2000/svg">
                        <rect width="150" height="150" fill="#dc3545"/>
                        <text x="75" y="75" font-family="Arial" font-size="12" fill="white" text-anchor="middle" dy=".3em">
                            Errore caricamento
                        </text>
                    </svg>';
                    
            return response($svg, 500, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'no-cache',
            ]);
        }
    }

    /**
     * Carica una nuova foto per il militare
     * 
     * @param Request $request Richiesta HTTP con il file della foto
     * @param int $id ID del militare
     * @return \Illuminate\Http\JsonResponse Risposta JSON con il risultato
     */
    public function uploadFoto(Request $request, $id)
    {
        try {
            // Validazione del file
            $request->validate([
                'foto' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120'
            ]);
            
            // Trova il militare
            $militare = Militare::findOrFail($id);
            
            // Carica la foto
            $result = $this->militareService->uploadFoto($militare, $request->file('foto'));
            
            return response()->json([
                'success' => true,
                'message' => 'Foto caricata con successo!',
                'data' => $result
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore di validazione: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Errore nell\'upload della foto', [
                'militare_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina la foto del militare
     * 
     * @param int $id ID del militare
     * @return \Illuminate\Http\JsonResponse Risposta JSON con il risultato
     */
    public function deleteFoto($id)
    {
        try {
            // Trova il militare
            $militare = Militare::findOrFail($id);
            
            // Elimina la foto
            $result = $this->militareService->deleteFoto($militare);
            
            return response()->json([
                'success' => true,
                'message' => 'Foto eliminata con successo!'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore nell\'eliminazione della foto', [
                'militare_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'eliminazione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ottiene i dati del militare per API
     * 
     * @param Militare $militare Modello del militare
     * @return \Illuminate\Http\JsonResponse Risposta JSON con i dati del militare
     */
    public function getApiData(Militare $militare)
    {
        try {
            $data = $this->militareService->getApiData($militare->id);
            
            return response()->json($data);
            
        } catch (\Exception $e) {
            Log::error('Errore nel recupero dati API militare', [
                'militare_id' => $militare->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero dati'
            ], 500);
        }
    }

    /**
     * Esporta i militari in Excel con i filtri applicati
     * 
     * @param Request $request Richiesta HTTP
     * @return \Illuminate\Http\Response File Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            Log::info('Export Excel Anagrafica iniziato');
            
            // Ottieni i militari filtrati usando lo stesso servizio dell'index
            $data = $this->militareService->getFilteredMilitari($request);
            $militari = $data['militari'];
            
            Log::info('Militari recuperati: ' . $militari->count());
            
            // Crea un nuovo spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Imposta il titolo del foglio
            $sheet->setTitle('Anagrafica Militari');
            
            // Intestazioni
            $headers = [
                'A1' => 'Compagnia',
                'B1' => 'Grado',
                'C1' => 'Cognome', 
                'D1' => 'Nome',
                'E1' => 'Plotone',
                'F1' => 'Ufficio',
                'G1' => 'Incarico',
                'H1' => 'Patenti',
                'I1' => 'NOS',
                'J1' => 'Anzianità',
                'K1' => 'Data di Nascita',
                'L1' => 'Email Istituzionale',
                'M1' => 'Cellulare'
            ];
            
            // Imposta le intestazioni
            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }
            
            // Stile per le intestazioni
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2C3E50']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ];
            
            $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);
            $sheet->getStyle('A1:M1')->getAlignment()->setWrapText(true);
            $sheet->getRowDimension('1')->setRowHeight(35);
            
            // Dati dei militari
            $row = 2;
            foreach ($militari as $militare) {
                $sheet->setCellValue('A' . $row, $militare->compagnia ? $militare->compagnia . 'a' : '');
                $sheet->setCellValue('B' . $row, $militare->grado->sigla ?? '');  // Usa sigla invece di nome
                $sheet->setCellValue('C' . $row, $militare->cognome);
                $sheet->setCellValue('D' . $row, $militare->nome);
                $sheet->setCellValue('E' . $row, $militare->plotone->nome ?? '');
                $sheet->setCellValue('F' . $row, $militare->polo->nome ?? '');
                $sheet->setCellValue('G' . $row, $militare->mansione->nome ?? '');
                
                // Patenti - mostra tutte le patenti separate da spazio
                $patenti = $militare->patenti->pluck('categoria')->toArray();
                $sheet->setCellValue('H' . $row, !empty($patenti) ? implode(' ', $patenti) : '');
                
                $sheet->setCellValue('I' . $row, $militare->nos_status ? ucfirst($militare->nos_status) : '');
                $sheet->setCellValue('J' . $row, $militare->anzianita ? (is_object($militare->anzianita) ? $militare->anzianita->format('d/m/Y') : $militare->anzianita) : '');
                $sheet->setCellValue('K' . $row, $militare->data_nascita ? $militare->data_nascita->format('d/m/Y') : '');
                $sheet->setCellValue('L' . $row, $militare->email_istituzionale ?? '');
                $sheet->setCellValue('M' . $row, $militare->telefono ?? '');
                
                $row++;
            }
            
            // Stile per i dati
            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ];
            
            if ($row > 2) {
                $sheet->getStyle('A2:M' . ($row - 1))->applyFromArray($dataStyle);
            }
            
            // Imposta la larghezza delle colonne (ottimizzate per evitare troncamenti)
            $columnWidths = [
                'A' => 12,  // Compagnia
                'B' => 12,  // Grado (sigla)
                'C' => 25,  // Cognome
                'D' => 22,  // Nome
                'E' => 25,  // Plotone
                'F' => 35,  // Ufficio
                'G' => 35,  // Incarico
                'H' => 18,  // Patenti
                'I' => 10,  // NOS
                'J' => 15,  // Anzianità
                'K' => 18,  // Data di Nascita
                'L' => 40,  // Email
                'M' => 20   // Cellulare
            ];
            
            foreach ($columnWidths as $column => $width) {
                $sheet->getColumnDimension($column)->setWidth($width);
            }
            
            // Crea il writer
            $writer = new Xlsx($spreadsheet);
            
            // Nome del file
            $filename = 'anagrafica_militari_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
            
            // Crea il file e invia come download
            $tempFile = tempnam(sys_get_temp_dir(), 'anagrafica_');
            $writer->save($tempFile);
            
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
                
        } catch (\Exception $e) {
            Log::error('Errore nell\'export Excel anagrafica', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Errore durante l\'esportazione del file Excel.');
        }
    }

    /**
     * Aggiorna un singolo campo di un militare via AJAX
     * 
     * @param Request $request Richiesta HTTP
     * @param Militare $militare Modello del militare
     * @return \Illuminate\Http\JsonResponse Risposta JSON
     */
    public function updateField(Request $request, Militare $militare)
    {
        try {
            // VERIFICA PERMESSI: Solo owner possono modificare
            if (!$militare->isEditableBy(auth()->user())) {
                Log::warning('Tentativo di modifica campo militare non autorizzato', [
                    'user_id' => auth()->id(),
                    'militare_id' => $militare->id,
                    'field' => $request->input('field'),
                    'relation_type' => $militare->getRelationType(auth()->user())
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Non hai i permessi per modificare questo militare. I militari acquisiti sono in sola lettura.'
                ], 403);
            }
            
            $field = $request->input('field');
            $value = $request->input('value');
            
            if (is_null($field)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campo non specificato'
                ], 400);
            }
            
            // Mapping dei campi frontend -> database
            $fieldMapping = [
                'compagnia' => 'compagnia_id',
                'grado' => 'grado_id',
                'plotone' => 'plotone_id',
                'ufficio' => 'polo_id',
                'incarico' => 'mansione_id',
            ];
            
            // Applica il mapping se necessario
            $dbField = $fieldMapping[$field] ?? $field;
            
            // Lista dei campi consentiti per l'aggiornamento (nomi database)
            $allowedFields = [
                'compagnia_id', 'grado_id', 'cognome', 'nome', 'plotone_id', 
                'polo_id', 'mansione_id', 'ruolo_id', 'nos_status', 
                'data_nascita', 'codice_fiscale', 'email', 'telefono', 'note',
                'email_istituzionale', 'anzianita'
            ];
            
            if (!in_array($dbField, $allowedFields)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campo non consentito: ' . $dbField
                ], 400);
            }
            
            // Converti il valore in null se è stringa vuota o "0" per i campi nullable
            $nullableFields = ['compagnia_id', 'grado_id', 'plotone_id', 'polo_id', 'mansione_id', 'ruolo_id'];
            if (in_array($dbField, $nullableFields)) {
                if ($value === '' || $value === null || $value === '0' || $value === 0) {
                    $value = null;
                }
            }
            
            // Converti in intero per i campi ID (solo se non è null)
            $idFields = ['compagnia_id', 'grado_id', 'plotone_id', 'polo_id', 'mansione_id', 'ruolo_id'];
            if (in_array($dbField, $idFields) && $value !== null) {
                // Converti in intero, gestendo anche stringhe numeriche
                $intValue = is_numeric($value) ? (int) $value : null;
                
                if ($intValue === null || $intValue <= 0) {
                    // Se il valore non è un numero valido, imposta null
                    $value = null;
                } else {
                    $value = $intValue;
                    
                    // Valida che il valore esista nella tabella di riferimento (solo per foreign keys)
                    // Nota: per compagnia_id, la validazione è opzionale perché la view usa un array hardcoded
                    if ($dbField === 'compagnia_id') {
                        // Verifica se la compagnia esiste, ma non blocca se non esiste (per compatibilità)
                        $compagnia = \App\Models\Compagnia::find($value);
                        if (!$compagnia) {
                            Log::warning('Compagnia non trovata nel database', [
                                'compagnia_id' => $value,
                                'militare_id' => $militare->id
                            ]);
                            // Non bloccare, permettere il salvataggio comunque
                            // Il database gestirà il foreign key constraint se necessario
                        }
                    } elseif ($dbField === 'grado_id') {
                        if (!\App\Models\Grado::find($value)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Grado non valido'
                            ], 400);
                        }
                    } elseif ($dbField === 'plotone_id') {
                        if (!\App\Models\Plotone::find($value)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Plotone non valido'
                            ], 400);
                        }
                    } elseif ($dbField === 'polo_id') {
                        if (!\App\Models\Polo::find($value)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Polo non valido'
                            ], 400);
                        }
                    } elseif ($dbField === 'mansione_id') {
                        if (!\App\Models\Mansione::find($value)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Mansione non valida'
                            ], 400);
                        }
                    }
                }
            }
            
            // Se cambia la compagnia, azzera il plotone (perché non appartiene più alla compagnia)
            if ($dbField === 'compagnia_id') {
                $oldCompagniaId = $militare->compagnia_id;
                // Confronta usando == per gestire null correttamente
                if ($value != $oldCompagniaId) {
                    $militare->plotone_id = null;
                }
            }
            
            // Aggiorna il campo
            try {
                $militare->$dbField = $value;
                $militare->save();
            } catch (\Illuminate\Database\QueryException $e) {
                // Se c'è un errore di foreign key, prova a gestirlo
                if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                    Log::error('Foreign key constraint violation', [
                        'field' => $dbField,
                        'value' => $value,
                        'militare_id' => $militare->id,
                        'error' => $e->getMessage()
                    ]);
                    throw $e; // Rilancia per essere gestito dal catch esterno
                }
                throw $e;
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Campo aggiornato con successo',
                'plotone_reset' => ($dbField === 'compagnia_id') // Indica se il plotone è stato resettato
            ]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Errore database nell\'aggiornamento campo militare', [
                'militare_id' => $militare->id,
                'field' => $request->input('field'),
                'value' => $request->input('value'),
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings()
            ]);
            
            $errorMessage = 'Errore durante l\'aggiornamento';
            // Se è un errore di foreign key constraint, fornisci un messaggio più chiaro
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $errorMessage = 'Il valore selezionato non è valido o non esiste nel database';
            } elseif (strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
                $errorMessage = 'Violazione di vincolo di integrità: il valore non è valido';
            }
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error_detail' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiornamento campo militare', [
                'militare_id' => $militare->id,
                'field' => $request->input('field'),
                'value' => $request->input('value'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage(),
                'error_detail' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Aggiorna il valore di un campo custom per un militare
     */
    public function updateCampoCustom(Request $request, Militare $militare)
    {
        try {
            // VERIFICA PERMESSI: Solo owner possono modificare
            if (!$militare->isEditableBy(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non hai i permessi per modificare questo militare.'
                ], 403);
            }
            
            $nomeCampo = $request->input('nome_campo');
            $valore = $request->input('valore');
            
            $success = $militare->setValoreCampoCustom($nomeCampo, $valore);
            
            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campo custom non trovato'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Campo aggiornato con successo'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiornamento campo custom', [
                'militare_id' => $militare->id,
                'nome_campo' => $request->input('nome_campo'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento'
            ], 500);
        }
    }

    /**
     * Ottiene i plotoni filtrati per compagnia
     * 
     * @param Request $request Richiesta HTTP
     * @return \Illuminate\Http\JsonResponse Risposta JSON con i plotoni
     */
    public function getPlotoniPerCompagnia(Request $request)
    {
        try {
            $compagniaId = $request->input('compagnia_id');
            
            if (!$compagniaId) {
                // Se non c'è compagnia, restituisci tutti i plotoni
                $plotoni = Plotone::orderBy('nome')->get();
            } else {
                // Filtra per compagnia
                $plotoni = Plotone::where('compagnia_id', $compagniaId)
                    ->orderBy('nome')
                    ->get();
            }
            
            return response()->json([
                'success' => true,
                'plotoni' => $plotoni
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore nel recupero plotoni per compagnia', [
                'compagnia_id' => $request->input('compagnia_id'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero dei plotoni'
            ], 500);
        }
    }

    /**
     * Aggiunge una patente a un militare
     * 
     * @param Request $request Richiesta HTTP
     * @param Militare $militare Militare (model binding)
     * @return \Illuminate\Http\JsonResponse Risposta JSON
     */
    public function addPatente(Request $request, Militare $militare)
    {
        try {
            $patente = $request->input('patente');
            
            // Verifica che la patente sia valida
            if (!in_array($patente, ['2', '3', '4', '5', '6'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patente non valida'
                ], 400);
            }
            
            // Verifica se la patente esiste già
            $exists = $militare->patenti()->where('categoria', $patente)->exists();
            
            if ($exists) {
                return response()->json([
                    'success' => true,
                    'message' => 'Patente già presente'
                ]);
            }
            
            // Crea la patente
            $militare->patenti()->create([
                'categoria' => $patente,
                'tipo' => 'MIL',
                'data_ottenimento' => now(),
                'data_scadenza' => now()->addYears(10)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Patente aggiunta con successo'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiunta patente', [
                'militare_id' => $militare->id,
                'patente' => $request->input('patente'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiunta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rimuove una patente da un militare
     * 
     * @param Request $request Richiesta HTTP
     * @param Militare $militare Militare (model binding)
     * @return \Illuminate\Http\JsonResponse Risposta JSON
     */
    public function removePatente(Request $request, Militare $militare)
    {
        try {
            $patente = $request->input('patente');
            
            // Elimina la patente
            $deleted = $militare->patenti()->where('categoria', $patente)->delete();
            
            return response()->json([
                'success' => true,
                'message' => $deleted > 0 ? 'Patente rimossa con successo' : 'Patente non trovata'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore nella rimozione patente', [
                'militare_id' => $militare->id,
                'patente' => $request->input('patente'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la rimozione: ' . $e->getMessage()
            ], 500);
        }
    }
}
