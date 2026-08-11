<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<div class="bg-brand-50 border border-brand-100 rounded-2xl px-5 py-4 mb-6 flex gap-3 text-sm text-brand-900">
    <i class="fas fa-circle-info text-brand-500 mt-0.5"></i>
    <p class="leading-relaxed">
        El precio y la duración se <strong>congelan en cada orden</strong> al momento de crearla. Cambiar un plan aquí
        afecta únicamente a las órdenes futuras: las anualidades ya cobradas conservan el importe con el que se pagaron.
    </p>
</div>

<div class="grid lg:grid-cols-3 gap-5">
    <!-- Planes existentes -->
    <div class="lg:col-span-2 space-y-4">
        <?php if (empty($plans)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 py-16 text-center">
                <i class="fas fa-tags text-4xl text-slate-200"></i>
                <p class="text-slate-500 mt-4">Todavía no hay planes configurados.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($plans as $p): ?>
        <div class="bg-white rounded-2xl border <?= $p['active'] ? 'border-slate-200' : 'border-slate-200 opacity-70' ?> p-5">
            <form method="POST" action="<?= BASE_URL ?>/plans/<?= (int) $p['id'] ?>/edit">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(Auth::generateCsrf()) ?>">

                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <span class="badge <?= $p['active'] ? 'bg-leaf-50 text-leaf-700 border border-leaf-200' : 'bg-slate-100 text-slate-500' ?>">
                            <?= $p['active'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                        <span class="text-xs text-slate-400">
                            <?= (int) $p['ordenes_pagadas'] ?> pagadas ·
                            <?= (int) $p['ordenes_pendientes'] ?> pendientes ·
                            <?= (int) $p['suscripciones'] ?> suscripciones
                        </span>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Nombre</label>
                        <input type="text" name="name" required value="<?= e($p['name']) ?>"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Descripción</label>
                        <textarea name="description" rows="2"
                                  class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm resize-y"><?= e($p['description']) ?></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Precio (MXN)</label>
                        <input type="number" name="price" step="0.01" min="0" required value="<?= e($p['price']) ?>"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Duración (días)</label>
                        <input type="number" name="duration_days" min="1" max="3650" required value="<?= (int) $p['duration_days'] ?>"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 outline-none">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 mt-4 pt-4 border-t border-slate-100">
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="active" value="1" <?= $p['active'] ? 'checked' : '' ?>
                               class="w-4 h-4 rounded border-slate-300 text-brand-500 focus:ring-brand-400">
                        Disponible en la aplicación
                    </label>
                    <button type="submit"
                            onclick="return confirm('Vas a modificar un plan. El nuevo importe sólo aplicará a órdenes futuras. ¿Continuar?');"
                            class="ml-auto bg-brand-500 hover:bg-brand-600 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-colors">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Nuevo plan -->
    <div>
        <form method="POST" action="<?= BASE_URL ?>/plans/create" class="bg-white rounded-2xl border border-slate-200 p-5 sticky top-4">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(Auth::generateCsrf()) ?>">
            <h3 class="font-bold text-slate-800 mb-4">Nuevo plan</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Nombre</label>
                    <input type="text" name="name" required placeholder="Plan Anual Individual"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Descripción</label>
                    <textarea name="description" rows="2" placeholder="Qué incluye este plan"
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm resize-y"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Precio (MXN)</label>
                        <input type="number" name="price" step="0.01" min="0" required value="0"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Días</label>
                        <input type="number" name="duration_days" min="1" max="3650" required value="365"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600 pt-1">
                    <input type="checkbox" name="active" value="1" checked
                           class="w-4 h-4 rounded border-slate-300 text-brand-500 focus:ring-brand-400">
                    Disponible de inmediato
                </label>
            </div>
            <button type="submit" class="w-full mt-5 bg-leaf-500 hover:bg-leaf-600 text-white py-2.5 rounded-lg text-sm font-semibold transition-colors">
                Crear plan
            </button>
        </form>
    </div>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
