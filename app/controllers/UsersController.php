<?php
require_once CORE_PATH . '/Controller.php';
require_once MODELS_PATH . '/User.php';

class UsersController extends Controller {

    public function index(): void {
        Auth::requireRole(ROLE_SUPER_ADMIN);
        $userModel = new User();
        $users = $userModel->allWithStats();
        $this->view('users/index', ['title' => 'Usuarios', 'users' => $users]);
    }

    public function create(): void {
        Auth::requireRole(ROLE_SUPER_ADMIN);
        $this->view('users/create', ['title' => 'Nuevo Usuario', 'roles' => ALL_ROLES]);
    }

    public function store(): void {
        Auth::requireRole(ROLE_SUPER_ADMIN);
        $this->verifyCsrf();

        $data = $this->sanitizeUserInput();
        if (!$data) return;

        $userModel = new User();
        if ($userModel->findByEmail($data['email'])) {
            $this->flash('error', 'Ya existe un usuario con ese correo.');
            $this->redirect('users/create');
        }

        $userModel->createUser($data);
        Auth::logActivity('CREATE', 'users', 0, "Created user: {$data['email']}");
        $this->flash('success', 'Usuario creado correctamente.');
        $this->redirect('users');
    }

    public function edit(string $id): void {
        Auth::requireRole(ROLE_SUPER_ADMIN);
        $userModel = new User();
        $user = $userModel->find((int)$id);
        if (!$user) { $this->flash('error', 'Usuario no encontrado.'); $this->redirect('users'); }
        $this->view('users/edit', ['title' => 'Editar Usuario', 'user' => $user, 'roles' => ALL_ROLES]);
    }

    public function update(string $id): void {
        Auth::requireRole(ROLE_SUPER_ADMIN);
        $this->verifyCsrf();
        $userModel = new User();
        $data = $this->sanitizeUserInput(false);
        if (!$data) return;
        $userModel->updateUser((int)$id, $data);
        Auth::logActivity('UPDATE', 'users', (int)$id, "Updated user ID {$id}");
        $this->flash('success', 'Usuario actualizado.');
        $this->redirect('users');
    }

    public function toggle(string $id): void {
        Auth::requireRole(ROLE_SUPER_ADMIN);
        $this->verifyCsrf();
        $userModel = new User();
        $userModel->toggleActive((int)$id);
        Auth::logActivity('TOGGLE', 'users', (int)$id, "Toggled user ID {$id}");
        $this->flash('success', 'Estado de usuario actualizado.');
        $this->redirect('users');
    }

    public function destroy(string $id): void {
        Auth::requireRole(ROLE_SUPER_ADMIN);
        $this->verifyCsrf();
        $current = Auth::currentUser();
        if ((int)$id === (int)$current['id']) {
            $this->flash('error', 'No puedes eliminar tu propio usuario.');
            $this->redirect('users');
        }
        $userModel = new User();
        $userModel->delete((int)$id);
        Auth::logActivity('DELETE', 'users', (int)$id, "Deleted user ID {$id}");
        $this->flash('success', 'Usuario eliminado.');
        $this->redirect('users');
    }

    private function sanitizeUserInput(bool $requirePassword = true): ?array {
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = trim($_POST['role']  ?? '');
        $pass  = trim($_POST['password'] ?? '');

        if (!$name || !$email || !$role) {
            $this->flash('error', 'Nombre, correo y rol son obligatorios.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? 'users');
            return null;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Correo electrónico no válido.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? 'users');
            return null;
        }
        if ($requirePassword && !$pass) {
            $this->flash('error', 'La contraseña es obligatoria.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? 'users');
            return null;
        }
        if (!in_array($role, ALL_ROLES, true)) {
            $this->flash('error', 'Rol no válido.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? 'users');
            return null;
        }
        $data = [
            'name'   => $name,
            'email'  => $email,
            'role'   => $role,
            'active' => isset($_POST['active']) ? 1 : 0,
        ];
        if ($pass) $data['password'] = $pass;
        return $data;
    }
}
