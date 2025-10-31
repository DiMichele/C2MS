<?php

/**
 * SUGECO: Sistema Unico di Gestione e Controllo
 * 
 * Questo file fa parte del sistema C2MS per la gestione militare digitale.
 * 
 * @package    C2MS
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
 * Modello per i ruoli militari
 * 
 * Questo modello rappresenta i ruoli funzionali che possono essere
 * assegnati ai militari. I ruoli definiscono la funzione specifica
 * e le competenze di ciascun militare nell'organizzazione.
 * 
 * @package App\Models
 * @version 1.0
 * 
 * @property int $id ID univoco del ruolo
 * @property string $nome Nome del ruolo
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Militare[] $militari Militari con questo ruolo
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|Ruolo perNome() Ordinati alfabeticamente per nome
 * @method static \Illuminate\Database\Eloquent\Builder|Ruolo conMilitari() Con militari caricati
 */
class Ruolo extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     * 
     * @var string
     */
    protected $table = 'ruoli';
    
    /**
     * Indica se il modello dovrebbe essere timestampato
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * Gli attributi che sono mass assignable
     * 
     * @var array<string>
     */
    protected $fillable = [
        'nome'
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Relazione con i militari che hanno questo ruolo
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function militari()
    {
        return $this->hasMany(Militare::class, 'ruolo_id');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per ordinare i ruoli alfabeticamente per nome
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerNome($query)
    {
        return $query->orderBy('nome');
    }

    /**
     * Scope per caricare i ruoli con i militari
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConMilitari($query)
    {
        return $query->with('militari');
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Ottiene il numero di militari con questo ruolo
     * 
     * @return int Numero di militari
     */
    public function getNumeroMilitari()
    {
        return $this->militari()->count();
    }

    /**
     * Verifica se il ruolo ha militari assegnati
     * 
     * @return bool True se ha militari assegnati
     */
    public function hasMilitari()
    {
        return $this->getNumeroMilitari() > 0;
    }

    /**
     * Ottiene la rappresentazione testuale del ruolo
     * 
     * @return string Nome del ruolo
     */
    public function __toString()
    {
        return $this->nome;
    }

    /**
     * Ottiene la tipologia del ruolo basata sul nome
     * 
     * @return string Tipologia del ruolo
     */
    public function getTipologia()
    {
        $nome = strtolower($this->nome);
        
        if (str_contains($nome, 'ufficiale') || str_contains($nome, 'officer')) {
            return 'Ufficiale';
        }
        
        if (str_contains($nome, 'sottoufficiale') || str_contains($nome, 'nco')) {
            return 'Sottoufficiale';
        }
        
        if (str_contains($nome, 'graduato') || str_contains($nome, 'corporal')) {
            return 'Graduato';
        }
        
        if (str_contains($nome, 'militare di truppa') || str_contains($nome, 'soldier')) {
            return 'Militare di Truppa';
        }
        
        if (str_contains($nome, 'civile') || str_contains($nome, 'civilian')) {
            return 'Personale Civile';
        }
        
        return 'Generico';
    }

    /**
     * Ottiene la classe CSS per la tipologia del ruolo
     * 
     * @return string Classe CSS appropriata
     */
    public function getTipologiaCssClass()
    {
        $tipologia = $this->getTipologia();
        
        return match($tipologia) {
            'Ufficiale' => 'badge-danger',
            'Sottoufficiale' => 'badge-warning',
            'Graduato' => 'badge-primary',
            'Militare di Truppa' => 'badge-info',
            'Personale Civile' => 'badge-secondary',
            default => 'badge-light'
        };
    }

    /**
     * Ottiene l'icona appropriata per la tipologia
     * 
     * @return string Classe icona FontAwesome
     */
    public function getTipologiaIcon()
    {
        $tipologia = $this->getTipologia();
        
        return match($tipologia) {
            'Ufficiale' => 'fas fa-star',
            'Sottoufficiale' => 'fas fa-chevron-up',
            'Graduato' => 'fas fa-shield-alt',
            'Militare di Truppa' => 'fas fa-user-shield',
            'Personale Civile' => 'fas fa-user-tie',
            default => 'fas fa-user'
        };
    }

    /**
     * Verifica se è un ruolo di ufficiale
     * 
     * @return bool True se è un ruolo di ufficiale
     */
    public function isUfficiale()
    {
        return $this->getTipologia() === 'Ufficiale';
    }

    /**
     * Verifica se è un ruolo di sottoufficiale
     * 
     * @return bool True se è un ruolo di sottoufficiale
     */
    public function isSottoufficiale()
    {
        return $this->getTipologia() === 'Sottoufficiale';
    }

    /**
     * Verifica se è un ruolo di graduato
     * 
     * @return bool True se è un ruolo di graduato
     */
    public function isGraduato()
    {
        return $this->getTipologia() === 'Graduato';
    }

    /**
     * Verifica se è un ruolo di militare di truppa
     * 
     * @return bool True se è un ruolo di militare di truppa
     */
    public function isMilitareTruppa()
    {
        return $this->getTipologia() === 'Militare di Truppa';
    }

    /**
     * Verifica se è personale civile
     * 
     * @return bool True se è personale civile
     */
    public function isCivile()
    {
        return $this->getTipologia() === 'Personale Civile';
    }

    /**
     * Ottiene il livello gerarchico del ruolo (1-5, dove 5 è il più alto)
     * 
     * @return int Livello gerarchico
     */
    public function getLivelloGerarchico()
    {
        return match($this->getTipologia()) {
            'Ufficiale' => 5,
            'Sottoufficiale' => 4,
            'Graduato' => 3,
            'Militare di Truppa' => 2,
            'Personale Civile' => 1,
            default => 1
        };
    }

    /**
     * Verifica se questo ruolo è superiore a un altro
     * 
     * @param \App\Models\Ruolo $altroRuolo Ruolo da confrontare
     * @return bool True se questo ruolo è superiore
     */
    public function isSuperioreA(Ruolo $altroRuolo)
    {
        return $this->getLivelloGerarchico() > $altroRuolo->getLivelloGerarchico();
    }

    /**
     * Verifica se questo ruolo è inferiore a un altro
     * 
     * @param \App\Models\Ruolo $altroRuolo Ruolo da confrontare
     * @return bool True se questo ruolo è inferiore
     */
    public function isInferioreA(Ruolo $altroRuolo)
    {
        return $this->getLivelloGerarchico() < $altroRuolo->getLivelloGerarchico();
    }
}
