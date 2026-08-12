<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<div class="flex flex-wrap gap-2 mb-5">
    <a href="<?= BASE_URL ?>/applications" class="badge px-3 py-1.5 <?= $filters['status'] === '' ? 'bg-brand-500 text-white' : 'bg-white text-slate-600 border border-slate-200' ?>">
        Todas (<?= (int) array_sum($counts) ?>)
    </a>
    <?php foreach (DefenderApplication::ESTADOS as $estado): ?>
        <?php $n = (int) ($counts[$estado] ?? 0); if ($n === 0 && $filters['status'] !== $estado) continue; ?>
        <a href="<?= BASE_URL ?>/applications?status=<?= $estado ?>"
           class="badge px-3 py-1.5 <?= $filters['status'] === $estado ? 'bg-brand-500 text-white' : estadoClase($estado) ?>">
            <?= e(str_replace('_', ' ', $estado)) ?> (<?= $n ?>)
        </a>
    <?php endforeach; ?>
</div>

<form method="GET" action="<?= BASE_URL ?>/applications" class="bg-white rounded-2xl border border-slate-200 p-4 mb-5">
    <div class="flex flex-wrap gap-3">
        <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Nombre o correo del postulante"
               class="flex-1 min-w-[220px] px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 outline-none">
        <select name="status" class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
            <option value="">Cualquier estado</option>
            <?php foreach (DefenderApplication::ESTADOS as $estado): ?>
            <option value="<?= $estado ?>" <?= $filters['status'] === $estado ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $estado)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Filtrar</button>
    </div>
</form>

<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <?php if (empty($applications)): ?>
        <div class="py-16 text-center">
            <i class="fas fa-user-check text-4xl text-slate-200"></i>
            <p class="text-slate-500 mt-4">No hay postulaciones que coincidan.</p>
        </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full data-table text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr><th>Postulante</th><th>Folio afiliado</th><th>Estado</th><th>Docs.</th><th>Enviada</th><th>Revisó</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($applications as $a): ?>
                <tr>
                    <td>
                        <p class="font-medium text-slate-800"><?= e($a['user_name'] ?? '—') ?></p>
                        <p class="text-xs text-slate-400"><?= e($a['user_email'] ?? '') ?></p>
                    </td>
                    <td class="font-mono text-xs text-slate-500"><?= e($a['affiliate_number'] ?? '—') ?></td>
                    <td><?= estadoBadge($a['status']) ?></td>
                    <td class="text-slate-600"><?= (int) $a['doc_count'] ?></td>
                    <td class="text-slate-400 whitespace-nowrap"><?= fechaCorta($a['submitted_at']) ?></td>
                    <td class="text-slate-600"><?= e($a['reviewer_name'] ?? '—') ?></td>
                    <td class="text-right">
                        <a href="<?= BASE_URL ?>/applications/<?= (int) $a['id'] ?>" class="text-brand-600 hover:text-brand-800 font-medium whitespace-nowrap">Revisar &rarr;</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?= paginacion($page, $pages, $filters) ?>
<p class="text-center text-xs text-slate-400 mt-3"><?= (int) $total ?> postulaciones en total</p>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
