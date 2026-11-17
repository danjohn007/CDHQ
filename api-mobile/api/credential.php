<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireMethod('GET');
$userId = currentUserId();
$query = database()->prepare(
    'SELECT u.name, u.profile_photo_token, a.affiliate_number, a.state AS affiliate_state,
            c.credential_number, c.status, c.issued_at, c.expires_at, c.verification_token
     FROM users u
     LEFT JOIN affiliates a ON a.user_id = u.id
     LEFT JOIN credentials c ON c.id = (
        SELECT c2.id FROM credentials c2 WHERE c2.affiliate_id = a.id ORDER BY c2.issued_at DESC LIMIT 1
     )
     WHERE u.id = :user_id LIMIT 1'
);
$query->execute(['user_id' => $userId]);
$credential = $query->fetch();
if ($credential && $credential['credential_number']) {
    $credential['verification_url'] = publicCredentialUrl($credential['verification_token']);
    $credential['profile_photo_url'] = $credential['profile_photo_token'] ? publicProfilePhotoUrl($credential['profile_photo_token']) : null;
    unset($credential['verification_token'], $credential['profile_photo_token']);
}
jsonResponse(['ok' => true, 'credential' => ($credential && $credential['credential_number']) ? $credential : null]);
