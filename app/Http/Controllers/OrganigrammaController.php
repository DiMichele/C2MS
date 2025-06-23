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
     * - Compagnia principale
     * - Plotoni con militari associati
     * - Poli con militari associati
     * - Informazioni sulle presenze
     * - Statistiche aggregate
     * 
     * Utilizza eager loading per ottimizzare le query e cache per le performance.
     * 
     * @return \Illuminate\View\View|\Illuminate\View\View Vista dell'organigramma o pagina di errore
     */
    public function index()
    {
        // Ottieni la compagnia con caching
        $compagnia = Cache::remember('organigramma.compagnia', self::CACHE_DURATION, function () {
            return Compagnia::with([
                'plotoni' => function ($q) {
                    $q->orderBy('nome');
                },
                'plotoni.militari' => function ($q) {
                    $q->orderByGradoENome();
                },
                'plotoni.militari.grado',
                'plotoni.militari.presenzaOggi',
                'poli' => function ($q) {
                    $q->orderBy('nome');
                },
                'poli.militari' => function ($q) {
                    $q->orderByGradoENome();
                },
                'poli.militari.grado',
                'poli.militari.presenzaOggi'
            ])->first();
        });
        
        if (!$compagnia) {
            // Se non ci sono compagnie, mostra pagina di errore personalizzata
            return view('errors.custom', [
                'title' => 'Nessuna compagnia trovata',
                'message' => 'Non è stata trovata nessuna compagnia nel sistema. Contattare l\'amministratore.'
            ]);
        }
        
        // Calcola statistiche aggregate (al volo per dati sempre aggiornati)
        $statistiche = $this->calcolaStatistiche($compagnia);
        
        return view('organigramma.organigramma', array_merge(
            compact('compagnia'),
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
    private function calcolaStatistiche(Compagnia $compagnia)
    {
        // Calcola totale effettivi
        $totaleEffettivi = $compagnia->plotoni->sum(function ($plotone) {
            return $plotone->militari->count();
        }) + $compagnia->poli->sum(function ($polo) {
            return $polo->militari->count();
        });
        
        // Calcola totale presenti
        $totalePresenti = $compagnia->plotoni->sum(function ($plotone) {
            return $plotone->militari->filter(function ($militare) {
                return $militare->isPresente();
            })->count();
        }) + $compagnia->poli->sum(function ($polo) {
            return $polo->militari->filter(function ($militare) {
                return $militare->isPresente();
            })->count();
        });
        
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
