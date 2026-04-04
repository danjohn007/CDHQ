<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<div class="flex items-center justify-between mb-6">
    <div class="flex gap-2">
        <a href="<?= $base_url ?>/council" class="text-sm border border-gray-300 hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg">
            ← Organigrama
        </a>
        <a href="<?= $base_url ?>/council/positions" class="text-sm border border-gray-300 hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg">
            Cargos
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- New assignment form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Nueva Asignación</h3>
        <form method="POST" action="<?= $base_url ?>/council/assignments/create">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(Auth::generateCsrf()) ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Cargo <span class="text-red-500">*</span></label>
                <select name="position_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="">Seleccionar cargo…</option>
                    <?php foreach ($positions as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?> (Nivel <?= $p['level'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Afiliado <span class="text-red-500">*</span></label>
                <select name="affiliate_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="">Seleccionar afiliado activo…</option>
                    <?php foreach ($affiliates as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['first_name'].' '.$a['last_name'].' – '.$a['affiliate_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Inicio</label>
                <input type="date" name="start_date" value="<?= date('Y-m-d') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                Crear Asignación
            </button>
        </form>
    </div>

    <!-- Assignments list -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 overflow-x-auto">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Asignaciones</h3>
        <table class="min-w-full text-xs">
            <thead><tr class="text-gray-500 border-b border-gray-100">
                <th class="py-2 text-left">Cargo</th>
                <th class="py-2 text-left">Afiliado</th>
                <th class="py-2 text-left">Inicio</th>
                <th class="py-2 text-left">Estado</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($assignments as $a): ?>
                <tr class="hover:bg-gray-50">
                    <td class="py-2 font-medium text-gray-700"><?= htmlspecialchars($a['position_title']) ?></td>
                    <td class="py-2 text-gray-600"><?= htmlspecialchars($a['affiliate_name']) ?></td>
                    <td class="py-2 text-gray-500"><?= htmlspecialchars($a['start_date']) ?></td>
                    <td class="py-2">
                        <span class="px-1.5 py-0.5 rounded text-xs <?= $a['status']==='ACTIVO' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                            <?= htmlspecialchars($a['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($assignments)): ?>
                <tr><td colspan="4" class="py-6 text-center text-gray-400">Sin asignaciones.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
