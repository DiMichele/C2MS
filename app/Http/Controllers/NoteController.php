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
use App\Models\Nota;
use Illuminate\Support\Facades\Log;

/**
 * Controller per la gestione delle note personali sui militari
 * 
 * Questo controller gestisce le operazioni sulle note personali
 * che gli utenti possono associare ai militari per annotazioni
 * e osservazioni private.
 * 
 * @package App\Http\Controllers
 * @version 1.0
 */
class NoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Salva o aggiorna una nota personale per un militare
     * 
     * Questo metodo permette di salvare note personali associate a un militare
     * specifico per l'utente corrente. Se esiste già una nota, viene aggiornata,
     * altrimenti ne viene creata una nuova.
     *
     * @param Request $request Richiesta HTTP con militare_id e contenuto della nota
     * @return \Illuminate\Http\JsonResponse Risposta JSON con esito operazione
     */
    public function save(Request $request)
    {
        try {
            // Validazione dei dati
            $validated = $request->validate([
                'militare_id' => 'required|exists:militari,id',
                'contenuto' => 'nullable|string|max:2000',
            ]);

            // Cerca una nota esistente o ne crea una nuova - Sistema monoutente
            $nota = Nota::updateOrCreate(
                [
                    'militare_id' => $validated['militare_id'],
                    'user_id' => 1, // Sistema monoutente - ID utente fisso
                ],
                [
                    'contenuto' => $validated['contenuto'],
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Nota salvata con successo',
                'nota' => [
                    'id' => $nota->id,
                    'contenuto' => $nota->contenuto,
                    'updated_at' => $nota->updated_at->format('d/m/Y H:i')
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Errore durante il salvataggio della nota', [
                'user_id' => 1, // Sistema monoutente
                'militare_id' => $request->get('militare_id'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il salvataggio della nota'
            ], 500);
        }
    }
}
