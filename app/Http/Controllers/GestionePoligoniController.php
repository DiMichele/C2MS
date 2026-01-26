<?php

namespace App\Http\Controllers;

use App\Models\TipoPoligono;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Controller per la gestione della configurazione dei tipi di poligono
 * 
 * Gestisce:
 * - Visualizzazione tipi di poligono
 * - Creazione nuovi tipi
 * - Modifica tipi esistenti (nome, descrizione, durata)
 * - Eliminazione tipi di poligono
 */
class GestionePoligoniController extends Controller
{
    /**
     * Codici dei 3 tipi standard iniziali (creati automaticamente se la tabella è vuota)
     */
    private const CODICI_STANDARD = [
        'teatro_operativo',
        'mantenimento_arma_lunga',
        'mantenimento_arma_corta',
    ];
    
    /**
     * Visualizza la pagina di gestione poligoni
     * Mostra tutti i tipi di poligono attivi
     */
    public function index(Request $request)
    {
        try {
            // Verifica se la tabella esiste
            if (!Schema::hasTable('tipi_poligono')) {
                return view('gestione-poligoni.index', [
                    'poligoni' => collect()
                ])->with('error', 'La tabella tipi_poligono non esiste. Eseguire le migration: php artisan migrate');
            }
            
            // Se la tabella è vuota, crea i tipi standard
            if (TipoPoligono::count() === 0) {
                $this->creaPoligoniStandard();
            }
            
            // Mostra tutti i tipi attivi
            $poligoni = TipoPoligono::where('attivo', true)
                ->ordinati()
                ->get();

            return view('gestione-poligoni.index', compact('poligoni'));
            
        } catch (\Exception $e) {
            Log::error('Errore caricamento gestione poligoni', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('gestione-poligoni.index', [
                'poligoni' => collect()
            ])->with('error', 'Errore durante il caricamento: ' . $e->getMessage());
        }
    }
    
    /**
     * Crea i 3 tipi di poligono standard se la tabella è vuota
     */
    private function creaPoligoniStandard(): void
    {
        $tipiStandard = [
            [
                'codice' => 'teatro_operativo',
                'nome' => 'Teatro Operativo',
                'descrizione' => 'Qualifica per teatro operativo',
                'punteggio_minimo' => 60,
                'punteggio_massimo' => 100,
                'durata_mesi' => 6,
                'ordine' => 1,
                'attivo' => true
            ],
            [
                'codice' => 'mantenimento_arma_lunga',
                'nome' => 'Mantenimento Arma Lunga',
                'descrizione' => 'Mantenimento qualifica con arma lunga (fucile)',
                'punteggio_minimo' => 55,
                'punteggio_massimo' => 100,
                'durata_mesi' => 6,
                'ordine' => 2,
                'attivo' => true
            ],
            [
                'codice' => 'mantenimento_arma_corta',
                'nome' => 'Mantenimento Arma Corta',
                'descrizione' => 'Mantenimento qualifica con arma corta (pistola)',
                'punteggio_minimo' => 50,
                'punteggio_massimo' => 100,
                'durata_mesi' => 6,
                'ordine' => 3,
                'attivo' => true
            ],
        ];
        
        foreach ($tipiStandard as $tipo) {
            TipoPoligono::updateOrCreate(
                ['codice' => $tipo['codice']],
                $tipo
            );
        }
        
        Log::info('Creati tipi di poligono standard automaticamente');
    }

    /**
     * Crea un nuovo tipo di poligono
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descrizione' => 'nullable|string',
            'durata_mesi' => 'required|integer|min:0',
            'punteggio_minimo' => 'nullable|integer|min:0',
            'punteggio_massimo' => 'nullable|integer|min:0',
        ]);

        try {
            // Verifica se esiste già un tipo attivo con questo nome
            $esistente = TipoPoligono::where('nome', $validated['nome'])
                ->where('attivo', true)
                ->first();
            
            if ($esistente) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Esiste già un tipo di poligono con questo nome'
                    ], 422);
                }
                return redirect()->back()
                    ->withErrors(['nome' => 'Esiste già un tipo di poligono con questo nome'])
                    ->withInput();
            }
            
            // Genera codice univoco
            $codice = \Str::slug($validated['nome'], '_');
            
            // Verifica unicità del codice
            $baseCode = $codice;
            $counter = 1;
            while (TipoPoligono::where('codice', $codice)->exists()) {
                $codice = $baseCode . '_' . $counter;
                $counter++;
            }

            // Determina ordine
            $maxOrdine = TipoPoligono::max('ordine') ?? 0;
            
            $poligono = TipoPoligono::create([
                'codice' => $codice,
                'nome' => $validated['nome'],
                'descrizione' => $validated['descrizione'] ?? null,
                'durata_mesi' => $validated['durata_mesi'],
                'punteggio_minimo' => $validated['punteggio_minimo'] ?? 0,
                'punteggio_massimo' => $validated['punteggio_massimo'] ?? 100,
                'attivo' => true,
                'ordine' => $maxOrdine + 1,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tipo di poligono creato con successo',
                    'poligono' => $poligono
                ]);
            }

            return redirect()->route('gestione-poligoni.index')
                ->with('success', 'Tipo di poligono creato con successo');

        } catch (\Exception $e) {
            Log::error('Errore creazione tipo poligono', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore durante la creazione: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Errore durante la creazione'])
                ->withInput();
        }
    }

    /**
     * Aggiorna un tipo di poligono (solo durata)
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'durata_mesi' => 'required|integer|min:0'
        ]);

        try {
            $poligono = TipoPoligono::findOrFail($id);
            $poligono->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Tipo di poligono aggiornato con successo'
            ]);

        } catch (\Exception $e) {
            Log::error('Errore aggiornamento tipo poligono', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Modifica completa un tipo di poligono
     */
    public function edit(Request $request, $id)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descrizione' => 'nullable|string',
            'durata_mesi' => 'required|integer|min:0',
            'punteggio_minimo' => 'nullable|integer|min:0',
            'punteggio_massimo' => 'nullable|integer|min:0',
        ]);

        try {
            $poligono = TipoPoligono::findOrFail($id);
            
            // Aggiorna il codice se il nome cambia
            if ($poligono->nome !== $validated['nome']) {
                $nuovoCodice = \Str::slug($validated['nome'], '_');
                
                // Verifica unicità del nuovo codice (escludendo il tipo corrente)
                $baseCode = $nuovoCodice;
                $counter = 1;
                while (TipoPoligono::where('codice', $nuovoCodice)->where('id', '!=', $id)->exists()) {
                    $nuovoCodice = $baseCode . '_' . $counter;
                    $counter++;
                }
                
                $validated['codice'] = $nuovoCodice;
            }
            
            $poligono->update($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tipo di poligono modificato con successo',
                    'poligono' => $poligono
                ]);
            }

            return redirect()->route('gestione-poligoni.index')
                ->with('success', 'Tipo di poligono modificato con successo');

        } catch (\Exception $e) {
            Log::error('Errore modifica tipo poligono', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore durante la modifica: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Errore durante la modifica'])
                ->withInput();
        }
    }

    /**
     * Elimina un tipo di poligono e tutte le scadenze associate
     */
    public function destroy(Request $request, $id)
    {
        try {
            $poligono = TipoPoligono::findOrFail($id);
            $nome = $poligono->nome;
            
            // Elimina prima le scadenze associate (se la tabella esiste)
            if (Schema::hasTable('scadenze_poligoni')) {
                $countScadenze = $poligono->scadenzePoligoni()->count();
                $poligono->scadenzePoligoni()->delete();
            } else {
                $countScadenze = 0;
            }
            
            // Elimina il tipo di poligono
            $poligono->delete();
            
            $message = 'Tipo di poligono "' . $nome . '" eliminato con successo';
            if ($countScadenze > 0) {
                $message .= ' (rimosse anche ' . $countScadenze . ' scadenze associate)';
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            return redirect()->route('gestione-poligoni.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Errore eliminazione tipo poligono', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Errore durante l\'eliminazione']);
        }
    }
}

