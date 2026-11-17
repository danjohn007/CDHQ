<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireMethod('POST');

$userId = currentUserId();
$input = jsonInput();
$password = (string) ($input['password'] ?? '');
$confirmation = lowercase(trim((string) ($input['confirmation'] ?? '')));
$reason = trim((string) ($input['reason'] ?? ''));

if ($confirmation !== 'eliminar') {
    jsonResponse([
        'ok' => false,
        'error' => 'CONFIRMATION_REQUIRED',
        'message' => 'Escribe la palabra ELIMINAR para confirmar.',
    ], 422);
}

$db = database();
$query = $db->prepare('SELECT id, email, password, app_role, profile_photo_filename FROM users WHERE id = :id AND active = 1 LIMIT 1');
$query->execute(['id' => $userId]);
$user = $query->fetch();

if (!$user) {
    jsonResponse(['ok' => false, 'error' => 'USER_NOT_FOUND', 'message' => 'La cuenta ya no está disponible.'], 404);
}
if ($password === '' || !password_verify($password, $user['password'])) {
    usleep(250000);
    jsonResponse([
        'ok' => false,
        'error' => 'INVALID_PASSWORD',
        'message' => 'La contraseña no coincide. Escríbela de nuevo para confirmar que eres el titular.',
    // 422 y no 401: la sesión sigue siendo válida, lo que falló es el dato que
    // se acaba de escribir. Un 401 haría que la app cerrara la sesión.
    ], 422);
}

// El personal no puede autoeliminarse: dejaría casos y conversaciones sin
// responsable. Debe solicitarlo a otro administrador.
if (in_array($user['app_role'], ['ASESOR', 'ADMINISTRADOR'], true)) {
    jsonResponse([
        'ok' => false,
        'error' => 'STAFF_ACCOUNT',
        'message' => 'Las cuentas de asesor y administrador se dan de baja desde el panel de CDHQ para no dejar casos sin responsable.',
    ], 403);
}

$previousEmail = (string) $user['email'];
$photoFilename = (string) ($user['profile_photo_filename'] ?? '');

/*
 * Los expedientes de solicitudes, postulaciones y pagos conservan llaves
 * foráneas hacia `users`, y la legislación de protección al consumidor obliga a
 * conservar los comprobantes fiscales. Por eso la cuenta no se borra con DELETE:
 * se eliminan todos los datos personales y se libera el correo, de modo que la
 * cuenta queda irrecuperable y sin información identificable, que es lo que
 * exige la directriz 5.1.1(v) de App Store.
 */
$db->beginTransaction();
try {
    // Correo irrepetible que libera el original para un alta futura.
    $anonymousEmail = 'cuenta-eliminada+' . bin2hex(random_bytes(8)) . '@cdhq.invalid';

    $db->prepare(
        'UPDATE users
         SET name = "Cuenta eliminada",
             email = :email,
             password = :password,
             active = 0,
             profile_photo_filename = NULL,
             profile_photo_token = NULL,
             profile_photo_updated_at = NULL
         WHERE id = :id'
    )->execute([
        'email' => $anonymousEmail,
        // Contraseña aleatoria imposible de adivinar: nadie puede reactivarla.
        'password' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
        'id' => $userId,
    ]);

    $db->prepare(
        'UPDATE affiliates
         SET first_name = "Cuenta", last_name = "eliminada", email = NULL,
             phone = NULL, address = NULL, notes = NULL
         WHERE user_id = :user_id'
    )->execute(['user_id' => $userId]);

    // Contenido escrito por la persona: se retira el texto libre conservando el
    // folio y las fechas que sostienen la estadística operativa.
    $db->prepare(
        'UPDATE support_requests
         SET description = "[Contenido eliminado a solicitud del titular]"
         WHERE user_id = :user_id'
    )->execute(['user_id' => $userId]);

    $db->prepare(
        'UPDATE chat_messages cm
         JOIN chat_conversations cc ON cc.id = cm.conversation_id
         SET cm.message = "[Mensaje eliminado a solicitud del titular]"
         WHERE cc.user_id = :user_id AND cm.sender_type = "USUARIO"'
    )->execute(['user_id' => $userId]);

    $db->prepare(
        'UPDATE defender_applications
         SET motivation = "[Eliminado a solicitud del titular]", education = NULL, experience = NULL
         WHERE user_id = :user_id'
    )->execute(['user_id' => $userId]);

    // Estas tablas se agregaron en 05 y 07; la instalación puede no tenerlas.
    foreach ([
        'UPDATE credential_requests SET reason = "[Eliminado a solicitud del titular]", shipping_address = NULL WHERE user_id = :user_id',
        'DELETE FROM notifications WHERE user_id = :user_id',
        'DELETE FROM push_tokens WHERE user_id = :user_id',
        'DELETE FROM password_resets WHERE user_id = :user_id',
    ] as $statement) {
        try {
            $db->prepare($statement)->execute(['user_id' => $userId]);
        } catch (PDOException $error) {
            error_log('Tabla no disponible al eliminar la cuenta: ' . $error->getMessage());
        }
    }

    // Las credenciales emitidas dejan de ser válidas de inmediato.
    $db->prepare(
        'UPDATE credentials c
         JOIN affiliates a ON a.id = c.affiliate_id
         SET c.status = "REVOCADA"
         WHERE a.user_id = :user_id AND c.status = "VIGENTE"'
    )->execute(['user_id' => $userId]);

    try {
        $db->prepare(
            'INSERT INTO account_deletions (email_hash, previous_user_id, reason)
             VALUES (:email_hash, :user_id, :reason)'
        )->execute([
            'email_hash' => hash('sha256', lowercase($previousEmail)),
            'user_id' => $userId,
            'reason' => $reason !== '' ? truncate($reason, 255) : null,
        ]);
    } catch (PDOException $error) {
        error_log('No fue posible registrar la eliminación: ' . $error->getMessage());
    }

    $db->prepare(
        'INSERT INTO activity_log (user_id, action, entity, entity_id, details, ip)
         VALUES (NULL, "DELETE_ACCOUNT", "users", :entity_id, :details, :ip)'
    )->execute([
        'entity_id' => $userId,
        'details' => 'Eliminación de cuenta solicitada por el titular desde la aplicación',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('No fue posible eliminar la cuenta: ' . $error->getMessage());
    jsonResponse([
        'ok' => false,
        'error' => 'DELETE_FAILED',
        'message' => 'No fue posible completar la eliminación. Intenta de nuevo o escribe a CDHQ.',
    ], 500);
}

// Fuera de la transacción: la fotografía ya no está referenciada en la base.
if ($photoFilename !== '' && preg_match('/^[a-f0-9]{64}\.jpg$/', $photoFilename)) {
    @unlink(dirname(__DIR__) . '/storage/profile-photos/' . $photoFilename);
}

revokeAllAuthTokens($userId);

startSecureSession();
$_SESSION = [];
session_destroy();

jsonResponse(['ok' => true, 'message' => 'Tu cuenta y tus datos personales fueron eliminados.']);
