<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\ScadenzaMilitare;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ScadenzeController extends Controller
{
    /**
     * Mostra la pagina delle scadenze
     */
    public function index(Request $request)
    {
        // Carica tutti i militari con le loro scadenze
        $query = Militare::with(['scadenza', 'grado', 'plotone', 'compagnia'])
            ->orderBy('cognome')
            ->orderBy('nome');

        // FILTRO PERMESSI: filtra per compagnia dell'utente se non è admin
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && !$user->hasRole('admin') && !$user->hasRole('amministratore')) {
            if ($user->compagnia_id) {
                $query->where('compagnia_id', $user->compagnia_id);
            }
        }

        $militari = $query->get();

        // Applica i filtri se presenti
        $filtri = $request->only([
            'pefo', 'idoneita_mans', 'idoneita_smi',
            'lavoratore_4h', 'lavoratore_8h',
            'preposto', 'dirigenti',
            'poligono_approntamento', 'poligono_mantenimento'
        ]);

        if (!empty($filtri)) {
            $militari = $this->applicaFiltri($militari, $filtri);
        }

        // Verifica se l'utente può modificare le scadenze
        $canEdit = auth()->user()->hasPermission('scadenze.edit');

        return view('scadenze.index', compact('militari', 'filtri', 'canEdit'));
    }

    /**
     * Applica i filtri alla collection di militari
     */
    private function applicaFiltri($militari, array $filtri)
    {
        return $militari->filter(function ($militare) use ($filtri) {
            $scadenza = $militare->scadenza;

            if (!$scadenza) {
                // Se non ci sono scadenze, mostra solo se il filtro è 'tutti' o 'scaduti'
                foreach ($filtri as $tipo => $valore) {
                    if ($valore === 'scaduti' || $valore === 'non_presenti') {
                        return true;
                    }
                }
                return false;
            }

            foreach ($filtri as $tipo => $valore) {
                if ($valore === 'tutti') {
                    continue;
                }

                $stato = $scadenza->verificaStato($tipo);

                if ($valore === 'validi' && $stato !== 'valido') {
                    return false;
                }
                if ($valore === 'in_scadenza' && $stato !== 'in_scadenza') {
                    return false;
                }
                if ($valore === 'scaduti' && $stato !== 'scaduto' && $stato !== 'non_presente') {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Aggiorna una scadenza
     */
    public function update(Request $request, $militareId)
    {
        // Verifica permessi
        if (!auth()->user()->hasPermission('scadenze.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per modificare le scadenze'
            ], 403);
        }

        try {
            $militare = Militare::findOrFail($militareId);
            
            // Crea o aggiorna il record scadenze
            $scadenza = ScadenzaMilitare::updateOrCreate(
                ['militare_id' => $militareId],
                $request->only([
                    'pefo_data_conseguimento',
                    'idoneita_mans_data_conseguimento',
                    'idoneita_smi_data_conseguimento',
                    'lavoratore_4h_data_conseguimento',
                    'lavoratore_8h_data_conseguimento',
                    'preposto_data_conseguimento',
                    'dirigenti_data_conseguimento',
                    'poligono_approntamento_data_conseguimento',
                    'poligono_mantenimento_data_conseguimento',
                ])
            );

            return response()->json([
                'success' => true,
                'message' => 'Scadenza aggiornata con successo',
                'scadenze' => $scadenza->getTutteScadenze()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aggiorna una singola data di conseguimento
     */
    public function updateSingola(Request $request, $militareId)
    {
        // Verifica permessi
        if (!auth()->user()->hasPermission('scadenze.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per modificare le scadenze'
            ], 403);
        }

        try {
            $militare = Militare::findOrFail($militareId);
            $tipo = $request->input('tipo');
            $data = $request->input('data');

            // Valida il tipo
            $tipiValidi = [
                'pefo', 'idoneita_mans', 'idoneita_smi',
                'lavoratore_4h', 'lavoratore_8h',
                'preposto', 'dirigenti',
                'poligono_approntamento', 'poligono_mantenimento'
            ];

            if (!in_array($tipo, $tipiValidi)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo certificato non valido'
                ], 400);
            }

            $campo = $tipo . '_data_conseguimento';

            // Converti stringa vuota in null
            $dataValue = empty($data) ? null : $data;

            // Crea o aggiorna il record
            $scadenza = ScadenzaMilitare::updateOrCreate(
                ['militare_id' => $militareId],
                [$campo => $dataValue]
            );

            // Calcola i dati aggiornati
            $dataScadenza = $scadenza->calcolaScadenza($tipo);
            $stato = $scadenza->verificaStato($tipo);

            return response()->json([
                'success' => true,
                'message' => 'Data aggiornata con successo',
                'data_conseguimento' => $data ? Carbon::parse($data)->format('d/m/Y') : '-',
                'data_scadenza' => $scadenza->formatScadenza($tipo),
                'stato' => $stato,
                'colore' => $scadenza->getColore($tipo)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }
}

