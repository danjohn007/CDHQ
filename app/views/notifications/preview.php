<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<div class="max-w-3xl mx-auto">

    <div class="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 mb-5 flex gap-3 text-sm text-amber-900">
        <i class="fas fa-triangle-exclamation text-amber-500 mt-0.5"></i>
        <p class="leading-relaxed">
            Estás a punto de enviar este aviso a
            <strong><?= (int) $destinatarios ?> <?= $destinatarios === 1 ? 'persona' : 'personas' ?></strong>.
            <strong>No se puede deshacer.</strong> Revisa el texto y los destinatarios antes de continuar.
        </p>
    </div>

    <!-- Cómo se verá -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-5">
        <h3 class="font-bold text-slate-800 mb-1">Así lo recibirá cada persona</h3>
        <p class="text-xs text-slate-500 mb-4">Vista aproximada del aviso en la aplicación.</p>

        <div class="border border-slate-200 rounded-xl p-4 bg-slate-50">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-full bg-brand-500 text-white flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-bullhorn text-sm"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-slate-800 text-sm leading-snug"><?= e($datos['title']) ?></p>
                    <p class="text-sm text-slate-600 mt-1.5 whitespace-pre-line leading-relaxed"><?= e($datos['message']) ?></p>
                    <?php if (!empty($datos['action_url'])): ?>
                    <p class="mt-2.5">
                        <span class="inline-block bg-brand-500 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">
                            Abrir enlace
                        </span>
                        <span class="block text-xs text-slate-400 mt-1.5 break-all"><?= e($datos['action_url']) ?></span>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- A quién -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-5">
        <h3 class="font-bold text-slate-800 mb-1">Destinatarios</h3>
        <p class="text-sm text-slate-600 mb-4"><?= e($descripcion) ?></p>

        <?php if (!empty($muestra)): ?>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($muestra as $u): ?>
            <span class="badge bg-slate-100 text-slate-600 border border-slate-200" title="<?= e($u['email']) ?>">
                <?= e($u['name']) ?>
            </span>
            <?php endforeach; ?>
            <?php if ($destinatarios > count($muestra)): ?>
            <span class="badge bg-brand-50 text-brand-700 border border-brand-200">
                y <?= (int) ($destinatarios - count($muestra)) ?> más
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Confirmar. Los datos viajan otra vez porque nada se guardó todavía. -->
    <form method="POST" action="<?= BASE_URL ?>/notifications/send" class="flex flex-wrap gap-3">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(Auth::generateCsrf()) ?>">
        <input type="hidden" name="destino"    value="<?= e($datos['destino']) ?>">
        <input type="hidden" name="segmento"   value="<?= e($datos['segmento']) ?>">
        <input type="hidden" name="user_id"    value="<?= (int) $datos['user_id'] ?>">
        <input type="hidden" name="title"      value="<?= e($datos['title']) ?>">
        <input type="hidden" name="message"    value="<?= e($datos['message']) ?>">
        <input type="hidden" name="action_url" value="<?= e($datos['action_url'] ?? '') ?>">

        <button type="submit"
                onclick="return confirm('Se enviará el aviso a <?= (int) $destinatarios ?> persona(s) y no podrá deshacerse. ¿Continuar?');"
                class="bg-brand-500 hover:bg-brand-600 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors">
            <i class="fas fa-paper-plane mr-1"></i>
            Enviar a <?= (int) $destinatarios ?> <?= $destinatarios === 1 ? 'persona' : 'personas' ?>
        </button>
        <a href="<?= BASE_URL ?>/notifications"
           class="px-6 py-2.5 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
            Volver a editar
        </a>
    </form>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
