<?php
declare(strict_types=1);

require_once __DIR__ . '/api/bootstrap.php';

header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$token = strtolower(trim((string) ($_GET['token'] ?? '')));

function photoNotFound(string $reason): never
{
    header('Cache-Control: no-store');
    header('X-CDHQ-Photo-Status: ' . $reason);
    http_response_code(404);
    exit;
}

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    photoNotFound('invalid-token');
}

$query = database()->prepare(
    'SELECT profile_photo_filename, profile_photo_token
     FROM users
     WHERE profile_photo_token = :token AND active = 1
     LIMIT 1'
);
$query->execute(['token' => $token]);
$photo = $query->fetch();
if (!$photo) {
    photoNotFound('record-not-found');
}

$storedFilename = (string) ($photo['profile_photo_filename'] ?? '');
$filenames = [];
if (preg_match('/^[a-f0-9]{64}\.jpg$/', $storedFilename)) {
    $filenames[] = $storedFilename;
}
$filenames[] = $token . '.jpg';
$directory = __DIR__ . '/storage/profile-photos';
$path = null;
foreach (array_unique($filenames) as $filename) {
    $candidate = $directory . '/' . $filename;
    if (is_file($candidate) && is_readable($candidate)) {
        $path = $candidate;
        break;
    }
}
if ($path === null) {
    error_log('CDHQ profile photo missing for user photo token ending in ' . substr($token, -8));
    photoNotFound($storedFilename === '' ? 'filename-missing' : 'file-missing');
}

header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=31536000, immutable');
readfile($path);
