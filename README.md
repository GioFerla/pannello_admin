# Pannello Amministrazione Eventi (Docker)

Applicazione full‑stack PHP + Apache con frontend Tailwind CSS e database MariaDB/MySQL orchestrata via Docker Compose. Include login, CRUD completo e gestione relazioni secondo il modello ER fornito.

## Indice
- [Architettura](#architettura)
- [Database](#database)
- [Avvio rapido](#avvio-rapido)
- [Struttura cartelle](#struttura-cartelle)
- [Workflow](#workflow)
- [Variabili d'ambiente principali](#variabili-dambiente-principali)
- [Note](#note)
- [Panoramica — Eliminazione Eventi](#panoramica---eliminazione-eventi)
- [Panoramica — Modifica Evento](#panoramica---modifica-evento)
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

## Workflow
1. Login.
2. Dashboard con conteggi tariffe/orari/media, edit/delete.
3. Creazione/modifica con sezioni: dettagli evento, ente+sede, accessibilità, tariffe, orari, media.
4. Eliminazione con conferma modale.

## Variabili d'ambiente principali
Configurate in `docker-compose.yml` per il servizio `web`:
- `APP_ADMIN_USER`, `APP_ADMIN_PASS`
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`

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

- Autenticazione obbligatoria tramite `require_login()`.
- Metodo POST obbligatorio per prevenire cancellazioni via GET.
- Validazione dell'input e type casting dell'ID.
- Raccomandazioni: autorizzazioni basate su ruoli, logging e uso di transazioni in `delete_event_record()`.

## Panoramica — Modifica Evento

Questa sezione descrive lo script che mostra e processa il form di modifica di un evento. Lo script combina caricamento dati esistenti, visualizzazione form con sezioni ripetibili e salvataggio delle modifiche.

### Posizione file
`/admin/edit_event.php` (o percorso equivalente nella directory `admin`)

> Nota: il codice di riferimento è il file PHP fornito nella root `admin/` che esegue:
> - inclusione delle dipendenze
> - controllo autenticazione
> - recupero evento tramite `id` GET
> - rendering del form precompilato
> - gestione POST per aggiornamento tramite `update_event_record()`

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

- Se l'ID non esiste o `fetch_event()` restituisce null, l'utente viene reindirizzato alla dashboard con errore.

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

> Nota sul comportamento: il codice imposta `startDateTime` / `endDateTime` a partire dai valori originali prima della fusione con `$_POST`. Questo mantiene i valori leggibili nel form ma l'aggiornamento effettivo viene eseguito con i dati passati in `$_POST` a `update_event_record()`.

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

Ogni sezione ripetibile usa il pattern:
- Container: `data-repeater="nome"`
- Righe: `data-row`
- Pulsante aggiungi: `data-add-row data-target="nome"`
- Pulsante rimuovi: `data-remove-row`

Il numero iniziale di righe è calcolato con PHP, ad esempio:
```php
$tariffeRows = max(1, count($input['tariffe_tipo'] ?? []));
```

### Gestione errori e feedback

- Errori server-side: l'array `$errors` (restituito da `update_event_record()`) viene mostrato in cima al form in un banner rosso e ogni errore è escapitato con `htmlspecialchars()`.
- Errori client-side: elemento `<div data-client-errors class="hidden"></div>` usato dal JS per mostrare validazioni dinamiche.
- Messaggi flash:
  - Successo: `Evento aggiornato.`
  - Errore di caricamento iniziale: `Evento non trovato.`

### Considerazioni di sicurezza

- Autenticazione obbligatoria tramite `require_login()`.
- Escaping output con `htmlspecialchars()` per prevenire XSS (`old_edit`, `old_array`).
- Validazione server-side centralizzata in `update_event_record()` (necessaria).
- Uso raccomandato di PDO con query parametrizzate in `includes/data.php` per prevenire SQL injection.
- Controlli aggiuntivi raccomandati:
  - Verificare l'autorizzazione (ruoli/permessi) prima di permettere modifiche.
  - Sanitizzare/validare URL multimediali.
  - Usare transazioni se l'aggiornamento coinvolge più tabelle.

### Parametri / metodi utilizzati

- Lettura evento: parametro `id` via GET obbligatorio per mostrare il form.
- Salvataggio: richiesta POST con campi del form; `update_event_record($id, $data)` prende i dati da `$_POST`.

### Esempio di accesso alla pagina di modifica

Link per aprire il form di modifica per un evento con id `123`:

```html
<a href="/admin/edit_event.php?id=123">Modifica evento</a>
```

### Note di manutenzione / miglioramenti consigliati

- Centralizzare la form-building / old helpers per riusabilità e consistenza.
- Migliorare la gestione delle date unificando la conversione tra timezone e formato `datetime-local`.
- Validare client-side i campi ripetibili e assicurarsi che `update_event_record()` gestisca array vuoti/assenze.
- Considerare la paginazione delle sezioni multimediali se grandi.

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
