<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$staff = requireRoles('ASESOR', 'ADMINISTRADOR');
$db = database();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $input = jsonInput();
    $id = filter_var($input['conversation_id'] ?? null, FILTER_VALIDATE_INT);
    $action = strtolower((string) ($input['action'] ?? 'claim'));
    if (!$id) jsonResponse(['ok' => false, 'error' => 'INVALID_CONVERSATION'], 422);

    $currentQuery = $db->prepare('SELECT id, user_id, status, assigned_to FROM chat_conversations WHERE id = :id LIMIT 1');
    $currentQuery->execute(['id' => $id]);
    $current = $currentQuery->fetch();
    if (!$current) jsonResponse(['ok' => false, 'error' => 'CHAT_NOT_FOUND'], 404);

    if ($action === 'claim') {
        if ($current['assigned_to'] && (int) $current['assigned_to'] !== (int) $staff['id']) {
            jsonResponse(['ok' => false, 'error' => 'CHAT_ALREADY_ASSIGNED', 'message' => 'La conversación ya fue asignada a otro asesor.'], 409);
        }
        $db->prepare(
            'UPDATE chat_conversations SET assigned_to = :staff, status = "CON_ASESOR", updated_at = NOW()
             WHERE id = :id'
        )->execute(['staff' => $staff['id'], 'id' => $id]);
        if ($current['status'] !== 'CON_ASESOR') {
            $db->prepare(
                'INSERT INTO chat_messages (conversation_id, sender_type, sender_user_id, message)
                 VALUES (:id, "SISTEMA", :staff, :message)'
            )->execute(['id' => $id, 'staff' => $staff['id'], 'message' => 'Un asesor tomó la conversación.']);
        }
    } elseif ($action === 'send') {
        if ($staff['app_role'] !== 'ADMINISTRADOR' && (int) $current['assigned_to'] !== (int) $staff['id']) {
            jsonResponse(['ok' => false, 'error' => 'CHAT_NOT_ASSIGNED', 'message' => 'Primero debes tomar la conversación.'], 403);
        }
        $message = trim((string) ($input['message'] ?? ''));
        if ($message === '' || textLength($message) > 2000) {
            jsonResponse(['ok' => false, 'error' => 'INVALID_MESSAGE', 'message' => 'Escribe un mensaje válido.'], 422);
        }
        $db->prepare(
            'INSERT INTO chat_messages (conversation_id, sender_type, sender_user_id, message)
             VALUES (:id, "ASESOR", :staff, :message)'
        )->execute(['id' => $id, 'staff' => $staff['id'], 'message' => $message]);
        $db->prepare('UPDATE chat_conversations SET updated_at = NOW() WHERE id = :id')->execute(['id' => $id]);

        if ($current['user_id']) {
            notifyUser(
                (int) $current['user_id'],
                'CHAT_RESPUESTA',
                'Un asesor de CDHQ te respondió',
                $message,
                '/chat'
            );
        }
    } elseif ($action === 'close') {
        $query = $db->prepare(
            'UPDATE chat_conversations SET status = "CERRADA", updated_at = NOW()
             WHERE id = :id AND (assigned_to = :staff OR :is_admin = 1)'
        );
        $query->execute(['id' => $id, 'staff' => $staff['id'], 'is_admin' => $staff['app_role'] === 'ADMINISTRADOR' ? 1 : 0]);
        if (!$query->rowCount()) jsonResponse(['ok' => false, 'error' => 'CHAT_NOT_ASSIGNED'], 403);
        $db->prepare(
            'INSERT INTO chat_messages (conversation_id, sender_type, sender_user_id, message)
             VALUES (:id, "SISTEMA", :staff, :message)'
        )->execute(['id' => $id, 'staff' => $staff['id'], 'message' => 'La conversación fue cerrada por el asesor.']);
    } else {
        jsonResponse(['ok' => false, 'error' => 'INVALID_ACTION'], 422);
    }
}

if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    jsonResponse(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED'], 405);
}

$conversationId = filter_var($_GET['conversation_id'] ?? null, FILTER_VALIDATE_INT);
if ($conversationId) {
    $detailQuery = $db->prepare(
        'SELECT cc.id, cc.status, cc.assigned_to, cc.updated_at, u.name AS user_name, u.email AS user_email
         FROM chat_conversations cc LEFT JOIN users u ON u.id = cc.user_id WHERE cc.id = :id LIMIT 1'
    );
    $detailQuery->execute(['id' => $conversationId]);
    $conversation = $detailQuery->fetch();
    if (!$conversation) jsonResponse(['ok' => false, 'error' => 'CHAT_NOT_FOUND'], 404);
    $canAccess = $staff['app_role'] === 'ADMINISTRADOR'
        || $conversation['status'] === 'ESPERANDO_ASESOR'
        || (int) $conversation['assigned_to'] === (int) $staff['id'];
    if (!$canAccess) jsonResponse(['ok' => false, 'error' => 'FORBIDDEN'], 403);
    $messagesQuery = $db->prepare(
        'SELECT cm.id, cm.sender_type, cm.message, cm.created_at, sender.name AS sender_name
         FROM chat_messages cm LEFT JOIN users sender ON sender.id = cm.sender_user_id
         WHERE cm.conversation_id = :id ORDER BY cm.created_at ASC, cm.id ASC LIMIT 300'
    );
    $messagesQuery->execute(['id' => $conversationId]);
    jsonResponse(['ok' => true, 'conversation' => $conversation, 'messages' => $messagesQuery->fetchAll()]);
}

$query = $db->prepare(
    'SELECT cc.id, cc.status, cc.assigned_to, cc.updated_at, u.name AS user_name, u.email AS user_email,
            (SELECT message FROM chat_messages cm WHERE cm.conversation_id = cc.id ORDER BY cm.id DESC LIMIT 1) AS last_message
     FROM chat_conversations cc LEFT JOIN users u ON u.id = cc.user_id
     WHERE cc.status = "ESPERANDO_ASESOR" OR cc.assigned_to = :staff ORDER BY cc.updated_at DESC'
);
$query->execute(['staff' => $staff['id']]);
jsonResponse(['ok' => true, 'conversations' => $query->fetchAll()]);
