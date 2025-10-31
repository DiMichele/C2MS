<?php

namespace App\Http\Controllers;

use App\Models\CodiciServizioGerarchia;
use App\Models\TipoServizio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * Controller per la gestione dei codici CPT
 * Permette di creare, modificare ed eliminare i codici/categorie utilizzati nel CPT
 */
class GestioneCptController extends Controller
{
    /**
     * Visualizza l'elenco di tutti i codici CPT
     */
    public function index(Request $request)
    {
        $query = CodiciServizioGerarchia::query();

        // Filtro per macro attività
        if ($request->filled('macro_attivita')) {
            $query->where('macro_attivita', $request->macro_attivita);
        }

        // Filtro per tipo attività
        if ($request->filled('tipo_attivita')) {
            $query->where('tipo_attivita', $request->tipo_attivita);
        }

        // Filtro per impiego
        if ($request->filled('impiego')) {
            $query->where('impiego', $request->impiego);
        }

        // Filtro per attivo/inattivo
        if ($request->filled('attivo')) {
            $query->where('attivo', $request->attivo === '1');
        }

        // Ricerca per codice o descrizione
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codice', 'like', "%{$search}%")
                  ->orWhere('attivita_specifica', 'like', "%{$search}%")
                  ->orWhere('descrizione_impiego', 'like', "%{$search}%");
            });
        }

        // Ordinamento
        $sortBy = $request->get('sort_by', 'ordine');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Ottieni TUTTI i codici (no paginazione)
        $codici = $query->get();

        // Ottieni valori unici per i filtri
        $macroAttivita = CodiciServizioGerarchia::select('macro_attivita')
            ->distinct()
            ->whereNotNull('macro_attivita')
            ->pluck('macro_attivita');

        $tipiAttivita = CodiciServizioGerarchia::select('tipo_attivita')
            ->distinct()
            ->whereNotNull('tipo_attivita')
            ->pluck('tipo_attivita');

        $impieghi = CodiciServizioGerarchia::select('impiego')
            ->distinct()
            ->pluck('impiego');

        return view('gestione-cpt.index', compact(
            'codici',
            'macroAttivita',
            'tipiAttivita',
            'impieghi'
        ));
    }

    /**
     * Mostra il form per creare un nuovo codice CPT
     */
    public function create()
    {
        // Ottieni valori esistenti per i dropdown
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

            $codice = CodiciServizioGerarchia::create([
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
            return redirect()->back()
                ->withErrors(['error' => 'Errore durante la creazione: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Mostra il form per modificare un codice CPT esistente
     */
    public function edit(CodiciServizioGerarchia $codice)
    {
        // Ottieni valori esistenti per i dropdown
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
    public function update(Request $request, CodiciServizioGerarchia $codice)
    {
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
                'ordine' => $ordine
            ]);

            // SINCRONIZZA con tipi_servizio
            $this->sincronizzaTipoServizio($codice);

            DB::commit();
            
            return redirect()->route('codici-cpt.index')
                ->with('success', "Codice '{$codice->codice}' aggiornato e sincronizzato con il CPT!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()])
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
            
            // Elimina anche da tipi_servizio se esiste
            TipoServizio::where('codice', $codice->codice)->delete();
            
            // Elimina da codici_servizio_gerarchia
            $codice->delete();

            DB::commit();
            
            return redirect()->route('codici-cpt.index')
                ->with('success', "Codice '{$codiceTesto}' eliminato e rimosso dal CPT!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Errore durante l\'eliminazione: ' . $e->getMessage());
        }
    }

    /**
     * Aggiorna lo stato attivo/inattivo di un codice
     */
    public function toggleAttivo(CodiciServizioGerarchia $codice)
    {
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
            return redirect()->back()
                ->with('error', 'Errore durante l\'aggiornamento: ' . $e->getMessage());
        }
    }

    /**
     * Duplica un codice CPT esistente
     */
    public function duplicate(CodiciServizioGerarchia $codice)
    {
        // Trova un codice univoco
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
     * Esporta i codici in formato CSV
     */
    public function export()
    {
        $codici = CodiciServizioGerarchia::orderBy('ordine')->get();

        $csv = "Codice;Macro Attività;Tipo Attività;Attività Specifica;Impiego;Descrizione;Colore;Attivo;Ordine\n";
        
        foreach ($codici as $codice) {
            $csv .= sprintf(
                "%s;%s;%s;%s;%s;%s;%s;%s;%d\n",
                $codice->codice,
                $codice->macro_attivita ?? '',
                $codice->tipo_attivita ?? '',
                $codice->attivita_specifica,
                $codice->impiego,
                $codice->descrizione_impiego ?? '',
                $codice->colore_badge,
                $codice->attivo ? 'SI' : 'NO',
                $codice->ordine
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="codici_cpt_' . date('Y-m-d') . '.csv"');
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

        foreach ($request->ordini as $ordine => $id) {
            CodiciServizioGerarchia::where('id', $id)->update(['ordine' => $ordine]);
        }

        return response()->json(['success' => true, 'message' => 'Ordine aggiornato con successo!']);
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

        // Crea o aggiorna il tipo servizio corrispondente
        TipoServizio::updateOrCreate(
            ['codice' => $codiceGerarchia->codice],
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

