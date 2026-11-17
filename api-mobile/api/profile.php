<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$userId = currentUserId();
$db = database();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $input = jsonInput();
    $name = trim((string) ($input['name'] ?? ''));
    $email = lowercase(trim((string) ($input['email'] ?? '')));
    $currentPassword = (string) ($input['current_password'] ?? '');

    if (textLength($name) < 3 || textLength($name) > 120) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_NAME', 'message' => 'Escribe un nombre válido de 3 a 120 caracteres.'], 422);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || textLength($email) > 160) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_EMAIL', 'message' => 'Escribe un correo electrónico válido.'], 422);
    }

    $current = $db->prepare('SELECT id, name, email, password, app_role FROM users WHERE id = :id AND active = 1 LIMIT 1');
    $current->execute(['id' => $userId]);
    $user = $current->fetch();
    if (!$user) jsonResponse(['ok' => false, 'error' => 'USER_NOT_FOUND'], 404);

    $emailChanged = lowercase((string) $user['email']) !== $email;
    if ($emailChanged && ($currentPassword === '' || !password_verify($currentPassword, $user['password']))) {
        jsonResponse([
            'ok' => false,
            'error' => 'CURRENT_PASSWORD_REQUIRED',
            'message' => 'Escribe tu contraseña actual para cambiar el correo.',
        ], 422);
    }
    if ($emailChanged) {
        $duplicate = $db->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
        $duplicate->execute(['email' => $email, 'id' => $userId]);
        if ($duplicate->fetch()) {
            jsonResponse(['ok' => false, 'error' => 'EMAIL_ALREADY_REGISTERED', 'message' => 'Ese correo ya pertenece a otra cuenta.'], 409);
        }
    }

    $parts = preg_split('/\s+/u', $name, 2) ?: [];
    $db->beginTransaction();
    try {
        $db->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id')
            ->execute(['name' => $name, 'email' => $email, 'id' => $userId]);
        $db->prepare(
            'UPDATE affiliates SET first_name = :first_name, last_name = :last_name, email = :email WHERE user_id = :user_id'
        )->execute([
            'first_name' => $parts[0] ?? $name,
            'last_name' => $parts[1] ?? '-',
            'email' => $email,
            'user_id' => $userId,
        ]);
        $db->prepare(
            'INSERT INTO activity_log (user_id, action, entity, entity_id, details, ip)
             VALUES (:user_id, "UPDATE_PROFILE", "users", :entity_id, :details, :ip)'
        )->execute([
            'user_id' => $userId,
            'entity_id' => $userId,
            'details' => json_encode(['name_changed' => $user['name'] !== $name, 'email_changed' => $emailChanged], JSON_FLAGS),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    }
}

if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    jsonResponse(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED'], 405);
}

$query = $db->prepare(
    'SELECT u.id, u.name, u.email, u.app_role, u.created_at, u.profile_photo_token,
            a.affiliate_number, a.state AS affiliate_state,
            c.credential_number, c.status AS credential_status, c.expires_at AS credential_expires_at,
            (SELECT da.status FROM defender_applications da WHERE da.user_id = u.id ORDER BY da.created_at DESC LIMIT 1) AS application_status
     FROM users u
     LEFT JOIN affiliates a ON a.user_id = u.id
     LEFT JOIN credentials c ON c.id = (
        SELECT c2.id FROM credentials c2 WHERE c2.affiliate_id = a.id ORDER BY c2.issued_at DESC LIMIT 1
     )
     WHERE u.id = :id AND u.active = 1 LIMIT 1'
);
$query->execute(['id' => $userId]);
$profile = $query->fetch();
if (!$profile) jsonResponse(['ok' => false, 'error' => 'USER_NOT_FOUND'], 404);
$profile['id'] = (int) $profile['id'];
$acceptedAsDefender = $profile['app_role'] === 'USUARIO'
    && in_array($profile['application_status'] ?? '', ['APROBADA', 'PENDIENTE_PAGO', 'ACREDITADA'], true);
$profile['account_role'] = $profile['app_role'];
$profile['role'] = $acceptedAsDefender ? 'DEFENSOR' : $profile['app_role'];
$profile['profile_photo_url'] = $profile['profile_photo_token'] ? publicProfilePhotoUrl($profile['profile_photo_token']) : null;
unset($profile['app_role'], $profile['profile_photo_token']);
jsonResponse(['ok' => true, 'profile' => $profile]);
