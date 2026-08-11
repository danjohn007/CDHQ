<?php
/**
 * Imágenes de noticias y eventos.
 *
 * Réplica exacta del procesamiento que hace `api-mobile/api/image_upload.php`
 * (mismo límite de 1800 px, misma calidad 88, mismo fondo blanco y mismo
 * nombre `<token>.jpg`), para que una imagen subida desde el portal se vea
 * idéntica a una subida desde la app.
 *
 * La diferencia es el manejo de errores: la API responde JSON y termina; aquí
 * devolvemos un mensaje para que el controlador lo muestre como flash y el
 * administrador no pierda el formulario que estaba llenando.
 */
class ContentImage {

    /** Última razón de fallo, en lenguaje para el administrador */
    private static string $error = '';

    public static function lastError(): string {
        return self::$error;
    }

    /**
     * Procesa un archivo subido y lo guarda como `<token>.jpg`.
     *
     * @return string|null El token de 64 caracteres, o null si falló.
     */
    public static function store(array $upload): ?string {
        self::$error = '';

        if (!isset($upload['error']) || (int) $upload['error'] !== UPLOAD_ERR_OK) {
            $demasiadoGrande = isset($upload['error'])
                && in_array((int) $upload['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
            self::$error = $demasiadoGrande
                ? 'La imagen supera el límite permitido por el servidor.'
                : 'No fue posible recibir la imagen.';
            return null;
        }
        if ((int) ($upload['size'] ?? 0) < 1 || (int) $upload['size'] > 8 * 1024 * 1024) {
            self::$error = 'La imagen debe pesar máximo 8 MB.';
            return null;
        }
        if (!is_uploaded_file((string) ($upload['tmp_name'] ?? ''))) {
            self::$error = 'El archivo recibido no es una subida válida.';
            return null;
        }
        if (!extension_loaded('gd')) {
            self::$error = 'El servidor necesita la extensión PHP GD para procesar imágenes.';
            return null;
        }

        $rutaTemporal = (string) $upload['tmp_name'];
        $info   = @getimagesize($rutaTemporal);
        $mime   = is_array($info) ? (string) ($info['mime'] ?? '') : '';
        $ancho  = (int) ($info[0] ?? 0);
        $alto   = (int) ($info[1] ?? 0);

        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)
            || $ancho < 1 || $alto < 1 || $ancho > 10000 || $alto > 10000
            || $ancho * $alto > 16000000) {
            self::$error = 'Usa una imagen JPG, PNG o WEBP válida.';
            return null;
        }

        $binario = file_get_contents($rutaTemporal);
        $origen  = $binario === false ? false : @imagecreatefromstring($binario);
        if (!$origen) {
            self::$error = 'La imagen no pudo procesarse.';
            return null;
        }

        // Las fotos de teléfono llegan giradas: sin esto salen acostadas en la app.
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif   = @exif_read_data($rutaTemporal, 'IFD0');
            $grados = match ((int) ($exif['Orientation'] ?? 1)) {
                3 => 180, 6 => -90, 8 => 90, default => 0,
            };
            if ($grados !== 0) {
                $girada = imagerotate($origen, $grados, 0xffffff);
                if ($girada) {
                    imagedestroy($origen);
                    $origen = $girada;
                }
            }
        }

        $anchoOrigen = imagesx($origen);
        $altoOrigen  = imagesy($origen);
        $escala      = min(1, 1800 / max($anchoOrigen, $altoOrigen));
        $anchoDestino = max(1, (int) round($anchoOrigen * $escala));
        $altoDestino  = max(1, (int) round($altoOrigen  * $escala));

        $procesada = imagecreatetruecolor($anchoDestino, $altoDestino);
        imagefill($procesada, 0, 0, imagecolorallocate($procesada, 255, 255, 255));
        imagecopyresampled($procesada, $origen, 0, 0, 0, 0,
            $anchoDestino, $altoDestino, $anchoOrigen, $altoOrigen);
        imagedestroy($origen);

        $directorio = CONTENT_IMAGE_DIR;
        if (!is_dir($directorio) && !mkdir($directorio, 0750, true) && !is_dir($directorio)) {
            imagedestroy($procesada);
            self::$error = 'No se pudo preparar el almacenamiento de imágenes. '
                         . 'Verifica que exista ' . $directorio . ' y que PHP pueda escribir ahí.';
            return null;
        }

        $token   = bin2hex(random_bytes(32));
        $destino = $directorio . '/' . $token . '.jpg';
        if (!imagejpeg($procesada, $destino, 88)) {
            imagedestroy($procesada);
            self::$error = 'No se pudo guardar la imagen. Revisa los permisos de ' . $directorio . '.';
            return null;
        }
        imagedestroy($procesada);

        @chmod($destino, 0640);
        clearstatcache(true, $destino);
        if (!is_file($destino) || !is_readable($destino) || filesize($destino) < 1) {
            @unlink($destino);
            self::$error = 'La imagen no quedó disponible después de guardarse. '
                         . 'Revisa los permisos de ' . $directorio . '.';
            return null;
        }

        return $token;
    }

    /**
     * Borra el archivo de una imagen anterior.
     *
     * El patrón del nombre se valida antes de tocar el disco: es lo que impide
     * que un valor manipulado en la base haga borrar un archivo ajeno.
     */
    public static function delete(?string $filename): void {
        $filename = (string) $filename;
        if (!preg_match('/^[a-f0-9]{64}\.jpg$/', $filename)) {
            return;
        }
        $ruta = CONTENT_IMAGE_DIR . '/' . $filename;
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }

    /** URL pública de la imagen, la misma que consume la app */
    public static function url(?string $token): ?string {
        $token = (string) $token;
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return MOBILE_API_URL . '/content-image.php?token=' . rawurlencode($token);
    }
}
