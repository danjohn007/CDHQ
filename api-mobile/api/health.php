<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/stripe.php';
requireMethod('GET');

// Se incrementa con cada despliegue. Sirve para confirmar desde el navegador
// que el código subido es realmente el que está atendiendo las peticiones, y no
// una copia anterior que quedó en caché o sin reemplazar.
const API_VERSION = '10';

try {
    $databaseOk = (int) database()->query('SELECT 1')->fetchColumn() === 1;
} catch (Throwable $error) {
    error_log($error->getMessage());
    $databaseOk = false;
}

/*
 * Estado de la migración 07. La API funciona sin ella, pero la sesión
 * persistente, las notificaciones y la eliminación de cuenta quedan inactivas,
 * así que conviene poder comprobarlo de un vistazo tras cada despliegue.
 */
$migration = ['ok' => false, 'missing' => []];
if ($databaseOk) {
    foreach (['auth_tokens', 'push_tokens', 'password_resets', 'notifications', 'account_deletions'] as $table) {
        if (!tableExists($table)) {
            $migration['missing'][] = $table;
        }
    }
    $migration['ok'] = $migration['missing'] === [];
}

// Sin esta cabecera la sesión del dispositivo no se sostiene y la aplicación
// vuelve a pedir la contraseña en cada apertura.
$authHeaderReaches = ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '') !== ''
    || function_exists('apache_request_headers');

jsonResponse([
    'ok' => $databaseOk,
    'service' => 'cdhq-mobile-api',
    'api_version' => API_VERSION,
    'public_base_url' => publicApplicationBaseUrl(),
    'database' => $databaseOk ? 'connected' : 'unavailable',
    'migration_07' => $migration['ok']
        ? 'installed'
        : ($databaseOk ? 'pending: faltan ' . implode(', ', $migration['missing']) : 'unknown'),
    'auth_header' => $authHeaderReaches ? 'readable' : 'blocked',
    'storage' => is_dir(dirname(__DIR__) . '/storage') ? 'present' : 'missing',
    'stripe' => stripeIsConfigured() ? 'test_configured' : 'not_configured',
    'curl' => function_exists('curl_init') ? 'available' : 'unavailable',
    'gd' => extension_loaded('gd') ? 'available' : 'unavailable',
], $databaseOk ? 200 : 503);
