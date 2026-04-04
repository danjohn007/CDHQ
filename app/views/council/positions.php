<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<div class="flex items-center justify-between mb-6">
    <div class="flex gap-2">
        <a href="<?= $base_url ?>/council" class="text-sm border border-gray-300 hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg">
            ← Organigrama
        </a>
        <a href="<?= $base_url ?>/council/assignments" class="text-sm border border-gray-300 hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg">
            Asignaciones
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Create position form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Nuevo Cargo</h3>
        <form method="POST" action="<?= $base_url ?>/council/positions/create">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(Auth::generateCsrf()) ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Título <span class="text-red-500">*</span></label>
                <input type="text" name="title" required placeholder="Ej. Presidente, Secretario…"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nivel jerárquico</label>
                <input type="number" name="level" min="1" value="1"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="description" rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
            </div>
            <div class="mb-4 flex items-center gap-2">
                <input type="checkbox" id="is_unique" name="is_unique" value="1" checked
                       class="w-4 h-4 text-indigo-600 border-gray-300 rounded">
                <label for="is_unique" class="text-sm text-gray-700">Cargo único (solo una persona activa)</label>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 rounded-lg transition-colors">
                Crear Cargo
            </button>
        </form>
    </div>

    <!-- Positions list -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Cargos Definidos</h3>
        <div class="space-y-2 max-h-96 overflow-y-auto">
            <?php foreach ($positions as $pos): ?>
            <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 hover:bg-gray-100">
                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center text-xs font-bold text-indigo-700 flex-shrink-0">
                    <?= $pos['level'] ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($pos['title']) ?></p>
                    <?php if ($pos['description']): ?>
                    <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($pos['description']) ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($pos['is_unique']): ?>
                <span class="text-xs text-gray-400">Único</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if (empty($positions)): ?>
            <p class="text-gray-400 text-sm text-center py-4">Sin cargos definidos.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
