<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/data.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/dashboard.php');
    exit;
}

$id = isset($_POST['id']) ? (string) $_POST['id'] : '';
if ($id === '') {
    add_flash('error', 'ID evento mancante.');
    header('Location: /admin/dashboard.php');
    exit;
}

if (delete_event_record($id)) {
    add_flash('success', 'Evento eliminato.');
} else {
    add_flash('error', 'Evento non trovato.');
}

header('Location: /admin/dashboard.php');
exit;
