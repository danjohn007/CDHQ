<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div class="flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>/users" class="badge px-3 py-1.5 <?= $filters['app_role'] === '' ? 'bg-brand-500 text-white' : 'bg-white text-slate-600 border border-slate-200' ?>">
            Todos
        </a>
        <?php foreach (ALL_APP_ROLES as $rol): ?>
        <a href="<?= BASE_URL ?>/users?app_role=<?= $rol ?>"
           class="badge px-3 py-1.5 <?= $filters['app_role'] === $rol ? 'bg-brand-500 text-white' : 'bg-white text-slate-600 border border-slate-200' ?>">
            <?= e($rol) ?> (<?= (int) ($counts[$rol] ?? 0) ?>)
        </a>
        <?php endforeach; ?>
    </div>
    <a href="<?= BASE_URL ?>/users/create" class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
        <i class="fas fa-plus text-xs mr-1"></i> Nuevo usuario
    </a>
</div>

<form method="GET" action="<?= BASE_URL ?>/users" class="bg-white rounded-2xl border border-slate-200 p-4 mb-5">
    <div class="flex flex-wrap gap-3">
        <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Nombre o correo"
               class="flex-1 min-w-[220px] px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 outline-none">
        <select name="app_role" class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
            <option value="">Cualquier rol</option>
            <?php foreach (ALL_APP_ROLES as $rol): ?>
            <option value="<?= $rol ?>" <?= $filters['app_role'] === $rol ? 'selected' : '' ?>><?= e($rol) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="active" class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
            <option value="">Activos e inactivos</option>
            <option value="1" <?= $filters['active'] === '1' ? 'selected' : '' ?>>Sólo activos</option>
            <option value="0" <?= $filters['active'] === '0' ? 'selected' : '' ?>>Sólo inactivos</option>
        </select>
        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Filtrar</button>
    </div>
</form>

<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <?php if (empty($users)): ?>
        <div class="py-16 text-center">
            <i class="fas fa-user-shield text-4xl text-slate-200"></i>
            <p class="text-slate-500 mt-4">No hay usuarios que coincidan.</p>
        </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full data-table text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr><th>Nombre</th><th>Rol en la app</th><th>Estado</th><th>Afiliación</th><th>Solicitudes</th><th>Última actividad</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <p class="font-medium text-slate-800"><?= e($u['name']) ?></p>
                        <p class="text-xs text-slate-400"><?= e($u['email']) ?></p>
                    </td>
                    <td>
                        <span class="badge <?= $u['app_role'] === APP_ROLE_ADMINISTRADOR ? 'bg-brand-100 text-brand-800' : ($u['app_role'] === APP_ROLE_ASESOR ? 'bg-leaf-50 text-leaf-700 border border-leaf-200' : 'bg-slate-100 text-slate-600') ?>">
                            <?= e($u['app_role']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $u['active'] ? 'bg-leaf-50 text-leaf-700 border border-leaf-200' : 'bg-slate-100 text-slate-500' ?>">
                            <?= $u['active'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td class="text-slate-600"><?= (int) $u['affiliate_count'] ? 'Sí' : '—' ?></td>
                    <td class="text-slate-600"><?= (int) $u['request_count'] ?></td>
                    <td class="text-slate-400 whitespace-nowrap"><?= fechaCorta($u['last_activity']) ?></td>
                    <td class="text-right whitespace-nowrap">
                        <a href="<?= BASE_URL ?>/users/<?= (int) $u['id'] ?>/edit" class="text-brand-600 hover:text-brand-800 font-medium mr-3">Editar</a>
                        <form method="POST" action="<?= BASE_URL ?>/users/<?= (int) $u['id'] ?>/toggle" class="inline"
                              onsubmit="return confirm('¿Cambiar el estado de esta cuenta?');">
                            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(Auth::generateCsrf()) ?>">
                            <button type="submit" class="text-slate-500 hover:text-slate-800 font-medium">
                                <?= $u['active'] ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?= paginacion($page, $pages, $filters) ?>
<p class="text-center text-xs text-slate-400 mt-3"><?= (int) $total ?> usuarios en total</p>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
