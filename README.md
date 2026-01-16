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

    App: http://localhost:8080

    DB: localhost:3306 (user/password: pannello/pannello, db: pannello)

Credenziali admin (configurabili via env): admin / admin123.
Struttura cartelle

    index.php — login

    admin/ — dashboard, create, edit, delete

    includes/ — sessione, config env-based, PDO, validazione/CRUD, layout

    assets/js/app.js — menu mobile, toast, modale, repeater, validazione client

    docker/ — db/init.sql per schema+dati

    docker-compose.yml, Dockerfile

Workflow

    Login.

    Dashboard con conteggi tariffe/orari/media, edit/delete.

    Creazione/modifica con sezioni: dettagli evento, ente+sede, accessibilità, tariffe, orari, media.

    Eliminazione con conferma modale.

Variabili d'ambiente principali

Configurate in docker-compose.yml per il servizio web:

    APP_ADMIN_USER, APP_ADMIN_PASS

    DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD

Note

    Per modificare lo schema iniziale, aggiorna docker/db/init.sql e ricrea i container.

    I dati persistono nel volume db_data.

    Tailwind è fornito da CDN per semplicità.

Panoramica del Form di Creazione Eventi

Questo documento descrive il funzionamento del form per la creazione di nuovi eventi nel sistema. Il file gestisce sia la visualizzazione del form che l'elaborazione dei dati inviati.
Struttura del File

Dipendenze

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();

    session.php: gestione delle sessioni utente

    data.php: funzioni per la manipolazione dei dati (contiene create_event())

    layout.php: funzioni per il rendering dell'interfaccia amministrativa

    require_login(): verifica che l'utente sia autenticato prima di accedere alla pagina

Flusso di Funzionamento
1. Inizializzazione Dati

$errors = [];
$input = [
    'name' => '',
    'description' => '',
    'category' => 'Arte e Cultura',
    // ... altri campi
];

L'array $input contiene i valori predefiniti per tutti i campi del form. Questi valori vengono utilizzati:

    Al primo caricamento della pagina (form vuoto)

    Dopo un errore di validazione (per ripopolare il form con i dati inseriti)

2. Elaborazione POST

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = array_merge($input, $_POST);
    [$errors, $createdId] = create_event($_POST);
    if (empty($errors)) {
        add_flash('success', 'Evento creato correttamente.');
        header('Location: /admin/dashboard.php');
        exit;
    }
}

    I dati POST vengono fusi con i valori predefiniti

    create_event() tenta di creare l'evento

    Se non ci sono errori: redirect alla dashboard con messaggio di successo

    Se ci sono errori: la pagina si ricarica mostrando gli errori e i dati inseriti

Funzioni Helper

old()

function old(array $data, string $key, string $fallback = ''): string
{
    return htmlspecialchars($data[$key] ?? $fallback);
}

    Uso: value="<?php echo old($input, 'name'); ?>"

    Scopo: ripopolare i campi del form dopo un errore di validazione, prevenendo XSS

old_array()

function old_array(array $data, string $key, int $index, string $fallback = ''): string
{
    $value = $data[$key][$index] ?? $fallback;
    return htmlspecialchars(is_string($value) ? $value : (string) $value);
}

    Uso: value="<?php echo old_array($input, 'tariffe_tipo', $i); ?>"

    Per campi array (tariffe, orari, media)

Struttura del Form

Il form è organizzato in sezioni logiche:
Sezione 1: Informazioni Base

    Nome evento

    Categoria (select con opzioni predefinite)

    Descrizione

    Data/ora inizio e fine

Sezione 2: Organizzatore e Sede

    Nome organizzatore (ENTE)

    Indirizzo completo (via, CAP, paese)

Sezione 3: Accessibilità

    Checkbox per rampe e ascensori

    Campo numerico per posti riservati ai disabili

Sezioni Ripetibili (con JavaScript)

    Tariffe

        tariffe_tipo[], tariffe_prezzo[], tariffe_valuta[]

    Orari

        orari_giorno[], orari_apertura[], orari_chiusura[]

    Contenuti Multimediali

        media_tipo[], media_url[], media_descrizione[]

Sistema di Righe Ripetibili

    Ogni sezione ripetibile ha: data-repeater="nome" sul container

    Ogni riga: data-row

    Pulsante aggiungi: data-add-row data-target="nome"

    Pulsante rimuovi: data-remove-row

Calcolo Righe Iniziali

<?php $tariffeRows = max(1, count($input['tariffe_tipo'] ?? [])); ?>

Mostra almeno 1 riga o tante righe quanti sono i valori nell'array (utile dopo errore di validazione).
Gestione Errori

Visualizzazione Errori

<?php if (!empty($errors)): ?>
<div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">
  <ul class="list-disc list-inside space-y-1">
    <?php foreach ($errors as $err): ?>
      <li><?php echo htmlspecialchars($err); ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

    Gli errori vengono mostrati in un banner rosso in cima al form

    Escapati per prevenire XSS

Contenitore Errori Client

<div data-client-errors class="hidden"></div>

    Per eventuali errori di validazione lato client (JavaScript)

Considerazioni di Sicurezza

    Autenticazione: require_login() protegge la pagina

    XSS Prevention: tutti gli output usano htmlspecialchars()

    Validazione: client-side (HTML5) e server-side (in create_event())

Attributi HTML5 di Validazione

    required su campi obbligatori

    type="datetime-local" per data/ora

    type="number" con min e step

    type="url" per URL

Styling

    Tailwind CSS con classi utility per:

        Layout responsive (grid, gap)

        Stili form (border, focus states)

        Spacing consistente

        Stati hover

Manutenzione Futura

Aggiungere un Nuovo Campo Semplice

    Aggiungi il campo all'array $input iniziale

    Aggiungi l'HTML del campo nel form

    Usa old($input, 'nome_campo') per il valore

    Aggiorna la funzione create_event() in data.php

Aggiungere una Nuova Sezione Ripetibile

    Aggiungi i campi array a $input (es. 'nuovo_campo[]' => [''])

    Crea la sezione con data-repeater="nuovo"

    Aggiungi pulsante con data-add-row data-target="nuovo"

    Usa old_array() per i valori

    Assicurati che il JavaScript gestisca la nuova sezione

Modificare Categorie

    Hardcoded nel select: ['Arte e Cultura', 'Formazione', 'Sport', 'Altro']

    Per modificarle, cambia l'array o spostale in configurazione/database.
