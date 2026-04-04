<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<!-- Toolbar -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">
    <form method="GET" action="<?= $base_url ?>/affiliates" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
               placeholder="Buscar afiliado…"
               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none w-56">
        <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">Todos los estados</option>
            <?php foreach (['PROSPECTO','ACTIVO','SUSPENDIDO','VENCIDO'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition-colors">Filtrar</button>
        <?php if ($search || $status): ?>
        <a href="<?= $base_url ?>/affiliates" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2">✕ Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS)): ?>
    <a href="<?= $base_url ?>/affiliates/create"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors whitespace-nowrap">
        ＋ Nuevo Afiliado
    </a>
    <?php endif; ?>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 text-sm text-gray-500">
        <?= number_format($total) ?> afiliados encontrados
    </div>
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Número</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Correo</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Teléfono</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Membresía</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($affiliates as $a): ?>
            <?php
                $stateColors = [
                    'ACTIVO'    => 'bg-green-100 text-green-700',
                    'SUSPENDIDO'=> 'bg-yellow-100 text-yellow-700',
                    'VENCIDO'   => 'bg-red-100 text-red-700',
                    'PROSPECTO' => 'bg-blue-100 text-blue-700',
                ];
                $sc = $stateColors[$a['state']] ?? 'bg-gray-100 text-gray-600';
            ?>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-mono text-xs text-gray-600"><?= htmlspecialchars($a['affiliate_number']) ?></td>
                <td class="px-4 py-3 font-medium text-gray-800">
                    <a href="<?= $base_url ?>/affiliates/<?= $a['id'] ?>" class="hover:text-indigo-600">
                        <?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?>
                    </a>
                </td>
                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($a['email'] ?? '') ?></td>
                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($a['phone'] ?? '') ?></td>
                <td class="px-4 py-3">
                    <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium <?= $sc ?>">
                        <?= htmlspecialchars($a['state']) ?>
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-gray-500">
                    <?= $a['sub_end'] ? 'Hasta ' . htmlspecialchars($a['sub_end']) : '—' ?>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <a href="<?= $base_url ?>/affiliates/<?= $a['id'] ?>" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Ver</a>
                        <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS)): ?>
                        <a href="<?= $base_url ?>/affiliates/<?= $a['id'] ?>/edit" class="text-gray-600 hover:text-gray-800 text-xs font-medium">Editar</a>
                        <?php endif; ?>
                        <a href="<?= $base_url ?>/credentials/<?= $a['id'] ?>" class="text-purple-600 hover:text-purple-800 text-xs font-medium">Credencial</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($affiliates)): ?>
            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No se encontraron afiliados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="px-4 py-3 border-t border-gray-100 flex items-center gap-2 flex-wrap">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a href="<?= $base_url ?>/affiliates?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"
           class="px-3 py-1 rounded text-sm <?= $p === $page ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
            <?= $p ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
