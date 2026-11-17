<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$staff = requireRoles('ASESOR', 'ADMINISTRADOR');
$db = database();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $input = jsonInput();
    $requestId = filter_var($input['request_id'] ?? null, FILTER_VALIDATE_INT);
    $action = (string) ($input['action'] ?? '');
    if (!$requestId) jsonResponse(['ok' => false, 'error' => 'INVALID_REQUEST'], 422);
    $find = $db->prepare('SELECT id, user_id, folio, subject, assigned_to, status FROM support_requests WHERE id = :id LIMIT 1');
    $find->execute(['id' => $requestId]);
    $request = $find->fetch();
    if (!$request) jsonResponse(['ok' => false, 'error' => 'REQUEST_NOT_FOUND'], 404);

    if ($action === 'claim') {
        if ($staff['app_role'] !== 'ASESOR') jsonResponse(['ok' => false, 'error' => 'INVALID_ACTION'], 422);
        if ($request['assigned_to'] && (int) $request['assigned_to'] !== (int) $staff['id']) jsonResponse(['ok' => false, 'error' => 'ALREADY_ASSIGNED', 'message' => 'Otro asesor ya tomó este caso.'], 409);
        $db->prepare('UPDATE support_requests SET assigned_to = :staff, status = "EN_REVISION" WHERE id = :id')->execute(['staff' => $staff['id'], 'id' => $requestId]);
        $newStatus = 'EN_REVISION';
    } elseif ($action === 'status') {
        $newStatus = strtoupper((string) ($input['status'] ?? ''));
        $allowed = ['EN_REVISION', 'INFORMACION_REQUERIDA', 'EN_ATENCION', 'CANALIZADA', 'CERRADA', 'CANCELADA'];
        if (!in_array($newStatus, $allowed, true)) jsonResponse(['ok' => false, 'error' => 'INVALID_STATUS'], 422);
        if ($staff['app_role'] === 'ASESOR' && (int) $request['assigned_to'] !== (int) $staff['id']) jsonResponse(['ok' => false, 'error' => 'REQUEST_NOT_ASSIGNED', 'message' => 'Primero debes tomar el caso.'], 403);
        $db->prepare('UPDATE support_requests SET status = :status, closed_at = IF(:status_check = "CERRADA", NOW(), closed_at) WHERE id = :id')->execute(['status' => $newStatus, 'status_check' => $newStatus, 'id' => $requestId]);
    } else {
        jsonResponse(['ok' => false, 'error' => 'INVALID_ACTION'], 422);
    }
    $db->prepare(
        'INSERT INTO support_request_updates (support_request_id, author_id, update_type, message, previous_status, new_status, visible_to_user)
         VALUES (:request_id, :author_id, "CAMBIO_ESTADO", :message, :previous, :new, 1)'
    )->execute(['request_id' => $requestId, 'author_id' => $staff['id'], 'message' => trim((string) ($input['message'] ?? 'Actualización de estado')), 'previous' => $request['status'], 'new' => $newStatus]);

    // Sólo se avisa cuando el estado cambia de verdad, para no repetir el mismo
    // mensaje si el personal pulsa dos veces el mismo botón.
    if ($newStatus !== $request['status']) {
        $statusMessages = [
            'EN_REVISION' => 'Un asesor de CDHQ comenzó a revisar tu caso.',
            'INFORMACION_REQUERIDA' => 'CDHQ necesita más información para continuar con tu caso. Abre la app para responder.',
            'EN_ATENCION' => 'Tu caso ya está en atención por parte de un asesor de CDHQ.',
            'CANALIZADA' => 'Tu caso fue canalizado a la instancia correspondiente.',
            'CERRADA' => 'Tu caso fue concluido. Si necesitas retomarlo, crea una nueva solicitud.',
            'CANCELADA' => 'Tu caso fue cancelado.',
        ];
        notifyUser(
            (int) $request['user_id'],
            'SOLICITUD_ESTADO',
            'Actualización del folio ' . $request['folio'],
            $statusMessages[$newStatus] ?? 'El estado de tu solicitud cambió.',
            '/requests'
        );
    }
}

if (!in_array($method, ['GET', 'POST'], true)) { header('Allow: GET, POST'); jsonResponse(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED'], 405); }

$sql = 'SELECT sr.id, sr.folio, sr.subject, sr.description, sr.status, sr.urgency, sr.assigned_to, sr.created_at, sr.updated_at,
               u.name AS user_name, u.email AS user_email, advisor.name AS advisor_name
        FROM support_requests sr JOIN users u ON u.id = sr.user_id LEFT JOIN users advisor ON advisor.id = sr.assigned_to';
$params = [];
if ($staff['app_role'] === 'ASESOR') { $sql .= ' WHERE sr.assigned_to = :id OR sr.assigned_to IS NULL'; $params['id'] = $staff['id']; }
$sql .= ' ORDER BY FIELD(sr.urgency, "EMERGENCIA", "ALTA", "MEDIA", "BAJA"), sr.updated_at DESC';
$query = $db->prepare($sql); $query->execute($params);
jsonResponse(['ok' => true, 'requests' => $query->fetchAll()]);
