<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<a href="<?= BASE_URL ?>/requests" class="text-sm text-brand-600 hover:underline">&larr; Volver a solicitudes</a>

<div class="grid lg:grid-cols-3 gap-5 mt-4">
    <!-- Expediente -->
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-mono text-xs text-slate-400"><?= e($request['folio']) ?></p>
                    <h2 class="text-xl font-bold text-slate-800 mt-1"><?= e($request['subject']) ?></h2>
                </div>
                <div class="flex gap-2">
                    <?= urgenciaBadge($request['urgency']) ?>
                    <?= estadoBadge($request['status']) ?>
                </div>
            </div>

            <div class="mt-5 pt-5 border-t border-slate-100">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Descripción</p>
                <p class="text-slate-700 text-sm leading-relaxed whitespace-pre-line"><?= e($request['description']) ?></p>
            </div>

            <dl class="grid sm:grid-cols-2 gap-4 mt-5 pt-5 border-t border-slate-100 text-sm">
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Solicitante</dt>
                    <dd class="text-slate-800 font-medium mt-0.5"><?= e($request['user_name'] ?? '—') ?></dd>
                    <dd class="text-slate-500 text-xs"><?= e($request['user_email'] ?? '') ?></dd>
                </div>
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Contacto preferido</dt>
                    <dd class="text-slate-800 mt-0.5"><?= e($request['preferred_contact']) ?></dd>
                </div>
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Fecha del incidente</dt>
                    <dd class="text-slate-800 mt-0.5"><?= fechaCorta($request['incident_date']) ?></dd>
                </div>
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Ubicación</dt>
                    <dd class="text-slate-800 mt-0.5">
                        <?= e(trim(($request['municipio'] ?? '') . ' ' . ($request['estado_mexico'] ?? ''))) ?: '—' ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Recibida</dt>
                    <dd class="text-slate-800 mt-0.5"><?= fechaHora($request['created_at']) ?></dd>
                </div>
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Consentimiento de privacidad</dt>
                    <dd class="text-slate-800 mt-0.5"><?= fechaHora($request['privacy_consent_at']) ?></dd>
                </div>
            </dl>
        </div>

        <!-- Historial -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 class="font-bold text-slate-800 mb-4">Historial del caso</h3>
            <?php if (empty($updates)): ?>
                <p class="text-slate-400 text-sm">Todavía no hay movimientos registrados.</p>
            <?php else: ?>
            <ol class="space-y-4">
                <?php foreach ($updates as $u): ?>
                <li class="flex gap-3">
                    <span class="w-2 h-2 rounded-full mt-2 shrink-0 <?= $u['visible_to_user'] ? 'bg-brand-500' : 'bg-slate-300' ?>"></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="badge bg-slate-100 text-slate-600"><?= e(str_replace('_', ' ', $u['update_type'])) ?></span>
                            <?php if (!$u['visible_to_user']): ?>
                                <span class="badge bg-amber-50 text-amber-700 border border-amber-200">Interna</span>
                            <?php endif; ?>
                            <span class="text-xs text-slate-400"><?= fechaHora($u['created_at']) ?> · <?= e($u['author_name'] ?? 'Sistema') ?></span>
                        </div>
                        <p class="text-sm text-slate-700 mt-1 whitespace-pre-line"><?= e($u['message']) ?></p>
                        <?php if ($u['previous_status'] || $u['new_status']): ?>
                        <p class="text-xs text-slate-400 mt-1"><?= e($u['previous_status']) ?> &rarr; <?= e($u['new_status']) ?></p>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ol>
            <?php endif; ?>
        </div>
    </div>

    <!-- Acciones -->
    <div class="space-y-5">
        <!-- Asignación -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-800 mb-1">Asesor asignado</h3>
            <p class="text-sm <?= $request['asesor_name'] ? 'text-slate-600' : 'text-amber-600 font-medium' ?> mb-4">
                <?= $request['asesor_name'] ? e($request['asesor_name']) : 'Sin asignar' ?>
            </p>
            <form method="POST" action="<?= BASE_URL ?>/requests/<?= (int) $request['id'] ?>/assign" class="space-y-3">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(Auth::generateCsrf()) ?>">
                <select name="assigned_to" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
                    <option value="0">— Sin asignar —</option>
                    <?php foreach ($advisors as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= (int) $request['assigned_to'] === (int) $a['id'] ? 'selected' : '' ?>>
                        <?= e($a['name']) ?> (<?= (int) $a['open_cases'] ?> abiertos)
                    </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="reason" required placeholder="Motivo de la asignación"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                    Guardar asignación
                </button>
            </form>
        </div>

        <!-- Cambio de estado -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-800 mb-1">Cambiar estado</h3>
            <p class="text-xs text-slate-400 mb-4">Sólo se muestran las transiciones válidas desde <strong><?= e($request['status']) ?></strong>.</p>
            <?php if (empty($allowed)): ?>
                <p class="text-sm text-slate-500 bg-slate-50 rounded-lg px-3 py-2.5">
                    Este caso está en un estado final. No admite más cambios.
                </p>
            <?php else: ?>
            <form method="POST" action="<?= BASE_URL ?>/requests/<?= (int) $request['id'] ?>/status" class="space-y-3">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(Auth::generateCsrf()) ?>">
                <select name="status" id="statusSelect" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
                    <option value="">Selecciona el nuevo estado</option>
                    <?php foreach ($allowed as $estado): ?>
                    <option value="<?= $estado ?>"><?= e(str_replace('_', ' ', $estado)) ?></option>
                    <?php endforeach; ?>
                </select>
                <textarea name="message" rows="3" placeholder="Comentario (obligatorio al cerrar)"
                          class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm resize-y"></textarea>
                <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                    Actualizar estado
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Mensaje / nota -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-800 mb-4">Agregar comentario</h3>
            <form method="POST" action="<?= BASE_URL ?>/requests/<?= (int) $request['id'] ?>/note" class="space-y-3">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(Auth::generateCsrf()) ?>">
                <textarea name="message" rows="3" required placeholder="Escribe el mensaje"
                          class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm resize-y"></textarea>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="internal" value="1" class="w-4 h-4 rounded border-slate-300 text-brand-500 focus:ring-brand-400">
                    Nota interna (no visible para la persona usuaria)
                </label>
                <button type="submit" class="w-full bg-white border border-brand-200 hover:border-brand-400 text-brand-700 py-2 rounded-lg text-sm font-semibold transition-colors">
                    Agregar
                </button>
            </form>
        </div>
    </div>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
