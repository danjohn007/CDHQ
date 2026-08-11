<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<form method="GET" action="<?= BASE_URL ?>/reports" class="bg-white rounded-2xl border border-slate-200 p-4 mb-5">
    <div class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Desde</label>
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>"
                   class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Hasta</label>
            <input type="date" name="date_to" value="<?= e($dateTo) ?>"
                   class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
        </div>
        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Aplicar</button>
        <p class="text-xs text-slate-400 ml-auto">El periodo aplica a solicitudes y movimientos; los totales acumulados no se filtran.</p>
    </div>
</form>

<!-- Indicadores -->
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <p class="text-slate-500 text-xs uppercase tracking-wide">Solicitudes cerradas</p>
        <p class="text-2xl font-extrabold text-slate-800 mt-1"><?= (int) ($atencion['cerradas'] ?? 0) ?></p>
        <p class="text-slate-400 text-xs mt-1">en el periodo</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <p class="text-slate-500 text-xs uppercase tracking-wide">Tiempo medio de atención</p>
        <p class="text-2xl font-extrabold text-slate-800 mt-1">
            <?= !empty($atencion['horas_promedio']) ? round((float) $atencion['horas_promedio']) . ' h' : '—' ?>
        </p>
        <p class="text-slate-400 text-xs mt-1">de recepción a cierre</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <p class="text-slate-500 text-xs uppercase tracking-wide">Tasa de acreditación</p>
        <p class="text-2xl font-extrabold text-slate-800 mt-1"><?= e((string) $tasaAcreditacion) ?>%</p>
        <p class="text-slate-400 text-xs mt-1">de las postulaciones</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <p class="text-slate-500 text-xs uppercase tracking-wide">Credenciales vigentes</p>
        <p class="text-2xl font-extrabold text-slate-800 mt-1"><?= (int) $credencialesVigentes ?></p>
        <p class="text-amber-600 text-xs mt-1"><?= (int) $credencialesPorVencer ?> vencen en 30 días</p>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-5">
    <!-- Ingresos -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h2 class="font-bold text-slate-800 mb-1">Ingresos confirmados por Stripe</h2>
        <p class="text-slate-400 text-xs mb-4">Últimos 12 meses</p>
        <?php if (empty($monthlyCollected)): ?>
            <p class="text-slate-400 text-sm py-10 text-center">Sin pagos confirmados todavía.</p>
        <?php else: ?>
            <canvas id="chartIngresos" height="150"></canvas>
        <?php endif; ?>
    </div>

    <!-- Urgencia -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h2 class="font-bold text-slate-800 mb-4">Solicitudes por urgencia</h2>
        <?php if (empty($porUrgencia)): ?>
            <p class="text-slate-400 text-sm py-10 text-center">Sin solicitudes en el periodo.</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php $maxU = max(array_column($porUrgencia, 'total')) ?: 1; ?>
            <?php foreach ($porUrgencia as $u): ?>
            <div class="flex items-center gap-3">
                <span class="w-28 shrink-0"><?= urgenciaBadge($u['urgency']) ?></span>
                <div class="flex-1 bg-slate-100 rounded-full h-2.5 overflow-hidden">
                    <div class="h-full rounded-full" style="width: <?= round($u['total'] / $maxU * 100) ?>%; background:linear-gradient(90deg,#0e85a5,#00ac00);"></div>
                </div>
                <span class="w-10 text-right text-sm font-semibold text-slate-700"><?= (int) $u['total'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Carga de asesores -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h2 class="font-bold text-slate-800 mb-1">Carga de trabajo por asesor</h2>
        <p class="text-slate-400 text-xs mb-4">Las métricas describen la carga, no evalúan a las personas.</p>
        <?php if (empty($cargaAsesores)): ?>
            <p class="text-slate-400 text-sm py-6 text-center">Todavía no hay casos asignados.</p>
        <?php else: ?>
        <table class="w-full data-table text-sm">
            <thead class="border-b border-slate-100"><tr><th>Asesor</th><th class="text-right">Abiertas</th><th class="text-right">Cerradas</th><th class="text-right">Total</th></tr></thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($cargaAsesores as $a): ?>
                <tr>
                    <td class="text-slate-800"><?= e($a['name']) ?></td>
                    <td class="text-right font-semibold <?= (int) $a['abiertas'] > 5 ? 'text-amber-600' : 'text-slate-700' ?>"><?= (int) $a['abiertas'] ?></td>
                    <td class="text-right text-slate-500"><?= (int) $a['cerradas'] ?></td>
                    <td class="text-right text-slate-500"><?= (int) $a['total'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Municipios -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h2 class="font-bold text-slate-800 mb-4">Solicitudes por municipio</h2>
        <?php if (empty($porMunicipio)): ?>
            <p class="text-slate-400 text-sm py-6 text-center">Sin datos de ubicación.</p>
        <?php else: ?>
        <div class="space-y-2">
            <?php $maxM = max(array_column($porMunicipio, 'total')) ?: 1; ?>
            <?php foreach ($porMunicipio as $m): ?>
            <div class="flex items-center gap-3 text-sm">
                <span class="w-40 shrink-0 truncate text-slate-600"><?= e($m['municipio']) ?></span>
                <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div class="h-full rounded-full bg-brand-400" style="width: <?= round($m['total'] / $maxM * 100) ?>%"></div>
                </div>
                <span class="w-8 text-right font-semibold text-slate-700"><?= (int) $m['total'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Postulaciones -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h2 class="font-bold text-slate-800 mb-4">Postulaciones por estado</h2>
        <?php if (empty($appsByStatus)): ?>
            <p class="text-slate-400 text-sm py-6 text-center">Sin postulaciones registradas.</p>
        <?php else: ?>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($appsByStatus as $estado => $total): ?>
            <span class="badge px-3 py-1.5 <?= estadoClase($estado) ?>"><?= e(str_replace('_', ' ', $estado)) ?>: <?= (int) $total ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Órdenes -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h2 class="font-bold text-slate-800 mb-4">Órdenes por estado</h2>
        <?php if (empty($ordersByStatus)): ?>
            <p class="text-slate-400 text-sm py-6 text-center">Sin órdenes registradas.</p>
        <?php else: ?>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($ordersByStatus as $estado => $total): ?>
            <span class="badge px-3 py-1.5 <?= estadoClase($estado) ?>"><?= e(str_replace('_', ' ', $estado)) ?>: <?= (int) $total ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($incomeByType)): ?>
        <h3 class="font-semibold text-slate-700 text-sm mt-6 mb-3">Movimientos registrados a mano</h3>
        <table class="w-full data-table text-sm">
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($incomeByType as $t): ?>
                <tr>
                    <td class="text-slate-600"><?= e($t['type'] ?? '—') ?></td>
                    <td class="text-right font-semibold text-slate-800"><?= e(dinero($t['total'] ?? 0)) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($monthlyCollected)): ?>
<script>
new Chart(document.getElementById('chartIngresos'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($monthlyCollected, 'periodo')) ?>,
        datasets: [{
            label: 'Cobrado (MXN)',
            data: <?= json_encode(array_map('floatval', array_column($monthlyCollected, 'total'))) ?>,
            borderColor: '#0e85a5',
            backgroundColor: 'rgba(14,133,165,0.12)',
            fill: true, tension: 0.3, pointRadius: 3,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => '$' + v.toLocaleString('es-MX') } }, x: { grid: { display: false } } }
    }
});
</script>
<?php endif; ?>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
