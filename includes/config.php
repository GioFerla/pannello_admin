<?php
// Basic application settings and credentials. Replace defaults in production.
return [
    'app_name' => 'Event Admin',
    'session_name' => 'event_admin_session',
    'admin_user' => [
        'username' => 'admin',
        'password' => 'admin123',
    ],
    'data_file' => __DIR__ . '/../data/events.json',
    'base_url' => '/',
];
