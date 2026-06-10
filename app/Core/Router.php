<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private ?array $lastRoute = null;

    public function get(string $pattern, array $handler): static
    {
        return $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, array $handler): static
    {
        return $this->addRoute('POST', $pattern, $handler);
    }

    public function addRoute(string $method, string $pattern, array $handler): static
    {
        $this->routes[] = [
            'method'     => $method,
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware' => [],
        ];
        $this->lastRoute = &$this->routes[array_key_last($this->routes)];
        return $this;
    }

    public function middleware(array $middleware): static
    {
        if ($this->lastRoute !== null) {
            $this->lastRoute['middleware'] = $middleware;
        }
        return $this;
    }

    public function dispatch(): void
    {
        $request = new Request();
        $method  = $request->method();
        $path    = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            // Middleware
            foreach ($route['middleware'] as $mw) {
                if ($mw === 'auth') {
                    if (!Auth::check()) {
                        Response::redirect('/backend/login');
                    }
                } elseif (str_starts_with($mw, 'role:')) {
                    $role = substr($mw, 5);
                    if (!Auth::hasRole($role)) {
                        Response::forbidden();
                    }
                }
            }

            [$class, $action] = $route['handler'];
            $controller = new $class();
            $controller->$action($params);
            return;
        }

        Response::notFound();
    }

    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        return array_filter($matches, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
    }
}
