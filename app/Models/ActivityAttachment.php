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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modello per gli allegati delle attività
 * 
 * Questo modello rappresenta i file allegati alle attività della bacheca.
 * Supporta diversi tipi di allegati (documenti, immagini, link, ecc.)
 * 
 * @package App\Models
 * @version 1.0
 * 
 * @property int $id ID univoco dell'allegato
 * @property int $activity_id ID dell'attività associata
 * @property string $title Titolo dell'allegato
 * @property string $url URL o percorso del file
 * @property string $type Tipo di allegato (document, image, link, ecc.)
 * @property \Illuminate\Support\Carbon|null $created_at Data creazione
 * @property \Illuminate\Support\Carbon|null $updated_at Data ultimo aggiornamento
 * 
 * @property-read \App\Models\BoardActivity $activity Attività associata
 */
class ActivityAttachment extends Model
{
    use HasFactory;

    /**
     * Gli attributi che sono mass assignable
     * 
     * @var array<string>
     */
    protected $fillable = ['activity_id', 'title', 'url', 'type'];

    // ==========================================
    // RELAZIONI
    // ==========================================

    /**
     * Relazione con l'attività associata
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function activity()
    {
        return $this->belongsTo(BoardActivity::class, 'activity_id');
    }

    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================

    /**
     * Verifica se l'allegato è un'immagine
     * 
     * @return bool True se è un'immagine
     */
    public function isImage()
    {
        return $this->type === 'image' || 
               in_array(strtolower(pathinfo($this->url, PATHINFO_EXTENSION)), 
                       ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg']);
    }

    /**
     * Verifica se l'allegato è un documento
     * 
     * @return bool True se è un documento
     */
    public function isDocument()
    {
        return $this->type === 'document' || 
               in_array(strtolower(pathinfo($this->url, PATHINFO_EXTENSION)), 
                       ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);
    }

    /**
     * Verifica se l'allegato è un link esterno
     * 
     * @return bool True se è un link esterno
     */
    public function isLink()
    {
        return $this->type === 'link' || 
               filter_var($this->url, FILTER_VALIDATE_URL);
    }

    /**
     * Ottiene l'estensione del file
     * 
     * @return string Estensione del file
     */
    public function getFileExtension()
    {
        return strtolower(pathinfo($this->url, PATHINFO_EXTENSION));
    }

    /**
     * Ottiene l'icona appropriata per il tipo di allegato
     * 
     * @return string Classe icona FontAwesome
     */
    public function getIcon()
    {
        if ($this->isImage()) {
            return 'fas fa-image';
        }
        
        if ($this->isLink()) {
            return 'fas fa-external-link-alt';
        }
        
        $extension = $this->getFileExtension();
        
        return match($extension) {
            'pdf' => 'fas fa-file-pdf',
            'doc', 'docx' => 'fas fa-file-word',
            'xls', 'xlsx' => 'fas fa-file-excel',
            'ppt', 'pptx' => 'fas fa-file-powerpoint',
            'zip', 'rar', '7z' => 'fas fa-file-archive',
            'txt' => 'fas fa-file-alt',
            default => 'fas fa-file'
        };
    }

    /**
     * Ottiene la classe CSS per il colore del tipo
     * 
     * @return string Classe CSS appropriata
     */
    public function getTypeCssClass()
    {
        if ($this->isImage()) {
            return 'badge-success';
        }
        
        if ($this->isLink()) {
            return 'badge-info';
        }
        
        $extension = $this->getFileExtension();
        
        return match($extension) {
            'pdf' => 'badge-danger',
            'doc', 'docx' => 'badge-primary',
            'xls', 'xlsx' => 'badge-success',
            'ppt', 'pptx' => 'badge-warning',
            default => 'badge-secondary'
        };
    }

    /**
     * Ottiene la dimensione del file se disponibile
     * 
     * @return string|null Dimensione formattata del file
     */
    public function getFileSize()
    {
        if ($this->isLink()) {
            return null;
        }
        
        $filePath = storage_path('app/public/' . $this->url);
        
        if (file_exists($filePath)) {
            $bytes = filesize($filePath);
            $units = ['B', 'KB', 'MB', 'GB'];
            
            for ($i = 0; $bytes > 1024; $i++) {
                $bytes /= 1024;
            }
            
            return round($bytes, 2) . ' ' . $units[$i];
        }
        
        return null;
    }
} 
