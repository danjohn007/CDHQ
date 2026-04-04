<?php
// Shared form fields for create/edit user
$u = $user ?? [];
function field(string $label, string $name, string $type = 'text', $val = '', bool $required = false): void {
    $id  = htmlspecialchars($name);
    $val = htmlspecialchars((string)$val);
    $req = $required ? 'required' : '';
    echo "<div class='mb-4'>
        <label for='{$id}' class='block text-sm font-medium text-gray-700 mb-1'>{$label}" . ($required ? ' <span class=\"text-red-500\">*</span>' : '') . "</label>
        <input type='{$type}' id='{$id}' name='{$id}' value='{$val}' {$req}
               class='w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition'>
    </div>";
}
?>
<?php field('Nombre completo', 'name', 'text', $u['name'] ?? '', true) ?>
<?php field('Correo electrónico', 'email', 'email', $u['email'] ?? '', true) ?>
<?php field('Contraseña', 'password', 'password', '', !isset($u['id'])) ?>
<?php if (isset($u['id'])): ?><p class="text-xs text-gray-400 -mt-3 mb-4">Deja en blanco para no cambiar la contraseña.</p><?php endif; ?>

<div class="mb-4">
    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Rol <span class="text-red-500">*</span></label>
    <select id="role" name="role" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition">
        <?php foreach ($roles as $r): ?>
        <option value="<?= htmlspecialchars($r) ?>" <?= ($u['role'] ?? '') === $r ? 'selected' : '' ?>>
            <?= htmlspecialchars($r) ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="mb-4 flex items-center gap-2">
    <input type="checkbox" id="active" name="active" value="1"
           <?= !isset($u['id']) || !empty($u['active']) ? 'checked' : '' ?>
           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
    <label for="active" class="text-sm text-gray-700">Usuario activo</label>
</div>
