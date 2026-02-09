<?php
require_once __DIR__ . '/includes/session.php';

if (is_logged_in()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = null;
$flashes = get_flashes();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (attempt_login($username, $password)) {
        add_flash('success', 'Accesso eseguito.');
        header('Location: /admin/dashboard.php');
        exit;
    }
    $error = 'Credenziali non valide.';
}

$authRequired = isset($_GET['auth']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Event Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
  <div class="w-full max-w-md">
    <div class="bg-white shadow-xl rounded-xl border border-slate-200 p-8">
      <h1 class="text-2xl font-semibold text-slate-800 mb-2">Pannello Admin</h1>
      <p class="text-sm text-slate-500 mb-6">Accedi per gestire gli eventi.</p>
      <?php if ($authRequired): ?>
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3 text-sm">
          Sessione scaduta. Effettua il login.
        </div>
      <?php endif; ?>
      <?php if (!empty($flashes)): ?>
        <?php foreach ($flashes as $flash): ?>
          <div class="mb-4 rounded-lg border px-4 py-3 text-sm <?php echo $flash['type'] === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'; ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>
      <form method="POST" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700" for="username">Username</label>
          <input id="username" name="username" type="text" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring focus:ring-blue-100" placeholder="admin">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700" for="password">Password</label>
          <input id="password" name="password" type="password" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring focus:ring-blue-100" placeholder="••••••">
        </div>
        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700 focus:outline-none focus:ring focus:ring-blue-200">Accedi</button>
      </form>
      <p class="mt-6 text-xs text-slate-500">Default: admin / admin123. Modifica in data/admin.json.</p>
    </div>
  </div>
</body>
</html>
