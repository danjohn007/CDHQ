<?php
// ─── Database Configuration ───────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'cdhq_sistema');
define('DB_USER', 'cdhq_sistema');
define('DB_PASS', '!(6IS_X3k,EO');
define('DB_CHARSET', 'utf8mb4');

// ─── URL style ────────────────────────────────────────────────────────────────
// true  → URLs limpias (/sistema/login). Requiere .htaccess + mod_rewrite.
// false → URLs por front controller (/sistema/index.php/login). Funciona SIEMPRE,
//         aunque el hosting ignore el .htaccess o no tenga mod_rewrite.
//
// Si al entrar a /sistema/login el servidor responde 404, cambia esto a false:
// todos los enlaces del sistema se regeneran solos, no hay que tocar nada más.
if (!defined('PRETTY_URLS')) {
    define('PRETTY_URLS', true);
}

// ─── Auto-detect install path / BASE_URL ──────────────────────────────────────
// Derive the install directory from the script location so the app works in any
// subdirectory. Some CGI/FPM setups append PATH_INFO to SCRIPT_NAME (e.g.
// "/sistema/index.php/login"), so cut the string at "/index.php" instead of
// relying on dirname() alone.
$cdhqScriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$cdhqCut        = strpos($cdhqScriptName, '/index.php');
$cdhqBasePath   = ($cdhqCut !== false) ? substr($cdhqScriptName, 0, $cdhqCut) : dirname($cdhqScriptName);
$cdhqBasePath   = rtrim($cdhqBasePath, '/');
if ($cdhqBasePath === '.' || $cdhqBasePath === '/') {
    $cdhqBasePath = '';
}
$cdhqProtocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$cdhqHost     = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Install path without scheme/host: "" at the domain root, "/sistema" in a subdir.
define('APP_BASE_PATH', $cdhqBasePath);
// Canonical root of the install, always without the front controller.
define('APP_ROOT_URL', $cdhqProtocol . '://' . $cdhqHost . $cdhqBasePath);

if (!defined('BASE_URL')) {
    // Every view builds links as BASE_URL . '/ruta', so appending the front
    // controller here is all that's needed to switch URL styles globally.
    define('BASE_URL', PRETTY_URLS ? APP_ROOT_URL : APP_ROOT_URL . '/index.php');
}
// Raíz del dominio: ahí viven la landing pública y los logotipos institucionales
// (CDHQ HORIZONTAL.png / CDHQ VERTICAL.png), que el sistema reutiliza.
if (!defined('SITE_URL')) {
    define('SITE_URL', $cdhqProtocol . '://' . $cdhqHost);
}

unset($cdhqScriptName, $cdhqCut, $cdhqBasePath, $cdhqProtocol, $cdhqHost);

// ─── Application Constants ────────────────────────────────────────────────────
define('APP_NAME', 'Sistema CDHQ');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // development | production

// ─── Session Settings ────────────────────────────────────────────────────────
define('SESSION_LIFETIME', 7200); // 2 hours

// ─── Security ────────────────────────────────────────────────────────────────
define('CSRF_TOKEN_NAME', 'csrf_token');

// ─── Roles ───────────────────────────────────────────────────────────────────
define('ROLE_SUPER_ADMIN',           'SUPER_ADMIN');
define('ROLE_ADMIN_MEMBRESIAS',      'ADMIN_MEMBRESIAS');
define('ROLE_ADMIN_FINANCIERO',      'ADMIN_FINANCIERO');
define('ROLE_COORD_EVENTOS',         'COORD_EVENTOS');
define('ROLE_EJECUTIVO_PROSPECTOS',  'EJECUTIVO_PROSPECTOS');
define('ROLE_AFILIADO',              'AFILIADO');

define('ALL_ROLES', [
    ROLE_SUPER_ADMIN,
    ROLE_ADMIN_MEMBRESIAS,
    ROLE_ADMIN_FINANCIERO,
    ROLE_COORD_EVENTOS,
    ROLE_EJECUTIVO_PROSPECTOS,
    ROLE_AFILIADO,
]);

// ─── Paths ───────────────────────────────────────────────────────────────────
define('ROOT_PATH',        dirname(__DIR__));
define('APP_PATH',         ROOT_PATH . '/app');
define('VIEWS_PATH',       APP_PATH  . '/views');
define('CONTROLLERS_PATH', APP_PATH  . '/controllers');
define('MODELS_PATH',      APP_PATH  . '/models');
define('CORE_PATH',        ROOT_PATH . '/core');

// ─── Timezone ────────────────────────────────────────────────────────────────
date_default_timezone_set('America/Mexico_City');

// ─── Error Reporting ─────────────────────────────────────────────────────────
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
