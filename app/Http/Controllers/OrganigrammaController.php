<?php

/**
 * SUGECO: Sistema Unico di Gestione e Controllo
 * 
 * Questo file fa parte del sistema SUGECO per la gestione militare digitale.
 * 
 * @package    SUGECO
 * @subpackage Controllers
 * @version    2.1.0
 * @author     Michele Di Gennaro
 * @copyright 2025 Michele Di Gennaro
 * @license    Proprietary
 */

namespace App\Http\Controllers;

use App\Models\Compagnia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Controller per la gestione dell'organigramma militare
 * 
 * Questo controller gestisce la visualizzazione dell'organigramma della compagnia
 * con plotoni, poli e militari associati. Utilizza un sistema di cache per
 * ottimizzare le performance delle query complesse.
 * 
 * @package App\Http\Controllers
 * @version 1.0
 */
class OrganigrammaController extends Controller
{
    /**
     * Durata della cache in secondi
     */
    private const CACHE_DURATION = 3600; // 1 ora
    
    /**
     * Mostra la pagina principale dell'organigramma
     * 
     * Carica la struttura completa dell'organigramma con:
     * - Compagnia principale (filtrabile)
     * - Plotoni con militari associati
     * - Poli con militari associati
     * - Informazioni sulle presenze
     * - Statistiche aggregate
     * 
     * Utilizza eager loading per ottimizzare le query e cache per le performance.
     * L'utente admin può selezionare una compagnia, gli altri vedono solo la propria.
     * 
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\View\View Vista dell'organigramma o pagina di errore
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $compagniaSelezionataId = null;
        
        // Carica tutte le compagnie per il selettore (solo per admin)
        $compagnie = collect();
        if ($user->hasRole('admin') || $user->hasRole('amministratore')) {
            $compagnie = Compagnia::orderBy('nome')->get();
            $compagniaSelezionataId = $request->get('compagnia_id');
        } else {
            // Utente non admin: usa la sua compagnia
            $compagniaSelezionataId = $user->compagnia_id;
        }
        
        // Genera chiave cache unica per la compagnia selezionata
        $cacheKey = 'organigramma.compagnia.' . ($compagniaSelezionataId ?? 'first');
        
        // Ottieni la compagnia con caching
        $compagnia = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($compagniaSelezionataId) {
            $query = Compagnia::with([
                'plotoni' => function ($q) {
                    $q->orderBy('nome');
                },
                'plotoni.militari' => function ($q) {
                    $q->orderByGradoENome();
                },
                'plotoni.militari.grado'
            ]);
            
            if ($compagniaSelezionataId) {
                return $query->find($compagniaSelezionataId);
            }
            
            return $query->first();
        });
        
        // Carica i poli globali con i militari filtrati per compagnia selezionata
        $poli = collect();
        if ($compagnia) {
            $poli = \App\Models\Polo::with(['militari' => function($q) use ($compagnia) {
                $q->where('compagnia_id', $compagnia->id)
                  ->orderByGradoENome();
            }, 'militari.grado'])
            ->whereHas('militari', function($q) use ($compagnia) {
                $q->where('compagnia_id', $compagnia->id);
            })
            ->orderBy('nome')
            ->get();
        }
        
        if (!$compagnia) {
            // Se non ci sono compagnie, mostra pagina di errore personalizzata
            return view('errors.custom', [
                'title' => 'Nessuna compagnia trovata',
                'message' => 'Non è stata trovata nessuna compagnia nel sistema. Contattare l\'amministratore.'
            ]);
        }
        
        // Calcola statistiche aggregate (al volo per dati sempre aggiornati)
        $statistiche = $this->calcolaStatistiche($compagnia, $poli);
        
        return view('organigramma.organigramma', array_merge(
            compact('compagnia', 'compagnie', 'compagniaSelezionataId', 'poli'),
            $statistiche
        ));
    }
    
    /**
     * Invalida la cache dell'organigramma
     * 
     * Rimuove la cache dell'organigramma per forzare il refresh
     * dei dati al prossimo caricamento. Utile quando vengono
     * effettuate modifiche alla struttura organizzativa.
     * 
     * @return \Illuminate\Http\RedirectResponse Redirect all'organigramma con messaggio di successo
     */
    public function refreshCache()
    {
        Cache::forget('organigramma.compagnia');
        
        return redirect()->route('organigramma')
            ->with('success', 'Organigramma aggiornato con successo!');
    }

    /**
     * Calcola le statistiche aggregate dell'organigramma
     * 
     * @param Compagnia $compagnia Compagnia di cui calcolare le statistiche
     * @return array Array con le statistiche calcolate
     */
    private function calcolaStatistiche(Compagnia $compagnia, $poli)
    {
        // Calcola totale effettivi dai plotoni
        $totaleEffettiviPlotoni = $compagnia->plotoni->sum(function ($plotone) {
            return $plotone->militari->count();
        });
        
        // Calcola totale effettivi dai poli (i poli sono globali ma filtrati per compagnia)
        $totaleEffettiviPoli = $poli->sum(function ($polo) {
            return $polo->militari->count();
        });
        
        $totaleEffettivi = $totaleEffettiviPlotoni + $totaleEffettiviPoli;
        
        // Calcola totale presenti dai plotoni
        $totalePresentiPlotoni = $compagnia->plotoni->sum(function ($plotone) {
            return $plotone->militari->filter(fn($m) => $m->isPresente())->count();
        });
        
        // Calcola totale presenti dai poli
        $totalePresentiPoli = $poli->sum(function ($polo) {
            return $polo->militari->filter(fn($m) => $m->isPresente())->count();
        });
        
        $totalePresenti = $totalePresentiPlotoni + $totalePresentiPoli;
        
        // Calcola percentuale presenti
        $percentualePresenti = $totaleEffettivi > 0 
            ? round(($totalePresenti / $totaleEffettivi) * 100) 
            : 0;
        
        return [
            'totaleEffettivi' => $totaleEffettivi,
            'totalePresenti' => $totalePresenti,
            'percentualePresenti' => $percentualePresenti
        ];
    }
}
