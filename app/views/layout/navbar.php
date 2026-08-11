<?php
$currentUser = $current_user ?? Auth::currentUser();
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/');
// Quita el prefijo de instalación para detectar el enlace activo
$baseStripped = ltrim(str_replace(
    rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?? '', '/'), '', '/' . $currentPath
), '/');
if (strpos($baseStripped, 'index.php') === 0) {
    $baseStripped = ltrim(substr($baseStripped, strlen('index.php')), '/');
}

function navLink(string $href, string $label, string $icon, string $current, int $badge = 0): string {
    $path   = ltrim($href, '/');
    $active = $path !== '' && ($current === $path || strpos($current, $path . '/') === 0);
    $class  = $active ? 'sidebar-link active' : 'sidebar-link';
    $chip   = $badge > 0
        ? '<span class="ml-auto badge bg-leaf-500 text-white">' . $badge . '</span>'
        : '';
    return '<a href="' . BASE_URL . '/' . $href . '" class="' . $class . '">'
         . '<span class="w-5 flex-shrink-0 text-center">' . $icon . '</span>'
         . '<span class="truncate">' . htmlspecialchars($label) . '</span>'
         . $chip
         . '</a>';
}
?>
<div class="flex h-screen overflow-hidden">
<!-- ── Barra lateral ────────────────────────────────────────────────────── -->
<aside id="sidebar" class="w-64 text-white flex flex-col flex-shrink-0 transition-transform duration-200 z-30
    fixed inset-y-0 left-0 lg:static lg:translate-x-0 -translate-x-full"
    style="background:linear-gradient(180deg,#0e5c75 0%,#124b60 60%,#143f51 100%);" aria-label="Menú principal">

    <div class="px-5 py-5 border-b border-white/10">
        <img src="<?= SITE_URL ?>/CDHQ%20HORIZONTAL%20B.png" alt="CDHQ" class="h-9 w-auto">
        <p class="text-white/60 text-[11px] mt-2 tracking-wide">PORTAL ADMINISTRATIVO</p>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-0.5">
        <?= navLink('dashboard', 'Panel general', '<i class="fas fa-chart-pie fa-fw"></i>', $baseStripped) ?>

        <p class="sidebar-group">Atención</p>
        <?= navLink('requests', 'Solicitudes de apoyo', '<i class="fas fa-inbox fa-fw"></i>', $baseStripped) ?>
        <?= navLink('applications', 'Postulaciones', '<i class="fas fa-user-check fa-fw"></i>', $baseStripped) ?>

        <p class="sidebar-group">Afiliación</p>
        <?= navLink('affiliates', 'Afiliados', '<i class="fas fa-users fa-fw"></i>', $baseStripped) ?>
        <?= navLink('credentials', 'Credenciales', '<i class="fas fa-address-card fa-fw"></i>', $baseStripped) ?>
        <?= navLink('subscriptions', 'Suscripciones', '<i class="fas fa-id-card fa-fw"></i>', $baseStripped) ?>

        <p class="sidebar-group">Comunicación</p>
        <?= navLink('content', 'Noticias y eventos', '<i class="fas fa-newspaper fa-fw"></i>', $baseStripped) ?>
        <?php /* Avisos a la app: el módulo está completo y sus rutas siguen
                 activas, pero la aplicación móvil todavía no tiene forma de
                 mostrarlos —la tabla `notifications` existe, y no hay endpoint
                 ni pantalla que la lea—. Se oculta del menú para no ofrecer un
                 envío que nadie recibiría. Para reactivarlo basta descomentar
                 la línea de abajo, una vez que la app lea esa tabla.

        <?= navLink('notifications', 'Avisos a la app', '<i class="fas fa-bullhorn fa-fw"></i>', $baseStripped) ?>
        */ ?>

        <p class="sidebar-group">Finanzas</p>
        <?= navLink('orders', 'Pagos y anualidades', '<i class="fab fa-stripe-s fa-fw"></i>', $baseStripped) ?>
        <?= navLink('plans', 'Planes y precios', '<i class="fas fa-tags fa-fw"></i>', $baseStripped) ?>
        <?= navLink('financial', 'Movimientos', '<i class="fas fa-dollar-sign fa-fw"></i>', $baseStripped) ?>
        <?= navLink('reports', 'Reportes', '<i class="fas fa-chart-bar fa-fw"></i>', $baseStripped) ?>

        <p class="sidebar-group">Administración</p>
        <?= navLink('users', 'Usuarios y roles', '<i class="fas fa-user-shield fa-fw"></i>', $baseStripped) ?>
        <?= navLink('audit', 'Auditoría', '<i class="fas fa-clipboard-list fa-fw"></i>', $baseStripped) ?>
        <?= navLink('config', 'Configuración', '<i class="fas fa-cog fa-fw"></i>', $baseStripped) ?>

        <p class="sidebar-group">Público</p>
        <a href="<?= BASE_URL ?>/verificar" class="sidebar-link" target="_blank" rel="noopener">
            <span class="w-5 flex-shrink-0 text-center"><i class="fas fa-qrcode fa-fw"></i></span>
            <span class="truncate">Verificar credencial</span>
        </a>
        <a href="<?= SITE_URL ?>/" class="sidebar-link" target="_blank" rel="noopener">
            <span class="w-5 flex-shrink-0 text-center"><i class="fas fa-globe fa-fw"></i></span>
            <span class="truncate">Sitio web</span>
        </a>
    </nav>

    <div class="px-4 py-4 border-t border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-white/15 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">
                <?= htmlspecialchars(mb_strtoupper(mb_substr($currentUser['name'] ?? 'U', 0, 1))) ?>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium truncate"><?= htmlspecialchars($currentUser['name'] ?? '') ?></p>
                <p class="text-xs text-white/55 truncate"><?= htmlspecialchars($currentUser['app_role'] ?? '') ?></p>
            </div>
            <a href="<?= BASE_URL ?>/logout" class="text-white/60 hover:text-white" title="Cerrar sesión">
                <i class="fas fa-right-from-bracket"></i>
            </a>
        </div>
    </div>
</aside>

<div id="sidebar-overlay" class="hidden fixed inset-0 bg-slate-900/40 z-20 lg:hidden"></div>

<!-- ── Contenido ────────────────────────────────────────────────────────── -->
<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <header class="bg-white border-b border-slate-200 px-4 py-3 flex items-center gap-4 flex-shrink-0">
        <button id="sidebar-toggle" class="lg:hidden p-2 rounded-lg hover:bg-slate-100 text-slate-500" aria-label="Abrir menú">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-lg font-semibold text-slate-800 flex-1 truncate"><?= htmlspecialchars($title ?? '') ?></h1>
        <span class="text-sm text-slate-400 hidden sm:block"><?= date('d/m/Y') ?></span>
    </header>

    <?php if (!empty($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div data-flash class="mx-4 mt-4 px-4 py-3 rounded-xl flex items-start gap-3 text-sm <?= $flash['type'] === 'success' ? 'bg-leaf-50 border border-leaf-200 text-leaf-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
        <span class="flex-shrink-0 font-bold"><?= $flash['type'] === 'success' ? '✓' : '✗' ?></span>
        <span><?= htmlspecialchars($flash['message']) ?></span>
    </div>
    <?php endif; ?>

    <main class="flex-1 overflow-y-auto p-4 lg:p-6">
