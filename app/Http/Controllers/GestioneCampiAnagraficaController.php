<?php

namespace App\Http\Controllers;

use App\Models\ConfigurazioneCampoAnagrafica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GestioneCampiAnagraficaController extends Controller
{
    /**
     * Mostra la pagina di gestione campi anagrafica
     */
    public function index(Request $request)
    {
        // Definisci i campi "di sistema" pre-esistenti dell'anagrafica
        $campiSistema = [
            [
                'nome_campo' => 'compagnia',
                'etichetta' => 'Compagnia',
                'tipo_campo' => 'select',
                'ordine' => 1,
            ],
            [
                'nome_campo' => 'grado',
                'etichetta' => 'Grado',
                'tipo_campo' => 'select',
                'ordine' => 2,
            ],
            [
                'nome_campo' => 'cognome',
                'etichetta' => 'Cognome',
                'tipo_campo' => 'text',
                'ordine' => 3,
            ],
            [
                'nome_campo' => 'nome',
                'etichetta' => 'Nome',
                'tipo_campo' => 'text',
                'ordine' => 4,
            ],
            [
                'nome_campo' => 'plotone',
                'etichetta' => 'Plotone',
                'tipo_campo' => 'select',
                'ordine' => 5,
            ],
            [
                'nome_campo' => 'ufficio',
                'etichetta' => 'Ufficio',
                'tipo_campo' => 'select',
                'ordine' => 6,
            ],
            [
                'nome_campo' => 'incarico',
                'etichetta' => 'Incarico',
                'tipo_campo' => 'select',
                'ordine' => 7,
            ],
            [
                'nome_campo' => 'patenti',
                'etichetta' => 'Patenti',
                'tipo_campo' => 'text',
                'ordine' => 8,
            ],
            [
                'nome_campo' => 'nos',
                'etichetta' => 'NOS',
                'tipo_campo' => 'select',
                'ordine' => 9,
            ],
            [
                'nome_campo' => 'anzianita',
                'etichetta' => 'Anzianità',
                'tipo_campo' => 'number',
                'ordine' => 10,
            ],
            [
                'nome_campo' => 'data_nascita',
                'etichetta' => 'Data di Nascita',
                'tipo_campo' => 'date',
                'ordine' => 11,
            ],
            [
                'nome_campo' => 'email_istituzionale',
                'etichetta' => 'Email Istituzionale',
                'tipo_campo' => 'email',
                'ordine' => 12,
            ],
            [
                'nome_campo' => 'telefono',
                'etichetta' => 'Cellulare',
                'tipo_campo' => 'tel',
                'ordine' => 13,
            ],
            [
                'nome_campo' => 'codice_fiscale',
                'etichetta' => 'Codice Fiscale',
                'tipo_campo' => 'text',
                'ordine' => 14,
            ],
            [
                'nome_campo' => 'istituti',
                'etichetta' => 'Istituti',
                'tipo_campo' => 'text',
                'ordine' => 15,
            ],
        ];

        // Assicurati che i campi di sistema esistano in configurazione_campi_anagrafica
        foreach ($campiSistema as $config) {
            ConfigurazioneCampoAnagrafica::firstOrCreate(
                ['nome_campo' => $config['nome_campo']],
                [
                    'etichetta' => $config['etichetta'],
                    'tipo_campo' => $config['tipo_campo'],
                    'opzioni' => null,
                    'ordine' => $config['ordine'],
                    'attivo' => true,
                    'obbligatorio' => false,
                    'descrizione' => null,
                ]
            );
        }

        // Carica tutti i campi (sistema + custom) ordinati
        $campi = ConfigurazioneCampoAnagrafica::ordinati()->get();

        // RIMOSSO: Normalizzazione ordine automatica ad ogni accesso
        // La normalizzazione era problematica perché modificava il database ad ogni page load.
        // Se serve normalizzare gli ordini, usare un comando Artisan dedicato:
        // php artisan campi:normalizza-ordine
        
        return view('gestione-campi-anagrafica.index', compact('campi'));
    }

    /**
     * Crea un nuovo campo
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'etichetta' => 'required|string|max:255',
                'tipo_campo' => 'required|in:text,select,date,number,textarea,checkbox,email,tel',
                'opzioni' => 'nullable|array',
                'obbligatorio' => 'nullable|boolean',
                'descrizione' => 'nullable|string',
            ]);
            
            // Gestisci opzioni: se il tipo è select o checkbox e non ci sono opzioni, imposta null
            if (($validated['tipo_campo'] === 'select' || $validated['tipo_campo'] === 'checkbox')) {
                if (empty($validated['opzioni']) || (is_array($validated['opzioni']) && count(array_filter($validated['opzioni'])) === 0)) {
                    $validated['opzioni'] = null;
                }
            } else {
                // Per altri tipi, rimuovi le opzioni
                $validated['opzioni'] = null;
            }
            
            // Genera slug univoco dal nome etichetta
            $nomeCampo = Str::slug($validated['etichetta'], '_');
            
            // Assicura unicità
            $counter = 1;
            $originalNomeCampo = $nomeCampo;
            while (ConfigurazioneCampoAnagrafica::where('nome_campo', $nomeCampo)->exists()) {
                $nomeCampo = $originalNomeCampo . '_' . $counter;
                $counter++;
            }
            
            // Calcola ordine automatico (posizione successiva)
            $maxOrdine = ConfigurazioneCampoAnagrafica::max('ordine') ?? 0;
            
            $campo = ConfigurazioneCampoAnagrafica::create([
                'nome_campo' => $nomeCampo,
                'etichetta' => $validated['etichetta'],
                'tipo_campo' => $validated['tipo_campo'],
                'opzioni' => $validated['opzioni'],
                'ordine' => $maxOrdine + 1,
                'attivo' => true,
                'obbligatorio' => $validated['obbligatorio'] ?? false,
                'descrizione' => $validated['descrizione'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campo creato con successo',
                'campo' => $campo
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Errore validazione campo anagrafica', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Errore di validazione',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Log dettagliato per debug, messaggio generico per l'utente
            Log::error('Errore creazione campo anagrafica', [
                'user_id' => auth()->id(),
                'input' => $request->except(['_token']),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante la creazione del campo. Riprova o contatta l\'amministratore.'
            ], 500);
        }
    }

    /**
     * Aggiorna un campo (nome, tipo, opzioni)
     */
    public function edit(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'etichetta' => 'required|string|max:255',
                'tipo_campo' => 'required|in:text,select,date,number,textarea,checkbox,email,tel',
                'opzioni' => 'nullable|array',
                'obbligatorio' => 'nullable|boolean',
                'descrizione' => 'nullable|string',
            ]);
            
            // Gestisci opzioni: se il tipo è select o checkbox e non ci sono opzioni, imposta null
            if (($validated['tipo_campo'] === 'select' || $validated['tipo_campo'] === 'checkbox')) {
                if (empty($validated['opzioni']) || (is_array($validated['opzioni']) && count(array_filter($validated['opzioni'])) === 0)) {
                    $validated['opzioni'] = null;
                }
            } else {
                // Per altri tipi, rimuovi le opzioni
                $validated['opzioni'] = null;
            }
            
            $campo = ConfigurazioneCampoAnagrafica::findOrFail($id);
            
            // Definisci i campi di sistema (il nome_campo non deve mai cambiare)
            $campiSistemaNomi = [
                'compagnia', 'grado', 'cognome', 'nome', 'plotone', 'ufficio', 
                'incarico', 'patenti', 'nos', 'anzianita', 'data_nascita', 
                'email_istituzionale', 'telefono', 'codice_fiscale', 'istituti'
            ];
            
            // Se è un campo di sistema, NON cambiare mai il nome_campo
            if (in_array($campo->nome_campo, $campiSistemaNomi)) {
                // Rimuovi nome_campo dai dati validati per evitare che venga modificato
                unset($validated['nome_campo']);
            } else {
                // Per i campi custom, aggiorna il nome_campo solo se l'etichetta è cambiata
                if ($campo->etichetta !== $validated['etichetta']) {
                    $nomeCampo = Str::slug($validated['etichetta'], '_');
                    
                    // Assicura unicità
                    $counter = 1;
                    $originalNomeCampo = $nomeCampo;
                    while (ConfigurazioneCampoAnagrafica::where('nome_campo', $nomeCampo)->where('id', '!=', $id)->exists()) {
                        $nomeCampo = $originalNomeCampo . '_' . $counter;
                        $counter++;
                    }
                    
                    $validated['nome_campo'] = $nomeCampo;
                }
            }
            
            $campo->update($validated);
            
            // Ricarica il modello per assicurarsi che le opzioni siano correttamente castate
            $campo->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Campo aggiornato con successo',
                'campo' => $campo
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Errore validazione campo anagrafica', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Errore di validazione',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Log dettagliato per debug, messaggio generico per l'utente
            Log::error('Errore aggiornamento campo anagrafica', [
                'user_id' => auth()->id(),
                'campo_id' => $id,
                'input' => $request->except(['_token']),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'aggiornamento. Riprova o contatta l\'amministratore.'
            ], 500);
        }
    }

    /**
     * Aggiorna solo l'ordine di un campo
     */
    public function updateOrder(Request $request, $id)
    {
        $validated = $request->validate([
            'ordine' => 'required|integer|min:0',
        ]);

        try {
            $campo = ConfigurazioneCampoAnagrafica::findOrFail($id);
            $campo->update(['ordine' => $validated['ordine']]);

            return response()->json([
                'success' => true,
                'message' => 'Ordine aggiornato'
            ]);
        } catch (\Exception $e) {
            Log::error('Errore aggiornamento ordine campo', [
                'user_id' => auth()->id(),
                'campo_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento dell\'ordine. Riprova.'
            ], 500);
        }
    }

    /**
     * Toggle attivo/disattivo
     */
    public function toggleActive(Request $request, $id)
    {
        try {
            $campo = ConfigurazioneCampoAnagrafica::findOrFail($id);
            $campo->update(['attivo' => !$campo->attivo]);

            return response()->json([
                'success' => true,
                'attivo' => $campo->attivo,
                'message' => 'Stato aggiornato'
            ]);
        } catch (\Exception $e) {
            Log::error('Errore toggle attivo campo', [
                'user_id' => auth()->id(),
                'campo_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento dello stato. Riprova.'
            ], 500);
        }
    }

    /**
     * Elimina un campo
     */
    public function destroy($id)
    {
        try {
            $campo = ConfigurazioneCampoAnagrafica::findOrFail($id);
            
            // Elimina anche tutti i valori associati (cascade)
            $campo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Campo eliminato con successo'
            ]);
        } catch (\Exception $e) {
            Log::error('Errore eliminazione campo anagrafica', [
                'user_id' => auth()->id(),
                'campo_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'eliminazione. Il campo potrebbe essere in uso.'
            ], 500);
        }
    }
}
