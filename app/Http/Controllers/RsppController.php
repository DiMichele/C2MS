<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\ScadenzaMilitare;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RsppController extends Controller
{
    /**
     * Visualizza la pagina RSPP con tutte le scadenze relative alla sicurezza
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
                'lavoratore_4h' => $this->calcolaScadenza($scadenza?->lavoratore_4h_data_conseguimento, 4), // 4 anni
                'lavoratore_8h' => $this->calcolaScadenza($scadenza?->lavoratore_8h_data_conseguimento, 4), // 4 anni
                'preposto' => $this->calcolaScadenza($scadenza?->preposto_data_conseguimento, 2), // 2 anni
                'dirigente' => $this->calcolaScadenza($scadenza?->dirigenti_data_conseguimento, 4), // 4 anni (CORRETTO!)
                'antincendio' => $this->calcolaScadenza($scadenza?->antincendio_data_conseguimento, 1), // 1 anno
                'blsd' => $this->calcolaScadenza($scadenza?->blsd_data_conseguimento, 2), // 2 anni (CORRETTO!)
                'primo_soccorso_aziendale' => $this->calcolaScadenza($scadenza?->primo_soccorso_aziendale_data_conseguimento, 2), // 2 anni
            ];
        });
        
        return view('scadenze.rspp', compact('data'));
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
        
        // Ottieni o crea il record scadenza
        $scadenza = $militare->scadenza;
        if (!$scadenza) {
            $scadenza = new ScadenzaMilitare();
            $scadenza->militare_id = $militare->id;
            $scadenza->save(); // Salva prima il record con solo il militare_id
        }
        
        $campo = $request->campo;
        
        $scadenza->$campo = $request->data;
        $scadenza->save();
        
        // Calcola la nuova scadenza (durate corrette!)
        $anni = match($campo) {
            'lavoratore_4h_data_conseguimento', 'lavoratore_8h_data_conseguimento', 'dirigenti_data_conseguimento' => 4, // 4 anni
            'preposto_data_conseguimento', 'primo_soccorso_aziendale_data_conseguimento', 'blsd_data_conseguimento' => 2, // 2 anni
            'antincendio_data_conseguimento' => 1, // 1 anno
            default => 1
        };
        
        $scadenzaCalcolata = $this->calcolaScadenza($scadenza->$campo, $anni);
        
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
