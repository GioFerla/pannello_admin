<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$event = $id ? find_event($id) : null;
if (!$event) {
    add_flash('error', 'Evento non trovato.');
    header('Location: /admin/dashboard.php');
    exit;
}

$errors = [];
$input = array_merge([
    'id' => $event['id'],
    'name' => $event['name'],
    'description' => $event['description'],
    'scope' => $event['scope'],
    'startDateTime' => $event['startDateTime'],
    'endDateTime' => $event['endDateTime'],
    'issueDateTime' => $event['issueDateTime'],
    'mediaResource_uri' => $event['mediaResource']['uri'] ?? '',
    'venue_id' => $event['venue']['id'] ?? 1,
    'venue_name' => $event['venue']['name'] ?? '',
    'venue_street' => $event['venue']['address']['street'] ?? '',
    'venue_city' => $event['venue']['address']['city'] ?? '',
    'venue_province' => $event['venue']['address']['province'] ?? '',
    'venue_zipcode' => $event['venue']['address']['zipcode'] ?? '',
    'contact_email' => $event['contact']['email'] ?? '',
    'contact_phone' => $event['contact']['phone'] ?? '',
], $_POST);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['id'] = $id;
    [$errors, $eventData] = validate_event($input, $event['issueDateTime']);
    if (empty($errors)) {
        $eventData['id'] = $id;
        $events = load_events();
        $events = update_event($events, $id, $eventData);
        save_events($events);
        add_flash('success', 'Evento aggiornato.');
        header('Location: /admin/dashboard.php');
        exit;
    }
}

function old_edit(array $data, string $key): string
{
    return htmlspecialchars($data[$key] ?? '');
}

function to_local_datetime(string $value): string
{
    try {
        $dt = new DateTime($value);
        return $dt->format('Y-m-d\TH:i');
    } catch (Exception $e) {
        return '';
    }
}

render_admin_shell_start('Modifica evento', 'dashboard');
?>
<div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
  <?php if (!empty($errors)): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">
      <ul class="list-disc list-inside space-y-1">
        <?php foreach ($errors as $err): ?>
          <li><?php echo htmlspecialchars($err); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST" class="space-y-6" data-event-form>
    <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
    <input type="hidden" name="issueDateTime" value="<?php echo old_edit($input, 'issueDateTime'); ?>">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-slate-700">Nome evento</label>
        <input type="text" name="name" value="<?php echo old_edit($input, 'name'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Ambito</label>
        <select name="scope" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
          <?php foreach (['Arte e Cultura', 'Formazione', 'Sport', 'Altro'] as $scope): ?>
            <option value="<?php echo htmlspecialchars($scope); ?>" <?php echo ($input['scope'] ?? '') === $scope ? 'selected' : ''; ?>><?php echo htmlspecialchars($scope); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">Descrizione</label>
        <textarea name="description" rows="3" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required><?php echo old_edit($input, 'description'); ?></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Data/ora inizio</label>
        <input type="datetime-local" name="startDateTime" value="<?php echo to_local_datetime($input['startDateTime'] ?? ''); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Data/ora fine</label>
        <input type="datetime-local" name="endDateTime" value="<?php echo to_local_datetime($input['endDateTime'] ?? ''); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">URL risorsa media</label>
        <input type="url" name="mediaResource_uri" value="<?php echo old_edit($input, 'mediaResource_uri'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Email contatto</label>
        <input type="email" name="contact_email" value="<?php echo old_edit($input, 'contact_email'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Telefono contatto</label>
        <input type="text" name="contact_phone" value="<?php echo old_edit($input, 'contact_phone'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-slate-700">Nome luogo</label>
        <input type="text" name="venue_name" value="<?php echo old_edit($input, 'venue_name'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Indirizzo</label>
        <input type="text" name="venue_street" value="<?php echo old_edit($input, 'venue_street'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Città</label>
        <input type="text" name="venue_city" value="<?php echo old_edit($input, 'venue_city'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
      </div>
      <div class="grid grid-cols-3 gap-3">
        <div>
          <label class="block text-sm font-medium text-slate-700">Provincia</label>
          <input type="text" name="venue_province" value="<?php echo old_edit($input, 'venue_province'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">CAP</label>
          <input type="text" name="venue_zipcode" value="<?php echo old_edit($input, 'venue_zipcode'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">ID sede</label>
          <input type="number" name="venue_id" value="<?php echo old_edit($input, 'venue_id'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-slate-700">Issue datetime (ISO 8601)</label>
        <input type="text" value="<?php echo old_edit($input, 'issueDateTime'); ?>" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 bg-slate-50" readonly>
      </div>
    </div>

    <div data-client-errors class="hidden"></div>

    <div class="flex items-center justify-end gap-3">
      <a href="/admin/dashboard.php" class="px-4 py-2 rounded-md border border-slate-200 text-slate-700 hover:bg-white">Annulla</a>
      <button type="submit" class="px-5 py-2 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700">Aggiorna evento</button>
    </div>
  </form>
</div>
<?php render_admin_shell_end(); ?>
