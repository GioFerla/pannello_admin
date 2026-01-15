<?php
$config = require __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name($config['session_name']);
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /index.php?auth=required');
        exit;
    }
}

function attempt_login(string $username, string $password): bool
{
    $cfg = require __DIR__ . '/config.php';
    if ($username === $cfg['admin_user']['username'] && $password === $cfg['admin_user']['password']) {
        $_SESSION['user'] = [
            'username' => $username,
            'logged_in_at' => date('c'),
        ];
        return true;
    }
    return false;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function add_flash(string $type, string $message): void
{
    $_SESSION['_flashes'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flashes(): array
{
    $flashes = $_SESSION['_flashes'] ?? [];
    unset($_SESSION['_flashes']);
    return $flashes;
}

function base_url(): string
{
    $cfg = require __DIR__ . '/config.php';
    return rtrim($cfg['base_url'], '/');
}
