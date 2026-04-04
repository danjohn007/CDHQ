<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<!-- Org Chart visualization -->
<div class="mb-6 flex items-center justify-between">
    <div></div>
    <div class="flex gap-2">
        <a href="<?= $base_url ?>/council/positions" class="text-sm border border-gray-300 hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg transition-colors">
            📋 Cargos
        </a>
        <a href="<?= $base_url ?>/council/assignments" class="text-sm border border-gray-300 hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg transition-colors">
            👥 Asignaciones
        </a>
    </div>
</div>

<?php
// Group by level
$byLevel = [];
foreach ($orgChart as $row) {
    $byLevel[$row['level']][] = $row;
}
ksort($byLevel);
?>

<div class="space-y-6">
    <?php foreach ($byLevel as $level => $positions): ?>
    <div>
        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Nivel <?= $level ?></h3>
        <div class="flex flex-wrap gap-4">
            <?php foreach ($positions as $pos): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 min-w-56 flex-1 max-w-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-indigo-700 text-lg">🏛️</span>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($pos['title']) ?></h4>
                        <?php if ($pos['description']): ?>
                        <p class="text-xs text-gray-400"><?= htmlspecialchars($pos['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($pos['holder_name']): ?>
                <div class="flex items-center gap-2 mt-2 p-2 bg-gray-50 rounded-lg">
                    <div class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        <?= htmlspecialchars(mb_strtoupper(mb_substr($pos['holder_name'], 0, 1))) ?>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-gray-800 truncate"><?= htmlspecialchars($pos['holder_name']) ?></p>
                        <p class="text-xs text-gray-400">Desde <?= htmlspecialchars($pos['start_date']) ?></p>
                    </div>
                </div>
                <?php else: ?>
                <div class="mt-2 p-2 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-400 italic">Vacante</p>
                </div>
                <?php endif; ?>
                <?php if (!$pos['is_unique']): ?>
                <p class="text-xs text-indigo-400 mt-2">Cargo compartido</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($orgChart)): ?>
    <div class="py-12 text-center text-gray-400 bg-white rounded-xl border border-gray-100">
        No hay cargos definidos. <a href="<?= $base_url ?>/council/positions" class="text-indigo-600 hover:underline">Crear cargos →</a>
    </div>
    <?php endif; ?>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
