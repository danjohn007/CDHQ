<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>
<?php $csrf = Auth::generateCsrf(); ?>

<!-- Qué está viendo la gente en la app ahora mismo -->
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Visibles en la app</p>
        <p class="text-2xl font-bold text-leaf-700 mt-1"><?= (int) $contadores['publicadas'] ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">En el carrusel de inicio</p>
        <p class="text-2xl font-bold text-brand-700 mt-1">
            <?= (int) $contadores['destacadas'] ?>
            <span class="text-sm font-normal text-slate-400">/ <?= ContentPost::MAX_DESTACADAS ?></span>
        </p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Borradores</p>
        <p class="text-2xl font-bold text-amber-600 mt-1"><?= (int) $contadores['borradores'] ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Eventos próximos</p>
        <p class="text-2xl font-bold text-slate-700 mt-1"><?= (int) $contadores['eventos_proximos'] ?></p>
    </div>
</div>

<div class="bg-brand-50 border border-brand-100 rounded-2xl px-5 py-4 mb-5 flex gap-3 text-sm text-brand-900">
    <i class="fas fa-mobile-screen text-brand-500 mt-0.5"></i>
    <p class="leading-relaxed">
        Lo que publiques aquí aparece en la aplicación móvil <strong>en cuanto se guarda</strong>: en la pestaña
        <strong>Novedades</strong>, y si lo marcas como destacado, también en el carrusel
        <strong>«Novedades destacadas»</strong> de la pantalla de inicio. Una fecha de publicación futura
        programa la aparición; mientras tanto nadie la ve.
    </p>
</div>

<!-- Filtros -->
<form method="GET" action="<?= BASE_URL ?>/content" class="bg-white rounded-2xl border border-slate-200 p-4 mb-5">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input type="text" name="q" value="<?= e($filtros['q']) ?>" placeholder="Título o resumen"
               class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 focus:border-transparent outline-none">
        <select name="type" class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-400 outline-none">
            <option value="">Noticias y eventos</option>
            <option value="NOTICIA" <?= $filtros['type'] === 'NOTICIA' ? 'selected' : '' ?>>Sólo noticias</option>
            <option value="EVENTO"  <?= $filtros['type'] === 'EVENTO'  ? 'selected' : '' ?>>Sólo eventos</option>
        </select>
        <select name="status" class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-400 outline-none">
            <option value="">Cualquier estado</option>
            <option value="PUBLICADA" <?= $filtros['status'] === 'PUBLICADA' ? 'selected' : '' ?>>Publicadas</option>
            <option value="BORRADOR"  <?= $filtros['status'] === 'BORRADOR'  ? 'selected' : '' ?>>Borradores</option>
        </select>
        <div class="flex gap-2">
            <label class="flex items-center gap-2 text-sm text-slate-600 px-2">
                <input type="checkbox" name="featured" value="1" <?= $filtros['featured'] ? 'checked' : '' ?>
                       class="w-4 h-4 rounded border-slate-300 text-brand-500 focus:ring-brand-400">
                Destacadas
            </label>
            <button type="submit" class="ml-auto bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                Filtrar
            </button>
        </div>
    </div>
</form>

<div class="flex justify-end mb-4">
    <a href="<?= BASE_URL ?>/content/create"
       class="bg-leaf-500 hover:bg-leaf-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">
        <i class="fas fa-plus mr-1"></i> Nueva publicación
    </a>
</div>

<!-- Listado -->
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <?php if (empty($posts)): ?>
        <div class="py-16 text-center">
            <i class="fas fa-newspaper text-4xl text-slate-200"></i>
            <p class="text-slate-500 mt-4">No hay publicaciones que coincidan con el filtro.</p>
            <?php if ($filtros['q'] || $filtros['type'] || $filtros['status'] || $filtros['featured']): ?>
            <a href="<?= BASE_URL ?>/content" class="text-brand-600 text-sm hover:underline mt-2 inline-block">Quitar filtros</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full data-table text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr><th>Tipo</th><th>Título</th><th>Estado</th><th>En la app</th><th>Fecha</th><th class="text-right">Acciones</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($posts as $p): ?>
                <?php
                    $esEvento  = $p['type'] === 'EVENTO';
                    $publicada = $p['status'] === 'PUBLICADA';
                    $programada = $publicada && $p['published_at'] && strtotime($p['published_at']) > time();
                    $visible   = $publicada && !$programada && $p['published_at'];
                ?>
                <tr>
                    <td class="whitespace-nowrap">
                        <span class="badge <?= $esEvento ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-brand-50 text-brand-700 border border-brand-200' ?>">
                            <i class="fas <?= $esEvento ? 'fa-calendar-day' : 'fa-newspaper' ?> mr-1"></i>
                            <?= $esEvento ? 'Evento' : 'Noticia' ?>
                        </span>
                    </td>
                    <td class="font-medium text-slate-800 max-w-sm">
                        <div class="flex items-center gap-2">
                            <?php if ($p['featured']): ?>
                                <i class="fas fa-star text-amber-400" title="Destacada en el inicio de la app"></i>
                            <?php endif; ?>
                            <span class="truncate"><?= e($p['title']) ?></span>
                        </div>
                        <?php if ($esEvento && $p['event_start']): ?>
                            <p class="text-xs text-slate-400 mt-0.5">
                                <i class="fas fa-clock"></i> <?= fechaHora($p['event_start']) ?>
                                <?php if ($p['event_location']): ?> · <?= e($p['event_location']) ?><?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </td>
                    <td><?= estadoBadge($p['status']) ?></td>
                    <td class="whitespace-nowrap">
                        <?php if ($visible): ?>
                            <span class="badge bg-leaf-50 text-leaf-700 border border-leaf-200">Visible</span>
                        <?php elseif ($programada): ?>
                            <span class="badge bg-brand-50 text-brand-700 border border-brand-200">Programada</span>
                        <?php else: ?>
                            <span class="badge bg-slate-100 text-slate-500 border border-slate-200">Oculta</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-slate-400 whitespace-nowrap text-xs">
                        <?= fechaHora($p['published_at'] ?: $p['updated_at']) ?>
                    </td>
                    <td class="text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1">
                            <form method="POST" action="<?= BASE_URL ?>/content/<?= (int) $p['id'] ?>/featured" class="inline">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrf) ?>">
                                <button type="submit" title="<?= $p['featured'] ? 'Quitar de destacadas' : 'Destacar en el inicio de la app' ?>"
                                        class="px-2 py-1.5 rounded-lg hover:bg-slate-100 <?= $p['featured'] ? 'text-amber-500' : 'text-slate-300' ?>">
                                    <i class="fas fa-star"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?= BASE_URL ?>/content/<?= (int) $p['id'] ?>/status" class="inline">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrf) ?>">
                                <button type="submit"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold <?= $publicada ? 'text-slate-600 hover:bg-slate-100' : 'text-leaf-700 hover:bg-leaf-50' ?>">
                                    <?= $publicada ? 'Retirar' : 'Publicar' ?>
                                </button>
                            </form>
                            <a href="<?= BASE_URL ?>/content/<?= (int) $p['id'] ?>/edit"
                               class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-brand-600 hover:bg-brand-50">Editar</a>
                            <form method="POST" action="<?= BASE_URL ?>/content/<?= (int) $p['id'] ?>/archive" class="inline"
                                  onsubmit="return confirm('Se archivará «<?= e(addslashes($p['title'])) ?>» y dejará de verse en la app. El registro se conserva. ¿Continuar?');">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrf) ?>">
                                <button type="submit" title="Archivar"
                                        class="px-2 py-1.5 rounded-lg text-slate-300 hover:text-red-600 hover:bg-red-50">
                                    <i class="fas fa-box-archive"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?= paginacion($page, $pages, array_filter($filtros)) ?>
<p class="text-center text-xs text-slate-400 mt-3"><?= (int) $total ?> publicaciones en total</p>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
