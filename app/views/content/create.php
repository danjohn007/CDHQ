<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<div class="mb-5">
    <a href="<?= BASE_URL ?>/content" class="text-sm text-slate-500 hover:text-slate-700">
        <i class="fas fa-arrow-left mr-1"></i> Volver a noticias y eventos
    </a>
</div>

<?php
$accion    = BASE_URL . '/content/create';
$image_url = null;
require VIEWS_PATH . '/content/_form.php';
?>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
