<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireMethod('POST');

$input = jsonInput();
$email = lowercase(trim((string) ($input['email'] ?? '')));
$password = (string) ($input['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    jsonResponse(['ok' => false, 'error' => 'INVALID_CREDENTIALS', 'message' => 'Correo o contraseña inválidos.'], 422);
}

$query = database()->prepare(
    'SELECT id, name, email, password, app_role, active FROM users WHERE email = :email LIMIT 1'
);
$query->execute(['email' => $email]);
$user = $query->fetch();

if (!$user || !(bool) $user['active'] || !password_verify($password, $user['password'])) {
    usleep(250000);
    jsonResponse(['ok' => false, 'error' => 'INVALID_CREDENTIALS', 'message' => 'Correo o contraseña inválidos.'], 401);
}

startSecureSession();
session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['app_role'] = $user['app_role'];

$log = database()->prepare(
    'INSERT INTO activity_log (user_id, action, entity, entity_id, details, ip)
     VALUES (:user_id, :action, :entity, :entity_id, :details, :ip)'
);
$log->execute([
    'user_id' => $user['id'],
    'action' => 'MOBILE_LOGIN',
    'entity' => 'users',
    'entity_id' => $user['id'],
    'details' => 'Inicio de sesión en la aplicación CDHQ',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
]);

unset($user['password'], $user['active']);

$session = issueAuthToken(
    (int) $user['id'],
    (string) ($input['device_name'] ?? ''),
    (string) ($input['platform'] ?? '')
);

jsonResponse([
    'ok' => true,
    'user' => publicUser($user),
    // Sin migración 07 no hay token: la app usa la cookie de sesión.
    'token' => $session['token'] ?? null,
    'token_expires_at' => $session['expires_at'] ?? null,
]);
