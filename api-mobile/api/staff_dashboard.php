<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireMethod('GET');
$staff = requireRoles('ASESOR', 'ADMINISTRADOR');
$db = database();

if ($staff['app_role'] === 'ADMINISTRADOR') {
    $metrics = [
        'users' => (int) $db->query('SELECT COUNT(*) FROM users WHERE active = 1')->fetchColumn(),
        'open_requests' => (int) $db->query('SELECT COUNT(*) FROM support_requests WHERE status NOT IN ("CERRADA", "CANCELADA")')->fetchColumn(),
        'pending_applications' => (int) $db->query('SELECT COUNT(*) FROM defender_applications WHERE status IN ("ENVIADA", "EN_REVISION", "CON_OBSERVACIONES")')->fetchColumn(),
        'waiting_chats' => (int) $db->query('SELECT COUNT(*) FROM chat_conversations WHERE status = "ESPERANDO_ASESOR"')->fetchColumn(),
    ];
    $recent = $db->query(
        'SELECT sr.id, sr.folio, sr.subject, sr.status, sr.urgency, sr.updated_at, u.name AS user_name
         FROM support_requests sr JOIN users u ON u.id = sr.user_id ORDER BY sr.updated_at DESC LIMIT 5'
    )->fetchAll();
} else {
    $metricQuery = $db->prepare(
        'SELECT SUM(assigned_to = :assigned_id AND status NOT IN ("CERRADA", "CANCELADA")) AS assigned_requests,
                SUM(assigned_to IS NULL AND status NOT IN ("CERRADA", "CANCELADA")) AS available_requests
         FROM support_requests'
    );
    $metricQuery->execute(['assigned_id' => $staff['id']]);
    $requestMetrics = $metricQuery->fetch() ?: [];
    $chatQuery = $db->prepare('SELECT COUNT(*) FROM chat_conversations WHERE assigned_to = :id AND status = "CON_ASESOR"');
    $chatQuery->execute(['id' => $staff['id']]);
    $metrics = [
        'assigned_requests' => (int) ($requestMetrics['assigned_requests'] ?? 0),
        'available_requests' => (int) ($requestMetrics['available_requests'] ?? 0),
        'active_chats' => (int) $chatQuery->fetchColumn(),
        'waiting_chats' => (int) $db->query('SELECT COUNT(*) FROM chat_conversations WHERE status = "ESPERANDO_ASESOR"')->fetchColumn(),
    ];
    $recentQuery = $db->prepare(
        'SELECT sr.id, sr.folio, sr.subject, sr.status, sr.urgency, sr.updated_at, u.name AS user_name
         FROM support_requests sr JOIN users u ON u.id = sr.user_id
         WHERE sr.assigned_to = :id OR sr.assigned_to IS NULL ORDER BY sr.updated_at DESC LIMIT 5'
    );
    $recentQuery->execute(['id' => $staff['id']]);
    $recent = $recentQuery->fetchAll();
}

jsonResponse(['ok' => true, 'staff' => publicUser($staff), 'metrics' => $metrics, 'recent_requests' => $recent]);
