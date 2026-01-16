<?php
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = require __DIR__ . '/config.php';
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $cfg['db']['host'],
        $cfg['db']['port'],
        $cfg['db']['name']
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'], $options);
    return $pdo;
}

function generate_event_id(): string
{
    return 'EVT-' . strtoupper(bin2hex(random_bytes(6))) . '-' . date('Y');
}
