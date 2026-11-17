<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireMethod('POST');

// El token del dispositivo se revoca antes de destruir la sesión, para que el
// cierre funcione igual si el cliente sólo envía la cabecera Authorization.
$userId = optionalUserId();
$token = bearerToken();
if ($token !== '') {
    revokeAuthToken($token);
}

// Al cerrar sesión se retira también el envío de notificaciones push a este
// dispositivo, evitando avisos para una cuenta que ya no está activa aquí.
$pushToken = trim((string) (json_decode(file_get_contents('php://input') ?: '{}', true)['push_token'] ?? ''));
if ($userId && $pushToken !== '') {
    try {
        database()
            ->prepare('DELETE FROM push_tokens WHERE user_id = :user_id AND token = :token')
            ->execute(['user_id' => $userId, 'token' => $pushToken]);
    } catch (Throwable $error) {
        error_log('No fue posible retirar el token push: ' . $error->getMessage());
    }
}

startSecureSession();
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => 'Lax',
    ]);
}
session_destroy();
jsonResponse(['ok' => true]);
