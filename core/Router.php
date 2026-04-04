<?php
class Router {
    private array $routes = [];

    public function add(string $method, string $pattern, string $controller, string $action): void {
        $this->routes[] = compact('method', 'pattern', 'controller', 'action');
    }

    public function get(string $pattern, string $controller, string $action): void {
        $this->add('GET', $pattern, $controller, $action);
    }

    public function post(string $pattern, string $controller, string $action): void {
        $this->add('POST', $pattern, $controller, $action);
    }

    public function dispatch(string $url): void {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $url = trim($url, '/');

        foreach ($this->routes as $route) {
            if (strtoupper($route['method']) !== strtoupper($requestMethod)) {
                continue;
            }
            $pattern = $this->buildPattern($route['pattern']);
            if (preg_match($pattern, $url, $matches)) {
                // Remove full match, keep named/numbered captures
                array_shift($matches);
                $this->invoke($route['controller'], $route['action'], $matches);
                return;
            }
        }

        // 404 fallback
        http_response_code(404);
        $this->invoke('DashboardController', 'notFound', []);
    }

    private function buildPattern(string $pattern): string {
        $pattern = trim($pattern, '/');
        // Convert :param to named capture group
        $pattern = preg_replace('/:([a-zA-Z_]+)/', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $pattern . '$#';
    }

    private function invoke(string $controllerName, string $action, array $params): void {
        $file = CONTROLLERS_PATH . '/' . $controllerName . '.php';
        if (!file_exists($file)) {
            $this->show404();
            return;
        }
        require_once $file;
        if (!class_exists($controllerName)) {
            $this->show404();
            return;
        }
        $controller = new $controllerName();
        if (!method_exists($controller, $action)) {
            $this->show404();
            return;
        }
        call_user_func_array([$controller, $action], $params);
    }

    private function show404(): void {
        http_response_code(404);
        echo '<h1>404 – Page Not Found</h1>';
    }
}
