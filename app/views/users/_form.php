<?php
/** Formulario compartido de alta y edición de usuarios */
$esEdicion = !empty($user);
$valor = static fn(string $campo, string $def = ''): string => e($user[$campo] ?? $def);
?>
<div class="grid sm:grid-cols-2 gap-5">
    <div>
        <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Nombre completo <span class="text-red-500">*</span></label>
        <input type="text" id="name" name="name" required value="<?= $valor('name') ?>"
               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 focus:border-transparent outline-none">
    </div>
    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Correo electrónico <span class="text-red-500">*</span></label>
        <input type="email" id="email" name="email" required value="<?= $valor('email') ?>"
               class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 focus:border-transparent outline-none">
    </div>
</div>

<div class="mt-5">
    <label for="app_role" class="block text-sm font-medium text-slate-700 mb-1.5">Rol en la aplicación <span class="text-red-500">*</span></label>
    <select id="app_role" name="app_role" required
            class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-400 outline-none">
        <?php foreach (ALL_APP_ROLES as $rol): ?>
        <option value="<?= $rol ?>" <?= ($user['app_role'] ?? APP_ROLE_USUARIO) === $rol ? 'selected' : '' ?>><?= e($rol) ?></option>
        <?php endforeach; ?>
    </select>
    <ul class="mt-2 text-xs text-slate-500 space-y-1">
        <li><strong>USUARIO</strong> — sus propios datos, solicitudes, pagos y credencial. Sin acceso a este portal.</li>
        <li><strong>ASESOR</strong> — atiende casos y chats desde la app. Sin acceso a este portal.</li>
        <li><strong>ADMINISTRADOR</strong> — supervisión global. <span class="text-brand-700 font-medium">Único rol que puede entrar aquí.</span></li>
    </ul>
</div>

<div class="mt-5">
    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">
        Contraseña <?= $esEdicion ? '' : '<span class="text-red-500">*</span>' ?>
    </label>
    <input type="password" id="password" name="password" <?= $esEdicion ? '' : 'required' ?>
           minlength="8" autocomplete="new-password"
           class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 focus:border-transparent outline-none">
    <p class="text-xs text-slate-500 mt-1.5">
        <?= $esEdicion
            ? 'Déjala vacía para conservar la actual. Nunca se muestra la contraseña existente.'
            : 'Mínimo 8 caracteres. Se guarda cifrada con bcrypt.' ?>
    </p>
</div>

<?php if ($esEdicion): ?>
<div class="mt-5">
    <label for="reason" class="block text-sm font-medium text-slate-700 mb-1.5">Motivo del cambio</label>
    <input type="text" id="reason" name="reason" placeholder="Queda registrado en la bitácora de auditoría"
           class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-400 outline-none">
</div>
<?php endif; ?>
