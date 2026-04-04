<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<?php
// Prepare chart data
$stateLabels  = [];
$stateData    = [];
$stateColors  = ['ACTIVO'=>'#22c55e','PROSPECTO'=>'#3b82f6','SUSPENDIDO'=>'#f59e0b','VENCIDO'=>'#ef4444'];
foreach ($affiliatesByState as $row) {
    $stateLabels[] = $row['state'];
    $stateData[]   = (int)$row['total'];
}

$incomeLabels = [];
$incomeData   = [];
foreach ($monthlyIncome as $row) {
    $incomeLabels[] = $row['month'];
    $incomeData[]   = (float)$row['total'];
}
?>

<!-- Stats Cards -->
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
    <?php
    $cards = [
        ['label'=>'Total Afiliados',   'value'=>$stats['total_affiliates'],     'icon'=>'👥', 'color'=>'indigo'],
        ['label'=>'Afiliados Activos', 'value'=>$stats['active_affiliates'],    'icon'=>'✅', 'color'=>'green'],
        ['label'=>'Membresías Activas','value'=>$stats['active_subscriptions'], 'icon'=>'🪪', 'color'=>'blue'],
        ['label'=>'Eventos Próximos',  'value'=>$stats['upcoming_events'],      'icon'=>'📅', 'color'=>'purple'],
        ['label'=>'Pagos Pendientes',  'value'=>$stats['pending_payments'],     'icon'=>'💳', 'color'=>'yellow'],
        ['label'=>'Nuevos Prospectos', 'value'=>$stats['new_prospects'],        'icon'=>'🔍', 'color'=>'pink'],
    ];
    foreach ($cards as $card):
    ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <span class="text-2xl"><?= $card['icon'] ?></span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?= number_format($card['value']) ?></p>
        <p class="text-xs text-gray-500 font-medium"><?= htmlspecialchars($card['label']) ?></p>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Affiliates by State -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Afiliados por Estado</h2>
        <div class="relative h-56">
            <canvas id="stateChart"></canvas>
        </div>
    </div>

    <!-- Monthly Income -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Ingresos Mensuales (últimos 12 meses)</h2>
        <div class="relative h-56">
            <canvas id="incomeChart"></canvas>
        </div>
    </div>
</div>

<!-- Upcoming Events & Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Upcoming Events -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-700">Próximos Eventos</h2>
            <a href="<?= $base_url ?>/events" class="text-xs text-indigo-600 hover:underline">Ver todos</a>
        </div>
        <?php if (empty($upcomingEvents)): ?>
            <p class="text-gray-400 text-sm">No hay eventos próximos.</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($upcomingEvents as $ev): ?>
            <div class="flex items-start gap-3 p-3 rounded-lg bg-gray-50">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex flex-col items-center justify-center flex-shrink-0">
                    <span class="text-xs font-bold text-indigo-700"><?= date('d', strtotime($ev['date'])) ?></span>
                    <span class="text-xs text-indigo-500"><?= date('M', strtotime($ev['date'])) ?></span>
                </div>
                <div class="min-w-0">
                    <a href="<?= $base_url ?>/events/<?= $ev['id'] ?>"
                       class="text-sm font-medium text-gray-800 hover:text-indigo-600 block truncate">
                        <?= htmlspecialchars($ev['name']) ?>
                    </a>
                    <p class="text-xs text-gray-500"><?= htmlspecialchars($ev['type']) ?> · <?= htmlspecialchars($ev['venue'] ?? '') ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Actividad Reciente</h2>
        <?php if (empty($recentActivity)): ?>
            <p class="text-gray-400 text-sm">Sin actividad reciente.</p>
        <?php else: ?>
        <div class="space-y-3 max-h-72 overflow-y-auto">
            <?php foreach ($recentActivity as $log): ?>
            <div class="flex items-start gap-3 text-sm">
                <span class="w-2 h-2 rounded-full bg-indigo-400 mt-1.5 flex-shrink-0"></span>
                <div class="min-w-0">
                    <span class="font-medium text-gray-700"><?= htmlspecialchars($log['user_name'] ?? 'Sistema') ?></span>
                    <span class="text-gray-500"> – <?= htmlspecialchars($log['action']) ?>
                        <?= htmlspecialchars($log['entity']) ?></span>
                    <?php if ($log['details']): ?>
                    <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($log['details']) ?></p>
                    <?php endif; ?>
                    <p class="text-xs text-gray-300"><?= htmlspecialchars($log['created_at']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Affiliates by state - Doughnut
(function() {
    const labels = <?= json_encode($stateLabels, JSON_UNESCAPED_UNICODE) ?>;
    const data   = <?= json_encode($stateData) ?>;
    const colorMap = {ACTIVO:'#22c55e',PROSPECTO:'#3b82f6',SUSPENDIDO:'#f59e0b',VENCIDO:'#ef4444'};
    const colors = labels.map(l => colorMap[l] || '#94a3b8');
    const ctx = document.getElementById('stateChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 2 }] },
            options: { responsive: true, maintainAspectRatio: false,
                       plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
        });
    }
})();

// Monthly income - Bar
(function() {
    const labels = <?= json_encode($incomeLabels) ?>;
    const data   = <?= json_encode($incomeData) ?>;
    const ctx = document.getElementById('incomeChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets: [{
                label: 'Ingresos $', data,
                backgroundColor: '#6366f1', borderRadius: 6,
            }]},
            options: { responsive: true, maintainAspectRatio: false,
                       scales: { y: { beginAtZero: true } },
                       plugins: { legend: { display: false } } }
        });
    }
})();
</script>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
