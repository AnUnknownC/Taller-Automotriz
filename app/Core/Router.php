<?php
declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\Middleware;
use App\Middleware\RoleMiddleware;
use RuntimeException;

/**
 * Enrutador: asocia "MÉTODO /ruta" con [Controlador, método] y su cadena de middleware.
 *   $router->get('/ordenes/{id}', [OrdenController::class, 'show'], ['auth', 'role:asesor']);
 * Todo POST pasa primero por CsrfMiddleware (RS-05), sin excepción.
 */
final class Router
{
    private array $routes = [];

    public function get(string $path, array $action, array $middleware = []): void
    {
        $this->add('GET', $path, $action, $middleware);
    }

    public function post(string $path, array $action, array $middleware = []): void
    {
        $this->add('POST', $path, $action, $middleware);
    }

    private function add(string $method, string $path, array $action, array $middleware): void
    {
        // {id} solo acepta números: /ordenes/abc no llega al controlador
        $regex = '#^' . preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[0-9]+)', rtrim($path, '/') ?: '/') . '$#';
        $this->routes[] = compact('method', 'regex', 'action', 'middleware');
    }

    public function dispatch(): void
    {
        $method = Request::method();
        $path = Request::path();
        $methodMismatch = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $methodMismatch = true;
                continue;
            }

            if ($method === 'POST') {
                (new CsrfMiddleware())->handle();
            }
            foreach ($route['middleware'] as $name) {
                $this->resolve($name)->handle();
            }

            $params = array_map('intval', array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
            [$class, $action] = $route['action'];
            (new $class())->$action(...array_values($params));
            return;
        }

        throw $methodMismatch ? new HttpException(405) : new HttpException(404);
    }

    /** 'auth' | 'guest' | 'role:administrador,asesor' */
    private function resolve(string $name): Middleware
    {
        [$key, $args] = array_pad(explode(':', $name, 2), 2, '');
        switch ($key) {
            case 'auth':
                return new AuthMiddleware();
            case 'guest':
                return new GuestMiddleware();
            case 'role':
                return new RoleMiddleware(array_filter(explode(',', $args)));
        }
        throw new RuntimeException('Middleware desconocido: ' . $name);
    }
}
