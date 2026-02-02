<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\TeatroOperativo;
use App\Models\TeatroOperativoMilitare;
use App\Models\ScadenzaApprontamento;
use App\Models\Compagnia;
use App\Models\Polo;
use App\Models\Mansione;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Controller per la gestione degli Impieghi del Personale
 * 
 * Gestisce i Teatri Operativi e l'assegnazione dei militari
 * con stati bozza/confermato.
 */
class ImpieghiPersonaleController extends Controller
{
    /**
     * Mostra la pagina degli Impieghi Personale
     */
    public function index(Request $request)
    {
        // Dati per i filtri
        $compagnie = Compagnia::orderBy('nome')->get();
        $uffici = Polo::orderBy('nome')->get();
        $incarichi = Mansione::orderBy('nome')->get();
        
        // Verifica permessi
        $canEdit = auth()->user()->hasPermission('impieghi.edit') 
                || auth()->user()->hasPermission('admin.access');

        return view('impieghi-personale.index', compact(
            'compagnie',
            'uffici',
            'incarichi',
            'canEdit'
        ));
    }

    /**
     * API: Ottiene la lista dei teatri operativi con conteggi
     */
    public function apiGetTeatri()
    {
        $teatri = TeatroOperativo::attivi()
            ->with('mountingCompagnia')
            ->withCount(['militari'])
            ->withCount(['militari as militari_confermati_count' => function($q) {
                $q->where('teatro_operativo_militare.stato', 'confermato');
            }])
            ->orderBy('nome')
            ->get()
            ->map(function($teatro) {
                return [
                    'id' => $teatro->id,
                    'nome' => $teatro->nome,
                    'militari_count' => $teatro->militari_count,
                    'militari_confermati_count' => $teatro->militari_confermati_count,
                    'stato_globale' => $teatro->militari_count > 0 
                        ? ($teatro->militari_confermati_count == $teatro->militari_count ? 'confermato' : 'bozza')
                        : 'vuoto',
                    'data_inizio' => $teatro->data_inizio?->format('d/m/Y'),
                    'data_fine' => $teatro->data_fine?->format('d/m/Y'),
                    'stato' => $teatro->stato,
                    'mounting_compagnia_id' => $teatro->mounting_compagnia_id,
                    'mounting_compagnia_nome' => $teatro->mountingCompagnia?->nome
                ];
            });

        return response()->json(['success' => true, 'teatri' => $teatri]);
    }

    /**
     * API: Ottiene tutti i militari con le loro assegnazioni ai teatri
     */
    public function apiGetMilitari(Request $request)
    {
        // Query base con tutte le relazioni necessarie
        $query = Militare::withVisibilityFlags()
            ->with(['grado', 'compagnia', 'polo', 'mansione'])
            ->leftJoin('teatro_operativo_militare', 'militari.id', '=', 'teatro_operativo_militare.militare_id')
            ->leftJoin('teatri_operativi', 'teatro_operativo_militare.teatro_operativo_id', '=', 'teatri_operativi.id')
            ->select(
                'militari.*',
                'teatro_operativo_militare.teatro_operativo_id',
                'teatro_operativo_militare.stato as stato_assegnazione',
                'teatro_operativo_militare.ruolo',
                'teatri_operativi.nome as teatro_nome'
            );

        // Filtro per teatro
        if ($request->filled('teatro_id')) {
            if ($request->teatro_id === 'non_assegnati') {
                $query->whereNull('teatro_operativo_militare.teatro_operativo_id');
            } else {
                $query->where('teatro_operativo_militare.teatro_operativo_id', $request->teatro_id);
            }
        }

        // Filtro per stato assegnazione
        if ($request->filled('stato') && $request->teatro_id !== 'non_assegnati') {
            $query->where('teatro_operativo_militare.stato', $request->stato);
        }

        // Filtro per compagnia
        if ($request->filled('compagnia_id')) {
            $query->where('militari.compagnia_id', $request->compagnia_id);
        }

        // Filtro per ufficio
        if ($request->filled('ufficio_id')) {
            $query->where('militari.polo_id', $request->ufficio_id);
        }

        // Filtro per incarico
        if ($request->filled('incarico_id')) {
            $query->where('militari.mansione_id', $request->incarico_id);
        }

        // Ricerca testuale
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('militari.cognome', 'like', "%{$search}%")
                  ->orWhere('militari.nome', 'like', "%{$search}%");
            });
        }

        $militari = $query->orderBy('militari.cognome')
                         ->orderBy('militari.nome')
                         ->get()
                         ->map(function($m) {
                             return [
                                 'id' => $m->id,
                                 'grado' => $m->grado->sigla ?? '-',
                                 'cognome' => $m->cognome,
                                 'nome' => $m->nome,
                                 'compagnia' => $m->compagnia->nome ?? '-',
                                 'ufficio' => $m->polo->nome ?? '-',
                                 'incarico' => $m->mansione->nome ?? '-',
                                 'telefono' => $m->telefono ?? '-',
                                 'teatro_id' => $m->teatro_operativo_id,
                                 'teatro_nome' => $m->teatro_nome,
                                 'stato_assegnazione' => $m->stato_assegnazione,
                                 'ruolo' => $m->ruolo
                             ];
                         });

        // Conteggi per il riepilogo
        $totale = $militari->count();
        $assegnati = $militari->whereNotNull('teatro_id')->count();
        $nonAssegnati = $militari->whereNull('teatro_id')->count();
        $confermati = $militari->where('stato_assegnazione', 'confermato')->count();
        $bozze = $militari->where('stato_assegnazione', 'bozza')->count();

        return response()->json([
            'success' => true,
            'militari' => $militari->values(),
            'conteggi' => [
                'totale' => $totale,
                'assegnati' => $assegnati,
                'non_assegnati' => $nonAssegnati,
                'confermati' => $confermati,
                'bozze' => $bozze
            ]
        ]);
    }

    /**
     * API: Ottiene i dettagli di un teatro specifico
     */
    public function apiGetTeatro($id)
    {
        $teatro = TeatroOperativo::with(['militari' => function($query) {
            $query->with(['grado', 'compagnia', 'polo', 'mansione'])
                  ->orderBy('cognome')
                  ->orderBy('nome');
        }])->findOrFail($id);

        $militari = $teatro->militari->map(function($m) {
            return [
                'id' => $m->id,
                'grado' => $m->grado->sigla ?? '-',
                'cognome' => $m->cognome,
                'nome' => $m->nome,
                'compagnia' => $m->compagnia->nome ?? '-',
                'ufficio' => $m->polo->nome ?? '-',
                'incarico' => $m->mansione->nome ?? '-',
                'telefono' => $m->telefono ?? '-',
                'stato_assegnazione' => $m->pivot->stato,
                'ruolo' => $m->pivot->ruolo
            ];
        });

        return response()->json([
            'success' => true,
            'teatro' => [
                'id' => $teatro->id,
                'nome' => $teatro->nome,
                'data_inizio' => $teatro->data_inizio?->format('Y-m-d'),
                'data_fine' => $teatro->data_fine?->format('Y-m-d'),
                'stato' => $teatro->stato,
                'mounting_compagnia_id' => $teatro->mounting_compagnia_id
            ],
            'militari' => $militari,
            'conteggi' => [
                'totale' => $militari->count(),
                'confermati' => $militari->where('stato_assegnazione', 'confermato')->count(),
                'bozze' => $militari->where('stato_assegnazione', 'bozza')->count()
            ]
        ]);
    }

    /**
     * API: Ottiene militari non assegnati a un teatro specifico (per modal aggiungi)
     */
    public function apiGetMilitariDisponibili($teatroId)
    {
        $assegnatiIds = TeatroOperativoMilitare::where('teatro_operativo_id', $teatroId)
            ->pluck('militare_id')
            ->toArray();

        $militari = Militare::withVisibilityFlags()
            ->with(['grado', 'compagnia'])
            ->whereNotIn('id', $assegnatiIds)
            ->orderBy('cognome')
            ->orderBy('nome')
            ->get()
            ->map(function($m) {
                return [
                    'id' => $m->id,
                    'grado' => $m->grado->sigla ?? '',
                    'cognome' => $m->cognome,
                    'nome' => $m->nome,
                    'compagnia' => $m->compagnia->nome ?? 'N/D'
                ];
            });

        return response()->json(['success' => true, 'militari' => $militari]);
    }

    /**
     * Crea un nuovo Teatro Operativo
     */
    public function storeTeatro(Request $request)
    {
        if (!auth()->user()->hasPermission('impieghi.edit') && !auth()->user()->hasPermission('admin.access')) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per creare teatri operativi'
            ], 403);
        }

        $request->validate([
            'nome' => 'required|string|max:100',
            'codice' => 'nullable|string|max:20|unique:teatri_operativi,codice',
            'descrizione' => 'nullable|string',
            'data_inizio' => 'nullable|date',
            'data_fine' => 'nullable|date|after_or_equal:data_inizio',
            'colore_badge' => 'nullable|string|max:7',
            'mounting_compagnia_id' => 'nullable|exists:compagnie,id'
        ]);

        try {
            $teatro = TeatroOperativo::create([
                'nome' => $request->nome,
                'codice' => $request->codice,
                'descrizione' => $request->descrizione,
                'data_inizio' => $request->data_inizio,
                'data_fine' => $request->data_fine,
                'colore_badge' => $request->colore_badge ?? '#0a2342',
                'stato' => 'attivo',
                'compagnia_id' => auth()->user()->compagnia_id,
                'mounting_compagnia_id' => $request->mounting_compagnia_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Teatro Operativo creato con successo',
                'teatro' => $teatro
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la creazione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aggiorna un Teatro Operativo
     */
    public function updateTeatro(Request $request, $id)
    {
        if (!auth()->user()->hasPermission('impieghi.edit') && !auth()->user()->hasPermission('admin.access')) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per modificare i teatri operativi'
            ], 403);
        }

        $teatro = TeatroOperativo::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:100',
            'codice' => 'nullable|string|max:20|unique:teatri_operativi,codice,' . $id,
            'descrizione' => 'nullable|string',
            'data_inizio' => 'nullable|date',
            'data_fine' => 'nullable|date|after_or_equal:data_inizio',
            'colore_badge' => 'nullable|string|max:7',
            'stato' => 'nullable|in:attivo,completato,sospeso,pianificato',
            'mounting_compagnia_id' => 'nullable|exists:compagnie,id'
        ]);

        try {
            $teatro->update($request->only([
                'nome', 'codice', 'descrizione', 'data_inizio', 
                'data_fine', 'colore_badge', 'stato', 'mounting_compagnia_id'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Teatro Operativo aggiornato con successo',
                'teatro' => $teatro
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina un Teatro Operativo
     */
    public function destroyTeatro($id)
    {
        if (!auth()->user()->hasPermission('admin.access')) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per eliminare i teatri operativi'
            ], 403);
        }

        try {
            $teatro = TeatroOperativo::findOrFail($id);
            $teatro->delete();

            return response()->json([
                'success' => true,
                'message' => 'Teatro Operativo eliminato con successo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assegna un militare a un Teatro Operativo
     */
    public function assegnaMilitare(Request $request)
    {
        if (!auth()->user()->hasPermission('impieghi.edit') && !auth()->user()->hasPermission('admin.access')) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per assegnare militari'
            ], 403);
        }

        $request->validate([
            'teatro_operativo_id' => 'required|exists:teatri_operativi,id',
            'militare_id' => 'required|exists:militari,id',
            'stato' => 'nullable|in:bozza,confermato',
            'ruolo' => 'nullable|string|max:100',
            'note' => 'nullable|string'
        ]);

        try {
            // Verifica che il militare non sia già assegnato
            $esistente = TeatroOperativoMilitare::where('teatro_operativo_id', $request->teatro_operativo_id)
                ->where('militare_id', $request->militare_id)
                ->first();
            
            if ($esistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Il militare è già assegnato a questo Teatro Operativo'
                ], 400);
            }

            $assegnazione = TeatroOperativoMilitare::create([
                'teatro_operativo_id' => $request->teatro_operativo_id,
                'militare_id' => $request->militare_id,
                'stato' => $request->stato ?? 'bozza',
                'ruolo' => $request->ruolo,
                'note' => $request->note,
                'data_assegnazione' => Carbon::today()
            ]);

            $militare = Militare::with('grado')->find($request->militare_id);

            return response()->json([
                'success' => true,
                'message' => 'Militare assegnato con successo',
                'assegnazione' => $assegnazione,
                'militare' => $militare
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'assegnazione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rimuove un militare da un Teatro Operativo
     */
    public function rimuoviMilitare(Request $request)
    {
        if (!auth()->user()->hasPermission('impieghi.edit') && !auth()->user()->hasPermission('admin.access')) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per rimuovere militari'
            ], 403);
        }

        $request->validate([
            'teatro_operativo_id' => 'required|exists:teatri_operativi,id',
            'militare_id' => 'required|exists:militari,id'
        ]);

        try {
            // Verifica se il militare ha altri teatri operativi confermati prima di rimuovere
            $altriTeatriConfermati = TeatroOperativoMilitare::where('militare_id', $request->militare_id)
                ->where('teatro_operativo_id', '!=', $request->teatro_operativo_id)
                ->where('stato', 'confermato')
                ->first();
            
            TeatroOperativoMilitare::where('teatro_operativo_id', $request->teatro_operativo_id)
                ->where('militare_id', $request->militare_id)
                ->delete();

            // Se non ci sono altri teatri confermati, rimuovi il teatro operativo dal CPT
            if (!$altriTeatriConfermati) {
                $scadenza = ScadenzaApprontamento::where('militare_id', $request->militare_id)->first();
                if ($scadenza) {
                    $scadenza->teatro_operativo = null;
                    $scadenza->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Militare rimosso dal Teatro Operativo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la rimozione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aggiorna lo stato di un'assegnazione (bozza/confermato)
     */
    public function updateStatoAssegnazione(Request $request)
    {
        if (!auth()->user()->hasPermission('impieghi.edit') && !auth()->user()->hasPermission('admin.access')) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per modificare lo stato'
            ], 403);
        }

        $request->validate([
            'teatro_operativo_id' => 'required|exists:teatri_operativi,id',
            'militare_id' => 'required|exists:militari,id',
            'stato' => 'required|in:bozza,confermato'
        ]);

        try {
            $assegnazione = TeatroOperativoMilitare::where('teatro_operativo_id', $request->teatro_operativo_id)
                ->where('militare_id', $request->militare_id)
                ->firstOrFail();
            
            $assegnazione->stato = $request->stato;
            $assegnazione->save();

            // Sincronizza il campo teatro_operativo nella tabella scadenze_approntamenti
            $this->sincronizzaTeatroOperativo($request->militare_id, $request->teatro_operativo_id, $request->stato);

            // Verifica se il teatro è ora completamente confermato
            $teatro = TeatroOperativo::find($request->teatro_operativo_id);
            $tuttiConfermati = $teatro->isTuttiConfermati();

            return response()->json([
                'success' => true,
                'message' => 'Stato aggiornato con successo',
                'stato' => $assegnazione->stato,
                'teatro_confermato' => $tuttiConfermati
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizza il campo teatro_operativo nella tabella scadenze_approntamenti
     */
    private function sincronizzaTeatroOperativo($militareId, $teatroId, $stato)
    {
        try {
            $teatro = TeatroOperativo::find($teatroId);
            
            if (!$teatro) {
                \Log::warning("Teatro Operativo non trovato per ID: {$teatroId}");
                return;
            }
            
            // Se confermato, imposta il nome del teatro
            // Se bozza, rimuovi il teatro operativo (null)
            $valoreTeatro = $stato === 'confermato' ? $teatro->nome : null;
            
            // Usa updateOrCreate per gestire sia creazione che aggiornamento
            ScadenzaApprontamento::updateOrCreate(
                ['militare_id' => $militareId],
                ['teatro_operativo' => $valoreTeatro]
            );
            
            \Log::info("Sincronizzato teatro operativo per militare {$militareId}: {$valoreTeatro}");
        } catch (\Exception $e) {
            // Log dell'errore ma non blocca l'operazione principale
            \Log::error('Errore sincronizzazione teatro operativo: ' . $e->getMessage(), [
                'militare_id' => $militareId,
                'teatro_id' => $teatroId,
                'stato' => $stato,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Conferma tutti i militari di un teatro
     */
    public function confermaTutti(Request $request)
    {
        if (!auth()->user()->hasPermission('impieghi.edit') && !auth()->user()->hasPermission('admin.access')) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per questa operazione'
            ], 403);
        }

        $request->validate([
            'teatro_operativo_id' => 'required|exists:teatri_operativi,id'
        ]);

        try {
            // Ottieni tutti i militari da confermare
            $assegnazioni = TeatroOperativoMilitare::where('teatro_operativo_id', $request->teatro_operativo_id)
                ->where('stato', 'bozza')
                ->get();
            
            // Aggiorna lo stato a confermato
            TeatroOperativoMilitare::where('teatro_operativo_id', $request->teatro_operativo_id)
                ->update(['stato' => 'confermato']);
            
            // Sincronizza il campo teatro_operativo per tutti i militari
            foreach ($assegnazioni as $assegnazione) {
                $this->sincronizzaTeatroOperativo($assegnazione->militare_id, $request->teatro_operativo_id, 'confermato');
            }

            return response()->json([
                'success' => true,
                'message' => 'Tutti i militari sono stati confermati'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aggiorna il ruolo/note di un'assegnazione
     */
    public function updateAssegnazione(Request $request)
    {
        if (!auth()->user()->hasPermission('impieghi.edit') && !auth()->user()->hasPermission('admin.access')) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per modificare l\'assegnazione'
            ], 403);
        }

        $request->validate([
            'teatro_operativo_id' => 'required|exists:teatri_operativi,id',
            'militare_id' => 'required|exists:militari,id',
            'ruolo' => 'nullable|string|max:100',
            'note' => 'nullable|string'
        ]);

        try {
            $assegnazione = TeatroOperativoMilitare::where('teatro_operativo_id', $request->teatro_operativo_id)
                ->where('militare_id', $request->militare_id)
                ->firstOrFail();
            
            $assegnazione->update($request->only(['ruolo', 'note']));

            return response()->json([
                'success' => true,
                'message' => 'Assegnazione aggiornata con successo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API per ottenere i militari confermati di un teatro (per integrazione CPT/Approntamenti)
     */
    public function getMilitariConfermati($teatroId)
    {
        $teatro = TeatroOperativo::with(['militariConfermati' => function($query) {
            $query->with(['grado', 'compagnia']);
        }])->findOrFail($teatroId);

        return response()->json([
            'success' => true,
            'teatro' => $teatro->nome,
            'militari' => $teatro->militariConfermati
        ]);
    }

    /**
     * API per ottenere tutti i teatri con militari confermati (per Approntamenti)
     */
    public function getTeatriConfermati()
    {
        $teatri = TeatroOperativo::attivi()
            ->with(['militariConfermati' => function($query) {
                $query->with(['grado', 'compagnia']);
            }])
            ->get()
            ->filter(function($teatro) {
                return $teatro->isTuttiConfermati() && $teatro->getNumeroMilitari() > 0;
            })
            ->values();

        return response()->json([
            'success' => true,
            'teatri' => $teatri
        ]);
    }

    /**
     * Export Excel
     */
    public function exportExcel(Request $request)
    {
        $teatroId = $request->teatro_id;
        
        if (!$teatroId) {
            return redirect()->back()->with('error', 'Seleziona un Teatro Operativo per esportare');
        }
        
        $teatro = TeatroOperativo::with(['militari' => function($query) {
            $query->with(['grado', 'compagnia', 'polo', 'mansione'])
                  ->orderBy('cognome')
                  ->orderBy('nome');
        }])->findOrFail($teatroId);
        
        $militari = $teatro->militari;
        
        // Applica filtri se presenti
        if ($request->filled('stato_assegnazione')) {
            $statoFiltro = $request->stato_assegnazione;
            $militari = $militari->filter(function($m) use ($statoFiltro) {
                return $m->pivot->stato === $statoFiltro;
            });
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($teatro->nome, 0, 30));

        // Header
        $headers = ['Grado', 'Cognome', 'Nome', 'Compagnia', 'Ufficio', 'Incarico', 'Stato', 'Ruolo', 'Note'];
        
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
            
            $sheet->setCellValue($col++ . $row, $militare->grado->sigla ?? '-');
            $sheet->setCellValue($col++ . $row, $militare->cognome);
            $sheet->setCellValue($col++ . $row, $militare->nome);
            $sheet->setCellValue($col++ . $row, $militare->compagnia->nome ?? '-');
            $sheet->setCellValue($col++ . $row, $militare->polo->nome ?? '-');
            $sheet->setCellValue($col++ . $row, $militare->mansione->nome ?? '-');
            $sheet->setCellValue($col++ . $row, ucfirst($militare->pivot->stato));
            $sheet->setCellValue($col++ . $row, $militare->pivot->ruolo ?? '-');
            $sheet->setCellValue($col++ . $row, $militare->pivot->note ?? '-');
            
            // Colora la riga in base allo stato
            $bgColor = $militare->pivot->stato === 'confermato' ? 'D4EDDA' : 'FFF3CD';
            $sheet->getStyle('A' . $row . ':I' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bgColor);
            
            $row++;
        }

        // Auto-size colonne
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        $writer = new Xlsx($spreadsheet);
        $filename = 'Impieghi_' . preg_replace('/[^a-zA-Z0-9]/', '_', $teatro->nome) . '_' . date('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
}
