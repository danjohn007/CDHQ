<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>
<?php
$b       = $borrador ?? [];
$destino = $b['destino'] ?? 'SEGMENTO';
?>

<div class="bg-brand-50 border border-brand-100 rounded-2xl px-5 py-4 mb-5 flex gap-3 text-sm text-brand-900">
    <i class="fas fa-bullhorn text-brand-500 mt-0.5"></i>
    <p class="leading-relaxed">
        Un aviso se guarda para <strong>cada persona del segmento</strong> y <strong>no se puede deshacer</strong>.
        Antes de enviarlo verás a cuántas personas llegará y algunos nombres, para que puedas detectar
        un segmento equivocado a tiempo.
    </p>
</div>

<div class="grid lg:grid-cols-3 gap-5">

    <!-- ── Redactar ───────────────────────────────────────────────────────── -->
    <div class="lg:col-span-2">
        <form method="POST" action="<?= BASE_URL ?>/notifications/preview" class="bg-white rounded-2xl border border-slate-200 p-5">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(Auth::generateCsrf()) ?>">
            <h3 class="font-bold text-slate-800 mb-4">Nuevo aviso</h3>

            <style>
                .destino-opcion { border-color: #e2e8f0; transition: background-color .15s, border-color .15s; }
                .destino-opcion[data-activo="1"] { border-color: #38bdf8; background-color: #f0f9ff; }
            </style>

            <div class="grid sm:grid-cols-2 gap-3 mb-5">
                <label class="destino-opcion cursor-pointer border rounded-xl p-3 flex items-center gap-3"
                       data-activo="<?= $destino === 'SEGMENTO' ? '1' : '0' ?>">
                    <input type="radio" name="destino" value="SEGMENTO" <?= $destino === 'SEGMENTO' ? 'checked' : '' ?>
                           class="w-4 h-4 text-brand-500 focus:ring-brand-400">
                    <span>
                        <span class="block text-sm font-semibold text-slate-800"><i class="fas fa-users mr-1 text-brand-500"></i> A un segmento</span>
                        <span class="block text-xs text-slate-500 mt-0.5">Aviso general</span>
                    </span>
                </label>
                <label class="destino-opcion cursor-pointer border rounded-xl p-3 flex items-center gap-3"
                       data-activo="<?= $destino === 'USUARIO' ? '1' : '0' ?>">
                    <input type="radio" name="destino" value="USUARIO" <?= $destino === 'USUARIO' ? 'checked' : '' ?>
                           class="w-4 h-4 text-brand-500 focus:ring-brand-400">
                    <span>
                        <span class="block text-sm font-semibold text-slate-800"><i class="fas fa-user mr-1 text-brand-500"></i> A una persona</span>
                        <span class="block text-xs text-slate-500 mt-0.5">Aviso individual</span>
                    </span>
                </label>
            </div>

            <!-- Segmento -->
            <div id="panel-segmento" class="mb-5 <?= $destino === 'SEGMENTO' ? '' : 'hidden' ?>">
                <label class="block text-xs font-medium text-slate-500 mb-2">Destinatarios</label>
                <div class="space-y-2">
                    <?php foreach ($segmentos as $clave => $etiqueta): ?>
                    <label class="flex items-center gap-3 border border-slate-200 rounded-xl px-3 py-2.5 cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="segmento" value="<?= e($clave) ?>"
                               <?= ($b['segmento'] ?? '') === $clave ? 'checked' : '' ?>
                               class="w-4 h-4 text-brand-500 focus:ring-brand-400">
                        <span class="text-sm text-slate-700 flex-1"><?= e($etiqueta) ?></span>
                        <span class="badge <?= ($conteos[$clave] ?? 0) > 0 ? 'bg-brand-50 text-brand-700 border border-brand-200' : 'bg-slate-100 text-slate-400 border border-slate-200' ?>">
                            <?= (int) ($conteos[$clave] ?? 0) ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Persona concreta -->
            <div id="panel-usuario" class="mb-5 <?= $destino === 'USUARIO' ? '' : 'hidden' ?>">
                <label class="block text-xs font-medium text-slate-500 mb-2">Persona destinataria</label>
                <?php if (!empty($encontrados)): ?>
                    <div class="space-y-2 mb-3">
                        <?php foreach ($encontrados as $u): ?>
                        <label class="flex items-center gap-3 border border-slate-200 rounded-xl px-3 py-2.5 cursor-pointer hover:bg-slate-50">
                            <input type="radio" name="user_id" value="<?= (int) $u['id'] ?>"
                                   <?= (int) ($b['user_id'] ?? 0) === (int) $u['id'] ? 'checked' : '' ?>
                                   class="w-4 h-4 text-brand-500 focus:ring-brand-400">
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm text-slate-800 truncate"><?= e($u['name']) ?></span>
                                <span class="block text-xs text-slate-400 truncate"><?= e($u['email']) ?></span>
                            </span>
                            <span class="badge bg-slate-100 text-slate-600 border border-slate-200"><?= e($u['app_role']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (($buscar ?? '') !== ''): ?>
                    <p class="text-sm text-slate-500 mb-3">Ninguna cuenta activa coincide con «<?= e($buscar) ?>».</p>
                <?php else: ?>
                    <p class="text-sm text-slate-500 mb-3">Busca a la persona por nombre o correo con el buscador de abajo.</p>
                <?php endif; ?>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Título <span class="text-slate-400">(5 a 180 caracteres)</span></label>
                    <input type="text" name="title" required minlength="5" maxlength="180"
                           value="<?= e($b['title'] ?? '') ?>"
                           placeholder="Suspensión de actividades por contingencia"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Mensaje <span class="text-slate-400">(10 a 2,000 caracteres)</span></label>
                    <textarea name="message" required minlength="10" maxlength="2000" rows="6"
                              placeholder="Explica con claridad qué necesita saber o hacer la persona."
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm resize-y leading-relaxed focus:ring-2 focus:ring-brand-400 outline-none"><?= e($b['message'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Enlace <span class="text-slate-400">(opcional)</span></label>
                    <input type="url" name="action_url" maxlength="500" value="<?= e($b['action_url'] ?? '') ?>"
                           placeholder="https://…"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 outline-none">
                </div>
            </div>

            <button type="submit" class="w-full mt-5 bg-brand-500 hover:bg-brand-600 text-white py-2.5 rounded-lg text-sm font-semibold transition-colors">
                Revisar antes de enviar
            </button>
        </form>

        <!-- Buscador del envío individual: va aparte porque los formularios no se anidan -->
        <form method="GET" action="<?= BASE_URL ?>/notifications" class="bg-white rounded-2xl border border-slate-200 p-4 mt-4 flex gap-2">
            <input type="text" name="buscar" value="<?= e($buscar ?? '') ?>" placeholder="Buscar persona por nombre o correo"
                   class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 outline-none">
            <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                Buscar
            </button>
        </form>
    </div>

    <!-- ── Historial ──────────────────────────────────────────────────────── -->
    <div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-800 mb-4">Avisos enviados</h3>

            <?php if (empty($historial)): ?>
                <div class="py-10 text-center">
                    <i class="fas fa-bell-slash text-3xl text-slate-200"></i>
                    <p class="text-slate-500 text-sm mt-3">Todavía no se ha enviado ningún aviso.</p>
                </div>
            <?php else: ?>
                <ul class="space-y-3">
                    <?php foreach ($historial as $h): ?>
                    <?php
                        $total  = (int) $h['destinatarios'];
                        $leidos = (int) $h['leidos'];
                        $pct    = $total > 0 ? (int) round($leidos * 100 / $total) : 0;
                    ?>
                    <li class="border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                        <p class="text-sm font-medium text-slate-800 leading-snug"><?= e($h['title']) ?></p>
                        <p class="text-xs text-slate-500 mt-1 line-clamp-2"><?= e(mb_strimwidth($h['message'], 0, 110, '…')) ?></p>
                        <div class="flex items-center gap-2 mt-2 text-xs text-slate-400">
                            <span><?= fechaHora($h['created_at']) ?></span>
                            <span>·</span>
                            <span><?= $total ?> <?= $total === 1 ? 'persona' : 'personas' ?></span>
                            <span>·</span>
                            <span title="<?= $leidos ?> de <?= $total ?> lo abrieron"><?= $pct ?>% leído</span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Muestra sólo el panel del tipo de destinatario elegido.
(function () {
    var opciones = document.querySelectorAll('input[name="destino"]');
    var paneles  = { SEGMENTO: document.getElementById('panel-segmento'),
                     USUARIO:  document.getElementById('panel-usuario') };

    function actualizar() {
        var elegido = document.querySelector('input[name="destino"]:checked').value;
        Object.keys(paneles).forEach(function (clave) {
            paneles[clave].classList.toggle('hidden', clave !== elegido);
        });
        opciones.forEach(function (opcion) {
            opcion.closest('.destino-opcion').dataset.activo = opcion.checked ? '1' : '0';
        });
    }

    opciones.forEach(function (opcion) { opcion.addEventListener('change', actualizar); });
    actualizar();
})();
</script>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
