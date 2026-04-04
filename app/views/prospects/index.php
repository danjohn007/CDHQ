<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<div class="flex items-center justify-between mb-6">
    <form method="GET" action="<?= $base_url ?>/prospects" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar prospecto…"
               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none w-48">
        <select name="state" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">Todos</option>
            <?php foreach (['NUEVO','CONTACTADO','SEGUIMIENTO','CONVERTIDO'] as $s): ?>
            <option value="<?= $s ?>" <?= $state==$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">Filtrar</button>
    </form>
    <a href="<?= $base_url ?>/prospects/create"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors whitespace-nowrap">
        ＋ Nuevo Prospecto
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Correo</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Teléfono</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Origen</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Asignado a</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php
            $pColors = [
                'NUEVO'      =>'bg-blue-100 text-blue-700',
                'CONTACTADO' =>'bg-yellow-100 text-yellow-700',
                'SEGUIMIENTO'=>'bg-orange-100 text-orange-700',
                'CONVERTIDO' =>'bg-green-100 text-green-700',
            ];
            foreach ($prospects as $p):
                $pc = $pColors[$p['state']] ?? 'bg-gray-100 text-gray-600';
            ?>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($p['name']) ?></td>
                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($p['email'] ?? '—') ?></td>
                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($p['phone'] ?? '—') ?></td>
                <td class="px-4 py-3 text-gray-500 text-xs"><?= htmlspecialchars($p['source'] ?? '—') ?></td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= $pc ?>"><?= htmlspecialchars($p['state']) ?></span>
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs"><?= htmlspecialchars($p['assigned_name'] ?? '—') ?></td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <a href="<?= $base_url ?>/prospects/<?= $p['id'] ?>/edit" class="text-indigo-600 text-xs font-medium hover:text-indigo-800">Editar</a>
                        <?php if ($p['state'] !== 'CONVERTIDO' && Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS)): ?>
                        <form method="POST" action="<?= $base_url ?>/prospects/<?= $p['id'] ?>/convert"
                              onsubmit="return confirm('¿Convertir a afiliado?')">
                            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(Auth::generateCsrf()) ?>">
                            <button type="submit" class="text-green-600 text-xs font-medium hover:text-green-800">Convertir</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($prospects)): ?>
            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No se encontraron prospectos.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
