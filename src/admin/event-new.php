<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$errors = [];
$input = [
    'name' => '',
    'description' => '',
  'category' => '',
    'startDateTime' => '',
    'endDateTime' => '',
  'sede_nome' => '',
  'sede_via' => '',
  'sede_citta' => '',
  'sede_provincia' => '',
  'contatto_email' => '',
  'contatto_telefono' => '',
  'media_nome' => [''],
  'media_tipo' => [''],
  'media_url' => [''],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = array_merge($input, $_POST);
    [$errors, $createdId] = create_event($_POST);
    if (empty($errors)) {
        add_flash('success', 'Evento creato correttamente.');
        header('Location: /admin/dashboard.php');
        exit;
    }
}

function old(array $data, string $key, string $fallback = ''): string
{
    return htmlspecialchars($data[$key] ?? $fallback);
}

function old_array(array $data, string $key, int $index, string $fallback = ''): string
{
    $value = $data[$key][$index] ?? $fallback;
    return htmlspecialchars(is_string($value) ? $value : (string) $value);
}

render_admin_shell_start('Aggiungi evento', 'new');
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

  <form method="POST" class="space-y-8" data-event-form>
    <section class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-slate-700">Nome evento</label>
          <input type="text" name="name" value="<?php echo old($input, 'name'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-blue-500 focus:ring focus:ring-blue-100" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Ambito</label>
          <input type="text" name="category" value="<?php echo old($input, 'category'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-blue-500 focus:ring focus:ring-blue-100" required>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-slate-700">Descrizione</label>
          <textarea name="description" rows="3" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-blue-500 focus:ring focus:ring-blue-100" required><?php echo old($input, 'description'); ?></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Data/ora inizio</label>
          <input type="datetime-local" name="startDateTime" value="<?php echo old($input, 'startDateTime'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Data/ora fine</label>
          <input type="datetime-local" name="endDateTime" value="<?php echo old($input, 'endDateTime'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
        </div>
      </div>
    </section>

    <section class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-slate-700">Nome sede</label>
          <input type="text" name="sede_nome" value="<?php echo old($input, 'sede_nome'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Indirizzo sede</label>
          <input type="text" name="sede_via" value="<?php echo old($input, 'sede_via'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Città</label>
          <input type="text" name="sede_citta" value="<?php echo old($input, 'sede_citta'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Provincia</label>
          <input type="text" name="sede_provincia" value="<?php echo old($input, 'sede_provincia'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
        </div>
      </div>
    </section>

    <section class="space-y-3">
      <h3 class="text-sm font-semibold text-slate-800">Contatto</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-slate-700">Email</label>
          <input type="email" name="contatto_email" value="<?php echo old($input, 'contatto_email'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Telefono</label>
          <input type="text" name="contatto_telefono" value="<?php echo old($input, 'contatto_telefono'); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
        </div>
      </div>
    </section>

    <section class="space-y-3">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-800">Contenuti multimediali</h3>
        <button type="button" class="px-3 py-1 text-sm rounded-md border border-slate-200 hover:bg-slate-50" data-add-row data-target="media">+ Riga</button>
      </div>
      <div class="space-y-2" data-repeater="media">
        <?php $mediaRows = max(1, count($input['media_nome'] ?? [])); ?>
        <?php for ($i = 0; $i < $mediaRows; $i++): ?>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end border border-slate-100 rounded-lg p-3" data-row>
            <div>
              <label class="block text-xs font-medium text-slate-600">Nome</label>
              <input type="text" name="media_nome[]" value="<?php echo old_array($input, 'media_nome', $i); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" placeholder="Titolo media">
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600">Tipo</label>
              <input type="text" name="media_tipo[]" value="<?php echo old_array($input, 'media_tipo', $i); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" placeholder="immagine / video">
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600">URL</label>
              <input type="url" name="media_url[]" value="<?php echo old_array($input, 'media_url', $i); ?>" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" placeholder="https://...">
            </div>
            <div class="flex justify-end">
              <button type="button" class="px-3 py-2 rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" data-remove-row>&times;</button>
            </div>
          </div>
        <?php endfor; ?>
      </div>
    </section>

    <div data-client-errors class="hidden"></div>

    <div class="flex items-center justify-end gap-3">
      <a href="/admin/dashboard.php" class="px-4 py-2 rounded-md border border-slate-200 text-slate-700 hover:bg-white">Annulla</a>
      <button type="submit" class="px-5 py-2 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700">Salva evento</button>
    </div>
  </form>
</div>
<?php render_admin_shell_end(); ?>
