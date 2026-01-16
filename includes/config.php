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
        'host' => getenv('DB_HOST') ?: 'db',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'pannello',
        'user' => getenv('DB_USER') ?: 'pannello',
        'pass' => getenv('DB_PASSWORD') ?: 'pannello',
    ],
    'base_url' => '/',
];
