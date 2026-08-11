<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<div class="bg-slate-100 border border-slate-200 rounded-2xl px-5 py-4 mb-5 flex gap-3 text-sm text-slate-700">
    <i class="fas fa-shield-halved text-slate-400 mt-0.5"></i>
    <p class="leading-relaxed">
        La bitácora es de <strong>sólo lectura</strong>: no puede editarse ni borrarse desde el portal. Registra las
        acciones administrativas junto con quién las hizo, cuándo y desde qué dirección IP.
    </p>
</div>

<form method="GET" action="<?= BASE_URL ?>/audit" class="bg-white rounded-2xl border border-slate-200 p-4 mb-5">
    <div class="flex flex-wrap gap-3">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar en detalle, entidad o persona"
               class="flex-1 min-w-[240px] px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 outline-none">
        <select name="action" class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
            <option value="">Cualquier acción</option>
            <?php foreach ($actions as $a): ?>
            <option value="<?= e($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $a)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Filtrar</button>
    </div>
</form>

<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <?php if (empty($entries)): ?>
        <div class="py-16 text-center">
            <i class="fas fa-clipboard-list text-4xl text-slate-200"></i>
            <p class="text-slate-500 mt-4">Sin registros que coincidan.</p>
        </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full data-table text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr><th>Fecha</th><th>Persona</th><th>Acción</th><th>Entidad</th><th>Detalle</th><th>IP</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($entries as $en): ?>
                <tr>
                    <td class="text-slate-400 whitespace-nowrap"><?= fechaHora($en['created_at']) ?></td>
                    <td>
                        <p class="text-slate-800"><?= e($en['user_name'] ?? 'Sistema') ?></p>
                        <?php if (!empty($en['user_email'])): ?>
                        <p class="text-xs text-slate-400"><?= e($en['user_email']) ?></p>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-slate-100 text-slate-600"><?= e(str_replace('_', ' ', $en['action'])) ?></span></td>
                    <td class="text-slate-500 text-xs">
                        <?= e($en['entity']) ?><?= $en['entity_id'] ? ' #' . (int) $en['entity_id'] : '' ?>
                    </td>
                    <td class="text-slate-600 max-w-lg"><?= e($en['details']) ?></td>
                    <td class="text-slate-400 text-xs font-mono"><?= e($en['ip']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?= paginacion($page, $pages, ['q' => $q, 'action' => $action]) ?>
<p class="text-center text-xs text-slate-400 mt-3"><?= (int) $total ?> registros en total</p>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
