<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireMethod('POST');

$userId = currentUserId();
$upload = $_FILES['photo'] ?? null;
if (!is_array($upload) || !isset($upload['error'], $upload['tmp_name'], $upload['size'])) {
    jsonResponse(['ok' => false, 'error' => 'PHOTO_REQUIRED', 'message' => 'Selecciona una fotografía.'], 422);
}
if ((int) $upload['error'] !== UPLOAD_ERR_OK) {
    $tooLarge = in_array((int) $upload['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
    jsonResponse([
        'ok' => false,
        'error' => $tooLarge ? 'PHOTO_TOO_LARGE' : 'PHOTO_UPLOAD_FAILED',
        'message' => $tooLarge ? 'La fotografía supera el límite permitido por el servidor.' : 'No fue posible recibir la fotografía.',
    ], 422);
}
if ((int) $upload['size'] < 1 || (int) $upload['size'] > 8 * 1024 * 1024) {
    jsonResponse(['ok' => false, 'error' => 'PHOTO_TOO_LARGE', 'message' => 'La fotografía debe pesar máximo 8 MB.'], 422);
}
if (!is_uploaded_file((string) $upload['tmp_name'])) {
    jsonResponse(['ok' => false, 'error' => 'INVALID_PHOTO_UPLOAD'], 422);
}
if (!extension_loaded('gd')) {
    jsonResponse(['ok' => false, 'error' => 'IMAGE_PROCESSING_UNAVAILABLE', 'message' => 'El servidor necesita la extensión PHP GD para procesar fotografías.'], 503);
}

$temporaryPath = (string) $upload['tmp_name'];
$imageInfo = @getimagesize($temporaryPath);
$mime = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
$width = (int) ($imageInfo[0] ?? 0);
$height = (int) ($imageInfo[1] ?? 0);
if (!in_array($mime, $allowedMimes, true) || $width < 1 || $height < 1 || $width > 10000 || $height > 10000 || $width * $height > 16000000) {
    jsonResponse(['ok' => false, 'error' => 'INVALID_PHOTO', 'message' => 'Usa una imagen JPG, PNG o WEBP válida.'], 422);
}

$binary = file_get_contents($temporaryPath);
$source = $binary === false ? false : @imagecreatefromstring($binary);
if (!$source) {
    jsonResponse(['ok' => false, 'error' => 'INVALID_PHOTO', 'message' => 'La fotografía no pudo procesarse.'], 422);
}

if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
    $exif = @exif_read_data($temporaryPath, 'IFD0');
    $orientation = (int) ($exif['Orientation'] ?? 1);
    $degrees = match ($orientation) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
    if ($degrees !== 0) {
        $rotated = imagerotate($source, $degrees, 0xffffff);
        if ($rotated) {
            imagedestroy($source);
            $source = $rotated;
        }
    }
}

$sourceWidth = imagesx($source);
$sourceHeight = imagesy($source);
$scale = min(1, 1600 / max($sourceWidth, $sourceHeight));
$targetWidth = max(1, (int) round($sourceWidth * $scale));
$targetHeight = max(1, (int) round($sourceHeight * $scale));
$processed = imagecreatetruecolor($targetWidth, $targetHeight);
$white = imagecolorallocate($processed, 255, 255, 255);
imagefill($processed, 0, 0, $white);
imagecopyresampled($processed, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
imagedestroy($source);

$directory = dirname(__DIR__) . '/storage/profile-photos';
if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
    imagedestroy($processed);
    jsonResponse(['ok' => false, 'error' => 'PHOTO_STORAGE_UNAVAILABLE'], 503);
}

$photoToken = bin2hex(random_bytes(32));
$filename = $photoToken . '.jpg';
$destination = $directory . '/' . $filename;
if (!imagejpeg($processed, $destination, 88)) {
    imagedestroy($processed);
    jsonResponse(['ok' => false, 'error' => 'PHOTO_SAVE_FAILED'], 500);
}
imagedestroy($processed);
@chmod($destination, 0640);
clearstatcache(true, $destination);
if (!is_file($destination) || !is_readable($destination) || filesize($destination) < 1) {
    @unlink($destination);
    jsonResponse(['ok' => false, 'error' => 'PHOTO_SAVE_FAILED', 'message' => 'El servidor no pudo conservar la fotografía.'], 500);
}

$db = database();
$db->beginTransaction();
try {
    $current = $db->prepare('SELECT profile_photo_filename FROM users WHERE id = :id AND active = 1 FOR UPDATE');
    $current->execute(['id' => $userId]);
    $user = $current->fetch();
    if (!$user) {
        throw new RuntimeException('USER_NOT_FOUND');
    }
    $db->prepare(
        'UPDATE users
         SET profile_photo_filename = :filename, profile_photo_token = :token, profile_photo_updated_at = NOW()
         WHERE id = :id'
    )->execute(['filename' => $filename, 'token' => $photoToken, 'id' => $userId]);
    $db->prepare(
        'INSERT INTO activity_log (user_id, action, entity, entity_id, details, ip)
         VALUES (:user_id, "UPDATE_PROFILE_PHOTO", "users", :entity_id, NULL, :ip)'
    )->execute(['user_id' => $userId, 'entity_id' => $userId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    @unlink($destination);
    throw $error;
}

$previousFilename = (string) ($user['profile_photo_filename'] ?? '');
if (preg_match('/^[a-f0-9]{64}\.jpg$/', $previousFilename)) {
    $previousPath = $directory . '/' . $previousFilename;
    if (is_file($previousPath)) {
        @unlink($previousPath);
    }
}

jsonResponse(['ok' => true, 'photo_url' => publicProfilePhotoUrl($photoToken)]);
