<?php

namespace App\Traits;

use Carbon\Carbon;

/**
 * SUGECO: Sistema Unico di Gestione e Controllo
 * 
 * Trait per la gestione comune di certificati e idoneità.
 * Fornisce metodi per verificare validità, scadenze e stati dei certificati.
 * 
 * @package    SUGECO
 * @subpackage Traits
 * @version 1.0
 * @since 1.0
 * @author Michele Di Gennaro
 * @copyright 2025 C2MS
 * 
 * @property \Carbon\Carbon $data_scadenza Data di scadenza del certificato
 * 
 * @method bool isValido() Verifica se il certificato è valido
 * @method bool isInScadenza(int $giorni) Verifica se il certificato è in scadenza
 * @method bool isScaduto() Verifica se il certificato è scaduto
 * @method string getStato() Restituisce lo stato del certificato
 * @method int getGiorniRimanenti() Restituisce i giorni rimanenti alla scadenza
 */
trait CertificatoTrait
{
    // ==========================================
    // METODI DI VALIDAZIONE
    // ==========================================
    
    /**
     * Verifica se il certificato/idoneità è valido (non scaduto)
     * 
     * Un certificato è considerato valido se la sua data di scadenza
     * è uguale o successiva alla data odierna.
     *
     * @return bool True se il certificato è valido, false altrimenti
     * 
     * @example
     * if ($certificato->isValido()) {
     *     echo "Certificato ancora valido";
     * }
     */
    public function isValido()
    {
        if (!$this->data_scadenza) {
            return false;
        }
        
        return Carbon::parse($this->data_scadenza)->gte(now());
    }
    
    /**
     * Verifica se il certificato/idoneità è in scadenza entro X giorni
     * 
     * Un certificato è considerato in scadenza se:
     * - È ancora valido (non scaduto)
     * - Scade entro il numero di giorni specificato
     *
     * @param int $giorni Giorni entro i quali considerare in scadenza (default: 30)
     * @return bool True se il certificato è in scadenza, false altrimenti
     * 
     * @example
     * // Verifica se scade entro 15 giorni
     * if ($certificato->isInScadenza(15)) {
     *     echo "Certificato in scadenza entro 15 giorni";
     * }
     */
    public function isInScadenza($giorni = 30)
    {
        if (!$this->data_scadenza) {
            return false;
        }
        
        $now = now();
        $scadenza = Carbon::parse($this->data_scadenza);
        
        return $scadenza->gt($now) && $scadenza->diffInDays($now) <= $giorni;
    }
    
    /**
     * Verifica se il certificato/idoneità è scaduto
     * 
     * Un certificato è considerato scaduto se la sua data di scadenza
     * è precedente alla data odierna.
     *
     * @return bool True se il certificato è scaduto, false altrimenti
     * 
     * @example
     * if ($certificato->isScaduto()) {
     *     echo "Certificato scaduto";
     * }
     */
    public function isScaduto()
    {
        if (!$this->data_scadenza) {
            return true; // Considera scaduto se non ha data di scadenza
        }
        
        return Carbon::parse($this->data_scadenza)->lt(now());
    }
    
    // ==========================================
    // METODI DI STATO
    // ==========================================
    
    /**
     * Restituisce lo stato del certificato (valido, in scadenza, scaduto)
     * 
     * Determina lo stato attuale del certificato basandosi sulla data di scadenza:
     * - 'scaduto': se la data è passata
     * - 'in_scadenza': se scade entro 30 giorni
     * - 'valido': se è ancora valido e non in scadenza
     *
     * @return string Stato del certificato
     * 
     * @example
     * switch ($certificato->getStato()) {
     *     case 'valido':
     *         echo "Certificato valido";
     *         break;
     *     case 'in_scadenza':
     *         echo "Certificato in scadenza";
     *         break;
     *     case 'scaduto':
     *         echo "Certificato scaduto";
     *         break;
     * }
     */
    public function getStato()
    {
        if ($this->isScaduto()) {
            return $this->getStatoNaming('scaduto');
        }
        
        if ($this->isInScadenza()) {
            return $this->getStatoNaming('in_scadenza');
        }
        
        return $this->getStatoNaming('valido');
    }
    
    /**
     * Restituisce i giorni rimanenti alla scadenza
     * 
     * Calcola il numero di giorni tra oggi e la data di scadenza.
     * Il valore può essere negativo se il certificato è già scaduto.
     *
     * @return int Numero di giorni rimanenti (negativo se scaduto)
     * 
     * @example
     * $giorni = $certificato->getGiorniRimanenti();
     * if ($giorni > 0) {
     *     echo "Scade tra {$giorni} giorni";
     * } elseif ($giorni < 0) {
     *     echo "Scaduto da " . abs($giorni) . " giorni";
     * } else {
     *     echo "Scade oggi";
     * }
     */
    public function getGiorniRimanenti()
    {
        if (!$this->data_scadenza) {
            return -999; // Valore convenzionale per "scaduto da tempo indeterminato"
        }
        
        $now = now();
        $scadenza = Carbon::parse($this->data_scadenza);
        
        return $scadenza->diffInDays($now, false);
    }
    
    // ==========================================
    // METODI DI UTILITÀ
    // ==========================================
    
    /**
     * Restituisce la classe CSS appropriata per lo stato del certificato
     * 
     * @return string Classe CSS per lo styling
     */
    public function getStatoCssClass()
    {
        switch ($this->getStato()) {
            case $this->getStatoNaming('valido'):
                return 'text-success';
            case $this->getStatoNaming('in_scadenza'):
                return 'text-warning';
            case $this->getStatoNaming('scaduto'):
                return 'text-danger';
            default:
                return 'text-muted';
        }
    }
    
    /**
     * Restituisce l'icona appropriata per lo stato del certificato
     * 
     * @return string Classe icona Font Awesome
     */
    public function getStatoIcon()
    {
        switch ($this->getStato()) {
            case $this->getStatoNaming('valido'):
                return 'fas fa-check-circle';
            case $this->getStatoNaming('in_scadenza'):
                return 'fas fa-exclamation-triangle';
            case $this->getStatoNaming('scaduto'):
                return 'fas fa-times-circle';
            default:
                return 'fas fa-question-circle';
        }
    }
    
    /**
     * Formatta la data di scadenza per la visualizzazione
     * 
     * @param string $format Formato della data (default: 'd/m/Y')
     * @return string Data formattata o stringa vuota se non presente
     */
    public function getDataScadenzaFormattata($format = 'd/m/Y')
    {
        if (!$this->data_scadenza) {
            return '';
        }
        
        return Carbon::parse($this->data_scadenza)->format($format);
    }
    
    /**
     * Verifica se il certificato scade entro un numero specifico di giorni
     * 
     * @param int $giorni Numero di giorni da verificare
     * @return bool True se scade entro i giorni specificati
     */
    public function scadeEntro($giorni)
    {
        return $this->isValido() && $this->getGiorniRimanenti() <= $giorni;
    }
    
    // ==========================================
    // METODI ASTRATTI/PERSONALIZZABILI
    // ==========================================
    
    /**
     * Restituisce la nomenclatura specifica per il tipo di certificato
     * 
     * Questo metodo può essere sovrascritto nelle classi che utilizzano il trait
     * per personalizzare i nomi degli stati in base al contesto.
     * 
     * @param string $stato Stato base ('valido', 'in_scadenza', 'scaduto')
     * @return string Nome personalizzato dello stato
     */
    protected function getStatoNaming($stato)
    {
        $naming = [
            'valido' => 'Valido',
            'in_scadenza' => 'In Scadenza',
            'scaduto' => 'Scaduto'
        ];
        
        return $naming[$stato] ?? $stato;
    }
}
