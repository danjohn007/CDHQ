<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$admin = requireRoles('ADMINISTRADOR');
$db = database();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$supportsCreatedBy = tableHasColumn($db, 'content_posts', 'created_by');
$supportsUpdatedBy = tableHasColumn($db, 'content_posts', 'updated_by');

function adminDateTime(mixed $value, string $field, bool $required = false): ?string
{
    $text = trim((string) ($value ?? ''));
    if ($text === '') {
        if ($required) jsonResponse(['ok' => false, 'error' => 'INVALID_' . strtoupper($field), 'message' => 'Completa la fecha y hora requeridas.'], 422);
        return null;
    }
    try {
        return (new DateTimeImmutable($text))->format('Y-m-d H:i:s');
    } catch (Throwable) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_' . strtoupper($field), 'message' => 'Usa una fecha y hora válidas.'], 422);
    }
}

function optionalHttpUrl(mixed $value): ?string
{
    $url = trim((string) ($value ?? ''));
    if ($url === '') return null;
    if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_URL', 'message' => 'El enlace debe iniciar con http:// o https://.'], 422);
    }
    return $url;
}

if ($method === 'POST') {
    $input = jsonInput();
    $action = strtolower(trim((string) ($input['action'] ?? 'save')));
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);

    if ($action === 'delete') {
        if (!$id) jsonResponse(['ok' => false, 'error' => 'INVALID_ID'], 422);
        $current = $db->prepare('SELECT image_filename FROM content_posts WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $current->execute(['id' => $id]);
        $post = $current->fetch();
        if (!$post) jsonResponse(['ok' => false, 'error' => 'CONTENT_NOT_FOUND'], 404);
        $deleteParams = ['id' => $id];
        $deleteSql = 'UPDATE content_posts SET deleted_at = NOW()';
        if ($supportsUpdatedBy) {
            $deleteSql .= ', updated_by = :admin';
            $deleteParams['admin'] = $admin['id'];
        }
        $db->prepare($deleteSql . ' WHERE id = :id')->execute($deleteParams);
        $filename = (string) ($post['image_filename'] ?? '');
        if (preg_match('/^[a-f0-9]{64}\.jpg$/', $filename)) {
            $path = dirname(__DIR__) . '/storage/content-images/' . $filename;
            if (is_file($path)) @unlink($path);
        }
        jsonResponse(['ok' => true]);
    }

    $type = strtoupper(trim((string) ($input['type'] ?? '')));
    $title = trim((string) ($input['title'] ?? ''));
    $summary = trim((string) ($input['summary'] ?? ''));
    $body = trim((string) ($input['body'] ?? ''));
    $status = strtoupper(trim((string) ($input['status'] ?? 'BORRADOR')));
    if (!in_array($type, ['NOTICIA', 'EVENTO'], true)) jsonResponse(['ok' => false, 'error' => 'INVALID_TYPE'], 422);
    if (textLength($title) < 5 || textLength($title) > 180) jsonResponse(['ok' => false, 'error' => 'INVALID_TITLE', 'message' => 'El título debe tener entre 5 y 180 caracteres.'], 422);
    if (textLength($summary) < 10 || textLength($summary) > 350) jsonResponse(['ok' => false, 'error' => 'INVALID_SUMMARY', 'message' => 'El resumen debe tener entre 10 y 350 caracteres.'], 422);
    if (textLength($body) < 20 || textLength($body) > 100000) jsonResponse(['ok' => false, 'error' => 'INVALID_BODY', 'message' => 'El contenido debe tener entre 20 y 100,000 caracteres.'], 422);
    if (!in_array($status, ['BORRADOR', 'PUBLICADA'], true)) jsonResponse(['ok' => false, 'error' => 'INVALID_STATUS'], 422);

    $eventStart = $type === 'EVENTO' ? adminDateTime($input['event_start'] ?? null, 'event_start', true) : null;
    $eventEnd = $type === 'EVENTO' ? adminDateTime($input['event_end'] ?? null, 'event_end') : null;
    if ($eventEnd && $eventStart && $eventEnd < $eventStart) jsonResponse(['ok' => false, 'error' => 'INVALID_EVENT_RANGE', 'message' => 'La fecha final no puede ser anterior al inicio.'], 422);
    $publishedAt = adminDateTime($input['published_at'] ?? null, 'published_at');
    if ($status === 'PUBLICADA' && !$publishedAt) $publishedAt = date('Y-m-d H:i:s');
    $latitude = trim((string) ($input['latitude'] ?? ''));
    $longitude = trim((string) ($input['longitude'] ?? ''));
    $latitude = $latitude === '' ? null : filter_var($latitude, FILTER_VALIDATE_FLOAT);
    $longitude = $longitude === '' ? null : filter_var($longitude, FILTER_VALIDATE_FLOAT);
    if ($latitude !== null && ($latitude === false || $latitude < -90 || $latitude > 90)) jsonResponse(['ok' => false, 'error' => 'INVALID_LATITUDE'], 422);
    if ($longitude !== null && ($longitude === false || $longitude < -180 || $longitude > 180)) jsonResponse(['ok' => false, 'error' => 'INVALID_LONGITUDE'], 422);

    $values = [
        'type' => $type, 'title' => $title, 'summary' => $summary, 'body' => $body,
        'status' => $status, 'featured' => filter_var($input['featured'] ?? false, FILTER_VALIDATE_BOOL) ? 1 : 0,
        'published_at' => $publishedAt, 'event_start' => $eventStart, 'event_end' => $eventEnd,
        'event_location' => $type === 'EVENTO' ? (trim((string) ($input['event_location'] ?? '')) ?: null) : null,
        'event_address' => $type === 'EVENTO' ? (trim((string) ($input['event_address'] ?? '')) ?: null) : null,
        'latitude' => $type === 'EVENTO' ? $latitude : null, 'longitude' => $type === 'EVENTO' ? $longitude : null,
        'registration_url' => $type === 'EVENTO' ? optionalHttpUrl($input['registration_url'] ?? null) : null,
    ];
    $wasAlreadyPublished = false;
    if ($id) {
        $previous = $db->prepare('SELECT status FROM content_posts WHERE id = :id AND deleted_at IS NULL');
        $previous->execute(['id' => $id]);
        $wasAlreadyPublished = $previous->fetchColumn() === 'PUBLICADA';

        $values['id'] = $id;
        $set = 'type=:type,title=:title,summary=:summary,body=:body,status=:status,featured=:featured,
             published_at=:published_at,event_start=:event_start,event_end=:event_end,event_location=:event_location,
             event_address=:event_address,latitude=:latitude,longitude=:longitude,registration_url=:registration_url';
        if ($supportsUpdatedBy) {
            $set .= ',updated_by=:admin';
            $values['admin'] = $admin['id'];
        }
        $query = $db->prepare('UPDATE content_posts SET ' . $set . ' WHERE id=:id AND deleted_at IS NULL');
        $query->execute($values);
        if ($query->rowCount() === 0) {
            $exists = $db->prepare('SELECT id FROM content_posts WHERE id = :id AND deleted_at IS NULL');
            $exists->execute(['id' => $id]);
            if (!$exists->fetch()) jsonResponse(['ok' => false, 'error' => 'CONTENT_NOT_FOUND'], 404);
        }
    } else {
        $columns = ['type', 'title', 'summary', 'body', 'status', 'featured', 'published_at', 'event_start', 'event_end', 'event_location', 'event_address', 'latitude', 'longitude', 'registration_url'];
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
        if ($supportsCreatedBy) {
            $columns[] = 'created_by';
            $placeholders[] = ':created_by';
            $values['created_by'] = $admin['id'];
        }
        if ($supportsUpdatedBy) {
            $columns[] = 'updated_by';
            $placeholders[] = ':updated_by';
            $values['updated_by'] = $admin['id'];
        }
        $query = $db->prepare('INSERT INTO content_posts (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', $placeholders) . ')');
        $query->execute($values);
        $id = (int) $db->lastInsertId();
    }

    /*
     * Sólo se avisa la primera vez que una publicación pasa a PUBLICADA. Así,
     * corregir una errata en una noticia ya publicada no vuelve a notificar a
     * todos los usuarios.
     */
    if ($status === 'PUBLICADA' && !$wasAlreadyPublished) {
        $isEvent = $type === 'EVENTO';
        notifyAllCitizens(
            $isEvent ? 'EVENTO_PUBLICADO' : 'NOTICIA_PUBLICADA',
            $isEvent ? 'Nuevo evento de CDHQ' : 'Nueva publicación de CDHQ',
            $values['title'] . ' — ' . $values['summary'],
            '/content/' . (int) $id
        );
    }

    jsonResponse(['ok' => true, 'id' => (int) $id]);
}

if ($method !== 'GET') {
    header('Allow: GET, POST');
    jsonResponse(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED'], 405);
}
$posts = $db->query(
    'SELECT id,type,title,summary,body,image_token,status,featured,published_at,event_start,event_end,event_location,event_address,latitude,longitude,registration_url,created_at,updated_at
     FROM content_posts WHERE deleted_at IS NULL ORDER BY updated_at DESC LIMIT 200'
)->fetchAll();
foreach ($posts as &$post) {
    $post['id'] = (int) $post['id'];
    $post['featured'] = (bool) $post['featured'];
    $post['image_url'] = $post['image_token'] ? publicContentImageUrl($post['image_token']) : null;
    unset($post['image_token']);
}
unset($post);
jsonResponse(['ok' => true, 'posts' => $posts]);
