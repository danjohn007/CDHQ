<?php
$currentUser = $current_user ?? Auth::currentUser();
$role = $currentUser['role'] ?? '';
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
// Strip base path prefix for active link detection
$baseStripped = ltrim(str_replace(
    rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/'), '', '/' . $currentPath
), '/');

function navLink(string $href, string $label, string $icon, string $current, string $base): string {
    $path = ltrim($href, '/');
    $active = ($current === $path || strpos($current, $path . '/') === 0) && $path !== '';
    $class  = $active ? 'sidebar-link active' : 'sidebar-link text-indigo-200';
    return '<a href="' . BASE_URL . '/' . $href . '" class="' . $class . '">'
         . '<span class="w-5 flex-shrink-0 text-center">' . $icon . '</span>'
         . '<span>' . htmlspecialchars($label) . '</span>'
         . '</a>';
}
?>
<div class="flex h-screen overflow-hidden">
<!-- ── Sidebar ──────────────────────────────────────────────────────────── -->
<aside id="sidebar" class="w-64 bg-indigo-800 text-white flex flex-col flex-shrink-0 transition-transform duration-200 z-30 
    fixed inset-y-0 left-0 lg:static lg:translate-x-0 -translate-x-full" aria-label="Sidebar">
    <!-- Logo -->
    <div class="px-6 py-5 border-b border-indigo-700 flex items-center gap-3">
        <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center flex-shrink-0">
            <span class="text-indigo-700 font-bold text-sm">CD</span>
        </div>
        <div>
            <p class="font-bold text-sm leading-tight">Sistema CDHQ</p>
            <p class="text-indigo-300 text-xs leading-tight">CDHQ Querétaro</p>
        </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <?= navLink('dashboard', 'Panel Principal', '<i class="fas fa-home fa-fw"></i>', $baseStripped, BASE_URL) ?>

        <?php if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS, ROLE_EJECUTIVO_PROSPECTOS])): ?>
        <p class="px-4 pt-3 pb-1 text-xs font-semibold uppercase text-indigo-400 tracking-wider">Afiliados</p>
        <?= navLink('affiliates', 'Afiliados', '<i class="fas fa-users fa-fw"></i>', $baseStripped, BASE_URL) ?>
        <?= navLink('subscriptions', 'Membresías', '<i class="fas fa-id-card fa-fw"></i>', $baseStripped, BASE_URL) ?>
        <?= navLink('credentials/1', 'Credenciales', '<i class="fas fa-address-card fa-fw"></i>', $baseStripped, BASE_URL) ?>
        <?php endif; ?>

        <?php if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN_FINANCIERO])): ?>
        <p class="px-4 pt-3 pb-1 text-xs font-semibold uppercase text-indigo-400 tracking-wider">Finanzas</p>
        <?= navLink('financial', 'Finanzas', '<i class="fas fa-dollar-sign fa-fw"></i>', $baseStripped, BASE_URL) ?>
        <?= navLink('reports', 'Reportes', '<i class="fas fa-chart-bar fa-fw"></i>', $baseStripped, BASE_URL) ?>
        <?php endif; ?>

        <?php if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS, ROLE_COORD_EVENTOS, ROLE_AFILIADO])): ?>
        <p class="px-4 pt-3 pb-1 text-xs font-semibold uppercase text-indigo-400 tracking-wider">Actividades</p>
        <?= navLink('events', 'Eventos', '<i class="fas fa-calendar-alt fa-fw"></i>', $baseStripped, BASE_URL) ?>
        <?php endif; ?>

        <?php if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS, ROLE_EJECUTIVO_PROSPECTOS])): ?>
        <p class="px-4 pt-3 pb-1 text-xs font-semibold uppercase text-indigo-400 tracking-wider">Prospección</p>
        <?= navLink('prospects', 'Prospectos', '<i class="fas fa-user-plus fa-fw"></i>', $baseStripped, BASE_URL) ?>
        <?php endif; ?>

        <?php if (in_array($role, [ROLE_SUPER_ADMIN, ROLE_ADMIN_MEMBRESIAS])): ?>
        <p class="px-4 pt-3 pb-1 text-xs font-semibold uppercase text-indigo-400 tracking-wider">Organización</p>
        <?= navLink('council', 'Consejo', '<i class="fas fa-landmark fa-fw"></i>', $baseStripped, BASE_URL) ?>
        <?php endif; ?>

        <?php if ($role === ROLE_SUPER_ADMIN): ?>
        <p class="px-4 pt-3 pb-1 text-xs font-semibold uppercase text-indigo-400 tracking-wider">Administración</p>
        <?= navLink('users', 'Usuarios', '<i class="fas fa-user fa-fw"></i>', $baseStripped, BASE_URL) ?>
        <?= navLink('config', 'Configuración', '<i class="fas fa-cog fa-fw"></i>', $baseStripped, BASE_URL) ?>
        <?php endif; ?>
    </nav>

    <!-- User info at bottom -->
    <div class="px-4 py-4 border-t border-indigo-700">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-indigo-600 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">
                <?= htmlspecialchars(mb_strtoupper(mb_substr($currentUser['name'] ?? 'U', 0, 1))) ?>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium truncate"><?= htmlspecialchars($currentUser['name'] ?? '') ?></p>
                <p class="text-xs text-indigo-300 truncate"><?= htmlspecialchars($role) ?></p>
            </div>
            <a href="<?= BASE_URL ?>/logout" class="text-indigo-300 hover:text-white ml-auto" title="Cerrar sesión">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </a>
        </div>
    </div>
</aside>

<!-- ── Main Content Area ──────────────────────────────────────────────────── -->
<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <!-- Top bar -->
    <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-4 flex-shrink-0">
        <!-- Mobile menu button -->
        <button id="sidebar-toggle" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 text-gray-500" 
                aria-label="Abrir menú">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <h1 class="text-lg font-semibold text-gray-800 flex-1"><?= htmlspecialchars($title ?? '') ?></h1>
        <span class="text-sm text-gray-500 hidden sm:block"><?= date('d/m/Y') ?></span>
    </header>

    <!-- Flash messages -->
    <?php if (!empty($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="mx-4 mt-4 px-4 py-3 rounded-lg flex items-start gap-3 <?= $flash['type'] === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
        <span class="flex-shrink-0 font-bold"><?= $flash['type'] === 'success' ? '✓' : '✗' ?></span>
        <span><?= htmlspecialchars($flash['message']) ?></span>
    </div>
    <?php endif; ?>

    <!-- Page content -->
    <main class="flex-1 overflow-y-auto p-4 lg:p-6">
