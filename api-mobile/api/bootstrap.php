<?php
declare(strict_types=1);

const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

function jsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_FLAGS);
    exit;
}

/**
 * ¿Está permitido responder a este origen? Devuelve el origen a autorizar o
 * null si no procede.
 *
 * Se permite siempre el propio dominio y, cuando el CORS de desarrollo está
 * activo, el servidor de Expo corriendo en la máquina de quien programa
 * (localhost o una IP de red local). Nunca se responde "*": con credenciales
 * el navegador lo rechaza, y además obligaría a abrir la API a cualquier sitio.
 */
function allowedCorsOrigin(): ?string
{
    $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($origin === '' || strlen($origin) > 200) {
        return null;
    }
    $parts = parse_url($origin);
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = strtolower((string) ($parts['host'] ?? ''));
    if ($host === '' || !in_array($scheme, ['http', 'https'], true)) {
        return null;
    }

    // El propio dominio que atiende la petición.
    $self = strtolower(explode(':', (string) ($_SERVER['HTTP_HOST'] ?? ''))[0]);
    if ($self !== '' && ($host === $self || $host === 'www.' . $self || 'www.' . $host === $self)) {
        return $origin;
    }

    // Entorno de desarrollo: se puede apagar desde config.php con
    // 'app' => ['allow_dev_cors' => false].
    if (serverConfig()['app']['allow_dev_cors'] ?? true) {
        $esLocal = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
        $esRedLocal = (bool) preg_match('/^(192\.168\.\d{1,3}\.\d{1,3}|10\.\d{1,3}\.\d{1,3}\.\d{1,3}|172\.(1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3})$/', $host);
        if ($esLocal || $esRedLocal) {
            return $origin;
        }
    }

    return null;
}

/**
 * Cabeceras CORS y respuesta a la verificación previa del navegador.
 *
 * Sin esto, probar la aplicación en el navegador es imposible: el navegador
 * bloquea cada petición antes de enviarla y obliga a compilar para cualquier
 * prueba. Las aplicaciones nativas no pasan por CORS, así que esto no cambia
 * nada para iOS ni Android.
 */
function applyCorsHeaders(): void
{
    $origin = allowedCorsOrigin();
    // Vary siempre: la respuesta depende del origen y no debe cachearse cruzada.
    header('Vary: Origin');

    if ($origin !== null) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Max-Age: 600');
    }

    // La verificación previa no lleva cuerpo ni debe ejecutar el endpoint.
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code($origin !== null ? 204 : 403);
        exit;
    }
}

set_exception_handler(static function (Throwable $error): void {
    error_log((string) $error);
    $message = $error->getMessage();
    $missingContentTable = $error instanceof PDOException
        && str_contains($message, 'content_posts')
        && (str_contains($message, '42S02') || str_contains(lowercase($message), 'doesn\'t exist'));
    if ($missingContentTable) {
        jsonResponse([
            'ok' => false,
            'error' => 'CONTENT_MODULE_NOT_INSTALLED',
            'message' => 'El módulo de noticias y eventos no está instalado en la base de datos. Ejecuta la migración 04_add_news_and_events.sql en phpMyAdmin.',
        ], 503);
    }
    jsonResponse([
        'ok' => false,
        'error' => 'INTERNAL_SERVER_ERROR',
        'message' => 'El servidor no pudo completar la operación.',
    ], 500);
});

function lowercase(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function textLength(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

/**
 * Recorta respetando caracteres multibyte. El hosting puede no tener mbstring,
 * igual que ya contemplan lowercase() y textLength().
 */
function truncate(string $value, int $length): string
{
    return function_exists('mb_substr') ? mb_substr($value, 0, $length, 'UTF-8') : substr($value, 0, $length);
}

/** Codifica un encabezado de correo con acentos, con respaldo sin mbstring. */
function mimeHeader(string $value): string
{
    if (function_exists('mb_encode_mimeheader')) {
        return mb_encode_mimeheader($value, 'UTF-8');
    }
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function serverConfig(): array
{
    static $config = null;
    if (is_array($config)) {
        return $config;
    }

    $path = dirname(__DIR__) . '/config.php';
    if (!is_file($path)) {
        jsonResponse([
            'ok' => false,
            'error' => 'SERVER_NOT_CONFIGURED',
            'message' => 'No se encontró el archivo config.php del servidor.',
        ], 503);
    }

    $loaded = require $path;
    if (!is_array($loaded)) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_SERVER_CONFIG'], 503);
    }
    $config = $loaded;
    return $config;
}

function publicApplicationBaseUrl(): string
{
    $configuredBaseUrl = trim((string) (serverConfig()['app']['public_base_url'] ?? ''));
    if ($configuredBaseUrl !== '') {
        return rtrim($configuredBaseUrl, '/');
    }

    $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
    $forwardedSsl = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')));
    $requestScheme = strtolower(trim((string) ($_SERVER['REQUEST_SCHEME'] ?? '')));
    $secure = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || $forwardedProto === 'https'
        || $forwardedSsl === 'on'
        || $requestScheme === 'https'
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!preg_match('/^[a-z0-9.-]+(?::\d+)?$/i', $host)) {
        $host = 'localhost';
    }
    $hostname = strtolower(explode(':', $host)[0]);
    if (in_array($hostname, ['cdhq.digital', 'www.cdhq.digital'], true)) {
        $secure = true;
    }
    $scheme = $secure ? 'https' : 'http';
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/api-mobile/api/credential.php'));
    $publicPath = dirname(dirname($scriptName));

    return $scheme . '://' . $host . rtrim($publicPath, '/');
}

/**
 * Remitente de los correos del sistema. Si config.php no define uno, se deduce
 * del dominio que atiende la petición, de modo que la instalación funciona sin
 * editar la configuración del servidor.
 */
function mailSenderAddress(): string
{
    $configured = trim((string) (serverConfig()['mail']['from_address'] ?? ''));
    if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_EMAIL)) {
        return $configured;
    }

    $host = strtolower(explode(':', (string) ($_SERVER['HTTP_HOST'] ?? ''))[0]);
    $host = preg_replace('/^www\./', '', $host);
    if ($host === '' || !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $host)) {
        return '';
    }
    return 'no-reply@' . $host;
}

function publicCredentialUrl(string $token): string
{
    return publicApplicationBaseUrl() . '/credential.php?token=' . rawurlencode($token);
}

function publicProfilePhotoUrl(string $token): string
{
    return publicApplicationBaseUrl() . '/profile-photo.php?token=' . rawurlencode($token);
}

function publicContentImageUrl(string $token): string
{
    return publicApplicationBaseUrl() . '/content-image.php?token=' . rawurlencode($token);
}

function database(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $database = serverConfig()['database'] ?? [];
    $host = (string) ($database['host'] ?? 'localhost');
    $port = (string) ($database['port'] ?? '3306');
    $name = (string) ($database['name'] ?? '');
    $user = (string) ($database['user'] ?? '');
    $password = (string) ($database['password'] ?? '');

    if ($name === '' || $user === '' || $password === '' || $password === 'COLOCA_AQUI_LA_PASSWORD_MYSQL') {
        jsonResponse([
            'ok' => false,
            'error' => 'DATABASE_NOT_CONFIGURED',
            'message' => 'Completa los datos MySQL en config.php.',
        ], 503);
    }

    try {
        $connection = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        jsonResponse([
            'ok' => false,
            'error' => 'DATABASE_UNAVAILABLE',
            'message' => 'No fue posible conectar con la base de datos.',
        ], 503);
    }
    return $connection;
}

/**
 * Indica si una tabla existe. Permite que la API siga operando aunque todavía no
 * se haya ejecutado la migración 07, en lugar de responder un error 500.
 */
function tableExists(string $table): bool
{
    static $cache = [];
    if (!preg_match('/^[a-z_]+$/i', $table)) {
        return false;
    }
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }
    try {
        $query = database()->query('SHOW TABLES LIKE ' . database()->quote($table));
        return $cache[$table] = (bool) $query->fetch();
    } catch (Throwable $error) {
        error_log('No fue posible comprobar la tabla ' . $table . ': ' . $error->getMessage());
        return $cache[$table] = false;
    }
}

/** Mensaje único para las funciones que dependen de la migración 07. */
function requireMigration07(): void
{
    if (tableExists('auth_tokens')) {
        return;
    }
    jsonResponse([
        'ok' => false,
        'error' => 'MIGRATION_PENDING',
        'message' => 'Esta función necesita la migración 07_add_sessions_and_notifications.sql. Ejecútala en phpMyAdmin.',
    ], 503);
}

function tableHasColumn(PDO $db, string $table, string $column): bool
{
    static $cache = [];
    if (!preg_match('/^[a-z_]+$/i', $table) || !preg_match('/^[a-z_]+$/i', $column)) {
        return false;
    }
    $key = strtolower($table . '.' . $column);
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $query = $db->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $db->quote($column));
        return $cache[$key] = (bool) $query->fetch();
    } catch (Throwable $error) {
        error_log('Unable to inspect schema for ' . $key . ': ' . $error->getMessage());
        return $cache[$key] = false;
    }
}

function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('cdhq_mobile');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function requireMethod(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== $method) {
        header('Allow: ' . $method);
        jsonResponse(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED'], 405);
    }
}

function jsonInput(): array
{
    $input = json_decode(file_get_contents('php://input') ?: '{}', true);
    if (!is_array($input)) {
        jsonResponse(['ok' => false, 'error' => 'INVALID_JSON'], 400);
    }
    return $input;
}

/**
 * Lee el token enviado en la cabecera Authorization. Algunos servidores Apache
 * con PHP en modo CGI no exponen HTTP_AUTHORIZATION, por eso se revisan también
 * la variante redirigida y apache_request_headers().
 */
function bearerToken(): string
{
    $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if ($header === '' && function_exists('apache_request_headers')) {
        foreach (apache_request_headers() as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                $header = (string) $value;
                break;
            }
        }
    }
    if (!preg_match('/^Bearer\s+([A-Za-z0-9._-]{20,512})$/i', trim($header), $matches)) {
        return '';
    }
    return $matches[1];
}

function hashAuthToken(string $token): string
{
    return hash('sha256', $token);
}

/**
 * Crea un token persistente para el dispositivo. La base guarda únicamente el
 * hash, de modo que un respaldo filtrado no permite suplantar sesiones.
 */
/**
 * Crea el token persistente del dispositivo.
 *
 * Devuelve null si la migración 07 aún no se ejecutó: en ese caso el inicio de
 * sesión sigue funcionando con la cookie de sesión de siempre, de modo que subir
 * el código antes que el SQL no deja a nadie fuera de la aplicación.
 */
function issueAuthToken(int $userId, ?string $deviceName = null, ?string $platform = null): ?array
{
    if (!tableExists('auth_tokens')) {
        error_log('auth_tokens no existe: ejecuta 07_add_sessions_and_notifications.sql para habilitar la sesión persistente.');
        return null;
    }

    try {
        $token = bin2hex(random_bytes(32));
        $expiresAt = (new DateTimeImmutable('+180 days'))->format('Y-m-d H:i:s');
        $insert = database()->prepare(
            'INSERT INTO auth_tokens (user_id, token_hash, device_name, platform, last_used_at, expires_at)
             VALUES (:user_id, :token_hash, :device_name, :platform, NOW(), :expires_at)'
        );
        $insert->execute([
            'user_id' => $userId,
            'token_hash' => hashAuthToken($token),
            'device_name' => $deviceName !== null && $deviceName !== '' ? truncate($deviceName, 120) : null,
            'platform' => $platform !== null && $platform !== '' ? truncate($platform, 20) : null,
            'expires_at' => $expiresAt,
        ]);

        // Limpieza oportunista de tokens caducados o revocados hace más de un mes.
        database()->exec(
            'DELETE FROM auth_tokens
             WHERE expires_at < NOW() OR (revoked_at IS NOT NULL AND revoked_at < DATE_SUB(NOW(), INTERVAL 30 DAY))'
        );

        return ['token' => $token, 'expires_at' => $expiresAt];
    } catch (Throwable $error) {
        // Un fallo al emitir el token nunca debe impedir iniciar sesión.
        error_log('No fue posible emitir el token de dispositivo: ' . $error->getMessage());
        return null;
    }
}

function revokeAuthToken(string $token): void
{
    if ($token === '' || !tableExists('auth_tokens')) {
        return;
    }
    $update = database()->prepare(
        'UPDATE auth_tokens SET revoked_at = NOW() WHERE token_hash = :token_hash AND revoked_at IS NULL'
    );
    $update->execute(['token_hash' => hashAuthToken($token)]);
}

function revokeAllAuthTokens(int $userId): void
{
    if (!tableExists('auth_tokens')) {
        return;
    }
    $update = database()->prepare(
        'UPDATE auth_tokens SET revoked_at = NOW() WHERE user_id = :user_id AND revoked_at IS NULL'
    );
    $update->execute(['user_id' => $userId]);
}

/**
 * Resuelve el usuario del token de dispositivo. Devuelve null cuando no hay
 * token, está revocado o caducó, para que la sesión PHP siga funcionando.
 */
function userIdFromBearerToken(): ?int
{
    $token = bearerToken();
    if ($token === '' || !tableExists('auth_tokens')) {
        return null;
    }
    $query = database()->prepare(
        'SELECT id, user_id FROM auth_tokens
         WHERE token_hash = :token_hash AND revoked_at IS NULL AND expires_at > NOW()
         LIMIT 1'
    );
    $query->execute(['token_hash' => hashAuthToken($token)]);
    $row = $query->fetch();
    if (!$row) {
        return null;
    }
    // Se registra el uso como máximo una vez por hora para no escribir en cada
    // petición de una pantalla que refresca con frecuencia.
    $touch = database()->prepare(
        'UPDATE auth_tokens SET last_used_at = NOW()
         WHERE id = :id AND (last_used_at IS NULL OR last_used_at < DATE_SUB(NOW(), INTERVAL 1 HOUR))'
    );
    $touch->execute(['id' => (int) $row['id']]);
    return (int) $row['user_id'];
}

function currentUserId(): int
{
    $userId = optionalUserId();
    if (!$userId) {
        jsonResponse(['ok' => false, 'error' => 'UNAUTHENTICATED', 'message' => 'Tu sesión expiró. Inicia sesión de nuevo.'], 401);
    }
    return $userId;
}

/** Igual que currentUserId() pero devuelve 0 en lugar de responder 401. */
function optionalUserId(): int
{
    $fromToken = userIdFromBearerToken();
    if ($fromToken) {
        return $fromToken;
    }
    startSecureSession();
    return (int) (filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT) ?: 0);
}

function requireRoles(string ...$roles): array
{
    $userId = currentUserId();
    $query = database()->prepare(
        'SELECT id, name, email, app_role FROM users WHERE id = :id AND active = 1 LIMIT 1'
    );
    $query->execute(['id' => $userId]);
    $user = $query->fetch();
    if (!$user) {
        jsonResponse(['ok' => false, 'error' => 'UNAUTHENTICATED'], 401);
    }
    if (!in_array($user['app_role'], $roles, true)) {
        jsonResponse([
            'ok' => false,
            'error' => 'FORBIDDEN',
            'message' => 'Tu nivel de usuario no permite realizar esta acción.',
        ], 403);
    }
    return $user;
}

function publicUser(array $user): array
{
    return [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['app_role'],
    ];
}

/**
 * Guarda una notificación para cada usuario indicado y, si tienen la
 * aplicación instalada, la entrega también como notificación push.
 *
 * Nunca interrumpe la operación que la originó: si la notificación falla, la
 * acción principal (aprobar, publicar, cambiar estado) ya quedó guardada.
 *
 * @param int[] $userIds
 */
function notifyUsers(array $userIds, string $type, string $title, string $message, ?string $actionUrl = null): void
{
    $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
    if (!$userIds || !tableExists('notifications')) {
        return;
    }

    try {
        $insert = database()->prepare(
            'INSERT INTO notifications (user_id, type, title, message, action_url)
             VALUES (:user_id, :type, :title, :message, :action_url)'
        );
        foreach ($userIds as $userId) {
            $insert->execute([
                'user_id' => $userId,
                'type' => truncate($type, 60),
                'title' => truncate($title, 180),
                'message' => $message,
                'action_url' => $actionUrl !== null && $actionUrl !== '' ? truncate($actionUrl, 500) : null,
            ]);
        }
    } catch (Throwable $error) {
        error_log('No fue posible guardar la notificación: ' . $error->getMessage());
        return;
    }

    sendExpoPush($userIds, $title, $message, ['type' => $type, 'url' => $actionUrl]);
}

function notifyUser(int $userId, string $type, string $title, string $message, ?string $actionUrl = null): void
{
    notifyUsers([$userId], $type, $title, $message, $actionUrl);
}

/**
 * Notifica a todo el personal con alguno de los roles indicados.
 *
 * @param string[] $roles
 */
function notifyRoles(array $roles, string $type, string $title, string $message, ?string $actionUrl = null): void
{
    $roles = array_values(array_filter($roles, static fn ($role) => in_array($role, ['USUARIO', 'ASESOR', 'ADMINISTRADOR'], true)));
    if (!$roles) {
        return;
    }
    try {
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $query = database()->prepare("SELECT id FROM users WHERE active = 1 AND app_role IN ({$placeholders})");
        $query->execute($roles);
        $userIds = array_map('intval', $query->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $error) {
        error_log('No fue posible resolver los destinatarios: ' . $error->getMessage());
        return;
    }
    notifyUsers($userIds, $type, $title, $message, $actionUrl);
}

/**
 * Notifica a todos los usuarios ciudadanos activos. Se usa para publicaciones
 * de interés general (noticias y eventos).
 */
function notifyAllCitizens(string $type, string $title, string $message, ?string $actionUrl = null): void
{
    notifyRoles(['USUARIO'], $type, $title, $message, $actionUrl);
}

/**
 * Entrega notificaciones push a través del servicio de Expo. Los errores se
 * registran en el log sin afectar la respuesta al cliente.
 *
 * @param int[] $userIds
 */
function sendExpoPush(array $userIds, string $title, string $body, array $data = []): void
{
    if (!$userIds || !function_exists('curl_init') || !tableExists('push_tokens')) {
        return;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $query = database()->prepare(
            "SELECT token FROM push_tokens WHERE user_id IN ({$placeholders}) AND failure_count < 5"
        );
        $query->execute($userIds);
        $tokens = array_values(array_unique($query->fetchAll(PDO::FETCH_COLUMN)));
    } catch (Throwable $error) {
        error_log('No fue posible leer los tokens push: ' . $error->getMessage());
        return;
    }
    if (!$tokens) {
        return;
    }

    $data = array_filter($data, static fn ($value) => $value !== null && $value !== '');

    // Expo acepta hasta 100 mensajes por petición.
    foreach (array_chunk($tokens, 100) as $chunk) {
        $messages = array_map(static fn (string $token): array => [
            'to' => $token,
            'title' => $title,
            'body' => truncate($body, 178),
            'sound' => 'default',
            'priority' => 'high',
            'channelId' => 'default',
            'data' => $data,
        ], $chunk);

        $payload = json_encode($messages, JSON_FLAGS);
        if ($payload === false) {
            continue;
        }

        $curl = curl_init('https://exp.host/--/api/v2/push/send');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Accept-Encoding: gzip, deflate',
            ],
        ]);
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($response === false || $status >= 400) {
            error_log('Expo push falló (HTTP ' . $status . '): ' . ($curlError !== '' ? $curlError : (string) $response));
            continue;
        }
        registerPushDeliveryResult($chunk, (string) $response);
    }
}

/**
 * Marca los tokens que Expo reporta como inválidos. Tras cinco fallos el token
 * deja de usarse, evitando reenviar a aplicaciones desinstaladas.
 *
 * @param string[] $tokens
 */
function registerPushDeliveryResult(array $tokens, string $response): void
{
    $decoded = json_decode($response, true);
    $tickets = is_array($decoded) && isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : [];
    if (!$tickets) {
        return;
    }

    $failed = [];
    foreach (array_values($tickets) as $index => $ticket) {
        $isError = is_array($ticket) && ($ticket['status'] ?? '') === 'error';
        if ($isError && isset($tokens[$index])) {
            $failed[] = $tokens[$index];
        }
    }
    if (!$failed) {
        return;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($failed), '?'));
        database()
            ->prepare("UPDATE push_tokens SET failure_count = failure_count + 1 WHERE token IN ({$placeholders})")
            ->execute($failed);
    } catch (Throwable $error) {
        error_log('No fue posible marcar tokens push fallidos: ' . $error->getMessage());
    }
}

// Se resuelve al cargar: los endpoints sólo hacen require de este archivo.
applyCorsHeaders();
