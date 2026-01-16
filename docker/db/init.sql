-- Schema e dati iniziali per pannello_admin
CREATE DATABASE IF NOT EXISTS pannello CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pannello;

SET NAMES utf8mb4;

DROP TABLE IF EXISTS multimedia;
DROP TABLE IF EXISTS orario;
DROP TABLE IF EXISTS tariffa;
DROP TABLE IF EXISTS accessibilita;
DROP TABLE IF EXISTS evento;
DROP TABLE IF EXISTS ente;
DROP TABLE IF EXISTS sede;

CREATE TABLE sede (
    id INT AUTO_INCREMENT PRIMARY KEY,
    via VARCHAR(255) NOT NULL,
    cap VARCHAR(10) NOT NULL,
    paese VARCHAR(120) NOT NULL
);

CREATE TABLE ente (
    nome VARCHAR(150) NOT NULL,
    id_indirizzo INT NOT NULL UNIQUE,
    PRIMARY KEY (nome),
    CONSTRAINT fk_ente_sede FOREIGN KEY (id_indirizzo) REFERENCES sede(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE evento (
    id CHAR(36) NOT NULL,
    nome VARCHAR(200) NOT NULL,
    descrizione TEXT NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    data_inizio DATETIME NOT NULL,
    data_fine DATETIME NOT NULL,
    organizzatore VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_evento_ente FOREIGN KEY (organizzatore) REFERENCES ente(nome) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_date_range CHECK (data_inizio <= data_fine)
);

CREATE TABLE accessibilita (
    id CHAR(36) NOT NULL,
    rampe BOOLEAN NOT NULL DEFAULT FALSE,
    ascensori BOOLEAN NOT NULL DEFAULT FALSE,
    posti_disabili INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    CONSTRAINT fk_accessibilita_evento FOREIGN KEY (id) REFERENCES evento(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_posti_disabili CHECK (posti_disabili >= 0)
);

CREATE TABLE tariffa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id CHAR(36) NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    prezzo DECIMAL(10,2) NOT NULL,
    valuta VARCHAR(10) NOT NULL,
    CONSTRAINT fk_tariffa_evento FOREIGN KEY (evento_id) REFERENCES evento(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_tariffa_prezzo CHECK (prezzo >= 0)
);

CREATE TABLE orario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id CHAR(36) NOT NULL,
    giorno DATE NOT NULL,
    apertura TIME NOT NULL,
    chiusura TIME NOT NULL,
    CONSTRAINT fk_orario_evento FOREIGN KEY (evento_id) REFERENCES evento(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_orario_range CHECK (apertura < chiusura)
);

CREATE TABLE multimedia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id CHAR(36) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    url VARCHAR(255) NOT NULL,
    descrizione TEXT,
    CONSTRAINT fk_multimedia_evento FOREIGN KEY (evento_id) REFERENCES evento(id) ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO sede (id, via, cap, paese) VALUES
    (1, 'Via Masaccio 4', '20063', 'Italia'),
    (2, 'Piazza Centrale 1', '20100', 'Italia');

INSERT INTO ente (nome, id_indirizzo) VALUES
    ('ITSOS Marie Curie', 1),
    ('Auditorium Comunale', 2);

INSERT INTO evento (id, nome, descrizione, categoria, data_inizio, data_fine, organizzatore) VALUES
    ('EVT-2026-0001', 'La notte bianca della lettura', 'Tradizionale notte bianca con letture pubbliche, workshop e musica dal vivo.', 'Arte e Cultura', '2026-07-19 18:00:00', '2026-07-20 02:00:00', 'ITSOS Marie Curie'),
    ('EVT-2026-0002', 'Forum delle Politiche Giovanili', 'Giornata di confronto su politiche giovanili, startup sociali e volontariato.', 'Formazione', '2026-09-12 09:00:00', '2026-09-12 17:00:00', 'Auditorium Comunale');

INSERT INTO accessibilita (id, rampe, ascensori, posti_disabili) VALUES
    ('EVT-2026-0001', TRUE, TRUE, 6),
    ('EVT-2026-0002', TRUE, FALSE, 4);

INSERT INTO tariffa (evento_id, tipo, prezzo, valuta) VALUES
    ('EVT-2026-0001', 'Ingresso intero', 10.00, 'EUR'),
    ('EVT-2026-0001', 'Ridotto studenti', 5.00, 'EUR'),
    ('EVT-2026-0002', 'Partecipazione', 0.00, 'EUR');

INSERT INTO orario (evento_id, giorno, apertura, chiusura) VALUES
    ('EVT-2026-0001', '2026-07-19', '18:00:00', '23:59:00'),
    ('EVT-2026-0001', '2026-07-20', '00:00:00', '02:00:00'),
    ('EVT-2026-0002', '2026-09-12', '09:00:00', '17:00:00');

INSERT INTO multimedia (evento_id, tipo, url, descrizione) VALUES
    ('EVT-2026-0001', 'immagine', 'https://www.example.com/image.png', 'Locandina ufficiale'),
    ('EVT-2026-0001', 'video', 'https://www.example.com/video.mp4', 'Trailer dell evento'),
    ('EVT-2026-0002', 'immagine', 'https://www.example.com/forum.png', 'Banner promozionale');
