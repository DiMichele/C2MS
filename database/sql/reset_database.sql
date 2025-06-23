-- Disabilita i controlli delle chiavi esterne
SET FOREIGN_KEY_CHECKS=0;

-- Elimina le tabelle esistenti
DROP TABLE IF EXISTS certificati_lavoratori;
DROP TABLE IF EXISTS idoneita;
DROP TABLE IF EXISTS presenze;
DROP TABLE IF EXISTS militare;
DROP TABLE IF EXISTS compagnie;
DROP TABLE IF EXISTS plotoni;
DROP TABLE IF EXISTS poli;
DROP TABLE IF EXISTS gradi;
DROP TABLE IF EXISTS ruoli;
DROP TABLE IF EXISTS mansioni;

-- Ricrea le tabelle nell'ordine corretto

-- Compagnie
CREATE TABLE compagnie (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    descrizione VARCHAR(255),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserisci la compagnia di default
INSERT INTO compagnie (nome, descrizione) VALUES ('Compagnia Default', 'Compagnia creata automaticamente');

-- Gradi
CREATE TABLE gradi (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    ordine INT NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ruoli
CREATE TABLE ruoli (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mansioni
CREATE TABLE mansioni (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plotoni
CREATE TABLE plotoni (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    compagnia_id BIGINT UNSIGNED,
    PRIMARY KEY (id),
    FOREIGN KEY (compagnia_id) REFERENCES compagnie(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Poli
CREATE TABLE poli (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    compagnia_id BIGINT UNSIGNED,
    PRIMARY KEY (id),
    FOREIGN KEY (compagnia_id) REFERENCES compagnie(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Militare
CREATE TABLE militare (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    cognome VARCHAR(255) NOT NULL,
    grado_id BIGINT UNSIGNED,
    plotone_id BIGINT UNSIGNED,
    polo_id BIGINT UNSIGNED,
    ruolo_id BIGINT UNSIGNED,
    mansione_id BIGINT UNSIGNED,
    compagnia_id BIGINT UNSIGNED,
    certificati_note TEXT,
    idoneita_note TEXT,
    PRIMARY KEY (id),
    FOREIGN KEY (grado_id) REFERENCES gradi(id) ON DELETE SET NULL,
    FOREIGN KEY (plotone_id) REFERENCES plotoni(id) ON DELETE SET NULL,
    FOREIGN KEY (polo_id) REFERENCES poli(id) ON DELETE SET NULL,
    FOREIGN KEY (ruolo_id) REFERENCES ruoli(id) ON DELETE SET NULL,
    FOREIGN KEY (mansione_id) REFERENCES mansioni(id) ON DELETE SET NULL,
    FOREIGN KEY (compagnia_id) REFERENCES compagnie(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Presenze
CREATE TABLE presenze (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    militare_id BIGINT UNSIGNED NOT NULL,
    data DATE NOT NULL,
    stato ENUM('Presente', 'Assente') NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (militare_id) REFERENCES militare(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Certificati Lavoratori
CREATE TABLE certificati_lavoratori (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    militare_id BIGINT UNSIGNED NOT NULL,
    tipo VARCHAR(255) NOT NULL,
    data_ottenimento DATE,
    data_scadenza DATE,
    file_path VARCHAR(255),
    PRIMARY KEY (id),
    FOREIGN KEY (militare_id) REFERENCES militare(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Idoneità
CREATE TABLE idoneita (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    militare_id BIGINT UNSIGNED NOT NULL,
    tipo VARCHAR(255) NOT NULL,
    data_ottenimento DATE,
    data_scadenza DATE,
    file_path VARCHAR(255),
    PRIMARY KEY (id),
    FOREIGN KEY (militare_id) REFERENCES militare(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Riabilita i controlli delle chiavi esterne
SET FOREIGN_KEY_CHECKS=1; 