<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<?php
$publicada  = $post['status'] === 'PUBLICADA';
$programada = $publicada && $post['published_at'] && strtotime($post['published_at']) > time();
$visible    = $publicada && !$programada && $post['published_at'];
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <a href="<?= BASE_URL ?>/content" class="text-sm text-slate-500 hover:text-slate-700">
        <i class="fas fa-arrow-left mr-1"></i> Volver a noticias y eventos
    </a>

    <div class="flex items-center gap-2">
        <?php if ($visible): ?>
            <span class="badge bg-leaf-50 text-leaf-700 border border-leaf-200">
                <i class="fas fa-circle-check mr-1"></i> Visible en la app
            </span>
        <?php elseif ($programada): ?>
            <span class="badge bg-brand-50 text-brand-700 border border-brand-200">
                <i class="fas fa-clock mr-1"></i> Programada para el <?= fechaHora($post['published_at']) ?>
            </span>
        <?php else: ?>
            <span class="badge bg-slate-100 text-slate-500 border border-slate-200">
                <i class="fas fa-eye-slash mr-1"></i> No se ve en la app
            </span>
        <?php endif; ?>

        <?php if (!empty($post['featured'])): ?>
            <span class="badge bg-amber-50 text-amber-700 border border-amber-200">
                <i class="fas fa-star mr-1"></i> Destacada
            </span>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($post['featured']) && $visible && $destacadas > ContentPost::MAX_DESTACADAS): ?>
<div class="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 mb-5 flex gap-3 text-sm text-amber-900">
    <i class="fas fa-triangle-exclamation text-amber-500 mt-0.5"></i>
    <p class="leading-relaxed">
        Hay <strong><?= (int) $destacadas ?> publicaciones destacadas</strong> visibles, pero el carrusel de la app
        sólo muestra las <strong><?= ContentPost::MAX_DESTACADAS ?> más recientes</strong>. Quita el destacado de
        alguna para asegurar que ésta se vea.
    </p>
</div>
<?php endif; ?>

<?php
$accion = BASE_URL . '/content/' . (int) $post['id'] . '/edit';
require VIEWS_PATH . '/content/_form.php';
?>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
