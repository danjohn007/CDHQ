<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireMethod('POST');

$userId = currentUserId();

// Sin la migración 07 esta función no puede operar; se avisa con claridad.
requireMigration07();
$input = jsonInput();
$token = trim((string) ($input['token'] ?? ''));
$platform = trim((string) ($input['platform'] ?? ''));
$remove = (bool) ($input['remove'] ?? false);

// Expo entrega identificadores con el formato ExponentPushToken[...] o
// ExpoPushToken[...]. Validarlo evita guardar cadenas que nunca podrán recibir
// una notificación.
if (!preg_match('/^Ex(ponent)?PushToken\[[A-Za-z0-9_-]{1,200}\]$/', $token)) {
    jsonResponse(['ok' => false, 'error' => 'INVALID_PUSH_TOKEN', 'message' => 'El identificador de notificaciones no es válido.'], 422);
}

$db = database();

if ($remove) {
    $db->prepare('DELETE FROM push_tokens WHERE token = :token AND user_id = :user_id')
        ->execute(['token' => $token, 'user_id' => $userId]);
    jsonResponse(['ok' => true, 'registered' => false]);
}

// Un mismo dispositivo puede cambiar de cuenta: el token se reasigna al usuario
// activo y se reinicia el contador de fallos.
$db->prepare(
    'INSERT INTO push_tokens (user_id, token, platform)
     VALUES (:user_id, :token, :platform)
     ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), platform = VALUES(platform), failure_count = 0'
)->execute([
    'user_id' => $userId,
    'token' => $token,
    'platform' => $platform !== '' ? truncate($platform, 20) : null,
]);

jsonResponse(['ok' => true, 'registered' => true]);
