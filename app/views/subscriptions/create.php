<?php require VIEWS_PATH . '/layout/header.php'; ?>
<?php require VIEWS_PATH . '/layout/navbar.php'; ?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-6">Nueva Membresía</h2>
        <form method="POST" action="<?= $base_url ?>/subscriptions/create">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars(Auth::generateCsrf()) ?>">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Afiliado <span class="text-red-500">*</span></label>
                <select name="affiliate_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="">Seleccionar afiliado…</option>
                    <?php $presel = (int)($_GET['affiliate_id'] ?? 0); ?>
                    <?php foreach ($affiliates as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $a['id']==$presel?'selected':'' ?>>
                        <?= htmlspecialchars($a['affiliate_number'] . ' – ' . $a['first_name'] . ' ' . $a['last_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Plan de Membresía <span class="text-red-500">*</span></label>
                <select name="plan_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="">Seleccionar plan…</option>
                    <?php foreach ($plans as $p): ?>
                    <option value="<?= $p['id'] ?>">
                        <?= htmlspecialchars($p['name']) ?> – $<?= number_format($p['price'],2) ?> (<?= $p['duration_days'] ?> días)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Inicio <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto Pagado ($)</label>
                    <input type="number" step="0.01" min="0" name="amount" placeholder="0.00"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Método de Pago</label>
                    <select name="method" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option>EFECTIVO</option>
                        <option>TRANSFERENCIA</option>
                        <option>TARJETA</option>
                        <option>PAYPAL</option>
                        <option>PENDIENTE</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Referencia</label>
                    <input type="text" name="reference" placeholder="Núm. de referencia"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Registrar Membresía
                </button>
                <a href="<?= $base_url ?>/subscriptions" class="flex-1 text-center border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php require VIEWS_PATH . '/layout/footer.php'; ?>
