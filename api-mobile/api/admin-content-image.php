<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/image_upload.php';
requireMethod('POST');
$admin = requireRoles('ADMINISTRADOR');
$postId = filter_var($_POST['post_id'] ?? null, FILTER_VALIDATE_INT);
if (!$postId) jsonResponse(['ok' => false, 'error' => 'INVALID_ID'], 422);
$upload = $_FILES['image'] ?? null;
if (!is_array($upload)) jsonResponse(['ok' => false, 'error' => 'IMAGE_REQUIRED', 'message' => 'Selecciona una imagen.'], 422);

$db = database();
$query = $db->prepare('SELECT image_filename FROM content_posts WHERE id = :id AND deleted_at IS NULL LIMIT 1');
$query->execute(['id' => $postId]);
$post = $query->fetch();
if (!$post) jsonResponse(['ok' => false, 'error' => 'CONTENT_NOT_FOUND'], 404);

$token = bin2hex(random_bytes(32));
$directory = dirname(__DIR__) . '/storage/content-images';
$filename = saveUploadedJpeg($upload, $directory, $token);
try {
    $params = ['filename' => $filename, 'token' => $token, 'id' => $postId];
    $set = 'image_filename=:filename,image_token=:token';
    if (tableHasColumn($db, 'content_posts', 'updated_by')) {
        $set .= ',updated_by=:admin';
        $params['admin'] = $admin['id'];
    }
    $db->prepare('UPDATE content_posts SET ' . $set . ' WHERE id=:id')->execute($params);
} catch (Throwable $error) {
    @unlink($directory . '/' . $filename);
    throw $error;
}
$previous = (string) ($post['image_filename'] ?? '');
if (preg_match('/^[a-f0-9]{64}\.jpg$/', $previous) && $previous !== $filename) {
    $previousPath = $directory . '/' . $previous;
    if (is_file($previousPath)) @unlink($previousPath);
}
jsonResponse(['ok' => true, 'image_url' => publicContentImageUrl($token)]);
