<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireMethod('GET');
currentUserId();

$db = database();
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$type = strtoupper(trim((string) ($_GET['type'] ?? '')));
$featured = filter_var($_GET['featured'] ?? false, FILTER_VALIDATE_BOOL);

$select =
    'SELECT id, type, title, summary, body, image_token, featured, published_at,
            event_start, event_end, event_location, event_address, latitude, longitude,
            registration_url, created_at, updated_at
     FROM content_posts';
$publicWhere = ' status = "PUBLICADA" AND deleted_at IS NULL AND published_at IS NOT NULL AND published_at <= NOW()';

if ($id) {
    $query = $db->prepare($select . ' WHERE id = :id AND' . $publicWhere . ' LIMIT 1');
    $query->execute(['id' => $id]);
    $post = $query->fetch();
    if (!$post) {
        jsonResponse(['ok' => false, 'error' => 'CONTENT_NOT_FOUND', 'message' => 'La publicación no está disponible.'], 404);
    }
    $post['id'] = (int) $post['id'];
    $post['featured'] = (bool) $post['featured'];
    $post['image_url'] = $post['image_token'] ? publicContentImageUrl($post['image_token']) : null;
    unset($post['image_token']);
    jsonResponse(['ok' => true, 'post' => $post]);
}

$where = [$publicWhere];
$params = [];
if (in_array($type, ['NOTICIA', 'EVENTO'], true)) {
    $where[] = 'type = :type';
    $params['type'] = $type;
}
if ($featured) {
    $where[] = 'featured = 1';
}
$limit = $featured ? 5 : 100;
$order = $featured
    ? 'featured DESC, published_at DESC'
    : 'CASE WHEN type = "EVENTO" AND event_start >= NOW() THEN 0 ELSE 1 END, CASE WHEN type = "EVENTO" THEN event_start ELSE published_at END DESC';
$query = $db->prepare($select . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $order . ' LIMIT ' . $limit);
$query->execute($params);
$posts = $query->fetchAll();
foreach ($posts as &$post) {
    $post['id'] = (int) $post['id'];
    $post['featured'] = (bool) $post['featured'];
    $post['image_url'] = $post['image_token'] ? publicContentImageUrl($post['image_token']) : null;
    unset($post['image_token']);
}
unset($post);
jsonResponse(['ok' => true, 'posts' => $posts]);
