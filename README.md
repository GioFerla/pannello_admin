# Pannello Amministrazione Eventi (Docker)

Applicazione full-stack PHP + Apache con frontend Tailwind CSS e database MariaDB/MySQL orchestrata via Docker Compose. Include login, CRUD completo e gestione relazioni secondo il modello ER fornito.

## Architettura
- **web**: PHP 8.2 + Apache, PDO MySQL, Tailwind via CDN.
- **db**: MariaDB 11 con volume persistente e bootstrap SQL (`docker/db/init.sql`).
- **Frontend**: form responsive con repeater per tariffe, orari e media; toast e modale conferma.
- **Backend**: PDO, transazioni, validazione server-side; CRUD su EVENTO, ENTE, SEDE, TARIFFA, ORARIO, MULTIMEDIA, ACCESSIBILITA.

## Database
Schema in `docker/db/init.sql` con chiavi primarie/esterne e `ON DELETE CASCADE` dove sensato. Tabelle: EVENTO, ENTE, SEDE, ACCESSIBILITA (1:1), TARIFFA/ORARIO/MULTIMEDIA (1:N). Dati esempio inclusi.

## Avvio rapido
```bash
docker-compose up --build
```
- App: http://localhost:8080
- DB: localhost:3306 (user/password: pannello/pannello, db: pannello)

Credenziali admin (configurabili via env): `admin` / `admin123`.

## Struttura cartelle
- index.php — login
- admin/ — dashboard, create, edit, delete
- includes/ — sessione, config env-based, PDO, validazione/CRUD, layout
- assets/js/app.js — menu mobile, toast, modale, repeater, validazione client
- docker/ — `db/init.sql` per schema+dati
- docker-compose.yml, Dockerfile

## Workflow
1. Login.
2. Dashboard con conteggi tariffe/orari/media, edit/delete.
3. Creazione/modifica con sezioni: dettagli evento, ente+sede, accessibilità, tariffe, orari, media.
4. Eliminazione con conferma modale.

## Variabili d'ambiente principali
Configurate in `docker-compose.yml` per il servizio `web`:
- `APP_ADMIN_USER`, `APP_ADMIN_PASS`
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`

## Documentazione dettagliata: event-edit.php

### Scopo
`admin/event-edit.php` è la pagina per la **modifica di eventi esistenti**. Carica tutti i dati dell'evento (incluse le relazioni 1:N con tariffe, orari e multimedia) e permette all'utente autenticato di aggiornare qualsiasi campo, gestendo transazioni database e validazione sia client che server-side.

### Flusso di esecuzione

#### 1. Caricamento evento
- Richiede parametro GET `?id=<evento_id>` (es. `EVT-2026-0001`).
- Chiama `fetch_event($id)` da `includes/data.php` che esegue query JOIN per recuperare:
  - Dati evento (EVENTO, ENTE, SEDE denormalizzati per semplicità)
  - Accessibilità (ACCESSIBILITA, relazione 1:1)
  - Tariffe (TARIFFA, relazione 1:N)
  - Orari (ORARIO, relazione 1:N)
  - Multimedia (MULTIMEDIA, relazione 1:N)
- Se l'evento non esiste, aggiunge messaggio flash di errore e reindirizza alla dashboard.

#### 2. Preparazione dati per il form
- Popola array `$input` con i valori esistenti dell'evento.
- Utilizza `array_column()` per estrarre i campi dalle relazioni 1:N (es. `tariffe_tipo`, `tariffe_prezzo`).
- Converte `data_inizio` e `data_fine` in formato `datetime-local` (`Y-m-d\TH:i`) tramite `to_datetime_local()` per compatibilità con input HTML5.

#### 3. Gestione POST (invio form)
- Merge dei dati esistenti con i nuovi valori `$_POST`.
- Chiama `update_event_record($id, $_POST)` da `includes/data.php`:
  - Valida payload con `validate_event_payload()` (lunghezza campi, date, numeri, obbligatorietà).
  - Avvia transazione PDO (`beginTransaction()`).
  - Aggiorna record EVENTO, ENTE, SEDE, ACCESSIBILITA.
  - **Elimina e ricrea** record correlati (TARIFFA, ORARIO, MULTIMEDIA) per semplicità.
  - Commit transazione o rollback in caso di errore.
- In caso di successo: flash message e redirect a dashboard.
- In caso di errore: mostra errori di validazione nel form.

### Struttura del form

Il form è organizzato in **sezioni semantiche**:

#### Sezione 1: Dettagli evento
- **Nome evento** (`name`, text, required): minimo 3 caratteri.
- **Categoria** (`category`, select, required): `Arte e Cultura`, `Formazione`, `Sport`, `Altro`.
- **Descrizione** (`description`, textarea, required): minimo 10 caratteri.
- **Data/ora inizio** (`startDateTime`, datetime-local, required): formato ISO 8601.
- **Data/ora fine** (`endDateTime`, datetime-local, required): deve essere successiva all'inizio.

#### Sezione 2: Ente e Sede
- **Organizzatore** (`organizzatore`, text, required): nome dell'ente (ENTE.nome).
- **Indirizzo sede** (`sede_via`, text, required): via della sede (SEDE.via).
- **CAP** (`sede_cap`, text, required): codice postale (SEDE.cap).
- **Paese** (`sede_paese`, text, required): nazione (SEDE.paese).

#### Sezione 3: Accessibilità (relazione 1:1)
- **Rampe** (`rampe`, checkbox): presenza rampe per disabili.
- **Ascensori** (`ascensori`, checkbox): presenza ascensori.
- **Posti riservati** (`posti_disabili`, number, min=0): numero posti per disabili.

#### Sezione 4: Tariffe (relazione 1:N) - Repeater
- Campi array: `tariffe_tipo[]`, `tariffe_prezzo[]`, `tariffe_valuta[]`.
- Ogni riga contiene: **Tipo** (es. "Intero", "Ridotto"), **Prezzo** (decimal, min=0), **Valuta** (es. "EUR").
- Pulsanti JavaScript: `+ Riga` (aggiunge), `×` (rimuove riga).
- Validazione: tipo, prezzo e valuta obbligatori se riga non vuota.

#### Sezione 5: Orari (relazione 1:N) - Repeater
- Campi array: `orari_giorno[]`, `orari_apertura[]`, `orari_chiusura[]`.
- Ogni riga: **Giorno** (date), **Apertura** (time), **Chiusura** (time).
- Vincolo: chiusura > apertura.

#### Sezione 6: Contenuti multimediali (relazione 1:N) - Repeater
- Campi array: `media_tipo[]`, `media_url[]`, `media_descrizione[]`.
- Ogni riga: **Tipo** (es. "immagine", "video"), **URL** (url), **Descrizione** (text, opzionale).

### Funzioni helper

#### `old_edit(array $data, string $key, string $fallback = '')`
- Recupera valore da array `$input` con fallback.
- Applica `htmlspecialchars()` per prevenire XSS.
- Usata per campi singoli (text, textarea, select).

#### `old_array(array $data, string $key, int $index, string $fallback = '')`
- Come `old_edit()` ma per campi array (repeater).
- Gestisce indice specifico (es. `tariffe_tipo[2]`).

#### `to_datetime_local(?string $value)`
- Converte timestamp MySQL (`Y-m-d H:i:s`) in formato `datetime-local` (`Y-m-d\TH:i`).
- Gestisce valori null o formato invalido ritornando stringa vuota.
- Usa `DateTime::format()` per la conversione.

### Validazione e sicurezza

#### Server-side (includes/data.php)
- `validate_event_payload()`: controlla lunghezza minima, obbligatorietà, tipi numerici, coerenza date.
- Prepared statements PDO per prevenire SQL injection.
- Transazioni per garantire atomicità delle operazioni.
- `htmlspecialchars()` su tutti gli output per prevenire XSS.

#### Client-side (assets/js/app.js)
- Attributi HTML5: `required`, `type="url"`, `type="datetime-local"`, `min`, `step`.
- Validazione JavaScript custom per relazioni 1:N (campo `data-event-form`).
- Messaggi errore dinamici in `data-client-errors`.

### Funzionalità UI/UX

#### Repeater dinamici
- Gestiti da JavaScript (`data-repeater`, `data-add-row`, `data-remove-row`).
- Clonano template HTML per aggiungere nuove righe.
- Permettono rimozione senza reload pagina.
- Garantiscono almeno 1 riga per sezione (inizializzata da PHP con `max(1, count(...))`).

#### Toast e feedback
- `add_flash()` (session-based) per messaggi successo/errore post-redirect.
- Sistema di feedback consistente con altre pagine admin (modale conferma per eliminazione in `event-delete.php`).

#### Responsive design
- Tailwind CSS con grid responsive (`grid-cols-1 md:grid-cols-2`).
- Form ottimizzato per mobile e desktop.

### Differenze con event-new.php
- `event-edit.php`: carica dati esistenti, esegue UPDATE, elimina/ricrea relazioni 1:N.
- `event-new.php`: crea da zero con INSERT, genera nuovo ID evento (`generate_event_id()`).
- Entrambi condividono validazione e logica di salvataggio da `includes/data.php`.

### Dipendenze
- `includes/session.php`: autenticazione utente (`require_login()`).
- `includes/data.php`: logica CRUD (`fetch_event()`, `update_event_record()`).
- `includes/layout.php`: header/footer admin (`render_admin_shell_start/end()`).
- `assets/js/app.js`: repeater, validazione client, toast.

### Esempio URL
```
http://localhost:8080/admin/event-edit.php?id=EVT-2026-0001
```

## Note
- Per modificare lo schema iniziale, aggiorna `docker/db/init.sql` e ricrea i container.
- I dati persistono nel volume `db_data`.
- Tailwind è fornito da CDN per semplicità.
