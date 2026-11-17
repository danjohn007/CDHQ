<?php
declare(strict_types=1);

function saveUploadedJpeg(array $upload, string $directory, string $token): string
{
    if (!isset($upload['error'], $upload['tmp_name'], $upload['size']) || (int) $upload['error'] !== UPLOAD_ERR_OK) {
        $tooLarge = isset($upload['error']) && in_array((int) $upload['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
        jsonResponse(['ok' => false, 'error' => $tooLarge ? 'IMAGE_TOO_LARGE' : 'IMAGE_UPLOAD_FAILED', 'message' => $tooLarge ? 'La imagen supera el límite permitido por el servidor.' : 'No fue posible recibir la imagen.'], 422);
    }
    if ((int) $upload['size'] < 1 || (int) $upload['size'] > 8 * 1024 * 1024) {
        jsonResponse(['ok' => false, 'error' => 'IMAGE_TOO_LARGE', 'message' => 'La imagen debe pesar máximo 8 MB.'], 422);
    }
    if (!is_uploaded_file((string) $upload['tmp_name'])) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_IMAGE_UPLOAD'], 422);
    }
    if (!extension_loaded('gd')) {
        jsonResponse(['ok' => false, 'error' => 'IMAGE_PROCESSING_UNAVAILABLE', 'message' => 'El servidor necesita la extensión PHP GD para procesar imágenes.'], 503);
    }

    $temporaryPath = (string) $upload['tmp_name'];
    $imageInfo = @getimagesize($temporaryPath);
    $mime = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';
    $width = (int) ($imageInfo[0] ?? 0);
    $height = (int) ($imageInfo[1] ?? 0);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || $width < 1 || $height < 1 || $width > 10000 || $height > 10000 || $width * $height > 16000000) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_IMAGE', 'message' => 'Usa una imagen JPG, PNG o WEBP válida.'], 422);
    }
    $binary = file_get_contents($temporaryPath);
    $source = $binary === false ? false : @imagecreatefromstring($binary);
    if (!$source) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_IMAGE', 'message' => 'La imagen no pudo procesarse.'], 422);
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
    $scale = min(1, 1800 / max($sourceWidth, $sourceHeight));
    $targetWidth = max(1, (int) round($sourceWidth * $scale));
    $targetHeight = max(1, (int) round($sourceHeight * $scale));
    $processed = imagecreatetruecolor($targetWidth, $targetHeight);
    $white = imagecolorallocate($processed, 255, 255, 255);
    imagefill($processed, 0, 0, $white);
    imagecopyresampled($processed, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
    imagedestroy($source);

    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        imagedestroy($processed);
        jsonResponse(['ok' => false, 'error' => 'IMAGE_STORAGE_UNAVAILABLE', 'message' => 'No se pudo preparar el almacenamiento de imágenes. Verifica que la carpeta storage/content-images exista y permita escritura para PHP.'], 503);
    }
    $filename = $token . '.jpg';
    $destination = $directory . '/' . $filename;
    if (!imagejpeg($processed, $destination, 88)) {
        imagedestroy($processed);
        jsonResponse(['ok' => false, 'error' => 'IMAGE_SAVE_FAILED', 'message' => 'No se pudo guardar la imagen en el servidor. Verifica los permisos de storage/content-images.'], 500);
    }
    imagedestroy($processed);
    @chmod($destination, 0640);
    clearstatcache(true, $destination);
    if (!is_file($destination) || !is_readable($destination) || filesize($destination) < 1) {
        @unlink($destination);
        jsonResponse(['ok' => false, 'error' => 'IMAGE_SAVE_FAILED', 'message' => 'La imagen no quedó disponible después de guardarse. Verifica los permisos de storage/content-images.'], 500);
    }
    return $filename;
}
