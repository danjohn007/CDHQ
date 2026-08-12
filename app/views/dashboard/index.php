<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<?php
$tarjetas = [
    ['Solicitudes abiertas',   $stats['solicitudes_abiertas'],   'fa-inbox',        'requests',     'brand', $stats['solicitudes_sin_asignar'] . ' sin asignar'],
    ['Postulaciones nuevas',   $stats['postulaciones_nuevas'],   'fa-user-check',   'applications', 'amber', $stats['postulaciones_revision'] . ' en revisión'],
    ['Credenciales vigentes',  $stats['credenciales_vigentes'],  'fa-address-card', 'credentials',  'leaf',  $stats['credenciales_por_vencer'] . ' por vencer'],
    ['Cobrado (30 días)',      dinero($stats['cobrado_30d']),    'fa-money-bill',   'orders',       'leaf',  $stats['ordenes_pendientes'] . ' órdenes pendientes'],
];
$colores = [
    'brand' => ['bg' => 'bg-brand-50',  'text' => 'text-brand-600',  'ring' => 'border-brand-100'],
    'leaf'  => ['bg' => 'bg-leaf-50',   'text' => 'text-leaf-600',   'ring' => 'border-leaf-100'],
    'amber' => ['bg' => 'bg-amber-50',  'text' => 'text-amber-600',  'ring' => 'border-amber-100'],
];
?>

<!-- Métricas principales -->
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <?php foreach ($tarjetas as [$label, $valor, $icono, $ruta, $color, $pie]): ?>
    <a href="<?= BASE_URL ?>/<?= $ruta ?>" class="bg-white rounded-2xl border border-slate-200 p-5 hover:border-brand-300 hover:shadow-md transition-all">
        <div class="flex items-start justify-between">
            <div class="min-w-0">
                <p class="text-slate-500 text-xs font-medium uppercase tracking-wide"><?= e($label) ?></p>
                <p class="text-2xl font-extrabold text-slate-800 mt-1.5 truncate"><?= is_string($valor) ? e($valor) : (int) $valor ?></p>
                <p class="text-slate-400 text-xs mt-1"><?= e($pie) ?></p>
            </div>
            <span class="w-11 h-11 shrink-0 rounded-xl <?= $colores[$color]['bg'] ?> <?= $colores[$color]['text'] ?> border <?= $colores[$color]['ring'] ?> flex items-center justify-center">
                <i class="fas <?= $icono ?>"></i>
            </span>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- Segunda fila de indicadores -->
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php
    $mini = [
        ['Defensores acreditados', $stats['defensores_acreditados'], 'fa-shield-halved'],
        ['Afiliados activos',      $stats['afiliados_activos'],      'fa-users'],
        ['Pendientes de pago',     $stats['postulaciones_pago'],     'fa-hourglass-half'],
        ['Órdenes vencidas',       $stats['ordenes_vencidas'],       'fa-triangle-exclamation'],
    ];
    foreach ($mini as [$label, $valor, $icono]): ?>
    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 flex items-center gap-3">
        <i class="fas <?= $icono ?> text-slate-300"></i>
        <div class="min-w-0">
            <p class="text-lg font-bold text-slate-800 leading-none"><?= (int) $valor ?></p>
            <p class="text-slate-500 text-xs mt-1 truncate"><?= e($label) ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid lg:grid-cols-3 gap-5">
    <!-- Gráficas -->
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h2 class="font-bold text-slate-800 mb-1">Ingresos confirmados por Stripe</h2>
            <p class="text-slate-400 text-xs mb-4">Últimos 12 meses · sólo órdenes pagadas</p>
            <?php if (empty($monthlyCollected)): ?>
                <p class="text-slate-400 text-sm py-10 text-center">Todavía no hay pagos confirmados.</p>
            <?php else: ?>
                <canvas id="chartIngresos" height="110"></canvas>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h2 class="font-bold text-slate-800 mb-4">Solicitudes por estado</h2>
            <?php if (empty($reqByStatus)): ?>
                <p class="text-slate-400 text-sm py-6 text-center">Todavía no hay solicitudes registradas.</p>
            <?php else: ?>
            <div class="space-y-2.5">
                <?php $maxReq = max($reqByStatus) ?: 1; ?>
                <?php foreach ($reqByStatus as $estado => $total): ?>
                <div class="flex items-center gap-3">
                    <span class="w-44 shrink-0"><?= estadoBadge($estado) ?></span>
                    <div class="flex-1 bg-slate-100 rounded-full h-2.5 overflow-hidden">
                        <div class="h-full rounded-full" style="width: <?= round($total / $maxReq * 100) ?>%; background:linear-gradient(90deg,#0e85a5,#00ac00);"></div>
                    </div>
                    <span class="w-10 text-right text-sm font-semibold text-slate-700"><?= (int) $total ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Listas de trabajo -->
    <div class="space-y-5">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-bold text-slate-800">Emergencias</h2>
                <a href="<?= BASE_URL ?>/requests?urgency=EMERGENCIA" class="text-xs text-brand-600 hover:underline">Ver todas</a>
            </div>
            <?php if (empty($urgentRequests)): ?>
                <p class="text-slate-400 text-sm py-3">Sin solicitudes de emergencia. </p>
            <?php else: ?>
            <ul class="space-y-3">
                <?php foreach ($urgentRequests as $r): ?>
                <li>
                    <a href="<?= BASE_URL ?>/requests/<?= (int) $r['id'] ?>" class="block hover:bg-brand-50 -mx-2 px-2 py-1.5 rounded-lg">
                        <p class="text-sm font-medium text-slate-800 truncate"><?= e($r['subject']) ?></p>
                        <p class="text-xs text-slate-400 mt-0.5"><?= e($r['folio']) ?> · <?= fechaCorta($r['created_at']) ?></p>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-bold text-slate-800">Postulaciones por revisar</h2>
                <a href="<?= BASE_URL ?>/applications?status=ENVIADA" class="text-xs text-brand-600 hover:underline">Ver todas</a>
            </div>
            <?php if (empty($pendingApps)): ?>
                <p class="text-slate-400 text-sm py-3">Nada pendiente por revisar.</p>
            <?php else: ?>
            <ul class="space-y-3">
                <?php foreach ($pendingApps as $a): ?>
                <li>
                    <a href="<?= BASE_URL ?>/applications/<?= (int) $a['id'] ?>" class="block hover:bg-brand-50 -mx-2 px-2 py-1.5 rounded-lg">
                        <p class="text-sm font-medium text-slate-800 truncate"><?= e($a['user_name']) ?></p>
                        <p class="text-xs text-slate-400 mt-0.5">Enviada <?= fechaCorta($a['submitted_at']) ?></p>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h2 class="font-bold text-slate-800 mb-3">Credenciales por vencer</h2>
            <?php if (empty($expiringCreds)): ?>
                <p class="text-slate-400 text-sm py-3">Ninguna vence en los próximos 30 días.</p>
            <?php else: ?>
            <ul class="space-y-2.5">
                <?php foreach ($expiringCreds as $c): ?>
                <li class="flex items-center justify-between gap-3 text-sm">
                    <span class="truncate text-slate-700"><?= e($c['affiliate_name']) ?></span>
                    <span class="text-xs text-amber-600 font-medium shrink-0"><?= fechaCorta($c['expires_at']) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Actividad reciente -->
<div class="bg-white rounded-2xl border border-slate-200 p-5 mt-5">
    <div class="flex items-center justify-between mb-3">
        <h2 class="font-bold text-slate-800">Actividad reciente</h2>
        <a href="<?= BASE_URL ?>/audit" class="text-xs text-brand-600 hover:underline">Ver bitácora completa</a>
    </div>
    <?php if (empty($recentActivity)): ?>
        <p class="text-slate-400 text-sm py-3">Sin actividad registrada.</p>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full data-table text-sm">
            <thead><tr class="border-b border-slate-100">
                <th>Cuándo</th><th>Quién</th><th>Acción</th><th>Detalle</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($recentActivity as $a): ?>
                <tr>
                    <td class="text-slate-400 whitespace-nowrap"><?= fechaHora($a['created_at']) ?></td>
                    <td class="text-slate-600"><?= e($a['user_name'] ?? 'Sistema') ?></td>
                    <td><span class="badge bg-slate-100 text-slate-600"><?= e(str_replace('_', ' ', $a['action'])) ?></span></td>
                    <td class="text-slate-500 max-w-md truncate"><?= e($a['details']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($monthlyCollected)): ?>
<script>
new Chart(document.getElementById('chartIngresos'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($monthlyCollected, 'periodo')) ?>,
        datasets: [{
            label: 'Cobrado (MXN)',
            data: <?= json_encode(array_map('floatval', array_column($monthlyCollected, 'total'))) ?>,
            backgroundColor: '#0e85a5',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => '$' + v.toLocaleString('es-MX') } },
            x: { grid: { display: false } }
        }
    }
});
</script>
<?php endif; ?>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
