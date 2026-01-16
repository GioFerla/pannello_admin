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

## Note
- Per modificare lo schema iniziale, aggiorna `docker/db/init.sql` e ricrea i container.
- I dati persistono nel volume `db_data`.
- Tailwind è fornito da CDN per semplicità.
