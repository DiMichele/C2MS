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
use App\Models\Militare;
use App\Models\Presenza;
// use App\Models\CertificatiLavoratori; // DEPRECATO - tabelle rimosse
// use App\Models\Idoneita; // DEPRECATO - tabelle rimosse
use App\Models\Plotone;
use App\Models\Polo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Controller per la gestione della dashboard principale
 * 
 * Questo controller gestisce la visualizzazione della dashboard con statistiche,
 * grafici e informazioni aggregate sui militari, utilizzando un sistema di cache
 * per ottimizzare le performance.
 * 
 * @package App\Http\Controllers
 * @version 1.0
 */
class DashboardController extends Controller
{
    /**
     * Durata della cache in secondi per statistiche generali
     */
    private const CACHE_DURATION = 3600; // 1 ora
    
    /**
     * Durata della cache in secondi per dati dinamici (presenze)
     */
    private const CACHE_DURATION_DYNAMIC = 600; // 10 minuti
    
    /**
     * Mostra la dashboard con statistiche e grafici
     * 
     * Carica tutte le statistiche principali utilizzando il sistema di cache
     * per migliorare le performance. Include:
     * - Statistiche generali (totale militari, presenze, scadenze)
     * - Distribuzione per plotoni e poli
     * - Certificati e idoneità in scadenza
     * - Percentuali di presenza
     * 
     * @return \Illuminate\View\View Vista della dashboard con tutti i dati
     */
    public function index()
    {
        // Statistiche di base (con caching)
        $stats = Cache::remember('dashboard.stats', self::CACHE_DURATION, function () {
            return [
                'totale_militari' => Militare::count(),
                'presenti_oggi' => Presenza::oggi()->presenti()->count(),
                'certificati_in_scadenza' => 0, // DEPRECATO - usare Scadenze
                'idoneita_in_scadenza' => 0, // DEPRECATO - usare Scadenze
            ];
        });
        
        // Plotoni più popolosi (con caching)
        $plotoni = Cache::remember('dashboard.plotoni', self::CACHE_DURATION, function () {
            return Plotone::withCount('militari')
                        ->orderByDesc('militari_count')
                        ->take(5)
                        ->get();
        });
        
        // Poli più popolosi (con caching)
        $poli = Cache::remember('dashboard.poli', self::CACHE_DURATION, function () {
            return Polo::withCount('militari')
                   ->orderByDesc('militari_count')
                   ->take(5)
                   ->get();
        });
        
        // DEPRECATO - usare Scadenze
        $certificati = collect();
        $idoneita = collect();
        
        // Presenti e assenti oggi (cache dinamica)
        $presentiOggiCount = Cache::remember('dashboard.presenti_oggi_count', self::CACHE_DURATION_DYNAMIC, function () {
            return Militare::presenti()->count();
        });
        
        $assentiOggiCount = Cache::remember('dashboard.assenti_oggi_count', self::CACHE_DURATION_DYNAMIC, function () {
            return Militare::assenti()->count();
        });
        
        // Calcola percentuale di presenze
        $totale = $presentiOggiCount + $assentiOggiCount;
        $percentualePresenti = $totale > 0 ? round(($presentiOggiCount / $totale) * 100) : 0;
        
        // Militari in eventi oggi (cache dinamica)
        $militariInEvento = Cache::remember('dashboard.militari_in_evento', self::CACHE_DURATION_DYNAMIC, function () {
            $oggi = Carbon::today()->toDateString();
            return Militare::inEvento($oggi)->count();
        });
        
        return view('dashboard', compact(
            'stats', 
            'plotoni', 
            'poli', 
            'certificati', 
            'idoneita',
            'presentiOggiCount',
            'assentiOggiCount',
            'percentualePresenti',
            'militariInEvento'
        ));
    }
    
    /**
     * Invalida la cache della dashboard
     * 
     * Rimuove tutte le chiavi di cache utilizzate dalla dashboard
     * per forzare il refresh dei dati al prossimo caricamento.
     * Utile quando vengono effettuate modifiche che devono essere
     * immediatamente visibili nella dashboard.
     * 
     * @return \Illuminate\Http\RedirectResponse Redirect alla dashboard con messaggio di successo
     */
    public function refreshCache()
    {
        $cacheKeys = [
            'dashboard.stats',
            'dashboard.plotoni',
            'dashboard.poli',
            'dashboard.certificati',
            'dashboard.idoneita',
            'dashboard.presenti_oggi_count',
            'dashboard.assenti_oggi_count',
            'dashboard.militari_in_evento'
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        
        return redirect()->route('dashboard')
            ->with('success', 'Cache aggiornata con successo!');
    }
}
