# Pannello Amministrazione Eventi

Applicazione PHP con frontend Tailwind CSS e database MySQL/MariaDB esterno. Include login, CRUD completo e gestione relazioni secondo il modello ER fornito.

## Requisiti
- PHP 8.x con estensione PDO MySQL
- Server web locale (es. Apache/XAMPP)
- Database MySQL/MariaDB raggiungibile via IP pubblico

## Database
Lo schema aggiornato è disponibile in `data/eventi_db.sql`. Importalo nel tuo DB esterno (database `eventi_db`).

## Configurazione
Imposta le credenziali del DB in `includes/config.php` oppure tramite variabili d'ambiente:
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`

Le credenziali admin sono salvate localmente in `data/admin.json` (password hash). Modificalo se necessario.

### SSH forwarding (DB remoto)
Se il server DB è accessibile solo via SSH, crea un tunnel e usa il DB in locale:
1. Avvia il tunnel:
	`ssh -L 3306:localhost:3306 esercitazioni.online`
2. Imposta `DB_HOST=127.0.0.1` (già nel file .env).
3. Mantieni aperta la sessione SSH mentre usi l'app.

## Avvio
Apri il progetto nel tuo server web locale e visita la homepage dell'app.

## Struttura cartelle
- index.php — login
- admin/ — dashboard, create, edit, delete
- includes/ — sessione, config, PDO, validazione/CRUD, layout
- assets/js/app.js — menu mobile, toast, modale, repeater, validazione client
- data/eventi_db.sql — schema database

## Note
- Tailwind è fornito da CDN per semplicità.
