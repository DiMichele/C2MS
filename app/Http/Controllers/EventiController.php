<?php

/**
 * SUGECO: Sistema Unico di Gestione e Controllo
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
use App\Models\Evento;
use App\Models\Militare;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller per la gestione degli eventi militari
 * 
 * Questo controller gestisce tutte le operazioni CRUD sugli eventi,
 * inclusi i controlli di sovrapposizione con assenze e altri eventi.
 * 
 * @package App\Http\Controllers
 * @version 1.0
 */
class EventiController extends Controller
{
    /**
     * Mostra l'elenco degli eventi
     * 
     * @return \Illuminate\View\View Vista con l'elenco degli eventi e militari
     */
    public function index()
    {
        $eventi = Evento::with('militare.grado')
            ->orderBy('data_inizio', 'desc')
            ->get();
            
        $militari = Militare::with('grado')
            ->orderByGradoENome()
            ->get();

        return view('eventi.index', compact('eventi', 'militari'));
    }

    /**
     * Mostra il form per creare un nuovo evento
     * 
     * @return \Illuminate\View\View Vista del form di creazione evento
     */
    public function create()
    {
        $militari = Militare::with('grado')
            ->orderByGradoENome()
            ->get();

        return view('eventi.create', compact('militari'));
    }

    /**
     * Salva un nuovo evento nel database
     * 
     * @param Request $request Richiesta HTTP con i dati dell'evento
     * @return \Illuminate\Http\RedirectResponse Redirect con messaggio di successo o errore
     */
    public function store(Request $request)
    {
        try {
            // Validazione dei dati
            $validated = $this->validateEventData($request);
            
            DB::beginTransaction();
            
            $result = $this->createEventsForMilitari($validated);
            
            DB::commit();
            
            return $this->handleStoreResult($result);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Errore durante la creazione degli eventi', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Errore durante il salvataggio: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Elimina un evento dal database
     * 
     * @param int $id ID dell'evento da eliminare
     * @return \Illuminate\Http\RedirectResponse Redirect con messaggio di successo o errore
     */
    public function destroy($id)
    {
        try {
            $evento = Evento::findOrFail($id);
            $evento->delete();

            return redirect()->route('eventi.index')
                ->with('success', 'Evento eliminato con successo.');
                
        } catch (\Exception $e) {
            Log::error('Errore durante l\'eliminazione dell\'evento', [
                'evento_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Errore durante l\'eliminazione: ' . $e->getMessage()]);
        }
    }

    /**
     * Valida i dati dell'evento
     * 
     * @param Request $request Richiesta HTTP
     * @return array Dati validati
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateEventData(Request $request)
    {
        return $request->validate([
            'militare_id' => 'required|array|min:1',
            'militare_id.*' => 'exists:militari,id',
            'tipologia' => 'required|string|max:255',
            'nome' => 'required|string|max:255',
            'data_inizio' => 'required|date|after_or_equal:today',
            'data_fine' => 'required|date|after_or_equal:data_inizio',
            'localita' => 'required|string|max:255',
        ]);
    }

    /**
     * Crea eventi per tutti i militari selezionati
     * 
     * @param array $validated Dati validati
     * @return array Risultato dell'operazione con contatori e messaggi
     */
    private function createEventsForMilitari(array $validated)
    {
        $errorMessages = [];
        $successCount = 0;

        foreach ($validated['militare_id'] as $militareId) {
            $militare = Militare::with('grado')->findOrFail($militareId);

            // Controllo sovrapposizioni
            $conflictCheck = $this->checkConflicts($militare, $validated['data_inizio'], $validated['data_fine']);
            
            if ($conflictCheck) {
                $errorMessages[] = $conflictCheck;
                continue;
            }

            // Creazione dell'evento
            Evento::create([
                'militare_id' => $militareId,
                'tipologia' => $validated['tipologia'],
                'nome' => $validated['nome'],
                'data_inizio' => $validated['data_inizio'],
                'data_fine' => $validated['data_fine'],
                'localita' => $validated['localita']
            ]);
            
            $successCount++;
        }

        return [
            'success_count' => $successCount,
            'error_messages' => $errorMessages
        ];
    }

    /**
     * Controlla conflitti con assenze e altri eventi
     * 
     * @param Militare $militare Militare da controllare
     * @param string $dataInizio Data di inizio evento
     * @param string $dataFine Data di fine evento
     * @return string|null Messaggio di errore o null se nessun conflitto
     */
    private function checkConflicts(Militare $militare, string $dataInizio, string $dataFine)
    {
        $nomeCompleto = $militare->getNomeCompleto();

        // Controllo assenze
        if ($militare->hasAssenzaInDate($dataInizio, $dataFine)) {
            return "Il {$nomeCompleto} risulta in assenza per le date selezionate.";
        }

        // Controllo eventi esistenti
        if ($militare->hasEventoInDate($dataInizio, $dataFine)) {
            return "Il {$nomeCompleto} ha già un evento pianificato per le date selezionate.";
        }

        return null;
    }

    /**
     * Gestisce il risultato della creazione eventi
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
                ? 'Alcuni eventi sono stati registrati con successo, ma ci sono stati i seguenti errori:' 
                : 'Non è stato possibile registrare gli eventi per i seguenti motivi:';
                
            return redirect()->back()
                ->with('warning', $message)
                ->withErrors($errorMessages)
                ->withInput();
        }

        return redirect()->route('eventi.index')
            ->with('success', "Eventi registrati con successo per {$successCount} militari.");
    }
}
