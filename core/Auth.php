<?php
class Auth {

    /** Motivo del último intento de login fallido (para el mensaje al usuario) */
    private static string $loginError = '';

    public static function lastLoginError(): string {
        return self::$loginError;
    }

    /** Attempt login. Returns true on success, false on failure. */
    public static function login(string $email, string $password): bool {
        require_once MODELS_PATH . '/User.php';
        self::$loginError = '';
        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user || !$user['active']) {
            self::$loginError = 'Credenciales inválidas o cuenta inactiva.';
            return false;
        }
        if (!password_verify($password, $user['password'])) {
            self::$loginError = 'Credenciales inválidas o cuenta inactiva.';
            return false;
        }

        // El portal es exclusivamente administrativo: se rechaza aquí, antes de
        // abrir sesión, para no dejar sesiones a medias de gente sin acceso.
        $isAdmin = ($user['app_role'] ?? '') === APP_ROLE_ADMINISTRADOR
                || ($user['role'] ?? '') === ROLE_SUPER_ADMIN;
        if (!$isAdmin) {
            self::$loginError = 'Este portal es exclusivo para personal administrativo. '
                              . 'Si eres persona afiliada o asesora, usa la aplicación móvil CDHQ.';
            self::logActivityFor((int)$user['id'], 'LOGIN_DENEGADO', 'users', (int)$user['id'],
                'Cuenta sin permisos administrativos intentó entrar al portal web');
            return false;
        }

        // Regenerate session ID on login to prevent fixation
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email']= $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_app_role'] = $user['app_role'] ?? APP_ROLE_USUARIO;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time']= time();

        // Log the login activity
        self::logActivity('LOGIN', 'users', $user['id'], 'User logged in');

        return true;
    }

    /** Destroy session and redirect to login */
    public static function logout(): void {
        self::logActivity('LOGOUT', 'users', $_SESSION['user_id'] ?? 0, 'User logged out');
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    /** Check if current session is authenticated */
    public static function check(): bool {
        if (empty($_SESSION['logged_in'])) return false;
        // Session timeout check
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
            self::logout();
        }
        return true;
    }

    /** Check if logged-in user has a specific role */
    public static function hasRole(string ...$roles): bool {
        if (!self::check()) return false;
        return in_array($_SESSION['user_role'] ?? '', $roles, true);
    }

    /**
     * ¿La sesión pertenece a un administrador del portal?
     *
     * El portal web es exclusivamente administrativo. Se acepta el rol de la
     * app (`app_role = ADMINISTRADOR`) y también `role = SUPER_ADMIN` para no
     * dejar fuera a las cuentas creadas antes de la migración a cdhq_mobile.
     */
    public static function isAdmin(): bool {
        if (!self::check()) return false;
        return ($_SESSION['user_app_role'] ?? '') === APP_ROLE_ADMINISTRADOR
            || ($_SESSION['user_role'] ?? '') === ROLE_SUPER_ADMIN;
    }

    /** Return current user data from session */
    public static function currentUser(): ?array {
        if (!self::check()) return null;
        return [
            'id'       => $_SESSION['user_id']   ?? null,
            'name'     => $_SESSION['user_name'] ?? '',
            'email'    => $_SESSION['user_email']?? '',
            'role'     => $_SESSION['user_role'] ?? '',
            'app_role' => $_SESSION['user_app_role'] ?? '',
            'is_admin' => self::isAdmin(),
        ];
    }

    /** Redirect to login if not authenticated */
    public static function requireAuth(): void {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    /**
     * Puerta única del portal: todo módulo administrativo pasa por aquí.
     *
     * El portal web es exclusivamente para administradores; las personas
     * usuarias y las asesoras operan desde la aplicación móvil.
     */
    public static function requireAdmin(): void {
        self::requireAuth();
        if (!self::isAdmin()) {
            self::logActivity('ACCESO_DENEGADO', 'users', (int)($_SESSION['user_id'] ?? 0),
                'Intento de acceso al portal administrativo sin permisos');
            self::denyAccess();
        }
    }

    /** Require specific role(s) or deny access */
    public static function requireRole(string ...$roles): void {
        self::requireAuth();
        if (!self::hasRole(...$roles) && !self::isAdmin()) {
            self::denyAccess();
        }
    }

    /** Página de acceso denegado con la identidad del Consejo */
    private static function denyAccess(): never {
        http_response_code(403);
        $siteUrl = defined('SITE_URL') ? SITE_URL : '';
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
           . '<title>Acceso denegado – CDHQ</title>'
           . '<script src="https://cdn.tailwindcss.com"></script>'
           . '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">'
           . '<style>*{font-family:Inter,sans-serif}</style></head>'
           . '<body class="min-h-screen flex items-center justify-center px-4" style="background:linear-gradient(165deg,#ffffff 0%,#f2fbfd 50%,#edfaf1 100%)">'
           . '<div class="max-w-md w-full bg-white rounded-3xl border border-[#dbf3fa] shadow-xl p-9 text-center">'
           . '<div class="w-16 h-16 mx-auto rounded-full bg-amber-500 text-white flex items-center justify-center text-3xl font-bold">!</div>'
           . '<h1 class="text-2xl font-bold text-slate-800 mt-5">Acceso restringido</h1>'
           . '<p class="text-slate-500 text-sm mt-3 leading-relaxed">Este portal es exclusivo para personal administrativo del Consejo. '
           . 'Si eres persona afiliada o asesora, usa la aplicación móvil CDHQ.</p>'
           . '<a href="' . htmlspecialchars(BASE_URL) . '/logout" class="inline-block mt-7 bg-[#0e85a5] hover:bg-[#0c7290] text-white font-semibold px-6 py-3 rounded-full transition-colors">Cerrar sesión</a>'
           . ($siteUrl ? '<p class="mt-5"><a href="' . htmlspecialchars($siteUrl) . '/" class="text-sm text-[#0e85a5] hover:underline">Ir al sitio público</a></p>' : '')
           . '</div></body></html>';
        exit;
    }

    /** Generate CSRF token and store in session */
    public static function generateCsrf(): string {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /** Validate submitted CSRF token */
    public static function validateCsrf(): bool {
        $token = $_POST[CSRF_TOKEN_NAME] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
    }

    /** Bitácora atribuida a un usuario concreto (aún sin sesión abierta) */
    public static function logActivityFor(int $userId, string $action, string $entity, int $entityId, string $details = ''): void {
        try {
            Database::getInstance()->execute(
                "INSERT INTO activity_log (user_id, action, entity, entity_id, details, ip, created_at)
                 VALUES (:uid, :action, :entity, :eid, :details, :ip, NOW())",
                [
                    ':uid'     => $userId ?: null,
                    ':action'  => $action,
                    ':entity'  => $entity,
                    ':eid'     => $entityId,
                    ':details' => $details,
                    ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                ]
            );
        } catch (Throwable $e) {
            // La bitácora nunca debe romper el flujo principal
        }
    }

    /** Log an activity to the activity_log table */
    public static function logActivity(string $action, string $entity, int $entityId, string $details = ''): void {
        try {
            $db = Database::getInstance();
            $db->execute(
                "INSERT INTO activity_log (user_id, action, entity, entity_id, details, ip, created_at)
                 VALUES (:uid, :action, :entity, :eid, :details, :ip, NOW())",
                [
                    ':uid'     => $_SESSION['user_id'] ?? 0,
                    ':action'  => $action,
                    ':entity'  => $entity,
                    ':eid'     => $entityId,
                    ':details' => $details,
                    ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                ]
            );
        } catch (Exception $e) {
            // Silently fail so logging never breaks the main flow
        }
    }
}
