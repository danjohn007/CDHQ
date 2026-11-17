<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireMethod('GET');
$userId = currentUserId();
$query = database()->prepare(
    'SELECT id, name, email, app_role FROM users WHERE id = :id AND active = 1 LIMIT 1'
);
$query->execute(['id' => $userId]);
$user = $query->fetch();

if (!$user) {
    jsonResponse(['ok' => false, 'error' => 'USER_NOT_FOUND'], 401);
}
jsonResponse(['ok' => true, 'user' => publicUser($user)]);
