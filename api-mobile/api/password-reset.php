<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireMethod('POST');

// Sin la migración 07 esta función no puede operar; se avisa con claridad.
requireMigration07();

$input = jsonInput();
$action = strtolower(trim((string) ($input['action'] ?? 'request')));
$email = lowercase(trim((string) ($input['email'] ?? '')));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['ok' => false, 'error' => 'INVALID_EMAIL', 'message' => 'Escribe un correo electrónico válido.'], 422);
}

$db = database();
$query = $db->prepare('SELECT id, name, email FROM users WHERE email = :email AND active = 1 LIMIT 1');
$query->execute(['email' => $email]);
$user = $query->fetch();

if ($action === 'request') {
    /*
     * La respuesta es idéntica exista o no la cuenta. Así nadie puede usar esta
     * pantalla para averiguar qué correos están registrados en CDHQ.
     */
    $genericResponse = [
        'ok' => true,
        'message' => 'Si el correo pertenece a una cuenta de CDHQ, recibirás un código de 6 dígitos en los próximos minutos.',
    ];

    if (!$user) {
        usleep(400000);
        jsonResponse($genericResponse);
    }

    // Máximo tres solicitudes por hora para que nadie sature el buzón ajeno.
    $recent = $db->prepare(
        'SELECT COUNT(*) FROM password_resets
         WHERE user_id = :user_id AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)'
    );
    $recent->execute(['user_id' => $user['id']]);
    if ((int) $recent->fetchColumn() >= 3) {
        jsonResponse($genericResponse);
    }

    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Los códigos anteriores dejan de servir en cuanto se pide uno nuevo.
    $db->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL')
        ->execute(['user_id' => $user['id']]);

    $db->prepare(
        'INSERT INTO password_resets (user_id, token_hash, expires_at, requested_ip)
         VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 20 MINUTE), :ip)'
    )->execute([
        'user_id' => $user['id'],
        'token_hash' => hash('sha256', $email . '|' . $code),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    sendPasswordResetEmail((string) $user['email'], (string) $user['name'], $code);
    jsonResponse($genericResponse);
}

if ($action !== 'confirm') {
    jsonResponse(['ok' => false, 'error' => 'INVALID_ACTION'], 422);
}

$code = preg_replace('/\D/', '', (string) ($input['code'] ?? ''));
$password = (string) ($input['password'] ?? '');

if (strlen((string) $code) !== 6) {
    jsonResponse(['ok' => false, 'error' => 'INVALID_CODE', 'message' => 'El código debe tener 6 dígitos.'], 422);
}
if (strlen($password) < 8 || strlen($password) > 72) {
    jsonResponse(['ok' => false, 'error' => 'INVALID_PASSWORD', 'message' => 'La contraseña debe tener entre 8 y 72 caracteres.'], 422);
}

$invalidCode = ['ok' => false, 'error' => 'CODE_NOT_VALID', 'message' => 'El código no es válido o ya expiró. Solicita uno nuevo.'];
if (!$user) {
    usleep(400000);
    jsonResponse($invalidCode, 422);
}

$reset = $db->prepare(
    'SELECT id FROM password_resets
     WHERE user_id = :user_id AND token_hash = :token_hash AND used_at IS NULL AND expires_at > NOW()
     ORDER BY id DESC LIMIT 1'
);
$reset->execute([
    'user_id' => $user['id'],
    'token_hash' => hash('sha256', $email . '|' . $code),
]);
$record = $reset->fetch();

if (!$record) {
    usleep(400000);
    jsonResponse($invalidCode, 422);
}

$db->beginTransaction();
try {
    $db->prepare('UPDATE users SET password = :password WHERE id = :id')
        ->execute(['password' => password_hash($password, PASSWORD_DEFAULT), 'id' => $user['id']]);
    $db->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id')
        ->execute(['id' => $record['id']]);
    $db->prepare(
        'INSERT INTO activity_log (user_id, action, entity, entity_id, details, ip)
         VALUES (:user_id, "PASSWORD_RESET", "users", :entity_id, :details, :ip)'
    )->execute([
        'user_id' => $user['id'],
        'entity_id' => $user['id'],
        'details' => 'Contraseña restablecida desde la aplicación CDHQ',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    throw $error;
}

// Cambiar la contraseña cierra la sesión en todos los dispositivos: si alguien
// más tenía acceso, lo pierde en ese momento.
revokeAllAuthTokens((int) $user['id']);

jsonResponse(['ok' => true, 'message' => 'Tu contraseña quedó actualizada. Inicia sesión con la nueva.']);

function sendPasswordResetEmail(string $email, string $name, string $code): void
{
    $from = mailSenderAddress();
    $fromName = trim((string) (serverConfig()['mail']['from_name'] ?? 'CDHQ'));

    if ($from === '' || !function_exists('mail')) {
        // Sin correo configurado el código queda en el log del servidor, para
        // que CDHQ pueda entregarlo manualmente mientras se configura el envío.
        error_log('Código de recuperación para ' . $email . ': ' . $code . ' (configura mail.from_address en config.php)');
        return;
    }

    $firstName = trim(explode(' ', trim($name))[0] ?? '');
    $subject = '=?UTF-8?B?' . base64_encode('Código para restablecer tu contraseña de CDHQ') . '?=';
    $body = "Hola" . ($firstName !== '' ? " {$firstName}" : '') . ",\n\n"
        . "Recibimos una solicitud para restablecer la contraseña de tu cuenta en la aplicación CDHQ.\n\n"
        . "Tu código de verificación es: {$code}\n\n"
        . "El código vence en 20 minutos y sólo puede usarse una vez.\n\n"
        . "Si no solicitaste este cambio, ignora este mensaje: tu contraseña actual sigue siendo válida.\n\n"
        . "Comisión de Derechos Humanos de Querétaro\n";

    $headers = implode("\r\n", [
        'From: ' . mimeHeader($fromName) . ' <' . $from . '>',
        'Reply-To: ' . $from,
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: CDHQ-API',
    ]);

    if (!@mail($email, $subject, $body, $headers, '-f' . $from)) {
        error_log('No fue posible enviar el correo de recuperación a ' . $email);
    }
}
