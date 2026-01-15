<?php
require_once __DIR__ . '/includes/session.php';
logout_user();
add_flash('success', 'Sei stato disconnesso.');
header('Location: /index.php');
exit;
