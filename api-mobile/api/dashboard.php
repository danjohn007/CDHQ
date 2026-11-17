<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireMethod('GET');
$userId = currentUserId();
$db = database();

$profileQuery = $db->prepare(
    'SELECT u.id, u.name, u.email, u.app_role,
        a.id AS affiliate_id, a.affiliate_number, a.state AS affiliate_state,
        s.id AS subscription_id, s.status AS subscription_status,
        s.start_date AS subscription_start, s.end_date AS subscription_end,
        sp.name AS plan_name, sp.price AS current_plan_price,
        c.credential_number, c.status AS credential_status,
        c.issued_at AS credential_issued_at, c.expires_at AS credential_expires_at
     FROM users u
     LEFT JOIN affiliates a ON a.user_id = u.id
     LEFT JOIN subscriptions s ON s.id = (
        SELECT s2.id FROM subscriptions s2 WHERE s2.affiliate_id = a.id
        ORDER BY (s2.status = "ACTIVA") DESC, s2.end_date DESC LIMIT 1
     )
     LEFT JOIN subscription_plans sp ON sp.id = s.plan_id
     LEFT JOIN credentials c ON c.id = (
        SELECT c2.id FROM credentials c2 WHERE c2.affiliate_id = a.id
        ORDER BY c2.issued_at DESC LIMIT 1
     )
     WHERE u.id = :user_id AND u.active = 1 LIMIT 1'
);
$profileQuery->execute(['user_id' => $userId]);
$profile = $profileQuery->fetch();
if (!$profile) {
    jsonResponse(['ok' => false, 'error' => 'USER_NOT_FOUND'], 404);
}

$requestQuery = $db->prepare(
    'SELECT id, folio, subject, urgency, status, updated_at
     FROM support_requests WHERE user_id = :user_id ORDER BY updated_at DESC LIMIT 5'
);
$requestQuery->execute(['user_id' => $userId]);

$applicationQuery = $db->prepare(
    'SELECT id, status, submitted_at, reviewed_at, review_notes
     FROM defender_applications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1'
);
$applicationQuery->execute(['user_id' => $userId]);

$planQuery = $db->query(
    'SELECT id, name, description, price, duration_days
     FROM subscription_plans WHERE active = 1 ORDER BY duration_days DESC, price ASC'
);

$countQuery = $db->prepare(
    'SELECT SUM(status NOT IN ("CERRADA", "CANCELADA")) AS active_requests,
            SUM(status = "CERRADA") AS closed_requests
     FROM support_requests WHERE user_id = :user_id'
);
$countQuery->execute(['user_id' => $userId]);
$counts = $countQuery->fetch() ?: [];

jsonResponse([
    'ok' => true,
    'profile' => $profile,
    'requests' => $requestQuery->fetchAll(),
    'application' => $applicationQuery->fetch() ?: null,
    'available_plans' => $planQuery->fetchAll(),
    'metrics' => [
        'active_requests' => (int) ($counts['active_requests'] ?? 0),
        'closed_requests' => (int) ($counts['closed_requests'] ?? 0),
    ],
]);
