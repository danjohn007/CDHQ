<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-6">Nuevo Afiliado</h2>
        <form method="POST" action="<?= $base_url ?>/affiliates/create">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(Auth::generateCsrf()) ?>">
            <?php $affiliate = []; include __DIR__ . '/_form.php'; ?>
            <div class="flex gap-3 mt-6">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Registrar Afiliado
                </button>
                <a href="<?= $base_url ?>/affiliates" class="flex-1 text-center border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
