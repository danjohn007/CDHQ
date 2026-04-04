<?php
// Shared form fields for create/edit affiliate
$a = $affiliate ?? [];
$v = fn(string $k) => htmlspecialchars((string)($a[$k] ?? ''));
?>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre(s) <span class="text-red-500">*</span></label>
        <input type="text" name="first_name" value="<?= $v('first_name') ?>" required
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Apellidos <span class="text-red-500">*</span></label>
        <input type="text" name="last_name" value="<?= $v('last_name') ?>" required
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
        <input type="email" name="email" value="<?= $v('email') ?>"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
        <input type="tel" name="phone" value="<?= $v('phone') ?>"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
    </div>
</div>
<div class="mt-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
    <input type="text" name="address" value="<?= $v('address') ?>"
           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
</div>
<div class="mt-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
    <textarea name="notes" rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm resize-none"><?= $v('notes') ?></textarea>
</div>
