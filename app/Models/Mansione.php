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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modello per le mansioni militari
 * 
 * Questo modello rappresenta le mansioni specifiche che possono essere
 * assegnate ai militari. Le mansioni definiscono il ruolo operativo
 * e le responsabilità specifiche di ciascun militare.
 * 
 * @package App\Models
 * @version 1.0
 * 
 * @property int $id ID univoco della mansione
 * @property string $nome Nome della mansione
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Militare[] $militari Militari con questa mansione
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|Mansione perNome() Ordinate alfabeticamente per nome
 * @method static \Illuminate\Database\Eloquent\Builder|Mansione conMilitari() Con militari caricati
 */
class Mansione extends Model
{
    use HasFactory;

    /**
     * Nome della tabella associata al modello
     * 
     * @var string
     */
    protected $table = 'mansioni';
    
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
        'nome',
    ];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Relazione con i militari che hanno questa mansione
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function militari()
    {
        return $this->hasMany(Militare::class, 'mansione_id');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope per ordinare le mansioni alfabeticamente per nome
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerNome($query)
    {
        return $query->orderBy('nome');
    }

    /**
     * Scope per caricare le mansioni con i militari
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
     * Ottiene il numero di militari con questa mansione
     * 
     * @return int Numero di militari
     */
    public function getNumeroMilitari()
    {
        return $this->militari()->count();
    }

    /**
     * Verifica se la mansione ha militari assegnati
     * 
     * @return bool True se ha militari assegnati
     */
    public function hasMilitari()
    {
        return $this->getNumeroMilitari() > 0;
    }

    /**
     * Ottiene la rappresentazione testuale della mansione
     * 
     * @return string Nome della mansione
     */
    public function __toString()
    {
        return $this->nome;
    }

    /**
     * Ottiene la categoria della mansione basata sul nome
     * 
     * @return string Categoria della mansione
     */
    public function getCategoria()
    {
        $nome = strtolower($this->nome);
        
        if (str_contains($nome, 'comandante') || str_contains($nome, 'command')) {
            return 'Comando';
        }
        
        if (str_contains($nome, 'tecnic') || str_contains($nome, 'technical')) {
            return 'Tecnica';
        }
        
        if (str_contains($nome, 'logistic') || str_contains($nome, 'logistico')) {
            return 'Logistica';
        }
        
        if (str_contains($nome, 'amministrat') || str_contains($nome, 'admin')) {
            return 'Amministrativa';
        }
        
        if (str_contains($nome, 'operativ') || str_contains($nome, 'operational')) {
            return 'Operativa';
        }
        
        if (str_contains($nome, 'sanitari') || str_contains($nome, 'medic')) {
            return 'Sanitaria';
        }
        
        return 'Generale';
    }

    /**
     * Ottiene la classe CSS per la categoria della mansione
     * 
     * @return string Classe CSS appropriata
     */
    public function getCategoriaCssClass()
    {
        $categoria = $this->getCategoria();
        
        return match($categoria) {
            'Comando' => 'badge-danger',
            'Tecnica' => 'badge-warning',
            'Logistica' => 'badge-info',
            'Amministrativa' => 'badge-secondary',
            'Operativa' => 'badge-primary',
            'Sanitaria' => 'badge-success',
            default => 'badge-light'
        };
    }

    /**
     * Ottiene l'icona appropriata per la categoria
     * 
     * @return string Classe icona FontAwesome
     */
    public function getCategoriaIcon()
    {
        $categoria = $this->getCategoria();
        
        return match($categoria) {
            'Comando' => 'fas fa-star',
            'Tecnica' => 'fas fa-cogs',
            'Logistica' => 'fas fa-truck',
            'Amministrativa' => 'fas fa-file-alt',
            'Operativa' => 'fas fa-shield-alt',
            'Sanitaria' => 'fas fa-medkit',
            default => 'fas fa-user'
        };
    }

    /**
     * Verifica se è una mansione di comando
     * 
     * @return bool True se è una mansione di comando
     */
    public function isComando()
    {
        return $this->getCategoria() === 'Comando';
    }

    /**
     * Verifica se è una mansione tecnica
     * 
     * @return bool True se è una mansione tecnica
     */
    public function isTecnica()
    {
        return $this->getCategoria() === 'Tecnica';
    }

    /**
     * Verifica se è una mansione operativa
     * 
     * @return bool True se è una mansione operativa
     */
    public function isOperativa()
    {
        return $this->getCategoria() === 'Operativa';
    }
}
