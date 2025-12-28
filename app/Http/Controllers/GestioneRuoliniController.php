<?php

namespace App\Http\Controllers;

use App\Models\TipoServizio;
use App\Models\ConfigurazioneRuolino;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller per la gestione delle configurazioni dei ruolini
 * 
 * Permette di configurare quali impegni CPT rendono un militare
 * presente o assente nei ruolini giornalieri
 */
class GestioneRuoliniController extends Controller
{
    /**
     * Mostra la pagina di gestione configurazione ruolini
     */
    public function index(Request $request)
    {
        // Recupera SOLO i tipi di servizio ATTIVI che esistono ANCHE in codici_servizio_gerarchia
        $tipiServizioQuery = TipoServizio::where('tipi_servizio.attivo', true)
            ->join('codici_servizio_gerarchia', function($join) {
                $join->on('tipi_servizio.codice', '=', 'codici_servizio_gerarchia.codice')
                     ->where('codici_servizio_gerarchia.attivo', true);
            })
            ->select('tipi_servizio.*')
            ->orderBy('tipi_servizio.ordine')
            ->orderBy('tipi_servizio.codice');
        
        // Log per debugging
        \Log::info('Gestione Ruolini - Tipi servizio sincronizzati:', [
            'count_tipi_servizio_attivi' => TipoServizio::where('attivo', true)->count(),
            'count_codici_gerarchia_attivi' => DB::table('codici_servizio_gerarchia')->where('attivo', true)->count(),
            'count_sincronizzati' => $tipiServizioQuery->count()
        ]);
        
        // Applica filtri
        if ($request->filled('categoria') && $request->categoria != 'tutte') {
            $tipiServizioQuery->where('tipi_servizio.categoria', $request->categoria);
        }
        
        $tipiServizio = $tipiServizioQuery->get();
        
        // Recupera tutte le configurazioni esistenti solo per i tipi servizio attivi
        $configurazioni = ConfigurazioneRuolino::with('tipoServizio')
            ->whereHas('tipoServizio', function($query) {
                $query->where('attivo', true);
            })
            ->get()
            ->keyBy('tipo_servizio_id');
        
        // Applica filtro stato lato server se necessario
        if ($request->filled('stato') && $request->stato != 'tutti') {
            $tipiServizio = $tipiServizio->filter(function($tipo) use ($configurazioni, $request) {
                $config = $configurazioni->get($tipo->id);
                $statoPresenza = $config ? $config->stato_presenza : 'assente';
                return $statoPresenza === $request->stato;
            });
        }
        
        return view('gestione-ruolini.index', compact('tipiServizio', 'configurazioni'));
    }

    /**
     * Aggiorna la configurazione di un tipo di servizio
     */
    public function update(Request $request, $tipoServizioId)
    {
        $request->validate([
            'stato_presenza' => 'required|in:presente,assente',
            'note' => 'nullable|string|max:500'
        ]);

        $data = [
            'stato_presenza' => $request->stato_presenza
        ];
        
        // Aggiungi note solo se presente nella request
        if ($request->has('note')) {
            $data['note'] = $request->note;
        }

        $configurazione = ConfigurazioneRuolino::updateOrCreate(
            ['tipo_servizio_id' => $tipoServizioId],
            $data
        );

        return response()->json([
            'success' => true,
            'message' => 'Configurazione aggiornata con successo',
            'configurazione' => $configurazione
        ]);
    }

    /**
     * Aggiorna multiple configurazioni in batch
     */
    public function updateBatch(Request $request)
    {
        $request->validate([
            'configurazioni' => 'required|array',
            'configurazioni.*.tipo_servizio_id' => 'required|exists:tipi_servizio,id',
            'configurazioni.*.stato_presenza' => 'required|in:presente,assente',
            'configurazioni.*.note' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->configurazioni as $config) {
                ConfigurazioneRuolino::updateOrCreate(
                    ['tipo_servizio_id' => $config['tipo_servizio_id']],
                    [
                        'stato_presenza' => $config['stato_presenza'],
                        'note' => $config['note'] ?? null
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tutte le configurazioni sono state aggiornate con successo'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina una configurazione (torna alla logica default)
     */
    public function destroy($tipoServizioId)
    {
        $configurazione = ConfigurazioneRuolino::where('tipo_servizio_id', $tipoServizioId)->first();

        if ($configurazione) {
            $configurazione->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Configurazione rimossa, verrà usata la logica di default'
        ]);
    }
}

