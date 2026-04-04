<?php
require_once CORE_PATH . '/Controller.php';

class AuthController extends Controller {

    public function showLogin(): void {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }
        $this->view('auth/login', ['title' => 'Iniciar Sesión']);
    }

    public function login(): void {
        $this->verifyCsrf();
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $this->flash('error', 'Por favor ingresa tu correo y contraseña.');
            $this->redirect('login');
        }

        if (Auth::login($email, $password)) {
            $this->redirect('dashboard');
        } else {
            $this->flash('error', 'Credenciales inválidas o cuenta inactiva.');
            $this->redirect('login');
        }
    }

    public function logout(): void {
        Auth::logout();
    }
}
