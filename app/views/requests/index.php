<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<!-- Resumen por estado -->
<div class="flex flex-wrap gap-2 mb-5">
    <a href="<?= BASE_URL ?>/requests" class="badge <?= $filters['status'] === '' ? 'bg-brand-500 text-white' : 'bg-white text-slate-600 border border-slate-200' ?> px-3 py-1.5">
        Todas (<?= (int) array_sum($counts) ?>)
    </a>
    <?php foreach (SupportRequest::ESTADOS as $estado): ?>
        <?php $n = (int) ($counts[$estado] ?? 0); if ($n === 0 && $filters['status'] !== $estado) continue; ?>
        <a href="<?= BASE_URL ?>/requests?status=<?= $estado ?>"
           class="badge px-3 py-1.5 <?= $filters['status'] === $estado ? 'bg-brand-500 text-white' : estadoClase($estado) ?>">
            <?= e(str_replace('_', ' ', $estado)) ?> (<?= $n ?>)
        </a>
    <?php endforeach; ?>
</div>

<!-- Filtros -->
<form method="GET" action="<?= BASE_URL ?>/requests" class="bg-white rounded-2xl border border-slate-200 p-4 mb-5">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Folio, asunto o descripción"
               class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 focus:border-transparent outline-none">
        <select name="status" class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-400 outline-none">
            <option value="">Cualquier estado</option>
            <?php foreach (SupportRequest::ESTADOS as $estado): ?>
            <option value="<?= $estado ?>" <?= $filters['status'] === $estado ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $estado)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="urgency" class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-400 outline-none">
            <option value="">Cualquier urgencia</option>
            <?php foreach (SupportRequest::URGENCIAS as $u): ?>
            <option value="<?= $u ?>" <?= $filters['urgency'] === $u ? 'selected' : '' ?>><?= e($u) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="flex gap-2">
            <label class="flex items-center gap-2 text-sm text-slate-600 px-2">
                <input type="checkbox" name="unassigned" value="1" <?= $filters['unassigned'] ? 'checked' : '' ?>
                       class="w-4 h-4 rounded border-slate-300 text-brand-500 focus:ring-brand-400">
                Sin asignar
            </label>
            <button type="submit" class="ml-auto bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                Filtrar
            </button>
        </div>
    </div>
</form>

<!-- Listado -->
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <?php if (empty($requests)): ?>
        <div class="py-16 text-center">
            <i class="fas fa-inbox text-4xl text-slate-200"></i>
            <p class="text-slate-500 mt-4">No hay solicitudes que coincidan con el filtro.</p>
            <?php if ($filters['q'] || $filters['status'] || $filters['urgency'] || $filters['unassigned']): ?>
            <a href="<?= BASE_URL ?>/requests" class="text-brand-600 text-sm hover:underline mt-2 inline-block">Quitar filtros</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full data-table text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr><th>Folio</th><th>Asunto</th><th>Solicitante</th><th>Urgencia</th><th>Estado</th><th>Asesor</th><th>Recibida</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td class="font-mono text-xs text-slate-500 whitespace-nowrap"><?= e($r['folio']) ?></td>
                    <td class="font-medium text-slate-800 max-w-xs truncate"><?= e($r['subject']) ?></td>
                    <td class="text-slate-600"><?= e($r['user_name'] ?? '—') ?></td>
                    <td><?= urgenciaBadge($r['urgency']) ?></td>
                    <td><?= estadoBadge($r['status']) ?></td>
                    <td class="<?= $r['asesor_name'] ? 'text-slate-600' : 'text-amber-600 font-medium' ?>">
                        <?= $r['asesor_name'] ? e($r['asesor_name']) : 'Sin asignar' ?>
                    </td>
                    <td class="text-slate-400 whitespace-nowrap"><?= fechaCorta($r['created_at']) ?></td>
                    <td class="text-right">
                        <a href="<?= BASE_URL ?>/requests/<?= (int) $r['id'] ?>"
                           class="text-brand-600 hover:text-brand-800 font-medium whitespace-nowrap">Abrir &rarr;</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?= paginacion($page, $pages, $filters) ?>
<p class="text-center text-xs text-slate-400 mt-3"><?= (int) $total ?> solicitudes en total</p>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
