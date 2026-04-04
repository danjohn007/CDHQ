<?php
class Auth {

    /** Attempt login. Returns true on success, false on failure. */
    public static function login(string $email, string $password): bool {
        require_once MODELS_PATH . '/User.php';
        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user || !$user['active']) {
            return false;
        }
        if (!password_verify($password, $user['password'])) {
            return false;
        }

        // Regenerate session ID on login to prevent fixation
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email']= $user['email'];
        $_SESSION['user_role'] = $user['role'];
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

    /** Return current user data from session */
    public static function currentUser(): ?array {
        if (!self::check()) return null;
        return [
            'id'    => $_SESSION['user_id']   ?? null,
            'name'  => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email']?? '',
            'role'  => $_SESSION['user_role'] ?? '',
        ];
    }

    /** Redirect to login if not authenticated; optionally require a role */
    public static function requireAuth(): void {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    /** Require specific role(s) or redirect with 403 */
    public static function requireRole(string ...$roles): void {
        self::requireAuth();
        if (!self::hasRole(...$roles)) {
            http_response_code(403);
            echo '<h1>403 – Acceso Denegado</h1>';
            exit;
        }
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
