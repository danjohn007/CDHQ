<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<a href="<?= BASE_URL ?>/users" class="text-sm text-brand-600 hover:underline">&larr; Volver a usuarios</a>

<form method="POST" action="<?= BASE_URL ?>/users/create" class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 mt-4 max-w-3xl">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e(Auth::generateCsrf()) ?>">
    <h2 class="text-lg font-bold text-slate-800 mb-6">Datos de la cuenta</h2>
    <?php require VIEWS_PATH . '/users/_form.php'; ?>
    <div class="flex gap-3 mt-8 pt-6 border-t border-slate-100">
        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors">
            Crear usuario
        </button>
        <a href="<?= BASE_URL ?>/users" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
            Cancelar
        </a>
    </div>
</form>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
