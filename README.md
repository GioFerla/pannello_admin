# Pannello Amministrazione Eventi (Docker)

Applicazione full‑stack PHP + Apache con frontend Tailwind CSS e database MariaDB/MySQL orchestrata via Docker Compose. Include login, CRUD completo e gestione relazioni secondo il modello ER fornit[...]

## Indice
- [Architettura](#architettura)
- [Database](#database)
- [Avvio rapido](#avvio-rapido)
- [Struttura cartelle](#struttura-cartelle)
- [Documentazione JavaScript (app.js)](#documentazione-javascript-assetsjsappjs)
- [Dettagli funzioni CRUD (includes/data.php)](#dettagli-funzioni-crud-includesdataphp)
- [Workflow](#workflow)
- [Variabili d'ambiente principali](#variabili-dambiente-principali)
- [Note](#note)
- [Panoramica — Eliminazione Eventi](#panoramica---eliminazione-eventi)
- [Panoramica — Modifica Evento](#panoramica---modifica-evento)
- [Panoramica — Lista eventi / Dashboard](#panoramica---lista-eventi--dashboard)
- [Panoramica del Form di Creazione Eventi](#panoramica-del-form-di-creazione-eventi)
  - [Dipendenze](#dipendenze)
  - [Flusso di funzionamento](#flusso-di-funzionamento)
  - [Helper PHP](#helper-php)
  - [Struttura del form](#struttura-del-form)
  - [Sezioni ripetibili (repeater)](#sezioni-ripetibili-repeater)
  - [Gestione errori](#gestione-errori)
  - [Considerazioni di sicurezza](#considerazioni-di-sicurezza)
  - [Attributi HTML5 di validazione](#attributi-html5-di-validazione)
  - [Styling](#styling)
  - [Manutenzione futura](#manutenzione-futura)

## Architettura
- **web**: PHP 8.2 + Apache, PDO MySQL. Tailwind via CDN per semplicità.
- **db**: MariaDB 11 con volume persistente e bootstrap SQL (`docker/db/init.sql`).
- **Frontend**: form responsive con repeater per tariffe, orari e media; toast e modale di conferma.
- **Backend**: PDO, transazioni, validazione server-side; CRUD su EVENTO, ENTE, SEDE, TARIFFA, ORARIO, MULTIMEDIA, ACCESSIBILITA.

## Database
Schema in `docker/db/init.sql` con chiavi primarie/esterne e `ON DELETE CASCADE` dove sensato. Tabelle principali:
- EVENTO
- ENTE
- SEDE
- ACCESSIBILITA (1:1)
- TARIFFA (1:N)
- ORARIO (1:N)
- MULTIMEDIA (1:N)

Dati di esempio inclusi nel bootstrap SQL.

## Avvio rapido
Esegui dalla root del progetto:

```bash
docker-compose up --build
```

Dopo l'avvio:
- App: http://localhost:8080
- DB: localhost:3306  
  (user/password: `pannello`/`pannello`, db: `pannello`)

Credenziali admin (configurabili via env): `admin` / `admin123`

## Struttura cartelle
- `index.php` — login
- `admin/` — dashboard, create, edit, delete
- `includes/` — sessione, config env-based, PDO, validazione/CRUD, layout
- `assets/js/app.js` — menu mobile, toast, modale, repeater, validazione client
- `docker/` — `db/init.sql` per schema + dati
- `docker-compose.yml`, `Dockerfile`

## Documentazione JavaScript (assets/js/app.js)

Il file `assets/js/app.js` gestisce l'interazione lato client del pannello admin, in particolare:
- **Repeater** (sezioni ripetibili) per Tariffe, Orari e Multimedia
- **Toast** (messaggi di feedback) e gestione UI correlata
- **Modale di conferma eliminazione** (apertura/chiusura + popolamento campi)
- (eventuali) comportamenti UI come menu mobile o utility

### Repeater (sezioni ripetibili)

I repeater servono a permettere l'inserimento di liste di valori che il backend riceve come array tramite input con suffisso `[]`, ad esempio:
- `tariffe_tipo[]`, `tariffe_prezzo[]`, `tariffe_valuta[]`
- `orari_giorno[]`, `orari_apertura[]`, `orari_chiusura[]`
- `media_tipo[]`, `media_url[]`, `media_descrizione[]`

#### Logica di base (come funziona)

Il comportamento tipico è:
1. Esiste un **template** (una “riga” o “blocco” HTML) da duplicare.
2. Quando l'utente clicca “Aggiungi”:
   - il JS **clona** il template,
   - **pulisce i valori** (input vuoti, checkbox deselezionate, ecc.),
   - e **appende** la nuova riga al container del repeater.
3. Quando l'utente clicca “Rimuovi” su una riga:
   - il JS rimuove il nodo dal DOM.

> Importante: se il repeater JS si rompe, il backend potrebbe **non ricevere gli array corretti** (campi mancanti, righe non aggiunte/rimosse), causando errori di validazione o dati incompleti.

#### Indici e name degli input

La strategia più robusta è mantenere `name="campo[]"` (senza indici espliciti):
- PHP ricostruisce automaticamente gli array in base all'ordine dei campi inviati.
- Non è necessario “rinumerare” gli indici a ogni modifica, perché l'array risultante sarà comunque coerente in POST.

Se invece venissero usati name indicizzati (es. `campo[0]`, `campo[1]`), allora il JS dovrebbe anche:
- aggiornare gli indici dopo ogni add/remove,
- per evitare “buchi” o associazioni errate.

### Modale di eliminazione (Delete Modal)

La dashboard mostra un pulsante “Elimina” (tipicamente con attributi `data-*` come `data-id` e `data-name`).
Il JS:
1. intercetta il click sul pulsante elimina,
2. apre la modale,
3. inserisce il nome evento nell'elemento dedicato (es. `#delete-event-name`),
4. valorizza l'input hidden con l'ID (es. `#delete-event-id`),
5. al submit del form, viene inviata una POST allo script di delete.

### Toast / Messaggi UI

I toast (o banner) servono a mostrare feedback non bloccanti (success/error/info).
In genere il backend imposta messaggi flash e il frontend li rende; il JS può occuparsi di:
- auto-hide dopo N secondi,
- chiusura manuale,
- transizioni CSS.

## Dettagli funzioni CRUD (includes/data.php)

Il file `includes/data.php` contiene le funzioni di accesso ai dati (PDO) e le operazioni CRUD principali sugli eventi e sulle tabelle correlate (tariffe, orari, multimedia, accessibilità, ecc.).

### Tabella rapida: firme e valori di ritorno

| Funzione | Scopo | Argomenti | Ritorno |
|---------|------|-----------|---------|
| `list_events()` | Elenca gli eventi per la dashboard (con conteggi correlati) | — | `array $events` |
| `fetch_event(string $id)` | Recupera un evento + record correlati (accessibilità, tariffe, orari, media) | `$id` | `array|null` (bundle dati o `null` se non trovato) |
| `create_event(array $data)` | Crea evento e tabelle collegate | `$data` (tipicamente `$_POST`) | `array [$errors, $newId]` dove: `array $errors`, `int|string|null $newId` |
| `update_event_record(string $id, array $data)` | Aggiorna evento e tabelle collegate | `$id`, `$data` | `array [$errors, $updatedId]` dove: `array $errors`, `int|string|null $updatedId` |
| `delete_event_record(string $id)` | Elimina evento (e cascata su tabelle correlate se configurato) | `$id` | `bool` (`true` se eliminato, `false` se non trovato/errore) |

> Nota: i tipi esatti di `$newId`/`$updatedId` dipendono dal tipo della PK nel DB e dall'implementazione PDO (potrebbe essere `int` o `string`). Se l'ID è un `AUTO_INCREMENT`, tipicamente è un `int`.

### Convenzioni di validazione (attese)

In generale, `create_event()` e `update_event_record()`:
- validano i campi obbligatori (nome, date, ecc.)
- validano coerenza dei repeater (array con lunghezze compatibili, valori numerici per prezzi, URL validi per media, ecc.)
- eseguono query con prepared statements (PDO) per evitare SQL injection
- usano transazioni se devono aggiornare più tabelle in modo atomico (consigliato)

Se ci sono errori:
- `$errors` contiene messaggi user-friendly
- la pagina PHP ripopola il form con i valori inviati e mostra gli errori in alto

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

## Panoramica — Eliminazione Eventi

Questo script PHP gestisce l'eliminazione di eventi dal sistema amministrativo. È progettato per essere richiamato esclusivamente tramite richieste POST dal pannello di amministrazione.

### Posizione file
`/admin/delete_event.php` (o percorso equivalente nella directory `admin`)

### Dipendenze

File richiesti:
- `../includes/session.php` — gestione delle sessioni utente
- `../includes/data.php` — funzioni di accesso ai dati (include `delete_event_record()`)

Funzioni esterne utilizzate:
- `require_login()` — verifica che l'utente sia autenticato
- `delete_event_record($id)` — elimina un evento dal database
- `add_flash($type, $message)` — aggiunge messaggi flash per l'utente

### Flusso di esecuzione

1. Controllo autenticazione

```php
require_login();
```

2. Validazione metodo HTTP

```php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/dashboard.php');
    exit;
}
```

3. Recupero e validazione ID

```php
$id = isset($_POST['id']) ? (string) $_POST['id'] : '';
if ($id === '') {
    add_flash('error', 'ID evento mancante.');
    header('Location: /admin/dashboard.php');
    exit;
}
```

4. Eliminazione evento

```php
if (delete_event_record($id)) {
    add_flash('success', 'Evento eliminato.');
} else {
    add_flash('error', 'Evento non trovato.');
}
```

5. Reindirizzamento finale

```php
header('Location: /admin/dashboard.php');
exit;
```

### Parametri POST richiesti

| Parametro | Tipo   | Obbligatorio | Descrizione                              |
|-----------|--------|--------------|------------------------------------------|
| id        | string | Sì           | Identificativo univoco dell'evento da eliminare |

### Messaggi flash

- Successo: `Evento eliminato.`
- Errori: `ID evento mancante.`, `Evento non trovato.`

### Esempio di utilizzo

```html
<form method="POST" action="/admin/delete_event.php" onsubmit="return confirm('Sei sicuro di voler eliminare questo evento?');">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($event_id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
    <button type="submit" class="btn-danger">Elimina Evento</button>
</form>
```

### Sicurezza

- Autenticazione obbligatoria: `require_login()` impedisce accessi non autorizzati.
- Metodo POST obbligatorio: previene eliminazioni accidentali via link GET.
- Validazione input: controlla che l'ID sia presente prima di procedere.
- Type casting: converte l'ID in stringa per coerenza.
- Consigli aggiuntivi: usare controlli di autorizzazione (ruoli/permessi) se presenti, loggare operazioni sensibili e usare transazioni in `delete_event_record()` se la cancellazione coinvolge più ta[...]

## Panoramica — Modifica Evento

Questa sezione descrive lo script che mostra e processa il form di modifica di un evento. Lo script combina caricamento dati esistenti, visualizzazione form con sezioni ripetibili e salvataggio delle [...]

### Posizione file
`/admin/edit_event.php` (o percorso equivalente nella directory `admin`)

### Dipendenze

File richiesti:
- `../includes/session.php` — gestione delle sessioni utente
- `../includes/data.php` — funzioni di accesso ai dati (include `fetch_event()` e `update_event_record()`)
- `../includes/layout.php` — funzioni di rendering (`render_admin_shell_start()`, `render_admin_shell_end()`)

Funzioni esterne utilizzate:
- `require_login()` — verifica che l'utente sia autenticato
- `fetch_event($id)` — recupera l'evento e i record correlati (accessibilità, tariffe, orari, multimedia)
- `update_event_record($id, $data)` — aggiorna l'evento con i dati inviati
- `add_flash($type, $message)` — aggiunge messaggi flash
- `render_admin_shell_start($title, $active)` / `render_admin_shell_end()` — wrapper layout

Helper locali definiti nel file:
- `old_edit(array $data, string $key, string $fallback = ''): string` — ripopolamento sicuro dei campi singoli (usa htmlspecialchars)
- `old_array(array $data, string $key, int $index, string $fallback = ''): string` — ripopolamento per campi array
- `to_datetime_local(?string $value): string` — converte una stringa datetime in formato `Y-m-d\TH:i` per `input[type="datetime-local"]`

### Flusso di esecuzione

1. Autenticazione

```php
require_login();
```

2. Recupero ID evento (GET)

```php
$id = isset($_GET['id']) ? (string) $_GET['id'] : '';
$eventBundle = $id ? fetch_event($id) : null;
if (!$eventBundle) {
    add_flash('error', 'Evento non trovato.');
    header('Location: /admin/dashboard.php');
    exit;
}
```

3. Preparazione dati per il form

- Estrazione dei sotto-array: evento, accessibilità, tariffe, orari, multimedia.
- Costruzione di `$input` con i valori correnti, incluse le colonne dei sotto-record tramite `array_column()` per popolare i repeater:
  - tariffe_tipo[], tariffe_prezzo[], tariffe_valuta[]
  - orari_giorno[], orari_apertura[], orari_chiusura[]
  - media_tipo[], media_url[], media_descrizione[]
- `startDateTime` e `endDateTime` sono impostati via `to_datetime_local()` sui valori originali (`data_inizio`, `data_fine`) per alimentare i campi `datetime-local`.

4. Gestione POST (salvataggio)

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['startDateTime'] = to_datetime_local($event['data_inizio']);
    $input['endDateTime'] = to_datetime_local($event['data_fine']);

    $input = array_merge($input, $_POST);
    [$errors, $updatedId] = update_event_record($id, $_POST);
    if (empty($errors)) {
        add_flash('success', 'Evento aggiornato.');
        header('Location: /admin/dashboard.php');
        exit;
    }
}
```

- I valori POST vengono fusi in `$input` per ripopolare il form in caso di errori.
- `update_event_record()` è responsabile della validazione server-side e dell'aggiornamento del DB; restituisce un array [$errors, $updatedId].
- In assenza di errori si aggiunge un messaggio flash di successo e si reindirizza alla dashboard.

### Struttura del form (campi principali)

Sezioni e campi principali mostrati nel form:
- Informazioni base:
  - `name` (text)
  - `category` (select: Arte e Cultura, Formazione, Sport, Altro)
  - `description` (textarea)
  - `startDateTime` / `endDateTime` (`datetime-local`)
- Organizzatore e sede:
  - `organizzatore` (text)
  - `sede_via`, `sede_cap`, `sede_paese` (text)
- Accessibilità:
  - `rampe`, `ascensori` (checkbox)
  - `posti_disabili` (number)
- Sezioni ripetibili:
  - Tariffe: `tariffe_tipo[]`, `tariffe_prezzo[]`, `tariffe_valuta[]`
  - Orari: `orari_giorno[]` (date), `orari_apertura[]` (time), `orari_chiusura[]` (time)
  - Multimedia: `media_tipo[]`, `media_url[]`, `media_descrizione[]`

### Gestione errori e feedback

- Errori server-side: l'array `$errors` (restituito da `update_event_record()`) viene mostrato in cima al form in un banner rosso e ogni errore è escapitato con `htmlspecialchars()`.
- Errori client-side: elemento `<div data-client-errors class="hidden"></div>` usato dal JS per mostrare validazioni dinamiche.
- Messaggi flash:
  - Successo: `Evento aggiornato.`
  - Errore di caricamento iniziale: `Evento non trovato.`

### Considerazioni di sicurezza

- Autenticazione obbligatoria tramite `require_login()`.
- Escaping output con `htmlspecialchars()` per prevenire XSS (`old_edit`, `old_array`).
- Validazione server-side centralizzata in `update_event_record()`.
- Uso raccomandato di PDO con query parametrizzate in `includes/data.php` per prevenire SQL injection.
- Sanitizzazione/validazione dei file multimediali prima del salvataggio o della visualizzazione.

## Panoramica — Lista eventi / Dashboard

Questa sezione documenta lo script che visualizza la lista degli eventi nella dashboard amministrativa e fornisce azioni rapide (crea, modifica, elimina).

### Posizione file
`/admin/dashboard.php`

### Dipendenze

File richiesti:
- `../includes/session.php` — gestione delle sessioni utente
- `../includes/data.php` — funzioni di accesso ai dati (include `list_events()`)
- `../includes/layout.php` — funzioni di rendering (`render_admin_shell_start()`, `render_admin_shell_end()`)

Funzioni esterne utilizzate:
- `require_login()` — verifica che l'utente sia autenticato
- `list_events()` — recupera l'array di eventi con campi essenziali e conteggi correlati
- `render_admin_shell_start($title, $active)` / `render_admin_shell_end()` — wrapper layout

### Flusso di esecuzione

1. Autenticazione e caricamento dati

```php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$events = list_events();
render_admin_shell_start('Lista eventi', 'dashboard');
```

- `list_events()` fornisce una lista di eventi; ogni elemento contiene campi come `id`, `nome`, `categoria`, `data_inizio`, `data_fine`, `via`, `cap`, `paese`, `organizzatore` e conteggi `tariffe_coun[...]

2. Rendering della lista

- Se l'array `$events` è vuoto viene mostrata una riga con il messaggio: "Nessun evento presente. Crea il primo."
- Per ogni evento viene mostrata una riga con colonne:
  - Evento: nome e categoria (escapati con `htmlspecialchars`)
  - Periodo: `data_inizio` → `data_fine` formattati con `date('d/m/Y H:i', strtotime(...))`
  - Luogo: via, CAP, paese
  - Organizzatore
  - Dataset: conteggi (tariffe, orari, media)
  - Azioni: pulsanti per Modifica ed Elimina

3. Azioni disponibili

- Nuovo evento: link a `/admin/event-new.php`
- Modifica: link a `/admin/event-edit.php?id=...` (uso di `urlencode($event['id'])`)
- Elimina: button HTML (non form) con attributi data usati dal JS:
  - `data-delete`
  - `data-id="<?php echo htmlspecialchars($event['id']); ?>"`
  - `data-name="<?php echo htmlspecialchars($event['nome']); ?>"`

4. Modal di conferma eliminazione

- Modal con id `delete-modal` (inizialmente `hidden`) contiene:
  - Titolo e testo di conferma
  - Elemento `#delete-event-name` dove il JS inserisce il nome dell'evento
  - Form con id `delete-form` che punta a `/admin/event-delete.php` (metodo POST) — include input hidden `id` con id `delete-event-id`
  - Pulsante `data-close-modal` per chiudere la modal

- Il comportamento del pulsante Elimina nella tabella è gestito dal JavaScript client (in `assets/js/app.js`), che apre la modal, popola `#delete-event-name` e `#delete-event-id`, e permette l'invio [...]

### Sicurezza & validazione lato server

- La pagina richiede autenticazione (`require_login()`).
- L'azione di eliminazione effettiva viene eseguita dal form POST contenuto nella modal; il server deve validare l'ID e le autorizzazioni prima di eliminare.
- Tutto l'output visibile è escapato con `htmlspecialchars()` per prevenire XSS.

### Struttura HTML/CSS/JS rilevante (sintesi)

- Tabella HTML responsiva con classi Tailwind.
- Pulsanti azione:
  - Link: `/admin/event-new.php`, `/admin/event-edit.php?id=...`
  - Delete button: attributi `data-delete`, `data-id`, `data-name`
- Modal di eliminazione:
  - id `delete-modal`
  - form `#delete-form` action `/admin/event-delete.php` method `POST`
  - input hidden `#delete-event-id`
  - button `data-close-modal`
- Comportamento dinamico del modal e dei repeater gestito in `assets/js/app.js`.

---

## Panoramica del Form di Creazione Eventi

Questo documento descrive il funzionamento del form per la creazione di nuovi eventi. Il file gestisce sia la visualizzazione del form che l'elaborazione dei dati inviati.

### Dipendenze
Nella pagina PHP tipica del form vengono inclusi:

```php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();
```

- `session.php`: gestione delle sessioni utente
- `data.php`: funzioni per la manipolazione dei dati (contiene `create_event()`)
- `layout.php`: funzioni per il rendering dell'interfaccia amministrativa
- `require_login()`: verifica che l'utente sia autenticato prima di accedere alla pagina

### Flusso di funzionamento
1. Inizializzazione dati:

```php
$errors = [];
$input = [
    'name' => '',
    'description' => '',
    'category' => 'Arte e Cultura',
    // ... altri campi
];
```

L'array `$input` contiene i valori predefiniti per tutti i campi del form. Viene usato:
- al primo caricamento della pagina (form vuoto)
- dopo un errore di validazione (per ripopolare il form con i dati inseriti)

2. Elaborazione POST:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = array_merge($input, $_POST);
    [$errors, $createdId] = create_event($_POST);
    if (empty($errors)) {
        add_flash('success', 'Evento creato correttamente.');
        header('Location: /admin/dashboard.php');
        exit;
    }
}
```

- I dati POST vengono fusi con i valori predefiniti.
- `create_event()` tenta di creare l'evento.
- Se non ci sono errori: redirect alla dashboard con messaggio di successo.
- Se ci sono errori: la pagina si ricarica mostrando gli errori e i dati inseriti.

### Helper PHP
Per ripopolare i campi e prevenire XSS:

```php
function old(array $data, string $key, string $fallback = ''): string
{
    return htmlspecialchars($data[$key] ?? $fallback, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
```

Uso: `value="<?php echo old($input, 'name'); ?>"`

Per campi array (tariffe, orari, media):

```php
function old_array(array $data, string $key, int $index, string $fallback = ''): string
{
    $value = $data[$key][$index] ?? $fallback;
    return htmlspecialchars(is_string($value) ? $value : (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
```

Uso: `value="<?php echo old_array($input, 'tariffe_tipo', $i); ?>"`

### Struttura del form
Il form è organizzato in sezioni logiche:

Sezione 1: Informazioni Base
- Nome evento
- Categoria (select con opzioni predefinite)
- Descrizione
- Data/ora inizio e fine

Sezione 2: Organizzatore e Sede
- Nome organizzatore (ENTE)
- Indirizzo completo (via, CAP, paese)

Sezione 3: Accessibilità
- Checkbox per rampe e ascensori
- Campo numerico per posti riservati ai disabili

Sezioni ripetibili (con JavaScript)
- Tariffe: `tariffe_tipo[]`, `tariffe_prezzo[]`, `tariffe_valuta[]`
- Orari: `orari_giorno[]`, `orari_apertura[]`, `orari_chiusura[]`
- Contenuti Multimediali: `media_tipo[]`, `media_url[]`, `media_descrizione[]`

### Sezioni ripetibili (repeater)
Implementazione consigliata:
- Container repeater: `data-repeater="nome"`
- Ogni riga: `data-row`
- Pulsante aggiungi: `data-add-row data-target="nome"`
- Pulsante rimuovi: `data-remove-row`

Calcolo righe iniziali (PHP):

```php
<?php $tariffeRows = max(1, count($input['tariffe_tipo'] ?? [])); ?>
```

Mostra almeno 1 riga o tante righe quanti sono i valori nell'array (utile dopo errore di validazione).

### Gestione errori
Visualizzazione errori server-side (esempio):

```php
<?php if (!empty($errors)): ?>
<div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">
  <ul class="list-disc list-inside space-y-1">
    <?php foreach ($errors as $err): ?>
      <li><?php echo htmlspecialchars($err, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
```

Errori client-side:
```html
<div data-client-errors class="hidden"></div>
```
Per la validazione lato client (JavaScript) e la visualizzazione dinamica degli errori.

### Considerazioni di sicurezza
- Autenticazione: `require_login()` protegge la pagina.
- XSS Prevention: tutti gli output usano `htmlspecialchars()` con opzioni adeguate.
- Validazione: client-side (HTML5/JS) e server-side (in `create_event()` e `update_event_record()`).
- Uso di PDO con query parametrizzate per prevenire SQL injection.
- Sanitizzazione/validazione dei file multimediali prima del salvataggio o della visualizzazione.

### Attributi HTML5 di validazione
- `required` su campi obbligatori
- `type="datetime-local"` per data/ora
- `type="number"` con `min` e `step`
- `type="url"` per URL

Questi attributi migliorano l'usabilità ma non sostituiscono la validazione server-side.

### Styling
- Tailwind CSS con classi utility per:
  - Layout responsive (grid, gap)
  - Stili form (border, focus states)
  - Spacing consistente
  - Stati hover

### Manutenzione futura

Aggiungere un nuovo campo semplice:
1. Aggiungi il campo all'array `$input` iniziale.
2. Aggiungi l'HTML del campo nel form.
3. Usa `old($input, 'nome_campo')` per il valore.
4. Aggiorna la funzione `create_event()` / `update_event_record()` in `includes/data.php`.

Aggiungere una nuova sezione ripetibile:
1. Aggiungi i campi array a `$input` (es. `'nuovo_campo[]' => ['']`).
2. Crea la sezione con `data-repeater="nuovo"`.
3. Aggiungi pulsante con `data-add-row data-target="nuovo"`.
4. Usa `old_array()` per i valori.
5. Assicurati che il JavaScript gestisca la nuova sezione.

Modificare categorie:
- Sono hardcoded nel select: `['Arte e Cultura', 'Formazione', 'Sport', 'Altro']`.
- Per modificarle, cambia l'array o spostale in configurazione/database.
