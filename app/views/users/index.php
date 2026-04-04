<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<div class="flex items-center justify-between mb-6">
    <div></div>
    <a href="<?= $base_url ?>/users/create"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        ＋ Nuevo Usuario
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Correo</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Rol</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Afiliados</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($users as $user): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($user['name']) ?></td>
                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($user['email']) ?></td>
                <td class="px-4 py-3">
                    <span class="inline-block bg-indigo-100 text-indigo-700 text-xs px-2 py-0.5 rounded-full font-medium">
                        <?= htmlspecialchars($user['role']) ?>
                    </span>
                </td>
                <td class="px-4 py-3">
                    <?php if ($user['active']): ?>
                    <span class="inline-block bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-medium">Activo</span>
                    <?php else: ?>
                    <span class="inline-block bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full font-medium">Inactivo</span>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-gray-600"><?= (int)$user['affiliate_count'] ?></td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <a href="<?= $base_url ?>/users/<?= $user['id'] ?>/edit"
                           class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Editar</a>
                        <form method="POST" action="<?= $base_url ?>/users/<?= $user['id'] ?>/toggle" class="inline">
                            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(Auth::generateCsrf()) ?>">
                            <button type="submit" class="text-yellow-600 hover:text-yellow-800 text-xs font-medium">
                                <?= $user['active'] ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>
                        <?php if ($user['id'] !== ($current_user['id'] ?? 0)): ?>
                        <form method="POST" action="<?= $base_url ?>/users/<?= $user['id'] ?>/delete" class="inline"
                              onsubmit="return confirm('¿Eliminar este usuario?')">
                            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(Auth::generateCsrf()) ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Eliminar</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No hay usuarios.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
