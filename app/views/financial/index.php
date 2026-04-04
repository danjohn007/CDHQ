<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<!-- Filters -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">
    <form method="GET" action="<?= $base_url ?>/financial" class="flex flex-wrap gap-2">
        <select name="type" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">Todos los tipos</option>
            <?php foreach (['MEMBRESIA','EVENTO','DONACION','OTRO'] as $t): ?>
            <option value="<?= $t ?>" <?= ($filters['type']==$t)?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>"
               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
        <input type="date" name="date_to"   value="<?= htmlspecialchars($filters['date_to']) ?>"
               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition-colors">Filtrar</button>
    </form>
    <a href="<?= $base_url ?>/financial/create"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors whitespace-nowrap">
        ＋ Registrar Pago
    </a>
</div>

<!-- Summary cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <?php
    $totals = [];
    foreach ($summary as $row) $totals[$row['type']] = $row;
    $types = ['MEMBRESIA','EVENTO','DONACION'];
    foreach ($types as $t):
        $row = $totals[$t] ?? ['total'=>0,'qty'=>0];
    ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider"><?= $t ?></p>
        <p class="text-2xl font-bold text-gray-800 mt-2">$<?= number_format($row['total'], 2) ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= $row['qty'] ?> transacciones</p>
    </div>
    <?php endforeach; ?>
</div>

<!-- Monthly chart -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="text-sm font-semibold text-gray-700 mb-4">Ingresos Mensuales</h3>
    <div class="relative h-48">
        <canvas id="finChart"></canvas>
    </div>
</div>

<!-- Transactions table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Fecha</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Afiliado</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Tipo</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Monto</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Método</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Referencia</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Notas</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($payments as $p): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($p['payment_date']) ?></td>
                <td class="px-4 py-3">
                    <?php if ($p['affiliate_id']): ?>
                    <a href="<?= $base_url ?>/affiliates/<?= $p['affiliate_id'] ?>" class="hover:text-indigo-600 font-medium text-gray-800">
                        <?= htmlspecialchars($p['affiliate_name'] ?? '—') ?>
                    </a>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        <?= $p['type']==='MEMBRESIA' ? 'bg-indigo-100 text-indigo-700' :
                           ($p['type']==='EVENTO'   ? 'bg-blue-100 text-blue-700'   :
                           ($p['type']==='DONACION' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600')) ?>">
                        <?= htmlspecialchars($p['type']) ?>
                    </span>
                </td>
                <td class="px-4 py-3 font-medium text-gray-800">$<?= number_format($p['amount'],2) ?></td>
                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($p['method']) ?></td>
                <td class="px-4 py-3 text-gray-500 text-xs"><?= htmlspecialchars($p['reference'] ?? '') ?></td>
                <td class="px-4 py-3 text-gray-500 text-xs max-w-xs truncate"><?= htmlspecialchars($p['notes'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($payments)): ?>
            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No se encontraron transacciones.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<script>
(function(){
    const labels = <?= json_encode(array_column($monthly, 'month')) ?>;
    const data   = <?= json_encode(array_map(fn($r)=>(float)$r['total'], $monthly)) ?>;
    const ctx = document.getElementById('finChart');
    if (ctx) {
        new Chart(ctx, {
            type:'bar', data:{labels, datasets:[{label:'$', data, backgroundColor:'#6366f1', borderRadius:4}]},
            options:{responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}
        });
    }
})();
</script>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
