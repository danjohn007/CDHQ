<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<!-- Date range filter -->
<div class="flex items-center justify-between mb-6">
    <form method="GET" action="<?= $base_url ?>/reports" class="flex gap-2 items-center flex-wrap">
        <label class="text-sm text-gray-600">Del:</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>"
               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
        <label class="text-sm text-gray-600">Al:</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>"
               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition-colors">Actualizar</button>
    </form>
</div>

<!-- Tabs (accessible without JS) -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Affiliates by State -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Afiliados por Estado</h3>
        <div class="relative h-52 mb-4">
            <canvas id="affChart"></canvas>
        </div>
        <table class="w-full text-sm">
            <thead><tr class="text-gray-500 text-xs border-b border-gray-100">
                <th class="py-2 text-left">Estado</th>
                <th class="py-2 text-right">Total</th>
            </tr></thead>
            <tbody>
                <?php foreach ($affiliatesByState as $row): ?>
                <tr class="border-b border-gray-50">
                    <td class="py-2 text-gray-700"><?= htmlspecialchars($row['state']) ?></td>
                    <td class="py-2 text-right font-medium"><?= number_format($row['total']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Income by Type -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Ingresos por Tipo (período seleccionado)</h3>
        <div class="relative h-52 mb-4">
            <canvas id="incomeTypeChart"></canvas>
        </div>
        <table class="w-full text-sm">
            <thead><tr class="text-gray-500 text-xs border-b border-gray-100">
                <th class="py-2 text-left">Tipo</th>
                <th class="py-2 text-right">Cantidad</th>
                <th class="py-2 text-right">Total</th>
            </tr></thead>
            <tbody>
                <?php foreach ($incomeByType as $row): ?>
                <tr class="border-b border-gray-50">
                    <td class="py-2 text-gray-700"><?= htmlspecialchars($row['type']) ?></td>
                    <td class="py-2 text-right text-gray-600"><?= number_format($row['qty']) ?></td>
                    <td class="py-2 text-right font-medium">$<?= number_format($row['total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($incomeByType)): ?>
                <tr><td colspan="3" class="py-4 text-center text-gray-400">Sin datos.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Monthly income trend -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Tendencia de Ingresos (12 meses)</h3>
        <div class="relative h-52">
            <canvas id="monthChart"></canvas>
        </div>
    </div>

    <!-- Prospect Conversion -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Conversión de Prospectos</h3>
        <div class="relative h-52 mb-4">
            <canvas id="prospectChart"></canvas>
        </div>
        <table class="w-full text-sm">
            <thead><tr class="text-gray-500 text-xs border-b border-gray-100">
                <th class="py-2 text-left">Estado</th>
                <th class="py-2 text-right">Total</th>
            </tr></thead>
            <tbody>
                <?php foreach ($prospectStats as $row): ?>
                <tr class="border-b border-gray-50">
                    <td class="py-2 text-gray-700"><?= htmlspecialchars($row['state']) ?></td>
                    <td class="py-2 text-right font-medium"><?= number_format($row['total']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Events Participation -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 lg:col-span-2">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Participación en Eventos Finalizados</h3>
        <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-gray-500 text-xs">
                    <th class="px-3 py-2 text-left">Evento</th>
                    <th class="px-3 py-2 text-left">Fecha</th>
                    <th class="px-3 py-2 text-right">Inscritos</th>
                    <th class="px-3 py-2 text-right">Asistieron</th>
                    <th class="px-3 py-2 text-right">Asistencia %</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($eventStats as $row): ?>
                <?php $pct = $row['registered'] > 0 ? round($row['attended']/$row['registered']*100) : 0; ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-medium text-gray-800"><?= htmlspecialchars($row['name']) ?></td>
                    <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($row['date']) ?></td>
                    <td class="px-3 py-2 text-right"><?= $row['registered'] ?></td>
                    <td class="px-3 py-2 text-right"><?= $row['attended'] ?></td>
                    <td class="px-3 py-2 text-right">
                        <span class="<?= $pct>=75?'text-green-600':($pct>=50?'text-yellow-600':'text-red-600') ?> font-medium">
                            <?= $pct ?>%
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($eventStats)): ?>
                <tr><td colspan="5" class="px-3 py-6 text-center text-gray-400">Sin datos de eventos.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script>
<?php
$stateColors = ['ACTIVO'=>'#22c55e','PROSPECTO'=>'#3b82f6','SUSPENDIDO'=>'#f59e0b','VENCIDO'=>'#ef4444'];
$affLabels   = array_column($affiliatesByState, 'state');
$affData     = array_column($affiliatesByState, 'total');
$affColors   = array_map(fn($l) => $stateColors[$l] ?? '#94a3b8', $affLabels);
?>
// Affiliates by state
(function(){
    const ctx = document.getElementById('affChart');
    if (!ctx) return;
    new Chart(ctx, {type:'doughnut', data:{
        labels: <?= json_encode($affLabels, JSON_UNESCAPED_UNICODE) ?>,
        datasets:[{data:<?= json_encode(array_map('intval',$affData)) ?>,backgroundColor:<?= json_encode($affColors) ?>,borderWidth:2}]
    }, options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{font:{size:10}}}}}});
})();

// Income by type
(function(){
    const ctx = document.getElementById('incomeTypeChart');
    if (!ctx) return;
    const labels = <?= json_encode(array_column($incomeByType, 'type'), JSON_UNESCAPED_UNICODE) ?>;
    const data   = <?= json_encode(array_map(fn($r)=>(float)$r['total'], $incomeByType)) ?>;
    new Chart(ctx, {type:'bar', data:{labels, datasets:[{label:'$',data,backgroundColor:'#6366f1',borderRadius:4}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
})();

// Monthly income
(function(){
    const ctx = document.getElementById('monthChart');
    if (!ctx) return;
    const labels = <?= json_encode(array_column($monthlyIncome, 'month')) ?>;
    const data   = <?= json_encode(array_map(fn($r)=>(float)$r['total'], $monthlyIncome)) ?>;
    new Chart(ctx, {type:'line', data:{labels, datasets:[{label:'Ingresos',data,borderColor:'#6366f1',backgroundColor:'rgba(99,102,241,0.1)',fill:true,tension:0.4}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
})();

// Prospects
(function(){
    const ctx = document.getElementById('prospectChart');
    if (!ctx) return;
    const labels = <?= json_encode(array_column($prospectStats,'state'), JSON_UNESCAPED_UNICODE) ?>;
    const data   = <?= json_encode(array_column($prospectStats,'total')) ?>;
    const colors = labels.map(l=>({NUEVO:'#3b82f6',CONTACTADO:'#f59e0b',SEGUIMIENTO:'#f97316',CONVERTIDO:'#22c55e'}[l]||'#94a3b8'));
    new Chart(ctx, {type:'doughnut', data:{labels,datasets:[{data,backgroundColor:colors,borderWidth:2}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{font:{size:10}}}}}});
})();
</script>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
