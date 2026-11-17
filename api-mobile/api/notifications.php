<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$userId = currentUserId();

// Sin la migración 07 esta función no puede operar; se avisa con claridad.
requireMigration07();
$db = database();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $limit = filter_var($_GET['limit'] ?? 40, FILTER_VALIDATE_INT, ['options' => ['default' => 40, 'min_range' => 1, 'max_range' => 100]]);

    $query = $db->prepare(
        'SELECT id, type, title, message, action_url, read_at, created_at
         FROM notifications
         WHERE user_id = :user_id
         ORDER BY created_at DESC, id DESC
         LIMIT ' . (int) $limit
    );
    $query->execute(['user_id' => $userId]);
    $notifications = $query->fetchAll();

    $unread = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND read_at IS NULL');
    $unread->execute(['user_id' => $userId]);

    jsonResponse([
        'ok' => true,
        'notifications' => array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'type' => $row['type'],
            'title' => $row['title'],
            'message' => $row['message'],
            'action_url' => $row['action_url'],
            'read' => $row['read_at'] !== null,
            'created_at' => $row['created_at'],
        ], $notifications),
        'unread_count' => (int) $unread->fetchColumn(),
    ]);
}

requireMethod('POST');
$input = jsonInput();
$action = strtolower(trim((string) ($input['action'] ?? 'read')));

if ($action === 'read_all') {
    $db->prepare('UPDATE notifications SET read_at = NOW() WHERE user_id = :user_id AND read_at IS NULL')
        ->execute(['user_id' => $userId]);
    jsonResponse(['ok' => true, 'unread_count' => 0]);
}

if ($action === 'delete') {
    $notificationId = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$notificationId) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_NOTIFICATION', 'message' => 'Indica la notificación a eliminar.'], 422);
    }
    $db->prepare('DELETE FROM notifications WHERE id = :id AND user_id = :user_id')
        ->execute(['id' => $notificationId, 'user_id' => $userId]);
} elseif ($action === 'read') {
    $notificationId = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$notificationId) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_NOTIFICATION', 'message' => 'Indica la notificación a marcar.'], 422);
    }
    $db->prepare('UPDATE notifications SET read_at = NOW() WHERE id = :id AND user_id = :user_id AND read_at IS NULL')
        ->execute(['id' => $notificationId, 'user_id' => $userId]);
} else {
    jsonResponse(['ok' => false, 'error' => 'INVALID_ACTION'], 422);
}

$unread = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND read_at IS NULL');
$unread->execute(['user_id' => $userId]);
jsonResponse(['ok' => true, 'unread_count' => (int) $unread->fetchColumn()]);
