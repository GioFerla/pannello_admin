<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/data.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/dashboard.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if (!$id) {
    add_flash('error', 'ID evento mancante.');
    header('Location: /admin/dashboard.php');
    exit;
}

$events = load_events();
$before = count($events);
$events = delete_event($events, $id);
$after = count($events);

if ($after === $before) {
    add_flash('error', 'Evento non trovato.');
} else {
    save_events($events);
    add_flash('success', 'Evento eliminato.');
}

header('Location: /admin/dashboard.php');
exit;
