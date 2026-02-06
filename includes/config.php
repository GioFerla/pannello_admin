<?php
    // Configurazione centrale: credenziali admin e variabili d'ambiente per il DB.
    return [
        'app_name' => getenv('APP_NAME') ?: 'Event Admin',
        'session_name' => 'event_admin_session',
        'admin_user' => [
            'username' => getenv('APP_ADMIN_USER') ?: 'admin',
            'password' => getenv('APP_ADMIN_PASS') ?: 'admin123',
        ],
        'db' => [
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: '3307',
            'name' => getenv('DB_NAME') ?: 'eventi_db',
            'user' => getenv('DB_USER') ?: 'classe',
            'pass' => getenv('DB_PASSWORD') ?: 'password',
        ],
        'base_url' => '/',
    ];
