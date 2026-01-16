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
- Validazione: client-side (HTML5/JS) e server-side (in `create_event()`).
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
4. Aggiorna la funzione `create_event()` in `includes/data.php`.

Aggiungere una nuova sezione ripetibile:
1. Aggiungi i campi array a `$input` (es. `'nuovo_campo[]' => ['']`).
2. Crea la sezione con `data-repeater="nuovo"`.
3. Aggiungi pulsante con `data-add-row data-target="nuovo"`.
4. Usa `old_array()` per i valori.
5. Assicurati che il JavaScript gestisca la nuova sezione.

Modificare categorie:
- Sono hardcoded nel select: `['Arte e Cultura', 'Formazione', 'Sport', 'Altro']`.
- Per modificarle, cambia l'array o spostale in configurazione/database.
