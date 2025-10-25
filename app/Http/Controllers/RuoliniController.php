<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\Compagnia;
use App\Models\Plotone;
use App\Models\PianificazioneGiornaliera;
// use App\Models\AssegnazioneTurno; // DISABILITATO - tabella non esiste
use App\Models\BoardActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Controller per la gestione dei Ruolini
 * 
 * Gestisce la visualizzazione del personale presente e assente
 * per una data selezionata, diviso per categorie (Ufficiali, Sottufficiali, Graduati, Volontari).
 */
class RuoliniController extends Controller
{
    /**
     * Mostra la pagina dei ruolini con il personale diviso per categorie
     */
    public function index(Request $request)
    {
        // Gestione data - default oggi
        $dataSelezionata = $request->get('data', Carbon::today()->format('Y-m-d'));
        $dataObj = Carbon::parse($dataSelezionata);
        
        // Recupera filtri dalla richiesta
        $compagniaId = $request->get('compagnia_id');
        $plotoneId = $request->get('plotone_id');
        
        // Recupera tutte le compagnie e plotoni per i filtri
        $compagnie = Compagnia::with('plotoni')->orderBy('nome')->get();
        $plotoni = Plotone::with('compagnia')->orderBy('nome')->get();
        
        // Query base per i militari
        $query = Militare::with([
            'grado',
            'plotone.compagnia',
            'compagnia'
        ])->orderByGradoENome();
        
        // Applica filtri
        if ($compagniaId) {
            $query->where(function($q) use ($compagniaId) {
                $q->whereHas('plotone', function($subq) use ($compagniaId) {
                    $subq->where('compagnia_id', $compagniaId);
                })
                ->orWhere('compagnia_id', $compagniaId);
            });
        }
        
        if ($plotoneId) {
            $query->where('plotone_id', $plotoneId);
        }
        
        $militari = $query->get();
        
        // Dividi militari per categoria
        $categorie = [
            'Ufficiali' => ['presenti' => [], 'assenti' => []],
            'Sottufficiali' => ['presenti' => [], 'assenti' => []],
            'Graduati' => ['presenti' => [], 'assenti' => []],
            'Volontari' => ['presenti' => [], 'assenti' => []],
        ];
        
        foreach ($militari as $militare) {
            $categoria = $this->getCategoriaGrado($militare->grado);
            $impegni = $this->getImpegniMilitare($militare, $dataSelezionata, $dataObj);
            
            if (empty($impegni)) {
                $categorie[$categoria]['presenti'][] = [
                    'militare' => $militare,
                ];
            } else {
                $categorie[$categoria]['assenti'][] = [
                    'militare' => $militare,
                    'impegni' => $impegni,
                ];
            }
        }
        
        // Calcola totali per categoria
        $totali = [];
        foreach ($categorie as $nome => $dati) {
            $totali[$nome] = [
                'presenti' => count($dati['presenti']),
                'assenti' => count($dati['assenti']),
                'totale' => count($dati['presenti']) + count($dati['assenti']),
            ];
        }
        
        return view('ruolini.index', compact(
            'categorie',
            'totali',
            'compagnie',
            'plotoni',
            'compagniaId',
            'plotoneId',
            'dataSelezionata',
            'dataObj'
        ));
    }
    
    /**
     * Determina la categoria del grado
     * 
     * @param \App\Models\Grado|null $grado
     * @return string
     */
    private function getCategoriaGrado($grado): string
    {
        if (!$grado || !$grado->categoria) {
            return 'Volontari'; // Default per militari senza grado
        }
        
        // Normalizza la categoria
        $categoria = trim($grado->categoria);
        
        // Mappa le categorie esistenti
        if (in_array($categoria, ['Ufficiali', 'Sottufficiali', 'Graduati'])) {
            return $categoria;
        }
        
        // Tutti gli altri (Soldati, VFP, ecc.) vanno in Volontari
        return 'Volontari';
    }
    
    /**
     * Recupera tutti gli impegni di un militare per una data specifica
     * 
     * @param Militare $militare
     * @param string $data Data nel formato Y-m-d
     * @param Carbon $dataObj Oggetto Carbon della data
     * @return array Array di impegni con tipo e descrizione
     */
    private function getImpegniMilitare(Militare $militare, string $data, Carbon $dataObj): array
    {
        $impegni = [];
        
        // 1. Controlla CPT (Pianificazione Giornaliera)
        $pianificazioneCpt = PianificazioneGiornaliera::where('militare_id', $militare->id)
            ->whereHas('pianificazioneMensile', function($q) use ($dataObj) {
                $q->where('mese', $dataObj->month)
                  ->where('anno', $dataObj->year);
            })
            ->where('giorno', $dataObj->day)
            ->with('tipoServizio')
            ->first();
        
        if ($pianificazioneCpt && $pianificazioneCpt->tipoServizio) {
            $impegni[] = [
                'tipo' => 'CPT',
                'descrizione' => $pianificazioneCpt->tipoServizio->nome,
                'codice' => $pianificazioneCpt->tipoServizio->codice,
                'colore' => $pianificazioneCpt->tipoServizio->colore_badge ?? '#6c757d',
            ];
        }
        
        // 2. Controlla Turni Settimanali - DISABILITATO (tabella assegnazioni_turno non esiste)
        // $turno = AssegnazioneTurno::where('militare_id', $militare->id)
        //     ->where('data_servizio', $data)
        //     ->with('servizioTurno')
        //     ->first();
        // 
        // if ($turno && $turno->servizioTurno) {
        //     $impegni[] = [
        //         'tipo' => 'Turno',
        //         'descrizione' => $turno->servizioTurno->nome,
        //         'codice' => $turno->servizioTurno->sigla ?? 'TRN',
        //         'colore' => '#0d6efd',
        //     ];
        // }
        
        // 3. Controlla Board Attività
        $attivita = BoardActivity::whereHas('militari', function($q) use ($militare) {
                $q->where('militari.id', $militare->id);
            })
            ->where('start_date', '<=', $dataObj)
            ->where(function($q) use ($dataObj) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $dataObj);
            })
            ->get();
        
        foreach ($attivita as $attivita_item) {
            $impegni[] = [
                'tipo' => 'Attività',
                'descrizione' => $attivita_item->title,
                'codice' => 'ATT',
                'colore' => '#198754',
            ];
        }
        
        return $impegni;
    }
}
