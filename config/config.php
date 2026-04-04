<?php
// ─── Database Configuration ───────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'cdhq');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// ─── Auto-detect BASE_URL ─────────────────────────────────────────────────────
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Derive the base path from the script location so the app works in any subdirectory
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = rtrim($scriptDir, '/');
    define('BASE_URL', $protocol . '://' . $host . $scriptDir);
}

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
