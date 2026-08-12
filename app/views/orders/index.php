<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<div class="grid sm:grid-cols-3 gap-4 mb-5">
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <p class="text-slate-500 text-xs uppercase tracking-wide">Cobrado (30 días)</p>
        <p class="text-2xl font-extrabold text-slate-800 mt-1"><?= e(dinero($collected)) ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <p class="text-slate-500 text-xs uppercase tracking-wide">Órdenes pendientes</p>
        <p class="text-2xl font-extrabold text-slate-800 mt-1"><?= (int) ($counts['PENDIENTE'] ?? 0) ?></p>
    </div>
    <div class="bg-white rounded-2xl border <?= $expired > 0 ? 'border-amber-300' : 'border-slate-200' ?> p-5">
        <p class="text-slate-500 text-xs uppercase tracking-wide">Pendientes vencidas</p>
        <p class="text-2xl font-extrabold <?= $expired > 0 ? 'text-amber-600' : 'text-slate-800' ?> mt-1"><?= (int) $expired ?></p>
    </div>
</div>

<div class="bg-brand-50 border border-brand-100 rounded-2xl px-5 py-4 mb-5 flex gap-3 text-sm text-brand-900">
    <i class="fas fa-lock text-brand-500 mt-0.5"></i>
    <p class="leading-relaxed">
        Este módulo es de <strong>sólo consulta</strong>. Un pago únicamente se confirma mediante el webhook firmado de
        Stripe; no existe forma de marcar una orden como pagada desde el portal.
    </p>
</div>

<form method="GET" action="<?= BASE_URL ?>/orders" class="bg-white rounded-2xl border border-slate-200 p-4 mb-5">
    <div class="flex flex-wrap gap-3">
        <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Folio o Payment Intent"
               class="flex-1 min-w-[220px] px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 outline-none">
        <select name="status" class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
            <option value="">Cualquier estado</option>
            <?php foreach (PaymentOrder::ESTADOS as $estado): ?>
            <option value="<?= $estado ?>" <?= $filters['status'] === $estado ? 'selected' : '' ?>>
                <?= e(str_replace('_', ' ', $estado)) ?> (<?= (int) ($counts[$estado] ?? 0) ?>)
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Filtrar</button>
    </div>
</form>

<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <?php if (empty($orders)): ?>
        <div class="py-16 text-center">
            <i class="fas fa-receipt text-4xl text-slate-200"></i>
            <p class="text-slate-500 mt-4">No hay órdenes que coincidan.</p>
        </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full data-table text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr><th>Folio</th><th>Afiliado</th><th>Plan</th><th>Importe</th><th>Estado</th><th>Creada</th><th>Pagada</th><th>Stripe</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($orders as $o): ?>
                <?php
                $vencida = $o['status'] === 'PENDIENTE'
                        && !empty($o['expires_at'])
                        && strtotime($o['expires_at']) < time();
                ?>
                <tr class="<?= $vencida ? 'bg-amber-50/50' : '' ?>">
                    <td class="font-mono text-xs text-slate-500 whitespace-nowrap"><?= e($o['folio']) ?></td>
                    <td>
                        <p class="text-slate-800"><?= e($o['affiliate_name'] ?? '—') ?></p>
                        <p class="text-xs text-slate-400 font-mono"><?= e($o['affiliate_number'] ?? '') ?></p>
                    </td>
                    <td class="text-slate-600"><?= e($o['plan_name'] ?? '—') ?></td>
                    <td class="font-semibold text-slate-800 whitespace-nowrap">
                        <?= e(dinero($o['amount_snapshot'])) ?>
                        <span class="block text-xs font-normal text-slate-400"><?= (int) $o['duration_days_snapshot'] ?> días</span>
                    </td>
                    <td>
                        <?= estadoBadge($o['status']) ?>
                        <?php if ($vencida): ?><span class="block text-xs text-amber-600 mt-1">Expiró</span><?php endif; ?>
                    </td>
                    <td class="text-slate-400 whitespace-nowrap"><?= fechaCorta($o['created_at']) ?></td>
                    <td class="text-slate-400 whitespace-nowrap"><?= fechaCorta($o['paid_at']) ?></td>
                    <td class="text-xs">
                        <?php if (!empty($o['provider_payment_intent_id'])): ?>
                        <a href="https://dashboard.stripe.com/payments/<?= e($o['provider_payment_intent_id']) ?>"
                           target="_blank" rel="noopener noreferrer"
                           class="text-brand-600 hover:underline font-mono break-all">
                            <?= e(substr($o['provider_payment_intent_id'], 0, 18)) ?>…
                        </a>
                        <?php else: ?>
                        <span class="text-slate-300">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?= paginacion($page, $pages, $filters) ?>
<p class="text-center text-xs text-slate-400 mt-3"><?= (int) $total ?> órdenes en total</p>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
