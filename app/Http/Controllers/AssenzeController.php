<?php

/**
 * C2MS: Gestione e Controllo Digitale a Supporto del Comando
 * 
 * Questo file fa parte del sistema C2MS per la gestione militare digitale.
 * 
 * @package    C2MS
 * @subpackage Controllers
 * @version    2.1.0
 * @author     Michele Di Gennaro
 * @copyright 2025 Michele Di Gennaro
 * @license    Proprietary
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assenza;
use App\Models\Militare;
use App\Http\Requests\StoreAssenzaRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Controller per la gestione delle assenze militari
 * 
 * Questo controller gestisce tutte le operazioni CRUD sulle assenze,
 * inclusi i controlli per il recupero compensativo e la validazione degli orari.
 * 
 * @package App\Http\Controllers
 * @version 1.0
 */
class AssenzeController extends Controller
{
    /**
     * Orari di lavoro configurabili
     */
    private const ORARIO_INIZIO = '08:00';
    private const ORARIO_FINE_VENERDI = '12:00';
    private const ORARIO_FINE_ALTRI_GIORNI = '16:30';
    
    /**
     * Mostra l'elenco delle assenze
     * 
     * @return \Illuminate\View\View Vista con l'elenco delle assenze e militari
     */
    public function index()
    {
        $assenze = Assenza::with('militare.grado')
            ->orderBy('data_inizio', 'desc')
            ->get();
            
        $militari = Militare::with('grado')
            ->orderByGradoENome()
            ->get();
        
        return view('assenze.index', compact('assenze', 'militari'));
    }

    /**
     * Mostra il form per creare una nuova assenza
     * 
     * @return \Illuminate\View\View Vista del form di creazione assenza
     */
    public function create()
    {
        $militari = Militare::with('grado')
            ->orderByGradoENome()
            ->get();
        
        return view('assenze.create', compact('militari'));
    }

    /**
     * Salva una nuova assenza nel database
     * 
     * @param Request $request Richiesta HTTP con i dati dell'assenza
     * @return \Illuminate\Http\RedirectResponse Redirect con messaggio di successo o errore
     */
    public function store(Request $request)
    {
        try {
            // Validazione dei dati
            $validated = $this->validateAssenzaData($request);
            
            DB::beginTransaction();
            
            $result = $this->createAssenzeForMilitari($validated);
            
            DB::commit();
            
            return $this->handleStoreResult($result);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Errore durante la creazione delle assenze', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Errore durante il salvataggio: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Approva un'assenza esistente
     * 
     * @param int $id ID dell'assenza da approvare
     * @return \Illuminate\Http\RedirectResponse Redirect con messaggio di successo o warning
     */
    public function update($id)
    {
        try {
            $assenza = Assenza::findOrFail($id);
            
            if ($assenza->stato === 'Richiesta Ricevuta') {
                $assenza->update(['stato' => 'Approvata']);
                
                return redirect()->route('assenze.index')
                    ->with('success', 'Assenza approvata con successo!');
            }
            
            return redirect()->route('assenze.index')
                ->with('warning', 'L\'assenza è già stata elaborata.');
                
        } catch (\Exception $e) {
            Log::error('Errore durante l\'approvazione dell\'assenza', [
                'assenza_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Errore durante l\'approvazione: ' . $e->getMessage()]);
        }
    }

    /**
     * Elimina un'assenza dal database
     * 
     * @param int $id ID dell'assenza da eliminare
     * @return \Illuminate\Http\RedirectResponse Redirect con messaggio di successo o errore
     */
    public function destroy($id)
    {
        try {
            $assenza = Assenza::findOrFail($id);
            $assenza->delete();
            
            return redirect()->route('assenze.index')
                ->with('success', 'Assenza eliminata con successo.');
                
        } catch (\Exception $e) {
            Log::error('Errore durante l\'eliminazione dell\'assenza', [
                'assenza_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Errore durante l\'eliminazione: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Valida i dati dell'assenza
     * 
     * @param Request $request Richiesta HTTP
     * @return array Dati validati
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateAssenzaData(Request $request)
    {
        $rules = [
            'militare_id' => 'required|array|min:1',
            'militare_id.*' => 'exists:militari,id',
            'tipologia' => 'required|string|max:255',
            'data_inizio' => 'required|date|after_or_equal:today',
            'data_fine' => 'required|date|after_or_equal:data_inizio',
        ];
        
        // Validazione aggiuntiva per il recupero compensativo
        if ($request->input('tipologia') === 'Recupero Compensativo') {
            $rules['orario_inizio'] = 'required|date_format:H:i';
            $rules['orario_fine'] = 'required|date_format:H:i|after:orario_inizio';
        }
        
        return $request->validate($rules);
    }

    /**
     * Crea assenze per tutti i militari selezionati
     * 
     * @param array $validated Dati validati
     * @return array Risultato dell'operazione con contatori e messaggi
     */
    private function createAssenzeForMilitari(array $validated)
    {
        $errorMessages = [];
        $successCount = 0;

        foreach ($validated['militare_id'] as $militareId) {
            $militare = Militare::with('grado')->findOrFail($militareId);
            
            // Controllo sovrapposizioni
            $conflictCheck = $this->checkConflicts($militare, $validated);
            
            if ($conflictCheck) {
                $errorMessages[] = $conflictCheck;
                continue;
            }

            // Creazione dell'assenza
            Assenza::create([
                'militare_id' => $militareId,
                'tipologia' => $validated['tipologia'],
                'data_inizio' => $validated['data_inizio'],
                'data_fine' => $validated['data_fine'],
                'orario_inizio' => $validated['orario_inizio'] ?? null,
                'orario_fine' => $validated['orario_fine'] ?? null,
                'stato' => $this->determineInitialState($validated['tipologia'])
            ]);
            
            $successCount++;
        }

        return [
            'success_count' => $successCount,
            'error_messages' => $errorMessages
        ];
    }

    /**
     * Controlla conflitti e validazioni per un militare
     * 
     * @param Militare $militare Militare da controllare
     * @param array $validated Dati validati dell'assenza
     * @return string|null Messaggio di errore o null se nessun conflitto
     */
    private function checkConflicts(Militare $militare, array $validated)
    {
        $nomeCompleto = $militare->getNomeCompleto();

        // Controllo assenze esistenti
        if ($militare->hasAssenzaInDate($validated['data_inizio'], $validated['data_fine'])) {
            return "Il {$nomeCompleto} ha già un'assenza in queste date.";
        }

        // Controllo orari per il Recupero Compensativo
        if ($validated['tipologia'] === 'Recupero Compensativo') {
            $errorOrario = $this->validateOrarioRecupero(
                $validated['data_inizio'],
                $validated['orario_inizio'],
                $validated['orario_fine']
            );
            
            if ($errorOrario) {
                return "Per {$nomeCompleto}: {$errorOrario}";
            }
        }

        return null;
    }

    /**
     * Determina lo stato iniziale dell'assenza
     * 
     * @param string $tipologia Tipologia dell'assenza
     * @return string Stato iniziale
     */
    private function determineInitialState(string $tipologia)
    {
        return $tipologia === 'R.M.D.' ? 'Approvata' : 'Richiesta Ricevuta';
    }

    /**
     * Gestisce il risultato della creazione assenze
     * 
     * @param array $result Risultato dell'operazione
     * @return \Illuminate\Http\RedirectResponse Redirect appropriato
     */
    private function handleStoreResult(array $result)
    {
        $successCount = $result['success_count'];
        $errorMessages = $result['error_messages'];

        if (!empty($errorMessages)) {
            $message = $successCount > 0 
                ? 'Alcune assenze sono state registrate con successo, ma ci sono stati i seguenti errori:' 
                : 'Non è stato possibile registrare le assenze per i seguenti motivi:';
                
            return redirect()->back()
                ->with('warning', $message)
                ->withErrors($errorMessages)
                ->withInput();
        }

        return redirect()->route('assenze.index')
            ->with('success', "Assenze registrate con successo per {$successCount} militari.");
    }
    
    /**
     * Valida gli orari per il recupero compensativo
     *
     * @param string $dataInizio Data di inizio in formato Y-m-d
     * @param string $orarioInizio Orario di inizio in formato H:i
     * @param string $orarioFine Orario di fine in formato H:i
     * @return string|null Messaggio di errore o null se valido
     */
    private function validateOrarioRecupero(string $dataInizio, string $orarioInizio, string $orarioFine)
    {
        $giornoSettimana = Carbon::parse($dataInizio)->dayOfWeek;
        $isVenerdi = $giornoSettimana === 5; // 5 = Venerdì
        
        $orarioMinimo = self::ORARIO_INIZIO;
        $orarioMassimo = $isVenerdi ? self::ORARIO_FINE_VENERDI : self::ORARIO_FINE_ALTRI_GIORNI;
        
        if ($orarioInizio < $orarioMinimo || $orarioFine > $orarioMassimo) {
            return "Il Recupero Compensativo deve essere tra {$orarioMinimo} e {$orarioMassimo}.";
        }
        
        return null;
    }
}
