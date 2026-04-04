<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<div class="flex items-center justify-between mb-6">
    <div></div>
    <a href="<?= $base_url ?>/subscriptions/create"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        ＋ Nueva Membresía
    </a>
</div>

<!-- Plans summary -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <?php foreach ($plans as $plan): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($plan['name']) ?></h3>
        <p class="text-3xl font-bold text-indigo-600 mt-2">$<?= number_format($plan['price'], 2) ?></p>
        <p class="text-xs text-gray-500 mt-1"><?= $plan['duration_days'] ?> días de vigencia</p>
        <p class="text-xs text-gray-400 mt-2"><?= htmlspecialchars($plan['description'] ?? '') ?></p>
    </div>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700">Membresías Registradas</h3>
    </div>
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Afiliado</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Número</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Plan</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Inicio</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Vence</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($subs as $s): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800">
                    <a href="<?= $base_url ?>/affiliates/<?= htmlspecialchars($s['affiliate_id'] ?? '') ?>"
                       class="hover:text-indigo-600"><?= htmlspecialchars($s['affiliate_name']) ?></a>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-gray-500"><?= htmlspecialchars($s['affiliate_number']) ?></td>
                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($s['plan_name']) ?></td>
                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($s['start_date']) ?></td>
                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($s['end_date']) ?></td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        <?= $s['status']==='ACTIVA' ? 'bg-green-100 text-green-700' :
                           ($s['status']==='VENCIDA'  ? 'bg-red-100 text-red-700'   : 'bg-yellow-100 text-yellow-700') ?>">
                        <?= htmlspecialchars($s['status']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($subs)): ?>
            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No hay membresías registradas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
