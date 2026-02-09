# Pannello Admin - Event Management System

Sistema di gestione eventi con pannello amministrativo per la gestione di eventi, multimedia e database remoto tramite tunnel SSH.

## ⚠️ Nota sulla Sicurezza

**IMPORTANTE**: Tutte le credenziali in questo repository sono solo esempi e non funzionali.

### Best Practices di Sicurezza

- **NON committare mai credenziali reali** nel repository
- Usare variabili d'ambiente per tutte le configurazioni sensibili
- Copiare il file `src/.env.example` in `src/.env` e inserire le proprie credenziali reali
- Il file `.env` è già incluso nel `.gitignore` per prevenire commit accidentali
- Mai condividere credenziali di produzione nel codice sorgente

## Installazione e Configurazione

### 1. Configurazione Ambiente

Prima di avviare l'applicazione, configura le tue credenziali:

```bash
cd src
cp .env.example .env
# Modifica .env con le tue credenziali reali
```

### 2. Avvio con Docker Compose

```bash
docker-compose up --build
```

L'applicazione sarà disponibile su `http://localhost:8101`

### 3. Connessione SSH (Esempio)

**NOTA**: L'host `ssh.example.com` è solo un esempio. Sostituisci con il tuo host reale.

```bash
ssh example_user@ssh.example.com
```

## Struttura del Progetto

```
.
├── docker-compose.yaml      # Configurazione Docker
├── src/                     # Codice sorgente applicazione
│   ├── .env.example        # File di esempio per variabili d'ambiente
│   ├── includes/           # File di configurazione e utility
│   │   └── config.php      # Configurazione centrale
│   ├── data/               # Dati dell'applicazione
│   │   └── admin.json      # Credenziali admin (con hash)
│   └── admin/              # Pannello amministrativo
└── ssh_tunnel/             # Configurazione tunnel SSH

```

## Credenziali di Default (Solo Esempi)

Le credenziali di default nel codice sono solo esempi:

- **Username Admin**: `demo_admin`
- **Password Admin**: `demo_password_123`
- **Database**: `example_events_db`
- **DB User**: `db_user_example`

⚠️ **Queste credenziali NON funzioneranno in produzione**. Devono essere sostituite con credenziali reali nel file `.env`.

## Documentazione

Per maggiori informazioni sulla gestione dei media e altre funzionalità, consulta `src/README.md`.

## Licenza

Questo progetto è fornito come esempio educativo.
