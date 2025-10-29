<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\ScadenzaMilitare;
use Illuminate\Http\Request;
use Carbon\Carbon;

class IdoneitzController extends Controller
{
    /**
     * Visualizza la pagina Idoneità Sanitarie
     */
    public function index(Request $request)
    {
        // Ottieni tutti i militari con le loro scadenze
        $militari = Militare::with('scadenza')
            ->orderByGradoENome()
            ->get();
        
        // Calcola le scadenze per ogni militare
        $data = $militari->map(function ($militare) {
            $scadenza = $militare->scadenza;
            
            return [
                'militare' => $militare,
                'idoneita_mansione' => $this->calcolaScadenza($scadenza?->idoneita_mans_data_conseguimento, 1), // 1 anno
                'idoneita_smi' => $this->calcolaScadenza($scadenza?->idoneita_smi_data_conseguimento, 1), // 1 anno
                'ecg' => $this->calcolaScadenza($scadenza?->ecg_data_conseguimento, 1), // 1 anno
                'prelievi' => $this->calcolaScadenza($scadenza?->prelievi_data_conseguimento, 1), // 1 anno
            ];
        });
        
        return view('scadenze.idoneita', compact('data'));
    }
    
    /**
     * Aggiorna una singola scadenza via AJAX
     */
    public function updateSingola(Request $request, Militare $militare)
    {
        $request->validate([
            'campo' => 'required|string',
            'data' => 'nullable|date',
        ]);
        
        $scadenza = $militare->scadenza ?? new ScadenzaMilitare(['militare_id' => $militare->id]);
        $campo = $request->campo;
        
        $scadenza->$campo = $request->data;
        $scadenza->save();
        
        // Calcola la nuova scadenza (tutte le idoneità hanno durata 1 anno)
        $scadenzaCalcolata = $this->calcolaScadenza($scadenza->$campo, 1);
        
        return response()->json([
            'success' => true,
            'scadenza' => $scadenzaCalcolata,
        ]);
    }
    
    /**
     * Calcola la scadenza e lo stato
     */
    private function calcolaScadenza($dataConseguimento, $durata)
    {
        if (!$dataConseguimento) {
            return [
                'data_conseguimento' => null,
                'data_scadenza' => null,
                'stato' => 'mancante',
                'giorni_rimanenti' => null,
            ];
        }
        
        $data = Carbon::parse($dataConseguimento);
        $scadenza = $data->copy()->addYears($durata);
        $oggi = Carbon::now();
        $giorniRimanenti = $oggi->diffInDays($scadenza, false);
        
        // Determina lo stato
        if ($giorniRimanenti < 0) {
            $stato = 'scaduto';
        } elseif ($giorniRimanenti <= 30) {
            $stato = 'in_scadenza';
        } else {
            $stato = 'valido';
        }
        
        return [
            'data_conseguimento' => $data,
            'data_scadenza' => $scadenza,
            'stato' => $stato,
            'giorni_rimanenti' => abs($giorniRimanenti),
        ];
    }
}
