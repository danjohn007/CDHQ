<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<div class="flex items-center justify-between mb-6">
    <form method="GET" action="<?= $base_url ?>/events" class="flex gap-2">
        <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            <option value="">Todos los estados</option>
            <?php foreach (['PROGRAMADO','EN_CURSO','FINALIZADO','CANCELADO'] as $s): ?>
            <option value="<?= $s ?>" <?= ($status==$s)?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">Filtrar</button>
    </form>
    <?php if (Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_COORD_EVENTOS)): ?>
    <a href="<?= $base_url ?>/events/create"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        ＋ Nuevo Evento
    </a>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php if (empty($events)): ?>
    <div class="col-span-3 py-12 text-center text-gray-400 bg-white rounded-xl border border-gray-100">
        No se encontraron eventos.
    </div>
    <?php endif; ?>
    <?php foreach ($events as $ev): ?>
    <?php
    $statusColors = [
        'PROGRAMADO' =>'bg-blue-100 text-blue-700',
        'EN_CURSO'   =>'bg-green-100 text-green-700',
        'FINALIZADO' =>'bg-gray-100 text-gray-600',
        'CANCELADO'  =>'bg-red-100 text-red-700',
    ];
    $sc = $statusColors[$ev['status']] ?? 'bg-gray-100 text-gray-600';
    ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col gap-3">
        <div class="flex items-start justify-between">
            <h3 class="font-semibold text-gray-800 leading-tight">
                <a href="<?= $base_url ?>/events/<?= $ev['id'] ?>" class="hover:text-indigo-600">
                    <?= htmlspecialchars($ev['name']) ?>
                </a>
            </h3>
            <span class="text-xs px-2 py-0.5 rounded-full font-medium ml-2 flex-shrink-0 <?= $sc ?>">
                <?= htmlspecialchars($ev['status']) ?>
            </span>
        </div>
        <div class="text-sm text-gray-500 space-y-1">
            <p>📅 <?= htmlspecialchars(date('d/m/Y H:i', strtotime($ev['date']))) ?></p>
            <p>📍 <?= htmlspecialchars($ev['venue'] ?? '—') ?></p>
            <p>🏷️ <?= htmlspecialchars($ev['type']) ?></p>
            <?php if ($ev['capacity']): ?>
            <p>👥 <?= (int)$ev['registrations'] ?>/<?= (int)$ev['capacity'] ?> inscritos</p>
            <?php endif; ?>
        </div>
        <a href="<?= $base_url ?>/events/<?= $ev['id'] ?>"
           class="mt-auto text-sm text-indigo-600 hover:text-indigo-800 font-medium">
            Ver detalles →
        </a>
    </div>
    <?php endforeach; ?>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
