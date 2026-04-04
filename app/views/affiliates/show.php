<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<?php
$stateColors = [
    'ACTIVO'    => 'bg-green-100 text-green-700 border-green-200',
    'SUSPENDIDO'=> 'bg-yellow-100 text-yellow-700 border-yellow-200',
    'VENCIDO'   => 'bg-red-100 text-red-700 border-red-200',
    'PROSPECTO' => 'bg-blue-100 text-blue-700 border-blue-200',
];
$sc = $stateColors[$affiliate['state']] ?? 'bg-gray-100 text-gray-600 border-gray-200';
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-4">
        <div class="w-16 h-16 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-2xl">
            <?= htmlspecialchars(mb_strtoupper(mb_substr($affiliate['first_name'], 0, 1))) ?>
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-800">
                <?= htmlspecialchars($affiliate['first_name'] . ' ' . $affiliate['last_name']) ?>
            </h2>
            <p class="text-sm text-gray-500 font-mono"><?= htmlspecialchars($affiliate['affiliate_number']) ?></p>
            <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full font-medium border <?= $sc ?>">
                <?= htmlspecialchars($affiliate['state']) ?>
            </span>
        </div>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <a href="<?= $base_url ?>/credentials/<?= $affiliate['id'] ?>"
           class="text-sm bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-lg transition-colors">
            🎫 Credencial
        </a>
        <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS)): ?>
        <a href="<?= $base_url ?>/affiliates/<?= $affiliate['id'] ?>/edit"
           class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition-colors">
            ✏️ Editar
        </a>
        <?php endif; ?>
        <a href="<?= $base_url ?>/affiliates" class="text-sm border border-gray-300 hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-lg transition-colors">
            ← Volver
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left: info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Contact info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Información de Contacto</h3>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-gray-500">Correo</dt><dd class="font-medium"><?= htmlspecialchars($affiliate['email'] ?? '—') ?></dd></div>
                <div><dt class="text-gray-500">Teléfono</dt><dd class="font-medium"><?= htmlspecialchars($affiliate['phone'] ?? '—') ?></dd></div>
                <div class="col-span-2"><dt class="text-gray-500">Dirección</dt><dd class="font-medium"><?= htmlspecialchars($affiliate['address'] ?? '—') ?></dd></div>
                <div><dt class="text-gray-500">Registro</dt><dd class="font-medium"><?= htmlspecialchars($affiliate['created_at'] ?? '') ?></dd></div>
                <div><dt class="text-gray-500">Actualización</dt><dd class="font-medium"><?= htmlspecialchars($affiliate['updated_at'] ?? '') ?></dd></div>
            </dl>
            <?php if ($affiliate['notes']): ?>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <dt class="text-xs text-gray-500 mb-1">Notas</dt>
                <dd class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($affiliate['notes'])) ?></dd>
            </div>
            <?php endif; ?>
        </div>

        <!-- Subscriptions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700">Membresías</h3>
                <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS)): ?>
                <a href="<?= $base_url ?>/subscriptions/create?affiliate_id=<?= $affiliate['id'] ?>"
                   class="text-xs bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 transition-colors">
                    ＋ Nueva
                </a>
                <?php endif; ?>
            </div>
            <?php if ($activeSub): ?>
            <div class="mb-3 p-3 bg-green-50 border border-green-200 rounded-lg text-sm">
                <p class="font-medium text-green-800">✓ Membresía Activa: <?= htmlspecialchars($activeSub['plan_name']) ?></p>
                <p class="text-green-600 text-xs mt-0.5">Válida hasta: <?= htmlspecialchars($activeSub['end_date']) ?></p>
            </div>
            <?php endif; ?>
            <table class="w-full text-xs divide-y divide-gray-100">
                <thead><tr class="text-gray-500">
                    <th class="py-2 text-left">Plan</th>
                    <th class="py-2 text-left">Inicio</th>
                    <th class="py-2 text-left">Fin</th>
                    <th class="py-2 text-left">Estado</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($subs as $s): ?>
                    <tr>
                        <td class="py-1.5 text-gray-700"><?= htmlspecialchars($s['plan_name']) ?></td>
                        <td class="py-1.5 text-gray-600"><?= htmlspecialchars($s['start_date']) ?></td>
                        <td class="py-1.5 text-gray-600"><?= htmlspecialchars($s['end_date']) ?></td>
                        <td class="py-1.5">
                            <span class="px-1.5 py-0.5 rounded text-xs <?= $s['status']==='ACTIVA' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                                <?= htmlspecialchars($s['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($subs)): ?><tr><td colspan="4" class="py-4 text-center text-gray-400">Sin membresías.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Payments -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Historial de Pagos</h3>
            <table class="w-full text-xs divide-y divide-gray-100">
                <thead><tr class="text-gray-500">
                    <th class="py-2 text-left">Fecha</th>
                    <th class="py-2 text-left">Tipo</th>
                    <th class="py-2 text-left">Monto</th>
                    <th class="py-2 text-left">Método</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td class="py-1.5 text-gray-600"><?= htmlspecialchars($p['payment_date']) ?></td>
                        <td class="py-1.5 text-gray-700"><?= htmlspecialchars($p['type']) ?></td>
                        <td class="py-1.5 font-medium text-gray-800">$<?= number_format($p['amount'], 2) ?></td>
                        <td class="py-1.5 text-gray-600"><?= htmlspecialchars($p['method']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($payments)): ?><tr><td colspan="4" class="py-4 text-center text-gray-400">Sin pagos.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right: actions & history -->
    <div class="space-y-6">
        <!-- Change status -->
        <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Cambiar Estado</h3>
            <form method="POST" action="<?= $base_url ?>/affiliates/<?= $affiliate['id'] ?>/status">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(Auth::generateCsrf()) ?>">
                <select name="state" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-3 focus:ring-2 focus:ring-indigo-500 outline-none">
                    <?php foreach (['PROSPECTO','ACTIVO','SUSPENDIDO','VENCIDO'] as $st): ?>
                    <option value="<?= $st ?>" <?= $affiliate['state']===$st?'selected':'' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
                <textarea name="reason" placeholder="Motivo del cambio…" rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none mb-3 focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                    Actualizar Estado
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Change log -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Historial de Cambios</h3>
            <div class="space-y-3 max-h-72 overflow-y-auto">
                <?php foreach ($history as $h): ?>
                <div class="text-xs border-l-2 border-indigo-200 pl-3">
                    <p class="font-medium text-gray-700">
                        <?= htmlspecialchars($h['field_changed']) ?>:
                        <span class="text-red-500"><?= htmlspecialchars($h['old_value'] ?: '—') ?></span>
                        → <span class="text-green-600"><?= htmlspecialchars($h['new_value']) ?></span>
                    </p>
                    <?php if ($h['reason']): ?>
                    <p class="text-gray-500 italic">"<?= htmlspecialchars($h['reason']) ?>"</p>
                    <?php endif; ?>
                    <p class="text-gray-400"><?= htmlspecialchars($h['created_at']) ?> – <?= htmlspecialchars($h['changed_by_name'] ?? 'Sistema') ?></p>
                </div>
                <?php endforeach; ?>
                <?php if (empty($history)): ?><p class="text-gray-400 text-xs">Sin historial.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
