<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\ScadenzaMilitare;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PoligoniController extends Controller
{
    /**
     * Visualizza la pagina Poligoni
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
                'tiri_approntamento' => $this->calcolaScadenza($scadenza?->tiri_approntamento_data_conseguimento, 6, 'mesi'), // 6 mesi
                'mantenimento_arma_lunga' => $this->calcolaScadenza($scadenza?->mantenimento_arma_lunga_data_conseguimento, 6, 'mesi'), // 6 mesi
                'mantenimento_arma_corta' => $this->calcolaScadenza($scadenza?->mantenimento_arma_corta_data_conseguimento, 6, 'mesi'), // 6 mesi
            ];
        });
        
        return view('scadenze.poligoni', compact('data'));
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
        
        // Calcola la nuova scadenza (tutti i poligoni hanno durata 6 mesi)
        $scadenzaCalcolata = $this->calcolaScadenza($scadenza->$campo, 6, 'mesi');
        
        return response()->json([
            'success' => true,
            'scadenza' => $scadenzaCalcolata,
        ]);
    }
    
    /**
     * Calcola la scadenza e lo stato
     */
    private function calcolaScadenza($dataConseguimento, $durata, $unita = 'anni')
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
        $scadenza = $unita === 'mesi' 
            ? $data->copy()->addMonths($durata) 
            : $data->copy()->addYears($durata);
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
