<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<form method="GET" action="<?= BASE_URL ?>/credentials" class="bg-white rounded-2xl border border-slate-200 p-4 mb-5">
    <div class="flex flex-wrap gap-3">
        <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Folio, número de credencial o nombre"
               class="flex-1 min-w-[240px] px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 outline-none">
        <select name="status" class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
            <option value="">Cualquier estado</option>
            <?php foreach (Credential::ESTADOS as $estado): ?>
            <option value="<?= $estado ?>" <?= $filters['status'] === $estado ? 'selected' : '' ?>><?= e($estado) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Filtrar</button>
    </div>
</form>

<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <?php if (empty($credentials)): ?>
        <div class="py-16 text-center">
            <i class="fas fa-address-card text-4xl text-slate-200"></i>
            <p class="text-slate-500 mt-4">No hay credenciales que coincidan.</p>
        </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full data-table text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr><th>Credencial</th><th>Afiliado</th><th>Estado</th><th>Emitida</th><th>Expira</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($credentials as $c): ?>
                <?php $porVencer = $c['status'] === 'VIGENTE' && strtotime($c['expires_at']) < strtotime('+30 days'); ?>
                <tr>
                    <td class="font-mono text-xs text-slate-500 whitespace-nowrap"><?= e($c['credential_number']) ?></td>
                    <td>
                        <p class="text-slate-800"><?= e($c['affiliate_name']) ?></p>
                        <p class="text-xs text-slate-400 font-mono"><?= e($c['affiliate_number']) ?></p>
                    </td>
                    <td>
                        <?= estadoBadge($c['status']) ?>
                        <?php if (!empty($c['status_reason'])): ?>
                        <p class="text-xs text-slate-400 mt-1 max-w-[16rem] truncate" title="<?= e($c['status_reason']) ?>"><?= e($c['status_reason']) ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="text-slate-400 whitespace-nowrap"><?= fechaCorta($c['issued_at']) ?></td>
                    <td class="whitespace-nowrap <?= $porVencer ? 'text-amber-600 font-medium' : 'text-slate-400' ?>">
                        <?= fechaCorta($c['expires_at']) ?>
                    </td>
                    <td class="text-right whitespace-nowrap">
                        <a href="<?= BASE_URL ?>/credentials/<?= (int) $c['id'] ?>" class="text-brand-600 hover:text-brand-800 font-medium mr-3">Ver</a>
                        <a href="<?= BASE_URL ?>/verificar/<?= e($c['verification_token']) ?>" target="_blank" rel="noopener"
                           class="text-slate-500 hover:text-slate-800 font-medium">Verificación pública</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?= paginacion($page, $pages, $filters) ?>
<p class="text-center text-xs text-slate-400 mt-3"><?= (int) $total ?> credenciales en total</p>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
