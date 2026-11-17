<?php
declare(strict_types=1);

require_once __DIR__ . '/api/bootstrap.php';

header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$token = strtolower(trim((string) ($_GET['token'] ?? '')));
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(404);
    exit;
}
$query = database()->prepare(
    'SELECT image_filename FROM content_posts
     WHERE image_token = :token AND deleted_at IS NULL LIMIT 1'
);
$query->execute(['token' => $token]);
$filename = (string) ($query->fetchColumn() ?: '');
if (!preg_match('/^[a-f0-9]{64}\.jpg$/', $filename)) {
    http_response_code(404);
    exit;
}
$path = __DIR__ . '/storage/content-images/' . $filename;
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit;
}
header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=31536000, immutable');
readfile($path);
