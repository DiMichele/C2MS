/**
 * Calcolo del Codice Fiscale Italiano
 * 
 * @version 1.0
 * @author SUGECO
 */

const CodiceFiscale = {
    // Mese -> lettera
    MESI: ['A', 'B', 'C', 'D', 'E', 'H', 'L', 'M', 'P', 'R', 'S', 'T'],
    
    // Carattere -> valore dispari
    DISPARI: {
        '0': 1, '1': 0, '2': 5, '3': 7, '4': 9, '5': 13, '6': 15, '7': 17, '8': 19, '9': 21,
        'A': 1, 'B': 0, 'C': 5, 'D': 7, 'E': 9, 'F': 13, 'G': 15, 'H': 17, 'I': 19, 'J': 21,
        'K': 2, 'L': 4, 'M': 18, 'N': 20, 'O': 11, 'P': 3, 'Q': 6, 'R': 8, 'S': 12, 'T': 14,
        'U': 16, 'V': 10, 'W': 22, 'X': 25, 'Y': 24, 'Z': 23
    },
    
    // Carattere -> valore pari
    PARI: {
        '0': 0, '1': 1, '2': 2, '3': 3, '4': 4, '5': 5, '6': 6, '7': 7, '8': 8, '9': 9,
        'A': 0, 'B': 1, 'C': 2, 'D': 3, 'E': 4, 'F': 5, 'G': 6, 'H': 7, 'I': 8, 'J': 9,
        'K': 10, 'L': 11, 'M': 12, 'N': 13, 'O': 14, 'P': 15, 'Q': 16, 'R': 17, 'S': 18, 'T': 19,
        'U': 20, 'V': 21, 'W': 22, 'X': 23, 'Y': 24, 'Z': 25
    },
    
    // Valore -> lettera per il carattere di controllo
    RESTO: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    
    /**
     * Estrae le consonanti da una stringa
     */
    estraiConsonanti: function(str) {
        return str.toUpperCase().replace(/[^BCDFGHJKLMNPQRSTVWXYZ]/g, '');
    },
    
    /**
     * Estrae le vocali da una stringa
     */
    estraiVocali: function(str) {
        return str.toUpperCase().replace(/[^AEIOU]/g, '');
    },
    
    /**
     * Calcola i 3 caratteri del cognome
     */
    calcolaCognome: function(cognome) {
        cognome = cognome.toUpperCase().replace(/[^A-Z]/g, '');
        const consonanti = this.estraiConsonanti(cognome);
        const vocali = this.estraiVocali(cognome);
        let codice = (consonanti + vocali + 'XXX').substring(0, 3);
        return codice;
    },
    
    /**
     * Calcola i 3 caratteri del nome
     */
    calcolaNome: function(nome) {
        nome = nome.toUpperCase().replace(/[^A-Z]/g, '');
        const consonanti = this.estraiConsonanti(nome);
        const vocali = this.estraiVocali(nome);
        
        let codice;
        if (consonanti.length >= 4) {
            // Se ci sono 4+ consonanti, prendi 1a, 3a e 4a
            codice = consonanti[0] + consonanti[2] + consonanti[3];
        } else {
            codice = (consonanti + vocali + 'XXX').substring(0, 3);
        }
        return codice;
    },
    
    /**
     * Calcola i 5 caratteri della data di nascita e sesso
     */
    calcolaDataNascita: function(dataNascita, sesso) {
        const data = new Date(dataNascita);
        const anno = data.getFullYear().toString().substring(2, 4);
        const mese = this.MESI[data.getMonth()];
        let giorno = data.getDate();
        
        // Per le femmine, aggiungi 40 al giorno
        if (sesso === 'F') {
            giorno += 40;
        }
        
        const giornoStr = giorno.toString().padStart(2, '0');
        return anno + mese + giornoStr;
    },
    
    /**
     * Calcola il carattere di controllo
     */
    calcolaCarattereControllo: function(codice15) {
        let somma = 0;
        for (let i = 0; i < 15; i++) {
            const char = codice15[i];
            if (i % 2 === 0) {
                // Posizione dispari (1, 3, 5...)
                somma += this.DISPARI[char];
            } else {
                // Posizione pari (2, 4, 6...)
                somma += this.PARI[char];
            }
        }
        return this.RESTO[somma % 26];
    },
    
    /**
     * Calcola il codice fiscale completo
     * @param {string} cognome
     * @param {string} nome
     * @param {string} dataNascita - formato YYYY-MM-DD
     * @param {string} sesso - 'M' o 'F'
     * @param {string} codiceComune - codice catastale del comune (es: H501 per Roma)
     * @returns {string} Codice fiscale di 16 caratteri
     */
    calcola: function(cognome, nome, dataNascita, sesso, codiceComune) {
        if (!cognome || !nome || !dataNascita || !sesso || !codiceComune) {
            return '';
        }
        
        const codiceCognome = this.calcolaCognome(cognome);
        const codiceNome = this.calcolaNome(nome);
        const codiceData = this.calcolaDataNascita(dataNascita, sesso);
        const codice15 = codiceCognome + codiceNome + codiceData + codiceComune.toUpperCase();
        
        if (codice15.length !== 15) {
            return '';
        }
        
        const carattereControllo = this.calcolaCarattereControllo(codice15);
        return codice15 + carattereControllo;
    }
};

/**
 * Inizializza il calcolo automatico del codice fiscale
 * Cerca automaticamente il codice catastale dal nome del comune
 */
function initCalcoloCodiceFiscale() {
    const cognomeInput = document.getElementById('cognome');
    const nomeInput = document.getElementById('nome');
    const dataNascitaInput = document.getElementById('data_nascita');
    const sessoSelect = document.getElementById('sesso');
    const luogoNascitaInput = document.getElementById('luogo_nascita');
    const provinciaNascitaInput = document.getElementById('provincia_nascita');
    const codiceFiscaleInput = document.getElementById('codice_fiscale');
    const cfStatusIcon = document.getElementById('cf-status');
    
    if (!cognomeInput || !nomeInput || !dataNascitaInput || !sessoSelect || !luogoNascitaInput || !codiceFiscaleInput) {
        return;
    }
    
    function aggiornaCF() {
        const cognome = cognomeInput.value.trim();
        const nome = nomeInput.value.trim();
        const dataNascita = dataNascitaInput.value;
        const sesso = sessoSelect.value;
        const luogoNascita = luogoNascitaInput.value.trim();
        const provincia = provinciaNascitaInput ? provinciaNascitaInput.value.trim() : '';
        
        // Se mancano dati essenziali, non calcolare
        if (!cognome || !nome || !dataNascita || !sesso || !luogoNascita) {
            if (cfStatusIcon) {
                cfStatusIcon.className = 'input-group-text bg-secondary text-white';
                cfStatusIcon.title = 'Completa tutti i campi per calcolare il CF';
            }
            return;
        }
        
        // Cerca il codice catastale del comune
        let codiceComune = null;
        if (typeof cercaCodiceComune === 'function') {
            codiceComune = cercaCodiceComune(luogoNascita, provincia);
        }
        
        if (!codiceComune) {
            // Comune non trovato
            if (cfStatusIcon) {
                cfStatusIcon.className = 'input-group-text bg-warning text-dark';
                cfStatusIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                cfStatusIcon.title = 'Comune "' + luogoNascita + '" non trovato nel database. Inserisci il CF manualmente.';
            }
            return;
        }
        
        // Calcola il codice fiscale
        const cf = CodiceFiscale.calcola(cognome, nome, dataNascita, sesso, codiceComune);
        
        if (cf) {
            codiceFiscaleInput.value = cf;
            codiceFiscaleInput.classList.add('calculated');
            if (cfStatusIcon) {
                cfStatusIcon.className = 'input-group-text bg-success text-white';
                cfStatusIcon.innerHTML = '<i class="fas fa-check"></i>';
                cfStatusIcon.title = 'Codice Fiscale calcolato automaticamente';
            }
        }
    }
    
    // Aggiungi listener a tutti i campi rilevanti
    const campiDaMonitorare = [cognomeInput, nomeInput, dataNascitaInput, sessoSelect, luogoNascitaInput];
    if (provinciaNascitaInput) {
        campiDaMonitorare.push(provinciaNascitaInput);
    }
    
    campiDaMonitorare.forEach(input => {
        if (input) {
            input.addEventListener('input', aggiornaCF);
            input.addEventListener('change', aggiornaCF);
        }
    });
    
    // Calcola immediatamente se tutti i campi sono già compilati
    aggiornaCF();
}

// Inizializza quando il DOM è pronto
document.addEventListener('DOMContentLoaded', initCalcoloCodiceFiscale);

