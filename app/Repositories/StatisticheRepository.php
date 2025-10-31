<?php

namespace App\Repositories;

use App\Models\Militare;
use App\Models\Presenza;
// use App\Models\CertificatiLavoratori; // DEPRECATO - tabelle rimosse
// use App\Models\Idoneita; // DEPRECATO - tabelle rimosse
use App\Models\Evento;
use App\Models\Assenza;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * SUGECO: Sistema Unico di Gestione e Controllo
 * 
 * Repository per la gestione delle statistiche e dei report della dashboard.
 * Centralizza le query complesse per le statistiche con sistema di cache ottimizzato.
 * 
 * @package C2MS
 * @subpackage Repositories
 * @version 1.0
 * @since 1.0
 * @author Michele Di Gennaro
 * @copyright 2025 C2MS
 * 
 * @method array getStatisticheGenerali() Recupera statistiche generali del sistema
 * @method array getStatistichePresenze(int $rangeGiorni) Recupera statistiche presenze per periodo
 * @method \Illuminate\Database\Eloquent\Collection getCertificatiInScadenza(int $limit) Recupera certificati in scadenza
 * @method \Illuminate\Database\Eloquent\Collection getIdoneitaInScadenza(int $limit) Recupera idoneità in scadenza
 * @method \Illuminate\Database\Eloquent\Collection getEventiProgrammati(int $limit) Recupera eventi programmati
 * @method void invalidateCache() Invalida tutte le cache delle statistiche
 */
class StatisticheRepository
{
    // ==========================================
    // COSTANTI
    // ==========================================
    
    /**
     * Durata cache in secondi (1 ora)
     * 
     * @var int
     */
    protected const CACHE_DURATION = 3600;
    
    /**
     * Prefisso chiavi cache
     * 
     * @var string
     */
    protected const CACHE_PREFIX = 'stats';
    
    /**
     * Giorni per considerare certificati in scadenza
     * 
     * @var int
     */
    protected const GIORNI_SCADENZA = 30;
    
    // ==========================================
    // METODI STATISTICHE GENERALI
    // ==========================================
    
    /**
     * Ottiene statistiche generali del sistema
     * 
     * Recupera i contatori principali per la dashboard:
     * - Totale militari
     * - Presenti oggi
     * - Certificati in scadenza
     * - Idoneità in scadenza
     * - Eventi attivi oggi
     * - Assenze oggi
     * 
     * @return array Array associativo con le statistiche
     */
    public function getStatisticheGenerali()
    {
        return Cache::remember(self::CACHE_PREFIX . '.generali', self::CACHE_DURATION, function () {
            return [
                'totale_militari' => Militare::count(),
                'presenti_oggi' => Presenza::oggi()->presenti()->count(),
                'certificati_in_scadenza' => 0, // DEPRECATO - usare Scadenze
                'idoneita_in_scadenza' => 0, // DEPRECATO - usare Scadenze
                'eventi_oggi' => Evento::attiviOggi()->count(),
                'assenze_oggi' => Assenza::attiveOggi()->count(),
            ];
        });
    }
    
    // ==========================================
    // METODI STATISTICHE PRESENZE
    // ==========================================
    
    /**
     * Ottiene statistiche presenze per un range di giorni
     * 
     * Genera un grafico delle presenze/assenze per il periodo specificato.
     * I dati vengono normalizzati per includere tutti i giorni del range,
     * anche quelli senza dati.
     * 
     * @param int $rangeGiorni Numero di giorni da analizzare (default: 7)
     * @return array Array con dati giornalieri di presenze/assenze
     */
    public function getStatistichePresenze($rangeGiorni = 7)
    {
        $cacheKey = self::CACHE_PREFIX . ".presenze.{$rangeGiorni}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($rangeGiorni) {
            $oggi = Carbon::today();
            $dataInizio = $oggi->copy()->subDays($rangeGiorni);
            
            // Query aggregata per presenze/assenze
            $presenze = Presenza::select(
                    DB::raw('DATE(data) as giorno'),
                    DB::raw('COUNT(CASE WHEN stato = "Presente" THEN 1 END) as presenti'),
                    DB::raw('COUNT(CASE WHEN stato != "Presente" THEN 1 END) as assenti')
                )
                ->whereBetween('data', [$dataInizio->format('Y-m-d'), $oggi->format('Y-m-d')])
                ->groupBy('giorno')
                ->orderBy('giorno')
                ->get();
            
            // Normalizza i dati (aggiungi giorni mancanti con valori zero)
            $dateRange = [];
            for ($i = 0; $i <= $rangeGiorni; $i++) {
                $data = $dataInizio->copy()->addDays($i)->format('Y-m-d');
                $dateRange[$data] = [
                    'giorno' => $data,
                    'presenti' => 0,
                    'assenti' => 0
                ];
            }
            
            // Sovrascrivi con dati reali dove disponibili
            foreach ($presenze as $presenza) {
                $dateRange[$presenza->giorno] = [
                    'giorno' => $presenza->giorno,
                    'presenti' => (int) $presenza->presenti,
                    'assenti' => (int) $presenza->assenti
                ];
            }
            
            return array_values($dateRange);
        });
    }
    
    // ==========================================
    // METODI SCADENZE
    // ==========================================
    
    /**
     * Ottiene certificati in scadenza nei prossimi giorni
     * 
     * @param int $limit Numero massimo di risultati (default: 10)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCertificatiInScadenza($limit = 10)
    {
        // DEPRECATO - usare Scadenze
        return collect();
    }
    
    /**
     * Ottiene idoneità in scadenza nei prossimi giorni
     * 
     * @param int $limit Numero massimo di risultati (default: 10)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getIdoneitaInScadenza($limit = 10)
    {
        // DEPRECATO - usare Scadenze
        return collect();
    }
    
    // ==========================================
    // METODI EVENTI
    // ==========================================
    
    /**
     * Ottiene eventi programmati per i prossimi giorni
     * 
     * @param int $limit Numero massimo di risultati (default: 5)
     * @return \Illuminate\Support\Collection Collezione raggruppata per nome evento
     */
    public function getEventiProgrammati($limit = 5)
    {
        $cacheKey = self::CACHE_PREFIX . ".eventi_programmati.{$limit}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($limit) {
            $oggi = Carbon::today()->format('Y-m-d');
            
            return Evento::with(['militare.grado'])
                ->where('data_inizio', '>=', $oggi)
                ->orderBy('data_inizio')
                ->take($limit)
                ->get()
                ->groupBy('nome');
        });
    }
    
    // ==========================================
    // METODI GESTIONE CACHE
    // ==========================================
    
    /**
     * Invalida tutte le cache delle statistiche
     * 
     * Rimuove tutte le chiavi di cache utilizzate dal repository
     * per forzare il ricalcolo delle statistiche.
     * 
     * @return void
     */
    public function invalidateCache()
    {
        $cacheKeys = [
            self::CACHE_PREFIX . '.generali',
            self::CACHE_PREFIX . '.presenze.7',
            self::CACHE_PREFIX . '.presenze.30',
            self::CACHE_PREFIX . '.certificati_scadenza.10',
            self::CACHE_PREFIX . '.idoneita_scadenza.10',
            self::CACHE_PREFIX . '.eventi_programmati.5'
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }
    
    /**
     * Invalida cache specifiche per tipo
     * 
     * @param string $tipo Tipo di cache da invalidare (presenze|certificati|idoneita|eventi|generali)
     * @return void
     */
    public function invalidateCacheByType($tipo)
    {
        switch ($tipo) {
            case 'presenze':
                Cache::forget(self::CACHE_PREFIX . '.presenze.7');
                Cache::forget(self::CACHE_PREFIX . '.presenze.30');
                break;
                
            case 'certificati':
                Cache::forget(self::CACHE_PREFIX . '.certificati_scadenza.10');
                break;
                
            case 'idoneita':
                Cache::forget(self::CACHE_PREFIX . '.idoneita_scadenza.10');
                break;
                
            case 'eventi':
                Cache::forget(self::CACHE_PREFIX . '.eventi_programmati.5');
                break;
                
            case 'generali':
                Cache::forget(self::CACHE_PREFIX . '.generali');
                break;
        }
    }
    
    /**
     * Ottiene informazioni sullo stato della cache
     * 
     * @return array Array con informazioni sulla cache
     */
    public function getCacheInfo()
    {
        $cacheKeys = [
            'generali' => self::CACHE_PREFIX . '.generali',
            'presenze_7' => self::CACHE_PREFIX . '.presenze.7',
            'presenze_30' => self::CACHE_PREFIX . '.presenze.30',
            'certificati' => self::CACHE_PREFIX . '.certificati_scadenza.10',
            'idoneita' => self::CACHE_PREFIX . '.idoneita_scadenza.10',
            'eventi' => self::CACHE_PREFIX . '.eventi_programmati.5'
        ];
        
        $info = [];
        foreach ($cacheKeys as $name => $key) {
            $info[$name] = [
                'key' => $key,
                'exists' => Cache::has($key),
                'ttl' => Cache::has($key) ? self::CACHE_DURATION : 0
            ];
        }
        
        return $info;
    }
}
