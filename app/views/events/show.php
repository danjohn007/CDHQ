<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<?php
$statusColors = [
    'PROGRAMADO'=>'bg-blue-100 text-blue-700',
    'EN_CURSO'  =>'bg-green-100 text-green-700',
    'FINALIZADO'=>'bg-gray-100 text-gray-600',
    'CANCELADO' =>'bg-red-100 text-red-700',
];
$sc = $statusColors[$event['status']] ?? 'bg-gray-100 text-gray-600';
?>

<div class="flex items-start justify-between mb-6 gap-4">
    <div>
        <div class="flex items-center gap-3 mb-1">
            <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($event['name']) ?></h2>
            <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= $sc ?>"><?= htmlspecialchars($event['status']) ?></span>
        </div>
        <div class="flex flex-wrap gap-4 text-sm text-gray-500">
            <span>📅 <?= htmlspecialchars(date('d/m/Y H:i', strtotime($event['date']))) ?></span>
            <span>🏷️ <?= htmlspecialchars($event['type']) ?></span>
            <span>📍 <?= htmlspecialchars($event['venue'] ?? '—') ?></span>
            <?php if ($event['capacity']): ?>
            <span>👥 <?= count($event['registrations']) ?>/<?= (int)$event['capacity'] ?> inscritos</span>
            <?php endif; ?>
        </div>
    </div>
    <a href="<?= $base_url ?>/events" class="text-sm border border-gray-300 hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-lg">← Volver</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left: details + registrations -->
    <div class="lg:col-span-2 space-y-6">
        <?php if ($event['description']): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Descripción</h3>
            <p class="text-sm text-gray-600 whitespace-pre-wrap"><?= htmlspecialchars($event['description']) ?></p>
        </div>
        <?php endif; ?>

        <!-- Attendees -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">
                Lista de Asistentes (<?= count($event['registrations']) ?>)
            </h3>
            <table class="w-full text-sm divide-y divide-gray-100">
                <thead><tr class="text-gray-500 text-xs">
                    <th class="py-2 text-left">Nombre</th>
                    <th class="py-2 text-left">Número</th>
                    <th class="py-2 text-left">Registro</th>
                    <th class="py-2 text-left">Asistió</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($event['registrations'] as $r): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 font-medium text-gray-800"><?= htmlspecialchars($r['affiliate_name']) ?></td>
                        <td class="py-2 text-xs font-mono text-gray-500"><?= htmlspecialchars($r['affiliate_number']) ?></td>
                        <td class="py-2 text-xs text-gray-500"><?= htmlspecialchars($r['registered_at']) ?></td>
                        <td class="py-2">
                            <?php if ($r['attended']): ?>
                            <span class="text-green-600 text-xs font-medium">✓ Sí</span>
                            <?php else: ?>
                            <span class="text-gray-400 text-xs">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($event['registrations'])): ?>
                    <tr><td colspan="4" class="py-6 text-center text-gray-400">Sin asistentes registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right: actions -->
    <div class="space-y-6">
        <!-- Register attendee -->
        <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_COORD_EVENTOS, ROLE_ADMIN_MEMBRESIAS)
               && !in_array($event['status'], ['FINALIZADO','CANCELADO'])): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Registrar Asistente</h3>
            <form method="POST" action="<?= $base_url ?>/events/<?= $event['id'] ?>/register">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(Auth::generateCsrf()) ?>">
                <select name="affiliate_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-3 focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="">Seleccionar afiliado…</option>
                    <?php foreach ($affiliates as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['first_name'].' '.$a['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                    Registrar
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Change status -->
        <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_COORD_EVENTOS)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Cambiar Estado</h3>
            <form method="POST" action="<?= $base_url ?>/events/<?= $event['id'] ?>/status">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(Auth::generateCsrf()) ?>">
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-3 focus:ring-2 focus:ring-indigo-500 outline-none">
                    <?php foreach (['PROGRAMADO','EN_CURSO','FINALIZADO','CANCELADO'] as $s): ?>
                    <option value="<?= $s ?>" <?= $event['status']===$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                    Actualizar Estado
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
