<?php
declare(strict_types=1);
namespace App\Core;

use Throwable;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function delete(string $path, array $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        $this->routes[] = compact('method', 'path', 'handler', 'middleware');
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method())
                continue;

            $pattern = '#^' . preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $route['path']) . '$#';
            if (!preg_match($pattern, $request->path(), $matches))
                continue;

            $request->setRouteParams(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));

            foreach ($route['middleware'] as $definition) {
                [$class, $argument] = array_pad(explode(':', $definition, 2), 2, null);
                (new $class())->handle($request, $argument);
            }

            [$controller, $method] = $route['handler'];
            (new $controller())->{$method}($request);
            return;
        }
        http_response_code(404);
        View::render(
            'pages/errors/404',
            ['title' => 'Página não encontrada'],
            'auth'
        );
    }
}
