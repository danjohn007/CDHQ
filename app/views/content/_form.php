<?php
/**
 * Formulario de noticia / evento.
 *
 * Espera:
 *   $post      array|null  Publicación existente, o null si es alta
 *   $accion    string      URL de destino del formulario
 *   $image_url string|null Portada actual, si la hay
 *
 * Los campos de evento se muestran u ocultan según el tipo elegido, pero
 * siempre se envían: el servidor es quien decide anularlos cuando el tipo es
 * NOTICIA, para que ocultar algo en pantalla nunca sea la única defensa.
 */
$esEdicion = !empty($post);
$tipo      = $post['type']   ?? 'NOTICIA';
$estado    = $post['status'] ?? 'BORRADOR';

/** Convierte un DATETIME de MySQL al formato que espera <input datetime-local> */
$paraInput = static function (?string $fecha): string {
    if (empty($fecha)) return '';
    $marca = strtotime($fecha);
    return $marca ? date('Y-m-d\TH:i', $marca) : '';
};
?>

<form method="POST" action="<?= e($accion) ?>" enctype="multipart/form-data" class="grid lg:grid-cols-3 gap-5">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(Auth::generateCsrf()) ?>">

    <!-- ── Contenido ──────────────────────────────────────────────────────── -->
    <div class="lg:col-span-2 space-y-5">

        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <label class="block text-xs font-medium text-slate-500 mb-2">¿Qué vas a publicar?</label>
            <?php /* El resaltado va en CSS propio y no en clases de Tailwind:
                     esta plantilla usa el CDN, que sólo genera las clases
                     presentes en el HTML inicial. Una clase añadida por JS
                     después se quedaría sin estilos. */ ?>
            <style>
                .tipo-opcion { border-color: #e2e8f0; transition: background-color .15s, border-color .15s; }
                .tipo-opcion[data-activo="1"][data-color="brand"] { border-color: #38bdf8; background-color: #f0f9ff; }
                .tipo-opcion[data-activo="1"][data-color="amber"] { border-color: #fbbf24; background-color: #fffbeb; }
            </style>
            <div class="grid grid-cols-2 gap-3 mb-5">
                <label class="tipo-opcion cursor-pointer border rounded-xl p-3 flex items-center gap-3"
                       data-color="brand" data-activo="<?= $tipo === 'NOTICIA' ? '1' : '0' ?>">
                    <input type="radio" name="type" value="NOTICIA" <?= $tipo === 'NOTICIA' ? 'checked' : '' ?>
                           class="w-4 h-4 text-brand-500 focus:ring-brand-400">
                    <span>
                        <span class="block text-sm font-semibold text-slate-800"><i class="fas fa-newspaper mr-1 text-brand-500"></i> Noticia</span>
                        <span class="block text-xs text-slate-500 mt-0.5">Comunicado o campaña</span>
                    </span>
                </label>
                <label class="tipo-opcion cursor-pointer border rounded-xl p-3 flex items-center gap-3"
                       data-color="amber" data-activo="<?= $tipo === 'EVENTO' ? '1' : '0' ?>">
                    <input type="radio" name="type" value="EVENTO" <?= $tipo === 'EVENTO' ? 'checked' : '' ?>
                           class="w-4 h-4 text-amber-500 focus:ring-amber-400">
                    <span>
                        <span class="block text-sm font-semibold text-slate-800"><i class="fas fa-calendar-day mr-1 text-amber-500"></i> Evento</span>
                        <span class="block text-xs text-slate-500 mt-0.5">Con fecha, sede y registro</span>
                    </span>
                </label>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Título <span class="text-slate-400">(5 a 180 caracteres)</span></label>
                    <input type="text" name="title" required minlength="5" maxlength="180"
                           value="<?= e($post['title'] ?? '') ?>"
                           placeholder="Foro estatal por los derechos humanos"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">
                        Resumen <span class="text-slate-400">(10 a 350 caracteres · es lo que se lee en la tarjeta de la app)</span>
                    </label>
                    <textarea name="summary" required minlength="10" maxlength="350" rows="3"
                              placeholder="Una o dos frases que expliquen de qué trata."
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm resize-y focus:ring-2 focus:ring-brand-400 outline-none"><?= e($post['summary'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">
                        Contenido <span class="text-slate-400">(mínimo 20 caracteres · texto corrido, sin HTML)</span>
                    </label>
                    <textarea name="body" required minlength="20" maxlength="100000" rows="14"
                              placeholder="Desarrolla aquí la nota completa. Separa los párrafos con un renglón en blanco."
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm resize-y leading-relaxed focus:ring-2 focus:ring-brand-400 outline-none"><?= e($post['body'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- ── Datos del evento ───────────────────────────────────────────── -->
        <div id="campos-evento" class="bg-white rounded-2xl border border-slate-200 p-5 <?= $tipo === 'EVENTO' ? '' : 'hidden' ?>">
            <h3 class="font-bold text-slate-800 mb-1">Datos del evento</h3>
            <p class="text-xs text-slate-500 mb-4">La app muestra la fecha en el calendario de la tarjeta y la sede debajo del título.</p>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Inicio <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="event_start" value="<?= e($paraInput($post['event_start'] ?? null)) ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-400 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Fin <span class="text-slate-400">(opcional)</span></label>
                    <input type="datetime-local" name="event_end" value="<?= e($paraInput($post['event_end'] ?? null)) ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-400 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Sede</label>
                    <input type="text" name="event_location" maxlength="180" value="<?= e($post['event_location'] ?? '') ?>"
                           placeholder="Auditorio del Consejo"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-400 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Enlace de registro <span class="text-slate-400">(opcional)</span></label>
                    <input type="url" name="registration_url" maxlength="500" value="<?= e($post['registration_url'] ?? '') ?>"
                           placeholder="https://…"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-400 outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Dirección</label>
                    <input type="text" name="event_address" maxlength="300" value="<?= e($post['event_address'] ?? '') ?>"
                           placeholder="Av. Constituyentes 100, Centro, Querétaro"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-400 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Latitud <span class="text-slate-400">(opcional)</span></label>
                    <input type="text" inputmode="decimal" name="latitude" value="<?= e($post['latitude'] ?? '') ?>" placeholder="20.5888"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-400 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Longitud <span class="text-slate-400">(opcional)</span></label>
                    <input type="text" inputmode="decimal" name="longitude" value="<?= e($post['longitude'] ?? '') ?>" placeholder="-100.3899"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-400 outline-none">
                </div>
            </div>
        </div>
    </div>

    <!-- ── Publicación ────────────────────────────────────────────────────── -->
    <div class="space-y-5">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 lg:sticky lg:top-4">
            <h3 class="font-bold text-slate-800 mb-4">Publicación</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Estado</label>
                    <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-400 outline-none">
                        <option value="BORRADOR"  <?= $estado === 'BORRADOR'  ? 'selected' : '' ?>>Borrador — no se ve en la app</option>
                        <option value="PUBLICADA" <?= $estado === 'PUBLICADA' ? 'selected' : '' ?>>Publicada — visible en la app</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">
                        Fecha de publicación <span class="text-slate-400">(opcional)</span>
                    </label>
                    <input type="datetime-local" name="published_at" value="<?= e($paraInput($post['published_at'] ?? null)) ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 outline-none">
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                        Déjala vacía para publicar de inmediato. Una fecha futura programa la aparición en la app.
                    </p>
                </div>

                <label class="flex items-start gap-2 text-sm text-slate-600 pt-1">
                    <input type="checkbox" name="featured" value="1" <?= !empty($post['featured']) ? 'checked' : '' ?>
                           class="w-4 h-4 mt-0.5 rounded border-slate-300 text-brand-500 focus:ring-brand-400">
                    <span>
                        Destacar en el inicio de la app
                        <span class="block text-xs text-slate-400 mt-0.5">
                            Entra al carrusel «Novedades destacadas». Sólo se alcanzan a ver las
                            <?= ContentPost::MAX_DESTACADAS ?> más recientes.
                        </span>
                    </span>
                </label>
            </div>

            <button type="submit" class="w-full mt-5 bg-brand-500 hover:bg-brand-600 text-white py-2.5 rounded-lg text-sm font-semibold transition-colors">
                <?= $esEdicion ? 'Guardar cambios' : 'Crear publicación' ?>
            </button>
            <a href="<?= BASE_URL ?>/content" class="block text-center text-sm text-slate-500 hover:text-slate-700 mt-3">Cancelar</a>
        </div>

        <!-- ── Portada ────────────────────────────────────────────────────── -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-800 mb-1">Imagen de portada</h3>
            <p class="text-xs text-slate-500 mb-4">
                JPG, PNG o WEBP, máximo 8 MB. Se recorta apaisada en la app; se recomienda horizontal.
            </p>

            <?php if (!empty($image_url)): ?>
                <img src="<?= e($image_url) ?>" alt="Portada actual"
                     class="w-full h-36 object-cover rounded-xl border border-slate-200 mb-3">
            <?php else: ?>
                <div class="w-full h-36 rounded-xl border border-dashed border-slate-300 bg-slate-50 flex flex-col items-center justify-center mb-3">
                    <i class="fas fa-image text-3xl text-slate-300"></i>
                    <p class="text-xs text-slate-400 mt-2">Sin portada</p>
                </div>
            <?php endif; ?>

            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                   class="w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                          file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">

            <?php if (!$esEdicion): ?>
                <p class="text-xs text-slate-400 mt-2">Puedes subirla ahora o después de crear la publicación.</p>
            <?php endif; ?>
        </div>
    </div>
</form>

<?php if ($esEdicion && !empty($image_url)): ?>
<!-- Fuera del formulario principal: los formularios no pueden anidarse -->
<form method="POST" action="<?= BASE_URL ?>/content/<?= (int) $post['id'] ?>/image/delete" class="mt-3 text-right"
      onsubmit="return confirm('¿Quitar la imagen de portada?');">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(Auth::generateCsrf()) ?>">
    <button type="submit" class="text-xs text-red-600 hover:underline">Quitar imagen de portada</button>
</form>
<?php endif; ?>

<script>
// Muestra los datos de evento sólo cuando aplican, y resalta la opción elegida.
(function () {
    var camposEvento = document.getElementById('campos-evento');
    var opciones     = document.querySelectorAll('input[name="type"]');

    function actualizar() {
        var esEvento = document.querySelector('input[name="type"]:checked').value === 'EVENTO';
        camposEvento.classList.toggle('hidden', !esEvento);

        opciones.forEach(function (opcion) {
            opcion.closest('.tipo-opcion').dataset.activo = opcion.checked ? '1' : '0';
        });

        // El inicio sólo es obligatorio para eventos; si no, el navegador
        // bloquearía el envío de una noticia por un campo oculto vacío.
        document.querySelector('input[name="event_start"]').required = esEvento;
    }

    opciones.forEach(function (opcion) { opcion.addEventListener('change', actualizar); });
    actualizar();
})();
</script>
