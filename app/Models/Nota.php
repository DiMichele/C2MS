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
 * Modello per le note sui militari
 * 
 * Questo modello rappresenta le note e osservazioni sui militari.
 * Le note vengono create dagli utenti autorizzati per tenere traccia
 * di comportamenti, eventi e valutazioni informali.
 * 
 * @package App\Models
 * @version 1.0
 * 
 * @property int $id ID univoco della nota
 * @property int $militare_id ID del militare
 * @property int $user_id ID dell'utente che ha creato la nota
 * @property string $contenuto Contenuto della nota
 * @property \Illuminate\Support\Carbon|null $created_at Data creazione
 * @property \Illuminate\Support\Carbon|null $updated_at Data ultimo aggiornamento
 * 
 * @property-read \App\Models\Militare $militare Militare associato
 * @property-read \App\Models\User $user Utente che ha creato la nota
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|Nota perMilitare(int $militareId) Per militare specifico
 * @method static \Illuminate\Database\Eloquent\Builder|Nota perUtente(int $userId) Per utente specifico
 * @method static \Illuminate\Database\Eloquent\Builder|Nota recenti(int $giorni = 30) Note recenti
 * @method static \Illuminate\Database\Eloquent\Builder|Nota cronologiche() Ordinate cronologicamente (più recenti prima)
 */
class Nota extends Model
{
    use HasFactory;

    /**
     * Gli attributi che sono mass assignable
     * 
     * @var array<string>
     */
    protected $fillable = [
        'militare_id',
        'user_id',
        'contenuto',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Relazione con il militare associato alla nota
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function militare()
    {
        return $this->belongsTo(Militare::class);
    }

    /**
     * Relazione con l'utente che ha creato la nota
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per filtrare note per militare
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
     * Scope per filtrare note per utente creatore (Sistema monoutente - sempre utente 1)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId ID dell'utente (ignorato in sistema monoutente)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerUtente($query, $userId = 1)
    {
        return $query->where('user_id', 1); // Sistema monoutente
    }

    /**
     * Scope per ottenere note recenti
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $giorni Numero di giorni per considerare "recente"
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecenti($query, $giorni = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($giorni));
    }

    /**
     * Scope per ordinare cronologicamente (più recenti prima)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCronologiche($query)
    {
        return $query->orderByDesc('created_at');
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Ottiene la data di creazione formattata
     * 
     * @return string Data formattata (es. "01/01/2024 14:30")
     */
    public function getDataCreazione()
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    /**
     * Ottiene il contenuto della nota troncato
     * 
     * @param int $lunghezza Lunghezza massima
     * @return string Contenuto troncato
     */
    public function getContenutoTroncato($lunghezza = 100)
    {
        if (strlen($this->contenuto) <= $lunghezza) {
            return $this->contenuto;
        }
        
        return substr($this->contenuto, 0, $lunghezza) . '...';
    }

    /**
     * Verifica se la nota è recente
     * 
     * @param int $giorni Numero di giorni per considerare "recente"
     * @return bool True se è recente
     */
    public function isRecente($giorni = 7)
    {
        return $this->created_at >= now()->subDays($giorni);
    }

    /**
     * Ottiene il tempo trascorso dalla creazione in formato umano
     * 
     * @return string Tempo trascorso (es. "2 ore fa", "3 giorni fa")
     */
    public function getTempoTrascorso()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Ottiene la lunghezza del contenuto
     * 
     * @return int Numero di caratteri
     */
    public function getLunghezzaContenuto()
    {
        return strlen($this->contenuto);
    }

    /**
     * Verifica se la nota è lunga
     * 
     * @param int $soglia Soglia di caratteri per considerare "lunga"
     * @return bool True se è lunga
     */
    public function isLunga($soglia = 500)
    {
        return $this->getLunghezzaContenuto() > $soglia;
    }

    /**
     * Ottiene il tipo di nota basato sul contenuto
     * 
     * @return string Tipo di nota
     */
    public function getTipo()
    {
        $contenuto = strtolower($this->contenuto);
        
        $parolePositive = ['ottimo', 'eccellente', 'bravo', 'buono', 'positivo', 'merito'];
        $paroleNegative = ['problema', 'negativo', 'errore', 'sbaglio', 'cattivo', 'disciplinare'];
        $paroleAmministrative = ['trasferimento', 'congedo', 'permesso', 'documenti', 'pratica'];
        
        foreach ($parolePositive as $parola) {
            if (str_contains($contenuto, $parola)) {
                return 'Positiva';
            }
        }
        
        foreach ($paroleNegative as $parola) {
            if (str_contains($contenuto, $parola)) {
                return 'Negativa';
            }
        }
        
        foreach ($paroleAmministrative as $parola) {
            if (str_contains($contenuto, $parola)) {
                return 'Amministrativa';
            }
        }
        
        return 'Generale';
    }

    /**
     * Ottiene la classe CSS per il tipo di nota
     * 
     * @return string Classe CSS appropriata
     */
    public function getTipoCssClass()
    {
        $tipo = $this->getTipo();
        
        return match($tipo) {
            'Positiva' => 'badge-success',
            'Negativa' => 'badge-danger',
            'Amministrativa' => 'badge-info',
            default => 'badge-secondary'
        };
    }

    /**
     * Ottiene l'icona appropriata per il tipo di nota
     * 
     * @return string Classe icona FontAwesome
     */
    public function getTipoIcon()
    {
        $tipo = $this->getTipo();
        
        return match($tipo) {
            'Positiva' => 'fas fa-thumbs-up',
            'Negativa' => 'fas fa-exclamation-triangle',
            'Amministrativa' => 'fas fa-file-alt',
            default => 'fas fa-sticky-note'
        };
    }

    /**
     * Verifica se l'utente può modificare questa nota (Sistema monoutente - sempre true)
     * 
     * @param \App\Models\User|null $user Utente da verificare (ignorato)
     * @return bool True se può modificare
     */
    public function canEdit($user = null)
    {
        // Sistema monoutente - tutte le note possono essere modificate
        return true;
    }

    /**
     * Verifica se l'utente può eliminare questa nota (Sistema monoutente - sempre true)
     * 
     * @param \App\Models\User|null $user Utente da verificare (ignorato)
     * @return bool True se può eliminare
     */
    public function canDelete($user = null)
    {
        // Sistema monoutente - tutte le note possono essere eliminate
        return true;
    }
}
