<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$userId = currentUserId();
$db = database();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $query = $db->prepare(
        'SELECT id, folio, subject, description, urgency, status, preferred_contact,
                incident_date, municipio, estado_mexico, created_at, updated_at
         FROM support_requests WHERE user_id = :user_id ORDER BY created_at DESC'
    );
    $query->execute(['user_id' => $userId]);
    jsonResponse(['ok' => true, 'requests' => $query->fetchAll()]);
}

if ($method === 'POST') {
    $input = jsonInput();
    $subject = trim((string) ($input['subject'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $urgency = strtoupper((string) ($input['urgency'] ?? 'MEDIA'));
    $allowedUrgency = ['BAJA', 'MEDIA', 'ALTA', 'EMERGENCIA'];

    if (textLength($subject) < 5 || textLength($subject) > 180) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_SUBJECT', 'message' => 'Escribe un asunto de entre 5 y 180 caracteres.'], 422);
    }
    if (textLength($description) < 15) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_DESCRIPTION', 'message' => 'Describe la situación con un poco más de detalle.'], 422);
    }
    if (!in_array($urgency, $allowedUrgency, true)) {
        $urgency = 'MEDIA';
    }

    $folio = 'CDHQ-AP-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $insert = $db->prepare(
        'INSERT INTO support_requests
         (folio, user_id, subject, description, urgency, preferred_contact, privacy_consent_at)
         VALUES (:folio, :user_id, :subject, :description, :urgency, "PORTAL", NOW())'
    );
    $insert->execute([
        'folio' => $folio,
        'user_id' => $userId,
        'subject' => $subject,
        'description' => $description,
        'urgency' => $urgency,
    ]);

    $requestId = (int) $db->lastInsertId();

    $authorName = (string) ($db->query('SELECT name FROM users WHERE id = ' . (int) $userId)->fetchColumn() ?: 'Un usuario');
    notifyRoles(
        ['ASESOR', 'ADMINISTRADOR'],
        'SOLICITUD_NUEVA',
        $urgency === 'EMERGENCIA' ? 'Solicitud urgente sin atender' : 'Nueva solicitud de apoyo',
        $authorName . ' registró el folio ' . $folio . ' (' . $subject . ').',
        '/requests'
    );
    notifyUser(
        $userId,
        'SOLICITUD_CREADA',
        'Tu solicitud quedó registrada',
        'Guarda tu folio ' . $folio . '. Te avisaremos cada vez que cambie el estado de tu caso.',
        '/requests'
    );

    jsonResponse(['ok' => true, 'id' => $requestId, 'folio' => $folio], 201);
}

header('Allow: GET, POST');
jsonResponse(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED'], 405);
