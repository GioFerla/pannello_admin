<?php
    // Configurazione centrale: credenziali admin e variabili d'ambiente per il DB.
    return [
        'app_name' => getenv('APP_NAME') ?: 'Event Admin',
        'session_name' => 'event_admin_session',
        'admin_user' => [
            'username' => getenv('APP_ADMIN_USER') ?: 'demo_admin',
            'password' => getenv('APP_ADMIN_PASS') ?: 'demo_password_123',
        ],
        'db' => [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: '3306',
            'name' => getenv('DB_NAME') ?: 'example_events_db',
            'user' => getenv('DB_USER') ?: 'db_user_example',
            'pass' => getenv('DB_PASSWORD') ?: 'db_password_example',
        ],
        'base_url' => '/',
    ];
