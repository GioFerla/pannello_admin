<?php
require_once __DIR__ . '/session.php';

function render_head(string $title = 'Admin Panel'): void
{
    $cfg = require __DIR__ . '/config.php';
    echo '<!DOCTYPE html>';
    echo '<html lang="it">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($title) . ' | ' . htmlspecialchars($cfg['app_name']) . '</title>';
    echo '<script src="https://cdn.tailwindcss.com"></script>';
    echo '<script>tailwind.config = { theme: { extend: { colors: { brand: { 50: "#f6f9ff", 100: "#e8f0ff", 300: "#a6c8ff", 500: "#3b82f6", 600: "#2563eb" }}}}};</script>';
    echo '</head>';
    echo '<body class="bg-slate-50 text-slate-900 min-h-screen">';
}

function render_admin_shell_start(string $title, string $active): void
{
    render_head($title);
    $user = current_user();
    echo '<div class="flex min-h-screen">';
    echo '<aside class="w-64 bg-white shadow-sm border-r border-slate-200 hidden md:block">';
    echo '<div class="p-6 border-b border-slate-100">';
    echo '<h1 class="text-xl font-semibold text-slate-800">Event Admin</h1>';
    echo '<p class="text-xs text-slate-500 mt-1">Gestione eventi istituzionali</p>';
    echo '</div>';
    echo '<nav class="p-4 space-y-1">';
    $links = [
        'dashboard' => ['/admin/dashboard.php', 'Lista eventi'],
        'new' => ['/admin/event-new.php', 'Aggiungi evento'],
        'logout' => ['/logout.php', 'Logout'],
    ];
    foreach ($links as $key => [$href, $label]) {
        $isActive = $key === $active;
        $classes = $isActive
            ? 'bg-blue-50 text-blue-600'
            : 'text-slate-700 hover:bg-slate-100';
        echo '<a class="flex items-center gap-2 px-3 py-2 rounded-md transition ' . $classes . '" href="' . $href . '">';
        echo '<span>' . htmlspecialchars($label) . '</span>';
        echo '</a>';
    }
    echo '</nav>';
    if ($user) {
        echo '<div class="p-4 border-t border-slate-100 text-sm text-slate-600">';
        echo 'Loggato come <span class="font-semibold text-slate-800">' . htmlspecialchars($user['username']) . '</span>';
        echo '</div>';
    }
    echo '</aside>';

    echo '<div class="flex-1 flex flex-col">';
    echo '<header class="md:hidden flex items-center justify-between px-4 py-3 bg-white border-b border-slate-200 shadow-sm">';
    echo '<div class="font-semibold text-slate-800">Event Admin</div>';
    echo '<button id="mobile-menu-btn" class="p-2 rounded-md border border-slate-200 text-slate-700" aria-label="Apri menu">';
    echo '<span class="sr-only">Apri menu</span>';
    echo '&#9776;';
    echo '</button>';
    echo '</header>';

    echo '<div id="mobile-menu" class="hidden md:hidden bg-white border-b border-slate-200 shadow-sm">';
    echo '<nav class="p-3 space-y-1">';
    foreach ($links as $key => [$href, $label]) {
        $isActive = $key === $active;
        $classes = $isActive
            ? 'bg-blue-50 text-blue-600'
            : 'text-slate-700 hover:bg-slate-100';
        echo '<a class="block px-3 py-2 rounded-md transition ' . $classes . '" href="' . $href . '">' . htmlspecialchars($label) . '</a>';
    }
    echo '</nav>';
    echo '</div>';

    echo '<main class="p-6 md:p-10 flex-1">';
    echo '<div class="max-w-6xl mx-auto">';
    echo '<div class="flex items-center justify-between mb-6">';
    echo '<div>'; 
    echo '<p class="text-sm uppercase tracking-wide text-slate-500">Pannello di controllo</p>';
    echo '<h2 class="text-2xl font-semibold text-slate-800">' . htmlspecialchars($title) . '</h2>';
    echo '</div>';
    if ($user) {
        echo '<span class="text-xs text-slate-500">Accesso: ' . htmlspecialchars($user['logged_in_at']) . '</span>';
    }
    echo '</div>';

    $flashes = get_flashes();
    if (!empty($flashes)) {
        echo '<div id="toast-container" class="space-y-2 mb-4">';
        foreach ($flashes as $flash) {
            $color = $flash['type'] === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800';
            echo '<div class="toast flex items-center justify-between rounded-md border px-4 py-3 ' . $color . '">';
            echo '<span>' . htmlspecialchars($flash['message']) . '</span>';
            echo '<button class="text-xs underline" data-dismiss-toast>&times;</button>';
            echo '</div>';
        }
        echo '</div>';
    }
}

function render_admin_shell_end(): void
{
    echo '</div>'; // max container
    echo '</main>';
    echo '</div>'; // flex-1
    echo '</div>'; // flex
    echo '<script src="/assets/js/app.js"></script>';
    echo '</body></html>';
}
