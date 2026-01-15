# Pannello Amministrazione Eventi

Pannello admin PHP + Tailwind CSS per la gestione di eventi (CRUD completo) con autenticazione a sessione e storage su file JSON.

## Perché JSON invece di MySQL
Per un contesto scolastico rapido da distribuire in locale, il file JSON evita dipendenze e setup di database. L’interfaccia dei dati è comunque separata in `includes/data.php`, quindi puoi passare a MySQL in seguito sostituendo solo quella parte.

## Struttura cartelle
- index.php — login
- logout.php — logout
- admin/
  - dashboard.php — lista + azioni
  - event-new.php — creazione
  - event-edit.php — modifica
  - event-delete.php — eliminazione
- includes/
  - config.php — impostazioni e credenziali
  - session.php — gestione sessione + flash
  - data.php — CRUD su JSON + validazione
  - layout.php — shell grafica comune
- data/events.json — dati eventi
- assets/js/app.js — script UI (menu, toast, modale, validazioni client)

## Requisiti
- PHP 8+ (sessioni attive)
- Server web (Apache/XAMPP) oppure `php -S` per test locale

## Avvio rapido con PHP built-in
```bash
cd pannello_admin
php -S localhost:8000
```
Apri http://localhost:8000

## Avvio con XAMPP / Apache
1. Copia la cartella `pannello_admin` in `htdocs`.
2. Avvia Apache da XAMPP.
3. Apri http://localhost/pannello_admin

## Credenziali di default
- username: `admin`
- password: `admin123`
Modifica in `includes/config.php`.

## Flusso di utilizzo
1. Effettua login da `index.php`.
2. Sidebar con 3 voci: Lista eventi, Aggiungi evento, Logout.
3. Crea/modifica/elimina eventi. Eliminazione chiede conferma via modale.
4. Toast di successo/errore mostrano l’esito delle operazioni.

## Struttura dati evento (JSON)
Ogni evento rispetta questo schema:
```json
{
  "id": 1,
  "name": "...",
  "description": "...",
  "scope": "...",
  "startDateTime": "2025-07-19T14:30:00",
  "endDateTime": "2025-07-19T16:30:00",
  "issueDateTime": "2025-08-05T14:52:22",
  "mediaResource": { "uri": "https://..." },
  "venue": {
    "id": 1,
    "name": "...",
    "address": {
      "street": "...",
      "city": "...",
      "province": "MI",
      "zipcode": "..."
    }
  },
  "contact": { "email": "...", "phone": "..." }
}
```

## Validazione
- Client-side: controlli minimi su form (lunghezze, URL, email, date).
- Server-side: validazione completa in `includes/data.php` (required, formato ISO date, URL/email/telefono, start <= end). Le risposte mostrano errori in pagina.

## Note di sicurezza
- Aggiorna subito le credenziali in produzione.
- Assicurati che `data/events.json` sia scrivibile dal server web.
- Per HTTPS e cookie secure, abilita l’opzione `cookie_secure` nella sessione se hai TLS.
