<?php
abstract class Controller {
    protected string $viewPath;

    public function __construct() {
        $this->viewPath = VIEWS_PATH;
    }

    /** Render a view with optional data */
    protected function view(string $view, array $data = []): void {
        // Make BASE_URL and current user available in every view
        $data['base_url']      = BASE_URL;
        $data['current_user']  = Auth::currentUser();
        $data['app_name']      = APP_NAME;

        extract($data, EXTR_SKIP);

        $viewFile = $this->viewPath . '/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException("View not found: {$viewFile}");
        }
        require $viewFile;
    }

    /** Render just a partial (no layout) */
    protected function partial(string $partial, array $data = []): void {
        $data['base_url']     = BASE_URL;
        $data['current_user'] = Auth::currentUser();
        extract($data, EXTR_SKIP);
        $file = $this->viewPath . '/layout/' . $partial . '.php';
        if (file_exists($file)) require $file;
    }

    /** Redirect to a URL */
    protected function redirect(string $path): void {
        $url = (strpos($path, 'http') === 0) ? $path : BASE_URL . '/' . ltrim($path, '/');
        header('Location: ' . $url);
        exit;
    }

    /** Send a JSON response */
    protected function json(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Flash message: store in session */
    protected function flash(string $type, string $message): void {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    /** CSRF-protect POST handlers */
    protected function verifyCsrf(): void {
        if (!Auth::validateCsrf()) {
            $this->flash('error', 'Token de seguridad inválido. Por favor recarga la página.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }

    /** 404 not found page */
    public function notFound(): void {
        http_response_code(404);
        echo '<!DOCTYPE html><html><head><title>404</title>
        <script src="https://cdn.tailwindcss.com"></script></head>
        <body class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="text-center">
        <h1 class="text-6xl font-bold text-indigo-600">404</h1>
        <p class="text-2xl text-gray-700 mt-4">Página no encontrada</p>
        <a href="' . BASE_URL . '" class="mt-6 inline-block bg-indigo-600 text-white px-6 py-2 rounded-lg">Inicio</a>
        </div></body></html>';
    }
}
