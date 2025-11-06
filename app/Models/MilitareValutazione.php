<?php

/**
 * SUGECO: Sistema Unico di Gestione e Controllo
 * 
 * Questo file fa parte del sistema SUGECO per la gestione militare digitale.
 * 
 * @package    SUGECO
 * @subpackage Models
 * @version    2.1.0
 * @author     Michele Di Gennaro
 * @copyright 2025 Michele Di Gennaro
 * @license    Proprietary
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modello per le valutazioni dei militari
 * 
 * Questo modello rappresenta le valutazioni periodiche dei militari
 * effettuate dai valutatori. Include criteri di valutazione standardizzati
 * e calcoli automatici delle medie.
 * 
 * @package App\Models
 * @version 1.0
 * 
 * @property int $id ID univoco della valutazione
 * @property int $militare_id ID del militare valutato
 * @property int $valutatore_id ID dell'utente valutatore
 * @property int $precisione_lavoro Voto per precisione nel lavoro (1-10)
 * @property int $affidabilita Voto per affidabilità (1-10)
 * @property int $capacita_tecnica Voto per capacità tecnica (1-10)
 * @property int $collaborazione Voto per collaborazione (1-10)
 * @property int $iniziativa Voto per iniziativa (1-10)
 * @property int $autonomia Voto per autonomia (1-10)
 * @property string|null $note_positive Note positive
 * @property string|null $note_negative Note negative
 * @property \Illuminate\Support\Carbon|null $created_at Data creazione
 * @property \Illuminate\Support\Carbon|null $updated_at Data ultimo aggiornamento
 * 
 * @property-read \App\Models\Militare $militare Militare valutato
 * @property-read \App\Models\User $valutatore Utente che ha fatto la valutazione
 * @property-read float $media Media calcolata di tutti i criteri
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|MilitareValutazione perMilitare(int $militareId)
 * @method static \Illuminate\Database\Eloquent\Builder|MilitareValutazione perValutatore(int $valutatoreId)
 */
class MilitareValutazione extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     * 
     * @var string
     */
    protected $table = 'militare_valutazioni';

    /**
     * Gli attributi che sono mass assignable
     * 
     * @var array<string>
     */
    protected $fillable = [
        'militare_id',
        'valutatore_id',
        'precisione_lavoro',
        'affidabilita',
        'capacita_tecnica',
        'collaborazione',
        'iniziativa',
        'autonomia',
        'note_positive',
        'note_negative'
    ];

    /**
     * Gli attributi che dovrebbero essere cast
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'precisione_lavoro' => 'integer',
        'affidabilita' => 'integer',
        'capacita_tecnica' => 'integer',
        'collaborazione' => 'integer',
        'iniziativa' => 'integer',
        'autonomia' => 'integer',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Relazione con il militare valutato
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function militare()
    {
        return $this->belongsTo(Militare::class);
    }

    /**
     * Relazione con l'utente che ha fatto la valutazione
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function valutatore()
    {
        return $this->belongsTo(User::class, 'valutatore_id');
    }

    // ==========================================
    // ATTRIBUTI CALCOLATI
    // ==========================================

    /**
     * Calcola la media di tutte le valutazioni
     * 
     * @return float Media dei criteri di valutazione
     */
    public function getMediaAttribute()
    {
        $criteri = [
            $this->precisione_lavoro,
            $this->affidabilita,
            $this->capacita_tecnica,
            $this->collaborazione,
            $this->iniziativa,
            $this->autonomia
        ];

        $validi = array_filter($criteri, function($valore) {
            return $valore > 0;
        });

        return count($validi) > 0 ? round(array_sum($validi) / count($validi), 1) : 0;
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per ottenere le valutazioni di un militare
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $militareId ID del militare
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerMilitare($query, $militareId)
    {
        return $query->where('militare_id', $militareId);
    }

    /**
     * Scope per ottenere le valutazioni fatte da un valutatore
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $valutatoreId ID del valutatore
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerValutatore($query, $valutatoreId)
    {
        return $query->where('valutatore_id', $valutatoreId);
    }

    // ==========================================
    // METODI STATICI
    // ==========================================

    /**
     * Restituisce i criteri di valutazione con le loro etichette
     * 
     * @return array<string, string> Array associativo criteri => etichette
     */
    public static function getCriteri()
    {
        return [
            'precisione_lavoro' => 'Precisione nel lavoro',
            'affidabilita' => 'Affidabilità',
            'capacita_tecnica' => 'Capacità tecnica',
            'collaborazione' => 'Collaborazione',
            'iniziativa' => 'Iniziativa',
            'autonomia' => 'Autonomia'
        ];
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Verifica se la valutazione è completa (tutti i criteri compilati)
     * 
     * @return bool True se tutti i criteri sono stati valutati
     */
    public function isCompleta()
    {
        $criteri = [
            $this->precisione_lavoro,
            $this->affidabilita,
            $this->capacita_tecnica,
            $this->collaborazione,
            $this->iniziativa,
            $this->autonomia
        ];

        return !in_array(0, $criteri) && !in_array(null, $criteri);
    }

    /**
     * Ottiene il giudizio testuale basato sulla media
     * 
     * @return string Giudizio testuale
     */
    public function getGiudizioTestuale()
    {
        $media = $this->media;
        
        if ($media >= 9) return 'Eccellente';
        if ($media >= 8) return 'Ottimo';
        if ($media >= 7) return 'Buono';
        if ($media >= 6) return 'Sufficiente';
        if ($media >= 5) return 'Mediocre';
        
        return 'Insufficiente';
    }

    /**
     * Ottiene la classe CSS per il colore del giudizio
     * 
     * @return string Classe CSS appropriata
     */
    public function getGiudizioCssClass()
    {
        $media = $this->media;
        
        if ($media >= 8) return 'text-success';
        if ($media >= 6) return 'text-warning';
        
        return 'text-danger';
    }
}
