<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireMethod('POST');

$input = jsonInput();
$name = trim((string) ($input['name'] ?? ''));
$email = lowercase(trim((string) ($input['email'] ?? '')));
$password = (string) ($input['password'] ?? '');

if (textLength($name) < 3 || textLength($name) > 120) {
    jsonResponse(['ok' => false, 'error' => 'INVALID_NAME', 'message' => 'Escribe un nombre válido.'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || textLength($email) > 160) {
    jsonResponse(['ok' => false, 'error' => 'INVALID_EMAIL', 'message' => 'Escribe un correo válido.'], 422);
}
if (strlen($password) < 8 || strlen($password) > 72) {
    jsonResponse(['ok' => false, 'error' => 'INVALID_PASSWORD', 'message' => 'La contraseña debe tener entre 8 y 72 caracteres.'], 422);
}

$db = database();
$exists = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$exists->execute(['email' => $email]);
if ($exists->fetch()) {
    jsonResponse(['ok' => false, 'error' => 'EMAIL_ALREADY_REGISTERED', 'message' => 'Ese correo ya está registrado.'], 409);
}

$insert = $db->prepare(
    'INSERT INTO users (name, email, password, role, app_role, active)
     VALUES (:name, :email, :password, "AFILIADO", "USUARIO", 1)'
);
$insert->execute([
    'name' => $name,
    'email' => $email,
    'password' => password_hash($password, PASSWORD_DEFAULT),
]);

$user = ['id' => (int) $db->lastInsertId(), 'name' => $name, 'email' => $email, 'app_role' => 'USUARIO'];
startSecureSession();
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['app_role'] = $user['app_role'];

$session = issueAuthToken(
    $user['id'],
    (string) ($input['device_name'] ?? ''),
    (string) ($input['platform'] ?? '')
);

jsonResponse([
    'ok' => true,
    'user' => publicUser($user),
    // Sin migración 07 no hay token: la app usa la cookie de sesión.
    'token' => $session['token'] ?? null,
    'token_expires_at' => $session['expires_at'] ?? null,
], 201);
