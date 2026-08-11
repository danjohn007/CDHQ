<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/User.php';

/**
 * Usuarios y roles.
 *
 * El rol que manda es `app_role` (USUARIO | ASESOR | ADMINISTRADOR), el mismo
 * que usa la aplicación móvil. `role` se conserva por compatibilidad con las
 * cuentas anteriores a la migración.
 */
class UsersController extends Controller {

    private const PER_PAGE = 30;

    public function index(): void {
        Auth::requireAdmin();

        $filters = [
            'q'        => trim($_GET['q'] ?? ''),
            'app_role' => $_GET['app_role'] ?? '',
            'active'   => $_GET['active'] ?? '',
        ];
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $model = new User();
        $total = $model->countWithFilters($filters);

        $this->view('users/index', [
            'title'   => 'Usuarios y roles',
            'users'   => $model->listWithFilters($filters, self::PER_PAGE, $offset),
            'filters' => $filters,
            'page'    => $page,
            'total'   => $total,
            'pages'   => max(1, (int) ceil($total / self::PER_PAGE)),
            'counts'  => $model->countsByAppRole(),
        ]);
    }

    public function create(): void {
        Auth::requireAdmin();
        $this->view('users/create', ['title' => 'Nuevo usuario']);
    }

    public function store(): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $appRole  = $_POST['app_role'] ?? APP_ROLE_USUARIO;

        if ($name === '' || $email === '' || $password === '') {
            $this->flash('error', 'Nombre, correo y contraseña son obligatorios.');
            $this->redirect('users/create');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'El correo no tiene un formato válido.');
            $this->redirect('users/create');
        }
        if (strlen($password) < 8) {
            $this->flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            $this->redirect('users/create');
        }
        if (!in_array($appRole, ALL_APP_ROLES, true)) {
            $this->flash('error', 'Rol no válido.');
            $this->redirect('users/create');
        }

        $model = new User();
        if ($model->findByEmail($email)) {
            $this->flash('error', 'Ya existe una cuenta con ese correo.');
            $this->redirect('users/create');
        }

        $id = $model->createUser([
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
            'app_role' => $appRole,
            // `role` heredado: se mantiene coherente con el rol de la app
            'role'     => $appRole === APP_ROLE_ADMINISTRADOR ? ROLE_SUPER_ADMIN : ROLE_AFILIADO,
            'active'   => 1,
        ]);

        Auth::logActivity('CREAR_USUARIO', 'users', $id, "{$email} como {$appRole}");
        $this->flash('success', 'Usuario creado.');
        $this->redirect('users');
    }

    public function edit(string $id): void {
        Auth::requireAdmin();
        $model = new User();
        $user  = $model->find((int) $id);
        if (!$user) {
            $this->flash('error', 'Usuario no encontrado.');
            $this->redirect('users');
        }
        $this->view('users/edit', ['title' => 'Editar usuario', 'user' => $user]);
    }

    public function update(string $id): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model = new User();
        $user  = $model->find((int) $id);
        if (!$user) {
            $this->flash('error', 'Usuario no encontrado.');
            $this->redirect('users');
        }

        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $appRole = $_POST['app_role'] ?? $user['app_role'];
        $motivo  = trim($_POST['reason'] ?? '');

        if ($name === '' || $email === '') {
            $this->flash('error', 'Nombre y correo son obligatorios.');
            $this->redirect("users/{$id}/edit");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'El correo no tiene un formato válido.');
            $this->redirect("users/{$id}/edit");
        }
        if (!in_array($appRole, ALL_APP_ROLES, true)) {
            $this->flash('error', 'Rol no válido.');
            $this->redirect("users/{$id}/edit");
        }

        $otro = $model->findByEmail($email);
        if ($otro && (int) $otro['id'] !== (int) $id) {
            $this->flash('error', 'Ese correo ya pertenece a otra cuenta.');
            $this->redirect("users/{$id}/edit");
        }

        // No dejar el sistema sin administradores
        if ($user['app_role'] === APP_ROLE_ADMINISTRADOR && $appRole !== APP_ROLE_ADMINISTRADOR
            && $model->isLastActiveAdmin((int) $id)) {
            $this->flash('error', 'No puedes quitar el último administrador activo del sistema.');
            $this->redirect("users/{$id}/edit");
        }

        $model->updateUser((int) $id, [
            'name'     => $name,
            'email'    => $email,
            'app_role' => $appRole,
            'role'     => $appRole === APP_ROLE_ADMINISTRADOR ? ROLE_SUPER_ADMIN : $user['role'],
            'password' => (string) ($_POST['password'] ?? ''),
        ]);

        $detalle = "{$email}";
        if ($user['app_role'] !== $appRole) {
            $detalle .= " · rol {$user['app_role']} → {$appRole}";
        }
        if ($motivo !== '') {
            $detalle .= " · motivo: {$motivo}";
        }
        Auth::logActivity('EDITAR_USUARIO', 'users', (int) $id, $detalle);
        $this->flash('success', 'Usuario actualizado.');
        $this->redirect('users');
    }

    public function toggle(string $id): void {
        Auth::requireAdmin();
        $this->verifyCsrf();

        $model = new User();
        $user  = $model->find((int) $id);
        if (!$user) {
            $this->flash('error', 'Usuario no encontrado.');
            $this->redirect('users');
        }

        $yo = (int) Auth::currentUser()['id'];
        if ((int) $id === $yo) {
            $this->flash('error', 'No puedes desactivar tu propia cuenta.');
            $this->redirect('users');
        }
        if ($user['active'] && $model->isLastActiveAdmin((int) $id)) {
            $this->flash('error', 'No puedes desactivar el último administrador activo.');
            $this->redirect('users');
        }

        $model->toggleActive((int) $id);
        Auth::logActivity('CAMBIAR_ESTADO_USUARIO', 'users', (int) $id,
            $user['email'] . ($user['active'] ? ' desactivado' : ' activado'));
        $this->flash('success', $user['active'] ? 'Cuenta desactivada.' : 'Cuenta activada.');
        $this->redirect('users');
    }
}
