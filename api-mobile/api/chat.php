<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$userId = currentUserId();
$db = database();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function botReply(string $message): string
{
    $text = lowercase($message);
    if (str_contains($text, 'folio') || str_contains($text, 'solicitud')) {
        return 'Puedes consultar el estado y el folio real de tus casos en la sección Solicitudes.';
    }
    if (str_contains($text, 'afiliar') || str_contains($text, 'defensor') || str_contains($text, 'credencial')) {
        return 'La afiliación requiere postulación, revisión y aprobación. El pago se habilitará posteriormente y nunca se solicitará antes de la aprobación.';
    }
    if (str_contains($text, 'emergencia') || str_contains($text, 'peligro') || str_contains($text, 'violencia')) {
        return 'Si existe peligro inmediato, contacta a los servicios de emergencia. También puedes crear una solicitud con urgencia alta para que CDHQ revise tu caso.';
    }
    return 'Puedo orientarte sobre solicitudes, afiliación y derechos. Para una revisión personal, solicita hablar con un asesor.';
}

$conversationQuery = $db->prepare(
    'SELECT id, status, assigned_to, created_at, updated_at FROM chat_conversations
     WHERE user_id = :user_id AND status <> "CERRADA" ORDER BY updated_at DESC LIMIT 1'
);
$conversationQuery->execute(['user_id' => $userId]);
$conversation = $conversationQuery->fetch();

if ($method === 'POST') {
    $input = jsonInput();
    if (!$conversation) {
        $create = $db->prepare('INSERT INTO chat_conversations (user_id, channel, status) VALUES (:user_id, "WEB", "BOT")');
        $create->execute(['user_id' => $userId]);
        $conversation = ['id' => (int) $db->lastInsertId(), 'status' => 'BOT'];
    }
    if (($input['action'] ?? 'send') === 'transfer') {
        $db->prepare('UPDATE chat_conversations SET status = "ESPERANDO_ASESOR", assigned_to = NULL, updated_at = NOW() WHERE id = :id')->execute(['id' => $conversation['id']]);
        $db->prepare('INSERT INTO chat_messages (conversation_id, sender_type, message) VALUES (:id, "SISTEMA", :message)')->execute([
            'id' => $conversation['id'], 'message' => 'Se solicitó atención de un asesor.',
        ]);

        // Sin este aviso la conversación puede quedarse en espera sin que nadie
        // del equipo se entere hasta que abra el panel.
        $nameQuery = $db->prepare('SELECT name FROM users WHERE id = :id');
        $nameQuery->execute(['id' => $userId]);
        notifyRoles(
            ['ASESOR', 'ADMINISTRADOR'],
            'CHAT_ESPERANDO',
            'Alguien está esperando un asesor',
            ((string) ($nameQuery->fetchColumn() ?: 'Un usuario')) . ' pidió hablar con un asesor en el chat.',
            '/membership'
        );
    } else {
        $message = trim((string) ($input['message'] ?? ''));
        if ($message === '' || textLength($message) > 2000) {
            jsonResponse(['ok' => false, 'error' => 'INVALID_MESSAGE', 'message' => 'Escribe un mensaje válido.'], 422);
        }
        $db->prepare(
            'INSERT INTO chat_messages (conversation_id, sender_type, sender_user_id, message)
             VALUES (:conversation_id, "USUARIO", :user_id, :message)'
        )->execute(['conversation_id' => $conversation['id'], 'user_id' => $userId, 'message' => $message]);
        if ($conversation['status'] === 'BOT') {
            $db->prepare('INSERT INTO chat_messages (conversation_id, sender_type, message) VALUES (:id, "BOT", :message)')->execute([
                'id' => $conversation['id'], 'message' => botReply($message),
            ]);
        }
        $db->prepare('UPDATE chat_conversations SET updated_at = NOW() WHERE id = :id')->execute(['id' => $conversation['id']]);
    }
    $conversationQuery->execute(['user_id' => $userId]);
    $conversation = $conversationQuery->fetch();
}

if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    jsonResponse(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED'], 405);
}

$messages = [];
if ($conversation) {
    $messagesQuery = $db->prepare(
        'SELECT id, sender_type, message, created_at FROM chat_messages
         WHERE conversation_id = :id ORDER BY created_at ASC, id ASC LIMIT 200'
    );
    $messagesQuery->execute(['id' => $conversation['id']]);
    $messages = $messagesQuery->fetchAll();
}
jsonResponse(['ok' => true, 'conversation' => $conversation ?: null, 'messages' => $messages]);
