<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$events = load_events();
render_admin_shell_start('Lista eventi', 'dashboard');
?>
<div class="flex justify-between items-center mb-4">
  <div>
    <p class="text-sm text-slate-500">Gestisci gli eventi pubblicati</p>
  </div>
  <div class="flex items-center gap-2">
    <a href="/admin/event-new.php" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-sm">+ Nuovo evento</a>
  </div>
</div>

<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200">
      <thead class="bg-slate-50 text-xs uppercase text-slate-500">
        <tr>
          <th class="px-4 py-3 text-left">Evento</th>
          <th class="px-4 py-3 text-left">Periodo</th>
          <th class="px-4 py-3 text-left">Luogo</th>
          <th class="px-4 py-3 text-left">Contatto</th>
          <th class="px-4 py-3 text-right">Azioni</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 bg-white">
        <?php if (empty($events)): ?>
          <tr>
            <td colspan="5" class="px-4 py-6 text-center text-slate-500">Nessun evento presente. Crea il primo.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($events as $event): ?>
            <tr class="hover:bg-slate-50">
              <td class="px-4 py-4">
                <div class="font-semibold text-slate-800"><?php echo htmlspecialchars($event['name']); ?></div>
                <div class="text-xs text-slate-500"><?php echo htmlspecialchars($event['scope']); ?></div>
              </td>
              <td class="px-4 py-4 text-sm text-slate-700">
                <?php echo htmlspecialchars($event['startDateTime']); ?>
                <div class="text-xs text-slate-500">&rarr; <?php echo htmlspecialchars($event['endDateTime']); ?></div>
              </td>
              <td class="px-4 py-4 text-sm text-slate-700">
                <div><?php echo htmlspecialchars($event['venue']['name']); ?></div>
                <div class="text-xs text-slate-500"><?php echo htmlspecialchars($event['venue']['address']['city']); ?></div>
              </td>
              <td class="px-4 py-4 text-sm text-slate-700">
                <div><?php echo htmlspecialchars($event['contact']['email']); ?></div>
                <div class="text-xs text-slate-500"><?php echo htmlspecialchars($event['contact']['phone']); ?></div>
              </td>
              <td class="px-4 py-4 text-right text-sm">
                <a href="/admin/event-edit.php?id=<?php echo (int) $event['id']; ?>" class="inline-flex items-center px-3 py-2 rounded-md border border-blue-200 text-blue-700 hover:bg-blue-50">Modifica</a>
                <button type="button" data-delete data-id="<?php echo (int) $event['id']; ?>" data-name="<?php echo htmlspecialchars($event['name']); ?>" class="ml-2 inline-flex items-center px-3 py-2 rounded-md border border-red-200 text-red-700 hover:bg-red-50">Elimina</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="delete-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 hidden">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-md border border-slate-200">
    <div class="px-5 py-4 border-b border-slate-100">
      <h3 class="text-lg font-semibold text-slate-800">Conferma eliminazione</h3>
      <p class="text-sm text-slate-500 mt-1">Sei sicuro di voler eliminare questo evento? Questa azione non è reversibile.</p>
    </div>
    <div class="px-5 py-4">
      <p class="text-sm text-slate-600">Evento: <span id="delete-event-name" class="font-semibold text-slate-800"></span></p>
    </div>
    <div class="px-5 py-4 flex justify-end gap-2 border-t border-slate-100 bg-slate-50">
      <button type="button" data-close-modal class="px-4 py-2 rounded-md border border-slate-200 text-slate-700 hover:bg-white">Annulla</button>
      <form id="delete-form" action="/admin/event-delete.php" method="POST">
        <input type="hidden" name="id" id="delete-event-id" value="">
        <button type="submit" class="px-4 py-2 rounded-md bg-red-600 text-white hover:bg-red-700">Elimina</button>
      </form>
    </div>
  </div>
</div>
<?php render_admin_shell_end(); ?>
