<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<a href="<?= BASE_URL ?>/applications" class="text-sm text-brand-600 hover:underline">&larr; Volver a postulaciones</a>

<div class="grid lg:grid-cols-3 gap-5 mt-4">
    <div class="lg:col-span-2 space-y-5">
        <!-- Expediente -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-slate-800"><?= e($application['user_name'] ?? '—') ?></h2>
                    <p class="text-slate-500 text-sm"><?= e($application['user_email'] ?? '') ?></p>
                </div>
                <?= estadoBadge($application['status']) ?>
            </div>

            <dl class="grid sm:grid-cols-3 gap-4 mt-5 pt-5 border-t border-slate-100 text-sm">
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Folio de afiliado</dt>
                    <dd class="text-slate-800 font-mono mt-0.5"><?= e($application['affiliate_number'] ?? 'Sin asignar') ?></dd>
                </div>
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Enviada</dt>
                    <dd class="text-slate-800 mt-0.5"><?= fechaHora($application['submitted_at']) ?></dd>
                </div>
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Última revisión</dt>
                    <dd class="text-slate-800 mt-0.5"><?= fechaHora($application['reviewed_at']) ?></dd>
                    <?php if ($application['reviewer_name']): ?>
                    <dd class="text-slate-500 text-xs">por <?= e($application['reviewer_name']) ?></dd>
                    <?php endif; ?>
                </div>
            </dl>

            <div class="mt-5 pt-5 border-t border-slate-100 space-y-5">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Motivación</p>
                    <p class="text-slate-700 text-sm leading-relaxed whitespace-pre-line"><?= e($application['motivation']) ?: '—' ?></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Formación</p>
                    <p class="text-slate-700 text-sm leading-relaxed whitespace-pre-line"><?= e($application['education']) ?: '—' ?></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Experiencia</p>
                    <p class="text-slate-700 text-sm leading-relaxed whitespace-pre-line"><?= e($application['experience']) ?: '—' ?></p>
                </div>
                <?php if (!empty($application['review_notes'])): ?>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-1.5">Observaciones registradas</p>
                    <p class="text-amber-900 text-sm whitespace-pre-line"><?= e($application['review_notes']) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-5 pt-5 border-t border-slate-100 flex flex-wrap gap-4 text-xs text-slate-500">
                <span><i class="fas fa-check-circle <?= $application['conduct_accepted_at'] ? 'text-leaf-500' : 'text-slate-300' ?>"></i>
                      Código de conducta <?= $application['conduct_accepted_at'] ? 'aceptado' : 'pendiente' ?></span>
                <span><i class="fas fa-check-circle <?= $application['truth_accepted_at'] ? 'text-leaf-500' : 'text-slate-300' ?>"></i>
                      Declaración de verdad <?= $application['truth_accepted_at'] ? 'aceptada' : 'pendiente' ?></span>
            </div>
        </div>

        <!-- Documentos -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 class="font-bold text-slate-800 mb-4">Documentos</h3>
            <?php if (empty($documents)): ?>
                <p class="text-slate-400 text-sm">La persona postulante aún no ha subido documentos.</p>
            <?php else: ?>
            <ul class="divide-y divide-slate-100">
                <?php foreach ($documents as $d): ?>
                <li class="py-3 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate"><?= e($d['original_name']) ?></p>
                        <p class="text-xs text-slate-400">
                            <?= e($d['document_type']) ?> · <?= number_format($d['size_bytes'] / 1024, 0) ?> KB · <?= fechaCorta($d['created_at']) ?>
                        </p>
                    </div>
                    <?= estadoBadge($d['status']) ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <p class="text-xs text-slate-400 mt-4">
                <i class="fas fa-lock"></i> Los archivos se almacenan fuera de la carpeta pública; su revisión se hace desde la app.
            </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resolución -->
    <div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 sticky top-4">
            <h3 class="font-bold text-slate-800 mb-1">Resolver postulación</h3>
            <p class="text-xs text-slate-400 mb-4">
                Sólo se ofrecen las transiciones válidas desde <strong><?= e($application['status']) ?></strong>.
            </p>

            <?php if (empty($allowed)): ?>
                <div class="text-sm text-slate-600 bg-slate-50 rounded-xl px-4 py-3.5 leading-relaxed">
                    <?php if ($application['status'] === 'ACREDITADA'): ?>
                        Esta postulación ya está <strong>acreditada</strong>. Por regla del sistema no puede rechazarse
                        desde aquí: una suspensión o revocación se hace en el módulo de
                        <a href="<?= BASE_URL ?>/credentials" class="text-brand-600 hover:underline">credenciales</a>.
                    <?php elseif ($application['status'] === 'PENDIENTE_PAGO'): ?>
                        Aprobada y esperando el pago de la anualidad en la aplicación.
                        La acreditación se registra sola cuando Stripe confirma el pago.
                        <a href="<?= BASE_URL ?>/orders" class="text-brand-600 hover:underline">Ver órdenes</a>.
                    <?php else: ?>
                        Estado final: no admite más cambios.
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <form method="POST" action="<?= BASE_URL ?>/applications/<?= (int) $application['id'] ?>/decide" class="space-y-3">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(Auth::generateCsrf()) ?>">
                <select name="status" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
                    <option value="">Selecciona la resolución</option>
                    <?php foreach ($allowed as $estado): ?>
                    <option value="<?= $estado ?>"><?= e(str_replace('_', ' ', $estado)) ?></option>
                    <?php endforeach; ?>
                </select>
                <textarea name="review_notes" rows="4"
                          placeholder="Observaciones (obligatorias al rechazar o pedir correcciones)"
                          class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm resize-y"></textarea>
                <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white py-2.5 rounded-lg text-sm font-semibold transition-colors">
                    Registrar resolución
                </button>
            </form>

            <?php if (in_array('APROBADA', $allowed, true)): ?>
            <div class="mt-4 text-xs text-slate-500 bg-brand-50 border border-brand-100 rounded-xl px-3 py-2.5 leading-relaxed">
                <i class="fas fa-circle-info text-brand-500"></i>
                Al aprobar se crea el afiliado como <strong>PROSPECTO</strong> y la postulación pasa a
                <strong>PENDIENTE DE PAGO</strong>. La credencial se emite hasta que Stripe confirme la anualidad.
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
