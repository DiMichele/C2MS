<?php

namespace App\Traits;

/**
 * SUGECO: Sistema Unico di Gestione e Controllo
 * 
 * Trait per aggiungere funzionalità di filtro su intervalli di date.
 * Fornisce scope queries comuni per filtrare entità basate su range temporali.
 * 
 * @package    SUGECO
 * @subpackage Traits
 * @version 1.0
 * @since 1.0
 * @author Michele Di Gennaro
 * @copyright 2025 C2MS
 * 
 * @method \Illuminate\Database\Eloquent\Builder scopeInRange(\Illuminate\Database\Eloquent\Builder $query, string $dataInizio, string $dataFine, string $campoInizio, string $campoFine)
 * @method \Illuminate\Database\Eloquent\Builder scopeAttiviAllaData(\Illuminate\Database\Eloquent\Builder $query, string $data, string $campoInizio, string $campoFine)
 * @method \Illuminate\Database\Eloquent\Builder scopeFuturiDopo(\Illuminate\Database\Eloquent\Builder $query, string $data, string $campoInizio)
 * @method \Illuminate\Database\Eloquent\Builder scopePassatiPrima(\Illuminate\Database\Eloquent\Builder $query, string $data, string $campoFine)
 */
trait DateRangeFilterable
{
    // ==========================================
    // SCOPE QUERIES PER INTERVALLI
    // ==========================================
    
    /**
     * Filtra entità che si sovrappongono con l'intervallo specificato
     * 
     * Questo scope trova tutte le entità che hanno una qualsiasi sovrapposizione
     * con l'intervallo di date specificato. Include:
     * - Entità che iniziano durante l'intervallo
     * - Entità che finiscono durante l'intervallo  
     * - Entità che coprono completamente l'intervallo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @param string $dataInizio Data di inizio intervallo (formato Y-m-d)
     * @param string $dataFine Data di fine intervallo (formato Y-m-d)
     * @param string $campoInizio Nome del campo data inizio (default: 'data_inizio')
     * @param string $campoFine Nome del campo data fine (default: 'data_fine')
     * @return \Illuminate\Database\Eloquent\Builder Query modificata
     * 
     * @example
     * // Trova eventi che si sovrappongono con gennaio 2025
     * $eventi = Evento::inRange('2025-01-01', '2025-01-31')->get();
     */
    public function scopeInRange($query, $dataInizio, $dataFine, $campoInizio = 'data_inizio', $campoFine = 'data_fine')
    {
        return $query->where(function ($q) use ($dataInizio, $dataFine, $campoInizio, $campoFine) {
            // L'entità inizia durante l'intervallo selezionato
            $q->whereBetween($campoInizio, [$dataInizio, $dataFine])
              // O l'entità finisce durante l'intervallo selezionato
              ->orWhereBetween($campoFine, [$dataInizio, $dataFine])
              // O l'entità copre completamente l'intervallo selezionato
              ->orWhere(function ($q) use ($dataInizio, $dataFine, $campoInizio, $campoFine) {
                  $q->where($campoInizio, '<=', $dataInizio)
                    ->where($campoFine, '>=', $dataFine);
              });
        });
    }
    
    /**
     * Filtra entità attive alla data specificata
     * 
     * Trova tutte le entità che sono attive (in corso) alla data specificata,
     * ovvero quelle che hanno iniziato prima o alla data e non sono ancora finite.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @param string $data Data di riferimento (formato Y-m-d)
     * @param string $campoInizio Nome del campo data inizio (default: 'data_inizio')
     * @param string $campoFine Nome del campo data fine (default: 'data_fine')
     * @return \Illuminate\Database\Eloquent\Builder Query modificata
     * 
     * @example
     * // Trova eventi attivi oggi
     * $eventiOggi = Evento::attiviAllaData(now()->format('Y-m-d'))->get();
     */
    public function scopeAttiviAllaData($query, $data, $campoInizio = 'data_inizio', $campoFine = 'data_fine')
    {
        return $query->where(function ($q) use ($data, $campoInizio, $campoFine) {
            $q->where($campoInizio, '<=', $data)
              ->where($campoFine, '>=', $data);
        });
    }
    
    /**
     * Filtra entità future rispetto alla data specificata
     * 
     * Trova tutte le entità che iniziano dopo la data specificata.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @param string $data Data di riferimento (formato Y-m-d)
     * @param string $campoInizio Nome del campo data inizio (default: 'data_inizio')
     * @return \Illuminate\Database\Eloquent\Builder Query modificata
     * 
     * @example
     * // Trova eventi futuri
     * $eventiFuturi = Evento::futuriDopo(now()->format('Y-m-d'))->get();
     */
    public function scopeFuturiDopo($query, $data, $campoInizio = 'data_inizio')
    {
        return $query->where($campoInizio, '>', $data);
    }
    
    /**
     * Filtra entità passate rispetto alla data specificata
     * 
     * Trova tutte le entità che sono terminate prima della data specificata.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @param string $data Data di riferimento (formato Y-m-d)
     * @param string $campoFine Nome del campo data fine (default: 'data_fine')
     * @return \Illuminate\Database\Eloquent\Builder Query modificata
     * 
     * @example
     * // Trova eventi terminati
     * $eventiPassati = Evento::passatiPrima(now()->format('Y-m-d'))->get();
     */
    public function scopePassatiPrima($query, $data, $campoFine = 'data_fine')
    {
        return $query->where($campoFine, '<', $data);
    }
    
    // ==========================================
    // SCOPE QUERIES AVANZATE
    // ==========================================
    
    /**
     * Filtra entità che iniziano in un determinato periodo
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @param string $dataInizio Data inizio periodo (formato Y-m-d)
     * @param string $dataFine Data fine periodo (formato Y-m-d)
     * @param string $campoInizio Nome del campo data inizio (default: 'data_inizio')
     * @return \Illuminate\Database\Eloquent\Builder Query modificata
     */
    public function scopeInizianoTra($query, $dataInizio, $dataFine, $campoInizio = 'data_inizio')
    {
        return $query->whereBetween($campoInizio, [$dataInizio, $dataFine]);
    }
    
    /**
     * Filtra entità che terminano in un determinato periodo
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @param string $dataInizio Data inizio periodo (formato Y-m-d)
     * @param string $dataFine Data fine periodo (formato Y-m-d)
     * @param string $campoFine Nome del campo data fine (default: 'data_fine')
     * @return \Illuminate\Database\Eloquent\Builder Query modificata
     */
    public function scopeTerminanoTra($query, $dataInizio, $dataFine, $campoFine = 'data_fine')
    {
        return $query->whereBetween($campoFine, [$dataInizio, $dataFine]);
    }
    
    /**
     * Filtra entità con durata specifica (in giorni)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @param int $giorni Durata in giorni
     * @param string $operatore Operatore di confronto ('=', '>', '<', '>=', '<=')
     * @param string $campoInizio Nome del campo data inizio (default: 'data_inizio')
     * @param string $campoFine Nome del campo data fine (default: 'data_fine')
     * @return \Illuminate\Database\Eloquent\Builder Query modificata
     */
    public function scopeConDurata($query, $giorni, $operatore = '=', $campoInizio = 'data_inizio', $campoFine = 'data_fine')
    {
        return $query->whereRaw("DATEDIFF({$campoFine}, {$campoInizio}) + 1 {$operatore} ?", [$giorni]);
    }
}
