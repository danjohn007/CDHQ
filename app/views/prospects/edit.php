<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-4xl mx-auto">
    <!-- Edit form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-6">Editar Prospecto</h2>
        <form method="POST" action="<?= $base_url ?>/prospects/<?= $prospect['id'] ?>/edit">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(Auth::generateCsrf()) ?>">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="<?= htmlspecialchars($prospect['name']) ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($prospect['email'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($prospect['phone'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Origen</label>
                <input type="text" name="source" value="<?= htmlspecialchars($prospect['source'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select name="state" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <?php foreach (['NUEVO','CONTACTADO','SEGUIMIENTO','CONVERTIDO'] as $s): ?>
                    <option value="<?= $s ?>" <?= $prospect['state']===$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                <textarea name="notes" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none focus:ring-2 focus:ring-indigo-500 outline-none"><?= htmlspecialchars($prospect['notes'] ?? '') ?></textarea>
            </div>

            <div class="flex gap-3 mt-2">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                    Guardar Cambios
                </button>
                <a href="<?= $base_url ?>/prospects" class="flex-1 text-center border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                    Cancelar
                </a>
            </div>
        </form>

        <?php if ($prospect['state'] !== 'CONVERTIDO' && Auth::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS)): ?>
        <hr class="my-6 border-gray-200">
        <form method="POST" action="<?= $base_url ?>/prospects/<?= $prospect['id'] ?>/convert"
              onsubmit="return confirm('¿Convertir este prospecto a afiliado?')">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(Auth::generateCsrf()) ?>">
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                ✅ Convertir a Afiliado
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Notes -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Notas de Seguimiento</h3>

        <!-- Add note form -->
        <form method="POST" action="<?= $base_url ?>/prospects/<?= $prospect['id'] ?>/note" class="mb-6">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(Auth::generateCsrf()) ?>">
            <textarea name="note" rows="3" placeholder="Agregar nota de seguimiento…" required
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none focus:ring-2 focus:ring-indigo-500 outline-none mb-2"></textarea>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                Agregar Nota
            </button>
        </form>

        <!-- Notes list -->
        <div class="space-y-3 max-h-80 overflow-y-auto">
            <?php foreach ($notes as $note): ?>
            <div class="border-l-2 border-indigo-200 pl-3">
                <p class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($note['note'])) ?></p>
                <p class="text-xs text-gray-400 mt-1">
                    <?= htmlspecialchars($note['author'] ?? 'Sistema') ?> –
                    <?= htmlspecialchars($note['created_at']) ?>
                </p>
            </div>
            <?php endforeach; ?>
            <?php if (empty($notes)): ?>
            <p class="text-gray-400 text-sm">Sin notas de seguimiento.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
