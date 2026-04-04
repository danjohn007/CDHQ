<?php
/**
 * test_connection.php – Sistema CDHQ
 * Test DB connection, PHP version, and BASE_URL detection.
 */
require_once __DIR__ . '/config/config.php';

$checks = [];

// 1. PHP Version
$phpOk = version_compare(PHP_VERSION, '7.4.0', '>=');
$checks[] = [
    'label'   => 'Versión de PHP',
    'value'   => PHP_VERSION,
    'ok'      => $phpOk,
    'message' => $phpOk ? 'OK (≥ 7.4)' : 'Se requiere PHP 7.4 o superior',
];

// 2. Required extensions
$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl', 'session'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $checks[] = [
        'label'   => "Extensión: {$ext}",
        'value'   => $loaded ? 'Cargada' : 'No encontrada',
        'ok'      => $loaded,
        'message' => $loaded ? 'OK' : "La extensión {$ext} es requerida",
    ];
}

// 3. BASE_URL detection
$checks[] = [
    'label'   => 'BASE_URL detectada',
    'value'   => BASE_URL,
    'ok'      => true,
    'message' => 'Detectado automáticamente',
];

// 4. DB Connection
$dbOk      = false;
$dbMessage = '';
$dbVersion = '';
try {
    require_once CORE_PATH . '/Database.php';
    $db        = Database::getInstance();
    $dbVersion = $db->fetchColumn("SELECT VERSION()");
    $dbOk      = true;
    $dbMessage = "Conexión exitosa – MySQL {$dbVersion}";
} catch (Exception $e) {
    $dbMessage = 'Error: ' . $e->getMessage();
}
$checks[] = [
    'label'   => 'Conexión a base de datos',
    'value'   => DB_HOST . ' / ' . DB_NAME,
    'ok'      => $dbOk,
    'message' => $dbMessage,
];

// 5. Table counts (only if DB connected)
if ($dbOk) {
    $tables = ['users', 'affiliates', 'subscription_plans', 'events', 'prospects', 'configurations'];
    foreach ($tables as $table) {
        try {
            $count = $db->fetchColumn("SELECT COUNT(*) FROM `{$table}`");
            $checks[] = [
                'label'   => "Tabla: {$table}",
                'value'   => "{$count} registros",
                'ok'      => true,
                'message' => 'OK',
            ];
        } catch (Exception $e) {
            $checks[] = [
                'label'   => "Tabla: {$table}",
                'value'   => 'No encontrada',
                'ok'      => false,
                'message' => 'Ejecuta sql/schema.sql',
            ];
        }
    }
}

// 6. Writable directories (for future uploads)
$dirs = [__DIR__];
foreach ($dirs as $dir) {
    $writable = is_writable($dir);
    $checks[] = [
        'label'   => 'Directorio escribible',
        'value'   => realpath($dir),
        'ok'      => $writable,
        'message' => $writable ? 'OK' : 'Sin permisos de escritura',
    ];
}

$allOk = array_reduce($checks, fn($carry, $c) => $carry && $c['ok'], true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Conexión – Sistema CDHQ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 bg-indigo-700 rounded-xl flex items-center justify-center text-white font-black text-xl">CD</div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Sistema CDHQ</h1>
                <p class="text-gray-500 text-sm">Verificación de entorno e instalación</p>
            </div>
        </div>

        <!-- Overall status -->
        <div class="mb-6 p-4 rounded-xl <?= $allOk ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
            <p class="font-semibold <?= $allOk ? 'text-green-800' : 'text-red-800' ?>">
                <?= $allOk ? '✅ Todo listo. El sistema está configurado correctamente.' : '❌ Se encontraron problemas. Revisa los detalles abajo.' ?>
            </p>
        </div>

        <!-- Checks table -->
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 rounded-lg">
                    <th class="px-4 py-2 text-left font-medium text-gray-600">Verificación</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-600">Valor</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-600">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($checks as $check): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-700"><?= htmlspecialchars($check['label']) ?></td>
                    <td class="px-4 py-3 text-gray-500 text-xs font-mono"><?= htmlspecialchars($check['value']) ?></td>
                    <td class="px-4 py-3">
                        <?php if ($check['ok']): ?>
                        <span class="inline-flex items-center gap-1 text-green-700 bg-green-100 text-xs px-2 py-0.5 rounded-full font-medium">✓ <?= htmlspecialchars($check['message']) ?></span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1 text-red-700 bg-red-100 text-xs px-2 py-0.5 rounded-full font-medium">✗ <?= htmlspecialchars($check['message']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($allOk): ?>
        <div class="mt-6 pt-4 border-t border-gray-100">
            <a href="<?= htmlspecialchars(BASE_URL) ?>/login"
               class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-2.5 rounded-lg transition-colors">
                🚀 Ir al sistema →
            </a>
        </div>
        <?php endif; ?>

        <p class="mt-6 text-xs text-gray-400 text-center">
            <?= htmlspecialchars(APP_NAME) ?> v<?= APP_VERSION ?> – <?= date('Y-m-d H:i:s') ?>
        </p>
    </div>
</div>
</body>
</html>
